<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – Dazzle Drys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #00796B 0%, #004D40 100%); min-height: 100vh; display: flex; align-items: center; }
        .card-box { background: white; border-radius: 16px; padding: 40px; max-width: 460px; width: 100%; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card-box shadow-lg">
                <div class="text-center mb-4">
                    <h3 class="fw-bold" style="color:#00796B">💧 Dazzle Drys</h3>
                    <p class="text-muted">Register your store</p>
                </div>

                @if($errors->any()) <div class="alert alert-danger">@foreach($errors->all() as $e) {{ $e }}<br> @endforeach</div> @endif

                <form method="POST" action="{{ route('store.register') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Your Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn w-100 text-white" style="background:#00796B">Create Account</button>
                </form>
                <div class="text-center mt-3">
                    <a href="{{ route('store.login') }}" class="text-decoration-none">Already have an account? Login</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
