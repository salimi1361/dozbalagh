@extends('layouts.admin')

@section('content')
<div class="p-6 relative">
    
    @if ($errors->any())
        <div class="bg-red-50 border-r-4 border-red-500 p-4 mb-6 rounded-md shadow-sm">
            <h3 class="text-sm font-bold text-red-800">خطا در ثبت اطلاعات:</h3>
            <ul class="list-disc pl-5 mt-2 text-sm text-red-700">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-r-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 text-sm font-bold shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="bg-green-100 border-r-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 text-sm font-bold shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">مدیریت شرکت‌ها</h1>
        <button type="button" onclick="openCompanyModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
            افزودن شرکت جدید
        </button>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full w-full table-auto text-right">
            <thead class="bg-gray-100 text-gray-600 border-b">
                <tr>
                    <th class="px-4 py-3 border-l">ردیف</th>
                    <th class="px-4 py-3 border-l">کد شرکت</th>
                    <th class="px-4 py-3 border-l">نام شرکت</th>
                    <th class="px-4 py-3 border-l">شناسه ملی</th>
                    <th class="px-4 py-3 border-l">موبایل مدیرعامل</th>
                    <th class="px-4 py-3 border-l text-center">وضعیت</th>
                    <th class="px-4 py-3 text-center">عملیات</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($companies as $index => $company)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 border-l">{{ $index + 1 }}</td>
                        <td class="px-4 py-3 border-l font-mono text-gray-500">{{ $company->company_code ?? '---' }}</td>
                        <td class="px-4 py-3 border-l font-bold">{{ $company->name_fa ?? $company->name }}</td>
                        <td class="px-4 py-3 border-l">{{ $company->national_id }}</td>
                        <td class="px-4 py-3 border-l">{{ $company->ceo_mobile }}</td>
                        
                        <td class="px-4 py-3 border-l text-center">
                            @if($company->status == 'approved')
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">تایید شده</span>
                            @else
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-bold">در انتظار</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.companies.edit', $company->id) }}" class="text-indigo-600 font-bold mx-1">ویرایش</a>
                            
                            @if($company->status !== 'approved')
                                <form action="{{ route('admin.companies.approve', $company->id) }}" method="POST" class="inline-block mx-1">
                                    @csrf @method('PUT')
                                    <button type="submit" class="text-green-600 font-bold">تایید</button>
                                </form>
                            @endif

                            <form action="{{ route('admin.companies.reset-password', $company->id) }}" method="POST" class="inline-block mx-1">
                                @csrf @method('PUT')
                                <button type="submit" class="text-gray-500 hover:text-black font-bold" title="بازنشانی رمز عبور">🗝️</button>
                            </form>

                            <button type="button" onclick="confirmDelete('delete-form-{{ $company->id }}', '{{ $company->name_fa ?? $company->name }}')" class="text-red-600 font-bold mx-1">حذف</button>
                            <form id="delete-form-{{ $company->id }}" action="{{ route('admin.companies.destroy', $company->id) }}" method="POST" class="hidden">
                                @csrf @method('DELETE')
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">هیچ شرکتی یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('admin.companies.create_modal')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmDelete(formId, companyName) {
        Swal.fire({
            title: 'اخطار مهم!',
            text: `آیا از حذف کامل شرکت "${companyName}" مطمئن هستید؟`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'بله، حذف کن!',
            cancelButtonText: 'انصراف'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    }
</script>
@endsection