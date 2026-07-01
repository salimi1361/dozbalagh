@extends('layouts.app')

@section('header_title', 'گزارشات لحظه‌ای و تحلیل وضعیت')

@section('content')
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
        @foreach(['صادر شده'=>'issued', 'استفاده شده'=>'used', 'لاشه'=>'returned', 'منقضی'=>'expired', 'تمدیدی'=>'renewed', 'مفقودی'=>'lost'] as $label => $key)
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm text-center">
                <span class="text-[10px] font-bold text-slate-400 block">{{ $label }}</span>
                <span class="text-xl font-black text-slate-800">{{ $counts[$key] }}</span>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-right text-xs">
            <thead class="bg-slate-100 text-slate-700">
                <tr>
                    <th class="p-4">کد ملی راننده</th>
                    <th class="p-4">شماره کارت هوشمند</th>
                    <th class="p-4">وضعیت</th>
                    <th class="p-4">تاریخ ثبت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($permits as $p)
                <tr>
                    <td class="p-4 font-mono">{{ $p->driver_id }}</td>
                    <td class="p-4 font-mono">{{ $p->fleet_id }}</td>
                    <td class="p-4"><span class="px-2 py-1 bg-slate-100 rounded-full">{{ $p->status }}</span></td>
                    <td class="p-4 text-slate-500">{{ $p->created_at }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection