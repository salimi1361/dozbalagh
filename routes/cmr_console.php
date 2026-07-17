<?php

use App\CMR\Models\CmrDocument;
use App\CMR\Models\CmrSerial;
use App\CMR\Models\CmrSerialPool;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('cmr:doctor', function () {
    $required = [
        'cmr_documents', 'cmr_goods', 'cmr_events', 'cmr_versions', 'cmr_amendments',
        'cmr_attachments', 'cmr_signatures', 'cmr_serials', 'cmr_serial_pools',
        'cmr_parties', 'cmr_locations', 'cmr_goods_templates', 'cmr_wallet_entries',
        'driver_locations', 'driver_events', 'cmr_handover_records', 'cmr_notifications',
    ];
    $failures = [];

    foreach ($required as $table) {
        if (! Schema::hasTable($table)) {
            $failures[] = "Missing table: {$table}";
        }
    }
    if ($failures !== []) {
        foreach ($failures as $failure) $this->error($failure);
        return 1;
    }

    foreach (['driver_locations', 'driver_events'] as $table) {
        if (! Schema::hasColumn($table, 'cmr_document_id')) {
            $failures[] = "Missing CMR tracking link: {$table}.cmr_document_id";
        }
    }

    $issuedWithoutSerial = CmrDocument::whereNotNull('issued_at')->whereNull('company_serial')->count();
    $issuedWithoutVersion = CmrDocument::whereNotNull('issued_at')->whereDoesntHave('versions')->count();
    $issuedWithoutDebit = CmrDocument::whereNotNull('issued_at')->where('issuance_fee', '>', 0)->whereDoesntHave('walletEntries')->count();
    $usedSerialWithoutDocument = CmrSerial::where('status', 'used')->whereNull('cmr_document_id')->count();
    $invalidCurrentHashes = CmrDocument::whereNotNull('issued_at')->get()->filter(function ($document) {
        $version = $document->versions()->where('version', $document->version)->first();
        return ! $version || ! hash_equals((string) $version->integrity_hash, (string) $document->integrity_hash);
    })->count();
    $orphanLocations = DB::table('driver_locations')->whereNotNull('cmr_document_id')
        ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('cmr_documents')->whereColumn('cmr_documents.id', 'driver_locations.cmr_document_id'))->count();

    foreach ([
        'Issued documents without official company serial' => $issuedWithoutSerial,
        'Issued documents without a version snapshot' => $issuedWithoutVersion,
        'Paid issuances without a wallet debit' => $issuedWithoutDebit,
        'Used serials without a linked document' => $usedSerialWithoutDocument,
        'Documents with an invalid current version hash' => $invalidCurrentHashes,
        'Orphan CMR tracking locations' => $orphanLocations,
    ] as $label => $count) {
        $count === 0 ? $this->info("PASS: {$label}") : $failures[] = "{$label}: {$count}";
    }

    $remaining = (int) CmrSerial::where('status', 'available')->count()
        + (int) CmrSerialPool::where('is_active', true)->selectRaw('COALESCE(SUM(GREATEST(range_end-next_number+1,0)),0) remaining')->value('remaining');
    $this->line("Official serials remaining: {$remaining}");

    if ($failures !== []) {
        foreach ($failures as $failure) $this->error("FAIL: {$failure}");
        return 1;
    }

    $this->info('CMR doctor completed successfully.');
    return 0;
})->purpose('Verify the e-CMR schema, serials, billing, version integrity, and tracking links');
