<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Portal</title>
    <style>
        :root { --bg:#f8fafc; --panel:#fff; --text:#0f172a; --muted:#475569; --line:#e2e8f0; --primary:#0f766e; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Segoe UI","Manrope",sans-serif; background:linear-gradient(180deg,#ccfbf1 0%,var(--bg) 220px); color:var(--text); }
        .wrap { width:min(1200px,100%); margin:0 auto; padding:16px; }
        .top { display:flex; justify-content:space-between; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:12px; }
        .btn, button { border:0; border-radius:10px; padding:9px 13px; font-weight:600; cursor:pointer; }
        .btn { background:var(--primary); color:#fff; text-decoration:none; }
        .card { background:var(--panel); border:1px solid var(--line); border-radius:14px; padding:14px; margin-bottom:12px; }
        table { width:100%; border-collapse:collapse; margin-top:8px; font-size:13px; }
        th, td { text-align:left; padding:8px 6px; border-bottom:1px solid var(--line); }
        .muted { color:var(--muted); }
        .badge { display:inline-block; padding:2px 9px; border-radius:999px; background:#e2e8f0; font-size:12px; }
    </style>
</head>
<body>
<main class="wrap">
    <header class="top">
        <div>
            <h1 style="margin:0;">Parent Portal</h1>
            <p class="muted" style="margin:4px 0 0;">View child attendance and alert settings.</p>
        </div>
        <div style="display:flex;gap:8px;">
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn" type="submit">Logout</button></form>
        </div>
    </header>

    <section class="card">
        <p class="badge">SMS notification if absent: Enabled (simulated log)</p>
        <p class="badge">Email alerts: Enabled</p>
    </section>

    @forelse($children as $childData)
        <section class="card">
            <h2 style="margin:0 0 4px;">{{ $childData['student']->name }} ({{ $childData['student']->student_id }})</h2>
            <p class="muted" style="margin:0 0 8px;">Attendance Percentage: <strong>{{ number_format($childData['percentage'], 2) }}%</strong></p>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th>Leave Type</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($childData['records'] as $record)
                    <tr>
                        <td>{{ $record->attendanceSession?->attendance_date?->format('Y-m-d') }}</td>
                        <td>{{ $record->attendanceSession?->schoolClass?->name }}</td>
                        <td>{{ $record->attendanceSession?->section?->name }}</td>
                        <td>{{ $record->attendanceSession?->period_no }}</td>
                        <td>{{ strtoupper(str_replace('_', ' ', $record->status)) }}</td>
                        <td>{{ $record->leave_type ? strtoupper($record->leave_type) : '-' }}</td>
                        <td>{{ $record->remark ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No attendance records found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    @empty
        <section class="card">
            <p>No child linked to this parent account. Ask admin to link parent-student profile.</p>
        </section>
    @endforelse
</main>
</body>
</html>
