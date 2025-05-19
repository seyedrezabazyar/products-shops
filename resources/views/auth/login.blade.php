<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به حساب کاربری</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/vazirmatn/33.0.3/Vazirmatn-font-face.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: {
                            100: '#fffcf0',
                            200: '#fff7d6',
                            300: '#fff0b3',
                            400: '#ffe066',
                            500: '#ffd700',
                            600: '#e6c300',
                            700: '#cca300',
                            800: '#b38600',
                            900: '#8c6900',
                        },
                        sepia: {
                            50: '#fcf9f5',
                            100: '#f8f1e9',
                            200: '#f0e0cf',
                            300: '#e4c9aa',
                            400: '#d5ad85',
                            500: '#c69c6d',
                            600: '#b38755',
                            700: '#96714a',
                            800: '#795c41',
                            900: '#5f4935',
                        }
                    },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: "Vazirmatn", system-ui, serif;
            background-color: #f8f1e9;
            color: #5f4935;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23d5ad85' fill-opacity='0.08' fill-rule='evenodd'/%3E%3C/svg%3E");
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-panel {
            background-color: #fcf9f5;
            border: 1px solid #d5ad85;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            max-width: 400px;
            width: 100%;
            padding: 2rem;
        }

        .btn-classic {
            background-color: #b38755;
            color: #fcf9f5;
            border: 1px solid #96714a;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-classic:hover {
            background-color: #96714a;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d5ad85;
            border-radius: 0.25rem;
            background-color: #fffcf0;
            color: #5f4935;
            font-size: 0.875rem;
        }

        .form-input:focus {
            outline: none;
            border-color: #b38755;
            box-shadow: 0 0 0 2px rgba(195, 151, 85, 0.2);
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #795c41;
            font-size: 0.875rem;
        }

        .error-message {
            color: #a83240;
            font-size: 0.75rem;
            margin-top: 0.25rem;
            text-align: right;
        }

        .status-message {
            background-color: #c6f6d5;
            color: #2f855a;
            padding: 0.75rem;
            margin-bottom: 1.5rem;
            border-radius: 0.25rem;
            border: 1px solid #68d391;
            font-size: 0.875rem;
            text-align: center;
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            color: #5f4935;
            font-size: 0.875rem;
        }

        .remember-me input {
            margin-left: 0.5rem;
        }

        .classic-title {
            font-family: "Vazirmatn", serif;
            color: #795c41;
            text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
<div class="login-panel">
    <h2 class="text-2xl font-bold classic-title">ورود به حساب کاربری</h2>

    <!-- Status Message -->
    @if (session('status'))
        <div class="status-message">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}">
        @csrf

        <!-- Email Address -->
        <div class="mb-5">
            <label for="email" class="form-label">آدرسه ایمیل</label>
            <input id="email"
                   class="form-input"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autofocus
                   autocomplete="username">

            @error('email')
            <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <!-- Password -->
        <div class="mb-5">
            <label for="password" class="form-label">رمز عبور</label>
            <input id="password"
                   class="form-input"
                   type="password"
                   name="password"
                   required
                   autocomplete="current-password">

            @error('password')
            <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="remember-me">
            <input id="remember_me"
                   type="checkbox"
                   name="remember">
            <label for="remember_me">مرا به خاطر بسپار</label>
        </div>

        <!-- Login Button -->
        <button type="submit" class="btn-classic w-full py-3 rounded text-sm flex items-center justify-center">
            <i class="fas fa-sign-in-alt ml-2"></i>
            ورود
        </button>
    </form>
</div>
</body>
</html>
