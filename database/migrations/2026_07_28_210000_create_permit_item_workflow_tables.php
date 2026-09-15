<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permit_request_items', function (Blueprint $table) {
            $table->string('financial_status', 30)->default('reserved')->after('price')->index();
            $table->decimal('reserved_amount', 15, 2)->default(0)->after('financial_status');
            $table->timestamp('financial_settled_at')->nullable()->after('reserved_amount');
            $table->timestamp('financial_released_at')->nullable()->after('financial_settled_at');
            $table->timestamp('reviewed_at')->nullable()->after('rejection_reason');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('reviewed_by_user_id');
            $table->foreignId('rejected_by_user_id')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            $table->string('cits_status', 30)->default('not_started')->after('rejected_by_user_id')->index();
            $table->text('cits_message')->nullable()->after('cits_status');
            $table->string('cits_error_image')->nullable()->after('cits_message');
            $table->timestamp('cits_attempted_at')->nullable()->after('cits_error_image');
            $table->string('disposition_type', 30)->nullable()->after('cits_attempted_at')->index();
            $table->text('disposition_note')->nullable()->after('disposition_type');
            $table->foreignId('disposition_changed_by_user_id')->nullable()->after('disposition_note')->constrained('users')->nullOnDelete();
            $table->timestamp('disposition_changed_at')->nullable()->after('disposition_changed_by_user_id');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->foreignId('permit_request_item_id')->nullable()->after('dozbalagh_item_id')
                ->constrained('permit_request_items')->nullOnDelete();
            $table->index(['permit_request_item_id', 'action_type'], 'wallet_tx_permit_item_action_idx');
        });

        Schema::create('permit_item_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_request_id')->constrained('permit_requests')->cascadeOnDelete();
            $table->foreignId('permit_request_item_id')->constrained('permit_request_items')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 30)->default('system');
            $table->string('event_type', 80)->index();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40)->nullable();
            $table->text('description')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->timestamps();
            $table->index(['permit_request_item_id', 'created_at'], 'permit_item_events_timeline_idx');
        });

        Schema::create('permit_courier_missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_request_item_id')->constrained('permit_request_items')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->enum('direction', ['association_to_company', 'company_to_association']);
            $table->string('purpose', 40);
            $table->string('status', 30)->default('pending')->index();
            $table->string('courier_name');
            $table->string('courier_mobile', 30);
            $table->string('courier_national_code', 30)->nullable();
            $table->string('courier_vehicle_plate', 100)->nullable();
            $table->string('identity_image')->nullable();
            $table->string('otp_hash', 64);
            $table->timestamp('otp_sent_at')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->unsignedSmallInteger('otp_attempts')->default(0);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['permit_request_item_id', 'direction', 'status'], 'permit_courier_item_direction_idx');
        });

        // Older requests reduced balance at reservation time. Convert wallets once
        // to the standard model: balance=total funds, blocked_balance=reserved funds.
        DB::table('wallets')->where('blocked_balance', '>', 0)->orderBy('id')->each(function ($wallet) {
            DB::table('wallets')->where('id', $wallet->id)->update([
                'balance' => DB::raw('balance + blocked_balance'),
                'updated_at' => now(),
            ]);

            DB::table('wallet_transactions')->insert([
                'wallet_id' => $wallet->id,
                'dozbalagh_item_id' => null,
                'permit_request_item_id' => null,
                'amount' => $wallet->blocked_balance,
                'type' => 'credit',
                'action_type' => 'manual_adjustment',
                'transaction_month' => now()->format('Y-m'),
                'description' => 'اصلاح یک‌باره ساختار مانده کیف پول؛ مبالغ بلوکه‌شده بدون کسر قطعی از مانده کل',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        DB::table('permit_request_items as item')
            ->join('permit_requests as request', 'request.id', '=', 'item.permit_request_id')
            ->whereIn('request.payment_status', ['reserved', 'settled', 'refunded'])
            ->update([
                'item.reserved_amount' => DB::raw('item.price'),
                'item.financial_status' => DB::raw(
                    "CASE request.payment_status WHEN 'settled' THEN 'settled' WHEN 'refunded' THEN 'released' ELSE 'reserved' END"
                ),
            ]);

        DB::table('permit_request_items')->orderBy('id')->chunkById(500, function ($items) {
            $rows = [];
            foreach ($items as $item) {
                $rows[] = [
                    'permit_request_id' => $item->permit_request_id,
                    'permit_request_item_id' => $item->id,
                    'actor_user_id' => null,
                    'actor_role' => 'system',
                    'event_type' => 'legacy_imported',
                    'from_status' => null,
                    'to_status' => $item->item_status ?? $item->return_status ?? 'pending',
                    'description' => 'پرونده موجود هنگام راه‌اندازی گردش‌کار ردیف‌محور وارد تاریخچه شد.',
                    'before_data' => null,
                    'after_data' => json_encode([
                        'serial_number' => $item->d_serial_number,
                        'financial_status' => $item->financial_status ?? null,
                    ], JSON_UNESCAPED_UNICODE),
                    'metadata' => null,
                    'ip_address' => null,
                    'created_at' => $item->created_at ?? now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('permit_item_events')->insert($rows);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_courier_missions');
        Schema::dropIfExists('permit_item_events');

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropIndex('wallet_tx_permit_item_action_idx');
            $table->dropConstrainedForeignId('permit_request_item_id');
        });

        Schema::table('permit_request_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropConstrainedForeignId('rejected_by_user_id');
            $table->dropConstrainedForeignId('disposition_changed_by_user_id');
            $table->dropColumn([
                'financial_status', 'reserved_amount', 'financial_settled_at', 'financial_released_at',
                'reviewed_at', 'rejected_at', 'cits_status', 'cits_message', 'cits_error_image',
                'cits_attempted_at', 'disposition_type', 'disposition_note', 'disposition_changed_at',
            ]);
        });
    }
};
