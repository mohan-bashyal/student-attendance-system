<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Console</title>
    <style>
        :root {
            --bg: #f8fafc;
            --panel: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --line: #e2e8f0;
            --primary: #0f766e;
            --warn: #b45309;
            --danger: #b91c1c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", "Manrope", sans-serif;
            background: linear-gradient(180deg, #ecfeff 0%, var(--bg) 180px);
            color: var(--text);
        }
        .app-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 260px 1fr;
        }
        .sidebar {
            background: #134e4a;
            color: #ccfbf1;
            border-right: 1px solid #115e59;
            padding: 20px 14px;
        }
        .brand {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .brand-sub {
            color: #99f6e4;
            font-size: 13px;
            margin-bottom: 18px;
        }
        .nav-group {
            display: grid;
            gap: 8px;
        }
        .nav-link {
            color: #ccfbf1;
            text-decoration: none;
            padding: 9px 10px;
            border-radius: 9px;
            display: block;
            background: rgba(153, 246, 228, 0.1);
        }
        .nav-link:hover {
            background: rgba(153, 246, 228, 0.2);
        }
        .main-area {
            min-width: 0;
        }
        .topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: #ffffffd9;
            backdrop-filter: blur(6px);
            border-bottom: 1px solid var(--line);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .wrap {
            width: min(1200px, 100%);
            margin: 0 auto;
            padding: 16px;
        }
        .top {
            display: flex;
            gap: 16px;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        .top h1 {
            margin: 0;
            font-size: clamp(22px, 4vw, 34px);
        }
        .top p {
            margin: 6px 0 0;
            color: var(--muted);
        }
        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn, button {
            border: 0;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn {
            text-decoration: none;
            color: #fff;
            background: var(--primary);
        }
        .btn-outline {
            background: #fff;
            border: 1px solid var(--line);
            color: var(--text);
        }
        .grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
        .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px;
        }
        .metric {
            font-size: 30px;
            font-weight: 700;
            margin: 4px 0 0;
        }
        .muted { color: var(--muted); }
        .section {
            margin-top: 14px;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px;
        }
        .section h2 {
            margin: 0 0 10px;
            font-size: 19px;
        }
        form.inline {
            display: inline;
        }
        .form-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        }
        label {
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            display: block;
            margin-bottom: 4px;
        }
        input, select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            padding: 9px 10px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 14px;
        }
        th, td {
            padding: 10px 8px;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
            text-align: left;
        }
        .badge {
            border-radius: 999px;
            padding: 3px 10px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .ok { background: #dcfce7; color: #166534; }
        .off { background: #fee2e2; color: #991b1b; }
        .trial { background: #fef3c7; color: #92400e; }
        .danger { color: var(--danger); }
        .warning { color: var(--warn); }
        .status-box {
            border: 1px solid #bbf7d0;
            color: #166534;
            background: #f0fdf4;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 10px;
        }
        @media (max-width: 980px) {
            .app-shell {
                grid-template-columns: 1fr;
            }
            .sidebar {
                border-right: 0;
                border-bottom: 1px solid #115e59;
            }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">Super Admin</div>
            <div class="brand-sub">Platform Console</div>
            <nav class="nav-group">
                <a class="nav-link" href="#overview">Overview</a>
                <a class="nav-link" href="#create-school">Create School</a>
                <a class="nav-link" href="#create-admin">Create School Admin</a>
                <a class="nav-link" href="#reports">System Reports</a>
            </nav>
        </aside>
        <div class="main-area">
            <header class="topbar">
                <strong>{{ auth()->user()->name }} (super_admin)</strong>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn">Logout</button>
                </form>
            </header>
    <main class="wrap">
        <header class="top" id="overview">
            <div>
                <h1>Super Admin Console</h1>
                <p>Platform-level school operations, reporting, and subscription management.</p>
            </div>
        </header>

        @if (session('status'))
            <div class="status-box">{{ session('status') }}</div>
        @endif
        @if (session('generated_device_token'))
            @php($deviceToken = session('generated_device_token'))
            <div class="status-box">
                <strong>Device Token Generated</strong><br>
                Device: {{ $deviceToken['name'] }}<br>
                Token: <code>{{ $deviceToken['token'] }}</code><br>
                Use this in API header: <code>X-Device-Token</code>
            </div>
        @endif

        <section class="grid">
            <article class="card">
                <p class="muted">Total Schools</p>
                <p class="metric">{{ $totalSchools }}</p>
            </article>
            <article class="card">
                <p class="muted">Total Students</p>
                <p class="metric">{{ $totalStudents }}</p>
            </article>
            <article class="card">
                <p class="muted">Signed In</p>
                <p class="metric" style="font-size: 20px;">{{ auth()->user()->name }}</p>
                <p class="muted">Role: {{ auth()->user()->role }}</p>
            </article>
        </section>

        <section class="section" id="create-school">
            <h2>Create School</h2>
            <p class="muted" style="margin:0 0 10px;">BASIC and PRO plans are enabled. BASIC enforces default 500 students.</p>
            <form method="POST" action="{{ route('super_admin.schools.store') }}">
                @csrf
                <div class="form-grid">
                    <div>
                        <label for="school_name">School Name</label>
                        <input id="school_name" name="name" type="text" value="{{ old('name') }}" required>
                    </div>
                    <div>
                        <label for="school_code">School Code</label>
                        <input id="school_code" name="code" type="text" value="{{ old('code') }}" placeholder="ALPHA" required>
                    </div>
                    <div>
                        <label for="school_domain">Domain</label>
                        <input id="school_domain" name="domain" type="text" value="{{ old('domain') }}" placeholder="school.example.com">
                    </div>
                    <div>
                        <label for="plan">Subscription Plan</label>
                        <select id="plan" name="subscription_plan" data-plan-select required>
                            @foreach ($planOptions as $plan)
                                <option value="{{ $plan }}" @selected(old('subscription_plan', 'basic') === $plan)>{{ strtoupper($plan) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="sub_status">Subscription Status</label>
                        <select id="sub_status" name="subscription_status" required>
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}" @selected(old('subscription_status', 'trial') === $status)>{{ strtoupper($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="sub_ends">Subscription End Date</label>
                        <input id="sub_ends" name="subscription_ends_at" type="date" value="{{ old('subscription_ends_at') }}">
                    </div>
                    <div>
                        <label for="max_students">Max Students</label>
                        <input id="max_students" name="max_students" data-max-students type="number" min="1" value="{{ old('max_students') }}">
                    </div>
                </div>
                <p style="margin-top: 10px;">
                    <button type="submit" class="btn">Create School</button>
                </p>
            </form>
            @if ($errors->any())
                <p class="danger">{{ $errors->first() }}</p>
            @endif
        </section>

        <section class="section" id="create-admin">
            <h2>Create School Admin</h2>
            <form method="POST" action="{{ route('super_admin.school_admins.store') }}">
                @csrf
                <div class="form-grid">
                    <div>
                        <label for="admin_school">School</label>
                        <select id="admin_school" name="school_id" required>
                            <option value="">Select School</option>
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}" @selected((string) old('school_id') === (string) $school->id)>
                                    {{ $school->name }} ({{ $school->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="admin_name">Admin Name</label>
                        <input id="admin_name" name="name" type="text" value="{{ old('name') }}" required>
                    </div>
                    <div>
                        <label for="admin_email">Admin Email</label>
                        <input id="admin_email" name="email" type="email" value="{{ old('email') }}" required>
                    </div>
                    <div>
                        <label for="admin_password">Temporary Password</label>
                        <input id="admin_password" name="password" type="password" minlength="8" required>
                    </div>
                </div>
                <p style="margin-top: 10px;">
                    <button type="submit" class="btn">Create School Admin</button>
                </p>
            </form>
        </section>

        <section class="section" id="reports">
            <h2>System-wide Reports</h2>
            <table>
                <thead>
                    <tr>
                        <th>School</th>
                        <th>Code</th>
                        <th>Status</th>
                        <th>Students</th>
                        <th>Admins</th>
                        <th>Subscription</th>
                        <th>Ends</th>
                        <th>Max Students</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schools as $school)
                        <tr>
                            <td>{{ $school->name }}</td>
                            <td>{{ $school->code }}</td>
                            <td>
                                <span class="badge {{ $school->is_active ? 'ok' : 'off' }}">
                                    {{ $school->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $school->students_count }}</td>
                            <td>{{ $school->admins_count }}</td>
                            <td>
                                <span class="badge {{ $school->subscription_status === 'trial' ? 'trial' : 'ok' }}">
                                    {{ strtoupper($school->subscription_plan) }} / {{ strtoupper($school->subscription_status) }}
                                </span>
                            </td>
                            <td>{{ optional($school->subscription_ends_at)->format('Y-m-d') ?? '-' }}</td>
                            <td>{{ $school->max_students ?? '-' }}</td>
                            <td>
                                <form class="inline" method="POST" action="{{ route('super_admin.schools.status', $school) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline {{ $school->is_active ? 'warning' : '' }}">
                                        {{ $school->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9">
                                <form method="POST" action="{{ route('super_admin.schools.subscription', $school) }}">
                                    @csrf
                                    @method('PATCH')
                                    <div class="form-grid">
                                        <div>
                                            <label>Plan</label>
                                            <select name="subscription_plan" data-plan-select required>
                                                @foreach ($planOptions as $plan)
                                                    <option value="{{ $plan }}" @selected($school->subscription_plan === $plan)>{{ strtoupper($plan) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label>Status</label>
                                            <select name="subscription_status" required>
                                                @foreach ($statusOptions as $status)
                                                    <option value="{{ $status }}" @selected($school->subscription_status === $status)>{{ strtoupper($status) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label>Ends At</label>
                                            <input name="subscription_ends_at" type="date" value="{{ optional($school->subscription_ends_at)->format('Y-m-d') }}">
                                        </div>
                                        <div>
                                            <label>Max Students</label>
                                            <input name="max_students" data-max-students type="number" min="1" value="{{ $school->max_students }}">
                                        </div>
                                        <div style="display:flex; align-items:flex-end;">
                                            <button type="submit" class="btn">Update Subscription</button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="muted">No schools found yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="section" id="devices">
            <h2>Enterprise Device Management</h2>
            <form method="POST" action="{{ route('super_admin.school_devices.store') }}">
                @csrf
                <div class="form-grid">
                    <div>
                        <label>School</label>
                        <select name="school_id" required>
                            <option value="">Select School</option>
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}">{{ $school->name }} ({{ strtoupper($school->subscription_plan) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Device Name</label>
                        <input name="name" type="text" required placeholder="Main Gate Biometric">
                    </div>
                    <div>
                        <label>Device Code</label>
                        <input name="device_code" type="text" placeholder="GATE-01">
                    </div>
                    <div style="display:flex;align-items:flex-end;">
                        <button type="submit" class="btn">Create Device Token</button>
                    </div>
                </div>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>School</th>
                        <th>Device</th>
                        <th>Code</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($devices as $device)
                        <tr>
                            <td>{{ $device->school?->name }}</td>
                            <td>{{ $device->name }}</td>
                            <td>{{ $device->device_code ?? '-' }}</td>
                            <td>{{ $device->is_active ? 'Active' : 'Inactive' }}</td>
                            <td>
                                <form method="POST" action="{{ route('super_admin.school_devices.status', $device) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline">{{ $device->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted">No school devices created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </main>
        </div>
    </div>
<script>
document.querySelectorAll('form').forEach((form) => {
    const planSelect = form.querySelector('[data-plan-select]');
    const maxStudents = form.querySelector('[data-max-students]');
    if (!planSelect || !maxStudents) {
        return;
    }

    const syncMaxStudents = () => {
        if (planSelect.value === 'basic') {
            maxStudents.value = '500';
            maxStudents.readOnly = true;
        } else if (planSelect.value === 'pro') {
            if (!maxStudents.value || maxStudents.value === '500') {
                maxStudents.value = '2000';
            }
            maxStudents.readOnly = false;
        } else if (planSelect.value === 'enterprise') {
            if (!maxStudents.value || maxStudents.value === '500' || maxStudents.value === '2000') {
                maxStudents.value = '10000';
            }
            maxStudents.readOnly = false;
        } else {
            maxStudents.readOnly = false;
        }
    };

    planSelect.addEventListener('change', syncMaxStudents);
    syncMaxStudents();
});
</script>
</body>
</html>
