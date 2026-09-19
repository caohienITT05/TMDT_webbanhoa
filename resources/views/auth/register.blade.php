<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản - BloomGift</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #fff0f3 0%, #fde2e4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 25px 20px;
        }
        .auth-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(232, 93, 117, 0.15);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
            border: 1px solid #ffe5ec;
        }
        .auth-header {
            background: linear-gradient(135deg, #ff758f 0%, #ff4d6d 100%);
            padding: 30px 25px;
            text-align: center;
            color: #ffffff;
        }
        .auth-header h3 {
            font-weight: 700;
            margin-bottom: 5px;
        }
        .auth-header p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 0;
        }
        .auth-body {
            padding: 30px 30px;
        }
        .form-label {
            font-weight: 600;
            font-size: 0.88rem;
            color: #59253a;
        }
        .input-group-text {
            background-color: #fff0f3;
            border-color: #ffccd5;
            color: #c9184a;
        }
        .form-control {
            border-color: #ffccd5;
            padding: 10px 14px;
        }
        .form-control:focus {
            border-color: #ff758f;
            box-shadow: 0 0 0 0.25rem rgba(255, 117, 143, 0.25);
        }
        .btn-bloom {
            background: linear-gradient(135deg, #ff758f 0%, #ff4d6d 100%);
            color: #ffffff;
            font-weight: 600;
            border: none;
            padding: 12px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-bloom:hover {
            background: linear-gradient(135deg, #ff4d6d 0%, #c9184a 100%);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(201, 24, 74, 0.3);
        }
        .auth-footer a {
            color: #ff4d6d;
            text-decoration: none;
            font-weight: 600;
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="auth-header">
        <h3>🌸 Tạo tài khoản mới</h3>
        <p>Gia nhập BloomGift để nhận thông tin ưu đãi sớm nhất</p>
    </div>

    <div class="auth-body">
        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 mb-3 small" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <!-- Họ và tên -->
            <div class="mb-3">
                <label for="name" class="form-label">Họ và tên</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="name" id="name" class="form-control" 
                           value="{{ old('name') }}" placeholder="Ví dụ: Cao Hiền" required autofocus>
                </div>
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label">Địa chỉ Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" id="email" class="form-control" 
                           value="{{ old('email') }}" placeholder="name@example.com" required>
                </div>
            </div>

            <!-- Mật khẩu -->
            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" 
                           placeholder="Tối thiểu 8 ký tự" required autocomplete="new-password">
                </div>
            </div>

            <!-- Xác nhận mật khẩu -->
            <div class="mb-4">
                <label for="password_confirmation" class="form-label">Xác nhận mật khẩu</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                    <input type="password" name="password_confirmation" id="password_confirmation" 
                           class="form-control" placeholder="Nhập lại mật khẩu" required>
                </div>
            </div>

            <!-- Nút đăng ký -->
            <button type="submit" class="btn btn-bloom w-100 mb-3">
                <i class="bi bi-person-plus me-1"></i> Hoàn tất đăng ký
            </button>

            <!-- Chuyển sang đăng nhập -->
            <div class="text-center auth-footer pt-2 border-top">
                <span class="small text-muted">Đã có tài khoản?</span>
                <a href="{{ route('login') }}" class="small ms-1">Đăng nhập</a>
                <div class="mt-2">
                    <a href="{{ route('home') }}" class="small text-secondary"><i class="bi bi-arrow-left"></i> Quay lại trang chủ</a>
                </div>
            </div>
        </form>
    </div>
</div>

</body>
</html>