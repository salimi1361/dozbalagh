<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountSecurityController extends Controller
{
    public function edit(): View
    {
        return view('admin.account.security');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'رمز عبور فعلی را وارد کنید.',
            'current_password.current_password' => 'رمز عبور فعلی صحیح نیست.',
            'password.required' => 'رمز عبور جدید را وارد کنید.',
            'password.confirmed' => 'تکرار رمز عبور جدید مطابقت ندارد.',
            'password.min' => 'رمز عبور جدید باید حداقل ۸ کاراکتر باشد.',
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => null,
        ])->save();

        $request->session()->regenerate();

        return redirect()
            ->route('admin.account.security.edit')
            ->with('success', 'رمز عبور پنل مدیریت با موفقیت تغییر کرد.');
    }
}
