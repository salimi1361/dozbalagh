<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PermitItemTransferFinancialService
{
    public function settings(): array
    {
        $raw = SystemSetting::getValue('permit_unused_transfer_financial', '{}');
        $settings = is_array($raw) ? $raw : json_decode((string) $raw, true);

        return array_merge(['enabled' => false], is_array($settings) ? $settings : []);
    }

    public function apply(int $itemId, string $serial, int $sourceCompanyId, int $destinationCompanyId): array
    {
        if (!(bool) $this->settings()['enabled']) {
            return ['fee' => 0.0, 'refund' => 0.0, 'fee_transaction_id' => null, 'refund_transaction_id' => null];
        }

        $pricing = DB::table('permit_request_items as item')
            ->join('countries as country', 'country.id', '=', 'item.country_id')
            ->where('item.id', $itemId)
            ->select('country.member_price', 'country.non_member_price')
            ->first();
        $destinationCompany = DB::table('companies')->where('id', $destinationCompanyId)->lockForUpdate()->first();
        if (!$pricing || !$destinationCompany) {
            throw new RuntimeException('تعرفه انتقال یا اطلاعات عضویت شرکت مقصد یافت نشد.');
        }

        $isMember = (bool) $destinationCompany->is_association_member;
        $fee = max(0, (float) ($isMember ? $pricing->member_price : $pricing->non_member_price));
        $feeTransactionId = null;

        if ($fee > 0) {
            $wallet = DB::table('wallets')->where('company_id', $destinationCompanyId)->lockForUpdate()->first();
            if (!$wallet || ((float) $wallet->balance - (float) $wallet->blocked_balance) < $fee) {
                throw new RuntimeException('موجودی قابل استفاده کیف پول شرکت مقصد برای هزینه انتقال کافی نیست.');
            }
            DB::table('wallets')->where('id', $wallet->id)->decrement('balance', $fee, ['updated_at' => now()]);
            $membershipLabel = $isMember ? 'عضو انجمن' : 'غیرعضو انجمن';
            $feeTransactionId = $this->transaction(
                (int) $wallet->id,
                $itemId,
                $fee,
                "هزینه انتقال دوزوله شماره {$serial} بر اساس تعرفه شرکت {$membershipLabel}"
            );
        }

        return ['fee' => $fee, 'refund' => 0.0, 'fee_transaction_id' => $feeTransactionId, 'refund_transaction_id' => null];
    }

    private function transaction(int $walletId, int $itemId, float $amount, string $description): int
    {
        return DB::table('wallet_transactions')->insertGetId([
            'wallet_id' => $walletId,
            'dozbalagh_item_id' => null,
            'permit_request_item_id' => $itemId,
            'amount' => $amount,
            'type' => 'debit',
            'action_type' => 'manual_adjustment',
            'business_event' => 'permit_transfer_fee',
            'transaction_month' => now()->format('Y-m'),
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
