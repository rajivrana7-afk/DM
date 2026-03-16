<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Login – Dazzle Drys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #00796B 0%, #004D40 100%); min-height: 100vh; display: flex; align-items: center; }
        .login-card { background: white; border-radius: 16px; padding: 40px; max-width: 420px; width: 100%; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="login-card shadow-lg">
                <div class="text-center mb-4">
                    <h3 class="fw-bold" style="color:#00796B">💧 Dazzle Drys</h3>
                    <p class="text-muted">Store Owner Login</p>
                </div>

                @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
                @if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

                <form method="POST" action="{{ route('store.login') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn w-100 text-white" style="background:#00796B">Login</button>
                </form>
                <div class="text-center mt-3">
                    <a href="{{ route('store.register') }}" class="text-decoration-none">New store? Register here</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
