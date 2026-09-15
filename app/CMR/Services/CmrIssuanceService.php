<?php

namespace App\CMR\Services;

use App\CMR\Models\CmrDocument;
use App\CMR\Models\CmrEvent;
use App\CMR\Models\CmrCompanySetting;
use App\CMR\Models\CmrSetting;
use App\CMR\Models\CmrVersion;
use App\CMR\Models\CmrWalletEntry;
use App\Models\Wallet;
use App\Models\Driver;
use App\Models\Fleet;
use App\Shahbaz\Services\CompanyEligibilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CmrIssuanceService
{
    public function __construct(
        private readonly CmrSerialService $serials,
        private readonly CompanyEligibilityService $companyEligibility,
    ) {}

    public function issue(CmrDocument $document, int $userId): CmrDocument
    {
        return DB::transaction(function () use ($document, $userId) {
            $document = CmrDocument::query()->lockForUpdate()->findOrFail($document->id);
            $document->loadMissing('company');
            if (!$document->company || !$this->companyEligibility->canOperate($document->company)) {
                throw new RuntimeException('اطلاعات شرکت، کنترل شحباز یا پروانه فعالیت معتبر نیست؛ صدور CMR مجاز نیست.');
            }
            if ($document->status !== 'draft') {
                throw new RuntimeException('فقط پیش‌نویس CMR قابل صدور است.');
            }
            if ($document->driver_id && ! Driver::active()->whereKey($document->driver_id)->exists()) {
                throw new RuntimeException('راننده این CMR توسط انجمن غیرفعال شده است و صدور سند امکان‌پذیر نیست.');
            }
            if ($document->fleet_id && ! Fleet::active()->whereKey($document->fleet_id)->exists()) {
                throw new RuntimeException('ناوگان این CMR توسط انجمن غیرفعال شده است و صدور سند امکان‌پذیر نیست.');
            }

            $assignmentPolicy = CmrCompanySetting::forCompany((int) $document->company_id)->assignment_policy;
            if ($assignmentPolicy === 'same_company') {
                if ($document->driver_id && (int) Driver::findOrFail($document->driver_id)->current_company_id !== (int) $document->company_id) {
                    throw new RuntimeException('The selected driver is no longer assigned to this company.');
                }
                if ($document->fleet_id && (int) Fleet::findOrFail($document->fleet_id)->company_id !== (int) $document->company_id) {
                    throw new RuntimeException('The selected fleet is no longer assigned to this company.');
                }
            }

            $settings = CmrSetting::current();
            $fee = $settings->billing_enabled ? (float) $settings->issuance_fee : 0.0;
            $wallet = null;
            if ($fee > 0) {
                Wallet::firstOrCreate(['company_id' => $document->company_id], ['balance' => 0, 'blocked_balance' => 0]);
                $wallet = Wallet::query()->where('company_id', $document->company_id)->lockForUpdate()->firstOrFail();
                $available = (float) $wallet->balance - (float) $wallet->blocked_balance;
                if ($available < $fee) {
                    throw new RuntimeException('موجودی قابل استفاده کیف پول شرکت برای صدور CMR کافی نیست.');
                }
            }

            $number = 'ECMR-'.now()->format('Y').'-'.str_pad((string) $document->id, 8, '0', STR_PAD_LEFT);
            $companySerial = $this->serials->allocate($document);
            $issuedAt = now();
            $snapshot = $this->snapshot($document->load('goods'), [
                'number' => $number,
                'company_serial' => $companySerial ?: $document->company_serial,
                'status' => 'issued',
                'issued_at' => $issuedAt->toIso8601String(),
                'issuance_fee' => $fee,
                'currency' => $settings->currency,
            ]);
            $hash = hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $verificationCode = hash('sha256', $document->uuid.'|'.$hash.'|'.Str::random(32));

            if ($fee > 0) {
                $before = (float) $wallet->balance;
                $wallet->decrement('balance', $fee);
                CmrWalletEntry::create([
                    'idempotency_key' => (string) Str::uuid(),
                    'cmr_document_id' => $document->id,
                    'wallet_id' => $wallet->id,
                    'type' => 'issuance_debit',
                    'amount' => $fee,
                    'currency' => $settings->currency,
                    'balance_before' => $before,
                    'balance_after' => $before - $fee,
                    'created_by' => $userId,
                    'description' => 'هزینه صدور '.$number,
                ]);
            }

            $document->update([
                'number' => $number,
                'company_serial' => $companySerial ?: $document->company_serial,
                'status' => 'issued',
                'issued_at' => $issuedAt,
                'issued_by' => $userId,
                'issuance_fee' => $fee,
                'currency' => $settings->currency,
                'integrity_hash' => $hash,
                'verification_code' => $verificationCode,
            ]);

            CmrVersion::create([
                'cmr_document_id' => $document->id,
                'version' => 1,
                'snapshot' => $snapshot,
                'integrity_hash' => $hash,
                'created_by' => $userId,
                'reason' => 'صدور اولیه',
            ]);
            CmrEvent::create([
                'cmr_document_id' => $document->id,
                'event_type' => 'issued',
                'from_status' => 'draft',
                'to_status' => 'issued',
                'actor_user_id' => $userId,
                'actor_role' => 'admin',
                'description' => 'CMR صادر شد.',
                'metadata' => ['fee' => $fee, 'currency' => $settings->currency, 'hash' => $hash],
                'occurred_at' => now(),
            ]);

            DB::afterCommit(fn () => app(CmrDriverNotificationService::class)->sendIssued((int)$document->id));

            return $document->fresh(['company', 'driver', 'fleet', 'goods']);
        });
    }

    private function snapshot(CmrDocument $document, array $issuanceData): array
    {
        return collect($document->attributesToArray())
            ->except(['created_at', 'updated_at', 'deleted_at', 'issued_by', 'integrity_hash'])
            ->merge($issuanceData)
            ->merge(['goods' => $document->goods->map(fn ($good) => collect($good->attributesToArray())->except(['created_at', 'updated_at'])->all())->all()])
            ->all();
    }
}
