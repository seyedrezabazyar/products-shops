<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    public function index()
    {
        try {
            // دریافت لیست دیتابیس‌هایی که در نامشان _ دارند
            $databases = DB::select("SHOW DATABASES LIKE '%_%'");

            // لیست دیتابیس‌هایی که جدول products دارند
            $validApis = [];
            $productsPerPage = 100; // تعداد محصولات در هر صفحه

            foreach ($databases as $db) {
                $dbName = current((array)$db);

                // تنظیم اتصال داینامیک به دیتابیس
                Config::set('database.connections.dynamic', [
                    'driver' => 'mysql',
                    'host' => config('database.connections.mysql.host'),
                    'port' => config('database.connections.mysql.port'),
                    'database' => $dbName,
                    'username' => config('database.connections.mysql.username'),
                    'password' => config('database.connections.mysql.password'),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                ]);

                // پاک‌سازی اتصال قبلی
                DB::purge('dynamic');

                // بررسی وجود جدول products و محاسبه اطلاعات
                try {
                    $tables = DB::connection('dynamic')->select("SHOW TABLES LIKE 'products'");
                    if (!empty($tables)) {
                        // تعداد کل محصولات
                        $totalProducts = DB::connection('dynamic')
                            ->table('products')
                            ->count();

                        // تعداد صفحات
                        $totalPages = ceil($totalProducts / $productsPerPage);

                        // تعداد محصولات موجود (availability = 1)
                        $availableProducts = DB::connection('dynamic')
                            ->table('products')
                            ->where('availability', 1)
                            ->count();

                        // آخرین به‌روزرسانی (آخرین updated_at)
                        $lastUpdated = DB::connection('dynamic')
                            ->table('products')
                            ->max('updated_at');

                        $validApis[] = [
                            'name' => $dbName,
                            'url' => url("/api/" . $dbName),
                            'total_products' => $totalProducts,
                            'total_pages' => $totalPages,
                            'available_products' => $availableProducts,
                            'last_updated' => $lastUpdated,
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::error("Failed to check tables in database {$dbName}: {$e->getMessage()}");
                    continue;
                }
            }

            // مرتب‌سازی بر اساس آخرین به‌روزرسانی (جدیدترین در بالا)
            usort($validApis, function ($a, $b) {
                $aDate = $a['last_updated'] ? strtotime($a['last_updated']) : 0;
                $bDate = $b['last_updated'] ? strtotime($b['last_updated']) : 0;
                return $bDate - $aDate; // نزولی (جدیدتر در بالا)
            });

            // رندر ویو با ارسال لیست API‌ها
            return view('api.index', [
                'apis' => $validApis,
                'total' => count($validApis)
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to fetch databases: {$e->getMessage()}");
            return response()->json([
                'error' => 'Unable to fetch API list'
            ], 500);
        }
    }
}
