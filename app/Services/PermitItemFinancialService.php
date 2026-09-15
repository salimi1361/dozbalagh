<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PermitItemFinancialService
{
    public function reserve(int $companyId, int $itemId, float $amount, string $description): void
    {
        $wallet = DB::table('wallets')->where('company_id', $companyId)->lockForUpdate()->first();
        if (!$wallet || ((float) $wallet->balance - (float) $wallet->blocked_balance) < $amount) {
            throw new RuntimeException('موجودی قابل استفاده کیف پول کافی نیست.');
        }

        DB::table('wallets')->where('id', $wallet->id)->increment('blocked_balance', $amount, ['updated_at' => now()]);
        $this->transaction($wallet->id, $itemId, $amount, 'debit', 'dozbalagh_reserve', $description);
        DB::table('permit_request_items')->where('id', $itemId)->update([
            'reserved_amount' => $amount,
            'financial_status' => 'reserved',
            'updated_at' => now(),
        ]);
    }

    public function settle(object $item, int $companyId, string $description): void
    {
        if (($item->financial_status ?? null) === 'waived') {
            return;
        }
        if (($item->financial_status ?? null) === 'settled') {
            return;
        }
        if (($item->financial_status ?? null) !== 'reserved') {
            throw new RuntimeException('وضعیت مالی این دوزوله برای کسر قطعی معتبر نیست.');
        }

        $amount = (float) ($item->reserved_amount ?: $item->price);
        $wallet = DB::table('wallets')->where('company_id', $companyId)->lockForUpdate()->first();
        if (!$wallet || (float) $wallet->blocked_balance < $amount || (float) $wallet->balance < $amount) {
            throw new RuntimeException('مانده بلوکه‌شده کیف پول برای چاپ این دوزوله کافی نیست.');
        }

        DB::table('wallets')->where('id', $wallet->id)->update([
            'balance' => DB::raw('balance - '.sprintf('%.2F', $amount)),
            'blocked_balance' => DB::raw('blocked_balance - '.sprintf('%.2F', $amount)),
            'updated_at' => now(),
        ]);
        $this->transaction($wallet->id, $item->id, $amount, 'debit', 'dozbalagh_purchase', $description);
        DB::table('permit_request_items')->where('id', $item->id)->update([
            'financial_status' => 'settled',
            'financial_settled_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function release(object $item, int $companyId, string $description): void
    {
        if (($item->financial_status ?? null) === 'waived') {
            return;
        }
        if (($item->financial_status ?? null) === 'released') {
            return;
        }
        if (($item->financial_status ?? null) !== 'reserved') {
            throw new RuntimeException('فقط مبلغ بلوکه‌شده قابل آزادسازی است.');
        }

        $amount = (float) ($item->reserved_amount ?: $item->price);
        $wallet = DB::table('wallets')->where('company_id', $companyId)->lockForUpdate()->first();
        if (!$wallet || (float) $wallet->blocked_balance < $amount) {
            throw new RuntimeException('مانده بلوکه‌شده کیف پول برای آزادسازی کافی نیست.');
        }

        DB::table('wallets')->where('id', $wallet->id)->decrement('blocked_balance', $amount, ['updated_at' => now()]);
        $this->transaction($wallet->id, $item->id, $amount, 'credit', 'dozbalagh_release', $description);
        DB::table('permit_request_items')->where('id', $item->id)->update([
            'financial_status' => 'released',
            'financial_released_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function transaction(int $walletId, int $itemId, float $amount, string $type, string $action, string $description): void
    {
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $walletId,
            'dozbalagh_item_id' => null,
            'permit_request_item_id' => $itemId,
            'amount' => $amount,
            'type' => $type,
            'action_type' => $action,
            'transaction_month' => now()->format('Y-m'),
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
