<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیست تنظیمات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">لیست تنظیمات</h1>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <a href="{{ route('configs.create') }}" class="btn btn-primary mb-3">ایجاد تنظیم جدید</a>

    <table class="table table-bordered">
        <thead>
        <tr>
            <th>نام</th>
            <th>روش</th>
            <th>عملیات</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($configs as $config)
            <tr>
                <td>{{ $config['name'] }}</td>
                <td>
                    @switch($config['data']['method'])
                        @case(1) روش 1 @break
                        @case(2) روش 2 @break
                        @case(3) روش 3 @break
                    @endswitch
                </td>
                <td>
                    <a href="{{ route('configs.edit', $config['name']) }}" class="btn btn-sm btn-warning">ویرایش</a>
                    <form action="{{ route('configs.destroy', $config['name']) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('آیا مطمئن هستید که می‌خواهید حذف کنید؟')">حذف</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center">هیچ تنظیمی یافت نشد.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
