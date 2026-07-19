<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Models\CompanyPerson;
use App\Shahbaz\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Morilog\Jalali\Jalalian;

class CompanyPeopleController extends Controller
{
    private const TYPES = ['personnel', 'board', 'shareholders'];

    public function index(Request $request, string $type)
    {
        $this->validType($type);
        $company = $request->user()->company;
        $items = CompanyPerson::with('person')->where('company_id', $company->id)->where('relation_type', $type)->latest()->get();
        return view('Shahbaz.company.people.index', compact('company', 'items', 'type') + ['editable' => $this->editable($company)]);
    }

    public function create(Request $request, string $type)
    {
        $this->validType($type); abort_unless($this->editable($request->user()->company), 403);
        return view('Shahbaz.company.people.form', ['type' => $type, 'item' => new CompanyPerson, 'person' => new Person] + $this->formOptions());
    }

    public function store(Request $request, string $type)
    {
        $this->validType($type); $company = $request->user()->company; abort_unless($this->editable($company), 403);
        $data = $this->validateData($request, $type);
        $this->ensureBoardPositionAvailable($company->id, $type, $data);
        $this->ensureSharePercentageAvailable($company->id, $type, $data);
        DB::transaction(function () use ($data, $company, $request, $type) {
            $identity = filled($data['national_code'] ?? null) ? ['national_code' => $data['national_code']] : ['passport_number' => $data['passport_number']];
            $person = Person::updateOrCreate($identity, $this->personData($data));
            if (CompanyPerson::where('company_id', $company->id)->where('person_id', $person->id)->where('relation_type', $type)->where('status', '!=', 'archived')->exists()) {
                throw ValidationException::withMessages(['national_code' => 'این شخص قبلاً با همین نقش فعال در پرونده ثبت شده است.']);
            }
            CompanyPerson::create($this->relationData($data, $type) + ['company_id' => $company->id, 'person_id' => $person->id, 'created_by_user_id' => $request->user()->id, 'status' => 'draft']);
        });
        return redirect()->route('company.shahbaz.people.index', $type)->with('success', 'اطلاعات شخص ثبت شد.');
    }

    public function edit(Request $request, string $type, CompanyPerson $item)
    {
        $this->owned($request, $type, $item); abort_unless($this->editable($request->user()->company), 403);
        return view('Shahbaz.company.people.form', ['type' => $type, 'item' => $item, 'person' => $item->person] + $this->formOptions());
    }

    public function update(Request $request, string $type, CompanyPerson $item)
    {
        $this->owned($request, $type, $item); abort_unless($this->editable($request->user()->company), 403);
        $data = $this->validateData($request, $type, $item->person_id);
        $this->ensureBoardPositionAvailable($item->company_id, $type, $data, $item->id);
        $this->ensureSharePercentageAvailable($item->company_id, $type, $data, $item->id);
        DB::transaction(function () use ($data, $item, $type) { $item->person->update($this->personData($data)); $item->update($this->relationData($data, $type)); });
        return redirect()->route('company.shahbaz.people.index', $type)->with('success', 'اطلاعات به‌روزرسانی شد.');
    }

    public function archive(Request $request, string $type, CompanyPerson $item)
    {
        $this->owned($request, $type, $item); abort_unless($this->editable($request->user()->company), 403);
        $request->validate(['archive_reason' => ['required', 'string', 'max:1000']]);
        $item->update(['status' => 'archived', 'ended_on' => $item->ended_on ?: today(), 'note' => $request->archive_reason, 'archived_at' => now(), 'archived_by_user_id' => $request->user()->id]);
        return back()->with('success', 'رکورد بدون حذف سابقه بایگانی شد.');
    }

