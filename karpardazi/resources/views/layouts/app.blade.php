<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'داشبورد') — سامانه کارپردازی مجتمع خراسان</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/persian-datepicker/persian-datepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary no-print">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">سامانه کارپردازی مجتمع خراسان</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">داشبورد</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('documents.index') }}">اسناد</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('documents.create') }}">سند جدید</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('reports.index') }}">گزارش‌ها</a></li>
                @if(auth()->user()->isKarpardaz())
                    <li class="nav-item"><a class="nav-link" href="{{ route('tankhah.edit') }}">اطلاعات تنخواه</a></li>
                @endif
                @if(auth()->user()->isAdmin())
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">مدیریت</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.users.index') }}">کاربران</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.branches.index') }}">شعب</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.settings.edit') }}">تنظیمات عمومی</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.audit.index') }}">سوابق و تغییرات</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.backup.index') }}">پشتیبان‌گیری و بازیابی</a></li>
                        </ul>
                    </li>
                @endif
            </ul>
            <span class="navbar-text text-white-50 small ms-3">{{ auth()->user()->name }}</span>
            <form method="post" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button class="btn btn-outline-light btn-sm" type="submit">خروج</button>
            </form>
        </div>
    </div>
</nav>

<main class="container-fluid px-3 px-lg-4 pb-5">
    @if(session('success'))
        <div class="alert alert-success mt-3 no-print">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mt-3 no-print">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mt-3 no-print">
            <div class="fw-bold mb-1">لطفاً خطاهای زیر را برطرف کنید:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/persian/persian-date.min.js') }}"></script>
<script src="{{ asset('vendor/persian-datepicker/persian-datepicker.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
