@extends('layouts.admin')

@section('content')
<div class="p-6 max-w-2xl mx-auto bg-white rounded-lg shadow">
    <h2 class="text-xl font-bold mb-4">افزودن کشور جدید</h2>
    
    <form action="{{ route('admin.countries.store') }}" method="POST">
        @csrf
        
        <div class="mb-4">
            <label class="block text-sm font-medium">نام کشور</label>
            <input type="text" name="name" class="w-full border rounded p-2" required>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">نوع مجوزهای مجاز:</label>
            <div class="grid grid-cols-2 gap-3 border p-4 rounded bg-gray-50">
                @foreach(['دوجانبه_ترانزیت' => 'دوجانبه-ترانزیت', 'ترانزیت' => 'ترانزیت', 'ثالث' => 'ثالث'] as $key => $label)
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