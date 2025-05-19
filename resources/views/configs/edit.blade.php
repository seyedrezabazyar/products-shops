<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ویرایش کانفیگ</title>
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
        <h1 class="text-2xl font-bold text-brown-700 mb-8 pb-4 border-b border-brown-200 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 ml-3 text-brown-600" viewBox="0 0 20 20"
                 fill="currentColor">
                <path fill-rule="evenodd"
                      d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"
                      clip-rule="evenodd"/>
            </svg>
            ویرایش کانفیگ: {{ $filename }}
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

        <form action="{{ route('configs.update', $filename) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- اطلاعات پایه -->
            <div class="card p-6 mb-6">
                <h2 class="section-header text-xl">اطلاعات پایه</h2>

                <div class="form-group">
                    <label for="site_name" class="block text-sm font-medium text-brown-700 mb-2">نام سایت <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="site_name" id="site_name" value="{{ old('site_name', $filename) }}"
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
                            <input type="radio" id="method1" name="method" value="1"
                                   class="hidden method-radio" {{ old('method', $content['method']) == 1 ? 'checked' : '' }}>
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
                            <input type="radio" id="method2" name="method" value="2"
                                   class="hidden method-radio" {{ old('method', $content['method']) == 2 ? 'checked' : '' }}>
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
                            <input type="radio" id="method3" name="method" value="3"
                                   class="hidden method-radio" {{ old('method', $content['method']) == 3 ? 'checked' : '' }}>
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
                        @foreach (old('base_urls', $content['base_urls'] ?? []) as $url)
                            <div class="flex">
                                <input type="url" name="base_urls[]" value="{{ $url }}"
                                       class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder="https://example.com" required>
                                <button type="button"
                                        class="remove-url mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                         fill="currentColor">
                                        <path fill-rule="evenodd"
                                              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
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
                        @foreach (old('products_urls', $content['products_urls'] ?? []) as $url)
                            <div class="flex">
                                <input type="url" name="products_urls[]" value="{{ $url }}"
                                       class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder="https://example.com/product/123" required>
                                <button type="button"
                                        class="remove-url mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                         fill="currentColor">
                                        <path fill-rule="evenodd"
                                              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
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
                            {{ old('keep_price_format', $content['keep_price_format'] ?? false) ? 'checked' : '' }}>
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
                            <option
                                value="query" {{ old('pagination.type', $content['method_settings']['method_1']['pagination']['type'] ?? 'query') == 'query' ? 'selected' : '' }}>
                                Query Parameter
                            </option>
                            <option
                                value="path" {{ old('pagination.type', $content['method_settings']['method_1']['pagination']['type'] ?? 'path') == 'path' ? 'selected' : '' }}>
                                Path Parameter
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">پارامتر صفحه‌بندی <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="pagination[parameter]"
                               value="{{ old('pagination.parameter', $content['method_settings']['method_1']['pagination']['parameter'] ?? 'page') }}"
                               class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                               required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">جداکننده <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="pagination[separator]"
                               value="{{ old('pagination.separator', $content['method_settings']['method_1']['pagination']['separator'] ?? '=') }}"
                               class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                               required>
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">حداکثر صفحات <span
                                class="text-red-500">*</span></label>
                        <input type="number" name="pagination[max_pages]"
                               value="{{ old('pagination.max_pages', $content['method_settings']['method_1']['pagination']['max_pages'] ?? 10) }}"
                               min="1"
                               class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="inline-flex items-center">
                        <input type="hidden" name="pagination[use_sample_url]" value="0">
                        <input type="checkbox" name="pagination[use_sample_url]" value="1"
                               id="pagination_use_sample_url"
                               class="w-5 h-5 text-brown-600 border-brown-300 rounded focus:ring-brown-500 bg-white"
                            {{ old('pagination.use_sample_url', $content['method_settings']['method_1']['pagination']['use_sample_url'] ?? false) ? 'checked' : '' }}>
                        <span class="mr-3 text-brown-700">استفاده از URL نمونه</span>
                    </label>
                </div>

                <div id="sample-url-container"
                     class="form-group {{ old('pagination.use_sample_url', $content['method_settings']['method_1']['pagination']['use_sample_url'] ?? false) ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-brown-700 mb-2">URL نمونه</label>
                    <input type="url" name="pagination[sample_url]"
                           value="{{ old('pagination.sample_url', $content['method_settings']['method_1']['pagination']['sample_url'] ?? '') }}"
                           class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                           placeholder="https://example.com/products?page=1">
                </div>
            </div>

            <!-- تنظیمات روش 2 و 3 -->
            <div id="method-2-3-settings"
                 class="card p-6 mb-6 method-settings method-2-settings method-3-settings {{ in_array(old('method', $content['method']), [2, 3]) ? '' : 'hidden' }}">
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
                           value="{{ old('container', $content['container'] ?? '') }}"
                           class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                    <small class="text-brown-500">این فیلد برای متد 3 اختیاری است.</small>
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-brown-700 mb-2">تعداد اسکرول</label>
                    <input type="number" name="scrool" value="{{ old('scrool', $content['scrool'] ?? 10) }}"
                           min="1"
                           class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-brown-700 mb-2">روش صفحه‌بندی <span
                            class="text-red-500">*</span></label>
                    <select name="pagination_method" id="pagination_method"
                            class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                        <option
                            value="next_button" {{ old('pagination_method', $content['method_settings'][old('method', $content['method']) == 2 ? 'method_2' : 'method_3']['navigation']['pagination']['method'] ?? 'next_button') == 'next_button' ? 'selected' : '' }}>
                            دکمه بعدی
                        </option>
                        <option
                            value="url" {{ old('pagination_method', $content['method_settings'][old('method', $content['method']) == 2 ? 'method_2' : 'method_3']['navigation']['pagination']['method'] ?? 'url') == 'url' ? 'selected' : '' }}>
                            URL
                        </option>
                    </select>
                </div>

                <div id="next-button-container"
                     class="form-group {{ old('pagination_method', $content['method_settings'][old('method', $content['method']) == 2 ? 'method_2' : 'method_3']['navigation']['pagination']['method'] ?? 'next_button') == 'next_button' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور دکمه بعدی <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="pagination_next_button_selector"
                           value="{{ old('pagination_next_button_selector', $content['method_settings'][old('method', $content['method']) == 2 ? 'method_2' : 'method_3']['navigation']['pagination']['next_button']['selector'] ?? '') }}"
                           class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                           placeholder=".next-page-button">
                </div>

                <div id="url-pagination-container"
                     class="hidden {{ old('pagination_method', $content['method_settings'][old('method', $content['method']) == 2 ? 'method_2' : 'method_3']['navigation']['pagination']['method'] ?? 'next_button') == 'url' ? '' : 'hidden' }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5">
                        <div class="form-group">
                            <label class="block text-sm font-medium text-brown-700 mb-2">نوع پیجینیشن URL <span
                                    class="text-red-500">*</span></label>
                            <select name="pagination_url_type"
                                    class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                                <option
                                    value="query" {{ old('pagination_url_type', $content['method_settings'][old('method', $content['method']) == 2 ? 'method_2' : 'method_3']['navigation']['pagination']['url']['type'] ?? 'query') == 'query' ? 'selected' : '' }}>
                                    Query Parameter
                                </option>
                                <option
                                    value="path" {{ old('pagination_url_type', $content['method_settings'][old('method', $content['method']) == 2 ? 'method_2' : 'method_3']['navigation']['pagination']['url']['type'] ?? 'path') == 'path' ? 'selected' : '' }}>
                                    Path Parameter
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="block text-sm font-medium text-brown-700 mb-2">پارامتر <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="pagination_url_parameter"
                                   value="{{ old('pagination_url_parameter', $content['method_settings'][old('method', $content['method']) == 2 ? 'method_2' : 'method_3']['navigation']['pagination']['url']['parameter'] ?? 'page') }}"
                                   class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5">
                        <div class="form-group">
                            <label class="block text-sm font-medium text-brown-700 mb-2">جداکننده <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="pagination_url_separator"
                                   value="{{ old('pagination_url_separator', $content['method_settings'][old('method', $content['method']) == 2 ? 'method_2' : 'method_3']['navigation']['pagination']['url']['separator'] ?? '=') }}"
                                   class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                        </div>
                        <div class="form-group">
                            <label class="block text-sm font-medium text-brown-700 mb-2">حداکثر صفحات <span
                                    class="text-red-500">*</span></label>
                            <input type="number" name="pagination_max_pages"
                                   value="{{ old('pagination_max_pages', $content['method_settings'][old('method', $content['method']) == 2 ? 'method_2' : 'method_3']['navigation']['pagination']['url']['max_pages'] ?? 3) }}"
                                   min="1"
                                   class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="pagination_use_sample_url" value="1"
                                   class="w-5 h-5 text-brown-600 border-brown-300 rounded focus:ring-brown-500 bg-white"
                                {{ old('pagination_use_sample_url', $content['method_settings'][old('method', $content['method']) == 2 ? 'method_2' : 'method_3']['navigation']['pagination']['url']['use_sample_url'] ?? false) ? 'checked' : '' }}>
                            <span class="mr-3 text-brown-700">استفاده از URL نمونه</span>
                        </label>
                    </div>

                    <div id="pagination-sample-url-container"
                         class="form-group {{ old('pagination_use_sample_url', $content['method_settings'][old('method', $content['method']) == 2 ? 'method_2' : 'method_3']['navigation']['pagination']['url']['use_sample_url'] ?? false) ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-brown-700 mb-2">URL نمونه</label>
                        <input type="url" name="pagination_sample_url"
                               value="{{ old('pagination_sample_url', $content['method_settings'][old('method', $content['method']) == 2 ? 'method_2' : 'method_3']['navigation']['pagination']['url']['sample_url'] ?? '') }}"
                               class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                               placeholder="https://example.com/products?page=1">
                    </div>
                </div>
            </div>

            <!-- تنظیمات روش 2 -->
            <div id="method-2-only-settings"
                 class="card p-6 mb-6 method-settings method-2-settings {{ old('method', $content['method']) == 2 ? '' : 'hidden' }}">
                <div class="form-group">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="share_product_id_from_method_2" value="1"
                               class="w-5 h-5 text-brown-600 border-brown-300 rounded focus:ring-brown-500 bg-white"
                            {{ old('share_product_id_from_method_2', $content['share_product_id_from_method_2'] ?? false) ? 'checked' : '' }}>
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
                                   value="{{ old('selectors.main_page.product_links.selector', $content['selectors']['main_page']['product_links']['selector'] ?? '') }}"
                                   class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                   placeholder=".product-item a" required>
                        </div>

                        <div class="form-group">
                            <label class="block text-sm font-medium text-brown-700 mb-2">صفت لینک محصولات <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="selectors[main_page][product_links][attribute]"
                                   value="{{ old('selectors.main_page.product_links.attribute', $content['selectors']['main_page']['product_links']['attribute'] ?? 'href') }}"
                                   class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                   required>
                        </div>

                        <!-- شناسه محصول در صفحه اصلی (شرطی) -->
                        <div id="main-page-product-id-container"
                             class="form-group {{ old('product_id_source', $content['product_id_source'] ?? 'product_page') == 'main_page' ? '' : 'hidden' }}">
                            <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور شناسه محصول <span
                                    class="text-red-500">*</span></label>
                            <input type="hidden" name="selectors[main_page][product_id][type]" value="css">
                            <input type="text" name="selectors[main_page][product_id][selector]"
                                   value="{{ old('selectors.main_page.product_id.selector', $content['selectors']['main_page']['product_id']['selector'] ?? '') }}"
                                   class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                   placeholder=".product-item .product-id">
                        </div>

                        <div id="main-page-product-id-attr-container"
                             class="form-group {{ old('product_id_source', $content['product_id_source'] ?? 'product_page') == 'main_page' ? '' : 'hidden' }}">
                            <label class="block text-sm font-medium text-brown-700 mb-2">صفت شناسه محصول <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="selectors[main_page][product_id][attribute]"
                                   value="{{ old('selectors.main_page.product_id.attribute', $content['selectors']['main_page']['product_id']['attribute'] ?? 'data-id') }}"
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
                                       value="{{ old('selectors.product_page.title.selector', $content['selectors']['product_page']['title']['selector'] ?? '') }}"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder=".product-title">
                            </div>

                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور دسته‌بندی</label>
                                <input type="hidden" name="selectors[product_page][category][type]" value="css">
                                <input type="text" name="selectors[product_page][category][selector]"
                                       value="{{ old('selectors.product_page.category.selector', $content['selectors']['product_page']['category']['selector'] ?? '') }}"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder=".breadcrumb">
                            </div>

                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور موجودی</label>
                                <input type="hidden" name="selectors[product_page][availability][type]" value="css">
                                <input type="text"
                                       name="selectors[product_page][availability][selector]"
                                       value="{{ old('selectors.product_page.availability.selector', is_string(\Illuminate\Support\Arr::get($content, 'selectors.product_page.availability.selector')) ? \Illuminate\Support\Arr::get($content, 'selectors.product_page.availability.selector') : '') }}"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder=".product-availability">
                            </div>

                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور قیمت</label>
                                <input type="hidden" name="selectors[product_page][price][type]" value="css">
                                <input type="text"
                                       name="selectors[product_page][price][selector]"
                                       value="{{ old('selectors.product_page.price.selector', is_string(\Illuminate\Support\Arr::get($content, 'selectors.product_page.price.selector')) ? \Illuminate\Support\Arr::get($content, 'selectors.product_page.price.selector') : '') }}"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder=".product-price">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور تصویر</label>
                                <input type="hidden" name="selectors[product_page][image][type]" value="css">
                                <input type="text" name="selectors[product_page][image][selector]"
                                       value="{{ old('selectors.product_page.image.selector', $content['selectors']['product_page']['image']['selector'] ?? '') }}"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder=".product-image">
                            </div>

                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">صفت تصویر</label>
                                <input type="text" name="selectors[product_page][image][attribute]"
                                       value="{{ old('selectors.product_page.image.attribute', $content['selectors']['product_page']['image']['attribute'] ?? 'src') }}"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                            </div>

                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور تخفیف</label>
                                <input type="hidden" name="selectors[product_page][off][type]" value="css">
                                <input type="text" name="selectors[product_page][off][selector]"
                                       value="{{ old('selectors.product_page.off.selector', $content['selectors']['product_page']['off']['selector'] ?? '') }}"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder=".product-discount">
                            </div>

                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور گارانتی</label>
                                <input type="hidden" name="selectors[product_page][guarantee][type]" value="css">
                                <input type="text" name="selectors[product_page][guarantee][selector]"
                                       value="{{ old('selectors.product_page.guarantee.selector', $content['selectors']['product_page']['guarantee']['selector'] ?? '') }}"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder=".product-guarantee">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div id="product-page-product-id-container"
                                 class="form-group {{ old('product_id_source', $content['product_id_source'] ?? 'product_page') == 'product_page' ? '' : 'hidden' }}">
                                <label class="block text-sm font-medium text-brown-700 mb-2">سلکتور شناسه محصول</label>
                                <input type="hidden" name="selectors[product_page][product_id][type]" value="css">
                                <input type="text" name="selectors[product_page][product_id][selector]"
                                       value="{{ old('selectors.product_page.product_id.selector', $content['selectors']['product_page']['product_id']['selector'] ?? '') }}"
                                       class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       placeholder=".product-id">
                            </div>

                            <div class="form-group">
                                <label class="block text-sm font-medium text-brown-700 mb-2">صفت شناسه محصول</label>
                                <input type="text" name="selectors[product_page][product_id][attribute]"
                                       value="{{ old('selectors.product_page.product_id.attribute', $content['selectors']['product_page']['product_id']['attribute'] ?? 'data-id') }}"
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
                            <option
                                value="selector" {{ old('product_id_method', $content['product_id_method'] ?? 'selector') == 'selector' ? 'selected' : '' }}>
                                سلکتور
                            </option>
                            <option
                                value="url" {{ old('product_id_method', $content['product_id_method'] ?? 'url') == 'url' ? 'selected' : '' }}>
                                URL
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">منبع شناسایی شناسه محصول <span
                                class="text-red-500">*</span></label>
                        <select name="product_id_source" id="product_id_source"
                                class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                            <option
                                value="product_page" {{ old('product_id_source', $content['product_id_source'] ?? 'product_page') == 'product_page' ? 'selected' : '' }}>
                                صفحه محصول
                            </option>
                            <option
                                value="url" {{ old('product_id_source', $content['product_id_source'] ?? 'url') == 'url' ? 'selected' : '' }}>
                                URL
                            </option>
                            <option
                                value="main_page" {{ old('product_id_source', $content['product_id_source'] ?? 'main_page') == 'main_page' ? 'selected' : '' }}>
                                صفحه اصلی
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="block text-sm font-medium text-brown-700 mb-2">روش شناسایی گارانتی <span
                            class="text-red-500">*</span></label>
                    <select name="guarantee_method"
                            class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200">
                        <option
                            value="selector" {{ old('guarantee_method', $content['guarantee_method'] ?? 'selector') == 'selector' ? 'selected' : '' }}>
                            سلکتور
                        </option>
                        <option
                            value="title" {{ old('guarantee_method', $content['guarantee_method'] ?? 'title') == 'title' ? 'selected' : '' }}>
                            عنوان
                        </option>
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-brown-700 mb-2">کلمات کلیدی گارانتی <span
                                class="text-red-500">*</span></label>
                        <div class="guarantee-keywords-container space-y-3">
                            @foreach (old('guarantee_keywords', $content['guarantee_keywords'] ?? []) as $keyword)
                                <div class="flex">
                                    <input type="text" name="guarantee_keywords[]" value="{{ $keyword }}"
                                           class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                           required>
                                    <button type="button"
                                            class="remove-keyword mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                             fill="currentColor">
                                            <path fill-rule="evenodd"
                                                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                  clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                            <div class="flex">
                                <input type="text" name="guarantee_keywords[]"
                                       class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       required>
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
                            @foreach (old('availability_keywords.positive', $content['availability_keywords']['positive'] ?? []) as $keyword)
                                <div class="flex">
                                    <input type="text" name="availability_keywords[positive][]" value="{{ $keyword }}"
                                           class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                           required>
                                    <button type="button"
                                            class="remove-keyword mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                             fill="currentColor">
                                            <path fill-rule="evenodd"
                                                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                  clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                            <div class="flex">
                                <input type="text" name="availability_keywords[positive][]"
                                       class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       required>
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
                            @foreach (old('availability_keywords.negative', $content['availability_keywords']['negative'] ?? []) as $keyword)
                                <div class="flex">
                                    <input type="text" name="availability_keywords[negative][]" value="{{ $keyword }}"
                                           class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                           required>
                                    <button type="button"
                                            class="remove-keyword mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                             fill="currentColor">
                                            <path fill-rule="evenodd"
                                                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                  clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                            <div class="flex">
                                <input type="text" name="availability_keywords[negative][]"
                                       class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       required>
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
                            @foreach (old('price_keywords.unpriced', $content['price_keywords']['unpriced'] ?? []) as $keyword)
                                <div class="flex">
                                    <input type="text" name="price_keywords[unpriced][]" value="{{ $keyword }}"
                                           class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                           required>
                                    <button type="button"
                                            class="remove-keyword mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                             fill="currentColor">
                                            <path fill-rule="evenodd"
                                                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                  clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                            <div class="flex">
                                <input type="text" name="price_keywords[unpriced][]"
                                       class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                       required>
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

            <!-- قوانین پیشوند عنوان -->
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
                        @foreach (old('title_prefix_rules.url', $content['title_prefix_rules'] ?? []) as $url => $rule)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 title-prefix-rule">
                                <div class="flex">
                                    <input type="url" name="title_prefix_rules[url][]" value="{{ $url }}"
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
                                           value="{{ $rule['prefix'] ?? '' }}"
                                           class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200"
                                           placeholder="کتاب">
                                </div>
                            </div>
                        @endforeach
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
                    به‌روزرسانی کانفیگ
                </button>
                <a href="{{ route('configs.index') }}"
                   class="btn-secondary text-lg font-bold py-3 px-8 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 focus:outline-none focus:ring-4 focus:ring-brown-500 focus:ring-opacity-50 ml-4">
                    بازگشت
                </a>
            </div>
    </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Method selection logic
            const methodBoxes = document.querySelectorAll('.method-box');
            const methodRadios = document.querySelectorAll('.method-radio');
            const methodSettings = document.querySelectorAll('.method-settings');

            methodBoxes.forEach(box => {
                box.addEventListener('click', function () {
                    const method = this.getAttribute('data-method');
                    methodRadios.forEach(radio => {
                        if (radio.value === method) {
                            radio.checked = true;
                        }
                    });
                    methodBoxes.forEach(b => b.classList.remove('selected'));
                    this.classList.add('selected');

                    methodSettings.forEach(setting => {
                        if (setting.classList.contains(`method-${method}-settings`)) {
                            setting.classList.remove('hidden');
                        } else {
                            setting.classList.add('hidden');
                        }
                    });

                    // Show/hide method-specific settings
                    if (method === '2') {
                        document.getElementById('method-2-only-settings').classList.remove('hidden');
                    } else {
                        document.getElementById('method-2-only-settings').classList.add('hidden');
                    }
                });
            });

            // Add/Remove base URLs
            const addBaseUrlBtn = document.querySelector('.add-base-url');
            const baseUrlsContainer = document.querySelector('.base-urls-container');
            addBaseUrlBtn.addEventListener('click', function () {
                const newUrlInput = document.createElement('div');
                newUrlInput.className = 'flex';
                newUrlInput.innerHTML = `
                        <input type="url" name="base_urls[]" class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" placeholder="https://example.com" required>
                        <button type="button" class="remove-url mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    `;
                baseUrlsContainer.insertBefore(newUrlInput, addBaseUrlBtn.parentElement);
                const removeBtn = newUrlInput.querySelector('.remove-url');
                removeBtn.addEventListener('click', function () {
                    newUrlInput.remove();
                });
            });

            // Add/Remove product URLs
            const addProductUrlBtn = document.querySelector('.add-product-url');
            const productsUrlsContainer = document.querySelector('.products-urls-container');
            addProductUrlBtn.addEventListener('click', function () {
                const newUrlInput = document.createElement('div');
                newUrlInput.className = 'flex';
                newUrlInput.innerHTML = `
                        <input type="url" name="products_urls[]" class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" placeholder="https://example.com/product/123" required>
                        <button type="button" class="remove-url mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    `;
                productsUrlsContainer.insertBefore(newUrlInput, addProductUrlBtn.parentElement);
                const removeBtn = newUrlInput.querySelector('.remove-url');
                removeBtn.addEventListener('click', function () {
                    newUrlInput.remove();
                });
            });

            // Add/Remove guarantee keywords
            const addGuaranteeKeywordBtn = document.querySelector('.add-guarantee-keyword');
            const guaranteeKeywordsContainer = document.querySelector('.guarantee-keywords-container');
            addGuaranteeKeywordBtn.addEventListener('click', function () {
                const newKeywordInput = document.createElement('div');
                newKeywordInput.className = 'flex';
                newKeywordInput.innerHTML = `
                        <input type="text" name="guarantee_keywords[]" class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" required>
                        <button type="button" class="remove-keyword mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    `;
                guaranteeKeywordsContainer.insertBefore(newKeywordInput, addGuaranteeKeywordBtn.parentElement);
                const removeBtn = newKeywordInput.querySelector('.remove-keyword');
                removeBtn.addEventListener('click', function () {
                    newKeywordInput.remove();
                });
            });

            // Add/Remove availability positive keywords
            const addAvailabilityPositiveBtn = document.querySelector('.add-availability-positive');
            const availabilityPositiveContainer = document.querySelector('.availability-positive-container');
            addAvailabilityPositiveBtn.addEventListener('click', function () {
                const newKeywordInput = document.createElement('div');
                newKeywordInput.className = 'flex';
                newKeywordInput.innerHTML = `
                        <input type="text" name="availability_keywords[positive][]" class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" required>
                        <button type="button" class="remove-keyword mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    `;
                availabilityPositiveContainer.insertBefore(newKeywordInput, addAvailabilityPositiveBtn.parentElement);
                const removeBtn = newKeywordInput.querySelector('.remove-keyword');
                removeBtn.addEventListener('click', function () {
                    newKeywordInput.remove();
                });
            });

            // Add/Remove availability negative keywords
            const addAvailabilityNegativeBtn = document.querySelector('.add-availability-negative');
            const availabilityNegativeContainer = document.querySelector('.availability-negative-container');
            addAvailabilityNegativeBtn.addEventListener('click', function () {
                const newKeywordInput = document.createElement('div');
                newKeywordInput.className = 'flex';
                newKeywordInput.innerHTML = `
                        <input type="text" name="availability_keywords[negative][]" class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" required>
                        <button type="button" class="remove-keyword mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    `;
                availabilityNegativeContainer.insertBefore(newKeywordInput, addAvailabilityNegativeBtn.parentElement);
                const removeBtn = newKeywordInput.querySelector('.remove-keyword');
                removeBtn.addEventListener('click', function () {
                    newKeywordInput.remove();
                });
            });

            // Add/Remove price unpriced keywords
            const addPriceUnpricedBtn = document.querySelector('.add-price-unpriced');
            const priceUnpricedContainer = document.querySelector('.price-unpriced-container');
            addPriceUnpricedBtn.addEventListener('click', function () {
                const newKeywordInput = document.createElement('div');
                newKeywordInput.className = 'flex';
                newKeywordInput.innerHTML = `
                        <input type="text" name="price_keywords[unpriced][]" class="input-field flex-1 px-4 py-2.5 rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" required>
                        <button type="button" class="remove-keyword mr-2 btn-danger px-3 py-2 rounded-lg flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    `;
                priceUnpricedContainer.insertBefore(newKeywordInput, addPriceUnpricedBtn.parentElement);
                const removeBtn = newKeywordInput.querySelector('.remove-keyword');
                removeBtn.addEventListener('click', function () {
                    newKeywordInput.remove();
                });
            });

            // Remove existing URLs
            document.querySelectorAll('.remove-url').forEach(btn => {
                btn.addEventListener('click', function () {
                    this.parentElement.remove();
                });
            });

            // Remove existing keywords
            document.querySelectorAll('.remove-keyword').forEach(btn => {
                btn.addEventListener('click', function () {
                    this.parentElement.remove();
                });
            });

            // Pagination settings toggle
            const paginationUseSampleUrl = document.getElementById('pagination_use_sample_url');
            const sampleUrlContainer = document.getElementById('sample-url-container');
            paginationUseSampleUrl.addEventListener('change', function () {
                sampleUrlContainer.classList.toggle('hidden', !this.checked);
            });

            // Pagination method toggle
            const paginationMethod = document.getElementById('pagination_method');
            const nextButtonContainer = document.getElementById('next-button-container');
            const urlPaginationContainer = document.getElementById('url-pagination-container');
            paginationMethod.addEventListener('change', function () {
                nextButtonContainer.classList.toggle('hidden', this.value !== 'next_button');
                urlPaginationContainer.classList.toggle('hidden', this.value !== 'url');
            });

            // Product ID source toggle
            const productIdSource = document.getElementById('product_id_source');
            const mainPageProductIdContainer = document.getElementById('main-page-product-id-container');
            const mainPageProductIdAttrContainer = document.getElementById('main-page-product-id-attr-container');
            const productPageProductIdContainer = document.getElementById('product-page-product-id-container');
            productIdSource.addEventListener('change', function () {
                mainPageProductIdContainer.classList.toggle('hidden', this.value !== 'main_page');
                mainPageProductIdAttrContainer.classList.toggle('hidden', this.value !== 'main_page');
                productPageProductIdContainer.classList.toggle('hidden', this.value !== 'product_page');
            });

            // URL pagination sample URL toggle
            const urlPaginationUseSampleUrl = urlPaginationContainer.querySelector('input[name="pagination_use_sample_url"]');
            const paginationSampleUrlContainer = document.getElementById('pagination-sample-url-container');
            if (urlPaginationUseSampleUrl) {
                urlPaginationUseSampleUrl.addEventListener('change', function () {
                    paginationSampleUrlContainer.classList.toggle('hidden', !this.checked);
                });
            }

            // Initial state
            methodBoxes.forEach(box => {
                if (box.querySelector('input[type="radio"]').checked) {
                    box.classList.add('selected');
                }
            });
            if (paginationUseSampleUrl.checked) {
                sampleUrlContainer.classList.remove('hidden');
            }
            if (paginationMethod.value === 'url') {
                urlPaginationContainer.classList.remove('hidden');
                nextButtonContainer.classList.add('hidden');
            } else {
                nextButtonContainer.classList.remove('hidden');
                urlPaginationContainer.classList.add('hidden');
            }
            if (productIdSource.value === 'main_page') {
                mainPageProductIdContainer.classList.remove('hidden');
                mainPageProductIdAttrContainer.classList.remove('hidden');
                productPageProductIdContainer.classList.add('hidden');
            } else if (productIdSource.value === 'product_page') {
                productPageProductIdContainer.classList.remove('hidden');
                mainPageProductIdContainer.classList.add('hidden');
                mainPageProductIdAttrContainer.classList.add('hidden');
            }
            if (urlPaginationUseSampleUrl && urlPaginationUseSampleUrl.checked) {
                paginationSampleUrlContainer.classList.remove('hidden');
            }
        });
        // دکمه‌های اضافه کردن و حذف قوانین پیشوند عنوان
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

        // حذف قوانین پیشوند موجود
        document.querySelectorAll('.remove-title-prefix-rule').forEach(btn => {
            btn.addEventListener('click', function () {
                this.parentElement.parentElement.remove();
            });
        });
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
    </script>
</div>
</body>
</html>
