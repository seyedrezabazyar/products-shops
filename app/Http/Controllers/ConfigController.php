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
        $this->configPath = storage_path('app/private');

        // ایجاد دایرکتوری‌های مورد نیاز
        $directories = [
            storage_path('app/private'),
            storage_path('app/private/private'),
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
        $files = Storage::files('private');
        $configs = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $configs[] = [
                    'filename' => pathinfo($file, PATHINFO_FILENAME),
                    'content' => json_decode(Storage::get($file), true)
                ];
            }
        }

        return view('configs.index', compact('configs'));
    }

    /**
     * Delete a log file.
     *
     * @param  string  $logfile
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
     * @param  \Illuminate\Http\Request  $request
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
     * @param  string  $filename
     * @return \Illuminate\Http\Response
     */
    public function edit($filename)
    {
        $content = json_decode(Storage::get('private/' . $filename . '.json'), true);
        return view('configs.edit', compact('content', 'filename'));
    }

    /**
     * Update the specified config in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $filename
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

        Storage::put('private/' . $filename . '.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return redirect()->route('configs.index')->with('success', 'کانفیگ با موفقیت به‌روزرسانی شد!');
    }

    /**
     * Remove the specified config from storage.
     *
     * @param  string  $filename
     * @return \Illuminate\Http\Response
     */
    public function destroy($filename)
    {
        Storage::delete('private/' . $filename . '.json');
        return redirect()->route('configs.index')->with('success', 'کانفیگ با موفقیت حذف شد!');
    }

    /**
     * Get validator for request data.
     *
     * @param  \Illuminate\Http\Request  $request
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $method
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
     * @param  string  $filename
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

        // ایجاد دایرکتوری runs اگر وجود ندارد
        if (!Storage::exists('private/runs')) {
            Storage::makeDirectory('private/runs', 0755);
        }

        // ذخیره اطلاعات اجرا در فایل
        $runInfo = [
            'filename' => $filename,
            'log_file' => $logFileName,
            'started_at' => date('Y-m-d H:i:s'),
            'pid' => (int)$pid,
            'status' => 'running'
        ];

        // نام فایل اطلاعات اجرا
        $runFileName = $filename . '_' . date('Y-m-d_H-i-s') . '.json';
        Storage::put('private/runs/' . $runFileName, json_encode($runInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return redirect()->route('configs.index')->with('success', 'اسکرپر برای کانفیگ ' . $filename . ' با موفقیت اجرا شد. می‌توانید لاگ‌ها را مشاهده کنید.');
    }

    /**
     * Show logs for the specified config.
     *
     * @param  string  $filename
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
            usort($logFiles, function($a, $b) {
                return $b['last_modified'] - $a['last_modified'];
            });
        }

        return view('configs.logs', compact('logFiles', 'filename'));
    }

    /**
     * Get the content of a log file.
     *
     * @param  string  $logfile
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
     * @param  string  $filename
     * @return \Illuminate\Http\Response
     */
    public function stopScraper($filename)
    {
        // بررسی وجود فایل‌های اجرای در حال اجرا
        $runsDirectory = storage_path('app/private/runs');
        $runningProcesses = [];

        if (file_exists($runsDirectory)) {
            $files = scandir($runsDirectory);

            foreach ($files as $file) {
                if (strpos($file, $filename . '_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $runInfo = json_decode(file_get_contents($runsDirectory . '/' . $file), true);

                    if (isset($runInfo['pid']) && isset($runInfo['status']) && $runInfo['status'] === 'running') {
                        $runningProcesses[] = $runInfo;
                    }
                }
            }
        }

        $stopped = false;

        // توقف پروسه‌های در حال اجرا
        foreach ($runningProcesses as $process) {
            $pid = $process['pid'];

            // بررسی وجود پروسه
            if ($this->isProcessRunning($pid)) {
                // توقف پروسه
                exec("kill -9 {$pid} 2>&1", $output, $result);

                if ($result === 0) {
                    $stopped = true;

                    // بروزرسانی وضعیت در فایل
                    $runInfo = $process;
                    $runInfo['status'] = 'stopped';
                    $runInfo['stopped_at'] = date('Y-m-d H:i:s');

                    $runFilePath = $runsDirectory . '/' . basename($process['log_file'], '.log') . '.json';
                    file_put_contents($runFilePath, json_encode($runInfo, JSON_PRETTY_PRINT));

                    // اضافه کردن پیام به فایل لاگ
                    $logPath = storage_path('logs/scrapers/' . $process['log_file']);
                    if (file_exists($logPath)) {
                        file_put_contents($logPath, "\n[" . date('Y-m-d H:i:s') . "] اسکرپر به صورت دستی متوقف شد.\n", FILE_APPEND);
                    }
                }
            }
        }

        // همچنین به دنبال پروسه‌های php artisan با نام فایل کانفیگ بگردیم
        $command = "ps aux | grep 'scrape:start.*{$filename}' | grep -v grep | awk '{print $2}'";
        exec($command, $output);

        if (!empty($output)) {
            foreach ($output as $pid) {
                exec("kill -9 {$pid} 2>&1");
                $stopped = true;
            }
        }

        if ($stopped) {
            return redirect()->route('configs.index')->with('success', 'اسکرپر با موفقیت متوقف شد.');
        } else {
            return redirect()->route('configs.index')->with('error', 'هیچ اسکرپر در حال اجرایی برای این کانفیگ یافت نشد.');
        }
    }

    /**
     * Check if a process is running by PID.
     *
     * @param  int  $pid
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
