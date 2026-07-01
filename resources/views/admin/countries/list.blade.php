@extends('layouts.admin')

@section('content')
<div class="p-6">
    <div class="flex justify-between mb-4">
        <h2 class="text-xl font-bold">مدیریت شرکت‌ها</h2>
        <a href="{{ route('admin.companies.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">
            + افزودن شرکت جدید
        </a>
    </div>

    <table class="w-full bg-white border">
        <thead>
            <tr class="bg-gray-100">
                <th class="p-2 border">نام شرکت</th>
                <th class="p-2 border">شناسه ملی</th>
                <th class="p-2 border">موبایل مدیرعامل</th>
                <th class="p-2 border">عملیات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($companies as $company)
            <tr>
                <td class="p-2 border">{{ $company->name }}</td>
                <td class="p-2 border">{{ $company->national_id }}</td>
                <td class="p-2 border">{{ $company->ceo_mobile }}</td>
                <td class="p-2 border">
                    <a href="{{ route('admin.companies.edit', $company->id) }}" class="text-blue-500">ویرایش</a>
                    <form action="{{ route('admin.companies.destroy', $company->id) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-500 ml-2" onclick="return confirm('حذف شود؟')">حذف</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="p-4 text-center">هیچ شرکتی در سیستم ثبت نشده است.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection