<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیست API‌ها</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700&display=swap">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body {
            font-family: 'Vazirmatn', sans-serif;
            background-color: #121212;
            color: #e0e0e0;
        }
        .card {
            background: rgba(30, 30, 30, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.4s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(128, 90, 213, 0.2);
            border-color: rgba(168, 85, 247, 0.4);
        }
        .header-decoration {
            background: linear-gradient(90deg, #9333ea, #d946ef);
            height: 3px;
            width: 80px;
            margin: 0 auto;
            border-radius: 3px;
        }
        .glow {
            box-shadow: 0 0 15px rgba(168, 85, 247, 0.5);
        }
        .icon-container {
            background: linear-gradient(135deg, #4c1d95, #7e22ce);
            box-shadow: 0 4px 15px rgba(126, 34, 206, 0.4);
        }
        .btn-view {
            background: linear-gradient(135deg, #4c1d95, #7e22ce);
            transition: all 0.3s ease;
        }
        .btn-view:hover {
            box-shadow: 0 0 15px rgba(168, 85, 247, 0.6);
            transform: translateY(-2px);
        }
        @media (max-width: 640px) {
            .grid {
                gap: 1rem;
            }
        }
    </style>
</head>
<body class="min-h-screen py-12 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-white mb-3">لیست API‌های موجود</h1>
            <div class="header-decoration mb-4"></div>
            <p class="text-lg text-gray-300">{{ $total }} سرویس API در دسترس شما</p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach ($apis as $api)
                <a href="{{ $api['url'] }}" class="block">
                    <div class="card rounded-2xl p-6 text-center h-full flex flex-col justify-between">
                        <div class="mb-4">
                            <div class="icon-container inline-flex items-center justify-center w-16 h-16 rounded-full mb-5 mx-auto">
                                <svg class="w-8 h-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                                                </svg>
                            </div>
                            <h2 class="text-xl font-bold text-white mb-3">{{ ucwords(str_replace('_', ' ', $api['name'])) }}</h2>
                            <p class="text-gray-400 text-sm">{{ $api['description'] ?? 'دسترسی به اطلاعات و سرویس‌های API' }}</p>
                             <br>
                            <p class="text-sm text-gray-500 mb-2">تعداد محصولات: {{ $api['total_products'] }}</p>
                                                    <p class="text-sm text-gray-500 mb-2">تعداد صفحات: {{ $api['total_pages'] }}</p>
                                                    <p class="text-sm text-gray-500 mb-2">محصولات موجود: {{ $api['available_products'] }}</p>
                                                   
                        </div>
                        <div class="mt-5">
                        
                            <span class="btn-view inline-flex items-center px-5 py-2.5 rounded-lg text-white text-sm font-medium">
                                مشاهده محصولات
                                <i class="ph-bold ph-arrow-left mr-2"></i>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</body>
</html>
