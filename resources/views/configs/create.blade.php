<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ایجاد تنظیم جدید</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">ایجاد تنظیم جدید</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('configs.store') }}" method="POST">
        @csrf

        <!-- نام تنظیم -->
        <div class="mb-3">
            <label for="name" class="form-label">نام تنظیم</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
        </div>

        <!-- روش -->
        <div class="mb-3">
            <label for="method" class="form-label">روش</label>
            <select name="method" id="method" class="form-select" required>
                <option value="1" {{ old('method') == 1 ? 'selected' : '' }}>روش 1</option>
                <option value="2" {{ old('method') == 2 ? 'selected' : '' }}>روش 2</option>
                <option value="3" {{ old('method') == 3 ? 'selected' : '' }}>روش 3</option>
            </select>
        </div>

        <!-- آدرس‌های پایه -->
        <div class="mb-3">
            <label for="base_urls" class="form-label">آدرس‌های پایه (جدا شده با کاما)</label>
            <textarea name="base_urls" id="base_urls" class="form-control">{{ old('base_urls') }}</textarea>
        </div>

        <!-- آدرس‌های محصولات -->
        <div class="mb-3">
            <label for="products_urls" class="form-label">آدرس‌های محصولات (جدا شده با کاما)</label>
            <textarea name="products_urls" id="products_urls" class="form-control">{{ old('products_urls') }}</textarea>
        </div>

        <!-- تنظیمات عمومی -->
        <div class="mb-3">
            <label for="request_delay_min" class="form-label">حداقل تأخیر درخواست (میلی‌ثانیه)</label>
            <input type="number" name="request_delay_min" id="request_delay_min" class="form-control" value="{{ old('request_delay_min', 3000) }}">
        </div>

        <div class="mb-3">
            <label for="request_delay_max" class="form-label">حداکثر تأخیر درخواست (میلی‌ثانیه)</label>
            <input type="number" name="request_delay_max" id="request_delay_max" class="form-control" value="{{ old('request_delay_max', 5000) }}">
        </div>

        <div class="mb-3">
            <label for="timeout" class="form-label">مهلت زمانی (ثانیه)</label>
            <input type="number" name="timeout" id="timeout" class="form-control" value="{{ old('timeout', 120) }}">
        </div>

        <div class="mb-3">
            <label for="max_retries" class="form-label">حداکثر تلاش مجدد</label>
            <input type="number" name="max_retries" id="max_retries" class="form-control" value="{{ old('max_retries', 2) }}">
        </div>

        <div class="mb-3">
            <label for="concurrency" class="form-label">همزمانی</label>
            <input type="number" name="concurrency" id="concurrency" class="form-control" value="{{ old('concurrency', 1) }}">
        </div>

        <div class="mb-3">
            <label for="batch_size" class="form-label">اندازه دسته</label>
            <input type="number" name="batch_size" id="batch_size" class="form-control" value="{{ old('batch_size', 1) }}">
        </div>

        <div class="mb-3">
            <label for="request_delay" class="form-label">تأخیر درخواست (میلی‌ثانیه)</label>
            <input type="number" name="request_delay" id="request_delay" class="form-control" value="{{ old('request_delay', 3000) }}">
        </div>

        <div class="mb-3">
            <label for="user_agent" class="form-label">User Agent</label>
            <input type="text" name="user_agent" id="user_agent" class="form-control" value="{{ old('user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124') }}">
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="verify_ssl" id="verify_ssl" class="form-check-input" {{ old('verify_ssl') ? 'checked' : '' }}>
            <label for="verify_ssl" class="form-check-label">تأیید SSL</label>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="keep_price_format" id="keep_price_format" class="form-check-input" {{ old('keep_price_format') ? 'checked' : '' }}>
            <label for="keep_price_format" class="form-check-label">حفظ فرمت قیمت</label>
        </div>

        <!-- تنظیمات تصویر -->
        <div class="mb-3">
            <label for="image_method" class="form-label">روش تصویر</label>
            <select name="image_method" id="image_method" class="form-select">
                <option value="product_page" {{ old('image_method') == 'product_page' ? 'selected' : '' }}>صفحه محصول</option>
                <option value="other" {{ old('image_method') == 'other' ? 'selected' : '' }}>سایر</option>
            </select>
        </div>

        <!-- تنظیمات موجودی -->
        <div class="mb-3">
            <label for="availability_mode" class="form-label">حالت موجودی</label>
            <select name="availability_mode" id="availability_mode" class="form-select">
                <option value="selector" {{ old('availability_mode') == 'selector' ? 'selected' : '' }}>انتخاب‌گر</option>
                <option value="keyword" {{ old('availability_mode') == 'keyword' ? 'selected' : '' }}>کلمه کلیدی</option>
            </select>
        </div>

        <!-- تنظیمات شناسه محصول -->
        <div class="mb-3">
            <label for="product_id_method" class="form-label">روش شناسه محصول</label>
            <select name="product_id_method" id="product_id_method" class="form-select">
                <option value="selector" {{ old('product_id_method') == 'selector' ? 'selected' : '' }}>انتخاب‌گر</option>
                <option value="url" {{ old('product_id_method') == 'url' ? 'selected' : '' }}>URL</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="product_id_source" class="form-label">منبع شناسه محصول</label>
            <select name="product_id_source" id="product_id_source" class="form-select">
                <option value="product_page" {{ old('product_id_source') == 'product_page' ? 'selected' : '' }}>صفحه محصول</option>
                <option value="main_page" {{ old('product_id_source') == 'main_page' ? 'selected' : '' }}>صفحه اصلی</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="product_id_url_pattern" class="form-label">الگوی URL شناسه محصول</label>
            <input type="text" name="product_id_url_pattern" id="product_id_url_pattern" class="form-control" value="{{ old('product_id_url_pattern') }}">
        </div>

        <!-- تنظیمات گارانتی -->
        <div class="mb-3">
            <label for="guarantee_method" class="form-label">روش گارانتی</label>
            <select name="guarantee_method" id="guarantee_method" class="form-select">
                <option value="title" {{ old('guarantee_method') == 'title' ? 'selected' : '' }}>عنوان</option>
                <option value="selector" {{ old('guarantee_method') == 'selector' ? 'selected' : '' }}>انتخاب‌گر</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="guarantee_keywords" class="form-label">کلمات کلیدی گارانتی (جدا شده با کاما)</label>
            <textarea name="guarantee_keywords" id="guarantee_keywords" class="form-control">{{ old('guarantee_keywords') }}</textarea>
        </div>

        <!-- انتخاب‌گرهای صفحه اصلی -->
        <h3>انتخاب‌گرهای صفحه اصلی</h3>
        <div class="mb-3">
            <label for="main_page_product_links_type" class="form-label">نوع انتخاب‌گر لینک محصولات</label>
            <select name="main_page_product_links_type" id="main_page_product_links_type" class="form-select">
                <option value="css" {{ old('main_page_product_links_type') == 'css' ? 'selected' : '' }}>CSS</option>
                <option value="xpath" {{ old('main_page_product_links_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="main_page_product_links_selector" class="form-label">انتخاب‌گر لینک محصولات</label>
            <input type="text" name="main_page_product_links_selector" id="main_page_product_links_selector" class="form-control" value="{{ old('main_page_product_links_selector') }}">
        </div>

        <div class="mb-3">
            <label for="main_page_product_links_attribute" class="form-label">ویژگی لینک محصولات</label>
            <input type="text" name="main_page_product_links_attribute" id="main_page_product_links_attribute" class="form-control" value="{{ old('main_page_product_links_attribute', 'href') }}">
        </div>

        <!-- انتخاب‌گر شناسه محصول در صفحه اصلی (اگر product_id_source == main_page) -->
        <div id="main_page_product_id_fields" style="display: none;">
            <div class="mb-3">
                <label for="main_page_product_id_type" class="form-label">نوع انتخاب‌گر شناسه محصول</label>
                <select name="main_page_product_id_type" id="main_page_product_id_type" class="form-select">
                    <option value="css" {{ old('main_page_product_id_type') == 'css' ? 'selected' : '' }}>CSS</option>
                    <option value="xpath" {{ old('main_page_product_id_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="main_page_product_id_selector" class="form-label">انتخاب‌گر شناسه محصول</label>
                <input type="text" name="main_page_product_id_selector" id="main_page_product_id_selector" class="form-control" value="{{ old('main_page_product_id_selector') }}">
            </div>

            <div class="mb-3">
                <label for="main_page_product_id_attribute" class="form-label">ویژگی شناسه محصول</label>
                <input type="text" name="main_page_product_id_attribute" id="main_page_product_id_attribute" class="form-control" value="{{ old('main_page_product_id_attribute') }}">
            </div>
        </div>

        <!-- انتخاب‌گرهای صفحه محصول -->
        <h3>انتخاب‌گرهای صفحه محصول</h3>
        <div class="mb-3">
            <label for="product_page_title_type" class="form-label">نوع انتخاب‌گر عنوان</label>
            <select name="product_page_title_type" id="product_page_title_type" class="form-select">
                <option value="css" {{ old('product_page_title_type') == 'css' ? 'selected' : '' }}>CSS</option>
                <option value="xpath" {{ old('product_page_title_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="product_page_title_selector" class="form-label">انتخاب‌گر عنوان</label>
            <input type="text" name="product_page_title_selector" id="product_page_title_selector" class="form-control" value="{{ old('product_page_title_selector') }}">
        </div>

        <div class="mb-3">
            <label for="product_page_category_type" class="form-label">نوع انتخاب‌گر دسته‌بندی</label>
            <select name="product_page_category_type" id="product_page_category_type" class="form-select">
                <option value="css" {{ old('product_page_category_type') == 'css' ? 'selected' : '' }}>CSS</option>
                <option value="xpath" {{ old('product_page_category_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="product_page_category_selector" class="form-label">انتخاب‌گر دسته‌بندی</label>
            <input type="text" name="product_page_category_selector" id="product_page_category_selector" class="form-control" value="{{ old('product_page_category_selector') }}">
        </div>

        <div class="mb-3">
            <label for="product_page_availability_type" class="form-label">نوع انتخاب‌گر موجودی</label>
            <select name="product_page_availability_type" id="product_page_availability_type" class="form-select">
                <option value="css" {{ old('product_page_availability_type') == 'css' ? 'selected' : '' }}>CSS</option>
                <option value="xpath" {{ old('product_page_availability_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="product_page_availability_selector" class="form-label">انتخاب‌گر موجودی (جدا شده با کاما)</label>
            <textarea name="product_page_availability_selector" id="product_page_availability_selector" class="form-control">{{ old('product_page_availability_selector') }}</textarea>
        </div>

        <div class="mb-3">
            <label for="product_page_availability_keyword" class="form-label">کلمه کلیدی موجودی</label>
            <input type="text" name="product_page_availability_keyword" id="product_page_availability_keyword" class="form-control" value="{{ old('product_page_availability_keyword', 'ناموجود') }}">
        </div>

        <div class="mb-3">
            <label for="product_page_price_type" class="form-label">نوع انتخاب‌گر قیمت</label>
            <select name="product_page_price_type" id="product_page_price_type" class="form-select">
                <option value="css" {{ old('product_page_price_type') == 'css' ? 'selected' : '' }}>CSS</option>
                <option value="xpath" {{ old('product_page_price_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="product_page_price_selector" class="form-label">انتخاب‌گر قیمت (جدا شده با کاما)</label>
            <textarea name="product_page_price_selector" id="product_page_price_selector" class="form-control">{{ old('product_page_price_selector') }}</textarea>
        </div>

        <div class="mb-3">
            <label for="product_page_image_type" class="form-label">نوع انتخاب‌گر تصویر</label>
            <select name="product_page_image_type" id="product_page_image_type" class="form-select">
                <option value="css" {{ old('product_page_image_type') == 'css' ? 'selected' : '' }}>CSS</option>
                <option value="xpath" {{ old('product_page_image_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="product_page_image_selector" class="form-label">انتخاب‌گر تصویر</label>
            <input type="text" name="product_page_image_selector" id="product_page_image_selector" class="form-control" value="{{ old('product_page_image_selector') }}">
        </div>

        <div class="mb-3">
            <label for="product_page_image_attribute" class="form-label">ویژگی تصویر</label>
            <input type="text" name="product_page_image_attribute" id="product_page_image_attribute" class="form-control" value="{{ old('product_page_image_attribute', 'src') }}">
        </div>

        <div class="mb-3">
            <label for="product_page_off_type" class="form-label">نوع انتخاب‌گر تخفیف</label>
            <select name="product_page_off_type" id="product_page_off_type" class="form-select">
                <option value="css" {{ old('product_page_off_type') == 'css' ? 'selected' : '' }}>CSS</option>
                <option value="xpath" {{ old('product_page_off_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="product_page_off_selector" class="form-label">انتخاب‌گر تخفیف</label>
            <input type="text" name="product_page_off_selector" id="product_page_off_selector" class="form-control" value="{{ old('product_page_off_selector') }}">
        </div>

        <div class="mb-3">
            <label for="product_page_guarantee_type" class="form-label">نوع انتخاب‌گر گارانتی</label>
            <select name="product_page_guarantee_type" id="product_page_guarantee_type" class="form-select">
                <option value="css" {{ old('product_page_guarantee_type') == 'css' ? 'selected' : '' }}>CSS</option>
                <option value="xpath" {{ old('product_page_guarantee_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="product_page_guarantee_selector" class="form-label">انتخاب‌گر گارانتی</label>
            <input type="text" name="product_page_guarantee_selector" id="product_page_guarantee_selector" class="form-control" value="{{ old('product_page_guarantee_selector') }}">
        </div>

        <div class="mb-3">
            <label for="product_page_product_id_type" class="form-label">نوع انتخاب‌گر شناسه محصول</label>
            <select name="product_page_product_id_type" id="product_page_product_id_type" class="form-select">
                <option value="css" {{ old('product_page_product_id_type') == 'css' ? 'selected' : '' }}>CSS</option>
                <option value="xpath" {{ old('product_page_product_id_type') == 'xpath' ? 'selected' : '' }}>XPath</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="product_page_product_id_selector" class="form-label">انتخاب‌گر شناسه محصول</label>
            <input type="text" name="product_page_product_id_selector" id="product_page_product_id_selector" class="form-control" value="{{ old('product_page_product_id_selector') }}">
        </div>

        <div class="mb-3">
            <label for="product_page_product_id_attribute" class="form-label">ویژگی شناسه محصول</label>
            <input type="text" name="product_page_product_id_attribute" id="product_page_product_id_attribute" class="form-control" value="{{ old('product_page_product_id_attribute') }}">
        </div>

        <!-- کلمات کلیدی موجودی -->
        <div class="mb-3">
            <label for="availability_keywords_positive" class="form-label">کلمات کلیدی مثبت موجودی (جدا شده با کاما)</label>
            <textarea name="availability_keywords_positive" id="availability_keywords_positive" class="form-control">{{ old('availability_keywords_positive') }}</textarea>
        </div>

        <div class="mb-3">
            <label for="availability_keywords_negative" class="form-label">کلمات کلیدی منفی موجودی (جدا شده با کاما)</label>
            <textarea name="availability_keywords_negative" id="availability_keywords_negative" class="form-control">{{ old('availability_keywords_negative') }}</textarea>
        </div>

        <!-- کلمات کلیدی قیمت -->
        <div class="mb-3">
            <label for="price_keywords_unpriced" class="form-label">کلمات کلیدی بدون قیمت (جدا شده با کاما)</label>
            <textarea name="price_keywords_unpriced" id="price_keywords_unpriced" class="form-control">{{ old('price_keywords_unpriced') }}</textarea>
        </div>

        <!-- الگوهای اسکریپت بازگشتی شناسه محصول -->
        <div class="mb-3">
            <label for="product_id_fallback_script_patterns" class="form-label">الگوهای اسکریپت بازگشتی شناسه محصول (جدا شده با کاما)</label>
            <textarea name="product_id_fallback_script_patterns" id="product_id_fallback_script_patterns" class="form-control">{{ old('product_id_fallback_script_patterns') }}</textarea>
        </div>

        <!-- تنظیمات روش 2 -->
        <div id="method2_fields" style="display: none;">
            <h3>تنظیمات روش 2</h3>
            <div class="mb-3 form-check">
                <input type="checkbox" name="share_product_id_from_method_2" id="share_product_id_from_method_2" class="form-check-input" {{ old('share_product_id_from_method_2') ? 'checked' : '' }}>
                <label for="share_product_id_from_method_2" class="form-check-label">اشتراک‌گذاری شناسه محصول</label>
            </div>

            <div class="mb-3">
                <label for="scrool" class="form-label">اسکرول</label>
                <input type="number" name="scrool" id="scrool" class="form-control" value="{{ old('scrool', 10) }}">
            </div>

            <div class="mb-3">
                <label for="container" class="form-label">کانتینر</label>
                <input type="text" name="container" id="container" class="form-control" value="{{ old('container') }}">
            </div>

            <!-- تنظیمات ناوبری -->
            <h4>تنظیمات ناوبری</h4>
            <div class="mb-3">
                <label for="pagination_method" class="form-label">روش صفحه‌بندی</label>
                <select name="pagination_method" id="pagination_method" class="form-select">
                    <option value="next_button" {{ old('pagination_method') == 'next_button' ? 'selected' : '' }}>دکمه بعدی</option>
                    <option value="url" {{ old('pagination_method') == 'url' ? 'selected' : '' }}>URL</option>
                </select>
            </div>

            <div id="pagination_next_button_fields" style="display: none;">
                <div class="mb-3">
                    <label for="pagination_next_button_selector" class="form-label">انتخاب‌گر دکمه بعدی</label>
                    <input type="text" name="pagination_next_button_selector" id="pagination_next_button_selector" class="form-control" value="{{ old('pagination_next_button_selector') }}">
                </div>

                <div class="mb-3">
                    <label for="pagination_max_pages" class="form-label">حداکثر صفحات</label>
                    <input type="number" name="pagination_max_pages" id="pagination_max_pages" class="form-control" value="{{ old('pagination_max_pages', 3) }}">
                </div>
            </div>

            <div id="pagination_url_fields" style="display: none;">
                <div class="mb-3">
                    <label for="pagination_url_type" class="form-label">نوع URL</label>
                    <select name="pagination_url_type" id="pagination_url_type" class="form-select">
                        <option value="query" {{ old('pagination_url_type') == 'query' ? 'selected' : '' }}>پارامتر کوئری</option>
                        <option value="path" {{ old('pagination_url_type') == 'path' ? 'selected' : '' }}>مسیر</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="pagination_url_parameter" class="form-label">پارامتر URL</label>
                    <input type="text" name="pagination_url_parameter" id="pagination_url_parameter" class="form-control" value="{{ old('pagination_url_parameter', 'page') }}">
                </div>

                <div class="mb-3">
                    <label for="pagination_url_separator" class="form-label">جداکننده URL</label>
                    <input type="text" name="pagination_url_separator" id="pagination_url_separator" class="form-control" value="{{ old('pagination_url_separator', '=') }}">
                </div>

                <div class="mb-3">
                    <label for="pagination_url_suffix" class="form-label">پسوند URL</label>
                    <input type="text" name="pagination_url_suffix" id="pagination_url_suffix" class="form-control" value="{{ old('pagination_url_suffix') }}">
                </div>

                <div class="mb-3">
                    <label for="pagination_max_pages" class="form-label">حداکثر صفحات</label>
                    <input type="number" name="pagination_max_pages" id="pagination_max_pages" class="form-control" value="{{ old('pagination_max_pages', 3) }}">
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" name="pagination_use_sample_url" id="pagination_use_sample_url" class="form-check-input" {{ old('pagination_use_sample_url') ? 'checked' : '' }}>
                    <label for="pagination_use_sample_url" class="form-check-label">استفاده از URL نمونه</label>
                </div>

                <div class="mb-3">
                    <label for="pagination_sample_url" class="form-label">URL نمونه</label>
                    <input type="text" name="pagination_sample_url" id="pagination_sample_url" class="form-control" value="{{ old('pagination_sample_url') }}">
                </div>
            </div>

            <div class="mb-3">
                <label for="scroll_delay" class="form-label">تأخیر اسکرول (میلی‌ثانیه)</label>
                <input type="number" name="scroll_delay" id="scroll_delay" class="form-control" value="{{ old('scroll_delay', 5000) }}">
            </div>
        </div>

        <!-- تنظیمات روش 3 -->
        <div id="method3_fields" style="display: none;">
            <h3>تنظیمات روش 3</h3>
            <div class="mb-3">
                <label for="scrool" class="form-label">اسکرول</label>
                <input type="number" name="scrool" id="scrool" class="form-control" value="{{ old('scrool', 10) }}">
            </div>

            <div class="mb-3">
                <label for="container" class="form-label">کانتینر</label>
                <input type="text" name="container" id="container" class="form-control" value="{{ old('container') }}">
            </div>

            <div class="mb-3">
                <label for="basescroll" class="form-label">اسکرول پایه</label>
                <input type="number" name="basescroll" id="basescroll" class="form-control" value="{{ old('basescroll', 10) }}">
            </div>

            <!-- تنظیمات ناوبری -->
            <h4>تنظیمات ناوبری</h4>
            <div class="mb-3">
                <label for="pagination_method" class="form-label">روش صفحه‌بندی</label>
                <select name="pagination_method" id="pagination_method_method3" class="form-select">
                    <option value="next_button" {{ old('pagination_method') == 'next_button' ? 'selected' : '' }}>دکمه بعدی</option>
                    <option value="url" {{ old('pagination_method') == 'url' ? 'selected' : '' }}>URL</option>
                </select>
            </div>

            <div id="pagination_next_button_fields_method3" style="display: none;">
                <div class="mb-3">
                    <label for="pagination_next_button_selector" class="form-label">انتخاب‌گر دکمه بعدی</label>
                    <input type="text" name="pagination_next_button_selector" id="pagination_next_button_selector_method3" class="form-control" value="{{ old('pagination_next_button_selector') }}">
                </div>
            </div>

            <div id="pagination_url_fields_method3" style="display: none;">
                <div class="mb-3">
                    <label for="pagination_url_type" class="form-label">نوع URL</label>
                    <select name="pagination_url_type" id="pagination_url_type_method3" class="form-select">
                        <option value="query" {{ old('pagination_url_type') == 'query' ? 'selected' : '' }}>پارامتر کوئری</option>
                        <option value="path" {{ old('pagination_url_type') == 'path' ? 'selected' : '' }}>مسیر</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="pagination_url_parameter" class="form-label">پارامتر URL</label>
                    <input type="text" name="pagination_url_parameter" id="pagination_url_parameter_method3" class="form-control" value="{{ old('pagination_url_parameter', 'page') }}">
                </div>

                <div class="mb-3">
                    <label for="pagination_url_separator" class="form-label">جداکننده URL</label>
                    <input type="text" name="pagination_url_separator" id="pagination_url_separator_method3" class="form-control" value="{{ old('pagination_url_separator', '=') }}">
                </div>

                <div class="mb-3">
                    <label for="pagination_url_suffix" class="form-label">پسوند URL</label>
                    <input type="text" name="pagination_url_suffix" id="pagination_url_suffix_method3" class="form-control" value="{{ old('pagination_url_suffix') }}">
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" name="pagination_use_sample_url" id="pagination_use_sample_url_method3" class="form-check-input" {{ old('pagination_use_sample_url') ? 'checked' : '' }}>
                    <label for="pagination_use_sample_url" class="form-check-label">استفاده از URL نمونه</label>
                </div>

                <div class="mb-3">
                    <label for="pagination_sample_url" class="form-label">URL نمونه</label>
                    <input type="text" name="pagination_sample_url" id="pagination_sample_url_method3" class="form-control" value="{{ old('pagination_sample_url') }}">
                </div>
            </div>

            <div class="mb-3">
                <label for="pagination_max_pages" class="form-label">حداکثر تکرار</label>
                <input type="number" name="pagination_max_pages" id="pagination_max_pages_method3" class="form-control" value="{{ old('pagination_max_pages', 3) }}">
            </div>

            <div class="mb-3">
                <label for="scroll_delay" class="form-label">تأخیر اسکرول (میلی‌ثانیه)</label>
                <input type="number" name="scroll_delay" id="scroll_delay_method3" class="form-control" value="{{ old('scroll_delay', 5000) }}">
            </div>
        </div>

        <!-- دکمه‌ها -->
        <div class="mb-3">
            <button type="submit" class="btn btn-primary">ایجاد</button>
            <a href="{{ route('configs.index') }}" class="btn btn-secondary">لغو</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // نمایش/مخفی کردن فیلدهای روش 2 و 3
    document.getElementById('method').addEventListener('change', function() {
        const method = this.value;
        document.getElementById('method2_fields').style.display = method == '2' ? 'block' : 'none';
        document.getElementById('method3_fields').style.display = method == '3' ? 'block' : 'none';
    });

    // نمایش/مخفی کردن فیلدهای شناسه محصول صفحه اصلی
    document.getElementById('product_id_source').addEventListener('change', function() {
        const source = this.value;
        document.getElementById('main_page_product_id_fields').style.display = source == 'main_page' ? 'block' : 'none';
    });

    // نمایش/مخفی کردن فیلدهای صفحه‌بندی روش 2
    document.getElementById('pagination_method').addEventListener('change', function() {
        const method = this.value;
        document.getElementById('pagination_next_button_fields').style.display = method == 'next_button' ? 'block' : 'none';
        document.getElementById('pagination_url_fields').style.display = method == 'url' ? 'block' : 'none';
    });

    // نمایش/مخفی کردن فیلدهای صفحه‌بندی روش 3
    document.getElementById('pagination_method_method3').addEventListener('change', function() {
        const method = this.value;
        document.getElementById('pagination_next_button_fields_method3').style.display = method == 'next_button' ? 'block' : 'none';
        document.getElementById('pagination_url_fields_method3').style.display = method == 'url' ? 'block' : 'none';
    });
</script>
</body>
</html>
