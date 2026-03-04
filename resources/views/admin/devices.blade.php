<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Settings</title>
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
        .btn-outline { background:#fff; color:var(--text); border:1px solid var(--line); }
        .wrap { width:min(1240px,100%); margin:0 auto; padding:16px; }
        .section { background:var(--panel); border:1px solid var(--line); border-radius:14px; padding:14px; margin-bottom:12px; }
        .form-grid { display:grid; gap:10px; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); }
        label { display:block; font-size:12px; font-weight:600; color:var(--muted); margin-bottom:3px; }
        input { width:100%; border:1px solid #cbd5e1; border-radius:9px; padding:8px 9px; font-size:14px; background:#fff; }
        table { width:100%; border-collapse:collapse; margin-top:8px; font-size:13px; }
        th, td { text-align:left; padding:8px 6px; border-bottom:1px solid var(--line); vertical-align:top; }
        .status { font-weight:700; }
        .status-on { color:#166534; }
        .status-off { color:#991b1b; }
        .muted { color:var(--muted); }
        @media (max-width:980px) { .app-shell { grid-template-columns:1fr; } .sidebar { border-right:0; border-bottom:1px solid #0c4a6e; } }
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
            <strong>Device Settings (Enterprise)</strong>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn" type="submit">Logout</button></form>
        </header>
        <main class="wrap">
            @if(session('status'))
                <section class="section"><strong>{{ session('status') }}</strong></section>
            @endif
            @if(session('generated_device_token'))
                @php($deviceToken = session('generated_device_token'))
                <section class="section">
                    <strong>New Device Token</strong>
                    <p style="margin:8px 0 0;">
                        Device: {{ $deviceToken['name'] }}<br>
                        Token: <code>{{ $deviceToken['token'] }}</code>
                    </p>
                    <p class="muted" style="margin:8px 0 0;">Copy and keep this token safe. It will not be shown fully again.</p>
                </section>
            @endif

            @if(!($featureFlags['device_attendance'] ?? false))
                <section class="section">
                    <h2 style="margin:0;">Device attendance is locked</h2>
                    <p class="muted" style="margin:8px 0 0;">This school is not in ENTERPRISE plan. Ask super admin to upgrade subscription.</p>
                </section>
            @else
                <section class="section">
                    <h2 style="margin:0 0 8px;">Add Device</h2>
                    <form method="POST" action="{{ route('admin.school_devices.store') }}">
                        @csrf
                        <div class="form-grid">
                            <div>
                                <label>Device Name</label>
                                <input type="text" name="name" required placeholder="Main Gate Scanner">
                            </div>
                            <div>
                                <label>Device Code</label>
                                <input type="text" name="device_code" placeholder="GATE-01">
                            </div>
                        </div>
                        <p style="margin:10px 0 0;"><button type="submit" class="btn">Add Device</button></p>
                    </form>
                </section>

                <section class="section">
                    <h2 style="margin:0 0 8px;">Device List</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Status</th>
                                <th>Realtime</th>
                                <th>Last Event</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($devices as $device)
                            <tr>
                                <td>{{ $device->name }}</td>
                                <td>{{ $device->device_code ?: '-' }}</td>
                                <td class="status {{ $device->is_active ? 'status-on' : 'status-off' }}">{{ $device->is_active ? 'Active' : 'Inactive' }}</td>
                                <td>
                                    @php($isOnline = $device->last_seen_at && $device->last_seen_at->gte(now()->subMinutes(2)))
                                    @if($isOnline)
                                        <span class="status status-on">Online</span><br>
                                    @else
                                        <span class="status status-off">Offline</span><br>
                                    @endif
                                    <span class="muted">{{ $device->last_seen_at?->format('Y-m-d H:i:s') ?: 'Never' }}</span>
                                </td>
                                <td>
                                    <span class="status">{{ $device->last_event_status ?: '-' }}</span><br>
                                    <span class="muted">{{ $device->last_event_message ?: '-' }}</span>
                                </td>
                                <td>{{ $device->created_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.school_devices.status', $device) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-outline">{{ $device->is_active ? 'Deactivate' : 'Activate' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="muted">No devices added yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </section>
            @endif
        </main>
    </div>
</div>
</body>
</html>
