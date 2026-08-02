<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <title>ورود به مدیریت | ناظر یدک</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/ico" href="/favicon.ico"/>
    <link rel="stylesheet" href="/assets/css/bootstrap.rtl.css">
    <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
    <style>
        * { font-family: vazir-fa-med, Tahoma, sans-serif; }
        @font-face {
            font-family: vazir-fa-med;
            src: url('/assets/font/IRANSans/IRANSansWeb(FaNum).woff') format('woff');
        }
        body {
            background: linear-gradient(135deg, #002f5b 0%, #00457c 50%, #0066b3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 420px;
            width: 100%;
            padding: 40px 35px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo img {
            height: 50px;
            margin-bottom: 12px;
        }
        .login-logo h5 {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a2e;
            margin: 0;
        }
        .login-logo p {
            font-size: 0.8rem;
            color: #777;
            margin: 5px 0 0;
        }
        .form-label { font-size: 0.85rem; color: #555; font-weight: 600; }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #dde4ec;
            font-size: 0.9rem;
        }
        .form-control:focus {
            border-color: #00457c;
            box-shadow: 0 0 0 3px rgba(0,69,124,0.1);
        }
        .btn-login {
            background: linear-gradient(135deg, #00457c, #0066b3);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #002f5b, #00457c);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0,69,124,0.3);
        }
        .alert { font-size: 0.82rem; border-radius: 8px; }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 0.9rem;
        }
        .input-icon input {
            padding-left: 40px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <img src="/assets/images/logo.png" alt="ناظر یدک">
            <h5>پنل مدیریت ناظر یدک</h5>
            <p>نام کاربری و رمز عبور خود را وارد کنید</p>
        </div>

        <form method="POST" action="/loginAdmin">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="username"><i class="fas fa-user me-1"></i> نام کاربری</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" id="username" class="form-control" placeholder="نام کاربری" autocomplete="off" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password"><i class="fas fa-lock me-1"></i> رمز عبور</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" class="form-control" placeholder="رمز عبور" required>
                </div>
            </div>

            @if ($errors->any())
            <div class="alert alert-danger py-2">
                @foreach ($errors->all() as $error)
                    <p class="mb-0">{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-1"></i> ورود به پنل
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="/" style="font-size:0.8rem; color:#00457c;">
                <i class="fas fa-arrow-right me-1"></i> بازگشت به سایت
            </a>
        </div>
    </div>
</body>
</html>
