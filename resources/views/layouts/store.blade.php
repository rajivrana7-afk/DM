<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dazzle Drys Store Panel')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --dd-teal: #00796B; }
        body { background: #f5f5f5; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 230px; min-height: 100vh; background: var(--dd-teal); color: white; position: fixed; top: 0; left: 0; padding-top: 20px; }
        .sidebar .brand { padding: 10px 20px 20px; font-size: 1.2rem; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 10px 20px; display: flex; align-items: center; gap: 10px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background: rgba(255,255,255,0.15); }
        .main-content { margin-left: 230px; padding: 30px; }
        .topbar { background: white; padding: 15px 30px; margin: -30px -30px 30px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; }
        .stat-card { background: white; border-radius: 12px; padding: 20px; border-left: 4px solid var(--dd-teal); }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: var(--dd-teal); }
        .table-card { background: white; border-radius: 12px; padding: 20px; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="brand"><i class="bi bi-water me-2"></i>Store Panel</div>
    <nav class="mt-3">
        <a href="{{ route('store.dashboard') }}" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="{{ route('store.store.edit') }}" class="nav-link"><i class="bi bi-shop"></i> Store Profile</a>
        <a href="{{ route('store.timings') }}" class="nav-link"><i class="bi bi-clock"></i> Timings</a>
        <a href="{{ route('store.pricing') }}" class="nav-link"><i class="bi bi-currency-rupee"></i> Pricing</a>
        <a href="{{ route('store.slots') }}" class="nav-link"><i class="bi bi-calendar-plus"></i> Booking Slots</a>
        <a href="{{ route('store.bookings') }}" class="nav-link"><i class="bi bi-calendar-check"></i> Bookings</a>
    </nav>
    <div style="position:absolute;bottom:0;width:100%;padding:15px">
        <form method="POST" action="{{ route('store.logout') }}">
            @csrf
            <button class="btn btn-sm btn-outline-light w-100"><i class="bi bi-box-arrow-left me-1"></i>Logout</button>
        </form>
    </div>
</div>

<div class="main-content">
    <div class="topbar">
        <h5 class="mb-0 fw-bold">@yield('page-title', 'Dashboard')</h5>
        <span class="text-muted small">{{ auth('store_owner')->user()->name }}</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">@foreach($errors->all() as $e) {{ $e }}<br> @endforeach</div>
    @endif

    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
