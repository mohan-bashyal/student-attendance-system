<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <style>
        :root { --bg:#f8fafc; --panel:#fff; --line:#e2e8f0; --text:#0f172a; --muted:#475569; --primary:#1d4ed8; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Segoe UI","Manrope",sans-serif; background:linear-gradient(180deg,#dbeafe 0%,var(--bg) 220px); color:var(--text); }
        .wrap { width:min(920px,100%); margin:30px auto; padding:16px; }
        .card { background:var(--panel); border:1px solid var(--line); border-radius:16px; padding:16px; margin-bottom:12px; }
        .form-grid { display:grid; gap:10px; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); }
        label { display:block; font-size:12px; color:var(--muted); font-weight:700; margin-bottom:4px; }
        input { width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:9px 10px; font-size:14px; }
        button { border:0; border-radius:10px; padding:10px 14px; background:var(--primary); color:#fff; font-weight:700; cursor:pointer; }
        .muted { color:var(--muted); }
        .error { border:1px solid #fecaca; background:#fef2f2; color:#991b1b; border-radius:10px; padding:10px 12px; margin-bottom:10px; }
        .tag { display:inline-block; padding:3px 10px; border-radius:999px; background:#dbeafe; color:#1e3a8a; font-size:12px; font-weight:700; }
    </style>
</head>
<body>
<main class="wrap">
    <section class="card">
        <h1 style="margin:0 0 8px;">Create Your Admin Account</h1>
        <p class="muted">
            Plan unlocked: <span class="tag">{{ strtoupper($selectedPlan) }}</span>
            | Token expires: {{ $order->token_expires_at?->format('Y-m-d H:i') }}
        </p>
    </section>

    @if($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <section class="card">
        <form method="POST" action="{{ route('public.register.admin.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="form-grid">
                <div>
                    <label>School Name</label>
                    <input name="school_name" required value="{{ old('school_name') }}">
                </div>
                <div>
                    <label>School Code (Unique)</label>
                    <input name="school_code" required value="{{ old('school_code') }}" placeholder="ALPHA">
                </div>
                <div>
                    <label>School Domain (Optional)</label>
                    <input name="school_domain" value="{{ old('school_domain') }}" placeholder="alpha.school.local">
                </div>
                <div>
                    <label>Admin Name</label>
                    <input name="admin_name" required value="{{ old('admin_name') }}">
                </div>
                <div>
                    <label>Admin Email</label>
                    <input type="email" name="admin_email" required value="{{ old('admin_email') }}">
                </div>
                <div>
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div>
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirmation" required>
                </div>
            </div>
            <p style="margin:12px 0 0;"><button type="submit">Create School And Admin</button></p>
        </form>
    </section>
</main>
</body>
</html>
