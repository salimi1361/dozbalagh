<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyDriverMessage;
use App\Models\Driver;
use App\Notifications\CompanyDriverMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DriverMessageController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company->id ?? auth()->user()->company_id;

        $drivers = Driver::query()
            ->where('current_company_id', $companyId)
            ->orderBy('last_name_fa')
            ->orderBy('first_name_fa')
            ->get(['id', 'national_code', 'first_name_fa', 'last_name_fa', 'mobile']);

        $threads = CompanyDriverMessage::query()
            ->selectRaw('driver_id, max(created_at) as last_message_at')
            ->where('company_id', $companyId)
            ->groupBy('driver_id')
            ->pluck('last_message_at', 'driver_id');

        $unreadByDriver = CompanyDriverMessage::query()
            ->selectRaw('driver_id, count(*) as total')
            ->where('company_id', $companyId)
            ->where('sender', 'driver')
            ->whereNull('read_at')
            ->groupBy('driver_id')
            ->pluck('total', 'driver_id');

        return view('company.driver_messages.index', compact('drivers', 'threads', 'unreadByDriver'));
    }

    public function store(Request $request)
    {
        $company = auth()->user()->company;
        $companyId = $company->id ?? auth()->user()->company_id;

        $validator = Validator::make($request->all(), [
            'scope' => ['required', Rule::in(['selected', 'all'])],
            'driver_ids' => ['required_if:scope,selected', 'array'],
            'driver_ids.*' => ['integer'],
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1000'],
            'category' => ['required', Rule::in(['general', 'warning', 'trip_change', 'action_required', 'document', 'settlement'])],
            'priority' => ['required', Rule::in(['normal', 'important', 'urgent'])],
            'requires_acknowledgement' => ['nullable', 'boolean'],
        ], [
            'driver_ids.required_if' => 'حداقل یک راننده را انتخاب کنید.',
            'title.required' => 'عنوان پیام را وارد کنید.',
            'message.required' => 'متن پیام را وارد کنید.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $driversQuery = Driver::query()->where('current_company_id', $companyId);

        if ($request->scope === 'selected') {
            $driversQuery->whereIn('id', $request->input('driver_ids', []));
        }

        $drivers = $driversQuery->get();

        if ($drivers->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'راننده‌ای برای ارسال پیام یافت نشد.'], 404);
        }

        $companyName = $company->name_fa ?? $company->name ?? auth()->user()->name ?? 'شرکت حمل و نقل';

        foreach ($drivers as $driver) {
            $message = CompanyDriverMessage::create([
                'company_id' => $companyId,
                'driver_id' => $driver->id,
                'sender' => 'company',
                'title' => $request->title,
                'message' => $request->message,
                'category' => $request->category,
                'priority' => $request->priority,
                'requires_acknowledgement' => $request->boolean('requires_acknowledgement'),
            ]);

            $driver->notify(new CompanyDriverMessageNotification(
                companyId: $companyId,
                companyName: $companyName,
                title: $request->title,
                message: $request->message,
                category: $request->category,
                priority: $request->priority,
                requiresAcknowledgement: $request->boolean('requires_acknowledgement'),
                messageId: $message->id
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'پیام برای ' . $drivers->count() . ' راننده ارسال شد.',
        ]);
    }

    public function thread(int $driverId)
    {
        $companyId = auth()->user()->company->id ?? auth()->user()->company_id;

        $driver = Driver::query()
            ->where('current_company_id', $companyId)
            ->findOrFail($driverId);

        CompanyDriverMessage::query()
            ->where('company_id', $companyId)
            ->where('driver_id', $driver->id)
            ->where('sender', 'driver')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = CompanyDriverMessage::query()
            ->where('company_id', $companyId)
            ->where('driver_id', $driver->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($message) => [
                'id' => $message->id,
                'sender' => $message->sender,
                'title' => $message->title,
                'message' => $message->message,
                'category' => $message->category,
                'priority' => $message->priority,
                'created_at' => optional($message->created_at)->format('Y/m/d H:i'),
            ]);

        return response()->json([
            'success' => true,
            'driver' => [
                'id' => $driver->id,
                'name' => trim(($driver->first_name_fa ?? '') . ' ' . ($driver->last_name_fa ?? '')),
                'mobile' => $driver->mobile,
                'national_code' => $driver->national_code,
            ],
            'messages' => $messages,
        ]);
    }
}
