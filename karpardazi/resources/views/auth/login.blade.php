<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود — سامانه کارپردازی مجتمع خراسان</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="d-flex align-items-center" style="min-height: 100vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card kpi-card">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <img src="{{ asset('images/logo.png') }}" alt="لوگو" style="height: 72px;">
                        <h5 class="mt-3 fw-bold">سامانه کارپردازی مجتمع خراسان</h5>
                        <div class="text-muted small">شرکت انبارهای عمومی و خدمات گمرکی ایران</div>
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger py-2">
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form method="post" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">نام کاربری</label>
                            <input type="text" name="username" class="form-control" value="{{ old('username') }}" required autofocus autocomplete="username" dir="ltr">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">رمز عبور</label>
                            <input type="password" name="password" class="form-control" required autocomplete="current-password" dir="ltr">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label" for="remember">مرا به خاطر بسپار</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">ورود</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
