<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Company;
use App\Models\Settlement;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class FinancialController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function dashboard(Request $request)
    {
        $companies = Company::orderBy('name', 'asc')->get();

        $totalSystemBalance = Wallet::sum('balance');
        $totalAvailable = $totalSystemBalance; 
        $totalBlocked = \Illuminate\Support\Facades\Schema::hasColumn('wallets', 'blocked_balance') ? Wallet::sum('blocked_balance') : 0;

        $chargedThisMonth = WalletTransaction::whereIn('type', ['credit', 'wallet_charge'])
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        $query = WalletTransaction::with('wallet.company')->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ref_id', 'LIKE', "%{$search}%")
                  ->orWhere('id', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('company_id')) {
            $companyId = $request->company_id;
            $query->whereHas('wallet', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $allTransactions = $query->paginate(10)->withQueryString();

        $associationTotalSettled = Settlement::sum('amount'); 
        $totalDozbalaghPurchased = WalletTransaction::where('action_type', 'dozbalagh_purchase')->sum('amount');
        $associationPendingDebt = max(0, $totalDozbalaghPurchased - $associationTotalSettled);

        return view('admin.financial.dashboard', compact(
            'companies',
            'totalSystemBalance',
            'totalAvailable',
            'totalBlocked',
            'chargedThisMonth',
            'allTransactions',
            'associationPendingDebt',
            'associationTotalSettled'
        ));
    }

    public function storeSettlement(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'ref_number' => 'required|string',
            'bank_name' => 'required|string',
            'receipt_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt_file')) {
            $receiptPath = $request->file('receipt_file')->store('receipts', 'public');
        }

        Settlement::create([
            'amount' => $request->amount,
            'ref_number' => $request->ref_number,
            'bank_name' => $request->bank_name,
            'receipt_file' => $receiptPath,
            'description' => 'تسویه حساب دوره‌ای سیستم با انجمن صنفی'
        ]);

        return back()->with('success', 'فیش تسویه حساب انجمن با موفقیت در سیستم ثبت و تراز مالی به‌روزرسانی شد.');
    }

    public function manualAdjustment(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'action_type' => 'required|in:charge,deduct',
            'amount' => 'required|numeric|min:1000',
            'description' => 'required|string|min:2'
        ]);

        // ۱. مطمئن می‌شویم که حتماً رکورد کیف پول شرکت وجود دارد یا ساخته می‌شود
        $wallet = Wallet::firstOrCreate(
            ['company_id' => $request->company_id],
            ['balance' => 0]
        );

        // ۲. 🚀 استخراج ماه شمسی جاری برای فیلد transaction_month
        $currentMonth = (int) \Morilog\Jalali\Jalalian::now()->getMonth();

        DB::beginTransaction();
        try {
            if ($request->action_type === 'charge') {
                // افزایش موجودی کیف پول
                $wallet->increment('balance', $request->amount);

                // ثبت تراکنش بستانکار با پر کردن فیلد اجباری ماه
                WalletTransaction::create([
                    'wallet_id'         => $wallet->id,
                    'amount'            => $request->amount,
                    'type'              => 'credit',
                    'action_type'       => 'manual_adjustment',
                    'transaction_month' => $currentMonth, // 👈 اضافه شدن فیلد اجباری دیتابیس
                    'status'            => 'success',
                    'description'       => $request->description
                ]);
            } else {
                if ($wallet->balance < $request->amount) {
                    return back()->withErrors(['error' => 'موجودی کیف پول کمتر از مبلغ کسر سفارش داده شده است!']);
                }
                
                // کاهش موجودی کیف پول
                $wallet->decrement('balance', $request->amount);

                // ثبت تراکنش بدهکار با پر کردن فیلد اجباری ماه
                WalletTransaction::create([
                    'wallet_id'         => $wallet->id,
                    'amount'            => $request->amount,
                    'type'              => 'debit',
                    'action_type'       => 'manual_adjustment',
                    'transaction_month' => $currentMonth, // 👈 اضافه شدن فیلد اجباری دیتابیس
                    'status'            => 'success',
                    'description'       => $request->description
                ]);
            }
            
            DB::commit();
            return back()->with('success', 'سند اصلاحی مالی دستی با موفقیت بر حساب شرکت اعمال و تراکنش ثبت شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطایی در ثبت سند اصلاحی رخ داد: ' . $e->getMessage()]);
        }
    }

    public function exportExcel(Request $request)
    {
        $query = WalletTransaction::with('wallet.company')->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ref_id', 'LIKE', "%{$search}%")
                  ->orWhere('id', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('company_id')) {
            $companyId = $request->company_id;
            $query->whereHas('wallet', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->get();
        $fileName = 'filtered-transactions-' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['شناسه تراکنش', 'نام شرکت', 'شرح تراکنش', 'نوع تراکنش', 'مبلغ (ریال)', 'تاریخ ثبت'];

        $callback = function() use($transactions, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns);

            foreach ($transactions as $trx) {
                fputcsv($file, [
                    '#' . ($trx->ref_id ?? $trx->id),
                    $trx->wallet->company->name ?? 'نامشخص',
                    $trx->description ?? 'بدون شرح',
                    ($trx->type === 'credit' || $trx->type === 'wallet_charge') ? 'بستانکار' : 'بدهکار',
                    number_format($trx->amount),
                    \Morilog\Jalali\Jalalian::fromCarbon($trx->created_at)->format('Y/m/d H:i')
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Auditable monthly report of issued Dozbalagh items.
     *
     * The source of truth is permit_request_items: every issued serial is one
     * financial row and its frozen price is the deducted amount for that item.
     */
    public function exportMonthlyDozbalaghReport(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        $from = $validated['date_from'];
        $to = $validated['date_to'];

        if (Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 31) {
            throw ValidationException::withMessages([
                'date_to' => 'بازه گزارش ماهانه نمی‌تواند بیشتر از ۳۱ روز باشد.',
            ]);
        }

        $query = DB::table('permit_request_items as item')
            ->join('permit_requests as permit', 'permit.id', '=', 'item.permit_request_id')
            ->join('companies as company', 'company.id', '=', 'permit.company_id')
            ->leftJoin('countries as country', 'country.id', '=', 'item.country_id')
            ->whereNotNull('item.d_serial_number')
            ->whereNotNull('item.issued_at')
            ->whereBetween('item.issued_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
            ->when($validated['company_id'] ?? null, fn ($q, $companyId) => $q->where('permit.company_id', $companyId))
            ->orderBy('item.issued_at')
            ->orderBy('item.id')
            ->select([
                'item.id as item_id',
                'permit.d_code as tracking_code',
                'item.d_serial_number as serial_number',
                'company.name as company_name',
                'company.name_fa as company_name_fa',
                'country.name as country_name',
                'item.loading_destination',
                'item.operation_type',
                'item.permit_type',
                'item.price as deducted_amount',
                'item.issued_at',
            ]);

        $rows = $query->get();
        $total = $rows->sum(fn ($row) => (float) $row->deducted_amount);
        $fileName = "dozbalagh-financial-report-{$from}-to-{$to}.csv";

        return response()->streamDownload(function () use ($rows, $total, $from, $to) {
            $file = fopen('php://output', 'wb');
            fwrite($file, "\xEF\xBB\xBF");
            $safeText = static function ($value): string {
                $value = (string) ($value ?? '');

                return preg_match('/^[=+\-@]/u', $value) ? "'{$value}" : $value;
            };

            fputcsv($file, ['گزارش مالی دوزوله‌های صادرشده']);
            fputcsv($file, ['از تاریخ', $from, 'تا تاریخ', $to]);
            fputcsv($file, ['تعداد ردیف', $rows->count(), 'جمع مبلغ کسرشده (ریال)', $total]);
            fputcsv($file, []);
            fputcsv($file, [
                'ردیف',
                'کد رهگیری پرونده',
                'شماره دوزوله',
                'شرکت صادرکننده',
                'کشور مقصد',
                'مقصد بارگیری',
                'نوع عملیات',
                'نوع دوزوله',
                'مبلغ کسرشده (ریال)',
                'تاریخ و ساعت صدور (شمسی)',
                'شناسه سیستمی ردیف',
            ]);

            foreach ($rows as $index => $row) {
                $issuedAt = \Illuminate\Support\Carbon::parse($row->issued_at);
                fputcsv($file, [
                    $index + 1,
                    $safeText($row->tracking_code),
                    $safeText($row->serial_number),
                    $safeText($row->company_name_fa ?: $row->company_name ?: 'نامشخص'),
                    $safeText($row->country_name ?: 'نامشخص'),
                    $safeText($row->loading_destination ?: '---'),
                    $safeText($row->operation_type ?: '---'),
                    $safeText($row->permit_type ?: '---'),
                    (float) $row->deducted_amount,
                    \Morilog\Jalali\Jalalian::fromCarbon($issuedAt)->format('Y/m/d H:i'),
                    $row->item_id,
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, ['', '', '', '', '', '', '', 'جمع کل', $total]);
            fclose($file);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function destroyAdjustment($id)
    {
        $transaction = WalletTransaction::findOrFail($id);

        if ($transaction->action_type !== 'manual_adjustment') {
            return back()->withErrors(['error' => 'تنها سندهای اصلاحی دستی قابلیت ابطال دارند!']);
        }

        // 🚀 باگ سینتکس نقطه به دو نقطه اصلاح شد
        $wallet = Wallet::find($transaction->wallet_id);

        DB::beginTransaction();
        try {
            if ($wallet) {
                if ($transaction->type === 'credit' || $transaction->type === 'wallet_charge') {
                    $wallet->decrement('balance', $transaction->amount);
                } else {
                    $wallet->increment('balance', $transaction->amount);
                }
            }

            $transaction->delete();
            DB::commit();

            return back()->with('success', 'سند اصلاحی مورد نظر با موفقیت ابطال شد و موجودی شرکت به حالت قبل بازگشت.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'خطایی در ابطال سند رخ داد: ' . $e->getMessage()]);
        }
    }
}
