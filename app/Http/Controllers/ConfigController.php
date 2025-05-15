<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ConfigController extends Controller
{
    protected $configPath;

    public function __construct()
    {
        $this->configPath = storage_path('app/configs');

        if (!File::exists($this->configPath)) {
            File::makeDirectory($this->configPath, 0755, true);
        }
    }

    /**
     * Display a listing of the configs.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $files = File::files($this->configPath);
        $configs = [];

        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $configData = json_decode(File::get($file), true);
            $configs[] = [
                'name' => $name,
                'data' => $configData
            ];
        }

        return view('configs.index', compact('configs'));
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
        $validator = Validator::make($request->all(), [
            'name' => 'required|alpha_dash|unique:configs,name',
            'method' => 'required|in:1,2,3',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $name = $request->input('name');
        $method = (int)$request->input('method');

        $config = $this->buildConfigData($request, $method);

        File::put($this->configPath . '/' . $name . '.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return redirect()->route('configs.index')->with('success', 'کانفیگ با موفقیت ایجاد شد!');
    }

    /**
     * Show the form for editing the specified config.
     *
     * @param  string  $name
     * @return \Illuminate\Http\Response
     */
    public function edit($name)
    {
        $filePath = $this->configPath . '/' . $name . '.json';

        if (!File::exists($filePath)) {
            return redirect()->route('configs.index')->with('error', 'کانفیگ پیدا نشد!');
        }

        $config = json_decode(File::get($filePath), true);

        return view('configs.edit', compact('name', 'config'));
    }

    /**
     * Update the specified config in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $name
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $name)
    {
        $filePath = $this->configPath . '/' . $name . '.json';

        if (!File::exists($filePath)) {
            return redirect()->route('configs.index')->with('error', 'کانفیگ پیدا نشد!');
        }

        $validator = Validator::make($request->all(), [
            'method' => 'required|in:1,2,3',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $method = (int)$request->input('method');
        $config = $this->buildConfigData($request, $method);

        File::put($filePath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return redirect()->route('configs.index')->with('success', 'کانفیگ با موفقیت به‌روزرسانی شد!');
    }

    /**
     * Remove the specified config from storage.
     *
     * @param  string  $name
     * @return \Illuminate\Http\Response
     */
    public function destroy($name)
    {
        $filePath = $this->configPath . '/' . $name . '.json';

        if (File::exists($filePath)) {
            File::delete($filePath);
            return redirect()->route('configs.index')->with('success', 'کانفیگ با موفقیت حذف شد!');
        }

        return redirect()->route('configs.index')->with('error', 'کانفیگ پیدا نشد!');
    }

    /**
     * Build config data from request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $method
     * @return array
     */
    private function buildConfigData(Request $request, $method)
    {
        // Common configuration for all methods
        $config = [
            'method' => $method,
            'processing_method' => $method === 3 ? 3 : 1,
            'base_urls' => $this->formatArrayInput($request->input('base_urls')),
            'products_urls' => $this->formatArrayInput($request->input('products_urls')),
            'request_delay_min' => (int)$request->input('request_delay_min', 3000),
            'request_delay_max' => (int)$request->input('request_delay_max', 5000),
            'timeout' => (int)$request->input('timeout', 120),
            'max_retries' => (int)$request->input('max_retries', 2),
            'concurrency' => (int)$request->input('concurrency', 10),
            'batch_size' => (int)$request->input('batch_size', 10),
            'request_delay' => (int)$request->input('request_delay', 3000),
            'user_agent' => $request->input('user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124'),
            'verify_ssl' => $request->has('verify_ssl'),
            'keep_price_format' => $request->has('keep_price_format'),
            'image_method' => $request->input('image_method', 'product_page'),
            'availability_mode' => $request->input('availability_mode', 'selector'),
            'product_id_method' => $request->input('product_id_method', 'selector'),
            'product_id_source' => $request->input('product_id_source', 'product_page'),
            'product_id_url_pattern' => $request->input('product_id_url_pattern', ''),
            'product_id_fallback_script_patterns' => $this->formatArrayInput($request->input('product_id_fallback_script_patterns', ['product_id:\\s*\"(\\d+)\"', 'product_id:\\s*(\\d+)'])),
            'category_method' => $request->input('category_method', 'selector'),
            'category_word_count' => (int)$request->input('category_word_count', 1),
            'guarantee_method' => $request->input('guarantee_method', 'title'),
            'guarantee_keywords' => $this->formatArrayInput($request->input('guarantee_keywords')),
            'selectors' => [
                'main_page' => [
                    'product_links' => [
                        'type' => $request->input('main_page_product_links_type', 'css'),
                        'selector' => $request->input('main_page_product_links_selector', ''),
                        'attribute' => $request->input('main_page_product_links_attribute', 'href'),
                    ],
                    'image' => [
                        'type' => $request->input('main_page_image_type', 'css'),
                        'selector' => $request->input('main_page_image_selector', 'li.product img'),
                        'attribute' => $request->input('main_page_image_attribute', 'src'),
                    ],
                    'guarantee' => [
                        'type' => $request->input('main_page_guarantee_type', 'css'),
                        'selector' => $request->input('main_page_guarantee_selector', 'div.product-seller-row:nth-child(2) > div:nth-child(2) > div:nth-child(1)'),
                    ],
                ],
                'product_page' => [
                    'title' => [
                        'type' => $request->input('product_page_title_type', 'css'),
                        'selector' => $request->input('product_page_title_selector', ''),
                    ],
                    'category' => [
                        'type' => $request->input('product_page_category_type', 'css'),
                        'selector' => $request->input('product_page_category_selector', ''),
                    ],
                    'availability' => [
                        'type' => $request->input('product_page_availability_type', 'css'),
                        'selector' => $this->formatArrayInput($request->input('product_page_availability_selector')),
                        'keyword' => $request->input('product_page_availability_keyword', 'ناموجود'),
                    ],
                    'price' => [
                        'type' => $request->input('product_page_price_type', 'css'),
                        'selector' => $this->formatArrayInput($request->input('product_page_price_selector')),
                    ],
                    'image' => [
                        'type' => $request->input('product_page_image_type', 'css'),
                        'selector' => $request->input('product_page_image_selector', ''),
                        'attribute' => $request->input('product_page_image_attribute', 'src'),
                    ],
                    'off' => [
                        'type' => $request->input('product_page_off_type', 'css'),
                        'selector' => $request->input('product_page_off_selector', ''),
                    ],
                    'guarantee' => [
                        'type' => $request->input('product_page_guarantee_type', 'css'),
                        'selector' => $request->input('product_page_guarantee_selector', ''),
                    ],
                    'product_id' => [
                        'type' => $request->input('product_page_product_id_type', 'css'),
                        'selector' => $request->input('product_page_product_id_selector', ''),
                        'attribute' => $request->input('product_page_product_id_attribute', ''),
                    ],
                ],
            ],
            'data_transformers' => [
                'price' => 'cleanPrice',
                'availability' => 'parseAvailability',
                'off' => 'cleanOff',
                'guarantee' => 'cleanGuarantee',
            ],
            'availability_keywords' => [
                'positive' => $this->formatArrayInput($request->input('availability_keywords_positive')),
                'negative' => $this->formatArrayInput($request->input('availability_keywords_negative')),
            ],
            'price_keywords' => [
                'unpriced' => $this->formatArrayInput($request->input('price_keywords_unpriced')),
            ],
        ];

        // Add main_page product_id if required
        if ($request->input('product_id_source') === 'main_page') {
            $config['selectors']['main_page']['product_id'] = [
                'type' => $request->input('main_page_product_id_type', 'css'),
                'selector' => $request->input('main_page_product_id_selector', ''),
                'attribute' => $request->input('main_page_product_id_attribute', ''),
            ];
        }

        // Configure method-specific settings
        $this->configureMethodSettings($config, $request, $method);

        return $config;
    }

    /**
     * Configure method-specific settings.
     *
     * @param  array  &$config
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $method
     */
    private function configureMethodSettings(&$config, $request, $method)
    {
        // Method 1 settings
        $config['method_settings']['method_1'] = [
            'enabled' => $method == 1,
            'pagination' => [
                'type' => $request->input('pagination_type', 'query'),
                'parameter' => $request->input('pagination_parameter', 'page'),
                'separator' => $request->input('pagination_separator', '='),
                'suffix' => $request->input('pagination_suffix', ''),
                'max_pages' => (int)$request->input('pagination_max_pages', 3),
                'use_sample_url' => $request->has('pagination_use_sample_url'),
                'sample_url' => $request->input('pagination_sample_url', ''),
                'use_webdriver' => false,
                'use_dynamic_pagination' => false,
                'force_trailing_slash' => true,
                'ignore_redirects' => true,
            ],
        ];

        // Method 2 settings
        if ($method == 2) {
            $config['method_settings']['method_2'] = [
                'enabled' => true,
                'share_product_id_from_method_2' => $request->has('share_product_id_from_method_2'),
                'scrool' => (int)$request->input('scrool', 10),
                'container' => $request->input('container', ''),
                'navigation' => $this->buildNavigationConfig($request, false),
            ];
        }

        // Method 3 settings
        if ($method == 3) {
            $config['method_settings']['method_3'] = [
                'enabled' => true,
                'scrool' => (int)$request->input('scrool', 10),
                'container' => $request->input('container', ''),
                'basescroll' => (int)$request->input('basescroll', 10),
                'navigation' => $this->buildNavigationConfig($request, true),
            ];
        }
    }

    /**
     * Build navigation configuration from request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $isMethod3
     * @return array
     */
    private function buildNavigationConfig(Request $request, $isMethod3 = false)
    {
        $navigation = [
            'use_webdriver' => true,
            'pagination' => [
                'method' => $request->input('pagination_method', 'next_button'),
            ],
        ];

        // Common pagination settings
        if ($request->input('pagination_method') === 'next_button') {
            $navigation['pagination']['next_button'] = [
                'selector' => $request->input('pagination_next_button_selector', ''),
            ];

            if (!$isMethod3) {
                $navigation['pagination']['next_button']['max_clicks'] =
                    (int)$request->input('pagination_max_pages', 3);
            }
        } else if ($request->input('pagination_method') === 'url') {
            $navigation['pagination']['url'] = [
                'type' => $request->input('pagination_url_type', 'query'),
                'parameter' => $request->input('pagination_url_parameter', 'page'),
                'separator' => $request->input('pagination_url_separator', '='),
                'suffix' => $request->input('pagination_url_suffix', ''),
                'max_pages' => (int)$request->input('pagination_max_pages', 3),
                'use_sample_url' => $request->has('pagination_use_sample_url'),
                'sample_url' => $request->input('pagination_sample_url', ''),
                'use_webdriver' => true,
            ];
        }

        // Method specific settings
        if ($isMethod3) {
            $navigation['max_iterations'] = (int)$request->input('pagination_max_pages', 3);
            $navigation['timing'] = [
                'scroll_delay' => (int)$request->input('scroll_delay', 5000),
            ];
        } else {
            $navigation['max_pages'] = (int)$request->input('pagination_max_pages', 3);
            $navigation['scroll_delay'] = (int)$request->input('scroll_delay', 5000);
        }

        return $navigation;
    }

    /**
     * Format array input from string or array.
     *
     * @param  mixed  $input
     * @return array
     */
    private function formatArrayInput($input)
    {
        if (empty($input)) {
            return [];
        }

        if (is_array($input)) {
            return array_filter($input, function ($value) {
                return !empty($value);
            });
        }

        $items = explode(',', $input);
        return array_map('trim', $items);
    }
}
