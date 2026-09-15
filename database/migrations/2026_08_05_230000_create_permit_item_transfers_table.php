<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('business_event', 60)->nullable()->after('action_type')->index();
        });
        Schema::table('permit_request_items', function (Blueprint $table) {
            $table->foreignId('issued_company_id')->nullable()->after('permit_request_id')->constrained('companies')->nullOnDelete();
        });
        DB::table('permit_request_items')->update([
            'issued_company_id' => DB::raw('(SELECT company_id FROM permit_requests WHERE permit_requests.id = permit_request_items.permit_request_id)'),
        ]);

        Schema::create('permit_item_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permit_request_item_id')->constrained('permit_request_items')->restrictOnDelete();
            $table->string('serial_number', 100);
            $table->foreignId('source_company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('source_driver_id')->constrained('drivers')->restrictOnDelete();
            $table->foreignId('source_fleet_id')->constrained('fleets')->restrictOnDelete();
            $table->foreignId('destination_company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('destination_driver_id')->constrained('drivers')->restrictOnDelete();
            $table->foreignId('destination_fleet_id')->constrained('fleets')->restrictOnDelete();
            $table->foreignId('transferred_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('transfer_fee', 15, 2)->default(0);
            $table->decimal('source_refund', 15, 2)->default(0);
            $table->foreignId('fee_wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->foreignId('refund_wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->text('reason');
            $table->timestamps();
            $table->index(['permit_request_item_id', 'created_at'], 'permit_transfer_item_timeline_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_item_transfers');
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn('business_event');
        });
        Schema::table('permit_request_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('issued_company_id');
        });
    }
};
