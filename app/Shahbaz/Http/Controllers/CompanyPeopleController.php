<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Models\CompanyPerson;
use App\Shahbaz\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        return view('Shahbaz.company.people.form', ['type' => $type, 'item' => new CompanyPerson, 'person' => new Person, 'jobTitles' => config('shahbaz_people.personnel_job_titles', [])]);
    }

    public function store(Request $request, string $type)
    {
        $this->validType($type); $company = $request->user()->company; abort_unless($this->editable($company), 403);
        $data = $this->validateData($request, $type);
        DB::transaction(function () use ($data, $company, $request, $type) {
            $identity = filled($data['national_code'] ?? null) ? ['national_code' => $data['national_code']] : ['passport_number' => $data['passport_number']];
            $person = Person::updateOrCreate($identity, $this->personData($data));
            CompanyPerson::create($this->relationData($data, $type) + ['company_id' => $company->id, 'person_id' => $person->id, 'created_by_user_id' => $request->user()->id, 'status' => 'draft']);
        });
        return redirect()->route('company.shahbaz.people.index', $type)->with('success', 'اطلاعات شخص ثبت شد.');
    }

    public function edit(Request $request, string $type, CompanyPerson $item)
    {
        $this->owned($request, $type, $item); abort_unless($this->editable($request->user()->company), 403);
        return view('Shahbaz.company.people.form', ['type' => $type, 'item' => $item, 'person' => $item->person, 'jobTitles' => config('shahbaz_people.personnel_job_titles', [])]);
    }

    public function update(Request $request, string $type, CompanyPerson $item)
    {
        $this->owned($request, $type, $item); abort_unless($this->editable($request->user()->company), 403);
        $data = $this->validateData($request, $type, $item->person_id);
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
        $data = $request->validate([
            'nationality' => ['required','string','max:100'], 'national_code' => ['nullable','required_without:passport_number','string','max:30',Rule::unique('shahbaz_people')->ignore($ignore)],
            'passport_number' => ['nullable','required_without:national_code','string','max:50',Rule::unique('shahbaz_people')->ignore($ignore)], 'first_name' => ['required','string','max:150'], 'last_name' => ['required','string','max:150'],
            'father_name' => ['nullable','string','max:150'], 'birth_certificate_number' => ['nullable','string','max:50'], 'birth_date' => ['nullable','date'], 'birth_place' => ['nullable','string','max:150'],
            'issued_on' => ['nullable','date'], 'issue_city' => ['nullable','string','max:150'], 'gender' => ['nullable',Rule::in(['مرد','زن'])], 'is_veteran' => ['nullable','boolean'],
            'job_title_choice' => [$type === 'personnel' ? 'required' : 'nullable','string','max:150'],
            'custom_job_title' => [$type === 'personnel' ? 'required_if:job_title_choice,__other__' : 'nullable','string','max:150'],
            'board_position' => [$type === 'board' ? 'required' : 'nullable','string','max:150'],
            'shareholder_type' => [$type === 'shareholders' ? 'required' : 'nullable','string','max:50'], 'share_type' => ['nullable','string','max:50'], 'share_amount' => ['nullable','numeric','min:0'],
            'share_percentage' => ['nullable','numeric','min:0','max:100'], 'started_on' => ['nullable','date'], 'ended_on' => ['nullable','date','after_or_equal:started_on'], 'note' => ['nullable','string','max:2000'],
        ]);
        if ($type === 'personnel') {
            $allowed = config('shahbaz_people.personnel_job_titles', []);
            if ($data['job_title_choice'] !== '__other__' && ! in_array($data['job_title_choice'], $allowed, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['job_title_choice' => 'عنوان شغلی انتخاب‌شده معتبر نیست.']);
            }
            $data['job_title'] = $data['job_title_choice'] === '__other__' ? $data['custom_job_title'] : $data['job_title_choice'];
        }
        unset($data['job_title_choice'], $data['custom_job_title']);
        return $data;
    }

    private function editable($company): bool { return in_array($company->shahbaz_verification_status, ['profile_incomplete','correction_required','shahbaz_mismatch'], true); }
    private function validType(string $type): void { abort_unless(in_array($type, self::TYPES, true), 404); }
    private function owned(Request $request, string $type, CompanyPerson $item): void { $this->validType($type); abort_unless($item->company_id === $request->user()->company->id && $item->relation_type === $type, 404); }
    private function personData(array $data): array { return array_intersect_key($data, array_flip(['nationality','national_code','passport_number','first_name','last_name','father_name','birth_certificate_number','birth_date','birth_place','issued_on','issue_city','gender','is_veteran'])); }
    private function relationData(array $data, string $type): array { return array_intersect_key($data, array_flip(['job_title','board_position','shareholder_type','share_type','share_amount','share_percentage','started_on','ended_on','note'])) + ['relation_type' => $type]; }
}
