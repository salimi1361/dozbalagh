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
            // ✅ اصلاحیه: متد index باید دقیقاً درخواست‌های منتظر بررسی (pending) را نشان دهد
            $requests = DB::table('permit_requests')
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
            
            foreach ($requests as $req) {
                // 👤 واکشی مشخصات راننده بر اساس ساختار واقعی دیتابیس شما
                $req->driver = null;
                if (isset($req->driver_id)) {
                    $req->driver = DB::table('drivers')->where('id', $req->driver_id)->first() 
                                ?? DB::table('drivers')->where('national_code', $req->driver_id)->first();
                }

                // 🚛 واکشی مشخصات ناوگان و پلاک ترانزیت
                $req->fleet = null;
                if (isset($req->fleet_id)) {
                    $req->fleet = DB::table('fleets')->where('id', $req->fleet_id)->first() 
                               ?? DB::table('fleets')->where('smart_id', $req->fleet_id)->first()
                               ?? DB::table('fleets')->where('smart_card_number', $req->fleet_id)->first();
                }
                
                // 🌍 واکشی لیست کشورهای مقصد از جدول واسط
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
        // ۱. اعتبارسنجی منعطف ورودی‌ها جهت پذیرش تصویر لاشه دوزبِلاغ
        $request->validate([
            'status' => 'required|string',
            'reject_reason' => 'nullable|string',
            'image' => 'nullable|image|max:5120'
        ]);

        DB::beginTransaction();
        try {
            // دریافت اطلاعات اصلی درخواست جهت دسترسی به مبلغ و آیدی شرکت
            $permit = DB::table('permit_requests')->where('id', $id)->first();
            if (!$permit) {
                return response()->json(['success' => false, 'message' => 'درخواست یافت نشد.'], 404);
            }

            // زمان جاری و شناسه ادمین لاگین شده برای ثبت سوابق زمان‌بندی فرآیندها
            $now = Carbon::now();
            $userId = auth()->id() ?? null;
            $status = $request->input('status');

            // آرایه پیش‌فرض بروزرسانی فیلدهای عمومی درخواست
            $updateData = [
                'status' => $status,
                'reject_reason' => $request->input('reject_reason') ?? null,
                'action_by_user_id' => $userId,
                'updated_at' => $now
            ];

            // الف) عملیات مالی در صورت رد کامل درخواست (Rejected)
            if ($status === 'rejected') {
                // ثبت دقیق فیلد زمان رد پرونده در دیتابیس
                $updateData['rejected_at'] = $now;

                $wallet = DB::table('wallets')->where('company_id', $permit->company_id)->first();
                if ($wallet) {
                    // عودت کل مبلغ بلوکه‌شده به موجودی در دسترس شرکت
                    DB::table('wallets')->where('id', $wallet->id)->update([
                        'blocked_balance' => $wallet->blocked_balance - $permit->total_amount,
                        'balance' => $wallet->balance + $permit->total_amount,
                        'updated_at' => $now
                    ]);

                    // ثبت تراکنش مالی برگشت وجه
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
            // ب) ثبت زمان تایید اولیه بدون مخدوش کردن سایر فرآیندها
            elseif ($status === 'approved') {
                $updateData['approved_at'] = $now;
            }
            // ج) ثبت زمان برگشت جهت اصلاح مدارک به شرکت
            elseif ($status === 'returned') {
                $updateData['returned_at'] = $now;
            }
            // د) عملیات آزادسازی راننده/ناوگان در صورت تحویل لاشه (Collected) یا مفقودی (Lost)
            elseif ($status === 'collected' || $status === 'lost' || $status === 'archived') {
                
                // 📸 فرآیند اصلی آپلود تصویر فشرده‌شده برگه لاشه
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    
                    // نام‌گذاری امن و لوکال: [serial_number]-[d_code].[extension]
                    $serialClean = $permit->serial_number ?? 'serial';
                    $fileName = $serialClean . '-' . $permit->d_code . '.' . $file->getClientOriginalExtension();
                    
                    // ذخیره‌سازی محلی در دیسک پابلیک پروژه لاراول
                    $file->storeAs('permits/collected', $fileName, 'public');
                    
                    // الصاق مسیر در آرایه بروزرسانی دیتابیس
                    $updateData['collected_image'] = 'permits/collected/' . $fileName;
                }

                // ثبت تاریخ خاتمه پرونده در ساختار دیتابیس
                $updateData['closed_at'] = $now;

                // 🔓 آزاد کردن وضعیت مسدودی راننده و ناوگان جهت امکان درخواست مجدد شرکت‌ها
                if (isset($permit->driver_id)) {
                    DB::table('drivers')->where('id', $permit->driver_id)->orWhere('national_code', $permit->driver_id)->update(['is_blocked' => false]);
                }
                if (isset($permit->fleet_id)) {
                    DB::table('fleets')->where('id', $permit->fleet_id)->orWhere('smart_card_number', $permit->fleet_id)->update(['is_blocked' => false]);
                }

                // 🎯 بروزرسانی وضعیت برگه در انبار دوزبلاغ (همراه با Try-Catch جهت جلوگیری از وقوع خطای ساختاری کدهای کالا و پارت‌ها)
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

            // ۲. به روزرسانی نهایی دیتابیس با سوابق اتمیک تاریخ و ساعت جدید
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
            // ۱. واکشی درخواست‌های تایید اولیه شده بدون سریال
            $requests = DB::table('permit_requests')
                ->where('status', 'approved')
                ->whereNull('serial_number')
                ->orderBy('id', 'desc')
                ->paginate(10);

            foreach ($requests as $req) {
                // 👤 واکشی راننده
                $req->driver = null;
                if (isset($req->driver_id)) {
                    $req->driver = DB::table('drivers')->where('id', $req->driver_id)->first() 
                                ?? DB::table('drivers')->where('national_code', $req->driver_id)->first();
                }

                // 🚛 واکشی ناوگان
                $req->fleet = null;
                if (isset($req->fleet_id)) {
                    $req->fleet = DB::table('fleets')->where('id', $req->fleet_id)->first() 
                               ?? DB::table('fleets')->where('smart_card_number', $req->fleet_id)->first();
                }
                
                // 🌍 واکشی کشور مقصد از جدول واسط
                $destination = DB::table('permit_request_items')->where('permit_request_id', $req->id)->first();
                
                $req->country_name = 'نامشخص';
                $req->next_serial_in_warehouse = 'بدون موجودی';

                if ($destination) {
                    $countryInfo = DB::table('countries')->where('id', $destination->country_id)->first();
                    if ($countryInfo) {
                        $req->country_name = $countryInfo->name;
                    }

                    // 🎫 استخراج دقیق اولین برگه خام (raw) از انبار پارت‌های دوزبلاغ بر اساس ساختار مدل شما
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
     * کسر قطعی و اتوماتیک برگه خام از انبار دوزبلاغ به همراه تسویه حساب مالی شرکت
     */
    public function assignPermitSerial(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $permit = DB::table('permit_requests')->where('id', $id)->first();
            if (!$permit) {
                return response()->json(['success' => false, 'message' => 'درخواست یافت نشد.'], 404);
            }

            // ۱. پیدا کردن کشور مقصد پروانه
            $destination = DB::table('permit_request_items')->where('permit_request_id', $id)->first();
            if (!$destination) {
                return response()->json(['success' => false, 'message' => 'مسیر سفر و کشور مقصد پرونده یافت نشد.']);
            }

            // ۲. 🎫 رزرو و کسر اولین شماره سریال خام واقعی از انبار اختصاصی آن کشور
            $warehouseItem = DB::table('dozbalagh_items')
                ->join('dozbalagh_batches', 'dozbalagh_items.batch_id', '=', 'dozbalagh_batches.id')
                ->where('dozbalagh_batches.country_id', $destination->country_id)
                ->where('dozbalagh_items.lifecycle_status', 'raw')
                ->orderBy('dozbalagh_items.serial_number', 'asc')
                ->select('dozbalagh_items.id', 'dozbalagh_items.serial_number')
                ->first();

            if (!$warehouseItem) {
                return response()->json(['success' => false, 'message' => 'موجودی برگه خام دوزبِلاغ برای این کشور در انبار کل به اتمام رسیده است!']);
            }

            $allocatedSerial = $warehouseItem->serial_number;
            $now = now();

            // ۳. آپدیت وضعیت وضعیت برگه در انبار کل به مصرف شده (consumed)
            DB::table('dozbalagh_items')->where('id', $warehouseItem->id)->update([
                'lifecycle_status' => 'consumed',
                'updated_at' => $now
            ]);

            // ۴. تسویه حساب نهایی مالی شرکت: کسر از مبلغ بلوکه شده
            $wallet = DB::table('wallets')->where('company_id', $permit->company_id)->first();
            if ($wallet) {
                DB::table('wallets')->where('id', $wallet->id)->update([
                    'blocked_balance' => $wallet->blocked_balance - $permit->total_amount,
                    'updated_at' => $now
                ]);
            }

            // ۵. آپدیت نهایی پرونده دوزبلاغ و فرستادن راننده به وضعیت تردد (issued)
            DB::table('permit_requests')->where('id', $id)->update([
                'serial_number' => $allocatedSerial,
                'status'        => 'issued', // در حال تردد تا زمان پس دادن لاشه
                'payment_status'=> 'settled',
                'updated_at'    => $now
            ]);

            DB::commit();
            return response()->json([
                'success' => true, 
                'message' => "سریال {$allocatedSerial} با موفقیت اختصاص یافت.",
                'serial' => $allocatedSerial
            ]);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Assign Permit Serial Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'خطای دیتابیس در کسر از انبار آیتم‌ها: ' . $e->getMessage()], 500);
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
            // واکشی پرونده‌هایی که صادر شده و در حال تردد هستند
            $requests = DB::table('permit_requests')
                ->where('status', 'issued')
                ->orderBy('updated_at', 'desc')
                ->paginate(10);

            foreach ($requests as $req) {
                // واکشی مشخصات راننده
                $req->driver = null;
                if (isset($req->driver_id)) {
                    $req->driver = DB::table('drivers')->where('id', $req->driver_id)->first() 
                                ?? DB::table('drivers')->where('national_code', $req->driver_id)->first();
                }

                // واکشی مشخصات ناوگان
                $req->fleet = null;
                if (isset($req->fleet_id)) {
                    $req->fleet = DB::table('fleets')->where('id', $req->fleet_id)->first() 
                               ?? DB::table('fleets')->where('smart_card_number', $req->fleet_id)->first();
                }

                // واکشی کشور مقصد برای نمایش در جدول
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
        // جهت هماهنگی، مستقیم ساختار جامع را صدا می‌زنیم تا دیتابیس مسدود نشود و تصویر لاشه هم در صورت وجود ثبت گردد
        return $this->updateRequestStatus($request, $id);
    }

    /**
     * نمایش لیست پروانه‌های بایگانی شده و خاتمه یافته (مرحله نهایی چرخه)
     */
    public function archivePermits()
    {
        try {
            // ✅ اصلاحیه طلایی: واکشی رکوردهایی که در هر یک از این وضعیت‌های نهایی هستند تا لیست هرگز خالی نماند
            $requests = DB::table('permit_requests')
                ->whereIn('status', ['archived', 'collected', 'lost'])
                ->orderBy('updated_at', 'desc')
                ->paginate(10);

            foreach ($requests as $req) {
                // 👤 واکشی مشخصات راننده
                $req->driver = null;
                if (isset($req->driver_id)) {
                    $req->driver = DB::table('drivers')->where('id', $req->driver_id)->first() 
                                ?? DB::table('drivers')->where('national_code', $req->driver_id)->first();
                }

                // 🚛 واکشی مشخصات ناوگان
                $req->fleet = null;
                if (isset($req->fleet_id)) {
                    $req->fleet = DB::table('fleets')->where('id', $req->fleet_id)->first() 
                               ?? DB::table('fleets')->where('smart_card_number', $req->fleet_id)->first();
                }

                // 🌍 واکشی کشور مقصد
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

            // واکشی مشخصات راننده و ناوگان
            $driver = DB::table('drivers')->where('id', $permit->driver_id)->orWhere('national_code', $permit->driver_id)->first();
            $fleet = DB::table('fleets')->where('id', $permit->fleet_id)->orWhere('smart_card_number', $permit->fleet_id)->first();

            // پیدا کردن کشور مقصد از روی آیتم‌های پرونده
            $destination = DB::table('permit_request_items')->where('permit_request_id', $id)->first();
            
            // پیدا کردن مشخصات کامل کشور
            $country = $destination ? DB::table('countries')->where('id', $destination->country_id)->first() : null;

            // استفاده از ID عددی کشور برای نام‌گذاری فایل (طبق ساختار دیتابیس شما)
            $countryId = $country ? $country->id : 'default';

            // چک کردن اینکه آیا فایل چاپ اختصاصی با این آیدی وجود دارد یا خیر؟
            if (view()->exists("association.driver.prints.{$countryId}")) {
                return view("association.driver.prints.{$countryId}", compact('permit', 'driver', 'fleet', 'country'));
            }

            // اگر فایل اختصاصی وجود نداشت، قالب عمومی را باز کن
            return view('association.driver.print_document', compact('permit', 'driver', 'fleet', 'country'));
            
        } catch (\Throwable $e) {
            Log::error('Print Permit Error: ' . $e->getMessage());
            return abort(500, 'خطا در لود صفحه چاپ: ' . $e->getMessage());
        }
    }
}