<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Exception;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * شارژ کیف پول (افزایش موجودی)
     */
    public function chargeWallet($companyId, $amount, $description = 'شارژ آنلاین کیف پول')
    {
        return DB::transaction(function () use ($companyId, $amount, $description) {
            $wallet = Wallet::firstOrCreate(['company_id' => $companyId]);

            // افزایش موجودی کل
            $wallet->increment('balance', $amount);

            // ثبت تراکنش در دفتر کل
            $this->recordTransaction(
                $wallet->id, 
                null, 
                $amount, 
                'credit', 
                'online_charge', 
                $description
            );

            return $wallet;
        });
    }

    /**
     * بلوکه کردن وجه (زمان ثبت اولیه درخواست دوزوله)
     */
    public function reserveFunds($companyId, $amount, $dozbalaghItemId = null, $description = 'بلوکه شدن وجه بابت درخواست دوزوله')
    {
        return DB::transaction(function () use ($companyId, $amount, $dozbalaghItemId, $description) {
            $wallet = Wallet::where('company_id', $companyId)->firstOrFail();

            if ($wallet->available_balance < $amount) {
                throw new Exception('موجودی قابل استفاده کیف پول کافی نیست.');
            }

            // انتقال از موجودی در دسترس به موجودی بلوکه‌شده (مانده کل تغییر نمی‌کند)
            $wallet->increment('blocked_balance', $amount);

            $this->recordTransaction(
                $wallet->id, 
                $dozbalaghItemId, 
                $amount, 
                'debit', 
                'dozbalagh_reserve', 
                $description
            );

            return $wallet;
        });
    }

    /**
     * تایید نهایی و کسر قطعی پول (در صورت تایید انجمن)
     */
    public function commitReservedFunds($companyId, $amount, $dozbalaghItemId = null, $description = 'کسر قطعی وجه دوزوله')
    {
        return DB::transaction(function () use ($companyId, $amount, $dozbalaghItemId, $description) {
            $wallet = Wallet::where('company_id', $companyId)->firstOrFail();

            if ($wallet->blocked_balance < $amount) {
                throw new Exception('مبلغ بلوکه‌شده کافی برای این عملیات وجود ندارد.');
            }

            // کسر قطعی از موجودی کل و موجودی بلوکه‌شده
            $wallet->decrement('balance', $amount);
            $wallet->decrement('blocked_balance', $amount);

            $this->recordTransaction(
                $wallet->id, 
                $dozbalaghItemId, 
                $amount, 
                'debit', 
                'dozbalagh_purchase', 
                $description
            );

            return $wallet;
        });
    }

    /**
     * آزادسازی وجه بلوکه‌شده (در صورت رد درخواست توسط انجمن)
     */
    public function releaseReservedFunds($companyId, $amount, $dozbalaghItemId = null, $description = 'آزادسازی وجه بلوکه‌شده بابت رد درخواست')
    {
        return DB::transaction(function () use ($companyId, $amount, $dozbalaghItemId, $description) {
            $wallet = Wallet::where('company_id', $companyId)->firstOrFail();

            if ($wallet->blocked_balance < $amount) {
                throw new Exception('مبلغ بلوکه‌شده کافی برای این عملیات وجود ندارد.');
            }

            // فقط مبلغ بلوکه‌شده را کم می‌کنیم تا پول به available_balance برگردد
            $wallet->decrement('blocked_balance', $amount);

            $this->recordTransaction(
                $wallet->id, 
                $dozbalaghItemId, 
                $amount, 
                'credit', 
                'dozbalagh_release', 
                $description
            );

            return $wallet;
        });
    }

    /**
     * متد کمکی برای ثبت ریز تراکنش‌ها
     */
    private function recordTransaction($walletId, $dozbalaghItemId, $amount, $type, $actionType, $description)
    {
        WalletTransaction::create([
            'wallet_id' => $walletId,
            'dozbalagh_item_id' => $dozbalaghItemId,
            'amount' => $amount,
            'type' => $type,
            'action_type' => $actionType,
            'transaction_month' => now()->format('Y-m'), // در صورت نیاز به تاریخ شمسی می‌توانید اینجا را تغییر دهید
            'description' => $description,
        ]);
    }
}