    private function validateData(Request $request, string $type, ?int $ignore = null): array
    {
        $this->mergeJalaliDates($request, [
            'birth_date_jalali' => 'birth_date',
            'issued_on_jalali' => 'issued_on',
            'started_on_jalali' => 'started_on',
            'ended_on_jalali' => 'ended_on',
        ]);
        $nationalCodeRules = ['nullable','required_without:passport_number','string','max:30'];
        $passportRules = ['nullable','required_without:national_code','string','max:50'];
        if ($ignore) { $nationalCodeRules[] = Rule::unique('shahbaz_people')->ignore($ignore); $passportRules[] = Rule::unique('shahbaz_people')->ignore($ignore); }
        $data = $request->validate([
            'nationality' => ['required','string','max:100'], 'national_code' => $nationalCodeRules,
            'passport_number' => $passportRules, 'first_name' => ['required','string','max:150'], 'last_name' => ['required','string','max:150'],
            'father_name' => ['nullable','string','max:150'], 'birth_certificate_number' => ['nullable','string','max:50'], 'birth_date' => ['nullable','date'], 'birth_place' => ['nullable','string','max:150'],
            'issued_on' => ['nullable','date'], 'issue_city' => ['nullable','string','max:150'], 'gender' => ['nullable',Rule::in(['مرد','زن'])], 'is_veteran' => ['nullable','boolean'],
            'job_title_choice' => [$type === 'personnel' ? 'required' : 'nullable','string','max:150'],
            'custom_job_title' => ['nullable', $type === 'personnel' ? 'required_if:job_title_choice,__other__' : 'sometimes', 'string', 'max:150'],
            'board_position_choice' => [$type === 'board' ? 'required' : 'nullable','string','max:150'],
            'custom_board_position' => ['nullable', $type === 'board' ? 'required_if:board_position_choice,__other__' : 'sometimes', 'string', 'max:150'],
            'shareholder_type' => [$type === 'shareholders' ? 'required' : 'nullable', Rule::in(config('shahbaz_people.shareholder_types', ['حقیقی']))],
            'share_type' => [$type === 'shareholders' ? 'required' : 'nullable', Rule::in(config('shahbaz_people.share_types', ['عادی','ممتاز']))],
            'share_amount' => [$type === 'shareholders' ? 'required' : 'nullable','numeric','min:1'],
            'share_percentage' => [$type === 'shareholders' ? 'required' : 'nullable','numeric','gt:0','max:100'], 'started_on' => ['nullable','date'], 'ended_on' => ['nullable','date','after_or_equal:started_on'], 'note' => ['nullable','string','max:2000'],
        ], [
            'custom_job_title.required_if' => 'در صورت انتخاب «سایر»، درج عنوان شغلی الزامی است.',
            'custom_job_title.string' => 'عنوان شغلی سایر باید به‌صورت متن وارد شود.',
            'job_title_choice.required' => 'انتخاب عنوان شغلی الزامی است.',
            'custom_board_position.required_if' => 'در صورت انتخاب «سایر»، درج سمت هیئت‌مدیره الزامی است.',
            'board_position_choice.required' => 'انتخاب سمت هیئت‌مدیره الزامی است.',
            'shareholder_type.required' => 'انتخاب نوع سهامدار الزامی است.',
            'share_type.required' => 'انتخاب نوع سهام الزامی است.',
            'share_amount.required' => 'مبلغ سهام الزامی است.',
            'share_percentage.required' => 'درصد سهام الزامی است.',
            'share_percentage.gt' => 'درصد سهام باید بیشتر از صفر باشد.',
        ]);
        if ($type === 'personnel') {
            $allowed = config('shahbaz_people.personnel_job_titles', []);
            if ($data['job_title_choice'] !== '__other__' && ! in_array($data['job_title_choice'], $allowed, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['job_title_choice' => 'عنوان شغلی انتخاب‌شده معتبر نیست.']);
            }
            $data['job_title'] = $data['job_title_choice'] === '__other__' ? $data['custom_job_title'] : $data['job_title_choice'];
        }
        if ($type === 'board') {
            $allowed = config('shahbaz_people.board_positions', []);
            if ($data['board_position_choice'] !== '__other__' && ! in_array($data['board_position_choice'], $allowed, true)) {
                throw ValidationException::withMessages(['board_position_choice' => 'سمت هیئت‌مدیره انتخاب‌شده معتبر نیست.']);
            }
            $data['board_position'] = $data['board_position_choice'] === '__other__' ? $data['custom_board_position'] : $data['board_position_choice'];
        }
        unset($data['job_title_choice'], $data['custom_job_title'], $data['board_position_choice'], $data['custom_board_position']);
        return $data;
    }

