<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $heading }}</title>
    <style>
        :root {
            --bg: #f1f5f9;
            --panel: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --primary: #0284c7;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", "Manrope", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 100% 0%, #bae6fd 0%, transparent 28%),
                radial-gradient(circle at 0% 100%, #bfdbfe 0%, transparent 24%),
                var(--bg);
            min-height: 100vh;
        }
        .wrapper {
            width: min(1180px, 100%);
            margin: 0 auto;
            padding: 28px 20px 36px;
        }
        .topbar {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 12px 35px rgba(15, 23, 42, 0.08);
        }
        .title h1 {
            margin: 0;
            font-size: clamp(20px, 3vw, 30px);
        }
        .title p {
            margin: 4px 0 0;
            color: var(--muted);
        }
        .meta {
            text-align: right;
            font-size: 14px;
            color: var(--muted);
        }
        .logout-btn {
            margin-top: 8px;
            border: 0;
            border-radius: 10px;
            padding: 10px 14px;
            color: #fff;
            background: linear-gradient(135deg, #0ea5e9, #0369a1);
            font-weight: 600;
            cursor: pointer;
        }
        .quick {
            margin: 18px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .quick a {
            text-decoration: none;
            border: 1px solid var(--border);
            color: #0f172a;
            background: #fff;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 14px;
        }
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 14px;
        }
        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }
        .icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            background: #e0f2fe;
            color: var(--primary);
            margin-bottom: 10px;
        }
        .card h3 {
            margin: 0 0 6px;
            font-size: 18px;
        }
        .card p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }
        svg { width: 20px; height: 20px; }
    </style>
</head>
<body>
    <main class="wrapper">
        <header class="topbar">
            <div class="title">
                <h1>{{ $heading }}</h1>
                <p>{{ $subheading }}</p>
            </div>
            <div class="meta">
                Signed in as <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->role }})
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout-btn" type="submit">Logout</button>
                </form>
            </div>
        </header>

        <section class="quick">
            <a href="{{ route('access.reports') }}">Permission: View Reports</a>
            <a href="{{ route('access.mark_attendance') }}">Permission: Mark Attendance</a>
        </section>

        <section class="cards">
            @foreach ($cards as $card)
                <article class="card">
                    <div class="icon" aria-hidden="true">
                        @switch($card['icon'])
                            @case('chart')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m7 14 4-4 3 3 6-6"/></svg>
                                @break
                            @case('megaphone')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 11 14-5v12L3 13z"/><path d="M11 14v6"/></svg>
                                @break
                            @case('building')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 22V4h16v18"/><path d="M9 22v-4h6v4"/></svg>
                                @break
                            @case('shield')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 4 7v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V7z"/></svg>
                                @break
                            @case('cog')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="m19.4 15 1.1 1.9-2 3.5-2.1-.5a7.7 7.7 0 0 1-1.6.9l-.3 2.2H9.5l-.3-2.2a7.7 7.7 0 0 1-1.6-.9l-2.1.5-2-3.5L4.6 15a8 8 0 0 1 0-2l-1.1-1.9 2-3.5 2.1.5a7.7 7.7 0 0 1 1.6-.9l.3-2.2h5l.3 2.2a7.7 7.7 0 0 1 1.6.9l2.1-.5 2 3.5-1.1 1.9a8 8 0 0 1 0 2z"/></svg>
                                @break
                            @case('users')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="3"/><path d="M20 8v6"/><path d="M23 11h-6"/></svg>
                                @break
                            @case('check')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m20 6-11 11-5-5"/></svg>
                                @break
                            @case('clipboard')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="2" width="6" height="4" rx="1"/><path d="M9 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3"/></svg>
                                @break
                            @case('calendar')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                @break
                            @case('bell')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10 20a2 2 0 0 0 4 0"/></svg>
                                @break
                            @case('heart')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 8.6A5.4 5.4 0 0 0 12 6a5.4 5.4 0 0 0-8.8 2.6c-.6 3.1 1.4 6 8.8 11.4 7.4-5.4 9.4-8.3 8.8-11.4z"/></svg>
                                @break
                            @case('message')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                @break
                            @case('desk')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="8" rx="1"/><path d="M7 14v4M17 14v4M3 18h18"/></svg>
                                @break
                            @case('document')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                @break
                            @default
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
                        @endswitch
                    </div>
                    <h3>{{ $card['title'] }}</h3>
                    <p>{{ $card['description'] }}</p>
                </article>
            @endforeach
        </section>
    </main>
</body>
</html>
