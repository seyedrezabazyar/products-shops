<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ویرایش کانفیگ {{ $filename }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
<header class="bg-blue-600 text-white p-4">
    <h1 class="text-2xl font-bold">ویرایش کانفیگ {{ $filename }}</h1>
</header>
<main class="container mx-auto p-6 flex-grow">
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form id="config-form" action="{{ route('configs.update', $filename) }}" method="POST" class="bg-white shadow-md rounded-lg p-6" onsubmit="return validateForm()">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">نام سایت</label>
            <input type="text" name="site_name" value="{{ old('site_name', $filename) }}" placeholder="مثال: imenstore" class="w-full px-3 py-2 border rounded" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">آدرس‌های پایه</label>
            <div id="base-urls">
                @foreach ($content['base_urls'] as $url)
                    <input type="url" name="base_urls[]" value="{{ $url }}" placeholder="https://example.com" class="w-full px-3 py-2 border rounded mb-2" required>
                @endforeach
            </div>
            <button type="button" onclick="addField('base-urls')" class="bg-blue-500 text-white px-3 py-1 rounded">افزودن آدرس</button>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">آدرس‌های محصولات</label>
            <div id="products-urls">
                @foreach ($content['products_urls'] as $url)
                    <input type="url" name="products_urls[]" value="{{ $url }}" placeholder="https://example.com/shop" class="w-full px-3 py-2 border rounded mb-2" required>
                @endforeach
            </div>
            <button type="button" onclick="addField('products-urls')" class="bg-blue-500 text-white px-3 py-1 rounded">افزودن آدرس</button>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">حفظ فرمت قیمت</label>
            <select name="keep_price_format" class="w-full px-3 py-2 border rounded">
                <option value="0" {{ $content['keep_price_format'] ? '' : 'selected' }}>خیر</option>
                <option value="1" {{ $content['keep_price_format'] ? 'selected' : '' }}>بله</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">روش شناسایی محصول</label>
            <select name="product_id_method" class="w-full px-3 py-2 border rounded">
                <option value="selector" {{ $content['product_id_method'] === 'selector' ? 'selected' : '' }}>انتخابگر</option>
                <option value="url" {{ $content['product_id_method'] === 'url' ? 'selected' : '' }}>آدرس</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">منبع شناسایی محصول</label>
            <select name="product_id_source" id="product_id_source" onchange="toggleProductIdFields()" class="w-full px-3 py-2 border rounded">
                <option value="product_page" {{ $content['product_id_source'] === 'product_page' ? 'selected' : '' }}>صفحه محصول</option>
                <option value="url" {{ $content['product_id_source'] === 'url' ? 'selected' : '' }}>آدرس</option>
                <option value="main_page" {{ $content['product_id_source'] === 'main_page' ? 'selected' : '' }}>صفحه اصلی</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">روش گارانتی</label>
            <select name="guarantee_method" class="w-full px-3 py-2 border rounded">
                <option value="selector" {{ $content['guarantee_method'] === 'selector' ? 'selected' : '' }}>انتخابگر</option>
                <option value="title" {{ $content['guarantee_method'] === 'title' ? 'selected' : '' }}>عنوان</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">کلمات کلیدی گارانتی</label>
            <div id="guarantee-keywords">
                @foreach ($content['guarantee_keywords'] as $keyword)
                    <input type="text" name="guarantee_keywords[]" value="{{ $keyword }}" placeholder="مثال: گارانتی 12 ماهه" class="w-full px-3 py-2 border rounded mb-2" required>
                @endforeach
            </div>
            <button type="button" onclick="addField('guarantee-keywords')" class="bg-blue-500 text-white px-3 py-1 rounded">افزودن کلمه کلیدی</button>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">کلمات کلیدی موجودی (مثبت)</label>
            <div id="availability-positive">
                @foreach ($content['availability_keywords']['positive'] as $keyword)
                    <input type="text" name="availability_keywords[positive][]" value="{{ $keyword }}" placeholder="مثال: موجود" class="w-full px-3 py-2 border rounded mb-2" required>
                @endforeach
            </div>
            <button type="button" onclick="addField('availability-positive')" class="bg-blue-500 text-white px-3 py-1 rounded">افزودن کلمه کلیدی</button>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">کلمات کلیدی موجودی (منفی)</label>
            <div id="availability-negative">
                @foreach ($content['availability_keywords']['negative'] as $keyword)
                    <input type="text" name="availability_keywords[negative][]" value="{{ $keyword }}" placeholder="مثال: ناموجود" class="w-full px-3 py-2 border rounded mb-2" required>
                @endforeach
            </div>
            <button type="button" onclick="addField('availability-negative')" class="bg-blue-500 text-white px-3 py-1 rounded">افزودن کلمه کلیدی</button>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">کلمات کلیدی قیمت (بدون قیمت)</label>
            <div id="price-unpriced">
                @foreach ($content['price_keywords']['unpriced'] as $keyword)
                    <input type="text" name="price_keywords[unpriced][]" value="{{ $keyword }}" placeholder="مثال: تماس بگیرید" class="w-full px-3 py-2 border rounded mb-2" required>
                @endforeach
            </div>
            <button type="button" onclick="addField('price-unpriced')" class="bg-blue-500 text-white px-3 py-1 rounded">افزودن کلمه کلیدی</button>
        </div>
        <h2 class="text-xl font-bold mb-4">انتخابگرهای صفحه اصلی</h2>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">لینک‌های محصول - نوع</label>
            <input type="text" name="selectors[main_page][product_links][type]" value="{{ old('selectors.main_page.product_links.type', $content['selectors']['main_page']['product_links']['type']) }}" placeholder="مثال: css" class="w-full px-3 py-2 border rounded" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">لینک‌های محصول - انتخابگر</label>
            <input type="text" name="selectors[main_page][product_links][selector]" value="{{ old('selectors.main_page.product_links.selector', $content['selectors']['main_page']['product_links']['selector']) }}" placeholder="مثال: div.product-grid-item > div > div > a" class="w-full px-3 py-2 border rounded" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">لینک‌های محصول - ویژگی</label>
            <input type="text" name="selectors[main_page][product_links][attribute]" value="{{ old('selectors.main_page.product_links.attribute', $content['selectors']['main_page']['product_links']['attribute']) }}" placeholder="مثال: href" class="w-full px-3 py-2 border rounded" required>
        </div>
        <div id="main-page-product-id" class="hidden">
            <h3 class="text-lg font-bold mb-2">شناسه محصول</h3>
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">نوع</label>
                <input type="text" name="selectors[main_page][product_id][type]" value="{{ old('selectors.main_page.product_id.type', $content['selectors']['main_page']['product_id']['type'] ?? '') }}" placeholder="مثال: css" class="w-full px-3 py-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">انتخابگر</label>
                <input type="text" name="selectors[main_page][product_id][selector]" value="{{ old('selectors.main_page.product_id.selector', $content['selectors']['main_page']['product_id']['selector'] ?? '') }}" placeholder="مثال: div.product-grid-item > div > div > div > div > div > a" class="w-full px-3 py-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">ویژگی</label>
                <input type="text" name="selectors[main_page][product_id][attribute]" value="{{ old('selectors.main_page.product_id.attribute', $content['selectors']['main_page']['product_id']['attribute'] ?? '') }}" placeholder="مثال: data-product_id" class="w-full px-3 py-2 border rounded">
            </div>
        </div>
        <h2 class="text-xl font-bold mb-4">انتخابگرهای صفحه محصول</h2>
        @foreach (['title' => 'عنوان', 'category' => 'دسته‌بندی', 'availability' => 'موجودی', 'price' => 'قیمت', 'image' => 'تصویر', 'off' => 'تخفیف', 'guarantee' => 'گارانتی', 'product_id' => 'شناسه محصول'] as $field => $label)
            <div class="mb-4">
                <h3 class="text-lg font-bold mb-2">{{ $label }}</h3>
                <div class="mb-2">
                    <label class="block text-gray-700 font-bold">نوع</label>
                    <input type="text" name="selectors[product_page][{{ $field }}][type]" value="{{ old('selectors.product_page.' . $field . '.type', $content['selectors']['product_page'][$field]['type']) }}" placeholder="مثال: css" class="w-full px-3 py-2 border rounded" required>
                </div>
                <div class="mb-2">
                    <label class="block text-gray-700 font-bold">انتخابگر</label>
                    <input type="text" name="selectors[product_page][{{ $field }}][selector]" value="{{ old('selectors.product_page.' . $field . '.selector', $content['selectors']['product_page'][$field]['selector']) }}" placeholder="مثال: .product_title" class="w-full px-3 py-2 border rounded" required>
                </div>
                @if (in_array($field, ['image', 'product_id']))
                    <div class="mb-2">
                        <label class="block text-gray-700 font-bold">ویژگی</label>
                        <input type="text" name="selectors[product_page][{{ $field }}][attribute]" value="{{ old('selectors.product_page.' . $field . '.attribute', $content['selectors']['product_page'][$field]['attribute'] ?? '') }}" placeholder="مثال: src" class="w-full px-3 py-2 border rounded">
                    </div>
                @endif
            </div>
        @endforeach
        <h2 class="text-xl font-bold mb-4">صفحه‌بندی</h2>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">نوع صفحه‌بندی</label>
            <select name="pagination[type]" id="pagination_type" class="w-full px-3 py-2 border rounded">
                <option value="path" {{ $content['method_settings']['method_1']['pagination']['type'] === 'path' ? 'selected' : '' }}>مسیر</option>
                <option value="query" {{ $content['method_settings']['method_1']['pagination']['type'] === 'query' ? 'selected' : '' }}>پرس‌وجو</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">پارامتر</label>
            <input type="text" name="pagination[parameter]" value="{{ old('pagination.parameter', $content['method_settings']['method_1']['pagination']['parameter']) }}" placeholder="مثال: page" class="w-full px-3 py-2 border rounded" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">جداکننده</label>
            <input type="text" name="pagination[separator]" value="{{ old('pagination.separator', $content['method_settings']['method_1']['pagination']['separator']) }}" placeholder="مثال: /" class="w-full px-3 py-2 border rounded" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">پسوند</label>
            <input type="text" name="pagination[suffix]" value="{{ old('pagination.suffix', $content['method_settings']['method_1']['pagination']['suffix']) }}" placeholder="مثال: .html" class="w-full px-3 py-2 border rounded">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">حداکثر تعداد صفحات</label>
            <input type="number" name="pagination[max_pages]" value="{{ old('pagination.max_pages', $content['method_settings']['method_1']['pagination']['max_pages']) }}" placeholder="مثال: 35" class="w-full px-3 py-2 border rounded" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">استفاده از آدرس نمونه</label>
            <select name="pagination[use_sample_url]" id="use_sample_url" onchange="toggleSampleUrl()" class="w-full px-3 py-2 border rounded">
                <option value="0" {{ $content['method_settings']['method_1']['pagination']['use_sample_url'] ? '' : 'selected' }}>خیر</option>
                <option value="1" {{ $content['method_settings']['method_1']['pagination']['use_sample_url'] ? 'selected' : '' }}>بله</option>
            </select>
        </div>
        <div class="mb-4 hidden" id="sample_url">
            <label class="block text-gray-700 font-bold mb-2">آدرس نمونه</label>
            <input type="url" name="pagination[sample_url]" id="sample_url_input" value="{{ old('pagination.sample_url', $content['method_settings']['method_1']['pagination']['sample_url']) }}" placeholder="مثال: https://example.com/shop/page/2/" class="w-full px-3 py-2 border rounded">
            <p id="sample_url_error" class="text-red-500 text-sm mt-1 hidden">لطفاً یک آدرس معتبر (مانند https://example.com) وارد کنید.</p>
        </div>
        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">ذخیره</button>
    </form>
</main>
<footer class="bg-gray-800 text-white text-center p-4">
    <p>© ۱۴۰۴ مدیریت کانفیگ</p>
</footer>
<script>
    function addField(containerId) {
        const container = document.getElementById(containerId);
        const input = document.createElement('input');
        input.type = containerId.includes('url') ? 'url' : 'text';
        input.name = container.querySelector('input').name;
        input.className = 'w-full px-3 py-2 border rounded mb-2';
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
        sampleUrlField.classList.toggle('hidden', useSampleUrl !== '1');
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
