<?php

namespace App\Http\Controllers\Association;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

class AssociationController
{
    /**
     * نمایش لیست درخواست‌های منتظر بررسی (Pending) در کارتابل انجمن
     */
    public function index()
    {
        try {
            $requests = DB::table('permit_requests')
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            
            foreach ($requests as $req) {
                $req->driver = null;
                if (isset($req->driver_id)) {
                    $req->driver = DB::table('drivers')->where('id', $req->driver_id)->first() 
                                ?? DB::table('drivers')->where('national_code', $req->driver_id)->first();
                }

                $req->fleet = null;
                if (isset($req->fleet_id)) {
                    $req->fleet = DB::table('fleets')->where('id', $req->fleet_id)->first() 
                               ?? DB::table('fleets')->where('smart_id', $req->fleet_id)->first()
                               ?? DB::table('fleets')->where('smart_card_number', $req->fleet_id)->first();
                }
                
                $destinations = DB::table('permit_request_items')
                    ->where('permit_request_id', $req->id)
                    ->get();
                
                $countryNames = [];
                foreach ($destinations as $dest) {
                    $countryInfo = DB::table('countries')->where('id', $dest->country_id)->first();
                    if ($countryInfo) {
                        $countryNames[] = $countryInfo->name;
                    }
                }
                $req->country_name = !empty($countryNames) ? implode('، ', $countryNames) : 'نامشخص';
            }
            
            return view('association.driver.index', compact('requests'));
        } catch (Throwable $e) {
            Log::error('Association Index Error: ' . $e->getMessage());
            return abort(500, 'خطا در کارتابل انجمن: ' . $e->getMessage());
        }
    }

