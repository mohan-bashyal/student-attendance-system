<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal</title>
    <style>
        :root { --bg:#f8fafc; --panel:#fff; --text:#0f172a; --muted:#475569; --line:#e2e8f0; --primary:#1d4ed8; --device-bg:#dcfce7; --device-text:#166534; --warn-bg:#fff7ed; --warn-text:#9a3412; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:"Segoe UI","Manrope",sans-serif; background:linear-gradient(180deg,#dbeafe 0%,var(--bg) 200px); color:var(--text); }
        .app-shell { min-height:100vh; display:grid; grid-template-columns:260px 1fr; }
        .sidebar { background:#1e3a8a; color:#dbeafe; padding:18px 14px; border-right:1px solid #1d4ed8; }
        .brand { font-size:18px; font-weight:700; margin-bottom:4px; }
        .brand-sub { font-size:13px; color:#bfdbfe; margin-bottom:18px; }
        .nav-group { display:grid; gap:8px; }
        .nav-link { color:#dbeafe; text-decoration:none; padding:9px 10px; border-radius:9px; display:block; background:rgba(191,219,254,0.12); }
        .nav-link:hover { background:rgba(191,219,254,0.22); }
        .main { min-width:0; }
        .topbar { position:sticky; top:0; z-index:20; background:#ffffffd9; backdrop-filter:blur(6px); border-bottom:1px solid var(--line); padding:12px 16px; display:flex; align-items:center; justify-content:space-between; gap:10px; }
        .btn, button { border:0; border-radius:10px; padding:9px 13px; font-weight:600; cursor:pointer; }
        .btn { background:var(--primary); color:#fff; text-decoration:none; }
        .btn-muted { background:#e2e8f0; color:#0f172a; text-decoration:none; }
        .wrap { width:min(1280px,100%); margin:0 auto; padding:16px; }
        .card { background:var(--panel); border:1px solid var(--line); border-radius:14px; padding:14px; margin-bottom:12px; }
        .grid { display:grid; gap:12px; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); }
        .metric { font-size:28px; font-weight:700; margin:6px 0 0; }
        .plan-tag { display:inline-block; padding:3px 10px; border-radius:999px; background:#dbeafe; font-size:12px; font-weight:700; color:#1e3a8a; }
        .muted { color:var(--muted); }
        .warning { border:1px solid #fed7aa; background:var(--warn-bg); color:var(--warn-text); border-radius:10px; padding:10px 12px; }
        .badge { display:inline-block; padding:3px 10px; border-radius:999px; background:#e2e8f0; font-size:12px; margin-right:6px; }
        .badge-device { background:var(--device-bg); color:var(--device-text); font-weight:700; }
        .form-grid { display:grid; gap:10px; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); }
        label { display:block; font-size:12px; color:var(--muted); font-weight:600; margin-bottom:4px; }
        input, select { width:100%; border:1px solid #cbd5e1; border-radius:9px; padding:8px 9px; font-size:14px; }
        table { width:100%; border-collapse:collapse; margin-top:8px; font-size:13px; }
        th, td { text-align:left; padding:8px 6px; border-bottom:1px solid var(--line); vertical-align:top; }
        .chart-wrap { position:relative; min-height:280px; }
        .chart-toolbar { display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap; margin-bottom:10px; }
        .calendar-wrap { border:1px solid var(--line); border-radius:12px; padding:12px; margin-top:10px; display:none; }
        .calendar-toolbar-ui { display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; margin-bottom:10px; }
        .calendar-grid { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:6px; }
        .calendar-head { font-size:12px; font-weight:700; color:var(--muted); text-align:center; padding:6px 4px; border-bottom:1px solid var(--line); }
        .calendar-cell { min-height:72px; border:1px solid var(--line); border-radius:8px; padding:6px; font-size:12px; background:#fff; }
        .calendar-cell.is-sunday { background:#fee2e2; border-color:#fecaca; color:#991b1b; }
        .calendar-cell.is-holiday { background:#fecaca; border-color:#f87171; color:#7f1d1d; font-weight:700; }
        .calendar-cell.is-muted { background:#f8fafc; color:#94a3b8; }
        .calendar-note { display:block; margin-top:4px; font-size:11px; font-weight:600; }
        @media (max-width:980px) { .app-shell { grid-template-columns:1fr; } .sidebar { border-right:0; border-bottom:1px solid #1d4ed8; } }
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">Student Portal</div>
        <div class="brand-sub">{{ $school?->name ?? 'School' }}</div>
        <nav class="nav-group">
            <a class="nav-link" href="{{ route('dashboard.student') }}">Overview</a>
            <a class="nav-link" href="#filter">Filters</a>
            <a class="nav-link" href="#records">Attendance Records</a>
        </nav>
    </aside>
    <div class="main">
        <header class="topbar">
            <strong>{{ auth()->user()->name }} (student)</strong>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn" type="submit">Logout</button></form>
        </header>
        <main class="wrap">
            @if(!$student)
                <section class="card">
                    <p>No linked student profile found. Contact school admin.</p>
                </section>
            @else
                <section class="card" id="overview">
                    <h1 style="margin:0;">My Attendance Overview</h1>
                    <p class="muted" style="margin:6px 0 0;">
                        <span class="plan-tag">{{ strtoupper((string) ($school?->subscription_plan ?? 'basic')) }} PLAN</span>
                        Selected range: {{ $dateFrom }} to {{ $dateTo }}
                    </p>
                    <div class="grid" style="margin-top:10px;">
                        <article>
                            <p class="muted">Student</p>
                            <p><strong>{{ $student->name }}</strong> ({{ $student->student_id }})</p>
                        </article>
                        <article>
                            <p class="muted">Attendance %</p>
                            <p class="metric">{{ number_format($percentage, 2) }}%</p>
                        </article>
                        <article>
                            <p class="muted">Total Records</p>
                            <p class="metric">{{ $records->count() }}</p>
                        </article>
                        <article>
                            <p class="muted">Present / Absent</p>
                            <p class="metric">{{ $summary['present'] ?? 0 }} / {{ $summary['absent'] ?? 0 }}</p>
                        </article>
                    </div>
                    <form method="GET" action="{{ route('student_portal.index') }}" class="chart-toolbar" style="margin-top:10px;">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                        <input type="hidden" name="date_to" value="{{ $dateTo }}">
                        @if(!empty($selectedStatus))
                            <input type="hidden" name="status" value="{{ $selectedStatus }}">
                        @endif
                        <div>
                            <label>Overview Chart Type</label>
                            <select name="overview_chart" onchange="this.form.submit()">
                                <option value="doughnut" @selected(($selectedOverviewChart ?? 'doughnut') === 'doughnut')>Doughnut</option>
                                <option value="bar" @selected(($selectedOverviewChart ?? 'doughnut') === 'bar')>Bar</option>
                                <option value="line" @selected(($selectedOverviewChart ?? 'doughnut') === 'line')>Line</option>
                            </select>
                        </div>
                    </form>
                    <div class="grid" style="margin-top:6px;">
                        <div class="chart-wrap"><canvas id="studentStatusChart"></canvas></div>
                        <div class="chart-wrap"><canvas id="studentTrendChart"></canvas></div>
                    </div>
                </section>

                @if($lowAttendanceAlert)
                    <section class="card">
                        <div class="warning">
                            Attendance {{ number_format($percentage, 2) }}% छ। 75% भन्दा तल गयो, नियमित उपस्थित हुनुहोस्।
                        </div>
                    </section>
                @endif

                <section class="card">
                    <h2 style="margin:0 0 8px;">Notifications</h2>
                    @forelse($notifications as $notification)
                        <article style="border:1px solid var(--line);border-radius:10px;padding:10px 12px;margin-bottom:8px;">
                            <strong>{{ $notification->message }}</strong>
                            <p class="muted" style="margin:6px 0 0;font-size:13px;">
                                {{ $notification->notified_at?->format('Y-m-d H:i') }}
                            </p>
                        </article>
                    @empty
                        <p class="muted" style="margin:0;">No notifications yet.</p>
                    @endforelse
                </section>

                @if($featureFlags['enterprise_features'] ?? false)
                    <section class="card">
                        <h2 style="margin:0 0 8px;">Enterprise Insights</h2>
                        <span class="badge badge-device">Device entries: {{ $deviceMarkedCount }}</span>
                        <span class="badge">Late: {{ $summary['late'] ?? 0 }}</span>
                        <span class="badge">Half-day: {{ $summary['half_day'] ?? 0 }}</span>
                        <span class="badge">Leave: {{ $summary['leave'] ?? 0 }}</span>
                    </section>
                @endif

                <section class="card" id="filter">
                    <h2 style="margin:0 0 8px;">Filter</h2>
                    <p style="margin:0 0 10px;">
                        <button type="button" id="showStudentCalendarBtn" class="btn-muted">Show Holiday Calendar</button>
                    </p>
                    <div id="studentCalendarWrap" class="calendar-wrap">
                        <div class="calendar-toolbar-ui">
                            <button type="button" class="btn-muted" id="studentPrevMonthBtn">Prev</button>
                            <strong id="studentCalendarTitle">Calendar</strong>
                            <button type="button" class="btn-muted" id="studentNextMonthBtn">Next</button>
                        </div>
                        <div id="studentCalendarGrid" class="calendar-grid"></div>
                    </div>
                    <form method="GET" action="{{ route('student_portal.index') }}">
                        <div class="form-grid">
                            <div>
                                <label>Month (Basic)</label>
                                <input type="month" name="month" value="{{ $month }}">
                            </div>
                            @if($featureFlags['advanced_reports'] ?? false)
                                <div>
                                    <label>Date From (Pro)</label>
                                    <input type="date" name="date_from" value="{{ $dateFrom }}">
                                </div>
                                <div>
                                    <label>Date To (Pro)</label>
                                    <input type="date" name="date_to" value="{{ $dateTo }}">
                                </div>
                                <div>
                                    <label>Status (Pro)</label>
                                    <select name="status">
                                        <option value="">All</option>
                                        <option value="present" @selected($selectedStatus === 'present')>PRESENT</option>
                                        <option value="absent" @selected($selectedStatus === 'absent')>ABSENT</option>
                                        <option value="late" @selected($selectedStatus === 'late')>LATE</option>
                                        <option value="half_day" @selected($selectedStatus === 'half_day')>HALF DAY</option>
                                        <option value="leave" @selected($selectedStatus === 'leave')>LEAVE</option>
                                    </select>
                                </div>
                            @endif
                            <div style="display:flex;align-items:flex-end;gap:8px;">
                                <button class="btn" type="submit">Load</button>
                                <a class="btn-muted" href="{{ route('student_portal.monthly_report', ['month' => $month]) }}">Download Monthly CSV</a>
                            </div>
                        </div>
                    </form>
                    @if(!($featureFlags['advanced_reports'] ?? false))
                        <p class="muted" style="margin:10px 0 0;">Pro plan मा date range र status filter उपलब्ध हुन्छ।</p>
                    @endif
                </section>

                <section class="card" id="records">
                    <h2 style="margin:0 0 8px;">Attendance Record</h2>
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
                                <th>Marked By</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($records as $record)
                            @php($isDeviceMarked = \Illuminate\Support\Str::startsWith((string) ($record->remark ?? ''), 'Marked via device:'))
                            <tr>
                                <td>{{ $record->attendanceSession?->attendance_date?->format('Y-m-d') }}</td>
                                <td>{{ $record->attendanceSession?->schoolClass?->name }}</td>
                                <td>{{ $record->attendanceSession?->section?->name }}</td>
                                <td>{{ $record->attendanceSession?->period_no }}</td>
                                <td>
                                    @if($isDeviceMarked)
                                        <span class="badge badge-device">Marked via device</span>
                                    @endif
                                    {{ strtoupper(str_replace('_', ' ', $record->status)) }}
                                </td>
                                <td>{{ $record->leave_type ? strtoupper($record->leave_type) : '-' }}</td>
                                <td>{{ $record->remark ?: '-' }}</td>
                                <td>{{ $record->attendanceSession?->markedBy?->name ?? ($isDeviceMarked ? 'DEVICE' : '-') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8">No attendance records for selected filter.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </section>
            @endif
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    if (typeof Chart === 'undefined') return;
    const statusCtx = document.getElementById('studentStatusChart');
    const trendCtx = document.getElementById('studentTrendChart');
    if (!statusCtx || !trendCtx) return;

    const selectedType = @json($selectedOverviewChart ?? 'doughnut');
    const statusLabels = @json($overviewChartData['statusLabels'] ?? []);
    const statusValues = @json($overviewChartData['statusValues'] ?? []);
    const trendLabels = @json($overviewChartData['trendLabels'] ?? []);
    const trendPercentages = @json($overviewChartData['trendPercentages'] ?? []);

    new Chart(statusCtx, {
        type: selectedType,
        data: {
            labels: statusLabels,
            datasets: [{
                label: 'Entries',
                data: statusValues,
                backgroundColor: ['#22c55e', '#ef4444', '#f59e0b', '#0ea5e9', '#8b5cf6'],
                borderColor: '#ffffff',
                borderWidth: 1
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Attendance % Trend',
                data: trendPercentages,
                borderColor: '#1d4ed8',
                backgroundColor: 'rgba(29,78,216,0.2)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { min: 0, max: 100 }
            }
        }
    });
})();
</script>
<script>
(() => {
    const holidayDates = @json($holidayCalendarDates ?? []);
    const holidayTitles = @json($holidayCalendarTitles ?? []);
    const holidaySet = new Set(holidayDates);

    const showBtn = document.getElementById('showStudentCalendarBtn');
    const wrap = document.getElementById('studentCalendarWrap');
    const grid = document.getElementById('studentCalendarGrid');
    const title = document.getElementById('studentCalendarTitle');
    const prevBtn = document.getElementById('studentPrevMonthBtn');
    const nextBtn = document.getElementById('studentNextMonthBtn');
    if (!showBtn || !wrap || !grid || !title || !prevBtn || !nextBtn) return;

    let current = new Date();
    current.setDate(1);
    const dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    const toKey = (dateObj) => {
        const y = dateObj.getFullYear();
        const m = String(dateObj.getMonth() + 1).padStart(2, '0');
        const d = String(dateObj.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    };

    const render = () => {
        grid.innerHTML = '';
        dayLabels.forEach((label) => {
            const head = document.createElement('div');
            head.className = 'calendar-head';
            head.textContent = label;
            grid.appendChild(head);
        });

        const year = current.getFullYear();
        const month = current.getMonth();
        const first = new Date(year, month, 1);
        const startDay = first.getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysPrev = new Date(year, month, 0).getDate();
        title.textContent = first.toLocaleString('en-US', { month: 'long', year: 'numeric' });

        for (let i = 0; i < startDay; i++) {
            const cell = document.createElement('div');
            cell.className = 'calendar-cell is-muted';
            cell.textContent = String(daysPrev - startDay + i + 1);
            grid.appendChild(cell);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateObj = new Date(year, month, day);
            const key = toKey(dateObj);
            const cell = document.createElement('div');
            cell.className = 'calendar-cell';
            if (dateObj.getDay() === 0) cell.classList.add('is-sunday');
            if (holidaySet.has(key)) cell.classList.add('is-holiday');
            cell.innerHTML = `<div>${day}</div>`;
            if (holidayTitles[key]) {
                const note = document.createElement('span');
                note.className = 'calendar-note';
                note.textContent = holidayTitles[key];
                cell.appendChild(note);
            }
            grid.appendChild(cell);
        }
    };

    showBtn.addEventListener('click', () => {
        const open = wrap.style.display === 'block';
        wrap.style.display = open ? 'none' : 'block';
        showBtn.textContent = open ? 'Show Holiday Calendar' : 'Hide Holiday Calendar';
        if (!open) render();
    });

    prevBtn.addEventListener('click', () => {
        current = new Date(current.getFullYear(), current.getMonth() - 1, 1);
        render();
    });

    nextBtn.addEventListener('click', () => {
        current = new Date(current.getFullYear(), current.getMonth() + 1, 1);
        render();
    });
})();
</script>
</body>
</html>
