<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Student Requests</title>
    <style>
        :root { --bg:#f8fafc; --panel:#fff; --text:#0f172a; --muted:#475569; --line:#e2e8f0; --primary:#0f766e; --danger:#991b1b; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Segoe UI","Manrope",sans-serif; color:var(--text); background:linear-gradient(180deg,#ccfbf1 0%,var(--bg) 180px); }
        .app-shell { min-height:100vh; display:grid; grid-template-columns:260px 1fr; }
        .sidebar { background:#134e4a; color:#ccfbf1; padding:20px 14px; border-right:1px solid #0f766e; }
        .brand { font-size:18px; font-weight:700; margin-bottom:4px; }
        .brand-sub { color:#99f6e4; font-size:13px; margin-bottom:18px; }
        .nav-group { display:grid; gap:8px; }
        .nav-link { color:#ccfbf1; text-decoration:none; padding:9px 10px; border-radius:9px; display:block; background:rgba(153,246,228,0.12); }
        .nav-link:hover { background:rgba(153,246,228,0.22); }
        .main-area { min-width:0; }
        .topbar { position:sticky; top:0; z-index:20; background:#ffffffd9; backdrop-filter:blur(6px); border-bottom:1px solid var(--line); padding:12px 16px; display:flex; align-items:center; justify-content:space-between; gap:10px; }
        .btn, button { border:0; border-radius:10px; padding:8px 12px; font-weight:600; cursor:pointer; }
        .btn { background:var(--primary); color:#fff; text-decoration:none; }
        .btn-outline { background:#fff; border:1px solid var(--line); color:var(--text); }
        .btn-danger { background:#fee2e2; color:var(--danger); border:1px solid #fecaca; }
        .wrap { width:min(1280px,100%); margin:0 auto; padding:16px; }
        .section { background:var(--panel); border:1px solid var(--line); border-radius:14px; padding:14px; margin-bottom:12px; }
        .form-grid { display:grid; gap:8px; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); }
        label { display:block; font-size:12px; font-weight:600; color:var(--muted); margin-bottom:3px; }
        input, select { width:100%; border:1px solid #cbd5e1; border-radius:9px; padding:8px 9px; font-size:14px; }
        table { width:100%; border-collapse:collapse; margin-top:8px; font-size:13px; }
        th, td { text-align:left; padding:8px 6px; border-bottom:1px solid var(--line); vertical-align:top; }
        .status { border:1px solid #bbf7d0; background:#f0fdf4; color:#166534; padding:10px 12px; border-radius:10px; margin-bottom:10px; }
        .error { border:1px solid #fecaca; background:#fef2f2; color:#991b1b; padding:10px 12px; border-radius:10px; margin-bottom:10px; }
        .badge { display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:700; }
        .badge-pending { background:#fef3c7; color:#92400e; }
        .badge-approved { background:#dcfce7; color:#166534; }
        .badge-rejected { background:#fee2e2; color:#991b1b; }
        @media (max-width:980px) { .app-shell { grid-template-columns:1fr; } .sidebar { border-right:0; border-bottom:1px solid #0f766e; } }
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">Teacher Portal</div>
        <div class="brand-sub">{{ $school?->name ?? 'School' }}</div>
        <nav class="nav-group">
            <a class="nav-link" href="{{ route('dashboard.teacher') }}">Attendance Dashboard</a>
            <a class="nav-link" href="{{ route('teacher.students.index') }}">Student Requests</a>
        </nav>
    </aside>
    <div class="main-area">
        <header class="topbar">
            <strong>Student Entry Requests</strong>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn" type="submit">Logout</button></form>
        </header>
        <main class="wrap">
            @if (session('status')) <div class="status">{{ session('status') }}</div> @endif
            @if (session('generated_student_credentials'))
                @php($credentials = session('generated_student_credentials'))
                <div class="status">
                    <strong>Student Portal One-Time Credentials</strong><br>
                    Name: {{ $credentials['student_name'] }} ({{ $credentials['student_id'] }})<br>
                    Email: {{ $credentials['student_email'] }}<br>
                    Password: <code>{{ $credentials['one_time_password'] }}</code><br>
                    Student must change password on next login.
                </div>
            @endif
            @if ($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif

            <section class="section">
                <form method="GET" action="{{ route('teacher.students.index') }}">
                    <div class="form-grid">
                        <div>
                            <label>Select Assigned Class/Section</label>
                            <select name="assignment_id" required>
                                @foreach($assignments as $assignment)
                                    <option value="{{ $assignment->id }}" @selected($selectedAssignment && $selectedAssignment->id === $assignment->id)>
                                        {{ $assignment->schoolClass?->name }} - {{ $assignment->section?->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div style="display:flex;align-items:flex-end;">
                            <button class="btn" type="submit">Load</button>
                        </div>
                    </div>
                </form>
            </section>

            @if($selectedAssignment)
                <section class="section">
                    <h2 style="margin:0 0 8px;">Request New Student (Admin approval required)</h2>
                    <form method="POST" action="{{ route('teacher.students.requests.create') }}">
                        @csrf
                        <input type="hidden" name="assignment_id" value="{{ $selectedAssignment->id }}">
                        <div class="form-grid">
                            <div><label>Name</label><input name="name" required></div>
                            <div><label>Email</label><input type="email" name="email"></div>
                            <div><label>Phone</label><input name="phone"></div>
                            <div><label>DOB</label><input type="date" name="date_of_birth"></div>
                            <div><label>Gender</label><input name="gender"></div>
                            <div><label>Device ID</label><input name="device_identifier"></div>
                        </div>
                        <p style="margin:8px 0 0;"><button class="btn" type="submit">Send Create Request</button></p>
                    </form>
                </section>

                <section class="section">
                    <h2 style="margin:0 0 8px;">Approved Student List (after admin approval)</h2>
                    <table>
                        <thead><tr><th>ID</th><th>Student</th><th>Action (Request)</th></tr></thead>
                        <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td>{{ $student->student_id }}</td>
                                <td>{{ $student->name }}<br>{{ $student->email ?? '-' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('teacher.students.generate_password', $student) }}" style="margin-bottom:6px;">
                                        @csrf
                                        <input type="hidden" name="assignment_id" value="{{ $selectedAssignment->id }}">
                                        <button class="btn btn-outline" type="submit">Generate Student OTP</button>
                                    </form>
                                    <details style="margin-bottom:6px;">
                                        <summary class="btn btn-outline" style="display:inline-block;">Request Edit</summary>
                                        <form method="POST" action="{{ route('teacher.students.requests.update', $student) }}" style="margin-top:6px;">
                                            @csrf
                                            <input type="hidden" name="assignment_id" value="{{ $selectedAssignment->id }}">
                                            <div class="form-grid">
                                                <input name="name" value="{{ $student->name }}" required>
                                                <input type="email" name="email" value="{{ $student->email }}">
                                                <input name="phone" value="{{ $student->phone }}">
                                                <input type="date" name="date_of_birth" value="{{ optional($student->date_of_birth)->format('Y-m-d') }}">
                                                <input name="gender" value="{{ $student->gender }}">
                                                <input name="device_identifier" value="{{ $student->device_identifier }}">
                                            </div>
                                            <p style="margin:6px 0 0;"><button class="btn btn-outline" type="submit">Send Edit Request</button></p>
                                        </form>
                                    </details>
                                    <form method="POST" action="{{ route('teacher.students.requests.delete', $student) }}">
                                        @csrf
                                        <input type="hidden" name="assignment_id" value="{{ $selectedAssignment->id }}">
                                        <input type="text" name="reason" placeholder="Delete reason (optional)" style="max-width:260px;margin-bottom:6px;">
                                        <br>
                                        <button class="btn-danger" type="submit">Send Delete Request</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No approved students yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </section>
            @endif

            <section class="section">
                <h2 style="margin:0 0 8px;">My Recent Requests</h2>
                <table>
                    <thead><tr><th>ID</th><th>Action</th><th>Class/Section</th><th>Status</th><th>Review</th></tr></thead>
                    <tbody>
                    @forelse($requests as $requestItem)
                        <tr>
                            <td>#{{ $requestItem->id }}</td>
                            <td>{{ strtoupper($requestItem->action) }}</td>
                            <td>{{ $requestItem->schoolClass?->name }} / {{ $requestItem->section?->name }}</td>
                            <td>
                                @if($requestItem->status === 'approved')
                                    <span class="badge badge-approved">APPROVED</span>
                                @elseif($requestItem->status === 'rejected')
                                    <span class="badge badge-rejected">REJECTED</span>
                                @else
                                    <span class="badge badge-pending">PENDING</span>
                                @endif
                            </td>
                            <td>
                                {{ $requestItem->review_note ?: '-' }}
                                <br>
                                <small style="color:#64748b;">{{ $requestItem->reviewedBy?->name ?? '-' }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No requests yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>
</body>
</html>
