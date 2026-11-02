<?php

use App\Services\PermitItemAuditService;
use App\Services\PermitItemFinancialService;
use App\Http\Controllers\Association\PermitWorkflowController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$assert(Schema::hasTable('permit_item_events'), 'permit_item_events table is missing');
$assert(Schema::hasTable('permit_courier_missions'), 'permit_courier_missions table is missing');
$assert(Schema::hasColumn('wallet_transactions', 'permit_request_item_id'), 'item ledger link is missing');

DB::beginTransaction();
try {
    $wallet = DB::table('wallets')
        ->whereRaw('balance - blocked_balance >= 200')
        ->whereExists(function ($query) {
            $query->selectRaw('1')
                ->from('permit_requests')
                ->whereColumn('permit_requests.company_id', 'wallets.company_id');
        })
        ->orderBy('id')
        ->lockForUpdate()
        ->first();
    $assert((bool) $wallet, 'No wallet with enough test balance');

    $request = DB::table('permit_requests')->where('company_id', $wallet->company_id)->first();
    $assert((bool) $request, 'No permit request for smoke-test company');
    $countryId = DB::table('countries')->value('id');
    $assert((bool) $countryId, 'No country exists');

    $makeItem = static function () use ($request, $countryId): int {
        return DB::table('permit_request_items')->insertGetId([
            'permit_request_id' => $request->id,
            'country_id' => $countryId,
            'permit_type' => 'smoke-test',
            'operation_type' => 'smoke-test',
            'price' => 100,
            'allocation_status' => 'pending',
            'item_status' => 'pending',
            'financial_status' => 'reserved',
            'reserved_amount' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    };

    $firstId = $makeItem();
    $secondId = $makeItem();
    $finance = app(PermitItemFinancialService::class);
    $audit = app(PermitItemAuditService::class);

    $initialBalance = (float) $wallet->balance;
    $initialBlocked = (float) $wallet->blocked_balance;
    $finance->reserve((int) $wallet->company_id, $firstId, 100, 'smoke reserve first');
    $finance->reserve((int) $wallet->company_id, $secondId, 100, 'smoke reserve second');

    $afterReserve = DB::table('wallets')->where('id', $wallet->id)->first();
    $assert((float) $afterReserve->balance === $initialBalance, 'Reservation changed total balance');
    $assert((float) $afterReserve->blocked_balance === $initialBlocked + 200, 'Reservation did not block per item');

    $first = DB::table('permit_request_items')->where('id', $firstId)->first();
    $finance->release($first, (int) $wallet->company_id, 'smoke reject first');
    $afterRelease = DB::table('wallets')->where('id', $wallet->id)->first();
    $assert((float) $afterRelease->balance === $initialBalance, 'Reject changed total balance');
    $assert((float) $afterRelease->blocked_balance === $initialBlocked + 100, 'Reject did not release only one item');

    $second = DB::table('permit_request_items')->where('id', $secondId)->first();
    $finance->settle($second, (int) $wallet->company_id, 'smoke print second');
    $afterSettle = DB::table('wallets')->where('id', $wallet->id)->first();
    $assert((float) $afterSettle->balance === $initialBalance - 100, 'Print did not deduct exactly one item');
    $assert((float) $afterSettle->blocked_balance === $initialBlocked, 'Print did not clear remaining reservation');

    $audit->record($second, 'smoke_event', 'smoke audit');
    $assert(
        DB::table('permit_item_events')->where('permit_request_item_id', $secondId)->where('event_type', 'smoke_event')->exists(),
        'Audit event was not persisted'
    );
    $assert(
        DB::table('wallet_transactions')->whereIn('permit_request_item_id', [$firstId, $secondId])->count() === 4,
        'Expected four item-level ledger entries'
    );

    $associationUserId = DB::table('users')
        ->join('roles', 'roles.id', '=', 'users.role_id')
        ->where('roles.name', 'association')
        ->value('users.id');
    $assert((bool) $associationUserId, 'No association user for render test');
    Auth::loginUsingId($associationUserId);
    View::share('errors', new ViewErrorBag());
    $httpRequest = Request::create('/web/association/permits', 'GET', ['scope' => 'active']);
    app()->instance('request', $httpRequest);
    $controller = app(PermitWorkflowController::class);

    DB::table('permit_requests')->where('id', $request->id)->update(['request_type' => 'new']);
    $decisionRequest = Request::create("/web/association/permits/{$firstId}/decision", 'POST', ['decision' => 'approved']);
    app()->instance('request', $decisionRequest);
    $controller->decide($decisionRequest, $firstId, $audit, $finance);
    $assert(
        DB::table('permit_request_items')->where('id', $firstId)->value('item_status') === 'ready_to_issue',
        'New request did not bypass CITS and move directly to issuance'
    );

    DB::table('permit_requests')->where('id', $request->id)->update(['request_type' => 'renewal']);
    DB::table('permit_request_items')->where('id', $firstId)->update(['item_status' => 'pending']);
    $renewalDecisionRequest = Request::create("/web/association/permits/{$firstId}/decision", 'POST', ['decision' => 'approved']);
    app()->instance('request', $renewalDecisionRequest);
    $controller->decide($renewalDecisionRequest, $firstId, $audit, $finance);
    $assert(
        DB::table('permit_request_items')->where('id', $firstId)->value('item_status') === 'ready_to_issue',
        'Renewal request did not move directly to issuance'
    );

    app()->instance('request', $httpRequest);
    $indexHtml = $controller->index($httpRequest)->render();
    $showHtml = $controller->show($secondId)->render();
    $assert(str_contains($indexHtml, 'کارتابل یکپارچه'), 'Unified workbench did not render');
    $assert(str_contains($showHtml, 'گردش کار و مکاتبات پرونده'), 'Permit dossier did not render');

    $workflowItemId = $makeItem();
    DB::table('permit_request_items')->where('id', $workflowItemId)->update([
        'item_status' => 'issued',
        'd_serial_number' => '88888888',
    ]);
    $workflowMarker = $request->d_code.'-'.$workflowItemId;

    $activeBeforePickup = $controller->index($httpRequest)->render();
    $awaitingPickupRequest = Request::create('/web/association/permits', 'GET', ['scope' => 'awaiting_pickup']);
    app()->instance('request', $awaitingPickupRequest);
    $awaitingPickupHtml = $controller->index($awaitingPickupRequest)->render();
    $assert(!str_contains($activeBeforePickup, $workflowMarker), 'Printed permit without a courier stayed in active workbench');
    $assert(str_contains($awaitingPickupHtml, $workflowMarker), 'Printed permit without a courier was not moved to awaiting pickup');

    $missionId = DB::table('permit_courier_missions')->insertGetId([
        'permit_request_item_id' => $workflowItemId,
        'company_id' => $request->company_id,
        'direction' => 'association_to_company',
        'purpose' => 'issued_permit_pickup',
        'status' => 'pending',
        'courier_name' => 'پیک تست',
        'courier_mobile' => '09120000000',
        'courier_national_code' => '0000000000',
        'otp_hash' => hash('sha256', '12345'),
        'delivery_code' => '12345',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    app()->instance('request', $httpRequest);
    $activeWithPickup = $controller->index($httpRequest)->render();
    $circulationBeforePickup = $controller->index(Request::create('/web/association/permits', 'GET', ['scope' => 'circulation']))->render();
    $assert(str_contains($activeWithPickup, $workflowMarker), 'Courier nomination did not return permit to active workbench');
    $assert(!str_contains($circulationBeforePickup, $workflowMarker), 'Permit with pending courier appeared in circulation');

    DB::table('permit_courier_missions')->where('id', $missionId)->update([
        'status' => 'completed',
        'completed_at' => now(),
        'updated_at' => now(),
    ]);
    $activeAfterPickup = $controller->index($httpRequest)->render();
    $circulationAfterPickup = $controller->index(Request::create('/web/association/permits', 'GET', ['scope' => 'circulation']))->render();
    $assert(!str_contains($activeAfterPickup, $workflowMarker), 'Delivered permit stayed in active workbench');
    $assert(str_contains($circulationAfterPickup, $workflowMarker), 'Delivered permit did not move to circulation');

    DB::table('permit_request_items')->where('id', $workflowItemId)->update([
        'company_cits_responded_at' => now(),
        'association_cits_response_seen_at' => null,
        'updated_at' => now(),
    ]);
    $activeWithCompanyResponse = $controller->index($httpRequest)->render();
    $circulationWithCompanyResponse = $controller->index(Request::create('/web/association/permits', 'GET', ['scope' => 'circulation']))->render();
    $assert(str_contains($activeWithCompanyResponse, $workflowMarker), 'New company response did not return permit to active workbench');
    $assert(!str_contains($circulationWithCompanyResponse, $workflowMarker), 'Permit with unread company response stayed in circulation');

    DB::table('permit_request_items')->where('id', $workflowItemId)->update([
        'association_cits_response_seen_at' => now()->addSecond(),
        'updated_at' => now(),
    ]);
    $activeAfterResponse = $controller->index($httpRequest)->render();
    $circulationAfterResponse = $controller->index(Request::create('/web/association/permits', 'GET', ['scope' => 'circulation']))->render();
    $assert(!str_contains($activeAfterResponse, $workflowMarker), 'Handled company response stayed in active workbench');
    $assert(str_contains($circulationAfterResponse, $workflowMarker), 'Handled company response did not return permit to circulation');

    $circulationRequest = Request::create('/web/association/permits', 'GET', ['scope' => 'circulation']);
    app()->instance('request', $circulationRequest);
    $circulationHtml = $controller->index($circulationRequest)->render();
    $assert(str_contains($circulationHtml, 'دوزوله‌های در گردش'), 'Circulation workbench did not render');

    DB::table('permit_request_items')->where('id', $firstId)->update(['item_status' => 'rejected']);
    DB::table('permit_request_items')->where('id', $secondId)->update([
        'item_status' => 'issued',
        'd_serial_number' => '99999999',
    ]);
    DB::table('permit_request_items')
        ->where('permit_request_id', $request->id)
        ->whereNotIn('id', [$firstId, $secondId])
        ->update(['item_status' => 'rejected']);
    $syncParent = new ReflectionMethod(PermitWorkflowController::class, 'syncParent');
    $syncParent->invoke($controller, (int) $request->id);
    $assert(
        DB::table('permit_requests')->where('id', $request->id)->value('status') === 'partially_issued',
        'Mixed issued/rejected items did not produce partially_issued parent'
    );

    echo "PASS: migration, finance, audit, direct issuance routing, mixed-route parent and rendered workbench/dossier\n";
} finally {
    DB::rollBack();
}
