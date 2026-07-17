<?php

namespace App\CMR\Services;

use App\CMR\Models\CmrDocument;
use App\CMR\Models\CmrNotification;
use App\Models\MobileAppVersion;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class CmrDriverNotificationService
{
    public function __construct(private readonly SmsService $sms) {}

    public function sendIssued(int $documentId): void
    {
        try {
            $cmr=CmrDocument::with(['driver','company'])->findOrFail($documentId);
            $mobile=trim((string)$cmr->driver?->mobile);
            if($mobile===''){Log::warning("CMR {$cmr->id} issuance SMS skipped: driver mobile is empty.");return;}
            $version=MobileAppVersion::query()->where('platform','android')->where('is_active',true)
                ->where(fn($q)=>$q->whereNull('published_at')->orWhere('published_at','<=',now()))->latest('published_at')->first();
            $downloadUrl=trim((string)$version?->download_url);
            $number=$cmr->company_serial?:$cmr->number;
            $company=$cmr->company?->name_fa?:$cmr->company?->name_en?:'شرکت حمل‌ونقل';
            $message="سامانه e-CMR\nراننده گرامی، CMR شماره {$number} توسط {$company} برای شما صادر شد.";
            if($downloadUrl!=='')$message.="\nنصب یا به‌روزرسانی اپ راننده:\n{$downloadUrl}";
            $notification=CmrNotification::firstOrCreate(['cmr_document_id'=>$cmr->id,'type'=>'issued_driver','recipient'=>$mobile],['channel'=>'sms','status'=>'pending','attempts'=>0,'message'=>$message,'download_url'=>$downloadUrl?:null,'metadata'=>['app_version'=>$version?->version_name,'app_build'=>$version?->latest_build]]);
            if($notification->status==='sent')return;
            $notification->increment('attempts');
            $sent=$this->sms->send($mobile,$message,'cmr_issued_driver');
            $notification->update(['status'=>$sent?'sent':'failed','sent_at'=>$sent?now():null,'last_error'=>$sent?null:'SMS provider rejected the request or is not configured.','message'=>$message,'download_url'=>$downloadUrl?:null]);
        } catch(\Throwable $exception) { Log::warning('CMR driver issuance notification failed: '.$exception->getMessage()); }
    }
}
