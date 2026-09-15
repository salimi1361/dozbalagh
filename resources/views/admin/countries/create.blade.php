@extends('layouts.admin')

@section('header_title', 'افزودن کشور مقصد')

@section('content')
@php($permitTypeLabels = config('permit_types.labels', []))
<div class="p-6 max-w-2xl mx-auto bg-white rounded-lg shadow">
    <h2 class="text-xl font-bold mb-4">افزودن کشور جدید</h2>
    
    <form action="{{ route('admin.countries.store') }}" method="POST">
        @csrf
        
        <div class="mb-4">
            <label class="block text-sm font-medium">نام کشور</label>
            <input type="text" name="name" class="w-full border rounded p-2" required>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium">قیمت عضو (ریال)</label>
                <input type="number" name="member_price" min="0" class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium">قیمت غیرعضو (ریال)</label>
                <input type="number" name="non_member_price" min="0" class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium">مدت اعتبار (روز)</label>
                <input type="number" name="validity_days" min="0" value="30" class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium">کد کشور</label>
                <input type="text" name="code" maxlength="10" class="w-full border rounded p-2">
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">نوع مجوزهای مجاز:</label>
            <div class="grid grid-cols-2 gap-3 border p-4 rounded bg-gray-50">
                @foreach($permitTypeLabels as $key => $label)
                    <label class="flex items-center">
                        <input type="checkbox" name="permit_types[]" value="{{ $key }}" class="mr-2">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">ذخیره کشور</button>
    </form>
</div>
@endsection
