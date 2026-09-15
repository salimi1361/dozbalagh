<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache; // 🚀 اضافه شدن کلاس کش

class WalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function index()
    {
        $companyId = auth()->user()->company_id ?? auth()->user()->company->id;
        $wallet = Wallet::firstOrCreate(['company_id' => $companyId]);

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderBy('id', 'desc')
            ->paginate(10);
        $this->attachSourceTransferTraces($transactions->getCollection(), $companyId);

        return view('company.wallet.index', compact('wallet', 'transactions'));
    }

    public function charge(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:100000', // حداقل مبلغ شارژ: ۱۰۰ هزار ریال
        ]);

        $companyId = auth()->user()->company_id ?? auth()->user()->company->id;
        $amountRial = (int) $request->amount;
        
        $merchantId = env('ZARINPAL_MERCHANT_ID', '12a746ee-5590-42cb-82f0-588660b0ec91');
        $callbackUrl = route('company.wallet.verify');

        try {
            // ۱. ارسال درخواست ساخت توکن به زرین‌پال
            $response = Http::withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://api.zarinpal.com/pg/v4/payment/request.json', [
                'merchant_id' => $merchantId,
                'amount' => $amountRial,
                'description' => 'شارژ کیف پول شرکت',
                'callback_url' => $callbackUrl,
            ]);

            $result = $response->json();

            // ۲. اگر زرین‌پال اوکی داد
            if (isset($result['data']['code']) && $result['data']['code'] == 100) {
                $authority = $result['data']['authority'];
                
                // 🚀 جادوی اصلی: ذخیره اطلاعات معلق در کش لاراول به جای دیتابیس
                // این اطلاعات به مدت 30 دقیقه منتظر بازگشت کاربر از بانک می‌ماند
                Cache::put('zarinpal_payment_' . $authority, [
                    'company_id' => $companyId,
                    'amount' => $amountRial
                ], now()->addMinutes(30));

                // پرتاب کاربر به درگاه بانک
                return redirect('https://www.zarinpal.com/pg/StartPay/' . $authority);
            }

            $errorMessage = $result['errors']['message'] ?? 'کد خطا: ' . ($result['errors']['code'] ?? 'نامشخص');
            return back()->withErrors(['error' => 'درگاه پرداخت پیام داد: ' . $errorMessage]);

        } catch (\Exception $e) {
            Log::error('Zarinpal Connection Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'خطای ارتباطی سرور: ' . $e->getMessage()]);
        }
    }

    public function verify(Request $request)
    {
        $authority = $request->query('Authority');
        $status = $request->query('Status');

        // ۱. بازیابی اطلاعات تراکنش از کش بر اساس Authority
        $paymentData = Cache::get('zarinpal_payment_' . $authority);

        // اگر زمان گذشته باشد یا شناسه دستکاری شده باشد
        if (!$paymentData) {
            return redirect()->route('company.wallet.index')->withErrors(['error' => 'تراکنش منقضی شده یا در سیستم یافت نشد.']);
        }

        // ۲. اگر کاربر انصراف داد
        if ($status !== 'OK') {
            Cache::forget('zarinpal_payment_' . $authority); // پاکسازی حافظه
            return redirect()->route('company.wallet.index')->withErrors(['error' => 'پرداخت لغو شد. در صورت کسر وجه، مبلغ بازمی‌گردد.']);
        }

        $merchantId = env('ZARINPAL_MERCHANT_ID', '12a746ee-5590-42cb-82f0-588660b0ec91');
        $amountRial = $paymentData['amount'];

        try {
            // ۳. تایید نهایی پرداخت با زرین‌پال
            $response = Http::withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://api.zarinpal.com/pg/v4/payment/verify.json', [
                'merchant_id' => $merchantId,
                'amount' => $amountRial,
                'authority' => $authority,
            ]);

            $result = $response->json();

            // ۴. اگر پرداخت کاملاً موفق بود (کد 100 یا 101)
            if (isset($result['data']['code']) && in_array($result['data']['code'], [100, 101])) {
                $refId = $result['data']['ref_id'];
                
                // 🚀 واریز قطعی به کیف پول از طریق سرویس خود شما (بدون تداخل با دیتابیس)
                $this->walletService->chargeWallet(
                    $paymentData['company_id'], 
                    $paymentData['amount'], 
                    'شارژ آنلاین حساب (کد پیگیری: ' . $refId . ')'
                );

                Cache::forget('zarinpal_payment_' . $authority); // پایان موفقیت‌آمیز و پاکسازی کش

                return redirect()->route('company.wallet.index')->with('success', 'کیف پول با موفقیت شارژ شد. کد پیگیری: ' . $refId);
            }

            return redirect()->route('company.wallet.index')->withErrors(['error' => 'تراکنش از سمت بانک تایید نهایی نشد.']);

        } catch (\Exception $e) {
            return redirect()->route('company.wallet.index')->withErrors(['error' => 'خطا در ارتباط با سرور بانک جهت تایید نهایی.']);
        }
    }

    public function exportExcel(Request $request)
    {
        $request->validate([
            'transaction_ids' => 'required|string'
        ]);

        $ids = explode(',', $request->transaction_ids);
        $companyId = auth()->user()->company_id ?? auth()->user()->company->id;
        $wallet = Wallet::where('company_id', $companyId)->first();

        if (!$wallet) {
            return back()->withErrors(['error' => 'کیف پول یافت نشد.']);
        }

        // دریافت فقط تراکنش‌های تیک‌خورده
        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->whereIn('id', $ids)
            ->orderBy('id', 'desc')
            ->get();
        $this->attachSourceTransferTraces($transactions, $companyId);

        $fileName = "transactions_export_" . date('Y-m-d_H-i') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // 📊 ستون‌های شمسی و میلادی مجزا شدند
        $columns = ['شناسه/پیگیری', 'شرح تراکنش', 'مبلغ (ریال)', 'نوع تراکنش', 'وضعیت', 'تاریخ شمسی', 'تاریخ میلادی'];

        $callback = function() use($transactions, $columns) {
            $file = fopen('php://output', 'w');
            // این کد برای این است که اکسل فونت فارسی را خراب نکند (BOM)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); 
            fputcsv($file, $columns);

            foreach ($transactions as $trx) {
                $type = ($trx->type === 'credit' || $trx->type === 'wallet_charge') ? 'بستانکار (+)' : 'بدهکار (-)';
                $isSettledDebit = $trx->action_type === 'dozbalagh_purchase'
                    || ($trx->business_event ?? null) === 'permit_transfer_fee';
                $status = $isSettledDebit
                    ? 'قطعی / پرداخت‌شده'
                    : ($trx->status === 'success' ? 'موفق' : ($trx->status === 'failed' ? 'ناموفق' : 'معلق'));

                // 📅 تبدیل زنده تاریخ به هجری شمسی و میلادی
                $shamsiDate = \Morilog\Jalali\Jalalian::fromCarbon($trx->created_at)->format('Y/m/d H:i');
                $miladiDate = \Carbon\Carbon::parse($trx->created_at)->format('Y-m-d H:i');

                fputcsv($file, [
                    $trx->ref_id ?? $trx->id,
                    $trx->source_transfer_trace
                        ? ($trx->description.' | دوزوله شماره '.$trx->source_transfer_trace->serial_number.' مختومه به علت انتقال توسط انجمن؛ رهگیری '.$trx->source_transfer_trace->tracking_code.'؛ تاریخ '.\Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($trx->source_transfer_trace->created_at))->format('Y/m/d H:i'))
                        : $trx->description,
                    $trx->amount,
                    $type,
                    $status,
                    $shamsiDate,
                    $miladiDate
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function attachSourceTransferTraces($transactions, int $companyId): void
    {
        $itemIds = $transactions
            ->where('action_type', 'dozbalagh_purchase')
            ->pluck('permit_request_item_id')
            ->filter()
            ->unique()
            ->values();
        if ($itemIds->isEmpty()) {
            return;
        }

        $traces = DB::table('permit_item_transfers')
            ->where('source_company_id', $companyId)
            ->whereIn('permit_request_item_id', $itemIds)
            ->orderByDesc('id')
            ->get(['id', 'permit_request_item_id', 'serial_number', 'created_at'])
            ->unique('permit_request_item_id')
            ->keyBy('permit_request_item_id');

        $transactions->each(function ($transaction) use ($traces) {
            $trace = $transaction->action_type === 'dozbalagh_purchase'
                ? $traces->get($transaction->permit_request_item_id)
                : null;
            if ($trace) {
                $trace->tracking_code = 'TRF-'.str_pad((string) $trace->id, 8, '0', STR_PAD_LEFT);
            }
            $transaction->source_transfer_trace = $trace;
        });
    }
}
