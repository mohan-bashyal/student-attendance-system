<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success</title>
    <style>
        :root { --bg:#f8fafc; --panel:#fff; --line:#e2e8f0; --text:#0f172a; --muted:#475569; --primary:#0f766e; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Segoe UI","Manrope",sans-serif; background:linear-gradient(180deg,#ccfbf1 0%,var(--bg) 240px); color:var(--text); }
        .wrap { width:min(760px,100%); margin:40px auto; padding:16px; }
        .card { background:var(--panel); border:1px solid var(--line); border-radius:16px; padding:18px; }
        .btn { display:inline-block; margin-top:12px; border:0; border-radius:10px; padding:10px 14px; background:var(--primary); color:#fff; text-decoration:none; font-weight:700; }
        .muted { color:var(--muted); }
    </style>
</head>
<body>
<main class="wrap">
    <section class="card">
        <h1 style="margin:0 0 8px;">Payment Completed</h1>
        <p class="muted">Plan: <strong>{{ strtoupper($order->plan) }}</strong></p>
        <p class="muted">Order: {{ $order->order_uuid }}</p>
        <p class="muted">Your admin registration link is ready. Complete registration within 24 hours.</p>
        <a class="btn" href="{{ route('public.register.admin', ['token' => $order->registration_token]) }}">Open Admin Registration</a>
    </section>
</main>
</body>
</html>