    private function ensureBoardPositionAvailable(int $companyId, string $type, array $data, ?int $ignoreId = null): void
    {
        if ($type !== 'board' || ($data['board_position'] ?? null) !== 'مدیرعامل') return;
        $query = CompanyPerson::where('company_id', $companyId)->where('relation_type', 'board')
            ->where('board_position', 'مدیرعامل')->where('status', '!=', 'archived');
        if ($ignoreId) $query->where('id', '!=', $ignoreId);
        if ($query->exists()) throw ValidationException::withMessages(['board_position_choice' => 'برای این شرکت قبلاً یک مدیرعامل فعال ثبت شده است. ابتدا سمت قبلی را پایان دهید یا بایگانی کنید.']);
    }

    private function ensureSharePercentageAvailable(int $companyId, string $type, array $data, ?int $ignoreId = null): void
    {
        if ($type !== 'shareholders') return;
        $query = CompanyPerson::where('company_id', $companyId)->where('relation_type', 'shareholders')->where('status', '!=', 'archived');
        if ($ignoreId) $query->where('id', '!=', $ignoreId);
        $currentTotal = (float) $query->sum('share_percentage');
        if ($currentTotal + (float) $data['share_percentage'] > 100.0000) {
            throw ValidationException::withMessages(['share_percentage' => 'مجموع درصد سهامداران فعال نمی‌تواند بیشتر از ۱۰۰٪ باشد. درصد قابل ثبت باقی‌مانده: '.max(0, 100 - $currentTotal).'٪']);
        }
    }

    private function formOptions(): array
    {
        return [
            'jobTitles' => config('shahbaz_people.personnel_job_titles', []),
            'boardPositions' => config('shahbaz_people.board_positions', []),
            'shareholderTypes' => config('shahbaz_people.shareholder_types', ['حقیقی']),
            'shareTypes' => config('shahbaz_people.share_types', ['عادی','ممتاز']),
        ];
    }

    private function mergeJalaliDates(Request $request, array $fields): void
    {
        foreach ($fields as $jalaliField => $dateField) {
            if (! $request->filled($jalaliField)) { $request->merge([$dateField => null]); continue; }
            try {
                $value = strtr((string) $request->input($jalaliField), ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']);
                $request->merge([$dateField => Jalalian::fromFormat('Y/m/d', $value)->toCarbon()->format('Y-m-d')]);
            } catch (\Throwable) {
                throw ValidationException::withMessages([$jalaliField => 'تاریخ شمسی انتخاب‌شده معتبر نیست.']);
            }
        }
    }

    private function editable($company): bool { return in_array($company->shahbaz_verification_status, ['profile_incomplete','correction_required','shahbaz_mismatch'], true); }
    private function validType(string $type): void { abort_unless(in_array($type, self::TYPES, true), 404); }
    private function owned(Request $request, string $type, CompanyPerson $item): void { $this->validType($type); abort_unless($item->company_id === $request->user()->company->id && $item->relation_type === $type, 404); }
    private function personData(array $data): array { return array_intersect_key($data, array_flip(['nationality','national_code','passport_number','first_name','last_name','father_name','birth_certificate_number','birth_date','birth_place','issued_on','issue_city','gender','is_veteran'])); }
    private function relationData(array $data, string $type): array { return array_intersect_key($data, array_flip(['job_title','board_position','shareholder_type','share_type','share_amount','share_percentage','started_on','ended_on','note'])) + ['relation_type' => $type]; }
}
