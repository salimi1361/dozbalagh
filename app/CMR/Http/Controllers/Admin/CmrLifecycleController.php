<?php

namespace App\CMR\Http\Controllers\Admin;

use App\CMR\Models\CmrAmendment;
use App\CMR\Models\CmrAttachment;
use App\CMR\Models\CmrDocument;
use App\CMR\Models\CmrEvent;
use App\CMR\Models\CmrSignature;
use App\CMR\Models\CmrVersion;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CmrLifecycleController extends Controller
{
    public function evidence(CmrDocument $cmr)
    {
        $cmr->load(['company', 'goods', 'versions', 'amendments', 'attachments', 'signatures', 'events', 'walletEntries']);
        $versions = $cmr->versions->sortBy('version')->values();
        $amendments = $cmr->amendments->sortBy('to_version')->values();
        $chainValid = $versions->isNotEmpty()
            && $versions->every(fn ($version) => filled($version->integrity_hash))
            && $amendments->every(function ($amendment) use ($versions) {
                $before = $versions->firstWhere('version', $amendment->from_version);
                $after = $versions->firstWhere('version', $amendment->to_version);
                return $before && $after
                    && hash_equals((string) $before->integrity_hash, (string) $amendment->previous_hash)
                    && hash_equals((string) $after->integrity_hash, (string) $amendment->new_hash);
            });

        $manifest = [
            'format' => 'dozbalagh-e-cmr-evidence-v1',
            'generated_at' => now()->toIso8601String(),
            'verification_url' => $cmr->verification_code ? route('cmr.verify', $cmr->verification_code) : null,
            'chain_valid' => $chainValid,
            'document' => $cmr->attributesToArray(),
            'goods' => $cmr->goods->toArray(),
            'versions' => $versions->toArray(),
            'amendments' => $amendments->toArray(),
            'signatures' => $cmr->signatures->toArray(),
            'attachments' => $cmr->attachments->map->only(['document_type', 'original_name', 'mime_type', 'size_bytes', 'sha256', 'created_at'])->all(),
            'events' => $cmr->events->toArray(),
            'billing' => $cmr->walletEntries->toArray(),
        ];

        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $filename = 'e-CMR-'.($cmr->company_serial ?: $cmr->number ?: $cmr->id).'-evidence.json';

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.str_replace('"', '', $filename).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function amend(Request $request, CmrDocument $cmr)
    {
        $latin = 'regex:/^[\p{Latin}\p{N}\p{P}\p{Z}\r\n]+$/u';
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'delivery_place' => ['nullable', 'string', 'max:255', $latin],
            'planned_delivery_at' => ['nullable', 'date'],
            'sender_instructions' => ['nullable', 'string', $latin],
            'carrier_reservations' => ['nullable', 'string', $latin],
            'special_agreements' => ['nullable', 'string', $latin],
        ]);

        DB::transaction(function () use ($cmr, $data) {
            $document = CmrDocument::query()->lockForUpdate()->findOrFail($cmr->id);
            if (! in_array($document->status, ['issued', 'accepted', 'in_transit'], true)) {
                throw new \RuntimeException('فقط سند جاری و صادرشده قابل اصلاح نسخه‌دار است.');
            }

            $reason = $data['reason'];
            unset($data['reason']);
            $changes = collect($data)->filter(fn ($value, $field) => $value !== null && (string) $document->{$field} !== (string) $value)->all();
            if ($changes === []) {
                throw new \RuntimeException('هیچ تغییری برای ثبت وارد نشده است.');
            }

            $previousHash = $document->integrity_hash;
            $fromVersion = (int) $document->version;
            $toVersion = $fromVersion + 1;
            $document->fill($changes);
            $snapshot = collect($document->attributesToArray())
                ->except(['created_at', 'updated_at', 'deleted_at', 'integrity_hash'])
                ->merge(['version' => $toVersion, 'goods' => $document->goods()->get()->toArray()])->all();
            $newHash = hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $document->forceFill(['version' => $toVersion, 'integrity_hash' => $newHash])->save();

            CmrVersion::create(['cmr_document_id' => $document->id, 'version' => $toVersion, 'snapshot' => $snapshot, 'integrity_hash' => $newHash, 'created_by' => auth()->id(), 'reason' => $reason]);
            CmrAmendment::create(['cmr_document_id' => $document->id, 'from_version' => $fromVersion, 'to_version' => $toVersion, 'reason' => $reason, 'changes' => $changes, 'previous_hash' => $previousHash, 'new_hash' => $newHash, 'created_by' => auth()->id()]);
            CmrEvent::create(['cmr_document_id' => $document->id, 'event_type' => 'amended', 'from_status' => $document->status, 'to_status' => $document->status, 'actor_user_id' => auth()->id(), 'actor_role' => 'admin', 'description' => $reason, 'metadata' => ['from_version' => $fromVersion, 'to_version' => $toVersion, 'changes' => array_keys($changes)], 'occurred_at' => now()]);
        });

        return back()->with('success', 'اصلاحیه ثبت شد و نسخه جدید سند ایجاد گردید.');
    }

    public function upload(Request $request, CmrDocument $cmr)
    {
        $data = $request->validate(['document_type' => ['required', 'string', 'max:100'], 'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png']]);
        $file = $data['file'];
        $hash = hash_file('sha256', $file->getRealPath());
        $path = $file->storeAs('cmr/'.$cmr->uuid.'/attachments', Str::uuid().'.'.$file->getClientOriginalExtension(), 'local');
        CmrAttachment::create(['cmr_document_id' => $cmr->id, 'document_type' => $data['document_type'], 'original_name' => $file->getClientOriginalName(), 'storage_path' => $path, 'mime_type' => $file->getMimeType() ?: 'application/octet-stream', 'size_bytes' => $file->getSize(), 'sha256' => $hash, 'uploaded_by_user_id' => auth()->id()]);
        return back()->with('success', 'پیوست با اثر انگشت SHA-256 ذخیره شد.');
    }

    public function download(CmrDocument $cmr, CmrAttachment $attachment)
    {
        abort_unless((int) $attachment->cmr_document_id === (int) $cmr->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->storage_path), 404);
        return Storage::disk('local')->download($attachment->storage_path, $attachment->original_name);
    }

    public function sign(Request $request, CmrDocument $cmr)
    {
        $data = $request->validate(['signer_role' => ['required', 'in:sender,carrier'], 'signer_name' => ['required', 'string', 'max:255'], 'signer_identifier' => ['nullable', 'string', 'max:255'], 'reservation' => ['nullable', 'string', 'max:2000'], 'confirmed' => ['accepted']]);
        abort_unless(in_array($cmr->status, ['issued', 'accepted', 'in_transit', 'delivered'], true), 422);
        $signedAt = now();
        $evidence = ['cmr_id' => $cmr->id, 'version' => $cmr->version, 'role' => $data['signer_role'], 'name' => $data['signer_name'], 'document_hash' => $cmr->integrity_hash, 'signed_at' => $signedAt->toIso8601String(), 'ip' => $request->ip()];
        CmrSignature::updateOrCreate(['cmr_document_id' => $cmr->id, 'document_version' => $cmr->version, 'signer_role' => $data['signer_role']], ['signer_name' => $data['signer_name'], 'signer_identifier' => $data['signer_identifier'] ?? null, 'reservation' => $data['reservation'] ?? null, 'document_hash' => $cmr->integrity_hash, 'evidence_hash' => hash('sha256', json_encode($evidence, JSON_UNESCAPED_UNICODE)), 'user_id' => auth()->id(), 'ip_address' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000), 'signed_at' => $signedAt]);
        CmrEvent::create(['cmr_document_id' => $cmr->id, 'event_type' => 'signed', 'from_status' => $cmr->status, 'to_status' => $cmr->status, 'actor_user_id' => auth()->id(), 'actor_role' => $data['signer_role'], 'description' => 'Electronic acknowledgement recorded.', 'metadata' => ['version' => $cmr->version, 'role' => $data['signer_role']], 'occurred_at' => $signedAt]);
        return back()->with('success', 'تأیید الکترونیکی برای نسخه جاری ثبت شد.');
    }

    public function finalize(CmrDocument $cmr)
    {
        try {
            DB::transaction(function () use ($cmr) {
            $document = CmrDocument::query()->lockForUpdate()->findOrFail($cmr->id);
            if ($document->status !== 'delivered') {
                throw new \RuntimeException('فقط CMR تحویل‌شده قابل نهایی‌سازی است.');
            }
            $roles = CmrSignature::where('cmr_document_id', $document->id)
                ->where('document_version', $document->version)->pluck('signer_role');
            if (! $roles->contains('driver') || ! $roles->contains('consignee')) {
                throw new \RuntimeException('تأیید راننده و گیرنده برای نسخه جاری الزامی است.');
            }

            $finalizedAt = now();
            $snapshot = collect($document->load(['goods', 'signatures'])->attributesToArray())
                ->except(['created_at', 'updated_at', 'deleted_at', 'integrity_hash'])
                ->merge(['status' => 'finalized', 'finalized_at' => $finalizedAt->toIso8601String(), 'goods' => $document->goods->toArray(), 'signatures' => $document->signatures->where('document_version', $document->version)->values()->toArray()])->all();
            $hash = hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $document->update(['status' => 'finalized', 'finalized_at' => $finalizedAt, 'integrity_hash' => $hash]);
            CmrVersion::updateOrCreate(['cmr_document_id' => $document->id, 'version' => $document->version], ['snapshot' => $snapshot, 'integrity_hash' => $hash, 'created_by' => auth()->id(), 'reason' => 'Final delivery record']);
            CmrEvent::create(['cmr_document_id' => $document->id, 'event_type' => 'finalized', 'from_status' => 'delivered', 'to_status' => 'finalized', 'actor_user_id' => auth()->id(), 'actor_role' => 'admin', 'description' => 'Final delivery record locked.', 'metadata' => ['version' => $document->version, 'hash' => $hash], 'occurred_at' => $finalizedAt]);
            });
            return back()->with('success', 'سند نهایی و قفل شد.');
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['finalize' => $exception->getMessage()]);
        }
    }
}
