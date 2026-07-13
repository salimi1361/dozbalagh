<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssociationUserController extends Controller
{
    public function index(): View
    {
        $users = User::whereHas('role', fn ($query) => $query->where('name', 'association'))
            ->latest('id')
            ->get();

        return view('admin.users.association', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'association'],
            ['title_fa' => 'کاربر انجمن', 'parent_id' => Role::where('name', 'admin')->value('id')],
        );

        User::create([
            'role_id' => $role->id,
            'username' => $validated['username'],
            'mobile' => $validated['mobile'] ?? null,
            'password' => Hash::make($validated['password']),
            'status' => 'active',
            'is_manual' => true,
        ]);

        return back()->with('success', 'کاربر جدید انجمن ایجاد شد.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->hasRole('association'), 404);

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'mobile' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->username = $validated['username'];
        $user->mobile = $validated['mobile'] ?? null;
        $user->status = $validated['status'];
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        return back()->with('success', 'اطلاعات کاربر انجمن به‌روزرسانی شد.');
    }
}
