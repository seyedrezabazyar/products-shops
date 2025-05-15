<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ایجاد پیکربندی جدید</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @font-face {
            font-family: 'IRANSans';
            src: url('https://cdn.jsdelivr.net/gh/rastikerdar/iranian-sans/WebFonts/woff2/IRANSansWeb.woff2') format('woff2');
            font-weight: normal;
        }

        * {
            font-family: 'IRANSans', Tahoma, Arial, sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
<div class="min-h-screen py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-10">
            <h1 class="text-3xl font-bold text-gray-800">ایجاد پیکربندی جدید</h1>
            <p class="mt-2 text-gray-600">لطفا اطلاعات مورد نیاز را با دقت وارد کنید.</p>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-md p-6">
            @if ($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <strong class="font-bold">خطا!</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('configs.store') }}" method="POST" class="space-y-8">
                @csrf

                <!-- Base Configuration -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">تنظیمات پایه</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">نام پیکربندی <span class="text-red-600">*</span></label>
                            <input type="text" name="name" id="name" required value="{{ old('name') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                            <p class="mt-1 text-xs text-gray-500">فقط حروف، اعداد، خط تیره و زیرخط مجاز است</p>
                        </div>

                        <div>
                            <label for="method" class="block text-sm font-medium text-gray-700 mb-1">متد استخراج <span class="text-red-600">*</span></label>
                            <select name="method" id="method" required class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                <option value="1" {{ old('method') == '1' ? 'selected' : '' }}>متد 1 (استخراج ساده)</option>
                                <option value="2" {{ old('method') == '2' ? 'selected' : '' }}>متد 2 (استخراج با مرورگر)</option>
                                <option value="3" {{ old('method') == '3' ? 'selected' : '' }}>متد 3 (استخراج پیشرفته)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- URL Configuration -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">پیکربندی URL ها</h3>
                    <div class="space-y-4">
                        <div>
                            <label for="base_urls" class="block text-sm font-medium text-gray-700 mb-1">URL های پایه</label>
                            <textarea name="base_urls" id="base_urls" rows="3" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">{{ old('base_urls') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">هر URL را در یک خط یا با کاما جدا کنید</p>
                        </div>

                        <div>
                            <label for="products_urls" class="block text-sm font-medium text-gray-700 mb-1">URL های محصولات</label>
                            <textarea name="products_urls" id="products_urls" rows="3" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">{{ old('products_urls') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">هر URL را در یک خط یا با کاما جدا کنید</p>
                        </div>
                    </div>
                </div>

                <!-- Request Settings -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">تنظیمات درخواست</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="request_delay_min" class="block text-sm font-medium text-gray-700 mb-1">حداقل تاخیر درخواست (ms)</label>
                            <input type="number" name="request_delay_min" id="request_delay_min" value="{{ old('request_delay_min', 3000) }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                        </div>

                        <div>
                            <label for="request_delay_max" class="block text-sm font-medium text-gray-700 mb-1">حداکثر تاخیر درخواست (ms)</label>
                            <input type="number" name="request_delay_max" id="request_delay_max" value="{{ old('request_delay_max', 5000) }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                        </div>

                        <div>
                            <label for="request_delay" class="block text-sm font-medium text-gray-700 mb-1">تاخیر درخواست (ms)</label>
                            <input type="number" name="request_delay" id="request_delay" value="{{ old('request_delay', 3000) }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                        </div>

                        <div>
                            <label for="timeout" class="block text-sm font-medium text-gray-700 mb-1">زمان انتظار (ثانیه)</label>
                            <input type="number" name="timeout" id="timeout" value="{{ old('timeout', 120) }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                        </div>

                        <div>
                            <label for="max_retries" class="block text-sm font-medium text-gray-700 mb-1">حداکثر تعداد تلاش مجدد</label>
                            <input type="number" name="max_retries" id="max_retries" value="{{ old('max_retries', 2) }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                        </div>

                        <div>
                            <label for="concurrency" class="block text-sm font-medium text-gray-700 mb-1">همزمانی</label>
                            <input type="number" name="concurrency" id="concurrency" value="{{ old('concurrency', 1) }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                        </div>

                        <div>
                            <label for="batch_size" class="block text-sm font-medium text-gray-700 mb-1">اندازه دسته</label>
                            <input type="number" name="batch_size" id="batch_size" value="{{ old('batch_size', 1) }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                        </div>

                        <div>
                            <label for="user_agent" class="block text-sm font-medium text-gray-700 mb-1">User Agent</label>
                            <input type="text" name="user_agent" id="user_agent" value="{{ old('user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                        </div>

                        <div class="flex items-center pt-6">
                            <input type="checkbox" name="verify_ssl" id="verify_ssl" {{ old('verify_ssl') ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="verify_ssl" class="mr-2 block text-sm text-gray-700">تایید SSL</label>
                        </div>

                        <div class="flex items-center pt-6">
                            <input type="checkbox" name="keep_price_format" id="keep_price_format" {{ old('keep_price_format') ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="keep_price_format" class="mr-2 block text-sm text-gray-700">حفظ فرمت قیمت</label>
                        </div>
                    </div>
                </div>

                <!-- WebDriver Settings (Method 2 & 3) -->
                <div class="border-b border-gray-200 pb-6 method-2-3-fields">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">تنظیمات WebDriver (متد 2 و 3)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="scrool" class="block text-sm font-medium text-gray-700 mb-1">تعداد اسکرول</label>
                            <input type="number" name="scrool" id="scrool" value="{{ old('scrool', 10) }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                        </div>

                        <div>
                            <label for="container" class="block text-sm font-medium text-gray-700 mb-1">سلکتور محتوا</label>
                            <input type="text" name="container" id="container" value="{{ old('container') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                            <p class="mt-1 text-xs text-gray-500">سلکتور CSS برای عنصر محتوایی اصلی</p>
                        </div>

                        <div class="method-3-fields">
                            <label for="basescroll" class="block text-sm font-medium text-gray-700 mb-1">تعداد اسکرول پایه (متد 3)</label>
                            <input type="number" name="basescroll" id="basescroll" value="{{ old('basescroll', 10) }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                        </div>

                        <div class="method-2-fields">
                            <div class="flex items-center pt-6">
                                <input type="checkbox" name="share_product_id_from_method_2" id="share_product_id_from_method_2" {{ old('share_product_id_from_method_2') ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <label for="share_product_id_from_method_2" class="mr-2 block text-sm text-gray-700">اشتراک‌گذاری شناسه محصول از متد 2</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination Settings -->
                <div class="border-b border-gray-200 pb-6 method-2-3-fields">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">تنظیمات صفحه‌بندی</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="pagination_method" class="block text-sm font-medium text-gray-700 mb-1">روش صفحه‌بندی</label>
                            <select name="pagination_method" id="pagination_method" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                <option value="next_button" {{ old('pagination_method', 'next_button') == 'next_button' ? 'selected' : '' }}>دکمه صفحه بعد</option>
                                <option value="url" {{ old('pagination_method') == 'url' ? 'selected' : '' }}>URL</option>
                            </select>
                        </div>

                        <div>
                            <label for="pagination_max_pages" class="block text-sm font-medium text-gray-700 mb-1">حداکثر تعداد صفحات</label>
                            <input type="number" name="pagination_max_pages" id="pagination_max_pages" value="{{ old('pagination_max_pages', 3) }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                        </div>

                        <div>
                            <label for="scroll_delay" class="block text-sm font-medium text-gray-700 mb-1">تاخیر اسکرول (ms)</label>
                            <input type="number" name="scroll_delay" id="scroll_delay" value="{{ old('scroll_delay', 5000) }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                        </div>

                        <!-- Next Button Settings -->
                        <div class="next-button-fields">
                            <label for="pagination_next_button_selector" class="block text-sm font-medium text-gray-700 mb-1">سلکتور دکمه صفحه بعد</label>
                            <input type="text" name="pagination_next_button_selector" id="pagination_next_button_selector" value="{{ old('pagination_next_button_selector') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                            <p class="mt-1 text-xs text-gray-500">سلکتور CSS برای دکمه صفحه بعد</p>
                        </div>

                        <!-- URL Settings -->
                        <div class="url-fields hidden">
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label for="pagination_url_type" class="block text-sm font-medium text-gray-700 mb-1">نوع URL</label>
                                    <select name="pagination_url_type" id="pagination_url_type" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                        <option value="query" {{ old('pagination_url_type', 'query') == 'query' ? 'selected' : '' }}>Query</option>
                                        <option value="path" {{ old('pagination_url_type') == 'path' ? 'selected' : '' }}>Path</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="pagination_url_parameter" class="block text-sm font-medium text-gray-700 mb-1">پارامتر URL</label>
                                    <input type="text" name="pagination_url_parameter" id="pagination_url_parameter" value="{{ old('pagination_url_parameter', 'page') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                </div>

                                <div>
                                    <label for="pagination_url_separator" class="block text-sm font-medium text-gray-700 mb-1">جداکننده URL</label>
                                    <input type="text" name="pagination_url_separator" id="pagination_url_separator" value="{{ old('pagination_url_separator', '=') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                </div>

                                <div>
                                    <label for="pagination_url_suffix" class="block text-sm font-medium text-gray-700 mb-1">پسوند URL</label>
                                    <input type="text" name="pagination_url_suffix" id="pagination_url_suffix" value="{{ old('pagination_url_suffix') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                </div>

                                <div class="flex items-center pt-2">
                                    <input type="checkbox" name="pagination_use_sample_url" id="pagination_use_sample_url" {{ old('pagination_use_sample_url') ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <label for="pagination_use_sample_url" class="mr-2 block text-sm text-gray-700">استفاده از URL نمونه</label>
                                </div>

                                <div class="sample-url-field {{ old('pagination_use_sample_url') ? '' : 'hidden' }}">
                                    <label for="pagination_sample_url" class="block text-sm font-medium text-gray-700 mb-1">URL نمونه</label>
                                    <input type="text" name="pagination_sample_url" id="pagination_sample_url" value="{{ old('pagination_sample_url') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Page Selectors -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">سلکتورهای صفحه اصلی</h3>
                    <div class="grid grid-cols-1 gap-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="main_page_product_links_type" class="block text-sm font-medium text-gray-700 mb-1">نوع سلکتور لینک محصولات</label>
                                <select name="main_page_product_links_type" id="main_page_product_links_type" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                    <option value="css" {{ old('main_page_product_links_type', 'css') == 'css' ? 'selected' : '' }}>CSS</option>
                                    <option value="xpath" {{ old('main_page_product_links_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
                                </select>
                            </div>

                            <div>
                                <label for="main_page_product_links_selector" class="block text-sm font-medium text-gray-700 mb-1">سلکتور لینک محصولات</label>
                                <input type="text" name="main_page_product_links_selector" id="main_page_product_links_selector" value="{{ old('main_page_product_links_selector') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                            </div>

                            <div>
                                <label for="main_page_product_links_attribute" class="block text-sm font-medium text-gray-700 mb-1">ویژگی لینک محصولات</label>
                                <input type="text" name="main_page_product_links_attribute" id="main_page_product_links_attribute" value="{{ old('main_page_product_links_attribute', 'href') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                            </div>
                        </div>

                        <!-- Product ID from Main Page Fields (conditional) -->
                        <div class="product-id-main-page-fields hidden">
                            <div class="mt-4 border-t border-gray-200 pt-4">
                                <h4 class="text-lg font-medium text-gray-800 mb-2">شناسه محصول از صفحه اصلی</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="main_page_product_id_type" class="block text-sm font-medium text-gray-700 mb-1">نوع سلکتور شناسه محصول</label>
                                        <select name="main_page_product_id_type" id="main_page_product_id_type" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                            <option value="css" {{ old('main_page_product_id_type', 'css') == 'css' ? 'selected' : '' }}>CSS</option>
                                            <option value="xpath" {{ old('main_page_product_id_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="main_page_product_id_selector" class="block text-sm font-medium text-gray-700 mb-1">سلکتور شناسه محصول</label>
                                        <input type="text" name="main_page_product_id_selector" id="main_page_product_id_selector" value="{{ old('main_page_product_id_selector') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                    </div>

                                    <div>
                                        <label for="main_page_product_id_attribute" class="block text-sm font-medium text-gray-700 mb-1">ویژگی شناسه محصول</label>
                                        <input type="text" name="main_page_product_id_attribute" id="main_page_product_id_attribute" value="{{ old('main_page_product_id_attribute') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product ID Configuration -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">پیکربندی شناسه محصول</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="product_id_method" class="block text-sm font-medium text-gray-700 mb-1">روش شناسه محصول</label>
                            <select name="product_id_method" id="product_id_method" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                <option value="selector" {{ old('product_id_method', 'selector') == 'selector' ? 'selected' : '' }}>سلکتور</option>
                                <option value="url_pattern" {{ old('product_id_method') == 'url_pattern' ? 'selected' : '' }}>الگوی URL</option>
                                <option value="script" {{ old('product_id_method') == 'script' ? 'selected' : '' }}>اسکریپت</option>
                            </select>
                        </div>

                        <div>
                            <label for="product_id_source" class="block text-sm font-medium text-gray-700 mb-1">منبع شناسه محصول</label>
                            <select name="product_id_source" id="product_id_source" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                <option value="product_page" {{ old('product_id_source', 'product_page') == 'product_page' ? 'selected' : '' }}>صفحه محصول</option>
                                <option value="main_page" {{ old('product_id_source') == 'main_page' ? 'selected' : '' }}>صفحه اصلی</option>
                            </select>
                        </div>

                        <div class="url-pattern-fields {{ old('product_id_method') == 'url_pattern' ? '' : 'hidden' }}">
                            <label for="product_id_url_pattern" class="block text-sm font-medium text-gray-700 mb-1">الگوی URL شناسه محصول</label>
                            <input type="text" name="product_id_url_pattern" id="product_id_url_pattern" value="{{ old('product_id_url_pattern') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                            <p class="mt-1 text-xs text-gray-500">مثال: /product-(\d+)/</p>
                        </div>

                        <div class="script-fields {{ old('product_id_method') == 'script' ? '' : 'hidden' }}">
                            <label for="product_id_fallback_script_patterns" class="block text-sm font-medium text-gray-700 mb-1">الگوهای اسکریپت شناسه محصول</label>
                            <textarea name="product_id_fallback_script_patterns" id="product_id_fallback_script_patterns" rows="2" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">{{ old('product_id_fallback_script_patterns') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">هر الگو را در یک خط یا با کاما جدا کنید</p>
                        </div>
                    </div>
                </div>

                <!-- Product Page Selectors -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">سلکتورهای صفحه محصول</h3>

                    <!-- Title Selector -->
                    <div class="mb-6">
                        <h4 class="text-lg font-medium text-gray-800 mb-2">عنوان محصول</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="product_page_title_type" class="block text-sm font-medium text-gray-700 mb-1">نوع سلکتور</label>
                                <select name="product_page_title_type" id="product_page_title_type" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                    <option value="css" {{ old('product_page_title_type', 'css') == 'css' ? 'selected' : '' }}>CSS</option>
                                    <option value="xpath" {{ old('product_page_title_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
                                </select>
                            </div>

                            <div>
                                <label for="product_page_title_selector" class="block text-sm font-medium text-gray-700 mb-1">سلکتور</label>
                                <input type="text" name="product_page_title_selector" id="product_page_title_selector" value="{{ old('product_page_title_selector') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Category Selector -->
                    <div class="mb-6">
                        <h4 class="text-lg font-medium text-gray-800 mb-2">دسته‌بندی محصول</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="product_page_category_type" class="block text-sm font-medium text-gray-700 mb-1">نوع سلکتور</label>
                                <select name="product_page_category_type" id="product_page_category_type" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                    <option value="css" {{ old('product_page_category_type', 'css') == 'css' ? 'selected' : '' }}>CSS</option>
                                    <option value="xpath" {{ old('product_page_category_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
                                </select>
                            </div>

                            <div>
                                <label for="product_page_category_selector" class="block text-sm font-medium text-gray-700 mb-1">سلکتور</label>
                                <input type="text" name="product_page_category_selector" id="product_page_category_selector" value="{{ old('product_page_category_selector') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Price Selector -->
                    <div class="mb-6">
                        <h4 class="text-lg font-medium text-gray-800 mb-2">قیمت محصول</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="product_page_price_type" class="block text-sm font-medium text-gray-700 mb-1">نوع سلکتور</label>
                                <select name="product_page_price_type" id="product_page_price_type" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                    <option value="css" {{ old('product_page_price_type', 'css') == 'css' ? 'selected' : '' }}>CSS</option>
                                    <option value="xpath" {{ old('product_page_price_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
                                </select>
                            </div>

                            <div>
                                <label for="product_page_price_selector" class="block text-sm font-medium text-gray-700 mb-1">سلکتور</label>
                                <input type="text" name="product_page_price_selector" id="product_page_price_selector" value="{{ old('product_page_price_selector') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                <p class="mt-1 text-xs text-gray-500">چندین سلکتور را با کاما جدا کنید</p>
                            </div>
                        </div>
                    </div>

                    <!-- Availability Selector -->
                    <div class="mb-6">
                        <h4 class="text-lg font-medium text-gray-800 mb-2">موجودی محصول</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="product_page_availability_type" class="block text-sm font-medium text-gray-700 mb-1">نوع سلکتور</label>
                                <select name="product_page_availability_type" id="product_page_availability_type" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                    <option value="css" {{ old('product_page_availability_type', 'css') == 'css' ? 'selected' : '' }}>CSS</option>
                                    <option value="xpath" {{ old('product_page_availability_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
                                </select>
                            </div>

                            <div>
                                <label for="product_page_availability_selector" class="block text-sm font-medium text-gray-700 mb-1">سلکتور</label>
                                <input type="text" name="product_page_availability_selector" id="product_page_availability_selector" value="{{ old('product_page_availability_selector') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                <p class="mt-1 text-xs text-gray-500">چندین سلکتور را با کاما جدا کنید</p>
                            </div>

                            <div>
                                <label for="product_page_availability_keyword" class="block text-sm font-medium text-gray-700 mb-1">کلمه کلیدی موجودی</label>
                                <input type="text" name="product_page_availability_keyword" id="product_page_availability_keyword" value="{{ old('product_page_availability_keyword', 'ناموجود') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                            </div>

                            <div>
                                <label for="availability_mode" class="block text-sm font-medium text-gray-700 mb-1">حالت موجودی</label>
                                <select name="availability_mode" id="availability_mode" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                    <option value="selector" {{ old('availability_mode', 'selector') == 'selector' ? 'selected' : '' }}>سلکتور</option>
                                    <option value="keyword" {{ old('availability_mode') == 'keyword' ? 'selected' : '' }}>کلمه کلیدی</option>
                                </select>
                            </div>

                            <div>
                                <label for="availability_keywords_positive" class="block text-sm font-medium text-gray-700 mb-1">کلمات کلیدی موجود</label>
                                <input type="text" name="availability_keywords_positive" id="availability_keywords_positive" value="{{ old('availability_keywords_positive') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                <p class="mt-1 text-xs text-gray-500">با کاما جدا کنید</p>
                            </div>

                            <div>
                                <label for="availability_keywords_negative" class="block text-sm font-medium text-gray-700 mb-1">کلمات کلیدی ناموجود</label>
                                <input type="text" name="availability_keywords_negative" id="availability_keywords_negative" value="{{ old('availability_keywords_negative') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                <p class="mt-1 text-xs text-gray-500">با کاما جدا کنید</p>
                            </div>
                        </div>
                    </div>

                    <!-- Image Selector -->
                    <div class="mb-6">
                        <h4 class="text-lg font-medium text-gray-800 mb-2">تصویر محصول</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="image_method" class="block text-sm font-medium text-gray-700 mb-1">روش تصویر</label>
                                <select name="image_method" id="image_method" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                    <option value="product_page" {{ old('image_method', 'product_page') == 'product_page' ? 'selected' : '' }}>صفحه محصول</option>
                                    <option value="main_page" {{ old('image_method') == 'main_page' ? 'selected' : '' }}>صفحه اصلی</option>
                                </select>
                            </div>

                            <div>
                                <label for="product_page_image_type" class="block text-sm font-medium text-gray-700 mb-1">نوع سلکتور</label>
                                <select name="product_page_image_type" id="product_page_image_type" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                    <option value="css" {{ old('product_page_image_type', 'css') == 'css' ? 'selected' : '' }}>CSS</option>
                                    <option value="xpath" {{ old('product_page_image_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
                                </select>
                            </div>

                            <div>
                                <label for="product_page_image_selector" class="block text-sm font-medium text-gray-700 mb-1">سلکتور</label>
                                <input type="text" name="product_page_image_selector" id="product_page_image_selector" value="{{ old('product_page_image_selector') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                            </div>

                            <div>
                                <label for="product_page_image_attribute" class="block text-sm font-medium text-gray-700 mb-1">ویژگی</label>
                                <input type="text" name="product_page_image_attribute" id="product_page_image_attribute" value="{{ old('product_page_image_attribute', 'src') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Off Selector -->
                    <div class="mb-6">
                        <h4 class="text-lg font-medium text-gray-800 mb-2">تخفیف محصول</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="product_page_off_type" class="block text-sm font-medium text-gray-700 mb-1">نوع سلکتور</label>
                                <select name="product_page_off_type" id="product_page_off_type" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                    <option value="css" {{ old('product_page_off_type', 'css') == 'css' ? 'selected' : '' }}>CSS</option>
                                    <option value="xpath" {{ old('product_page_off_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
                                </select>
                            </div>

                            <div>
                                <label for="product_page_off_selector" class="block text-sm font-medium text-gray-700 mb-1">سلکتور</label>
                                <input type="text" name="product_page_off_selector" id="product_page_off_selector" value="{{ old('product_page_off_selector') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Guarantee Selector -->
                    <div class="mb-6">
                        <h4 class="text-lg font-medium text-gray-800 mb-2">گارانتی محصول</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="guarantee_method" class="block text-sm font-medium text-gray-700 mb-1">روش گارانتی</label>
                                <select name="guarantee_method" id="guarantee_method" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                    <option value="title" {{ old('guarantee_method', 'title') == 'title' ? 'selected' : '' }}>عنوان</option>
                                    <option value="selector" {{ old('guarantee_method') == 'selector' ? 'selected' : '' }}>سلکتور</option>
                                </select>
                            </div>

                            <div class="guarantee-selector-fields {{ old('guarantee_method') == 'selector' ? '' : 'hidden' }}">
                                <label for="product_page_guarantee_type" class="block text-sm font-medium text-gray-700 mb-1">نوع سلکتور</label>
                                <select name="product_page_guarantee_type" id="product_page_guarantee_type" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                    <option value="css" {{ old('product_page_guarantee_type', 'css') == 'css' ? 'selected' : '' }}>CSS</option>
                                    <option value="xpath" {{ old('product_page_guarantee_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
                                </select>
                            </div>

                            <div class="guarantee-selector-fields {{ old('guarantee_method') == 'selector' ? '' : 'hidden' }}">
                                <label for="product_page_guarantee_selector" class="block text-sm font-medium text-gray-700 mb-1">سلکتور</label>
                                <input type="text" name="product_page_guarantee_selector" id="product_page_guarantee_selector" value="{{ old('product_page_guarantee_selector') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                            </div>

                            <div>
                                <label for="guarantee_keywords" class="block text-sm font-medium text-gray-700 mb-1">کلمات کلیدی گارانتی</label>
                                <input type="text" name="guarantee_keywords" id="guarantee_keywords" value="{{ old('guarantee_keywords') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                <p class="mt-1 text-xs text-gray-500">با کاما جدا کنید</p>
                            </div>
                        </div>
                    </div>

                    <!-- Product ID Selector (from product page) -->
                    <div class="mb-6 product-id-product-page-fields {{ old('product_id_source') == 'main_page' ? 'hidden' : '' }}">
                        <h4 class="text-lg font-medium text-gray-800 mb-2">شناسه محصول (از صفحه محصول)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="product_page_product_id_type" class="block text-sm font-medium text-gray-700 mb-1">نوع سلکتور</label>
                                <select name="product_page_product_id_type" id="product_page_product_id_type" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                                    <option value="css" {{ old('product_page_product_id_type', 'css') == 'css' ? 'selected' : '' }}>CSS</option>
                                    <option value="xpath" {{ old('product_page_product_id_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
                                </select>
                            </div>

                            <div>
                                <label for="product_page_product_id_selector" class="block text-sm font-medium text-gray-700 mb-1">سلکتور</label>
                                <input type="text" name="product_page_product_id_selector" id="product_page_product_id_selector" value="{{ old('product_page_product_id_selector') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                            </div>

                            <div>
                                <label for="product_page_product_id_attribute" class="block text-sm font-medium text-gray-700 mb-1">ویژگی</label>
                                <input type="text" name="product_page_product_id_attribute" id="product_page_product_id_attribute" value="{{ old('product_page_product_id_attribute') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Price Keywords -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">کلمات کلیدی قیمت</h3>
                    <div>
                        <label for="price_keywords_unpriced" class="block text-sm font-medium text-gray-700 mb-1">کلمات کلیدی بدون قیمت</label>
                        <input type="text" name="price_keywords_unpriced" id="price_keywords_unpriced" value="{{ old('price_keywords_unpriced') }}" class="border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md p-2 text-sm">
                        <p class="mt-1 text-xs text-gray-500">با کاما جدا کنید</p>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-between items-center">
                    <a href="{{ route('configs.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-arrow-right ml-2"></i>
                        بازگشت
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-save ml-2"></i>
                        ذخیره پیکربندی
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Method selection handling
        const methodSelect = document.getElementById('method');
        const method23Fields = document.querySelectorAll('.method-2-3-fields');
        const method2Fields = document.querySelectorAll('.method-2-fields');
        const method3Fields = document.querySelectorAll('.method-3-fields');

        function updateMethodFields() {
            const methodValue = methodSelect.value;

            // Method 2 & 3 fields
            method23Fields.forEach(field => {
                field.classList.toggle('hidden', methodValue === '1');
            });

            // Method 2 specific fields
            method2Fields.forEach(field => {
                field.classList.toggle('hidden', methodValue !== '2');
            });

            // Method 3 specific fields
            method3Fields.forEach(field => {
                field.classList.toggle('hidden', methodValue !== '3');
            });
        }

        methodSelect.addEventListener('change', updateMethodFields);
        updateMethodFields(); // Initial state

        // Pagination method handling
        const paginationMethodSelect = document.getElementById('pagination_method');
        const nextButtonFields = document.querySelectorAll('.next-button-fields');
        const urlFields = document.querySelectorAll('.url-fields');

        function updatePaginationFields() {
            const paginationMethodValue = paginationMethodSelect.value;

            nextButtonFields.forEach(field => {
                field.classList.toggle('hidden', paginationMethodValue !== 'next_button');
            });

            urlFields.forEach(field => {
                field.classList.toggle('hidden', paginationMethodValue !== 'url');
            });
        }

        paginationMethodSelect.addEventListener('change', updatePaginationFields);
        updatePaginationFields(); // Initial state

        // Sample URL checkbox handling
        const sampleUrlCheckbox = document.getElementById('pagination_use_sample_url');
        const sampleUrlField = document.querySelector('.sample-url-field');

        function updateSampleUrlField() {
            sampleUrlField.classList.toggle('hidden', !sampleUrlCheckbox.checked);
        }

        sampleUrlCheckbox.addEventListener('change', updateSampleUrlField);
        updateSampleUrlField(); // Initial state

        // Product ID method handling
        const productIdMethodSelect = document.getElementById('product_id_method');
        const urlPatternFields = document.querySelectorAll('.url-pattern-fields');
        const scriptFields = document.querySelectorAll('.script-fields');

        function updateProductIdMethodFields() {
            const productIdMethodValue = productIdMethodSelect.value;

            urlPatternFields.forEach(field => {
                field.classList.toggle('hidden', productIdMethodValue !== 'url_pattern');
            });

            scriptFields.forEach(field => {
                field.classList.toggle('hidden', productIdMethodValue !== 'script');
            });
        }

        productIdMethodSelect.addEventListener('change', updateProductIdMethodFields);
        updateProductIdMethodFields(); // Initial state

        // Product ID source handling
        const productIdSourceSelect = document.getElementById('product_id_source');
        const productIdMainPageFields = document.querySelectorAll('.product-id-main-page-fields');
        const productIdProductPageFields = document.querySelectorAll('.product-id-product-page-fields');

        function updateProductIdSourceFields() {
            const productIdSourceValue = productIdSourceSelect.value;

            productIdMainPageFields.forEach(field => {
                field.classList.toggle('hidden', productIdSourceValue !== 'main_page');
            });

            productIdProductPageFields.forEach(field => {
                field.classList.toggle('hidden', productIdSourceValue !== 'product_page');
            });
        }

        productIdSourceSelect.addEventListener('change', updateProductIdSourceFields);
        updateProductIdSourceFields(); // Initial state

        // Guarantee method handling
        const guaranteeMethodSelect = document.getElementById('guarantee_method');
        const guaranteeSelectorFields = document.querySelectorAll('.guarantee-selector-fields');

        function updateGuaranteeMethodFields() {
            const guaranteeMethodValue = guaranteeMethodSelect.value;

            guaranteeSelectorFields.forEach(field => {
                field.classList.toggle('hidden', guaranteeMethodValue !== 'selector');
            });
        }

        guaranteeMethodSelect.addEventListener('change', updateGuaranteeMethodFields);
        updateGuaranteeMethodFields(); // Initial state
    });
</script>
</body>
</html>
