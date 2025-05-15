<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ایجاد کانفیگ متد ۱</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Vazirmatn', sans-serif;
            background-color: #f5f7fa;
        }
        .card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid #edf2f7;
            overflow: hidden;
        }
        .card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }
        .card-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #edf2f7;
            padding: 1rem 1.5rem;
        }
        .input-field {
            transition: all 0.2s ease;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
        }
        .input-field:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        .btn {
            transition: all 0.2s ease;
            font-weight: 500;
        }
        .btn:hover {
            transform: translateY(-1px);
        }
        .btn-primary {
            background-color: #3b82f6;
            color: white;
            border-radius: 0.5rem;
        }
        .btn-primary:hover {
            background-color: #2563eb;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        .btn-success {
            background-color: #10b981;
            color: white;
            border-radius: 0.5rem;
        }
        .btn-success:hover {
            background-color: #059669;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        .section-title {
            position: relative;
            padding-right: 1rem;
        }
        .section-title:before {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 1.25rem;
            background-color: #3b82f6;
            border-radius: 2px;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
<header class="bg-gradient-to-r from-blue-700 to-blue-500 text-white p-6 shadow-md">
    <div class="container mx-auto">
        <h1 class="text-3xl font-bold">ایجاد کانفیگ متد ۱</h1>
        <p class="text-blue-100 mt-2">تنظیمات پیکربندی سایت برای استخراج محصولات</p>
    </div>
</header>
<main class="container mx-auto p-6 flex-grow">
    @if ($errors->any())
        <div class="bg-red-50 border-r-4 border-red-500 text-red-800 px-6 py-4 rounded-lg mb-6 shadow-sm">
            <div class="flex items-center">
                <div class="py-1">
                    <svg class="w-6 h-6 ml-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <p class="font-bold">لطفاً خطاهای زیر را برطرف کنید:</p>
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif
    <form id="config-form" action="{{ route('configs.store') }}" method="POST" class="space-y-8" onsubmit="return validateForm()">
        @csrf
        <!-- Site Name -->
        <div class="card">
            <div class="card-header">
                <h2 class="section-title text-lg font-bold text-gray-800">اطلاعات پایه</h2>
            </div>
            <div class="p-6">
                <label class="block text-gray-700 font-medium mb-2">نام سایت</label>
                <input type="text" name="site_name" value="{{ old('site_name') }}" placeholder="مثال: imenstore"
                       class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none" required>
                <p class="text-gray-500 text-sm mt-2">نام سایت برای ذخیره فایل کانفیگ استفاده می‌شود.</p>
            </div>
        </div>

        <!-- URLs -->
        <div class="card">
            <div class="card-header">
                <h2 class="section-title text-lg font-bold text-gray-800">آدرس‌های سایت</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-gray-700 font-medium mb-3">لینک اصلی</label>
                        <div id="base-urls" class="space-y-3">
                            <input type="url" name="base_urls[]" value="{{ old('base_urls.0') }}" placeholder="https://example.com"
                                   class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none" required>
                        </div>
                        <button type="button" onclick="addField('base-urls')"
                                class="btn btn-primary px-4 py-2 mt-3 flex items-center">
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            افزودن آدرس
                        </button>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-3">لینک صفحه محصولات</label>
                        <div id="products-urls" class="space-y-3">
                            <input type="url" name="products_urls[]" value="{{ old('products_urls.0') }}" placeholder="https://example.com/shop"
                                   class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none" required>
                        </div>
                        <button type="button" onclick="addField('products-urls')"
                                class="btn btn-primary px-4 py-2 mt-3 flex items-center">
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            افزودن آدرس
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuration Options -->
        <div class="card">
            <div class="card-header">
                <h2 class="section-title text-lg font-bold text-gray-800">تنظیمات پیکربندی</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">حفظ فرمت قیمت</label>
                        <select name="keep_price_format" class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none">
                            <option value="0" {{ old('keep_price_format') === '0' ? 'selected' : '' }}>خیر</option>
                            <option value="1" {{ old('keep_price_format') === '1' ? 'selected' : '' }}>بله</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">روش شناسایی محصول</label>
                        <select name="product_id_method" class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none">
                            <option value="selector" {{ old('product_id_method') === 'selector' ? 'selected' : '' }}>انتخابگر</option>
                            <option value="url" {{ old('product_id_method') === 'url' ? 'selected' : '' }}>آدرس</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">منبع شناسایی محصول</label>
                        <select name="product_id_source" id="product_id_source" onchange="toggleProductIdFields()"
                                class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none">
                            <option value="product_page" {{ old('product_id_source') === 'product_page' ? 'selected' : '' }}>صفحه محصول</option>
                            <option value="url" {{ old('product_id_source') === 'url' ? 'selected' : '' }}>آدرس</option>
                            <option value="main_page" {{ old('product_id_source') === 'main_page' ? 'selected' : '' }}>صفحه اصلی</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">روش گارانتی</label>
                        <select name="guarantee_method" class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none">
                            <option value="selector" {{ old('guarantee_method') === 'selector' ? 'selected' : '' }}>انتخابگر</option>
                            <option value="title" {{ old('guarantee_method') === 'title' ? 'selected' : '' }}>عنوان</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Keywords -->
        <div class="card">
            <div class="card-header">
                <h2 class="section-title text-lg font-bold text-gray-800">کلمات کلیدی</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-blue-50 p-5 rounded-lg border border-blue-100">
                        <label class="block text-gray-700 font-medium mb-3">کلمات کلیدی گارانتی</label>
                        <div id="guarantee-keywords" class="space-y-3">
                            <input type="text" name="guarantee_keywords[]" value="{{ old('guarantee_keywords.0') }}"
                                   placeholder="مثال: گارانتی 12 ماهه" class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none" required>
                        </div>
                        <button type="button" onclick="addField('guarantee-keywords')"
                                class="btn btn-primary px-4 py-2 mt-3 flex items-center">
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            افزودن کلمه کلیدی
                        </button>
                    </div>
                    <div class="bg-green-50 p-5 rounded-lg border border-green-100">
                        <label class="block text-gray-700 font-medium mb-3">کلمات کلیدی موجودی (مثبت)</label>
                        <div id="availability-positive" class="space-y-3">
                            <input type="text" name="availability_keywords[positive][]" value="{{ old('availability_keywords.positive.0') }}"
                                   placeholder="مثال: موجود" class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none" required>
                        </div>
                        <button type="button" onclick="addField('availability-positive')"
                                class="btn btn-primary px-4 py-2 mt-3 flex items-center">
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            افزودن کلمه کلیدی
                        </button>
                    </div>
                    <div class="bg-red-50 p-5 rounded-lg border border-red-100">
                        <label class="block text-gray-700 font-medium mb-3">کلمات کلیدی موجودی (منفی)</label>
                        <div id="availability-negative" class="space-y-3">
                            <input type="text" name="availability_keywords[negative][]" value="{{ old('availability_keywords.negative.0') }}"
                                   placeholder="مثال: ناموجود" class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none" required>
                        </div>
                        <button type="button" onclick="addField('availability-negative')"
                                class="btn btn-primary px-4 py-2 mt-3 flex items-center">
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            افزودن کلمه کلیدی
                        </button>
                    </div>
                    <div class="bg-amber-50 p-5 rounded-lg border border-amber-100">
                        <label class="block text-gray-700 font-medium mb-3">کلمات کلیدی قیمت (بدون قیمت)</label>
                        <div id="price-unpriced" class="space-y-3">
                            <input type="text" name="price_keywords[unpriced][]" value="{{ old('price_keywords.unpriced.0') }}"
                                   placeholder="مثال: تماس بگیرید" class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none" required>
                        </div>
                        <button type="button" onclick="addField('price-unpriced')"
                                class="btn btn-primary px-4 py-2 mt-3 flex items-center">
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            افزودن کلمه کلیدی
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selectors -->
        <div class="card">
            <div class="card-header">
                <h2 class="section-title text-lg font-bold text-gray-800">انتخابگرها</h2>
            </div>
            <div class="p-6">
                <div class="mb-8">
                    <h3 class="text-lg font-bold mb-4 text-blue-700 border-b pb-2">صفحه اصلی</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">لینک‌های محصول - انتخابگر</label>
                            <input type="text" name="selectors[main_page][product_links][selector]"
                                   value="{{ old('selectors.main_page.product_links.selector') }}"
                                   placeholder="مثال: div.product-grid-item > div > div > a"
                                   class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">لینک‌های محصول - ویژگی</label>
                            <input type="text" name="selectors[main_page][product_links][attribute]"
                                   value="{{ old('selectors.main_page.product_links.attribute') }}"
                                   placeholder="مثال: href"
                                   class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none" required>
                        </div>
                    </div>
                    <div id="main-page-product-id" class="hidden bg-yellow-50 p-5 rounded-lg border border-yellow-100 mb-6">
                        <h3 class="text-lg font-bold mb-3 text-yellow-700">شناسه محصول</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">انتخابگر</label>
                                <input type="text" name="selectors[main_page][product_id][selector]"
                                       value="{{ old('selectors.main_page.product_id.selector') }}"
                                       placeholder="مثال: div.product-grid-item > div > div > div > div > div > a"
                                       class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium mb-2">ویژگی</label>
                                <input type="text" name="selectors[main_page][product_id][attribute]"
                                       value="{{ old('selectors.main_page.product_id.attribute') }}"
                                       placeholder="مثال: data-product_id"
                                       class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none">
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-bold mb-4 text-blue-700 border-b pb-2">صفحه محصول</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        @foreach (['title' => 'عنوان', 'category' => 'دسته‌بندی', 'availability' => 'موجودی', 'price' => 'قیمت', 'image' => 'تصویر', 'off' => 'تخفیف', 'guarantee' => 'گارانتی', 'product_id' => 'شناسه محصول'] as $field => $label)
                            <div class="bg-gray-50 p-5 rounded-lg border border-gray-100 mb-4">
                                <h4 class="text-md font-bold mb-3 text-gray-700">{{ $label }}</h4>
                                <div class="mb-4">
                                    <label class="block text-gray-700 font-medium mb-2">انتخابگر</label>
                                    <input type="text" name="selectors[product_page][{{ $field }}][selector]"
                                           value="{{ old('selectors.product_page.' . $field . '.selector') }}"
                                           placeholder="مثال: .product_title"
                                           class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none" required>
                                </div>
                                @if (in_array($field, ['image', 'product_id']))
                                    <div class="mb-2">
                                        <label class="block text-gray-700 font-medium mb-2">ویژگی</label>
                                        <input type="text" name="selectors[product_page][{{ $field }}][attribute]"
                                               value="{{ old('selectors.product_page.' . $field . '.attribute') }}"
                                               placeholder="مثال: src"
                                               class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none">
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="card">
            <div class="card-header">
                <h2 class="section-title text-lg font-bold text-gray-800">صفحه‌بندی</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">نوع صفحه‌بندی</label>
                        <select name="pagination[type]" id="pagination_type" class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none">
                            <option value="path" {{ old('pagination.type') === 'path' ? 'selected' : '' }}>مسیر</option>
                            <option value="query" {{ old('pagination.type') === 'query' ? 'selected' : '' }}>پرس‌وجو</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">پارامتر</label>
                        <input type="text" name="pagination[parameter]" value="{{ old('pagination.parameter') }}"
                               placeholder="مثال: page" class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">جداکننده</label>
                        <input type="text" name="pagination[separator]" value="{{ old('pagination.separator') }}"
                               placeholder="مثال: /" class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">پسوند</label>
                        <input type="text" name="pagination[suffix]" value="{{ old('pagination.suffix') }}"
                               placeholder="مثال: .html" class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">حداکثر تعداد صفحات</label>
                        <input type="number" name="pagination[max_pages]" value="{{ old('pagination.max_pages') }}"
                               placeholder="مثال: 35" class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">استفاده از آدرس نمونه</label>
                        <select name="pagination[use_sample_url]" id="use_sample_url" onchange="toggleSampleUrl()"
                                class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none">
                            <option value="0" {{ old('pagination.use_sample_url') === '0' ? 'selected' : '' }}>خیر</option>
                            <option value="1" {{ old('pagination.use_sample_url') === '1' ? 'selected' : '' }}>بله</option>
                        </select>
                    </div>
                    <div class="hidden" id="sample_url">
                        <label class="block text-gray-700 font-medium mb-2">آدرس نمونه</label>
                        <input type="url" name="pagination[sample_url]" id="sample_url_input" value="{{ old('pagination.sample_url') }}"
                               placeholder="مثال: https://example.com/shop/page/2/" class="input-field w-full px-4 py-3 text-gray-700 focus:outline-none">
                        <p id="sample_url_error" class="text-red-500 text-sm mt-1 hidden">لطفاً یک آدرس معتبر (مانند https://example.com) وارد کنید.</p>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success px-8 py-3 text-lg font-medium flex items-center justify-center mx-auto">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            ذخیره کانفیگ
        </button>
    </form>
</main>
<footer class="bg-gray-800 text-white text-center p-6 mt-10">
    <div class="container mx-auto">
        <p>© ۱۴۰۴ مدیریت کانفیگ</p>
        <p class="text-gray-400 text-sm mt-2">سیستم استخراج اطلاعات محصولات</p>
    </div>
</footer>
<script>
    function addField(containerId) {
        const container = document.getElementById(containerId);
        const input = document.createElement('input');
        input.type = containerId.includes('url') ? 'url' : 'text';
        input.name = container.querySelector('input').name;
        input.className = 'input-field w-full px-4 py-3 text-gray-700 focus:outline-none';
        input.required = true;
        input.placeholder = container.querySelector('input').placeholder;
        container.appendChild(input);
    }

    function toggleProductIdFields() {
        const source = document.getElementById('product_id_source').value;
        const productIdSection = document.getElementById('main-page-product-id');
        productIdSection.classList.toggle('hidden', source !== 'main_page');
    }

    function toggleSampleUrl() {
        const useSampleUrl = document.getElementById('use_sample_url').value;
        const sampleUrlField = document.getElementById('sample_url');
        const sampleUrlInput = document.getElementById('sample_url_input');
        sampleUrlField.classList.toggle('hidden', useSampleUrl !== '1');
        if (useSampleUrl !== '1') {
            sampleUrlInput.value = '';
            sampleUrlInput.removeAttribute('required');
        } else {
            sampleUrlInput.setAttribute('required', 'required');
        }
    }

    function validateForm() {
        const useSampleUrl = document.getElementById('use_sample_url').value;
        const sampleUrlInput = document.getElementById('sample_url_input');
        const sampleUrlError = document.getElementById('sample_url_error');

        if (useSampleUrl === '1') {
            const urlPattern = /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/;
            if (!urlPattern.test(sampleUrlInput.value)) {
                sampleUrlError.classList.remove('hidden');
                sampleUrlInput.focus();
                return false;
            } else {
                sampleUrlError.classList.add('hidden');
            }
        }
        return true;
    }

    toggleProductIdFields();
    toggleSampleUrl();
</script>
</body>
</html>
