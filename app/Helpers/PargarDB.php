<?php

namespace App\Helpers;

class PargarDB
{
    protected static $connection = null;

    /**
     * برقراری اتصال به دیتابیس پرگار در صورت عدم وجود اتصال فعال
     */
    protected static function connect()
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        // خواندن مستقیم مشخصات از فایل کانفیگ یا .env
        $host = config('database.connections.oracle.host', '192.168.100.100');
        $port = config('database.connections.oracle.port', '1521');
        $database = config('database.connections.oracle.database', 'PARGAR');
        $username = config('database.connections.oracle.username', 'Pargar');
        $password = config('database.connections.oracle.password', 'bs!#8797hg%!sj435DHv%#7234a');

        self::$connection = @oci_connect($username, $password, "//$host:$port/$database", 'AL32UTF8');

        if (!self::$connection) {
            $e = oci_error();
            throw new \Exception("خطا در اتصال به دیتابیس پرگار: " . $e['message']);
        }

        return self::$connection;
    }

    /**
     * اجرای کوئری‌های انتخابی و بازگرداندن خروجی به صورت آرایه تمیز (مانند متد select لاراول)
     */
    public static function select($query, $bindings = [])
    {
        $conn = self::connect();
        $statement = oci_parse($conn, $query);

        if (!$statement) {
            $e = oci_error($conn);
            throw new \Exception("خطا در آماده‌سازی کوئری: " . $e['message']);
        }

        // بایند کردن متغیرها در صورت وجود (برای جلوگیری از SQL Injection)
        foreach ($bindings as $key => $value) {
            // اوراکل بایندینگ‌ها را با کلماتی مثل :id یا :name می‌شناسد
            $placeholder = is_numeric($key) ? ":b$key" : (str_starts_with($key, ':') ? $key : ":$key");
            oci_bind_by_name($statement, $placeholder, $bindings[$key]);
        }

        $result = oci_execute($statement);

        if (!$result) {
            $e = oci_error($statement);
            throw new \Exception("خطا در اجرای کوئری اوراکل: " . $e['message']);
        }

        $data = [];
        // دریافت اطلاعات به صورت آرایه متناظر با کلیدهای حروف کوچک برای هماهنگی با لاراول
        while ($row = oci_fetch_array($statement, OCI_ASSOC + OCI_RETURN_NULLS)) {
            $data[] = array_change_key_case($row, CASE_LOWER);
        }

        oci_free_statement($statement);
        return $data;
    }
}