<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class SystemMapController extends Controller
{
    public function __invoke()
    {
        $map = [
            'company'     => [],
            'association' => [],
            'admin'       => [],
            'shared'      => []
        ];

        $routeDescriptions = [
            'dashboard' => '🎛️ داشبورد اصلی شرکت حمل‌ونقل',
            'dozbalagh.index' => '📑 لیست تمام درخواست‌های دوزبلاغ شرکت',
            'dozbalagh.create' => '🚛 فرم ویزاردی ثبت درخواست دوزبلاغ جدید یا تمدیدی',
            'dozbalagh.renewable_list' => '🔄 وب‌سرویس استعلام پروانه‌های باز و قابل تمدید ناوگان',
            'dozbalagh.store' => '💾 ذخیره‌سازی درخواست و بلوکه‌کردن هزینه از کیف پول',
            'dozbalagh.check_fleet' => '🔍 استعلام زنده و آنی وضعیت تداخل ناوگان و راننده',
            'web.company.driver.index' => '👤 کارتابل مدیریت رانندگان ملکی شرکت',
            'web.company.fleet.index' => '🚛 کارتابل مدیریت کامیون‌ها و ناوگان شرکت',
            'company.wallet.index' => '💰 مشاهده موجودی و سوابق مالی کیف پول شرکت',
            'association.pending.index' => '⏳ کارتابل بررسی و تایید اولیه درخواست‌های معلق شرکت‌ها',
            'association.approved.index' => '✍️ کارتابل تخصیص سریال فیزیکی دوزبلاغ و صدور قطعی',
            'association.transit.index' => '🚚 مدیریت ناوگان در حال تردد و کارتابل تحویل لاشه فیزیکی',
            'association.archive.index' => '🗂️ بایگانی کل پروانه‌های خاتمه‌یافته، مفقودی یا ابطال‌شده',
            'association.permit.print' => '🖨️ صفحه رسمی چاپ برگه فیزیکی دوزبلاغ بر اساس فرمت کشور مقصد',
            'admin.dashboard' => '📊 داشبورد مرکزی مدیریت کل سامانه و آمارهای حیاتی',
            'admin.countries.index' => '🌍 مدیریت کشورها، مرزها و قیمت پایه مجوزها',
            'admin.inventory.index' => '📦 مدیریت انبار کل و پارت‌های سهمیه دوزبلاغ',
            'admin.allocations.index' => '🎯 مدیریت و تخصیص سقف سهمیه دوره‌ای شرکت‌ها',
            'admin.financial.dashboard' => '💳 کارتابل جامع حسابداری سیستم، ترازها و اسناد اصلاحی دستی',
        ];

        $controllerCounts = [];
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if ($action !== 'Closure' && str_contains($action, '@')) {
                list($controllerClass, $method) = explode('@', $action);
                $controllerName = class_basename($controllerClass);
                $controllerCounts[$controllerName] = ($controllerCounts[$controllerName] ?? 0) + 1;
            }
        }

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (str_contains($uri, '_ignition') || str_contains($uri, 'sanctum')) {
                continue;
            }

            $action = $route->getActionName();
            $filePath = 'توابع درون‌خطی Route';
            $bladePath = 'بدون قالب (عملیات پردازشی/AJAX)';
            $isShared = false;

            if ($action !== 'Closure' && str_contains($action, '@')) {
                list($controllerClass, $method) = explode('@', $action);
                $controllerName = class_basename($controllerClass);
                $isShared = ($controllerCounts[$controllerName] ?? 0) > 1;
                
                try {
                    if (class_exists($cleanClass = ltrim($controllerClass, '\\'))) {
                        $reflector = new \ReflectionClass($cleanClass);
                        $filePath = str_replace(base_path() . '/', '', $reflector->getFileName());

                        // 🔍 جادوی جدید: اسکن داخل متد برای پیدا کردن دستور لود قالب بلید فرانت
                        if ($reflector->hasMethod($method)) {
                            $methodReflector = $reflector->getMethod($method);
                            $startLine = $methodReflector->getStartLine();
                            $endLine = $methodReflector->getEndLine();
                            $length = $endLine - $startLine;
                            
                            $source = file($reflector->getFileName());
                            $methodCode = implode('', array_slice($source, $startLine - 1, $length));

                            // پیدا کردن الگوهایی مثل view('association.driver.index')
                            if (preg_match('/view\(\s*[\'"]([^\'"]+)[\'"]/', $methodCode, $matches)) {
                                $viewName = $matches[1];
                                $bladePath = 'resources/views/' . str_replace('.', '/', $viewName) . '.blade.php';
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $filePath = 'یافت نشد';
                }
            }

            $routeName = $route->getName() ?? '---';
            $methods = is_array($route->methods()) ? implode('|', $route->methods()) : 'GET';
            $description = $routeDescriptions[$routeName] ?? 'صفحه عملیاتی و فرعی پورتال سیستم';

            $methodType = 'نمایش صفحه';
            if (str_contains($methods, 'POST')) $methodType = '📥 ثبت اطلاعات';
            elseif (str_contains($methods, 'PUT')) $methodType = '📝 ویرایش/آپدیت';
            elseif (str_contains($methods, 'DELETE')) $methodType = '❌ حذف ردیف';

            $item = [
                'uri'         => '/' . ltrim($uri, '/'),
                'name'        => $routeName,
                'action'      => str_replace('App\Http\Controllers\\', '', $action),
                'file_path'   => $filePath,
                'blade_path'  => $bladePath, // 📌 فیلد جدید آدرس بلید
                'method'      => str_replace(['GET|HEAD', 'GET'], 'GET', $methods),
                'method_type' => $methodType,
                'description' => $description,
                'is_shared'   => $isShared
            ];

            if (str_contains($uri, 'admin')) {
                $map['admin'][] = $item;
            } elseif (str_contains($uri, 'association')) {
                $map['association'][] = $item;
            } else {
                $map['company'][] = $item;
            }

            if ($isShared) {
                $map['shared'][] = $item;
            }
        }

        return view('admin.system_map', compact('map'));
    }
}