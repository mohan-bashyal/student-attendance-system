<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance OS Login</title>
    <style>
        :root {
            --bg: #0b172a;
            --bg-soft: #14233a;
            --panel: #ffffff;
            --text: #0f172a;
            --muted: #4b5563;
            --primary: #0ea5e9;
            --primary-strong: #0284c7;
            --ring: rgba(14, 165, 233, 0.25);
            --danger: #dc2626;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", "Manrope", sans-serif;
            color: #e2e8f0;
            background:
                radial-gradient(circle at 20% 20%, #1d4ed8 0%, transparent 35%),
                radial-gradient(circle at 80% 10%, #0ea5e9 0%, transparent 30%),
                linear-gradient(135deg, var(--bg) 0%, var(--bg-soft) 100%);
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .shell {
            width: min(1020px, 100%);
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(2, 6, 23, 0.45);
            background: rgba(15, 23, 42, 0.25);
            border: 1px solid rgba(148, 163, 184, 0.2);
        }
        .hero {
            padding: 40px;
            backdrop-filter: blur(6px);
        }
        .hero h1 {
            margin: 0 0 12px;
            font-size: clamp(28px, 4vw, 42px);
            line-height: 1.1;
        }
        .hero p {
            margin: 0;
            color: #cbd5e1;
            max-width: 38ch;
        }
        .role-list {
            margin-top: 28px;
            display: grid;
            gap: 10px;
        }
        .role-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #dbeafe;
            background: rgba(30, 41, 59, 0.45);
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 10px;
            padding: 10px 12px;
        }
        .form-wrap {
            background: var(--panel);
            color: var(--text);
            padding: 40px;
        }
        h2 {
            margin: 0 0 6px;
            font-size: 26px;
        }
        .muted { color: var(--muted); margin: 0 0 20px; }
        label {
            display: block;
            margin: 14px 0 6px;
            font-size: 14px;
            font-weight: 600;
        }
        input, select {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 12px;
            font-size: 15px;
            outline: none;
        }
        input:focus, select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--ring);
        }
        .actions {
            margin-top: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .btn {
            border: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), var(--primary-strong));
            color: #fff;
            padding: 12px 18px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }
        .error {
            color: var(--danger);
            font-size: 13px;
            margin-top: 8px;
        }
        .hint {
            margin-top: 18px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 13px;
            color: #1e3a8a;
        }
        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
            .hero { display: none; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <section class="hero">
            <h1>Student Attendance System</h1>
            <p>Secure, role-aware, and multi-school ready access for advanced attendance operations.</p>
            <div class="role-list">
                <div class="role-item">Super Admin - Multi-school governance</div>
                <div class="role-item">Admin - School-level operations</div>
                <div class="role-item">Teacher / Student / Parent / Staff workflows</div>
            </div>
        </section>
        <section class="form-wrap">
            <h2>Sign in</h2>
            <p class="muted">Use your school code for tenant-aware access.</p>

            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <label for="school_code">School Code (optional for Super Admin)</label>
                <input id="school_code" name="school_code" type="text" placeholder="e.g. ALPHA" value="{{ old('school_code') }}">
                @if ($errors->has('school_code'))
                    <div class="error">{{ $errors->first('school_code') }}</div>
                @endif

                <label for="email">Email</label>
                <input id="email" name="email" type="email" required value="{{ old('email') }}">
                @if ($errors->has('email'))
                    <div class="error">{{ $errors->first('email') }}</div>
                @endif

                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
                @if ($errors->has('password'))
                    <div class="error">{{ $errors->first('password') }}</div>
                @endif

                <div class="actions">
                    <label style="margin:0; display:flex; align-items:center; gap:8px; font-weight:500;">
                        <input type="checkbox" name="remember" value="1" style="width:auto;">
                        Remember me
                    </label>
                    <button class="btn" type="submit">Login</button>
                </div>
            </form>

            <div class="hint">
                Demo password for seeded accounts: <strong>password</strong>
                <br>
                School codes:
                @foreach ($schools as $school)
                    <span><strong>{{ $school->code }}</strong> ({{ $school->name }})</span>@if (! $loop->last), @endif
                @endforeach
            </div>
        </section>
    </div>
</body>
</html>
