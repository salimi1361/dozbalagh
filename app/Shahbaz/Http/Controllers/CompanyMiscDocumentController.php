<?php

namespace App\Shahbaz\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shahbaz\Models\MiscDocument;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyMiscDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->company;

        return view('Shahbaz.company.misc-documents.index', [
            'company' => $company,
            'documents' => MiscDocument::where('company_id', $company->id)->latest()->get(),
            'editable' => in_array($company->shahbaz_verification_status, ['profile_incomplete', 'correction_required', 'shahbaz_mismatch'], true),
        ]);
    }

    public function store(Request $request)
    {
        $company = $request->user()->company;
        abort_unless(in_array($company->shahbaz_verification_status, ['profile_incomplete', 'correction_required', 'shahbaz_mismatch'], true), 403);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
        ]);
        $file = $request->file('document');
        $path = $file->storeAs(
            'shahbaz/misc-documents/'.$company->id,
            Str::uuid().'.'.$file->getClientOriginalExtension(),
            'local'
        );

        MiscDocument::create([
            'company_id' => $company->id,
            'subject' => $data['subject'],
            'description' => $data['description'] ?? null,
            'storage_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => 'active',
            'uploaded_by_user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'مدرک متفرقه با موفقیت ثبت شد.');
    }

    public function download(Request $request, MiscDocument $document): StreamedResponse
    {
        abort_unless($document->company_id === $request->user()->company->id, 403);
        abort_unless(Storage::disk('local')->exists($document->storage_path), 404);

        return Storage::disk('local')->download($document->storage_path, $document->original_name);
    }

    public function archive(Request $request, MiscDocument $document)
    {
        $company = $request->user()->company;
        abort_unless($document->company_id === $company->id, 403);
        abort_unless(in_array($company->shahbaz_verification_status, ['profile_incomplete', 'correction_required', 'shahbaz_mismatch'], true), 403);

        if ($document->status !== 'archived') {
            $document->update([
                'status' => 'archived',
                'archived_at' => now(),
                'archived_by_user_id' => $request->user()->id,
            ]);
        }

        return back()->with('success', 'مدرک بایگانی شد و فایل آن در سوابق باقی ماند.');
    }
}
