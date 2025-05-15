<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ConfigController extends Controller
{
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
            usort($configs, fn($a, $b) => $a['content']['method'] <=> $b['content']['method']);
        }
        return view('configs.index', compact('configs'));
    }

    public function create()
    {
        $method = request()->query('method', 1); // پیش‌فرض متد ۱
        return view('configs.create', compact('method'));
    }

    public function store(Request $request)
    {
        $method = $request->input('method', 1);
        $rules = $this->getValidationRules($method);

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $config = $this->buildConfig($request->all(), $method);
        $filename = $request->site_name . '.json';
        Storage::put('private/' . $filename, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return redirect()->route('configs.index')->with('success', 'کانفیگ با موفقیت ذخیره شد!');
    }

    public function edit($filename)
    {
        $content = json_decode(Storage::get('private/' . $filename . '.json'), true);
        $method = $content['method'];
        return view('configs.edit', compact('content', 'filename', 'method'));
    }

    public function update(Request $request, $filename)
    {
        $method = $request->input('method');
        $rules = $this->getValidationRules($method);

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $config = $this->buildConfig($request->all(), $method);
        Storage::put('private/' . $filename . '.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return redirect()->route('configs.index')->with('success', 'کانفیگ با موفقیت به‌روزرسانی شد!');
    }

    public function destroy($filename)
    {
        Storage::delete('private/' . $filename . '.json');
        return redirect()->route('configs.index')->with('success', 'کانفیگ با موفقیت حذف شد!');
    }

    private function getValidationRules($method)
    {
        $commonRules = [
            'method' => 'required|in:1,2,3',
            'site_name' => 'required|string|max:255',
            'base_urls' => 'required|array|min:1',
            'base_urls.*' => 'required|url',
            'products_urls' => 'required|array|min:1',
            'products_urls.*' => 'required|url',
            'keep_price_format' => 'required|boolean',
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

        $methodSpecificRules = [];

        if ($method == 1) {
            $methodSpecificRules = [
                'pagination.type' => 'required|in:query,path',
                'pagination.parameter' => 'required|string',
                'pagination.separator' => 'required|string',
                'pagination.max_pages' => 'required|integer|min:1',
                'pagination.use_sample_url' => 'required|boolean',
                'pagination.sample_url' => 'required_if:pagination.use_sample_url,1|url',
            ];
        } elseif ($method == 2) {
            $methodSpecificRules = [
                'container' => 'required|string',
                'scrool' => 'required|integer|min:1',
                'share_product_id_from_method_2' => 'required|boolean',
                'pagination.method' => 'required|in:next_button,url',
                'pagination.next_button.selector' => 'required_if:pagination.method,next_button|string',
                'pagination.url.type' => 'required_if:pagination.method,url|in:query,path',
                'pagination.url.parameter' => 'required_if:pagination.method,url|string',
                'pagination.url.separator' => 'required_if:pagination.method,url|string',
                'pagination.url.max_pages' => 'required_if:pagination.method,url|integer|min:1',
                'pagination.url.use_sample_url' => 'required_if:pagination.method,url|boolean',
                'pagination.url.sample_url' => 'required_if:pagination.url.use_sample_url,1|url',
                'max_pages' => 'required|integer|min:1',
            ];
        } elseif ($method == 3) {
            $methodSpecificRules = [
                'container' => 'required|string',
                'scrool' => 'required|integer|min:1',
                'share_product_id_from_method_2' => 'required|boolean',
                'pagination.method' => 'required|in:next_button,url',
                'pagination.next_button.selector' => 'required_if:pagination.method,next_button|string',
                'pagination.url.type' => 'required_if:pagination.method,url|in:query,path',
                'pagination.url.parameter' => 'required_if:pagination.method,url|string',
                'pagination.url.separator' => 'required_if:pagination.method,url|string',
                'pagination.url.max_pages' => 'required_if:pagination.method,url|integer|min:1',
                'pagination.url.use_sample_url' => 'required_if:pagination.method,url|boolean',
                'pagination.url.sample_url' => 'required_if:pagination.url.use_sample_url,1|url',
                'max_iterations' => 'required|integer|min:1',
            ];
        }

        // اضافه کردن قوانین برای انتخابگرهای صفحه محصول
        $productPageSelectors = [
            'title', 'category', 'availability', 'price', 'image', 'off', 'guarantee', 'product_id'
        ];
        foreach ($productPageSelectors as $field) {
            $commonRules["selectors.product_page.{$field}.type"] = 'required|string';
            $commonRules["selectors.product_page.{$field}.selector"] = 'required|string';
            if (in_array($field, ['image', 'product_id'])) {
                $commonRules["selectors.product_page.{$field}.attribute"] = 'required|string';
            }
        }

        return array_merge($commonRules, $methodSpecificRules);
    }

    private function buildConfig(array $data, $method)
    {
        $config = [
            'method' => (int) $method,
            'base_urls' => $data['base_urls'],
            'products_urls' => $data['products_urls'],
            'request_delay_min' => 3000,
            'request_delay_max' => 5000,
            'timeout' => 120,
            'max_retries' => 2,
            'concurrency' => 1,
            'batch_size' => 1,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124',
            'verify_ssl' => false,
            'keep_price_format' => filter_var($data['keep_price_format'], FILTER_VALIDATE_BOOLEAN),
            'image_method' => 'product_page',
            'product_id_method' => $data['product_id_method'],
            'product_id_source' => $data['product_id_source'],
            'product_id_fallback_script_patterns' => [
                'product_id:\\s*\"(\\d+)\"',
                'product_id:\\s*(\\d+)'
            ],
            'guarantee_method' => $data['guarantee_method'],
            'guarantee_keywords' => $data['guarantee_keywords'],
            'availability_keywords' => [
                'positive' => $data['availability_keywords']['positive'],
                'negative' => $data['availability_keywords']['negative'],
            ],
            'price_keywords' => [
                'unpriced' => $data['price_keywords']['unpriced'],
            ],
            'selectors' => [
                'main_page' => [
                    'product_links' => [
                        'type' => $data['selectors']['main_page']['product_links']['type'],
                        'selector' => $data['selectors']['main_page']['product_links']['selector'],
                        'attribute' => $data['selectors']['main_page']['product_links']['attribute'],
                    ],
                    'product_id' => $data['product_id_source'] === 'main_page' ? [
                        'type' => $data['selectors']['main_page']['product_id']['type'] ?? '',
                        'selector' => $data['selectors']['main_page']['product_id']['selector'] ?? '',
                        'attribute' => $data['selectors']['main_page']['product_id']['attribute'] ?? '',
                    ] : [],
                    'guarantee' => [
                        'type' => 'css',
                        'selector' => '',
                    ],
                    'image' => [
                        'type' => 'css',
                        'selector' => '',
                        'attribute' => 'src',
                    ],
                ],
                'product_page' => [
                    'title' => [
                        'type' => $data['selectors']['product_page']['title']['type'],
                        'selector' => $data['selectors']['product_page']['title']['selector'],
                    ],
                    'category' => [
                        'type' => $data['selectors']['product_page']['category']['type'],
                        'selector' => $data['selectors']['product_page']['category']['selector'],
                    ],
                    'availability' => [
                        'type' => $data['selectors']['product_page']['availability']['type'],
                        'selector' => $data['selectors']['product_page']['availability']['selector'],
                    ],
                    'price' => [
                        'type' => $data['selectors']['product_page']['price']['type'],
                        'selector' => $data['selectors']['product_page']['price']['selector'],
                    ],
                    'image' => [
                        'type' => $data['selectors']['product_page']['image']['type'],
                        'selector' => $data['selectors']['product_page']['image']['selector'],
                        'attribute' => $data['selectors']['product_page']['image']['attribute'],
                    ],
                    'off' => [
                        'type' => $data['selectors']['product_page']['off']['type'],
                        'selector' => $data['selectors']['product_page']['off']['selector'],
                    ],
                    'guarantee' => [
                        'type' => $data['selectors']['product_page']['guarantee']['type'],
                        'selector' => $data['selectors']['product_page']['guarantee']['selector'],
                    ],
                    'product_id' => [
                        'type' => $data['selectors']['product_page']['product_id']['type'],
                        'selector' => $data['selectors']['product_page']['product_id']['selector'],
                        'attribute' => $data['selectors']['product_page']['product_id']['attribute'],
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

        if ($method == 1) {
            $config['method_settings'] = [
                'method_1' => [
                    'enabled' => true,
                    'pagination' => [
                        'type' => $data['pagination']['type'],
                        'parameter' => $data['pagination']['parameter'],
                        'separator' => $data['pagination']['separator'],
                        'suffix' => $data['pagination']['suffix'] ?? '',
                        'max_pages' => (int) $data['pagination']['max_pages'],
                        'use_sample_url' => filter_var($data['pagination']['use_sample_url'], FILTER_VALIDATE_BOOLEAN),
                        'sample_url' => $data['pagination']['use_sample_url'] ? ($data['pagination']['sample_url'] ?? '') : '',
                        'use_webdriver' => false,
                        'use_dynamic_pagination' => false,
                        'force_trailing_slash' => true,
                        'ignore_redirects' => true,
                    ],
                ],
            ];
        } elseif ($method == 2) {
            $config['processing_method'] = 1;
            $config['share_product_id_from_method_2'] = filter_var($data['share_product_id_from_method_2'], FILTER_VALIDATE_BOOLEAN);
            $config['container'] = $data['container'];
            $config['scrool'] = (int) $data['scrool'];
            $config['method_2'] = [
                'enabled' => true,
                'navigation' => [
                    'use_webdriver' => true,
                    'pagination' => [
                        'method' => $data['pagination']['method'],
                        'next_button' => $data['pagination']['method'] === 'next_button' ? [
                            'selector' => $data['pagination']['next_button']['selector'] ?? '',
                            'max_clicks' => (int) $data['max_pages'],
                        ] : [],
                        'url' => $data['pagination']['method'] === 'url' ? [
                            'type' => $data['pagination']['url']['type'] ?? '',
                            'parameter' => $data['pagination']['url']['parameter'] ?? '',
                            'separator' => $data['pagination']['url']['separator'] ?? '',
                            'suffix' => $data['pagination']['url']['suffix'] ?? '',
                            'max_pages' => (int) $data['pagination']['url']['max_pages'],
                            'use_sample_url' => filter_var($data['pagination']['url']['use_sample_url'], FILTER_VALIDATE_BOOLEAN),
                            'sample_url' => $data['pagination']['url']['use_sample_url'] ? ($data['pagination']['url']['sample_url'] ?? '') : '',
                            'use_webdriver' => true,
                        ] : [],
                    ],
                    'max_pages' => (int) $data['max_pages'],
                    'scroll_delay' => 5000,
                ],
            ];
        } elseif ($method == 3) {
            $config['processing_method'] = 3;
            $config['share_product_id_from_method_2'] = filter_var($data['share_product_id_from_method_2'], FILTER_VALIDATE_BOOLEAN);
            $config['container'] = $data['container'];
            $config['scrool'] = (int) $data['scrool'];
            $config['method_3'] = [
                'enabled' => true,
                'navigation' => [
                    'use_webdriver' => true,
                    'pagination' => [
                        'method' => $data['pagination']['method'],
                        'next_button' => $data['pagination']['method'] === 'next_button' ? [
                            'selector' => $data['pagination']['next_button']['selector'] ?? '',
                            'max_clicks' => (int) $data['max_iterations'],
                        ] : [],
                        'url' => $data['pagination']['method'] === 'url' ? [
                            'type' => $data['pagination']['url']['type'] ?? '',
                            'parameter' => $data['pagination']['url']['parameter'] ?? '',
                            'separator' => $data['pagination']['url']['separator'] ?? '',
                            'suffix' => $data['pagination']['url']['suffix'] ?? '',
                            'max_pages' => (int) $data['max_iterations'],
                            'use_sample_url' => filter_var($data['pagination']['url']['use_sample_url'], FILTER_VALIDATE_BOOLEAN),
                            'sample_url' => $data['pagination']['url']['use_sample_url'] ? ($data['pagination']['url']['sample_url'] ?? '') : '',
                            'use_webdriver' => true,
                        ] : [],
                    ],
                    'max_iterations' => (int) $data['max_iterations'],
                    'timing' => [
                        'scroll_delay' => 5000,
                    ],
                ],
            ];
        }

        return $config;
    }
}
