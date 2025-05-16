<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ConfigController extends Controller
{
    protected $configPath;

    public function __construct()
    {
        $this->configPath = storage_path('app/');

        // ایجاد دایرکتوری‌های مورد نیاز
        $directories = [
            storage_path('app/'),
            storage_path('logs/scrapers'),
        ];

        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
        }
    }

    /**
     * Display a listing of the configs.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $files = Storage::files();
        $configs = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $filename = pathinfo($file, PATHINFO_FILENAME);
                $content = json_decode(Storage::get($file), true);

                // اطلاعات محصولات از دیتابیس
                $total_products = 0;
                $total_pages = 0;
                $available_products = 0;
                try {
                    $this->setupDynamicConnection($filename);
                    $total_products = \App\Models\Product::count();
                    $products_per_page = 100; // مشابه ProductController
                    $total_pages = ceil($total_products / $products_per_page);
                    $available_products = \App\Models\Product::where('availability', 1)->count();
                } catch (\Exception $e) {
                    \Log::error("Failed to fetch product data for config {$filename}: {$e->getMessage()}");
                    // در صورت خطا، مقادیر صفر باقی می‌مانند
                }

                // اطلاعات آخرین اجرا
                $last_run_at = null;
                $run_file_path = "private/runs/{$filename}.json";
                if (Storage::exists($run_file_path)) {
                    $run_info = json_decode(Storage::get($run_file_path), true);
                    $last_run_at = $run_info['started_at'] ?? null;
                }

                $configs[] = [
                    'filename' => $filename,
                    'content' => array_merge($content, [
                        'total_products' => $total_products,
                        'total_pages' => $total_pages,
                        'available_products' => $available_products,
                    ]),
                    'last_run_at' => $last_run_at,
                ];
            }
        }

        return view('configs.index', compact('configs'));
    }

    private function setupDynamicConnection(string $store): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $store)) {
            \Log::error("Invalid database name: {$store}");
            throw new \Exception("نام دیتابیس نامعتبر است: {$store}");
        }

        \Config::set('database.connections.dynamic', [
            'driver' => 'mysql',
            'host' => config('database.connections.mysql.host'),
            'port' => config('database.connections.mysql.port'),
            'database' => $store,
            'username' => config('database.connections.mysql.username'),
            'password' => config('database.connections.mysql.password'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        \DB::purge('dynamic');

        try {
            \DB::connection('dynamic')->getPdo();
            \Log::info("Successfully connected to database: {$store}");
        } catch (\Exception $e) {
            \Log::error("Failed to connect to database {$store}: {$e->getMessage()}");
            throw new \Exception("دیتابیس '{$store}' یافت نشد یا خطایی رخ داد: {$e->getMessage()}");
        }
    }

    public function deleteAllLogs()
    {
        $logDirectory = storage_path('logs');
        $logPatterns = ['scraper*', 'playwright_method3_*', 'playwright_*'];

        $deletedCount = 0;
        $errorCount = 0;

        if (file_exists($logDirectory)) {
            foreach ($logPatterns as $pattern) {
                $files = glob($logDirectory . '/' . $pattern . '.log');
                foreach ($files as $file) {
                    if (is_file($file) && unlink($file)) {
                        $deletedCount++;
                    } else {
                        $errorCount++;
                    }
                }
            }
        }

        if ($deletedCount > 0 && $errorCount == 0) {
            return redirect()->route('configs.index')->with('success', "تعداد $deletedCount فایل لاگ با موفقیت حذف شدند.");
        } elseif ($deletedCount > 0 && $errorCount > 0) {
            return redirect()->route('configs.index')->with('warning', "تعداد $deletedCount فایل لاگ حذف شدند، اما $errorCount فایل حذف نشدند.");
        } elseif ($errorCount > 0) {
            return redirect()->route('configs.index')->with('error', 'خطا در حذف فایل‌های لاگ.');
        } else {
            return redirect()->route('configs.index')->with('info', 'هیچ فایل لاگ مرتبطی یافت نشد.');
        }
    }


    /**
     * Delete a log file.
     *
     * @param string $logfile
     * @return \Illuminate\Http\Response
     */
    public function deleteLog($logfile)
    {
        $logPath = storage_path('logs/scrapers/' . $logfile);

        if (!file_exists($logPath)) {
            return redirect()->back()->with('error', 'فایل لاگ یافت نشد.');
        }

        // حذف فایل
        if (unlink($logPath)) {
            return redirect()->back()->with('success', 'فایل لاگ با موفقیت حذف شد.');
        } else {
            return redirect()->back()->with('error', 'خطا در حذف فایل لاگ.');
        }
    }

    /**
     * Show the form for creating a new config.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('configs.create');
    }

    /**
     * Store a newly created config in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = $this->getValidator($request);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $method = (int)$request->input('method', 1);
        $config = $this->buildConfig($request, $method);

        $siteName = $request->input('site_name');
        $filename = $siteName . '.json';

        Storage::put('private/' . $filename, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return redirect()->route('configs.index')->with('success', 'کانفیگ با موفقیت ذخیره شد!');
    }

    /**
     * Show the form for editing the specified config.
     *
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function edit($filename)
    {
        $filePath = "{$filename}.json";
        if (!Storage::exists($filePath)) {
            return redirect()->route('configs.index')->with('error', 'فایل کانفیگ یافت نشد.');
        }

        $content = json_decode(Storage::get($filePath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return redirect()->route('configs.index')->with('error', 'خطا در خواندن فایل کانفیگ.');
        }

        return view('configs.edit', compact('content', 'filename'));
    }

    /**
     * Update the specified config in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $filename)
    {
        $validator = $this->getValidator($request);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $method = (int)$request->input('method', 1);
        $config = $this->buildConfig($request, $method);

        $content = json_decode(Storage::get('private/' . $filename . '.json'), true);

        return redirect()->route('configs.index')->with('success', 'کانفیگ با موفقیت به‌روزرسانی شد!');
    }

    /**
     * Remove the specified config from storage.
     *
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function destroy($filename)
    {
        return redirect()->route('configs.index')->with('success', 'کانفیگ با موفقیت حذف شد!');
    }

    /**
     * Get validator for request data.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function getValidator(Request $request)
    {
        $rules = [
            'site_name' => 'required|string|max:255',
            'method' => 'required|in:1,2,3',
            'base_urls' => 'required|array|min:1',
            'base_urls.*' => 'required|url',
            'products_urls' => 'required|array|min:1',
            'products_urls.*' => 'required|url',
            'keep_price_format' => 'boolean',
            'product_id_method' => 'required|in:selector,url',
            'product_id_source' => 'required|in:product_page,url,main_page',
            'guarantee_method' => 'required|in:selector,title',
            'guarantee_keywords' => 'required|array|min:1',
            'guarantee_keywords.*' => 'required|string',
            'availability_keywords.positive' => 'required|array|min:1',
            'availability_keywords.positive.*' => 'required|string',
            'availability_keywords.negative' => 'required|array|min:1',
            'availability_keywords.negative.*' => 'required|string',
            'price_keywords.unpriced' => 'required|array|min:1',
            'price_keywords.unpriced.*' => 'required|string',
            'selectors.main_page.product_links.type' => 'required|string',
            'selectors.main_page.product_links.selector' => 'required|string',
            'selectors.main_page.product_links.attribute' => 'required|string',
        ];

        // Add method-specific validation
        $method = (int)$request->input('method', 1);

        if ($method == 1) {
            $rules['pagination.type'] = 'required|in:query,path';
            $rules['pagination.parameter'] = 'required|string';
            $rules['pagination.separator'] = 'required|string';
            $rules['pagination.max_pages'] = 'required|integer|min:1';
            $rules['pagination.use_sample_url'] = 'boolean';
            $rules['pagination.sample_url'] = 'required_if:pagination.use_sample_url,1|url';
        } else {
            // Method 2 & 3 specific validations
            if ($method == 2) {
                $rules['share_product_id_from_method_2'] = 'boolean';
                $rules['container'] = 'required|string'; // کانتینر فقط برای متد 2 اجباری است
                $rules['scrool'] = 'integer|min:1';
            } else if ($method == 3) {
                // متد 3 - کانتینر اختیاری است
                $rules['container'] = 'nullable|string';
                $rules['scrool'] = 'integer|min:1';
            }

            if (in_array($method, [2, 3])) {
                $rules['pagination_method'] = 'required|in:next_button,url';

                if ($request->input('pagination_method') == 'next_button') {
                    $rules['pagination_next_button_selector'] = 'required|string';
                } else {
                    $rules['pagination_url_type'] = 'required|in:query,path';
                    $rules['pagination_url_parameter'] = 'required|string';
                    $rules['pagination_url_separator'] = 'required|string';
                    $rules['pagination_max_pages'] = 'required|integer|min:1';
                    $rules['pagination_use_sample_url'] = 'boolean';
                    $rules['pagination_sample_url'] = 'required_if:pagination_use_sample_url,1|url';
                }
            }
        }

        // Add validation for product_id in main_page if selected
        if ($request->input('product_id_source') == 'main_page') {
            $rules['selectors.main_page.product_id.type'] = 'required|string';
            $rules['selectors.main_page.product_id.selector'] = 'required|string';
            $rules['selectors.main_page.product_id.attribute'] = 'required|string';
        }

        return Validator::make($request->all(), $rules);
    }

    /**
     * Build config from request data.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $method
     * @return array
     */
    private function buildConfig(Request $request, $method)
    {
        // Base config (common for all methods)
        $config = [
            'method' => $method,
            'processing_method' => $method == 3 ? 3 : 1,
            'base_urls' => $request->input('base_urls'),
            'products_urls' => $request->input('products_urls'),
            'request_delay_min' => (int)$request->input('request_delay_min', 1000),
            'request_delay_max' => (int)$request->input('request_delay_max', 1000),
            'timeout' => (int)$request->input('timeout', 60),
            'max_retries' => (int)$request->input('max_retries', 2),
            'concurrency' => (int)$request->input('concurrency', 10),
            'batch_size' => (int)$request->input('batch_size', 10),
            'user_agent' => $request->input('user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124'),
            'verify_ssl' => filter_var($request->input('verify_ssl', false), FILTER_VALIDATE_BOOLEAN),
            'keep_price_format' => filter_var($request->input('keep_price_format', false), FILTER_VALIDATE_BOOLEAN),
            'image_method' => $request->input('image_method', 'product_page'),
            'product_id_method' => $request->input('product_id_method'),
            'product_id_source' => $request->input('product_id_source'),
            'product_id_fallback_script_patterns' => [
                'product_id:\\s*\"(\\d+)\"',
                'product_id:\\s*(\\d+)'
            ],
            'category_method' => $request->input('category_method', 'selector'),
            'category_word_count' => (int)$request->input('category_word_count', 1),
            'guarantee_method' => $request->input('guarantee_method'),
            'guarantee_keywords' => $request->input('guarantee_keywords'),
            'availability_keywords' => [
                'positive' => $request->input('availability_keywords.positive'),
                'negative' => $request->input('availability_keywords.negative'),
            ],
            'price_keywords' => [
                'unpriced' => $request->input('price_keywords.unpriced'),
            ],
            'selectors' => [
                'main_page' => [
                    'product_links' => [
                        'type' => $request->input('selectors.main_page.product_links.type'),
                        'selector' => $request->input('selectors.main_page.product_links.selector'),
                        'attribute' => $request->input('selectors.main_page.product_links.attribute'),
                    ],
                ],
                'product_page' => [
                    'title' => [
                        'type' => $request->input('selectors.product_page.title.type'),
                        'selector' => $request->input('selectors.product_page.title.selector'),
                    ],
                    'category' => [
                        'type' => $request->input('selectors.product_page.category.type'),
                        'selector' => $request->input('selectors.product_page.category.selector'),
                    ],
                    'availability' => [
                        'type' => $request->input('selectors.product_page.availability.type'),
                        'selector' => $request->input('selectors.product_page.availability.selector'),
                    ],
                    'price' => [
                        'type' => $request->input('selectors.product_page.price.type'),
                        'selector' => $request->input('selectors.product_page.price.selector'),
                    ],
                    'image' => [
                        'type' => $request->input('selectors.product_page.image.type'),
                        'selector' => $request->input('selectors.product_page.image.selector'),
                        'attribute' => $request->input('selectors.product_page.image.attribute'),
                    ],
                    'off' => [
                        'type' => $request->input('selectors.product_page.off.type'),
                        'selector' => $request->input('selectors.product_page.off.selector'),
                    ],
                    'guarantee' => [
                        'type' => $request->input('selectors.product_page.guarantee.type'),
                        'selector' => $request->input('selectors.product_page.guarantee.selector'),
                    ],
                    'product_id' => [
                        'type' => $request->input('selectors.product_page.product_id.type'),
                        'selector' => $request->input('selectors.product_page.product_id.selector'),
                        'attribute' => $request->input('selectors.product_page.product_id.attribute'),
                    ],
                ],
            ],
            'data_transformers' => [
                'price' => 'cleanPrice',
                'availability' => 'parseAvailability',
                'off' => 'cleanOff',
                'guarantee' => 'cleanGuarantee',
            ],
        ];

        // Add product_id to main_page if selected
        if ($request->input('product_id_source') == 'main_page') {
            $config['selectors']['main_page']['product_id'] = [
                'type' => $request->input('selectors.main_page.product_id.type'),
                'selector' => $request->input('selectors.main_page.product_id.selector'),
                'attribute' => $request->input('selectors.main_page.product_id.attribute'),
            ];
        }

        // Method-specific settings
        if ($method == 1) {
            // Method 1 settings
            $config['method_settings'] = [
                'method_1' => [
                    'enabled' => true,
                    'pagination' => [
                        'type' => $request->input('pagination.type'),
                        'parameter' => $request->input('pagination.parameter'),
                        'separator' => $request->input('pagination.separator'),
                        'suffix' => $request->input('pagination.suffix', ''),
                        'max_pages' => (int)$request->input('pagination.max_pages'),
                        'use_sample_url' => filter_var($request->input('pagination.use_sample_url', false), FILTER_VALIDATE_BOOLEAN),
                        'sample_url' => $request->input('pagination.use_sample_url') ? $request->input('pagination.sample_url', '') : '',
                        'use_webdriver' => false,
                        'use_dynamic_pagination' => false,
                        'force_trailing_slash' => true,
                        'ignore_redirects' => true,
                    ],
                ],
            ];
        } else {
            // Method 2 & 3 common settings
            if ($method == 2) {
                // Method 2 specific settings
                $config['share_product_id_from_method_2'] = filter_var($request->input('share_product_id_from_method_2', false), FILTER_VALIDATE_BOOLEAN);
                $config['container'] = $request->input('container', '');
                $config['scrool'] = (int)$request->input('scrool', 10);

                // Build pagination for method 2
                $pagination = $this->buildPaginationConfig($request);

                $config['method_settings'] = [
                    'method_2' => [
                        'enabled' => true,
                        'navigation' => [
                            'use_webdriver' => true,
                            'pagination' => $pagination,
                            'max_pages' => (int)$request->input('pagination_max_pages', 3),
                            'scroll_delay' => (int)$request->input('scroll_delay', 5000)
                        ],
                    ],
                ];
            } elseif ($method == 3) {
                // Method 3 specific settings
                $container = $request->input('container');
                if (!empty($container)) {
                    $config['container'] = $container;
                }

                $config['scrool'] = (int)$request->input('scrool', 10);

                // Build pagination for method 3
                $pagination = $this->buildPaginationConfig($request);

                $config['method_settings'] = [
                    'method_3' => [
                        'enabled' => true,
                        'navigation' => [
                            'use_webdriver' => true,
                            'pagination' => $pagination,
                            'max_iterations' => (int)$request->input('pagination_max_pages', 13),
                            'timing' => [
                                'scroll_delay' => (int)$request->input('scroll_delay', 5000)
                            ]
                        ],
                    ],
                ];
            }
        }

        return $config;
    }

    private function buildPaginationConfig(Request $request)
    {
        $pagination = [
            'method' => $request->input('pagination_method', 'next_button'),
        ];

        if ($request->input('pagination_method') == 'next_button') {
            // Next button pagination
            $pagination['next_button'] = [
                'selector' => $request->input('pagination_next_button_selector', '')
            ];
        } else {
            // URL pagination
            $pagination['url'] = [
                'type' => $request->input('pagination_url_type', 'query'),
                'parameter' => $request->input('pagination_url_parameter', 'page'),
                'separator' => $request->input('pagination_url_separator', '='),
                'suffix' => $request->input('pagination_url_suffix', ''),
                'max_pages' => (int)$request->input('pagination_max_pages', 3),
                'use_sample_url' => filter_var($request->input('pagination_use_sample_url', false), FILTER_VALIDATE_BOOLEAN),
                'sample_url' => $request->input('pagination_use_sample_url') ? $request->input('pagination_sample_url', '') : '',
                'use_webdriver' => true
            ];
        }

        return $pagination;
    }

    private function buildNavigationConfig(Request $request)
    {
        $navigation = [
            'pagination' => [
                'method' => $request->input('pagination_method', 'next_button'),
            ],
        ];

        if ($request->input('pagination_method') == 'next_button') {
            // Next button pagination
            $navigation['pagination']['next_button'] = [
                'selector' => $request->input('pagination_next_button_selector', '')
            ];
        } else {
            // URL pagination
            $navigation['pagination']['url'] = [
                'type' => $request->input('pagination_url_type', 'query'),
                'parameter' => $request->input('pagination_url_parameter', 'page'),
                'separator' => $request->input('pagination_url_separator', '='),
                'suffix' => $request->input('pagination_url_suffix', ''),
                'max_pages' => (int)$request->input('pagination_max_pages', 3),
                'use_sample_url' => filter_var($request->input('pagination_use_sample_url', false), FILTER_VALIDATE_BOOLEAN),
                'sample_url' => $request->input('pagination_use_sample_url') ? $request->input('pagination_sample_url', '') : '',
                'use_webdriver' => true
            ];
        }

        return $navigation;
    }


    /**
     * Run the scraper for the specified config.
     *
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function runScraper($filename)
    {
        // مسیر کامل فایل کانفیگ
        $configPath = storage_path('app/private/' . $filename . '.json');

        // بررسی وجود فایل
        if (!file_exists($configPath)) {
            return redirect()->route('configs.index')->with('error', 'فایل کانفیگ یافت نشد!');
        }

        // بررسی آیا این کانفیگ در حال اجرا است یا خیر
        $runFileName = $filename . '.json';
        $runFilePath = 'private/runs/' . $runFileName;

        if (Storage::exists($runFilePath)) {
            $existingRun = json_decode(Storage::get($runFilePath), true);

            // بررسی اینکه آیا اسکرپر در حال اجراست
            if (isset($existingRun['status']) && $existingRun['status'] === 'running' && isset($existingRun['pid'])) {
                // چک کردن اینکه پروسه هنوز در حال اجراست
                if ($this->isProcessRunning($existingRun['pid'])) {
                    return redirect()->route('configs.index')->with('error', 'اسکرپر برای کانفیگ ' . $filename . ' در حال حاضر در حال اجراست! لطفاً ابتدا آن را متوقف کنید.');
                }

                // چک کردن پروسه‌های مرتبط دیگر
                $command = "ps aux | grep 'scrape:start.*{$filename}' | grep -v grep";
                exec($command, $output);

                if (!empty($output)) {
                    return redirect()->route('configs.index')->with('error', 'اسکرپر برای کانفیگ ' . $filename . ' در حال حاضر در حال اجراست! لطفاً ابتدا آن را متوقف کنید.');
                }

                // اگر به اینجا رسیدیم، پروسه کرش کرده است
                $existingRun['status'] = 'crashed';
                $existingRun['stopped_at'] = date('Y-m-d H:i:s');
                Storage::put($runFilePath, json_encode($existingRun, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        // ایجاد مسیر برای ذخیره لاگ‌ها
        $logDirectory = storage_path('logs/scrapers');
        if (!file_exists($logDirectory)) {
            mkdir($logDirectory, 0755, true);
        }

        // نام فایل لاگ بر اساس نام کانفیگ و تاریخ
        $logFileName = $filename . '_' . date('Y-m-d_H-i-s') . '.log';
        $logFile = $logDirectory . '/' . $logFileName;

        // ایجاد یک فایل لاگ خالی با هدر UTF-8
        file_put_contents($logFile, "اجرای اسکرپر برای کانفیگ {$filename} در تاریخ " . date('Y-m-d H:i:s') . " شروع شد...\n");

        // اجرای دستور به صورت غیر همزمان و ذخیره PID
        $cmd = sprintf(
            'nohup php %s scrape:start --config=%s >> %s 2>&1 & echo $!',
            base_path('artisan'),
            $configPath,
            $logFile
        );

        $pid = exec($cmd);

        // اگر PID خالی باشد یا صفر باشد، اجرا با خطا مواجه شده است
        if (empty($pid) || $pid == 0) {
            // اضافه کردن پیام خطا به فایل لاگ
            file_put_contents($logFile, "\n[" . date('Y-m-d H:i:s') . "] خطا در اجرای اسکرپر: PID نامعتبر\n", FILE_APPEND);
            return redirect()->route('configs.index')->with('error', 'خطا در اجرای اسکرپر. لطفاً لاگ‌ها را بررسی کنید.');
        }

        // ایجاد دایرکتوری runs اگر وجود ندارد
        if (!Storage::exists('private/runs')) {
            Storage::makeDirectory('private/runs', 0755);
        }

        // بررسی آیا قبلاً فایل run برای این کانفیگ ایجاد شده است
        $runInfo = [];

        if (Storage::exists($runFilePath)) {
            // اگر فایل قبلاً وجود دارد، آن را بخوانیم و به‌روزرسانی کنیم
            $runInfo = json_decode(Storage::get($runFilePath), true);

            // اضافه کردن تاریخچه اجرای قبلی
            if (!isset($runInfo['history'])) {
                $runInfo['history'] = [];
            }

            // اگر اطلاعات اجرای قبلی وجود دارد، آن را به تاریخچه اضافه کنیم
            if (isset($runInfo['started_at']) && isset($runInfo['log_file'])) {
                $previousRun = [
                    'started_at' => $runInfo['started_at'],
                    'log_file' => $runInfo['log_file']
                ];

                if (isset($runInfo['stopped_at'])) {
                    $previousRun['stopped_at'] = $runInfo['stopped_at'];
                }

                if (isset($runInfo['status'])) {
                    $previousRun['status'] = $runInfo['status'];
                }

                // اضافه کردن به تاریخچه
                array_unshift($runInfo['history'], $previousRun);

                // محدود کردن تعداد تاریخچه به 10 آیتم آخر
                if (count($runInfo['history']) > 10) {
                    $runInfo['history'] = array_slice($runInfo['history'], 0, 10);
                }
            }
        }

        // به‌روزرسانی اطلاعات اجرای فعلی
        $runInfo['filename'] = $filename;
        $runInfo['log_file'] = $logFileName;
        $runInfo['started_at'] = date('Y-m-d H:i:s');
        $runInfo['pid'] = (int)$pid;
        $runInfo['status'] = 'running';

        // اگر وضعیت قبلی توقف بود، آن را حذف کنیم
        if (isset($runInfo['stopped_at'])) {
            unset($runInfo['stopped_at']);
        }

        // ذخیره اطلاعات اجرا در فایل
        Storage::put($runFilePath, json_encode($runInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return redirect()->route('configs.index')->with('success', 'اسکرپر برای کانفیگ ' . $filename . ' با موفقیت اجرا شد. می‌توانید لاگ‌ها را مشاهده کنید.');
    }


    /**
     * Show logs for the specified config.
     *
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function showLogs($filename)
    {
        // مسیر دایرکتوری لاگ‌ها
        $logDirectory = storage_path('logs/scrapers');

        // لیست فایل‌های لاگ مربوط به این کانفیگ
        $logFiles = [];

        if (file_exists($logDirectory)) {
            $allFiles = scandir($logDirectory);

            foreach ($allFiles as $file) {
                if (strpos($file, $filename . '_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                    // استخراج تاریخ از نام فایل
                    $datePart = str_replace($filename . '_', '', pathinfo($file, PATHINFO_FILENAME));

                    $logFiles[] = [
                        'filename' => $file,
                        'date' => $datePart,
                        'full_path' => $logDirectory . '/' . $file,
                        'size' => filesize($logDirectory . '/' . $file),
                        'last_modified' => filemtime($logDirectory . '/' . $file)
                    ];
                }
            }

            // مرتب‌سازی بر اساس تاریخ تغییر (جدیدترین در ابتدا)
            usort($logFiles, function ($a, $b) {
                return $b['last_modified'] - $a['last_modified'];
            });
        }

        return view('configs.logs', compact('logFiles', 'filename'));
    }

    /**
     * Get the content of a log file.
     *
     * @param string $logfile
     * @return \Illuminate\Http\Response
     */
    public function getLogContent($logfile)
    {
        $logPath = storage_path('logs/scrapers/' . $logfile);

        if (!file_exists($logPath)) {
            return response('فایل لاگ یافت نشد.', 404);
        }

        // خواندن محتوای فایل با پشتیبانی از UTF-8
        $content = file_get_contents($logPath);

        // تشخیص اینکه آیا فایل با BOM شروع می‌شود یا خیر
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            // حذف BOM
            $content = substr($content, 3);
        }

        return response($content)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }


    /**
     * Stop the running scraper for the specified config.
     *
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function stopScraper($filename)
    {
        // بررسی وجود فایل اجرا
        $runFilePath = 'private/runs/' . $filename . '.json';

        if (!Storage::exists($runFilePath)) {
            return redirect()->route('configs.index')->with('error', 'هیچ اسکرپر در حال اجرایی برای این کانفیگ یافت نشد.');
        }

        $runInfo = json_decode(Storage::get($runFilePath), true);

        if (!isset($runInfo['pid']) || !isset($runInfo['status']) || $runInfo['status'] !== 'running') {
            return redirect()->route('configs.index')->with('error', 'اسکرپر در حال حاضر در حال اجرا نیست.');
        }

        $pid = $runInfo['pid'];
        $stopped = false;

        // بررسی وجود پروسه
        if ($this->isProcessRunning($pid)) {
            // توقف پروسه
            exec("kill -9 {$pid} 2>&1", $output, $result);

            if ($result === 0) {
                $stopped = true;
            }
        }

        // همچنین به دنبال پروسه‌های php artisan با نام فایل کانفیگ بگردیم
        $command = "ps aux | grep 'scrape:start.*{$filename}' | grep -v grep | awk '{print $2}'";
        exec($command, $output);

        if (!empty($output)) {
            foreach ($output as $extraPid) {
                exec("kill -9 {$extraPid} 2>&1");
                $stopped = true;
            }
        }

        if ($stopped) {
            // به‌روزرسانی وضعیت در فایل
            $runInfo['status'] = 'stopped';
            $runInfo['stopped_at'] = date('Y-m-d H:i:s');

            Storage::put($runFilePath, json_encode($runInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // اضافه کردن پیام به فایل لاگ
            if (isset($runInfo['log_file'])) {
                $logPath = storage_path('logs/scrapers/' . $runInfo['log_file']);
                if (file_exists($logPath)) {
                    file_put_contents($logPath, "\n[" . date('Y-m-d H:i:s') . "] اسکرپر به صورت دستی متوقف شد.\n", FILE_APPEND);
                }
            }

            return redirect()->route('configs.index')->with('success', 'اسکرپر با موفقیت متوقف شد.');
        } else {
            return redirect()->route('configs.index')->with('error', 'پروسه اسکرپر یافت نشد، اما وضعیت آن به متوقف شده تغییر کرد.');
        }
    }

    /**
     * Check if a process is running by PID.
     *
     * @param int $pid
     * @return bool
     */
    private function isProcessRunning($pid)
    {
        if (empty($pid)) {
            return false;
        }

        exec("ps -p {$pid}", $output, $result);
        return $result === 0;
    }

}
