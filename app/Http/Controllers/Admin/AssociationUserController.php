<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\PanelFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssociationUserController extends Controller
{
    public function index(PanelFeatureService $features): View
    {
        $users = User::whereHas('role', fn ($query) => $query->where('name', 'association'))
            ->latest('id')
            ->get();

        return view('admin.users.association', [
            'users' => $users,
            'featureDefinitions' => $features->definitions()['association'] ?? [],
            'features' => $features,
        ]);
    }

    public function store(Request $request, PanelFeatureService $features): RedirectResponse
    {
        $featureKeys = array_keys($features->definitions()['association'] ?? []);
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'features' => ['required', 'array', 'min:1'],
            'features.*' => ['string', 'distinct', Rule::in($featureKeys)],
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
            'association_feature_keys' => array_values($validated['features']),
        ]);

        return back()->with('success', 'کاربر جدید انجمن ایجاد شد.');
    }

    public function update(Request $request, User $user, PanelFeatureService $features): RedirectResponse
    {
        abort_unless($user->hasRole('association'), 404);

        $featureKeys = array_keys($features->definitions()['association'] ?? []);
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'mobile' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'features' => ['required', 'array', 'min:1'],
            'features.*' => ['string', 'distinct', Rule::in($featureKeys)],
        ]);

        $user->username = $validated['username'];
        $user->mobile = $validated['mobile'] ?? null;
        $user->status = $validated['status'];
        $user->association_feature_keys = array_values($validated['features']);
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        return back()->with('success', 'اطلاعات کاربر انجمن به‌روزرسانی شد.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->hasRole('association'), 404);

        $user->forceFill([
            'username' => sprintf('deleted-association-%d-%s', $user->id, Str::lower(Str::random(8))),
            'mobile' => null,
            'status' => 'inactive',
            'remember_token' => null,
        ])->save();
        $user->delete();

        return back()->with('success', 'کاربر انجمن با موفقیت حذف شد.');
    }
}
