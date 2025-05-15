<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت کانفیگ‌ها</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/vazirmatn/33.0.3/Vazirmatn-font-face.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: {
                            50: '#f8fafc',
                            100: '#e2e8f0',
                            200: '#cbd5e1',
                            300: '#94a3b8',
                            400: '#64748b',
                            500: '#475569',
                            600: '#334155',
                            700: '#1e293b',
                            800: '#0f172a',
                            900: '#020617',
                        },
                    },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: "Vazirmatn", system-ui, sans-serif;
            background-color: #0f172a;
            color: #e2e8f0;
        }
        .btn-stop {
            background-color: #ef4444;
            color: white;
            transition: all 0.2s ease;
        }

        .btn-stop:hover {
            background-color: #dc2626;
            transform: translateY(-1px);
        }
        .custom-shadow {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .btn-primary {
            background-color: #3b82f6;
            color: white;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: #475569;
            color: white;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background-color: #334155;
        }

        .btn-danger {
            background-color: #ef4444;
            color: white;
            transition: all 0.2s ease;
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        .btn-success {
            background-color: #10b981;
            color: white;
            transition: all 0.2s ease;
        }

        .btn-success:hover {
            background-color: #059669;
        }

        .btn-play {
            background-color: #8b5cf6;
            color: white;
            transition: all 0.2s ease;
        }

        .btn-play:hover {
            background-color: #7c3aed;
        }

        .config-card {
            background-color: #1e293b;
            border-radius: 1rem;
            border: 1px solid #334155;
            transition: all 0.3s ease;
        }

        .config-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            border-color: #3b82f6;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 500;
        }

        .badge-method-1 {
            background-color: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid #3b82f6;
        }

        .badge-method-2 {
            background-color: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid #10b981;
        }

        .badge-method-3 {
            background-color: rgba(249, 115, 22, 0.2);
            color: #fb923c;
            border: 1px solid #f97316;
        }

        .empty-state {
            background-color: rgba(30, 41, 59, 0.5);
            border: 2px dashed #334155;
        }

        .table-row {
            border-bottom: 1px solid #334155;
            transition: all 0.2s ease;
        }

        .table-row:hover {
            background-color: rgba(30, 41, 59, 0.8);
        }

        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .7;
            }
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 50;
        }

        .modal-content {
            position: relative;
            margin: 10% auto;
            max-width: 600px;
            background-color: #1e293b;
            border-radius: 0.75rem;
            border: 1px solid #334155;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 1.5rem;
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .log-console {
            background-color: #0f172a;
            border-radius: 0.5rem;
            padding: 1rem;
            height: 300px;
            overflow-y: auto;
            font-family: monospace;
            white-space: pre-wrap;
            color: #e2e8f0;
        }

        .running-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #10b981;
            margin-right: 0.5rem;
            animation: pulse 2s infinite;
        }
        .btn-play {
            background-color: #8b5cf6;
            color: white;
            transition: all 0.2s ease;
        }

        .btn-play:hover {
            background-color: #7c3aed;
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="min-h-screen py-10">
<div class="container mx-auto px-4">
    <div class="max-w-6xl mx-auto custom-shadow rounded-xl bg-dark-800 p-8 border border-dark-600">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 pb-6 border-b border-dark-600">
            <div class="flex items-center mb-4 md:mb-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mr-3 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                </svg>
                <h1 class="text-3xl font-bold text-blue-400">مدیریت کانفیگ‌ها</h1>
            </div>
            <a href="{{ route('configs.create') }}" class="btn-primary px-6 py-3 rounded-lg flex items-center shadow-lg hover:shadow-xl transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                ایجاد کانفیگ جدید
            </a>
        </div>

        <!-- Success Message -->
        @if(session('success'))
            <div class="bg-green-900/30 border border-green-500 text-green-200 px-6 py-4 rounded-lg mb-6 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Error Message -->
        @if(session('error'))
            <div class="bg-red-900/30 border border-red-500 text-red-200 px-6 py-4 rounded-lg mb-6 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Configs Grid View -->
        <div class="mb-8">
            <h2 class="text-xl font-bold text-gray-200 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                نمای کارت‌ها
            </h2>

            @if(count($configs) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($configs as $config)
                        <div class="config-card p-6">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-lg font-bold text-blue-300">{{ $config['filename'] }}</h3>
                                <span class="badge {{ 'badge-method-' . $config['content']['method'] }}">
                                        روش {{ $config['content']['method'] }}
                                    </span>
                            </div>

                            <div class="mb-4 text-gray-300">
                                <div class="flex items-center mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                    <span class="text-sm truncate max-w-xs">{{ count($config['content']['base_urls']) }} URL پایه</span>
                                </div>

                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                    <span class="text-sm truncate max-w-xs">{{ count($config['content']['products_urls']) }} URL محصول</span>
                                </div>
                            </div>

                            <!-- در قسمت کارت‌ها، در بخش عملیات هر کارت -->
                            <!-- در قسمت کارت‌ها، در بخش عملیات هر کارت -->
                            <div class="flex justify-between pt-4 border-t border-dark-600">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('configs.edit', $config['filename']) }}" class="btn-secondary px-3 py-2 rounded-lg text-sm flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        ویرایش
                                    </a>

                                    <form action="{{ route('configs.run', $config['filename']) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="btn-play px-3 py-2 rounded-lg text-sm flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            اجرا
                                        </button>
                                    </form>

                                    <form action="{{ route('configs.stop', $config['filename']) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="btn-stop px-3 py-2 rounded-lg text-sm flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v6H9z" />
                                            </svg>
                                            توقف
                                        </button>
                                    </form>

                                    <a href="{{ route('configs.logs', $config['filename']) }}" class="btn-secondary px-3 py-2 rounded-lg text-sm flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        لاگ‌ها
                                    </a>
                                </div>

                                <form action="{{ route('configs.destroy', $config['filename']) }}" method="POST" class="inline-block" onsubmit="return confirm('آیا از حذف این کانفیگ اطمینان دارید؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger px-3 py-2 rounded-lg text-sm flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        حذف
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state rounded-xl p-10 flex flex-col items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-dark-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <h3 class="text-xl font-bold text-gray-300 mb-2">هیچ کانفیگی یافت نشد</h3>
                    <p class="text-gray-400 mb-6 text-center">برای شروع، یک کانفیگ جدید ایجاد کنید.</p>
                    <a href="{{ route('configs.create') }}" class="btn-primary px-6 py-3 rounded-lg flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        ایجاد کانفیگ جدید
                    </a>
                </div>
            @endif
        </div>

        <!-- Configs Table View -->
        <div>
            <h2 class="text-xl font-bold text-gray-200 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                نمای جدول
            </h2>

            @if(count($configs) > 0)
                <div class="overflow-x-auto rounded-xl border border-dark-600">
                    <table class="min-w-full">
                        <thead class="bg-dark-700">
                        <tr>
                            <th class="px-6 py-4 text-right text-sm font-medium text-blue-300 tracking-wider">نام فایل</th>
                            <th class="px-6 py-4 text-right text-sm font-medium text-blue-300 tracking-wider">روش</th>
                            <th class="px-6 py-4 text-right text-sm font-medium text-blue-300 tracking-wider">تعداد URL پایه</th>
                            <th class="px-6 py-4 text-right text-sm font-medium text-blue-300 tracking-wider">تعداد URL محصول</th>
                            <th class="px-6 py-4 text-right text-sm font-medium text-blue-300 tracking-wider">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="bg-dark-800 divide-y divide-dark-600">
                        @foreach($configs as $config)
                            <tr class="table-row">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-200">{{ $config['filename'] }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="badge {{ 'badge-method-' . $config['content']['method'] }}">
                                                روش {{ $config['content']['method'] }}
                                            </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    {{ count($config['content']['base_urls']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    {{ count($config['content']['products_urls']) }}
                                </td>
                                <!-- همچنین در بخش جدول، ستون عملیات را به شکل زیر اصلاح کنید -->
                                <!-- همچنین در بخش جدول، ستون عملیات را به شکل زیر اصلاح کنید -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex flex-wrap gap-1">
                                    <a href="{{ route('configs.edit', $config['filename']) }}" class="btn-secondary px-2 py-1 rounded text-sm">
                                        ویرایش
                                    </a>

                                    <form action="{{ route('configs.run', $config['filename']) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="btn-play px-2 py-1 rounded text-sm flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            </svg>
                                            اجرا
                                        </button>
                                    </form>

                                    <form action="{{ route('configs.stop', $config['filename']) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="btn-stop px-2 py-1 rounded text-sm flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v6H9z" />
                                            </svg>
                                            توقف
                                        </button>
                                    </form>

                                    <a href="{{ route('configs.logs', $config['filename']) }}" class="btn-secondary px-2 py-1 rounded text-sm">
                                        لاگ‌ها
                                    </a>

                                    <form action="{{ route('configs.destroy', $config['filename']) }}" method="POST" class="inline-block" onsubmit="return confirm('آیا از حذف این کانفیگ اطمینان دارید؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger px-2 py-1 rounded text-sm">
                                            حذف
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state rounded-xl p-8 flex flex-col items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-dark-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-lg font-bold text-gray-300 mb-1">جدول خالی است</h3>
                    <p class="text-gray-400 text-sm">داده‌ای برای نمایش وجود ندارد.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal for log display -->
<div id="logModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2 class="text-xl font-bold text-blue-400 mb-4">لاگ اجرای اسکرپر</h2>
        <div class="flex items-center mb-2">
            <span class="running-indicator"></span>
            <span class="text-green-400">در حال اجرا...</span>
        </div>
        <div id="logContent" class="log-console">در حال بارگذاری لاگ...</div>
    </div>
</div>

<script>
    // Modal functionality
    const modal = document.getElementById('logModal');
    const closeModal = document.querySelector('.close-modal');

    if (closeModal) {
        closeModal.addEventListener('click', function() {
            modal.style.display = 'none';
            if (modal.dataset.intervalId) {
                clearInterval(parseInt(modal.dataset.intervalId));
            }
        });
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
            if (modal.dataset.intervalId) {
                clearInterval(parseInt(modal.dataset.intervalId));
            }
        }
    });

    // Function to show modal with log content
    function showLogModal(logFile) {
        const logContent = document.getElementById('logContent');
        logContent.textContent = 'در حال بارگذاری لاگ...';
        modal.style.display = 'block';

        // Fetch log content periodically
        fetchLogContent(logFile);
        const interval = setInterval(() => {
            fetchLogContent(logFile);
        }, 2000);

        // Store interval ID to clear it when modal is closed
        modal.dataset.intervalId = interval;
    }

    // Function to fetch log content
    function fetchLogContent(logFile) {
        fetch(`/configs/logs/${logFile}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('خطا در دریافت لاگ');
                }
                return response.text();
            })
            .then(data => {
                const logContent = document.getElementById('logContent');
                logContent.textContent = data;
                logContent.scrollTop = logContent.scrollHeight;
            })
            .catch(error => {
                console.error('Error fetching log:', error);
                const logContent = document.getElementById('logContent');
                logContent.textContent = 'خطا در دریافت لاگ: ' + error.message;
            });
    }

    // Check if there's a log file in session and show modal
    @if(session('log_file'))
    document.addEventListener('DOMContentLoaded', function() {
        showLogModal("{{ session('log_file') }}");
    });
    @endif
</script>
</body>
</html>
