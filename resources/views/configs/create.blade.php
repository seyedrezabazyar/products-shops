<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ایجاد کانفیگ جدید</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/vazirmatn/33.0.3/Vazirmatn-font-face.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cream: {
                            50: '#FFFBF0',
                            100: '#FFF5E1',
                            200: '#FFECC3',
                            300: '#FFE0A1',
                            400: '#FFD47F',
                            500: '#FFC857',
                            600: '#FFBC30',
                            700: '#FFA800',
                            800: '#CC8600',
                            900: '#996400',
                        },
                        brown: {
                            50: '#F9F6F3',
                            100: '#F3EDE7',
                            200: '#E7DBCF',
                            300: '#D9C9B6',
                            400: '#CAB59C',
                            500: '#BBA183',
                            600: '#A88C6A',
                            700: '#8D7455',
                            800: '#6F5B42',
                            900: '#524330',
                        }
                    },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: "Vazirmatn", system-ui, sans-serif;
            background-color: #F9F6F3;
            color: #524330;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23d5ad85' fill-opacity='0.08' fill-rule='evenodd'/%3E%3C/svg%3E");

        }

        .card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #E7DBCF;
        }

        .input-field {
            background-color: #fff;
            border: 1px solid #E7DBCF;
            color: #524330;
        }

        .input-field:focus {
            border-color: #BBA183;
            box-shadow: 0 0 0 2px rgba(187, 161, 131, 0.2);
        }

        .btn-primary {
            background-color: #A88C6A;
            color: white;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background-color: #8D7455;
        }

        .btn-secondary {
            background-color: #D9C9B6;
            color: #524330;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background-color: #CAB59C;
        }

        .btn-danger {
            background-color: #ef4444;
            color: white;
            transition: all 0.2s ease;
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        .section-header {
            border-bottom: 1px solid #E7DBCF;
            padding-bottom: 0.75rem;
            margin-bottom: 1.25rem;
            font-weight: 700;
            color: #8D7455;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .method-box.selected {
            background-color: rgba(187, 161, 131, 0.1);
            border-color: #BBA183 !important;
        }

        .method-box.selected .method-circle {
            background-color: #BBA183;
            border-color: #A88C6A;
        }

        .method-box.selected .method-title {
            color: #8D7455;
        }

        .method-box.selected .method-check-indicator {
            opacity: 1;
        }

        .method-box {
            background-color: white;
            border: 1px solid #E7DBCF;
        }

        .method-circle {
            background-color: #F3EDE7;
            border-color: #E7DBCF;
        }

        .tab-active {
            background-color: #FFF5E1;
            color: #996400;
            font-weight: 600;
        }

        /* استایل‌های تم دارک */
        body.dark {
            background-color: #2d2d2d;
            color: #e0e0e0;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23808080' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
        }

        body.dark .card {
            background-color: #3a3a3a;
            border-color: #555;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        body.dark .input-field {
            background-color: #444;
            border-color: #666;
            color: #e0e0e0;
        }

        body.dark .input-field:focus {
            border-color: #888;
            box-shadow: 0 0 0 2px rgba(136, 136, 136, 0.3);
        }

        body.dark .btn-primary {
            background-color: #666;
        }

        body.dark .btn-primary:hover {
            background-color: #777;
        }

        body.dark .btn-secondary {
            background-color: #555;
            color: #e0e0e0;
        }

        body.dark .btn-secondary:hover {
            background-color: #666;
        }

        body.dark .btn-danger {
            background-color: #ef4444;
        }

        body.dark .btn-danger:hover {
            background-color: #dc2626;
        }

        body.dark .section-header {
            border-color: #555;
            color: #bbb;
        }

        body.dark .method-box {
            background-color: #444;
            border-color: #666;
        }

        body.dark .method-box.selected {
            background-color: rgba(136, 136, 136, 0.2);
            border-color: #888;
        }

        body.dark .method-box.selected .method-circle {
            background-color: #888;
            border-color: #777;
        }

        body.dark .method-box.selected .method-title {
            color: #bbb;
        }

        body.dark .method-circle {
            background-color: #555;
            border-color: #666;
        }

        body.dark .text-brown-700 {
            color: #e0e0e0;
        }

        body.dark .text-brown-600 {
            color: #bbb;
        }

        body.dark .text-brown-500 {
            color: #aaa;
        }

        body.dark .border-brown-200 {
            border-color: #555;
        }

        body.dark .bg-cream-50 {
            background-color: #444;
            border-color: #666;
        }
    </style>
</head>
<body class="min-h-screen py-8">
<div class="container mx-auto px-4">
    <div class="max-w-5xl mx-auto card p-8">
        <h1 class="text-2xl font-bold text-brown-700 mb-8 pb-4 border-b border-brown-200 flex items-center justify-between">
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 ml-3 text-brown-600" viewBox="0 0 20 20"
                     fill="currentColor">
                    <path fill-rule="evenodd"
                          d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"
                          clip-rule="evenodd"/>
                </svg>
                ایجاد کانفیگ جدید
            </div>
            <button id="theme-toggle" class="btn-secondary px-4 py-2 rounded-lg flex items-center">
                <svg id="theme-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </button>
        </h1>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-300 text-red-800 px-6 py-4 rounded-lg mb-6">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2 text-red-600" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <strong class="font-bold text-lg">خطا!</strong>
                </div>
                <ul class="list-disc mr-8 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('configs.store') }}" method="POST">
            @csrf

            <!-- اطلاعات پایه -->
            <div class="card p-6 mb-6">
                <h2 class="section-header text-xl">اطلاعات پایه</h2>

                <div class="form-group">
                    <label for="site_name" class="block text-sm font-medium text-brown-700 mb-2">نام سایت <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="site_name" id="site_name" value="{{ old('site_name') }}"
                           class="input-field w-full px-4 py-3 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                           placeholder="مثال: دیجی‌کالا" required>
                </div>

                <!-- روش اسکرپ با باکس‌های انتخابی -->
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-brown-700 mb-4 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        روش اسکرپ
                        <span class="text-red-500 mr-1">*</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <!-- روش 1 -->
                        <div class="method-box cursor-pointer transition-all duration-300 rounded-lg shadow-sm"
                             data-method="1">
                            <input type="radio" id="method1" name="method" value="1" class="hidden method-radio"
                                   checked>
                            <label for="method1" class="block w-full h-full cursor-pointer">
                                <div class="p-5 text-center">
                                    <div class="flex justify-center mb-3">
                                        <div
                                            class="w-14 h-14 rounded-full flex items-center justify-center border-2 method-circle">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-brown-600"
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <h3 class="text-lg font-bold method-title">روش 1</h3>
                                    <p class="text-brown-600 mt-2">صفحه‌بندی ساده</p>
                                    <div class="mt-3 method-check-indicator opacity-0 transition-opacity duration-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto text-brown-600"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- روش 2 -->
                        <div class="method-box cursor-pointer transition-all duration-300 rounded-lg shadow-sm"
                             data-method="2">
                            <input type="radio" id="method2" name="method" value="2" class="hidden method-radio">
                            <label for="method2" class="block w-full h-full cursor-pointer">
                                <div class="p-5 text-center">
                                    <div class="flex justify-center mb-3">
                                        <div
                                            class="w-14 h-14 rounded-full flex items-center justify-center border-2 method-circle">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-brown-600"
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <h3 class="text-lg font-bold method-title">روش 2</h3>
                                    <p class="text-brown-600 mt-2">وب درایور</p>
                                    <div class="mt-3 method-check-indicator opacity-0 transition-opacity duration-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto text-brown-600"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- روش 3 -->
                        <div class="method-box cursor-pointer transition-all duration-300 rounded-lg shadow-sm"
                             data-method="3">
                            <input type="radio" id="method3" name="method" value="3" class="hidden method-radio">
                            <label for="method3" class="block w-full h-full cursor-pointer">
                                <div class="p-5 text-center">
                                    <div class="flex justify-center mb-3">
                                        <div
                                            class="w-14 h-14 rounded-full flex items-center justify-center border-2 method-circle">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-brown-600"
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <h3 class="text-lg font-bold method-title">روش 3</h3>
                                    <p class="text-brown-600 mt-2">وب درایور بهینه</p>
                                    <div class="mt-3 method-check-indicator opacity-0 transition-opacity duration-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto text-brown-600"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- آدرس‌های پایه -->
            <div class="card p-6 mb-6">
                <h2 class="section-header text-xl flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2 text-brown-600" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    آدرس‌های پایه
                </h2>

                <div class="form-group">
                    <label class="block text-sm font-medium text-brown-700 mb-2">آدرس‌های پایه (URL) <span
                            class="text-red-500">*</span></label>
                    <div class="base-urls-container space-y-3">
                        <div class="flex">
                            <input type="url" name="base_urls[]"
                                   class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                   placeholder="https://example.com" required>
                            <button type="button"
                                    class="add-base-url mr-2 btn-primary px-3 py-2 rounded-lg flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                     fill="currentColor">
                                    <path fill-rule="evenodd"
                                          d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                          clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-brown-700 mb-2">آدرس‌های صفحات محصول <span
                            class="text-red-500">*</span></label>
                    <div class="products-urls-container space-y-3">
                        <div class="flex">
                            <input type="url" name="products_urls[]"
                                   class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                   placeholder="https://example.com/product/123" required>
                            <button type="button"
                                    class="add-product-url mr-2 btn-primary px-3 py-2 rounded-lg flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                     fill="currentColor">
                                    <path fill-rule="evenodd"
                                          d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                          clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- تنظیمات دیتابیس و اجرا -->
            <div class="card p-6 mb-6">
                <h2 class="section-header text-xl flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2 text-brown-600" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                    تنظیمات دیتابیس و اجرا
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- روش اجرا -->
                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">روش اجرا <span
                                class="text-red-500">*</span></label>
                        <select name="run_method" id="run_method"
                                class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                required>
                            <option value="new" {{ old('run_method', 'new') === 'new' ? 'selected' : '' }}>جدید</option>
                            <option value="continue" {{ old('run_method') === 'continue' ? 'selected' : '' }}>ادامه
                            </option>
                        </select>
                    </div>

                    <!-- دیتابیس -->
                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">وضعیت دیتابیس <span
                                class="text-red-500">*</span></label>
                        <select name="database" id="database"
                                class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                required>
                            <option value="clear" {{ old('database', 'clear') === 'clear' ? 'selected' : '' }}>پاک
                                کردن
                            </option>
                            <option value="continue" {{ old('database') === 'continue' ? 'selected' : '' }}>ادامه
                            </option>
                        </select>
                    </div>
                </div>

                <!-- دسته‌بندی ثابت -->
                <div class="form-group">
                    <label class="inline-flex items-center">
                        <input type="hidden" name="use_set_category" value="0">
                        <input type="checkbox" name="use_set_category" value="1" id="use_set_category"
                               class="w-5 h-5 text-brown-600 border-brown-300 rounded focus:ring-brown-500 bg-white" {{ old('use_set_category') ? 'checked' : '' }}>
                        <span class="mr-3 text-brown-700">استفاده از دسته‌بندی ثابت</span>
                    </label>
                </div>

                <div id="set-category-container" class="form-group {{ old('use_set_category') ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-brown-700 mb-2">دسته‌بندی ثابت <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="set_category" id="set_category" value="{{ old('set_category') }}"
                           class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                           placeholder="مثال: لوازم خانگی" {{ old('use_set_category') ? 'required' : '' }}>
                </div>
            </div>

            <!-- تنظیمات قیمت -->
            <div class="card p-6 mb-6">
                <h2 class="section-header text-xl flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2 text-brown-600" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    تنظیمات قیمت
                </h2>

                <div class="form-group mb-4">
                    <div class="flex items-center">
                        <input type="hidden" name="keep_price_format" value="0">
                        <input type="checkbox" name="keep_price_format" value="1" id="keep_price_format"
                               class="w-5 h-5 text-brown-600 border-brown-300 rounded focus:ring-brown-500 bg-white"
                            {{ old('keep_price_format') ? 'checked' : '' }}>
                        <label for="keep_price_format" class="mr-2 text-brown-700">حفظ فرمت قیمت</label>
                    </div>
                </div>
            </div>

            <!-- تنظیمات پیجینیشن -->
            <div id="pagination-settings" class="card p-6 mb-6 method-settings method-1-settings">
                <h2 class="section-header text-xl flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2 text-brown-600" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
                    </svg>
                    تنظیمات صفحه‌بندی
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">نوع صفحه‌بندی <span
                                class="text-red-500">*</span></label>
                        <select name="pagination[type]"
                                class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                            <option value="query">Query Parameter</option>
                            <option value="path">Path Parameter</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">پارامتر صفحه‌بندی <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="pagination[parameter]" value="page"
                               class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                               required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">جداکننده <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="pagination[separator]" value="="
                               class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                               required>
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">حداکثر صفحات <span
                                class="text-red-500">*</span></label>
                        <input type="number" name="pagination[max_pages]" value="10" min="1"
                               class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="inline-flex items-center">
                        <input type="hidden" name="pagination[use_sample_url]" value="0">
                        <input type="checkbox" name="pagination[use_sample_url]" value="1"
                               id="pagination_use_sample_url"
                               class="w-5 h-5 text-brown-600 border-brown-300 rounded focus:ring-brown-500 bg-white">
                        <span class="mr-3 text-brown-700">استفاده از URL نمونه</span>
                    </label>
                </div>

                <div id="sample-url-container" class="form-group hidden">
                    <label class="block text-sm font-medium text-brown-700 mb-2">URL نمونه</label>
                    <input type="url" name="pagination[sample_url]"
                           class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                           placeholder="https://example.com/products?page=1">
                </div>
            </div>

            <!-- تنظیمات روش 2 و 3 -->
            <div id="method-2-3-settings"
                 class="card p-6 mb-6 method-settings method-2-settings method-3-settings hidden">
                <h2 class="section-header text-xl flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2 text-brown-600" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    تنظیمات وب درایور
                </h2>

                <!-- فیلد کانتینر برای متد 3 -->
                <div class="form-group method-3-field">
                    <label for="container" class="block text-sm font-medium text-brown-700 mb-2">کانتینر <span
                            class="text-brown-400">(اختیاری)</span></label>
                    <input type="text" name="container" id="container"
                           class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                           value="{{ old('container') }}">
                    <small class="text-brown-500">این فیلد برای متد 3 اختیاری است.</small>
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-brown-700 mb-2">تعداد اسکرول</label>
                    <input type="number" name="scrool" value="10" min="1"
                           class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-brown-700 mb-2">روش صفحه‌بندی <span
                            class="text-red-500">*</span></label>
                    <select name="pagination_method" id="pagination_method"
                            class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                        <option value="next_button">دکمه بعدی</option>
                        <option value="url">URL</option>
                    </select>
                </div>

                <div id="next-button-container" class="form-group">
                    <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور دکمه بعدی <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="pagination_next_button_selector"
                           class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                           placeholder=".next-page-button">
                </div>

                <div id="url-pagination-container" class="hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5">
                        <div class="form-group">
                            <label class="block text-sm font-medium text-brown-700 mb-2">نوع پیجینیشن URL <span
                                    class="text-red-500">*</span></label>
                            <select name="pagination_url_type"
                                    class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                                <option value="query">Query Parameter</option>
                                <option value="path">Path Parameter</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="block text-sm font-medium text-brown-700 mb-2">پارامتر <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="pagination_url_parameter" value="page"
                                   class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5">
                        <div class="form-group">
                            <label class="block text-sm font-medium text-brown-700 mb-2">جداکننده <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="pagination_url_separator" value="="
                                   class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                        </div>
                        <div class="form-group">
                            <label class="block text-sm font-medium text-brown-700 mb-2">حداکثر صفحات <span
                                    class="text-red-500">*</span></label>
                            <input type="number" name="pagination_max_pages" value="3" min="1"
                                   class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="inline-flex items-center">
                            <input type="checkbox" id="pagination_use_sample_url" name="pagination_use_sample_url"
                                   value="1"
                                   class="w-5 h-5 text-brown-600 border-brown-300 rounded focus:ring-brown-500 bg-white">
                            <span class="mr-3 text-brown-700">استفاده از URL نمونه</span>
                        </label>
                    </div>

                    <div id="pagination-sample-url-container" class="form-group hidden">
                        <label class="block text-sm font-medium text-brown-700 mb-2">URL نمونه</label>
                        <input type="url" name="pagination_sample_url"
                               class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                               placeholder="https://example.com/products?page=1">
                    </div>
                </div>
            </div>

            <!-- تنظیمات روش 2 -->
            <div id="method-2-only-settings" class="card p-6 mb-6 method-settings method-2-settings hidden">
                <div class="form-group">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="share_product_id_from_method_2" value="1"
                               class="w-5 h-5 text-brown-600 border-brown-300 rounded focus:ring-brown-500 bg-white">
                        <span class="mr-3 text-brown-700">اشتراک گذاری شناسه محصول از روش 2</span>
                    </label>
                </div>
            </div>

            <!-- سلکتورها -->
            <div class="card p-6 mb-6">
                <h2 class="section-header text-xl flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2 text-brown-600" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                    </svg>
                    سلکتورها
                </h2>

                <!-- سلکتورهای صفحه اصلی -->
                <div class="mt-6 border-t border-brown-200 pt-5">
                    <h3 class="text-lg font-semibold text-brown-700 mb-4 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 text-brown-600" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        صفحه اصلی
                    </h3>

                    <div class="bg-cream-50 p-5 rounded-lg border border-cream-200">
                        <div class="form-group">
                            <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور لینک محصولات <span
                                    class="text-red-500">*</span></label>
                            <input type="hidden" name="selectors[main_page][product_links][type]" value="css">
                            <input type="text" name="selectors[main_page][product_links][selector]"
                                   class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                   placeholder=".product-item a" required>
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-brown-700 mb-2">صفت لینک محصولات <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="selectors[main_page][product_links][attribute]" value="href"
                                   class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                   required>
                        </div>

                        <!-- شناسه محصول در صفحه اصلی (شرطی) -->
                        <div id="main-page-product-id-container" class="form-group hidden">
                            <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور شناسه محصول <span
                                    class="text-red-500">*</span></label>
                            <input type="hidden" name="selectors[main_page][product_id][type]" value="css">
                            <input type="text" name="selectors[main_page][product_id][selector]"
                                   class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                   placeholder=".product-item .product-id">
                        </div>

                        <div id="main-page-product-id-attr-container" class="form-group hidden">
                            <label class="block text-sm font-medium text-brown-700 mb-2">صفت شناسه محصول <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="selectors[main_page][product_id][attribute]" value="data-id"
                                   class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                        </div>
                    </div>
                </div>


                <!-- سلکتورهای صفحه محصول -->
                <div class="mt-6 border-t border-brown-200 pt-5">
                    <h3 class="text-lg font-semibold text-brown-700 mb-4 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 text-brown-600" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        صفحه محصول
                    </h3>

                    <div class="bg-cream-50 p-5 rounded-lg border border-cream-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور عنوان</label>
                                <input type="hidden" name="selectors[product_page][title][type]" value="css">
                                <input type="text" name="selectors[product_page][title][selector]"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder=".product-title">
                            </div>

                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور دسته‌بندی</label>
                                <input type="hidden" name="selectors[product_page][category][type]" value="css">
                                <input type="text" name="selectors[product_page][category][selector]"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder=".breadcrumb">
                            </div>

                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور موجودی</label>
                                <input type="hidden" name="selectors[product_page][availability][type]" value="css">
                                <input type="text" name="selectors[product_page][availability][selector]"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder=".product-availability">
                            </div>

                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور قیمت</label>
                                <input type="hidden" name="selectors[product_page][price][type]" value="css">
                                <input type="text" name="selectors[product_page][price][selector]"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder=".product-price">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور تصویر</label>
                                <input type="hidden" name="selectors[product_page][image][type]" value="css">
                                <input type="text" name="selectors[product_page][image][selector]"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder=".product-image">
                            </div>

                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">صفت تصویر</label>
                                <input type="text" name="selectors[product_page][image][attribute]" value="src"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                            </div>

                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور تخفیف</label>
                                <input type="hidden" name="selectors[product_page][off][type]" value="css">
                                <input type="text" name="selectors[product_page][off][selector]"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder=".product-discount">
                            </div>

                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور گارانتی</label>
                                <input type="hidden" name="selectors[product_page][guarantee][type]" value="css">
                                <input type="text" name="selectors[product_page][guarantee][selector]"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder=".product-guarantee">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور دکمه افزودن به سبد خرید
                                <span class="text-red-500">*</span></label>
                            <input type="hidden" name="selectors[product_page][add_to_cart_button][type]" value="css">
                            <input type="text" name="selectors[product_page][add_to_cart_button][selector]"
                                   class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                   placeholder=".add-to-cart-button" required>
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور ناموجودی <span
                                    class="text-red-500">*</span></label>
                            <input type="hidden" name="selectors[product_page][out_of_stock][type]" value="css">
                            <input type="text" name="selectors[product_page][out_of_stock][selector]"
                                   class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                   placeholder=".out-of-stock" required>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div id="product-page-product-id-container" class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور شناسه محصول</label>
                                <input type="hidden" name="selectors[product_page][product_id][type]" value="css">
                                <input type="text" name="selectors[product_page][product_id][selector]"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder=".product-id">
                            </div>

                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">صفت شناسه محصول</label>
                                <input type="text" name="selectors[product_page][product_id][attribute]" value="data-id"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- روش شناسایی محصول -->
            <div class="card p-6 mb-6">
                <h2 class="section-header text-xl flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2 text-brown-600" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    روش‌های شناسایی محصول
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">روش شناسایی شناسه محصول <span
                                class="text-red-500">*</span></label>
                        <select name="product_id_method" id="product_id_method"
                                class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                            <option value="selector">سلکتور</option>
                            <option value="url">URL</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">منبع شناسایی شناسه محصول <span
                                class="text-red-500">*</span></label>
                        <select name="product_id_source" id="product_id_source"
                                class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                            <option value="product_page">صفحه محصول</option>
                            <option value="url">URL</option>
                            <option value="main_page">صفحه اصلی</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-brown-700 mb-2">روش شناسایی گارانتی <span
                            class="text-red-500">*</span></label>
                    <select name="guarantee_method"
                            class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                        <option value="selector">سلکتور</option>
                        <option value="title">عنوان</option>
                    </select>
                </div>
            </div>

            <!-- کلمات کلیدی گارانتی -->
            <div class="card p-6 mb-6">
                <h2 class="section-header text-xl flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2 text-brown-600" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    کلمات کلیدی
                </h2>

                <div class="form-group">
                    <label class="block text-sm font-medium text-brown-700 mb-2">حالت شناسایی موجودی <span
                            class="text-red-500">*</span></label>
                    <select name="availability_mode" id="availability_mode"
                            class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                            required>
                        <option value="smart" {{ old('availability_mode', 'smart') === 'smart' ? 'selected' : '' }}>
                            هوشمند
                        </option>
                        <option value="keyword" {{ old('availability_mode') === 'keyword' ? 'selected' : '' }}>کلمه
                            کلیدی
                        </option>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">کلمات کلیدی گارانتی <span
                                class="text-red-500">*</span></label>
                        <div class="guarantee-keywords-container space-y-3">
                            <div class="flex">
                                <input type="text" name="guarantee_keywords[]"
                                       class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       value="گارانتی" required>
                                <button type="button"
                                        class="add-guarantee-keyword mr-2 btn-primary px-3 py-2 rounded-lg flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                         fill="currentColor">
                                        <path fill-rule="evenodd"
                                              d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">کلمات کلیدی موجودی (مثبت) <span
                                class="text-red-500">*</span></label>
                        <div class="availability-positive-container space-y-3">
                            <div class="flex">
                                <input type="text" name="availability_keywords[positive][]"
                                       class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       value="موجود" required>
                                <button type="button"
                                        class="add-availability-positive mr-2 btn-primary px-3 py-2 rounded-lg flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                         fill="currentColor">
                                        <path fill-rule="evenodd"
                                              d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">کلمات کلیدی موجودی (منفی) <span
                                class="text-red-500">*</span></label>
                        <div class="availability-negative-container space-y-3">
                            <div class="flex">
                                <input type="text" name="availability_keywords[negative][]"
                                       class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       value="ناموجود" required>
                                <button type="button"
                                        class="add-availability-negative mr-2 btn-primary px-3 py-2 rounded-lg flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                         fill="currentColor">
                                        <path fill-rule="evenodd"
                                              d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">کلمات کلیدی قیمت (بدون قیمت) <span
                                class="text-red-500">*</span></label>
                        <div class="price-unpriced-container space-y-3">
                            <div class="flex">
                                <input type="text" name="price_keywords[unpriced][]"
                                       class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       value="تماس بگیرید" required>
                                <button type="button"
                                        class="add-price-unpriced mr-2 btn-primary px-3 py-2 rounded-lg flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                         fill="currentColor">
                                        <path fill-rule="evenodd"
                                              d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-6 mb-6">
                <h2 class="section-header text-xl flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2 text-brown-600" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    قوانین پیشوند عنوان
                </h2>

                <div class="form-group">
                    <label class="block text-sm font-medium text-brown-700 mb-2">قوانین پیشوند عنوان <span
                            class="text-brown-400">(اختیاری)</span></label>
                    <div class="title-prefix-rules-container space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 title-prefix-rule">
                            <div class="flex">
                                <input type="url" name="title_prefix_rules[url][]"
                                       class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder="https://example.com/fa/book/">
                                <button type="button"
                                        class="remove-title-prefix-rule mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                         fill="currentColor">
                                        <path fill-rule="evenodd"
                                              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex">
                                <input type="text" name="title_prefix_rules[prefix][]"
                                       class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder="کتاب">
                            </div>
                        </div>
                    </div>
                    <button type="button"
                            class="add-title-prefix-rule mt-3 btn-primary px-4 py-2 rounded-lg flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20"
                             fill="currentColor">
                            <path fill-rule="evenodd"
                                  d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                  clip-rule="evenodd"/>
                        </svg>
                        افزودن قانون پیشوند
                    </button>
                </div>
            </div>

            <!-- دکمه ثبت -->
            <div class="flex justify-center mt-8">
                <button type="submit"
                        class="btn-primary text-lg font-bold py-3 px-8 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 focus:outline-none focus:ring-4 focus:ring-brown-500 focus:ring-opacity-50">
                    ذخیره کانفیگ
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // دکمه‌های اضافه کردن آدرس‌های پایه
        document.querySelector('.add-base-url').addEventListener('click', function () {
            const container = document.querySelector('.base-urls-container');
            const newInput = document.createElement('div');
            newInput.className = 'flex';
            newInput.innerHTML = `
            <input type="url" name="base_urls[]" class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" placeholder="https://example.com" required>
            <button type="button" class="remove-url mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        `;
            container.appendChild(newInput);

            newInput.querySelector('.remove-url').addEventListener('click', function () {
                container.removeChild(newInput);
            });
        });

        // دکمه‌های اضافه کردن آدرس‌های محصول
        document.querySelector('.add-product-url').addEventListener('click', function () {
            const container = document.querySelector('.products-urls-container');
            const newInput = document.createElement('div');
            newInput.className = 'flex';
            newInput.innerHTML = `
            <input type="url" name="products_urls[]" class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" placeholder="https://example.com/product/123" required>
            <button type="button" class="remove-url mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        `;
            container.appendChild(newInput);

            newInput.querySelector('.remove-url').addEventListener('click', function () {
                container.removeChild(newInput);
            });
        });

        // دکمه‌های اضافه کردن کلمات کلیدی گارانتی
        document.querySelector('.add-guarantee-keyword').addEventListener('click', function () {
            const container = document.querySelector('.guarantee-keywords-container');
            const newInput = document.createElement('div');
            newInput.className = 'flex';
            newInput.innerHTML = `
            <input type="text" name="guarantee_keywords[]" class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" required>
            <button type="button" class="remove-keyword mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        `;
            container.appendChild(newInput);

            newInput.querySelector('.remove-keyword').addEventListener('click', function () {
                container.removeChild(newInput);
            });
        });

        // دکمه‌های اضافه کردن کلمات کلیدی موجودی مثبت
        document.querySelector('.add-availability-positive').addEventListener('click', function () {
            const container = document.querySelector('.availability-positive-container');
            const newInput = document.createElement('div');
            newInput.className = 'flex';
            newInput.innerHTML = `
            <input type="text" name="availability_keywords[positive][]" class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" required>
            <button type="button" class="remove-keyword mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        `;
            container.appendChild(newInput);

            newInput.querySelector('.remove-keyword').addEventListener('click', function () {
                container.removeChild(newInput);
            });
        });

        // دکمه‌های اضافه کردن کلمات کلیدی موجودی منفی
        document.querySelector('.add-availability-negative').addEventListener('click', function () {
            const container = document.querySelector('.availability-negative-container');
            const newInput = document.createElement('div');
            newInput.className = 'flex';
            newInput.innerHTML = `
            <input type="text" name="availability_keywords[negative][]" class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" required>
            <button type="button" class="remove-keyword mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        `;
            container.appendChild(newInput);

            newInput.querySelector('.remove-keyword').addEventListener('click', function () {
                container.removeChild(newInput);
            });
        });

        // دکمه‌های اضافه کردن کلمات کلیدی قیمت بدون قیمت
        document.querySelector('.add-price-unpriced').addEventListener('click', function () {
            const container = document.querySelector('.price-unpriced-container');
            const newInput = document.createElement('div');
            newInput.className = 'flex';
            newInput.innerHTML = `
            <input type="text" name="price_keywords[unpriced][]" class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" required>
            <button type="button" class="remove-keyword mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        `;
            container.appendChild(newInput);

            newInput.querySelector('.remove-keyword').addEventListener('click', function () {
                container.removeChild(newInput);
            });
        });

        // نمایش/مخفی کردن URL نمونه بر اساس چک‌باکس
        const useSampleUrlCheckbox = document.querySelector('#pagination_use_sample_url');
        const sampleUrlContainer = document.getElementById('sample-url-container');
        const sampleUrlInput = document.querySelector('input[name="pagination[sample_url]"]');

        if (useSampleUrlCheckbox && sampleUrlContainer && sampleUrlInput) {
            // تنظیم حالت اولیه
            sampleUrlContainer.classList.toggle('hidden', !useSampleUrlCheckbox.checked);
            sampleUrlInput.disabled = !useSampleUrlCheckbox.checked;

            // رویداد تغییر چک‌باکس
            useSampleUrlCheckbox.addEventListener('change', function () {
                sampleUrlContainer.classList.toggle('hidden', !this.checked);
                sampleUrlInput.disabled = !this.checked;
                if (!this.checked) {
                    sampleUrlInput.value = ''; // خالی کردن مقدار فیلد در صورت غیرفعال شدن
                }
            });
        } else {
            console.error('Checkbox, sample URL container, or input not found');
        }

        // تغییر روش پیجینیشن در روش‌های 2 و 3
        document.getElementById('pagination_method').addEventListener('change', function () {
            const nextButtonContainer = document.getElementById('next-button-container');
            const urlPaginationContainer = document.getElementById('url-pagination-container');

            if (this.value === 'next_button') {
                nextButtonContainer.classList.remove('hidden');
                urlPaginationContainer.classList.add('hidden');
            } else {
                nextButtonContainer.classList.add('hidden');
                urlPaginationContainer.classList.remove('hidden');
            }
        });

        // تغییر منبع شناسایی شناسه محصول
        document.getElementById('product_id_source').addEventListener('change', function () {
            const mainPageProductIdContainer = document.getElementById('main-page-product-id-container');
            const mainPageProductIdAttrContainer = document.getElementById('main-page-product-id-attr-container');
            const productPageProductIdContainer = document.getElementById('product-page-product-id-container');

            if (this.value === 'main_page') {
                mainPageProductIdContainer.classList.remove('hidden');
                mainPageProductIdAttrContainer.classList.remove('hidden');
            } else {
                mainPageProductIdContainer.classList.add('hidden');
                mainPageProductIdAttrContainer.classList.add('hidden');
            }

            if (this.value === 'product_page') {
                productPageProductIdContainer.classList.remove('hidden');
            } else {
                productPageProductIdContainer.classList.add('hidden');
            }
        });

        // تغییر روش اسکرپ
        const methodRadios = document.querySelectorAll('input[name="method"]');
        methodRadios.forEach(radio => {
            radio.addEventListener('change', function () {
                const method = this.value;

                // مخفی کردن همه تنظیمات
                document.querySelectorAll('.method-settings').forEach(el => {
                    el.classList.add('hidden');
                });

                // نمایش تنظیمات مرتبط با روش انتخابی
                document.querySelectorAll(`.method-${method}-settings`).forEach(el => {
                    el.classList.remove('hidden');
                });
            });
        });

        // تغییر تم
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');

        themeToggleBtn.addEventListener('click', function () {
            document.body.classList.toggle('dark');

            // تغییر آیکون بر اساس تم
            if (document.body.classList.contains('dark')) {
                themeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        `;
            } else {
                themeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        `;
            }
        });

        // تنظیم اولیه نمایش/مخفی بر اساس مقادیر پیش‌فرض
        const initialMethod = document.querySelector('input[name="method"]:checked').value;
        document.querySelectorAll('.method-settings').forEach(el => {
            el.classList.add('hidden');
        });
        document.querySelectorAll(`.method-${initialMethod}-settings`).forEach(el => {
            el.classList.remove('hidden');
        });

        // تنظیم اولیه روش پیجینیشن
        const initialPaginationMethod = document.getElementById('pagination_method').value;
        if (initialPaginationMethod === 'next_button') {
            document.getElementById('next-button-container').classList.remove('hidden');
            document.getElementById('url-pagination-container').classList.add('hidden');
        } else {
            document.getElementById('next-button-container').classList.add('hidden');
            document.getElementById('url-pagination-container').classList.remove('hidden');
        }

        // تنظیم اولیه منبع شناسایی شناسه محصول
        const initialProductIdSource = document.getElementById('product_id_source').value;
        if (initialProductIdSource === 'main_page') {
            document.getElementById('main-page-product-id-container').classList.remove('hidden');
            document.getElementById('main-page-product-id-attr-container').classList.remove('hidden');
        } else {
            document.getElementById('main-page-product-id-container').classList.add('hidden');
            document.getElementById('main-page-product-id-attr-container').classList.add('hidden');
        }

        if (initialProductIdSource !== 'product_page') {
            document.getElementById('product-page-product-id-container').classList.add('hidden');
        }

        // تنظیم باکس‌های انتخاب روش
        const methodBoxes = document.querySelectorAll('.method-box');
        // تنظیم حالت اولیه
        const initialMethodBox = document.querySelector(`.method-box[data-method="${initialMethod}"]`);
        if (initialMethodBox) {
            initialMethodBox.classList.add('selected');
        }

        // اضافه کردن رویداد کلیک به باکس‌ها
        methodBoxes.forEach(box => {
            box.addEventListener('click', function () {
                // حذف کلاس selected از همه باکس‌ها
                methodBoxes.forEach(b => b.classList.remove('selected'));

                // اضافه کردن کلاس selected به باکس انتخاب شده
                this.classList.add('selected');

                // انتخاب رادیو باتن مربوطه
                const radioBtn = this.querySelector('.method-radio');
                radioBtn.checked = true;

                // فراخوانی رویداد change برای رادیو باتن
                const event = new Event('change');
                radioBtn.dispatchEvent(event);
            });
        });

        // دکمه‌های اضافه کردن قوانین پیشوند عنوان
        document.querySelector('.add-title-prefix-rule').addEventListener('click', function () {
            const container = document.querySelector('.title-prefix-rules-container');
            const newRule = document.createElement('div');
            newRule.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 title-prefix-rule';
            newRule.innerHTML = `
            <div class="flex">
                <input type="url" name="title_prefix_rules[url][]" class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" placeholder="https://example.com/fa/book/">
                <button type="button" class="remove-title-prefix-rule mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div class="flex">
                <input type="text" name="title_prefix_rules[prefix][]" class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" placeholder="کتاب">
            </div>
        `;
            container.appendChild(newRule);

            newRule.querySelector('.remove-title-prefix-rule').addEventListener('click', function () {
                container.removeChild(newRule);
            });
        });
    });
    // نمایش/مخفی کردن فیلد دسته‌بندی ثابت
    const useSetCategoryCheckbox = document.querySelector('#use_set_category');
    const setCategoryContainer = document.getElementById('set-category-container');
    const setCategoryInput = document.querySelector('#set_category');

    if (useSetCategoryCheckbox && setCategoryContainer && setCategoryInput) {
        // تنظیم حالت اولیه
        setCategoryContainer.classList.toggle('hidden', !useSetCategoryCheckbox.checked);
        setCategoryInput.required = useSetCategoryCheckbox.checked;

        // رویداد تغییر چک‌باکس
        useSetCategoryCheckbox.addEventListener('change', function () {
            setCategoryContainer.classList.toggle('hidden', !this.checked);
            setCategoryInput.required = this.checked;
            if (!this.checked) {
                setCategoryInput.value = ''; // خالی کردن مقدار فیلد در صورت غیرفعال شدن
            }
        });
    } else {
        console.error('Checkbox, set category container, or input not found');
    }
</script>
</body>
</html>
