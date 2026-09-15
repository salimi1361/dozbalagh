<section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between gap-3">
        <div><h2 class="text-xl font-black text-slate-800">کاربران فعلی انجمن</h2><p class="mt-1 text-xs text-slate-500">هر کاربر یک ردیف مستقل دارد؛ برای مدیریت، همان ردیف را باز کنید.</p></div>
        <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-black text-sky-700">{{ $users->count() }} کاربر</span>
    </div>
    <div class="mt-5 space-y-3">
        @forelse($users as $user)
            @php $selectedFeatures = $user->association_feature_keys ?? array_keys($featureDefinitions); @endphp
            <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <summary class="flex cursor-pointer list-none flex-col gap-3 bg-slate-50 px-5 py-4 hover:bg-sky-50 sm:flex-row sm:items-center sm:justify-between">
                    <div><strong class="block text-sm text-slate-800">{{ $user->username }}</strong><span class="mt-1 block text-xs text-slate-500">{{ $user->mobile ?: 'بدون موبایل' }}</span></div>
                    <div class="flex flex-wrap items-center gap-2"><span class="rounded-full px-3 py-1 text-[10px] font-black {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">{{ $user->status === 'active' ? 'فعال' : 'غیرفعال' }}</span><span class="rounded-full bg-violet-100 px-3 py-1 text-[10px] font-black text-violet-700">{{ count($selectedFeatures) }} دسترسی</span><span class="rounded-xl bg-slate-800 px-4 py-2 text-xs font-black text-white group-open:hidden">مدیریت کاربر</span><span class="hidden rounded-xl bg-sky-600 px-4 py-2 text-xs font-black text-white group-open:inline-block">بستن</span></div>
                </summary>
                <div class="space-y-5 border-t border-slate-200 p-5">
                    <section class="rounded-2xl border border-slate-200 p-4">
                        <h3 class="font-black text-slate-700">مشخصات و وضعیت</h3>
                        <form method="POST" action="{{ route('admin.association-users.profile', $user) }}" class="mt-4 grid items-end gap-3 md:grid-cols-4">@csrf @method('PUT')
                            <label class="space-y-1"><span class="text-xs font-bold text-slate-500">نام کاربری</span><input name="username" value="{{ $user->username }}" required autocomplete="off" class="w-full rounded-xl border-slate-300"></label>
                            <label class="space-y-1"><span class="text-xs font-bold text-slate-500">موبایل</span><input name="mobile" value="{{ $user->mobile }}" autocomplete="off" class="w-full rounded-xl border-slate-300"></label>
                            <label class="space-y-1"><span class="text-xs font-bold text-slate-500">وضعیت</span><select name="status" class="w-full rounded-xl border-slate-300"><option value="active" @selected($user->status === 'active')>فعال</option><option value="inactive" @selected($user->status === 'inactive')>غیرفعال</option><option value="suspended" @selected($user->status === 'suspended')>تعلیق</option></select></label>
                            <button class="rounded-xl bg-slate-800 px-5 py-3 text-sm font-black text-white">ذخیره مشخصات</button>
                        </form>
                    </section>

                    <section class="rounded-2xl border border-violet-200 bg-violet-50/40 p-4">
                        <h3 class="font-black text-violet-950">دسترسی‌های این کاربر</h3>
                        <form method="POST" action="{{ route('admin.association-users.features', $user) }}" class="mt-4">@csrf @method('PUT')
                            <div class="grid gap-3 lg:grid-cols-3">@foreach($featureGroups as $group => $items)<div class="overflow-hidden rounded-xl border border-slate-200 bg-white"><div class="flex items-center justify-between bg-slate-50 px-4 py-3"><strong class="text-sm text-slate-700">{{ $groupLabels[$group] ?? ($items->first()['group_label'] ?? $group) }}</strong><span class="text-[10px] text-slate-400">{{ $items->keys()->intersect($selectedFeatures)->count() }} فعال</span></div><div class="max-h-64 divide-y divide-slate-100 overflow-y-auto px-4">@foreach($items as $key => $definition)<label class="flex cursor-pointer items-start gap-3 py-3 text-sm font-bold text-slate-600"><input type="checkbox" name="features[]" value="{{ $key }}" class="mt-1 h-4 w-4 rounded border-slate-300 text-violet-600" @checked(in_array($key, $selectedFeatures, true))><span>{{ $definition['label'] }}</span></label>@endforeach</div></div>@endforeach</div>
                            <button class="mt-4 rounded-xl bg-violet-700 px-6 py-3 text-sm font-black text-white">فقط ذخیره دسترسی‌ها</button>
                        </form>
                    </section>

                    <details class="rounded-2xl border border-amber-200 bg-amber-50">
                        <summary class="cursor-pointer list-none px-4 py-3 text-sm font-black text-amber-800">تغییر رمز عبور</summary>
                        <form method="POST" action="{{ route('admin.association-users.password', $user) }}" class="grid gap-3 border-t border-amber-200 p-4 md:grid-cols-3">@csrf @method('PUT')
                            <input type="password" name="password" required minlength="8" autocomplete="new-password" class="rounded-xl border-amber-200" placeholder="رمز جدید">
                            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="rounded-xl border-amber-200" placeholder="تکرار رمز جدید">
                            <button class="rounded-xl bg-amber-600 px-5 py-3 text-sm font-black text-white">تغییر رمز</button>
                        </form>
                    </details>

                    <form method="POST" action="{{ route('admin.association-users.destroy', $user) }}" onsubmit="return confirm('این کاربر حذف شود؟')">@csrf @method('DELETE')<button class="rounded-xl border border-rose-200 px-4 py-2 text-xs font-black text-rose-600">حذف کاربر</button></form>
                </div>
            </details>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm font-bold text-slate-500">هنوز کاربر انجمن ساخته نشده است.</div>
        @endforelse
    </div>
</section>
