<?php

namespace Tests\Feature;

use App\Models\PermitRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PermitRequestCompanyNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_permit_requests_support_a_separate_company_note_for_the_association(): void
    {
        $this->assertTrue(Schema::hasColumn('permit_requests', 'company_request_note'));

        $permitRequest = new PermitRequest();
        $permitRequest->fill([
            'company_request_note' => 'توضیح تکمیلی شرکت برای بررسی انجمن',
        ]);

        $this->assertSame('توضیح تکمیلی شرکت برای بررسی انجمن', $permitRequest->company_request_note);
    }
}
