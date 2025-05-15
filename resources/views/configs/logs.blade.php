<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>لاگ‌های کانفیگ {{ $filename }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/vazirmatn/33.0.3/Vazirmatn-font-face.min.css" rel="stylesheet">    <script>
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
        .custom-shadow {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        .btn-danger {
            background-color: #ef4444;
            color: white;
            transition: all 0.2s ease;
        }
        .btn-danger:hover {
            background-color: #dc2626;
            transform: translateY(-1px);
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
            transform: translateY(-1px);
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
        .log-card {
            background-color: #1e293b;
            border-radius: 0.75rem;
            border: 1px solid #334155;
            transition: all 0.3s ease;
        }
        .log-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            border-color: #3b82f6;
        }
        .empty-state {
            background-color: rgba(30, 41, 59, 0.5);
            border: 2px dashed #334155;
        }
        .log-console {
            background-color: #0f172a;
            border-radius: 0.5rem;
            padding: 1rem;
            height: 500px;
            overflow-y: auto;
            font-family: monospace;
            white-space: pre-wrap;
            color: #e2e8f0;
            font-size: 0.875rem;
            line-height: 1.5;
            width: 100%; /* Make sure it takes full width */
        }
        .log-console::-webkit-scrollbar {
            width: 8px;
        }
        .log-console::-webkit-scrollbar-track {
            background: #1e293b;
            border-radius: 4px;
        }
        .log-console::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }
        .log-console::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
        .log-line {
            margin-bottom: 0.25rem;
        }
        .log-info {
            color: #60a5fa;
        }
        .log-error {
            color: #f87171;
        }
        .log-success {
            color: #34d399;
        }
        .log-warning {
            color: #fbbf24;
        }
        .auto-refresh-toggle {
            display: flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
            margin-right: 8px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #475569;
            transition: .4s;
            border-radius: 24px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: #3b82f6;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
    </style>
</head>
<body class="min-h-screen py-10">
<div class="container mx-auto px-4 max-w-full"> <!-- Changed from container to full width -->
    <div class="max-w-7xl mx-auto custom-shadow rounded-xl bg-dark-800 p-8 border border-dark-600"> <!-- Changed from max-w-5xl to max-w-7xl -->
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 pb-6 border-b border-dark-600">
            <div class="flex items-center mb-4 md:mb-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mr-3 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011-1h10a1 1 0 011 1v1H4V2zm1 3a1 1 0 00-1 1v10a1 1 0 001 1h10a1 1 0 001-1V6a1 1 0 00-1-1H5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <h1 class="text-3xl font-bold text-blue-400">لاگ‌های کانفیگ {{ $filename }}</h1>
            </div>
            <a href="{{ route('configs.index') }}" class="btn-secondary px-6 py-3 rounded-lg flex items-center shadow-lg hover:shadow-xl transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                </svg>
                بازگشت به لیست کانفیگ‌ها
            </a>
        </div>
        <!-- Logs List -->
        <div class="mb-8">
            <h2 class="text-xl font-bold text-gray-200 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                لیست فایل‌های لاگ
            </h2>
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
            @if(count($logFiles) > 0)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8"> <!-- Changed from grid-cols-2 to grid-cols-3 -->
                    @foreach($logFiles as $log)
                        <div class="log-card p-5 relative">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-lg font-medium text-blue-300 cursor-pointer" onclick="showLogContent('{{ $log['filename'] }}')">
                                    {{ \Carbon\Carbon::createFromTimestamp($log['last_modified'])->format('Y-m-d H:i:s') }}
                                </h3>
                                <span class="text-xs text-gray-400">
                            {{ round($log['size'] / 1024, 2) }} KB
                        </span>
                            </div>
                            <div class="text-sm text-gray-400 mb-3 cursor-pointer" onclick="showLogContent('{{ $log['filename'] }}')">
                                {{ $log['filename'] }}
                            </div>
                            <div class="flex justify-between items-center">
                                <button type="button" class="btn-secondary px-3 py-1 rounded-lg text-xs flex items-center" onclick="showLogContent('{{ $log['filename'] }}')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    نمایش محتوا
                                </button>
                                <form action="{{ route('configs.logs.delete', $log['filename']) }}" method="POST" class="inline-block" onsubmit="return confirm('آیا از حذف این فایل لاگ اطمینان دارید؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger px-3 py-1 rounded-lg text-xs flex items-center">
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
                <div class="empty-state rounded-xl p-8 flex flex-col items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-dark-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-lg font-bold text-gray-300 mb-1">هیچ لاگی یافت نشد</h3>
                    <p class="text-gray-400 text-sm">هنوز هیچ اسکرپی برای این کانفیگ اجرا نشده است.</p>
                </div>
            @endif
        </div>
        <!-- Log Content Section -->
        <div id="log-content-section" class="hidden">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-200 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span id="log-title">محتوای لاگ</span>
                </h2>
                <button id="refresh-button" type="button" class="btn-primary px-3 py-1 rounded-lg text-sm flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    بروزرسانی
                </button>
            </div>
            <div class="log-console overflow-x-auto" id="log-content"></div> <!-- Added overflow-x-auto for horizontal scrolling -->
        </div>
    </div>
</div>
<script>
    let currentLogFile = null;
    function showLogContent(logFile) {
        currentLogFile = logFile;
        document.getElementById('log-content-section').classList.remove('hidden');
        document.getElementById('log-title').textContent = 'محتوای لاگ: ' + logFile;
        fetchLogContent();
    }
    function fetchLogContent() {
        if (!currentLogFile) return;
        fetch("{{ url('configs/log-content') }}/" + currentLogFile)
            .then(response => {
                if (!response.ok) {
                    throw new Error('خطا در دریافت محتوای لاگ');
                }
                return response.text();
            })
            .then(data => {
                const logContent = document.getElementById('log-content');
                // پردازش خطوط لاگ و اضافه کردن کلاس‌های رنگی
                const lines = data.split('\n');
                let formattedContent = '';
                lines.forEach(line => {
                    // اگر خط خالی است، آن را نادیده بگیر
                    if (!line.trim()) return;
                    let lineClass = 'log-line';
                    if (line.includes('ERROR') || line.includes('خطا') || line.includes('exception')) {
                        lineClass += ' log-error';
                    } else if (line.includes('SUCCESS') || line.includes('موفق')) {
                        lineClass += ' log-success';
                    } else if (line.includes('WARNING') || line.includes('هشدار')) {
                        lineClass += ' log-warning';
                    } else if (line.includes('INFO') || line.includes('اطلاعات')) {
                        lineClass += ' log-info';
                    }
                    // اطمینان از اینکه متن UTF-8 به درستی نمایش داده می‌شود
                    const escapedLine = line
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                    formattedContent += `<div class="${lineClass}" dir="auto">${escapedLine}</div>`;
                });
                logContent.innerHTML = formattedContent;
                // اسکرول به انتهای محتوا
                logContent.scrollTop = logContent.scrollHeight;
            })
            .catch(error => {
                console.error('Error fetching log content:', error);
                document.getElementById('log-content').textContent = 'خطا در دریافت محتوای لاگ: ' + error.message;
            });
    }
    // دکمه بروزرسانی دستی
    document.getElementById('refresh-button').addEventListener('click', function() {
        fetchLogContent();
    });
</script>
</body>
</html>
