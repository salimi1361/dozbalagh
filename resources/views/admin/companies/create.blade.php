@extends('layouts.admin')
<button type="submit" onclick="alert('دکمه کار می‌کند!');">تست دکمه</button>
@section('content')
<div class="p-6 max-w-2xl mx-auto bg-white rounded-lg shadow">
    <h2 class="text-xl font-bold mb-4">افزودن شرکت جدید</h2>
    
    <form action="{{ route('admin.companies.store') }}" method="POST">
    @csrf  <input type="text" name="name" placeholder="نام شرکت" required>
    <input type="text" name="national_id" placeholder="شناسه ملی" required>
    
    <button type="submit">ذخیره شرکت</button>
</form>
</div>
@endsection