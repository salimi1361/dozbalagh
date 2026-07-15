<?php

namespace App\Http\Controllers\Association;

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
        return view('association.account.security');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'رمز عبور فعلی صحیح نیست.',
            'password.confirmed' => 'تکرار رمز عبور جدید مطابقت ندارد.',
            'password.min' => 'رمز عبور جدید باید حداقل ۸ کاراکتر باشد.',
        ]);

        $request->user()->update(['password' => Hash::make($validated['password'])]);
        $request->session()->regenerate();

        return back()->with('success', 'رمز عبور پنل انجمن با موفقیت تغییر کرد.');
    }
}
