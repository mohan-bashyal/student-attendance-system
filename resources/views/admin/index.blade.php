<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        :root { --bg:#f8fafc; --panel:#fff; --text:#0f172a; --muted:#475569; --line:#e2e8f0; --primary:#0369a1; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Segoe UI","Manrope",sans-serif; color:var(--text); background:linear-gradient(180deg,#e0f2fe 0%,var(--bg) 180px); }
        .app-shell { min-height:100vh; display:grid; grid-template-columns:260px 1fr; }
        .sidebar { background:#082f49; color:#e0f2fe; padding:20px 14px; border-right:1px solid #0c4a6e; }
        .brand { font-size:18px; font-weight:700; margin-bottom:4px; }
        .brand-sub { color:#bae6fd; font-size:13px; margin-bottom:18px; }
        .nav-group { display:grid; gap:8px; }
        .nav-link { color:#e0f2fe; text-decoration:none; padding:9px 10px; border-radius:9px; display:block; background:rgba(186,230,253,0.08); }
        .nav-link:hover { background:rgba(186,230,253,0.18); }
        .main-area { min-width:0; }
        .topbar { position:sticky; top:0; z-index:20; background:#ffffffd9; backdrop-filter:blur(6px); border-bottom:1px solid var(--line); padding:12px 16px; display:flex; align-items:center; justify-content:space-between; gap:10px; }
        .btn, button { border:0; border-radius:10px; padding:9px 13px; font-weight:600; cursor:pointer; }
        .btn { background:var(--primary); color:#fff; text-decoration:none; }
        .wrap { width:min(1240px,100%); margin:0 auto; padding:16px; }
        .grid { display:grid; gap:12px; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); }
        .card { background:var(--panel); border:1px solid var(--line); border-radius:14px; padding:14px; }
        .metric { margin-top:6px; font-size:30px; font-weight:700; }
        .muted { color:var(--muted); }
        .quick { margin-top:12px; display:grid; gap:12px; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); }
        .quick a { display:block; text-decoration:none; color:var(--text); border:1px solid var(--line); border-radius:12px; background:#fff; padding:12px; font-weight:600; }
        .notify-list { margin-top:12px; display:grid; gap:10px; }
        .notify-item { border:1px solid var(--line); border-radius:12px; background:#fff; padding:10px 12px; }
        .top-right { display:flex; align-items:center; gap:10px; }
        .bell { position:relative; border:1px solid var(--line); border-radius:10px; padding:7px 10px; background:#fff; font-weight:700; }
        .bell-count { position:absolute; top:-8px; right:-8px; background:#ef4444; color:#fff; border-radius:999px; min-width:20px; height:20px; font-size:12px; display:flex; align-items:center; justify-content:center; }
        .btn-outline { background:#fff; border:1px solid var(--line); color:var(--text); }
        .btn-danger { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
        .form-inline { display:flex; gap:6px; flex-wrap:wrap; align-items:center; margin-top:8px; }
        .form-inline input { border:1px solid #cbd5e1; border-radius:8px; padding:7px 8px; min-width:180px; }
        .status { border:1px solid #bbf7d0; background:#f0fdf4; color:#166534; padding:10px 12px; border-radius:10px; margin-bottom:10px; }
        .error { border:1px solid #fecaca; background:#fef2f2; color:#991b1b; padding:10px 12px; border-radius:10px; margin-bottom:10px; }
        @media (max-width: 980px) { .app-shell { grid-template-columns:1fr; } .sidebar { border-right:0; border-bottom:1px solid #0c4a6e; } }
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">Admin Panel</div>
        <div class="brand-sub">{{ $school?->name ?? 'School' }}</div>
        <nav class="nav-group">
            <a class="nav-link" href="{{ route('admin.index') }}">Overview</a>
            <a class="nav-link" href="{{ route('admin.class_sections.index') }}">Class & Section CRUD</a>
            <a class="nav-link" href="{{ route('admin.teachers.index') }}">Teacher Management</a>
            <a class="nav-link" href="{{ route('admin.students.index') }}">Student Management</a>
            <a class="nav-link" href="{{ route('admin.attendance.index') }}">Attendance</a>
            <a class="nav-link" href="{{ route('admin.holidays.index') }}">Holiday Calendar</a>
            <a class="nav-link" href="{{ route('admin.devices.index') }}">Device Settings</a>
        </nav>
    </aside>
    <div class="main-area">
        <header class="topbar">
            <strong>{{ auth()->user()->name }} (admin)</strong>
            <div class="top-right">
                <div class="bell" title="Pending student approval requests">
                    🔔
                    <span class="bell-count">{{ $pendingStudentChangeRequests->count() }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn" type="submit">Logout</button></form>
            </div>
        </header>
        <main class="wrap">
            @if (session('status')) <div class="status">{{ session('status') }}</div> @endif
            @if ($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif
            <h1 style="margin:0;">Admin Dashboard</h1>
            <p class="muted" style="margin-top:4px;">Choose management module from sidebar.</p>
            <section class="grid">
                <article class="card"><p class="muted">Total Students</p><p class="metric">{{ $totalStudents }}</p></article>
                <article class="card"><p class="muted">Total Teachers</p><p class="metric">{{ $totalTeachers }}</p></article>
                <article class="card"><p class="muted">Classes</p><p class="metric">{{ $classes->count() }}</p></article>
                <article class="card"><p class="muted">Sections</p><p class="metric">{{ $sections->count() }}</p></article>
            </section>
            <section class="quick">
                <a href="{{ route('admin.class_sections.index') }}">Open Class & Section CRUD</a>
                <a href="{{ route('admin.teachers.index') }}">Open Teacher Management</a>
                <a href="{{ route('admin.students.index') }}">Open Student Management</a>
                <a href="{{ route('admin.attendance.index') }}">Open Attendance Module</a>
                <a href="{{ route('admin.devices.index') }}">Open Device Settings</a>
            </section>
            <section class="card" style="margin-top:12px;">
                <h2 style="margin:0;">Attendance Notifications</h2>
                @if($featureFlags['admin_attendance_notifications'] ?? false)
                    <p class="muted" style="margin:4px 0 0;">Class teachers ko latest attendance submission updates.</p>
                    <div class="notify-list">
                        @forelse($attendanceNotifications as $notification)
                            <article class="notify-item">
                                <strong>{{ $notification->class_name }} - {{ $notification->section_name }}</strong>
                                <p style="margin:6px 0 0;">{{ $notification->message }}</p>
                                <p class="muted" style="margin:6px 0 0;font-size:13px;">
                                    Students: {{ $notification->total_students }} | Updated: {{ $notification->updated_at?->format('Y-m-d H:i') }}
                                </p>
                            </article>
                        @empty
                            <p class="muted" style="margin:8px 0 0;">No attendance notifications yet.</p>
                        @endforelse
                    </div>
                @else
                    <p class="muted" style="margin:8px 0 0;">Attendance notifications are locked in BASIC plan.</p>
                @endif
            </section>

            <section class="card" style="margin-top:12px;">
                <h2 style="margin:0;">Student Change Requests (Teacher -> Admin Approval)</h2>
                <p class="muted" style="margin:4px 0 0;">Class teacher ले पठाएको create/edit/delete request approve गरेपछि मात्र live data update हुन्छ।</p>
                <div class="notify-list">
                    @forelse($pendingStudentChangeRequests as $requestItem)
                        <article class="notify-item">
                            <strong>#{{ $requestItem->id }} | {{ strtoupper($requestItem->action) }} | {{ $requestItem->teacher?->name }}</strong>
                            <p style="margin:6px 0 0;">
                                Class: {{ $requestItem->schoolClass?->name }} - {{ $requestItem->section?->name }}<br>
                                Student: {{ $requestItem->student?->name ?? ($requestItem->payload['name'] ?? '-') }}
                            </p>
                            <div class="form-inline">
                                <form method="POST" action="{{ route('admin.student_change_requests.approve', $requestItem) }}">
                                    @csrf
                                    <button class="btn btn-outline" type="submit">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.student_change_requests.reject', $requestItem) }}">
                                    @csrf
                                    <input type="text" name="review_note" placeholder="Reject reason (optional)">
                                    <button class="btn-danger" type="submit">Reject</button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <p class="muted" style="margin:8px 0 0;">No pending student requests.</p>
                    @endforelse
                </div>
            </section>
        </main>
    </div>
</div>
</body>
</html>
