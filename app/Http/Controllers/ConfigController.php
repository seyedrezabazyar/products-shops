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
        $this->configPath = storage_path('app/private/');

        // ایجاد دایرکتوری‌های مورد نیاز
        $directories = [
            storage_path('app/private/'),
            storage_path('app/private/runs/'),
            storage_path('logs/scrapers/'),
        ];

        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
        }
    }


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

    public function deleteAllLogs()
    {
        $logDirectory = storage_path('logs/');
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

    public function create()
    {
        return view('configs.create');
    }

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


    public function edit($filename)
    {
        $filePath = "private/{$filename}.json";
        if (!Storage::exists($filePath)) {
            return redirect()->route('configs.index')->with('error', 'فایل کانفیگ یافت نشد.');
        }

        $content = json_decode(Storage::get($filePath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return redirect()->route('configs.index')->with('error', 'خطا در خواندن فایل کانفیگ.');
        }

        // Check if set_category exists in the config to determine if checkbox should be checked
        if (isset($content['set_category'])) {
            $content['use_set_category'] = true;
        }

        // اطمینان از وجود availability_mode در محتوا
        if (!isset($content['availability_mode'])) {
            $content['availability_mode'] = 'smart'; // مقدار پیش‌فرض
        }

        return view('configs.edit', compact('content', 'filename'));
    }

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


        // به‌روزرسانی فایل کانفیگ
        Storage::put('private/' . $filename . '.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return redirect()->route('configs.index')->with('success', 'کانفیگ با موفقیت به‌روزرسانی شد!');
    }

    public function destroy($filename)
    {
        $filePath = "private/{$filename}.json";
        if (!Storage::exists($filePath)) {
            return redirect()->route('configs.index')->with('error', 'فایل کانفیگ یافت نشد.');
        }

        Storage::delete($filePath);
        return redirect()->route('configs.index')->with('success', 'کانفیگ با موفقیت حذف شد!');
    }

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
            'availability_mode' => 'required|in:smart,keyword', // اضافه کردن availability_mode
            'availability_keywords.positive' => 'required|array|min:1',
            'availability_keywords.positive.*' => 'required|string',
            'availability_keywords.negative' => 'required|array|min:1',
            'availability_keywords.negative.*' => 'required|string',
            'price_keywords.unpriced' => 'required|array|min:1',
            'price_keywords.unpriced.*' => 'required|string',
            'selectors.main_page.product_links.type' => 'required|string',
            'selectors.main_page.product_links.selector' => 'required|string',
            'selectors.main_page.product_links.attribute' => 'required|string',
            // اضافه کردن سلکتورهای جدید
            'selectors.product_page.add_to_cart_button.type' => 'required|string',
            'selectors.product_page.add_to_cart_button.selector' => 'required|string',
            'selectors.product_page.out_of_stock.type' => 'required|string',
            'selectors.product_page.out_of_stock.selector' => 'required|string',
        ];

        // Add method-specific validation
        $method = (int)$request->input('method', 1);

        if ($method == 1) {
            $rules['pagination.type'] = 'required|in:query,path';
            $rules['pagination.parameter'] = 'required|string';
            $rules['pagination.separator'] = 'required|string';
            $rules['pagination.max_pages'] = 'required|integer|min:1';
            $rules['pagination.use_sample_url'] = 'nullable|boolean';
            $rules['pagination.sample_url'] = 'nullable|required_if:pagination.use_sample_url,1|url';
        } else {
            // Method 2 & 3 specific validations
            if ($method == 2) {
                $rules['share_product_id_from_method_2'] = 'boolean';
                $rules['container'] = 'required|string';
                $rules['scrool'] = 'integer|min:1';
            } else if ($method == 3) {
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
                    $rules['pagination_use_sample_url'] = 'nullable|boolean';
                    $rules['pagination_sample_url'] = 'nullable|required_if:pagination_use_sample_url,1|url';
                }
            }
        }
        $rules['run_method'] = 'required|in:new,continue';
        $rules['database'] = 'required|in:clear,continue';
        $rules['use_set_category'] = 'boolean';
        $rules['set_category'] = 'nullable|required_if:use_set_category,1|string';
        $rules['title_prefix_rules.url.*'] = 'nullable|url';
        $rules['title_prefix_rules.prefix.*'] = 'nullable|string|max:255';

        if ($request->input('product_id_source') == 'main_page') {
            $rules['selectors.main_page.product_id.type'] = 'required|string';
            $rules['selectors.main_page.product_id.selector'] = 'required|string';
            $rules['selectors.main_page.product_id.attribute'] = 'required|string';
        }

        return Validator::make($request->all(), $rules);
    }

    private function buildConfig(Request $request, $method)
    {
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
            'availability_mode' => $request->input('availability_mode', 'smart'), // اضافه کردن availability_mode
            'run_method' => $request->input('run_method', 'new'),
            'database' => $request->input('database', 'clear'),
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
                    'add_to_cart_button' => [ // اضافه کردن سلکتور add_to_cart_button
                        'type' => $request->input('selectors.product_page.add_to_cart_button.type'),
                        'selector' => $request->input('selectors.product_page.add_to_cart_button.selector'),
                    ],
                    'out_of_stock' => [ // اضافه کردن سلکتور out_of_stock
                        'type' => $request->input('selectors.product_page.out_of_stock.type'),
                        'selector' => $request->input('selectors.product_page.out_of_stock.selector'),
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
                'data_transformers' => [
                    'price' => 'cleanPrice',
                    'availability' => 'parseAvailability',
                    'off' => 'cleanOff',
                    'guarantee' => 'cleanGuarantee',
                ],
            ],
        ];

        // فقط در صورتی که use_set_category تیک خورده باشد، set_category را اضافه کن
        if (filter_var($request->input('use_set_category', false), FILTER_VALIDATE_BOOLEAN)) {
            $setCategory = trim($request->input('set_category', ''));
            if (!empty($setCategory)) {
                $config['set_category'] = $setCategory;
            }
        }

        if ($request->input('product_id_source') == 'main_page') {
            $config['selectors']['main_page']['product_id'] = [
                'type' => $request->input('selectors.main_page.product_id.type'),
                'selector' => $request->input('selectors.main_page.product_id.selector'),
                'attribute' => $request->input('selectors.main_page.product_id.attribute'),
            ];
        }

        $titlePrefixRules = [];
        $urls = $request->input('title_prefix_rules.url', []);
        $prefixes = $request->input('title_prefix_rules.prefix', []);

        foreach ($urls as $index => $url) {
            if (!empty($url) && !empty($prefixes[$index])) {
                $titlePrefixRules[$url] = [
                    'prefix' => $prefixes[$index],
                ];
            }
        }

        if (!empty($titlePrefixRules)) {
            $config['title_prefix_rules'] = $titlePrefixRules;
        }

        if ($method == 1) {
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
            if ($method == 2) {
                $config['share_product_id_from_method_2'] = filter_var($request->input('share_product_id_from_method_2', false), FILTER_VALIDATE_BOOLEAN);
                $config['container'] = $request->input('container', '');
                $config['scrool'] = (int)$request->input('scrool', 10);

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
                $container = $request->input('container');
                if (!empty($container)) {
                    $config['container'] = $container;
                }

                $config['scrool'] = (int)$request->input('scrool', 10);

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
            $pagination['next_button'] = [
                'selector' => $request->input('pagination_next_button_selector', '')
            ];
        } else {
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
            $navigation['pagination']['next_button'] = [
                'selector' => $request->input('pagination_next_button_selector', '')
            ];
        } else {
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

    public function runScraper($filename)
    {
        $configPath = $this->configPath . 'private/' . $filename . '.json';

        if (!file_exists($configPath)) {
            return redirect()->route('configs.index')->with('error', 'فایل کانفیگ یافت نشد!');
        }

        $runFileName = $filename . '.json';
        $runFilePath = 'private/runs/' . $runFileName;

        if (Storage::exists($runFilePath)) {
            $existingRun = json_decode(Storage::get($runFilePath), true);

            if (isset($existingRun['status']) && $existingRun['status'] === 'running' && isset($existingRun['pid'])) {
                if ($this->isProcessRunning($existingRun['pid'])) {
                    return redirect()->route('configs.index')->with('error', 'اسکرپر برای کانفیگ ' . $filename . ' در حال حاضر در حال اجراست! لطفاً ابتدا آن را متوقف کنید.');
                }

                $command = "ps aux | grep 'scrape:start.*{$filename}' | grep -v grep";
                exec($command, $output);

                if (!empty($output)) {
                    return redirect()->route('configs.index')->with('error', 'اسکرپر برای کانفیگ ' . $filename . ' در حال حاضر در حال اجراست! لطفاً ابتدا آن را متوقف کنید.');
                }

                $existingRun['status'] = 'crashed';
                $existingRun['stopped_at'] = date('Y-m-d H:i:s');
                Storage::put($runFilePath, json_encode($existingRun, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        $logDirectory = storage_path('logs/scrapers');
        if (!file_exists($logDirectory)) {
            mkdir($logDirectory, 0755, true);
        }

        $logFileName = $filename . '_' . date('Y-m-d_H-i-s') . '.log';
        $logFile = $logDirectory . '/' . $logFileName;

        file_put_contents($logFile, "اجرای اسکرپر برای کانفیگ {$filename} در تاریخ " . date('Y-m-d H:i:s') . " شروع شد...\n");

        $cmd = sprintf(
            'nohup php %s scrape:start --config=%s >> %s 2>&1 & echo $!',
            base_path('artisan'),
            $configPath,
            $logFile
        );

        $pid = exec($cmd);

        if (empty($pid) || $pid == 0) {
            file_put_contents($logFile, "\n[" . date('Y-m-d H:i:s') . "] خطا در اجرای اسکرپر: PID نامعتبر\n", FILE_APPEND);
            return redirect()->route('configs.index')->with('error', 'خطا در اجرای اسکرپر. لطفاً لاگ‌ها را بررسی کنید.');
        }

        if (!Storage::exists('private/runs')) {
            Storage::makeDirectory('private/runs', 0755);
        }

        $runInfo = [];

        if (Storage::exists($runFilePath)) {
            $runInfo = json_decode(Storage::get($runFilePath), true);

            if (!isset($runInfo['history'])) {
                $runInfo['history'] = [];
            }

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

                array_unshift($runInfo['history'], $previousRun);

                if (count($runInfo['history']) > 10) {
                    $runInfo['history'] = array_slice($runInfo['history'], 0, 10);
                }
            }
        }

        $runInfo['filename'] = $filename;
        $runInfo['log_file'] = $logFileName;
        $runInfo['started_at'] = date('Y-m-d H:i:s');
        $runInfo['pid'] = (int)$pid;
        $runInfo['status'] = 'running';

        if (isset($runInfo['stopped_at'])) {
            unset($runInfo['stopped_at']);
        }

        Storage::put($runFilePath, json_encode($runInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return redirect()->route('configs.index')->with('success', 'اسکرپر برای کانفیگ ' . $filename . ' با موفقیت اجرا شد. می‌توانید لاگ‌ها را مشاهده کنید.');
    }

    public function history()
    {
        $files = Storage::files('private');
        $configs = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $filename = pathinfo($file, PATHINFO_FILENAME);
                $configData = [
                    'filename' => $filename,
                    'content' => json_decode(Storage::get($file), true)
                ];

                $runFilePath = 'private/runs/' . $filename . '.json';
                if (Storage::exists($runFilePath)) {
                    $runInfo = json_decode(Storage::get($runFilePath), true);
                    if (isset($runInfo['history'])) {
                        $configData['history'] = $runInfo['history'];
                    }
                }

                $configs[] = $configData;
            }
        }

        usort($configs, function ($a, $b) {
            $aTime = isset($a['history'][0]['started_at']) ? $a['history'][0]['started_at'] : '0000-00-00';
            $bTime = isset($b['history'][0]['started_at']) ? $b['history'][0]['started_at'] : '0000-00-00';
            return strcmp($bTime, $aTime);
        });

        return view('configs.history', compact('configs'));
    }

    public function showLogs($filename)
    {
        $logDirectory = storage_path('logs/scrapers');

        $logFiles = [];

        if (file_exists($logDirectory)) {
            $allFiles = scandir($logDirectory);

            foreach ($allFiles as $file) {
                if (strpos($file, $filename . '_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'log') {
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

            usort($logFiles, function ($a, $b) {
                return $b['last_modified'] - $a['last_modified'];
            });
        }

        return view('configs.logs', compact('logFiles', 'filename'));
    }


    public function getLogContent($logfile)
    {
        $logPath = storage_path('logs/scrapers/' . $logfile);

        if (!file_exists($logPath)) {
            return response('فایل لاگ یافت نشد.', 404);
        }

        $content = file_get_contents($logPath);

        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        return response($content)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }


    public function stopScraper($filename)
    {
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

        if ($this->isProcessRunning($pid)) {
            exec("kill -9 {$pid} 2>&1", $output, $result);

            if ($result === 0) {
                $stopped = true;
            }
        }

        $command = "ps aux | grep 'scrape:start.*{$filename}' | grep -v grep | awk '{print $2}'";
        exec($command, $output);

        if (!empty($output)) {
            foreach ($output as $extraPid) {
                exec("kill -9 {$extraPid} 2>&1");
                $stopped = true;
            }
        }

        if ($stopped) {
            $runInfo['status'] = 'stopped';
            $runInfo['stopped_at'] = date('Y-m-d H:i:s');

            Storage::put($runFilePath, json_encode($runInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

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


    private function isProcessRunning($pid)
    {
        if (empty($pid)) {
            return false;
        }

        exec("ps -p {$pid}", $output, $result);
        return $result === 0;
    }
}
