<?php

namespace App\CMR\Services;

use App\CMR\Models\CmrCompanySetting;
use App\CMR\Models\CmrDocument;
use App\CMR\Models\CmrSerial;
use App\CMR\Models\CmrSerialPool;
use RuntimeException;

class CmrSerialService
{
    public function allocate(CmrDocument $document): ?string
    {
        $settings = CmrCompanySetting::forCompany((int) $document->company_id);

        if ($settings->serial_mode === 'system') {
            return null;
        }

        if ($settings->serial_mode === 'manual') {
            $serial = trim((string) $document->company_serial);
            if ($serial === '') {
                throw new RuntimeException('شماره سریال CMR شرکت پیش از صدور الزامی است.');
            }
            $this->assertUnique($document, $serial);
            return $serial;
        }

        $pool = CmrSerialPool::query()
            ->where('company_id', $document->company_id)
            ->where('is_active', true)
            ->whereColumn('next_number', '<=', 'range_end')
            ->lockForUpdate()
            ->first();
        if (! $pool) {
            throw new RuntimeException('بازه شماره سریال فعال یا شماره آزاد برای شرکت وجود ندارد.');
        }

        $serial = (string) ($pool->prefix ?? '').$pool->next_number;
        $this->assertUnique($document, $serial);
        $record = CmrSerial::create([
            'company_id' => $document->company_id,
            'serial_pool_id' => $pool->id,
            'serial' => $serial,
            'status' => 'used',
            'cmr_document_id' => $document->id,
            'reserved_at' => now(),
            'used_at' => now(),
        ]);
        $pool->increment('next_number');

        return $record->serial;
    }

    private function assertUnique(CmrDocument $document, string $serial): void
    {
        $exists = CmrDocument::query()
            ->where('company_id', $document->company_id)
            ->where('company_serial', $serial)
            ->where('id', '!=', $document->id)
            ->exists();
        if ($exists) {
            throw new RuntimeException('این شماره سریال قبلاً برای شرکت مصرف شده است.');
        }
    }
}