    /**
     * مدیریت دکمه‌های تغییر وضعیت سریع و ثبت ردیابی تاریخ و ساعت اتمیک
     */
    public function updateRequestStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
            'reject_reason' => 'nullable|string',
            'image' => 'nullable|image|max:5120'
        ]);

        DB::beginTransaction();
        try {
            $permit = DB::table('permit_requests')->where('id', $id)->first();
            if (!$permit) {
                return response()->json(['success' => false, 'message' => 'درخواست یافت نشد.'], 404);
            }

            $now = Carbon::now();
            $userId = auth()->id() ?? null;
            $status = $request->input('status');

            $updateData = [
                'status' => $status,
                'reject_reason' => $request->input('reject_reason') ?? null,
                'action_by_user_id' => $userId,
                'updated_at' => $now
            ];

            if ($status === 'rejected') {
                $updateData['rejected_at'] = $now;

                $wallet = DB::table('wallets')->where('company_id', $permit->company_id)->first();
                if ($wallet) {
                    DB::table('wallets')->where('id', $wallet->id)->update([
                        'blocked_balance' => $wallet->blocked_balance - $permit->total_amount,
                        'balance' => $wallet->balance + $permit->total_amount,
                        'updated_at' => $now
                    ]);

                    DB::table('wallet_transactions')->insert([
                        'wallet_id' => $wallet->id,
                        'amount' => $permit->total_amount,
                        'type' => 'credit',
                        'action_type' => 'credit', 
                        'description' => "برگشت کل وجه به دلیل رد درخواست دوزوله پرونده {$permit->d_code}",
                        'transaction_month' => $now->format('Y-m'),
                        'created_at' => $now, 
                        'updated_at' => $now
                    ]);
                }
            } 
            elseif ($status === 'approved') {
                $updateData['approved_at'] = $now;
            }
            elseif ($status === 'returned') {
                $updateData['returned_at'] = $now;
            }
            elseif ($status === 'collected' || $status === 'lost' || $status === 'archived') {
                
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $serialClean = $permit->serial_number ?? 'serial';
                    $fileName = $serialClean . '-' . $permit->d_code . '.' . $file->getClientOriginalExtension();
                    $file->storeAs('permits/collected', $fileName, 'public');
                    $updateData['collected_image'] = 'permits/collected/' . $fileName;
                }

                $updateData['closed_at'] = $now;

                if (isset($permit->driver_id)) {
                    DB::table('drivers')->where('id', $permit->driver_id)->orWhere('national_code', $permit->driver_id)->update(['is_blocked' => false]);
                }
                if (isset($permit->fleet_id)) {
                    DB::table('fleets')->where('id', $permit->fleet_id)->orWhere('smart_card_number', $permit->fleet_id)->update(['is_blocked' => false]);
                }

                try {
                    if (isset($permit->serial_number)) {
                        DB::table('dozbalagh_items')
                            ->where('serial_number', $permit->serial_number)
                            ->update([
                                'lifecycle_status' => $status,
                                'returned_at'      => $now,
                                'updated_at'       => $now
                            ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Dozbalagh Items Warehouse Update Bypassed: ' . $e->getMessage());
                }
            }

            DB::table('permit_requests')->where('id', $id)->update($updateData);

            DB::commit();
            
            $messages = [
                'approved' => 'درخواست تایید اولیه شد و به کارتابل تخصیص سریال انتقال یافت.',
                'rejected' => 'درخواست رد و وجه عودت داده شد.',
                'returned' => 'پرونده جهت اصلاح به شرکت برگشت خورد.',
                'collected' => 'تصویر لاشه دوزبِلاغ با موفقیت فشرده، بایگانی و ناوگان آزاد شد.',
                'archived' => 'لاشه پروانه با موفقیت تحویل گرفته شد و پرونده به بایگانی کل منتقل گردید.',
                'lost' => 'وضعیت مفقودی ثبت و راننده آزاد شد.'
            ];

            return response()->json(['success' => true, 'message' => $messages[$status] ?? 'عملیات با موفقیت انجام شد.'] );
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Update Request Status Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'خطا در پردازش عملیات مالی و اداری: ' . $e->getMessage()], 500);
        }
    }

    /**
     * بارگذاری لیست دوزوله‌های تایید اولیه شده که منتظر درج شماره سریال و چاپ هستند
     */
    public function associationApprovedPermits()
    {
        try {
            $requests = DB::table('permit_requests')
                ->where('status', 'approved')
                ->whereNull('serial_number')
                ->orderBy('id', 'desc')
                ->paginate(10);

            foreach ($requests as $req) {
                $req->driver = null;
                if (isset($req->driver_id)) {
                    $req->driver = DB::table('drivers')->where('id', $req->driver_id)->first() 
                                ?? DB::table('drivers')->where('national_code', $req->driver_id)->first();
                }

                $req->fleet = null;
                if (isset($req->fleet_id)) {
                    $req->fleet = DB::table('fleets')->where('id', $req->fleet_id)->first() 
                               ?? DB::table('fleets')->where('smart_card_number', $req->fleet_id)->first();
                }
                
                $destination = DB::table('permit_request_items')->where('permit_request_id', $req->id)->first();
                
                $req->country_name = 'نامشخص';
                $req->next_serial_in_warehouse = 'بدون موجودی';
                
                // 🟢 متغیرهای جدید برای محاسبه و ارسال اتوماتیک به فرانت‌اِند
                $req->validity_days = 30; // پیش‌فرض
                $req->expire_date_jalali = '---';

                if ($destination) {
                    $countryInfo = DB::table('countries')->where('id', $destination->country_id)->first();
                    if ($countryInfo) {
                        $req->country_name = $countryInfo->name;
                        
                        // واکشی تعداد روز اعتبار از کشور
                        $req->validity_days = $countryInfo->validity_days ?? 30;
                        
                        // 🟢 محاسبه خودکار تاریخ پایان در بک‌اند برای نمایش به اپراتور
                        $expireDate = now()->addDays($req->validity_days);
                        try {
                            $req->expire_date_jalali = \Morilog\Jalali\Jalalian::fromCarbon($expireDate)->format('Y/m/d');
                        } catch (\Exception $e) {
                            $req->expire_date_jalali = $expireDate->format('Y-m-d');
                        }
                    }

                    $nextAvailableItem = DB::table('dozbalagh_items')
                        ->join('dozbalagh_batches', 'dozbalagh_items.batch_id', '=', 'dozbalagh_batches.id')
                        ->where('dozbalagh_batches.country_id', $destination->country_id)
                        ->where('dozbalagh_items.lifecycle_status', 'raw')
                        ->orderBy('dozbalagh_items.serial_number', 'asc')
                        ->select('dozbalagh_items.serial_number')
                        ->first();

                    if ($nextAvailableItem) {
                        $req->next_serial_in_warehouse = $nextAvailableItem->serial_number;
                    }
                }
            }

            return view('association.driver.approved', compact('requests'));
        } catch (Throwable $e) {
            Log::error('Association Approved Permits Error: ' . $e->getMessage());
            return abort(500, 'خطا در بارگذاری کارتابل صدور: ' . $e->getMessage());
        }
    }

    /**
     * کسر قطعی برگه خام از انبار دوزبلاغ با امکان درج دستی سریال توسط اپراتور انجمن
     */
    public function assignPermitSerial(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // 🟢 تبدیل هوشمند اعداد فارسی کیبورد اپراتور به انگلیسی
            $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $manualSerial = str_replace($persianDigits, $englishDigits, trim((string) $request->input('serial_number', '')));

            if ($manualSerial === '') {
                return response()->json(['success' => false, 'message' => 'لطفاً شماره سریال دوزبلاغ کشور را وارد کنید.'], 422);
            }

            if (!preg_match('/^[0-9]+$/', $manualSerial)) {
                return response()->json(['success' => false, 'message' => 'شماره سریال باید فقط عدد باشد.'], 422);
            }

            $permit = DB::table('permit_requests')->where('id', $id)->lockForUpdate()->first();
            if (!$permit) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'درخواست یافت نشد.'], 404);
            }

            if (!empty($permit->serial_number)) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'برای این پرونده قبلاً سریال ثبت شده است.'], 422);
            }

            // ۱. پیدا کردن کشور مقصد پروانه
            $destination = DB::table('permit_request_items')->where('permit_request_id', $id)->first();
            if (!$destination) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'مسیر سفر و کشور مقصد پرونده یافت نشد.']);
            }

            // 🟢 واکشی اتوماتیک تعداد روز اعتبار (برای سازگاری با کدهای فرانت در صورت ارسال)
            $countryInfo = DB::table('countries')->where('id', $destination->country_id)->first();
            $validityDaysInput = trim((string) $request->input('validity_days', ''));
            
            // اولویت با مقدار ارسالی از فرانت است، در غیر اینصورت از دیتابیس می‌خواند
            $validityDays = (!empty($validityDaysInput) && is_numeric($validityDaysInput)) 
                            ? (int)$validityDaysInput 
                            : ($countryInfo->validity_days ?? 30);

            if ($validityDays > 3650) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'تعداد روز اعتبار بیش از حد مجاز است.'], 422);
            }

            // تاریخ پایان اعتبار به صورت سیستمی محاسبه می‌شود
            $issuedAt = now();
            $validUntil = $issuedAt->copy()->startOfDay()->addDays($validityDays);

            // ۲. بررسی موجودی انبار خام
            $warehouseItem = DB::table('dozbalagh_items')
                ->join('dozbalagh_batches', 'dozbalagh_items.batch_id', '=', 'dozbalagh_batches.id')
                ->where('dozbalagh_batches.country_id', $destination->country_id)
                ->where('dozbalagh_items.serial_number', $manualSerial)
                ->where('dozbalagh_items.lifecycle_status', 'raw')
                ->select('dozbalagh_items.id', 'dozbalagh_items.serial_number')
                ->lockForUpdate()
                ->first();

            if (!$warehouseItem) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'این شماره سریال برای کشور مقصد این پرونده در انبار خام موجود نیست، قبلاً مصرف شده یا متعلق به کشور دیگری است.'
                ], 422);
            }

            $allocatedSerial = $warehouseItem->serial_number;

            // ۳. آپدیت وضعیت برگه در انبار کل
            DB::table('dozbalagh_items')->where('id', $warehouseItem->id)->update([
                'lifecycle_status' => 'consumed',
                'updated_at' => $issuedAt
            ]);

            // ۴. تسویه حساب نهایی مالی شرکت
            $wallet = DB::table('wallets')->where('company_id', $permit->company_id)->first();
            if ($wallet) {
                DB::table('wallets')->where('id', $wallet->id)->update([
                    'blocked_balance' => max(0, $wallet->blocked_balance - $permit->total_amount),
                    'updated_at' => $issuedAt
                ]);
            }

            // ۵. آپدیت نهایی پرونده
            DB::table('permit_requests')->where('id', $id)->update([
                'serial_number'       => $allocatedSerial,
                'issued_at'           => $issuedAt,
                'validity_days'       => $validityDays,
                'permit_valid_until'  => $validUntil->toDateString(),
                'status'              => 'issued',
                'payment_status'      => 'settled',
                'updated_at'          => $issuedAt
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => "سریال {$allocatedSerial} با موفقیت ثبت و پرونده صادر شد.",
                'serial' => $allocatedSerial,
                'valid_until' => \Morilog\Jalali\Jalalian::fromCarbon($validUntil)->format('Y/m/d'),
                'remaining_days' => $validityDays
            ]);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Assign Permit Serial Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'خطای دیتابیس در ثبت سریال دستی و صدور پروانه: ' . $e->getMessage()], 500);
        }
    }

    /**
     * نمایش جزئیات یک درخواست پروانه
     */
    public function show($id)
    {
        return view('association.driver.show', compact('id'));
    } 

    /**
     * مرحله سوم: کارتابل مدیریت تردد و پیوست لاشه
     */
    public function transitPermits()
    {
        try {
            $requests = DB::table('permit_requests')
                ->where('status', 'issued')
                ->orderBy('updated_at', 'desc')
                ->paginate(10);

            foreach ($requests as $req) {
                $req->driver = null;
                if (isset($req->driver_id)) {
                    $req->driver = DB::table('drivers')->where('id', $req->driver_id)->first() 
                                ?? DB::table('drivers')->where('national_code', $req->driver_id)->first();
                }

                $req->fleet = null;
                if (isset($req->fleet_id)) {
                    $req->fleet = DB::table('fleets')->where('id', $req->fleet_id)->first() 
                               ?? DB::table('fleets')->where('smart_card_number', $req->fleet_id)->first();
                }

                $destination = DB::table('permit_request_items')->where('permit_request_id', $req->id)->first();
                $req->country_name = 'نامشخص';
                if ($destination) {
                    $countryInfo = DB::table('countries')->where('id', $destination->country_id)->first();
                    if ($countryInfo) {
                        $req->country_name = $countryInfo->name;
                    }
                }
            }

            return view('association.driver.transit', compact('requests'));
        } catch (\Throwable $e) {
            Log::error('Transit Permits Load Error: ' . $e->getMessage());
            return abort(500, 'خطا در بارگذاری کارتابل تردد: ' . $e->getMessage());
        }
    }

    /**
     * ثبت لاشه فیزیکی پروانه و بایگانی نهایی (آزاد سازی راننده و ناوگان)
     */
    public function settleTransitPermit(Request $request, $id)
    {
        return $this->updateRequestStatus($request, $id);
    }

    /**
     * نمایش لیست پروانه‌های بایگانی شده و خاتمه یافته (مرحله نهایی چرخه)
     */
    public function archivePermits()
    {
        try {
            $requests = DB::table('permit_requests')
                ->whereIn('status', ['archived', 'collected', 'lost'])
                ->orderBy('updated_at', 'desc')
                ->paginate(10);

            foreach ($requests as $req) {
                $req->driver = null;
                if (isset($req->driver_id)) {
                    $req->driver = DB::table('drivers')->where('id', $req->driver_id)->first() 
                                ?? DB::table('drivers')->where('national_code', $req->driver_id)->first();
                }

                $req->fleet = null;
                if (isset($req->fleet_id)) {
                    $req->fleet = DB::table('fleets')->where('id', $req->fleet_id)->first() 
                               ?? DB::table('fleets')->where('smart_card_number', $req->fleet_id)->first();
                }

                $destination = DB::table('permit_request_items')->where('permit_request_id', $req->id)->first();
                $req->country_name = 'نامشخص';
                if ($destination) {
                    $countryInfo = DB::table('countries')->where('id', $destination->country_id)->first();
                    if ($countryInfo) {
                        $req->country_name = $countryInfo->name;
                    }
                }
            }

            return view('association.driver.archive', compact('requests'));
        } catch (\Throwable $e) {
            Log::error('Archive Permits Load Error: ' . $e->getMessage());
            return abort(500, 'خطا در بارگذاری بایگانی کل: ' . $e->getMessage());
        }
    }

    /**
     * نمایش ساختار قالب چاپ بر اساس آیدی کشور مقصد (کاملاً هوشمند)
     */
    public function printPermit($id)
    {
        try {
            $permit = DB::table('permit_requests')->where('id', $id)->first();
            if (!$permit) {
                return abort(404, 'پرونده یافت نشد.');
            }

            $driver = DB::table('drivers')->where('id', $permit->driver_id)->orWhere('national_code', $permit->driver_id)->first();
            $fleet = DB::table('fleets')->where('id', $permit->fleet_id)->orWhere('smart_card_number', $permit->fleet_id)->first();

            $destination = DB::table('permit_request_items')->where('permit_request_id', $id)->first();
            
            $country = $destination ? DB::table('countries')->where('id', $destination->country_id)->first() : null;

            $countryId = $country ? $country->id : 'default';

            if (view()->exists("association.driver.prints.{$countryId}")) {
                return view("association.driver.prints.{$countryId}", compact('permit', 'driver', 'fleet', 'country'));
            }

            return view('association.driver.print_document', compact('permit', 'driver', 'fleet', 'country'));
            
        } catch (\Throwable $e) {
            Log::error('Print Permit Error: ' . $e->getMessage());
            return abort(500, 'خطا در لود صفحه چاپ: ' . $e->getMessage());
        }
    }

    /**
     * عودت پرونده به شرکت جهت اصلاح مدارک
     */
    public function returnToCompany(Request $request, $id)
    {
        $request->validate([
            'reject_reason' => 'required|string|max:500'
        ]);

        try {
            $permit = DB::table('permit_requests')->where('id', $id)->first();
            if (!$permit || $permit->status !== 'pending') {
                return response()->json(['success' => false, 'message' => 'پرونده معتبر یافت نشد.']);
            }

            DB::table('permit_requests')->where('id', $id)->update([
                'status' => 'returned',
                'reject_reason' => $request->reject_reason,
                'updated_at' => now()
            ]);

            return response()->json(['success' => true, 'message' => 'پرونده جهت اصلاح مدارک به شرکت عودت داده شد.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}