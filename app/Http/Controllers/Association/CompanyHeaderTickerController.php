<?php

namespace App\Http\Controllers\Association;

use App\Http\Controllers\Controller;
use App\Models\CompanyHeaderTickerItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Morilog\Jalali\Jalalian;

class CompanyHeaderTickerController extends Controller
{
    public function feed(): JsonResponse
    {
        $messages = [];

        if (Schema::hasTable('company_header_ticker_items')) {
            $messages = CompanyHeaderTickerItem::query()
                ->visibleNow()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('message')
                ->map(fn ($message) => trim((string) $message))
                ->filter()
                ->values()
                ->all();
        }

        return response()
            ->json(['messages' => $messages])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function edit(): View
    {
        return view('association.company-header-ticker.edit', [
            'items' => CompanyHeaderTickerItem::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['created_by'] = $request->user()?->id;
        $validated['sort_order'] ??= ((int) CompanyHeaderTickerItem::max('sort_order')) + 10;

        CompanyHeaderTickerItem::create($validated);

        return back()->with('success', 'خبر جدید به تابلوی روان اضافه شد.');
    }

    public function update(Request $request, CompanyHeaderTickerItem $tickerItem): RedirectResponse
    {
        $tickerItem->update($this->validated($request));

        return back()->with('success', 'خبر تابلوی روان به‌روزرسانی شد.');
    }

    public function destroy(CompanyHeaderTickerItem $tickerItem): RedirectResponse
    {
        $tickerItem->delete();

        return back()->with('success', 'خبر از تابلوی روان حذف شد.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'starts_at_jalali' => ['nullable', 'string', 'max:20'],
            'ends_at_jalali' => ['nullable', 'string', 'max:20'],
            // Keep accepting the old Gregorian field names for existing integrations.
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'is_active' => ['nullable', Rule::in(['1', 1, true, '0', 0, false])],
        ]);

        $hasScheduleInput = array_key_exists('starts_at_jalali', $validated)
            || array_key_exists('ends_at_jalali', $validated)
            || array_key_exists('starts_at', $validated)
            || array_key_exists('ends_at', $validated);

        if ($hasScheduleInput) {
            $startsAt = filled($validated['starts_at_jalali'] ?? null)
                ? $this->jalaliDateTime($validated['starts_at_jalali'], 'starts_at_jalali')
                : (filled($validated['starts_at'] ?? null) ? Carbon::parse($validated['starts_at']) : null);
            $endsAt = filled($validated['ends_at_jalali'] ?? null)
                ? $this->jalaliDateTime($validated['ends_at_jalali'], 'ends_at_jalali')
                : (filled($validated['ends_at'] ?? null) ? Carbon::parse($validated['ends_at']) : null);

            if ($startsAt && $endsAt && $endsAt->lt($startsAt)) {
                throw ValidationException::withMessages([
                    'ends_at_jalali' => 'زمان پایان نمایش باید بعد از زمان شروع باشد.',
                ]);
            }

            $validated['starts_at'] = $startsAt;
            $validated['ends_at'] = $endsAt;
        }

        $validated['message'] = trim($validated['message']);
        unset($validated['starts_at_jalali'], $validated['ends_at_jalali']);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function jalaliDateTime(?string $value, string $field): mixed
    {
        if (blank($value)) {
            return null;
        }

        $value = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);

        if (! preg_match('/^1[34-9]\d{2}\/(0[1-9]|1[0-2])\/(0[1-9]|[12]\d|3[01]) ([01]\d|2[0-3]):[0-5]\d$/', $value)) {
            throw ValidationException::withMessages([$field => 'تاریخ و ساعت شمسی معتبر نیست.']);
        }

        try {
            return Jalalian::fromFormat('Y/m/d H:i', $value)->toCarbon();
        } catch (\Throwable) {
            throw ValidationException::withMessages([$field => 'تاریخ و ساعت شمسی معتبر نیست.']);
        }
    }
}
