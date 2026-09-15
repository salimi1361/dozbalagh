<?php

namespace App\Services\Assistant;

use App\Models\PermitRequest;
use App\Models\Wallet;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CompanyAssistantDataService
{
    public function answer(string $message, AssistantContext $context): ?string
    {
        if ($context->role !== 'company' || ! $context->companyId) {
            return null;
        }

        $message = $this->normalize($message);

        if (str_contains($message, 'دوزوله')) {
            $driverAnswer = $this->driverPermitSummary($message, $context->companyId);

            if ($driverAnswer !== null) {
                return $driverAnswer;
            }
        }

        if ($this->containsAny($message, ['علت رد', 'دلیل رد', 'چرا رد', 'واسه چی رد', 'برای چی رد'])) {
            return $this->rejectionReasons($context->companyId);
        }

        if ($this->containsAny($message, ['چند ناوگان', 'چندتا ناوگان', 'چنتا ناوگان', 'تعداد ناوگان', 'ناوگان دارم'])) {
            return $this->fleetCount($context->companyId);
        }

        if ($this->containsAny($message, ['چند راننده', 'چندتا راننده', 'چنتا راننده', 'تعداد راننده', 'راننده دارم'])) {
            return $this->driverCount($context->companyId);
        }

        if ($this->containsAny($message, ['چند درخواست', 'چندتا درخواست', 'چنتا درخواست', 'تعداد درخواست', 'درخواست زدم', 'درخواست ثبت کردم'])) {
            return $this->requestCount($context->companyId);
        }

        if ($this->containsAny($message, ['منتظر صدور', 'در انتظار صدور', 'باید صادر', 'صدور نشده'])) {
            return $this->awaitingIssuance($context->companyId);
        }

        if (str_contains($message, 'تمدید')) {
            return $this->renewalSummary($context->companyId);
        }

        if ($this->containsAny($message, ['چندتا صادر', 'چنتا صادر', 'تعداد صادر', 'صادر کرده', 'صادر شده'])) {
            return $this->issuedCount($context->companyId);
        }

        if ($this->containsAny($message, ['موجودی کیف', 'موجودی حساب', 'پول دارم', 'کیف پولم'])) {
            return $this->walletSummary($context->companyId);
        }

        if ($this->containsAny($message, ['خلاصه وضعیت', 'وضعیت درخواستام', 'وضعیت درخواست ها', 'آمار درخواست', 'گزارش درخواست'])) {
            return $this->requestStatusSummary($context->companyId);
        }

        if ($this->containsAny($message, ['اطلاعات شرکت', 'آمار شرکت', 'گزارش شرکت'])) {
            return $this->companySummary($context->companyId);
        }

        return null;
    }

    private function fleetCount(int $companyId): string
    {
        $count = DB::table('fleets')->where('company_id', $companyId)->where('is_active', true)->count();

        return "شرکت شما {$count} ناوگان ثبت‌شده دارد.";
    }

    private function driverCount(int $companyId): string
    {
        $count = DB::table('drivers')->where('current_company_id', $companyId)->where('is_active', true)->count();

        return "در حال حاضر {$count} راننده به شرکت شما متصل است.";
    }

    private function requestCount(int $companyId): string
    {
        $count = $this->requests($companyId)->count();

        return "شرکت شما در مجموع {$count} درخواست ثبت کرده است.";
    }

    private function driverPermitSummary(string $message, int $companyId): ?string
    {
        $drivers = DB::table('drivers')
            ->where('current_company_id', $companyId)
            ->where('is_active', true)
            ->get(['id', 'national_code', 'first_name_fa', 'last_name_fa']);

        $driver = $this->resolveDriver($message, $drivers);

        if ($driver === null) {
            return preg_match('/\b\d{8,12}\b/u', $message)
                ? 'راننده‌ای با این کد ملی در شرکت شما پیدا نشد.'
                : null;
        }

        $destinations = DB::table('permit_requests as pr')
            ->join('permit_request_items as item', 'item.permit_request_id', '=', 'pr.id')
            ->leftJoin('countries as country', 'country.id', '=', 'item.country_id')
            ->where('pr.company_id', $companyId)
            ->where('pr.driver_id', $driver->id)
            ->whereNotNull('item.d_serial_number')
            ->selectRaw("COALESCE(country.name, 'نامشخص') as destination_name, COUNT(item.id) as total")
            ->groupByRaw("COALESCE(country.name, 'نامشخص')")
            ->orderByDesc('total')
            ->get();

        $total = (int) $destinations->sum('total');
        $name = trim(($driver->first_name_fa ?? '').' '.($driver->last_name_fa ?? ''));

        if ($total === 0) {
            return "برای راننده {$name} دوزوله صادرشده‌ای در اطلاعات شرکت شما ثبت نشده است.";
        }

        $destinationText = $destinations
            ->map(fn ($row): string => "{$row->destination_name} ({$row->total})")
            ->implode('، ');

        return "برای راننده {$name} تعداد {$total} دوزوله صادر شده است. مقصدها: {$destinationText}.";
    }

    private function resolveDriver(string $message, Collection $drivers): ?object
    {
        if (preg_match('/\b\d{8,12}\b/u', $message, $matches)) {
            return $drivers->first(
                fn ($driver): bool => $this->normalize((string) $driver->national_code) === $matches[0],
            );
        }

        return $drivers->first(function ($driver) use ($message): bool {
            $firstName = $this->normalize((string) $driver->first_name_fa);
            $lastName = $this->normalize((string) $driver->last_name_fa);
            $fullName = trim($firstName.' '.$lastName);

            return $fullName !== ''
                && (
                    str_contains($message, $fullName)
                    || ($firstName !== '' && $lastName !== ''
                        && str_contains($message, $firstName)
                        && str_contains($message, $lastName))
                );
        });
    }

    private function rejectionReasons(int $companyId): string
    {
        $requests = $this->requests($companyId)
            ->where('status', 'rejected')
            ->latest('id')
            ->limit(3)
            ->get(['d_code', 'reject_reason', 'association_note']);

        if ($requests->isEmpty()) {
            return 'درخواست ردشده‌ای برای شرکت شما ثبت نشده است.';
        }

        $lines = $requests->map(function (PermitRequest $request): string {
            $code = $request->d_code ?: 'بدون کد رهگیری';
            $reason = trim((string) ($request->reject_reason ?: $request->association_note));

            return $reason !== ''
                ? "درخواست {$code}: {$reason}"
                : "درخواست {$code}: انجمن دلیل متنی ثبت نکرده است.";
        });

        return "آخرین علت‌های رد ثبت‌شده برای شرکت شما:\n".$lines->implode("\n");
    }

    private function awaitingIssuance(int $companyId): string
    {
        $approved = $this->requests($companyId)->where('status', 'approved')->count();

        return $approved > 0
            ? "{$approved} درخواست تأییدشده شرکت شما در انتظار صدور یا تخصیص سریال است."
            : 'در حال حاضر درخواست تأییدشده‌ای در انتظار صدور برای شرکت شما وجود ندارد.';
    }

    private function renewalSummary(int $companyId): string
    {
        $renewals = $this->requests($companyId)->where('request_type', 'renewal');
        $total = (clone $renewals)->count();
        $issued = (clone $renewals)->where('status', 'issued')->count();
        $inProgress = (clone $renewals)
            ->whereIn('status', ['pending', 'approved', 'under_review'])
            ->count();
        $rejected = (clone $renewals)->where('status', 'rejected')->count();

        return "شرکت شما در مجموع {$total} درخواست تمدیدی دارد؛ {$issued} مورد صادر شده، {$inProgress} مورد در حال بررسی یا انتظار صدور و {$rejected} مورد رد شده است.";
    }

    private function issuedCount(int $companyId): string
    {
        $issued = $this->requests($companyId)->where('status', 'issued')->count();

        return "تعداد درخواست‌های صادرشده شرکت شما {$issued} مورد است.";
    }

    private function walletSummary(int $companyId): string
    {
        $wallet = Wallet::query()
            ->where('company_id', $companyId)
            ->first(['balance', 'blocked_balance']);

        if (! $wallet) {
            return 'کیف پولی برای شرکت شما ثبت نشده است.';
        }

        $balance = number_format((float) $wallet->balance);
        $blocked = number_format((float) $wallet->blocked_balance);
        $available = number_format((float) $wallet->balance - (float) $wallet->blocked_balance);

        return "موجودی کل کیف پول شرکت شما {$balance} ریال است؛ {$blocked} ریال مسدود و {$available} ریال قابل استفاده است.";
    }

    private function requestStatusSummary(int $companyId): string
    {
        $counts = $this->requests($companyId)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        if ($counts->isEmpty()) {
            return 'هنوز درخواستی برای شرکت شما ثبت نشده است.';
        }

        $labels = [
            'draft' => 'پیش‌نویس',
            'pending' => 'در انتظار بررسی',
            'under_review' => 'در حال بررسی',
            'approved' => 'تأییدشده و منتظر صدور',
            'issued' => 'صادرشده',
            'rejected' => 'ردشده',
            'returned' => 'برگشت برای اصلاح',
            'collected' => 'تحویل‌شده',
            'archived' => 'بایگانی‌شده',
            'lost' => 'مفقودی',
        ];

        $parts = collect($counts)->map(
            fn ($total, $status): string => ($labels[$status] ?? $status).': '.(int) $total,
        );

        return 'خلاصه وضعیت درخواست‌های شرکت شما: '.$parts->implode('، ').'.';
    }

    private function companySummary(int $companyId): string
    {
        $requestCount = $this->requests($companyId)->count();
        $issuedCount = $this->requests($companyId)->where('status', 'issued')->count();
        $driverCount = DB::table('drivers')->where('current_company_id', $companyId)->where('is_active', true)->count();
        $fleetCount = DB::table('fleets')->where('company_id', $companyId)->where('is_active', true)->count();

        return "خلاصه شرکت شما: {$requestCount} درخواست، {$issuedCount} درخواست صادرشده، {$driverCount} راننده متصل و {$fleetCount} ناوگان ثبت‌شده.";
    }

    private function requests(int $companyId): Builder
    {
        return PermitRequest::query()->where('company_id', $companyId);
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function containsAny(string $message, array $needles): bool
    {
        $messageTokens = $this->tokens($message);

        foreach ($needles as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }

            $needleTokens = $this->tokens($needle);

            if (
                count($needleTokens) > 1
                && count(array_diff($needleTokens, $messageTokens)) === 0
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function tokens(string $value): array
    {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $this->normalize($value), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique($tokens ?: []));
    }

    private function normalize(string $value): string
    {
        $value = str_replace(
            ['ي', 'ى', 'ك', "\u{200C}", "\u{200F}", "\u{200E}", '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
            ['ی', 'ی', 'ک', ' ', '', '', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $value,
        );

        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));
    }
}
