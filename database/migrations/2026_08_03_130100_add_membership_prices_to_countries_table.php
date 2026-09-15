<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('countries', 'member_price')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->unsignedBigInteger('member_price')->default(0)->after('price');
            });
        }

        if (! Schema::hasColumn('countries', 'non_member_price')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->unsignedBigInteger('non_member_price')->default(0)->after('member_price');
            });
        }

        // مقدار فعلی برای شروع هر دو گروه کپی می‌شود؛ اعمال تعرفه بر اساس عضویت
        // عمداً در این مرحله تغییر نمی‌کند و در مرحله بعد به فرایند ثبت درخواست وصل می‌شود.
        DB::table('countries')->update([
            'member_price' => DB::raw('COALESCE(price, 0)'),
            'non_member_price' => DB::raw('COALESCE(price, 0)'),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('countries', 'non_member_price')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropColumn('non_member_price');
            });
        }

        if (Schema::hasColumn('countries', 'member_price')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropColumn('member_price');
            });
        }
    }
};
