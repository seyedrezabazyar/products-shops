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
        }
        return view('configs.index', compact('configs'));
    }

    public function create()
    {
        return view('configs.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
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
            'pagination.type' => 'required|in:query,path',
            'pagination.parameter' => 'required|string',
            'pagination.separator' => 'required|string',
            'pagination.max_pages' => 'required|integer|min:1',
            'pagination.use_sample_url' => 'required|boolean',
            'pagination.sample_url' => 'required_if:pagination.use_sample_url,1|url',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $config = $this->buildConfig($request->all());
        $filename = $request->site_name . '.json';
        Storage::put('private/' . $filename, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return redirect()->route('configs.index')->with('success', 'کانفیگ با موفقیت ذخیره شد!');
    }

    public function edit($filename)
    {
        $content = json_decode(Storage::get('private/' . $filename . '.json'), true);
        return view('configs.edit', compact('content', 'filename'));
    }

    public function update(Request $request, $filename)
    {
        $validator = Validator::make($request->all(), [
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
            'pagination.type' => 'required|in:query,path',
            'pagination.parameter' => 'required|string',
            'pagination.separator' => 'required|string',
            'pagination.max_pages' => 'required|integer|min:1',
            'pagination.use_sample_url' => 'required|boolean',
            'pagination.sample_url' => 'required_if:pagination.use_sample_url,1|url',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $config = $this->buildConfig($request->all());
        Storage::put('private/' . $filename . '.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return redirect()->route('configs.index')->with('success', 'کانفیگ با موفقیت به‌روزرسانی شد!');
    }

    public function destroy($filename)
    {
        Storage::delete('private/' . $filename . '.json');
        return redirect()->route('configs.index')->with('success', 'کانفیگ با موفقیت حذف شد!');
    }

    private function buildConfig(array $data)
    {
        return [
            'method' => 1,
            'base_urls' => $data['base_urls'],
            'products_urls' => $data['products_urls'],
            'request_delay_min' => 1000,
            'request_delay_max' => 1000,
            'timeout' => 60,
            'max_retries' => 2,
            'concurrency' => 10,
            'batch_size' => 10,
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
            'category_method' => 'selector',
            'category_word_count' => 1,
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
                        'product_id' => $data['selectors']['main_page']['product_links']['product_id'] ?? '',
                    ],
                    'product_id' => $data['product_id_source'] === 'main_page' ? [
                        'type' => $data['selectors']['main_page']['product_id']['type'] ?? '',
                        'selector' => $data['selectors']['main_page']['product_id']['selector'] ?? '',
                        'attribute' => $data['selectors']['main_page']['product_id']['attribute'] ?? '',
                    ] : [],
                    'guarantee' => [
                        'type' => 'css',
                        'selector' => 'div.product-seller-row:nth-child(2) > div:nth-child(2) > div:nth-child(1)',
                    ],
                    'image' => [
                        'type' => 'css',
                        'selector' => 'li.product img',
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
            'method_settings' => [
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
            ],
        ];
    }
}
