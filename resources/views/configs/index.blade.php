<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت کانفیگ‌ها</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
<header class="bg-blue-600 text-white p-4">
    <h1 class="text-2xl font-bold">مدیریت کانفیگ‌ها</h1>
</header>
<main class="container mx-auto p-6 flex-grow">
    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('success') }}
        </div>
    @endif
    <div class="mb-4 flex items-center space-x-4">
        <label class="text-gray-700 font-bold">انتخاب متد:</label>
        <select id="method-select" class="px-3 py-2 border rounded">
            <option value="1">متد ۱</option>
            <option value="2">متد ۲</option>
            <option value="3">متد ۳</option>
        </select>
        <a id="create-link" href="{{ route('configs.create') }}?method=1" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">ایجاد کانفیگ جدید</a>
    </div>
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-200">
            <tr>
                <th class="px-4 py-2">نام سایت</th>
                <th class="px-4 py-2">متد</th>
                <th class="px-4 py-2">عملیات</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($configs as $config)
                <tr class="border-b">
                    <td class="px-4 py-2">{{ $config['filename'] }}</td>
                    <td class="px-4 py-2">{{ $config['content']['method'] }}</td>
                    <td class="px-4 py-2 flex space-x-2">
                        <a href="{{ route('configs.edit', $config['filename']) }}" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">ویرایش</a>
                        <form action="{{ route('configs.destroy', $config['filename']) }}" method="POST" onsubmit="return confirm('آیا مطمئن هستید؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">حذف</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</main>
<footer class="bg-gray-800 text-white text-center p-4">
    <p>© ۱۴۰۴ مدیریت کانفیگ</p>
</footer>
<script>
    document.getElementById('method-select').addEventListener('change', function() {
        document.getElementById('create-link').href = '{{ route("configs.create") }}?method=' + this.value;
    });
</script>
</body>
</html>
