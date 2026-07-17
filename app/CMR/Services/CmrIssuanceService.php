<?php

namespace App\CMR\Services;

use App\CMR\Models\CmrDocument;
use App\CMR\Models\CmrEvent;
use App\CMR\Models\CmrSetting;
use App\CMR\Models\CmrVersion;
use App\CMR\Models\CmrWalletEntry;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CmrIssuanceService
{
    public function __construct(private readonly CmrSerialService $serials) {}

    public function issue(CmrDocument $document, int $userId): CmrDocument
    {
        return DB::transaction(function () use ($document, $userId) {
            $document = CmrDocument::query()->lockForUpdate()->findOrFail($document->id);
            if ($document->status !== 'draft') {
                throw new RuntimeException('فقط پیش‌نویس CMR قابل صدور است.');
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
