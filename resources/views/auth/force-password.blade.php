<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <style>
        :root { --bg:#0b172a; --panel:#ffffff; --text:#0f172a; --muted:#475569; --primary:#0369a1; --line:#cbd5e1; --error:#b91c1c; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Segoe UI","Manrope",sans-serif; color:#e2e8f0; min-height:100vh; display:grid; place-items:center; padding:20px; background:linear-gradient(135deg,#0f172a,#1e293b); }
        .card { width:min(520px,100%); background:var(--panel); color:var(--text); border-radius:14px; border:1px solid #e2e8f0; padding:18px; }
        h1 { margin:0 0 8px; font-size:24px; }
        p { margin:0 0 12px; color:var(--muted); }
        label { display:block; margin:10px 0 4px; font-size:13px; font-weight:600; color:var(--muted); }
        input { width:100%; border:1px solid var(--line); border-radius:9px; padding:10px; font-size:14px; }
        button { margin-top:12px; width:100%; border:0; border-radius:10px; padding:10px 12px; font-weight:700; background:var(--primary); color:#fff; cursor:pointer; }
        .error { margin-top:8px; color:var(--error); font-size:13px; }
    </style>
</head>
<body>
<section class="card">
    <h1>Change Your Password</h1>
    <p>{{ $user->name }} - This is a one-time password. Set a new password to continue.</p>

    <form method="POST" action="{{ route('password.force.update') }}">
        @csrf
        <label for="password">New Password</label>
        <input id="password" name="password" type="password" required minlength="8">

        <label for="password_confirmation">Confirm New Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8">

        @if ($errors->has('password'))
            <div class="error">{{ $errors->first('password') }}</div>
        @endif

        <button type="submit">Update Password</button>
    </form>
</section>
</body>
</html>
