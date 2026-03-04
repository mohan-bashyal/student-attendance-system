<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Attendance</title>
    <style>
        :root { --bg:#f8fafc; --panel:#fff; --text:#0f172a; --muted:#475569; --line:#e2e8f0; --primary:#0f766e; --device-bg:#dcfce7; --device-text:#166534; --warn-bg:#fffbeb; --warn-text:#92400e; }
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
        .btn, button { border:0; border-radius:10px; padding:9px 13px; font-weight:600; cursor:pointer; }
        .btn { background:var(--primary); color:#fff; text-decoration:none; }
        .btn-outline { background:#fff; border:1px solid var(--line); color:var(--text); }
        .wrap { width:min(1280px,100%); margin:0 auto; padding:16px; }
        .card { background:var(--panel); border:1px solid var(--line); border-radius:14px; padding:14px; margin-bottom:12px; }
        .grid { display:grid; gap:12px; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); }
        .metric { margin-top:6px; font-size:26px; font-weight:700; }
        .muted { color:var(--muted); }
        .status { border:1px solid #bbf7d0; background:#f0fdf4; color:#166534; padding:10px 12px; border-radius:10px; margin-bottom:10px; }
        .warning { border:1px solid #fde68a; background:var(--warn-bg); color:var(--warn-text); padding:10px 12px; border-radius:10px; margin-bottom:10px; }
        .badge { display:inline-block; padding:3px 10px; border-radius:999px; background:#e2e8f0; font-size:12px; margin-right:6px; }
        .badge-device { background:var(--device-bg); color:var(--device-text); font-weight:700; }
        .form-grid { display:grid; gap:10px; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); }
        label { display:block; font-size:12px; font-weight:600; color:var(--muted); margin-bottom:3px; }
        input, select { width:100%; border:1px solid #cbd5e1; border-radius:9px; padding:8px 9px; font-size:14px; background:#fff; }
        table { width:100%; border-collapse:collapse; margin-top:8px; font-size:13px; }
        th, td { text-align:left; padding:8px 6px; border-bottom:1px solid var(--line); vertical-align:top; }
        .plan-tag { display:inline-block; padding:3px 10px; border-radius:999px; background:#ccfbf1; color:#115e59; font-size:12px; font-weight:700; }
        .chart-card { margin-top:10px; border:1px solid var(--line); border-radius:12px; padding:10px; background:#fff; }
        .chart-wrap { position:relative; min-height:280px; }
        .chart-toolbar { display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap; margin-bottom:8px; }
        .calendar-wrap { border:1px solid var(--line); border-radius:12px; padding:12px; margin-top:10px; display:none; }
        .calendar-toolbar { display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; margin-bottom:10px; }
        .calendar-grid { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:6px; }
        .calendar-head { font-size:12px; font-weight:700; color:var(--muted); text-align:center; padding:6px 4px; border-bottom:1px solid var(--line); }
        .calendar-cell { min-height:72px; border:1px solid var(--line); border-radius:8px; padding:6px; font-size:12px; background:#fff; }
        .calendar-cell.is-sunday { background:#fee2e2; border-color:#fecaca; color:#991b1b; }
        .calendar-cell.is-holiday { background:#fecaca; border-color:#f87171; color:#7f1d1d; font-weight:700; }
        .calendar-cell.is-muted { background:#f8fafc; color:#94a3b8; }
        .calendar-note { display:block; margin-top:4px; font-size:11px; font-weight:600; }
        @media (max-width:980px) { .app-shell { grid-template-columns:1fr; } .sidebar { border-right:0; border-bottom:1px solid #0f766e; } }
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">Teacher Portal</div>
        <div class="brand-sub">{{ $school?->name ?? 'School' }}</div>
        <nav class="nav-group">
            <a class="nav-link" href="{{ route('dashboard.teacher') }}">Overview</a>
            <a class="nav-link" href="#selector">Class Selector</a>
            <a class="nav-link" href="#marking">Mark Attendance</a>
            <a class="nav-link" href="{{ route('teacher.students.index') }}">Student Requests</a>
        </nav>
    </aside>
    <div class="main-area">
        <header class="topbar">
            <strong>{{ auth()->user()->name }} (teacher)</strong>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn" type="submit">Logout</button></form>
        </header>
        <main class="wrap">
            @if (session('status'))
                <div class="status">{{ session('status') }}</div>
            @endif

                <section class="card">
                    <h1 style="margin:0;">Class Teacher Attendance</h1>
                <p class="muted" style="margin:6px 0 0;">
                    <span class="plan-tag">{{ strtoupper((string) ($school?->subscription_plan ?? 'basic')) }} PLAN</span>
                    @if($featureFlags['extended_correction_window'] ?? false)
                        Enterprise: आजदेखि पछिल्लो 30 दिनसम्म correction गर्न सक्नुहुन्छ।
                    @elseif($featureFlags['correction_window'] ?? false)
                        Pro: आज र हिजोको attendance correction गर्न सक्नुहुन्छ।
                    @else
                        Basic: आजको attendance मात्र mark गर्न सकिन्छ ({{ $today }}).
                    @endif
                    </p>
                    @if($selectedDateCalendarEvent)
                        @if($selectedDateCalendarEvent->event_type === \App\Models\SchoolCalendarEvent::TYPE_HOLIDAY)
                            <div class="warning" style="margin-top:10px;">
                                {{ $selectedDateCalendarEvent->event_date?->format('Y-m-d') }} is marked as <strong>HOLIDAY</strong>.
                                Attendance save is locked for this class/section.
                            </div>
                        @else
                            <div class="status" style="margin-top:10px;">
                                Calendar Note: <strong>{{ strtoupper(str_replace('_', ' ', $selectedDateCalendarEvent->event_type)) }}</strong>
                                - {{ $selectedDateCalendarEvent->title }}
                            </div>
                        @endif
                    @endif
                    <p style="margin:10px 0 0;">
                        <button type="button" id="showTeacherCalendarBtn" class="btn-outline">Show Holiday Calendar</button>
                    </p>
                    <div id="teacherCalendarWrap" class="calendar-wrap">
                        <div class="calendar-toolbar">
                            <button type="button" class="btn-outline" id="teacherPrevMonthBtn">Prev</button>
                            <strong id="teacherCalendarTitle">Calendar</strong>
                            <button type="button" class="btn-outline" id="teacherNextMonthBtn">Next</button>
                        </div>
                        <div id="teacherCalendarGrid" class="calendar-grid"></div>
                    </div>
                </section>

            @if(!$teacher)
                <div class="warning">No teacher profile linked with your user account. Contact admin.</div>
            @elseif(!$teacher->has_attendance_access)
                <div class="warning">Attendance access is disabled for your account. Contact admin.</div>
            @else
                <section class="card">
                    <h2 style="margin:0 0 8px;">Today/Selected Date Summary</h2>
                    <div class="grid">
                        <article><p class="muted">Present</p><p class="metric">{{ $summary['present'] ?? 0 }}</p></article>
                        <article><p class="muted">Absent</p><p class="metric">{{ $summary['absent'] ?? 0 }}</p></article>
                        <article><p class="muted">Late</p><p class="metric">{{ $summary['late'] ?? 0 }}</p></article>
                        <article><p class="muted">Half-day / Leave</p><p class="metric">{{ $summary['half_day'] ?? 0 }} / {{ $summary['leave'] ?? 0 }}</p></article>
                    </div>
                    <p class="muted" style="margin:10px 0 0;">
                        Marked by: {{ $sessionMarkedBy ?: '-' }}
                        @if($featureFlags['device_attendance'] ?? false)
                            | <span class="badge badge-device">Device entries: {{ $deviceMarkedCount }}</span>
                        @endif
                    </p>
                    <div class="chart-card" id="overview">
                        <form method="GET" action="{{ route('dashboard.teacher') }}" class="chart-toolbar">
                            @if($selectedAssignment)
                                <input type="hidden" name="assignment_id" value="{{ $selectedAssignment->id }}">
                            @endif
                            @if($canUseCorrectionWindow)
                                <input type="hidden" name="attendance_date" value="{{ $selectedAttendanceDate }}">
                            @endif
                            <div>
                                <label style="margin-bottom:3px;">Overview Chart Type</label>
                                <select name="overview_chart" onchange="this.form.submit()">
                                    <option value="doughnut" @selected(($selectedOverviewChart ?? 'doughnut') === 'doughnut')>Doughnut</option>
                                    <option value="bar" @selected(($selectedOverviewChart ?? 'doughnut') === 'bar')>Bar</option>
                                    <option value="line" @selected(($selectedOverviewChart ?? 'doughnut') === 'line')>Line</option>
                                </select>
                            </div>
                        </form>
                        <div class="grid">
                            <div class="chart-wrap"><canvas id="teacherStatusChart"></canvas></div>
                            <div class="chart-wrap"><canvas id="teacherTrendChart"></canvas></div>
                        </div>
                    </div>
                </section>

                <section class="card" id="selector">
                    <form method="GET" action="{{ route('dashboard.teacher') }}">
                        <div class="form-grid">
                            <div>
                                <label>Assigned Class/Section</label>
                                <select name="assignment_id" required>
                                    @foreach($assignments as $assignment)
                                        <option value="{{ $assignment->id }}" @selected($selectedAssignment && $selectedAssignment->id === $assignment->id)>
                                            {{ $assignment->schoolClass?->name }} - {{ $assignment->section?->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if($canUseCorrectionWindow)
                                <div>
                                    <label>Attendance Date</label>
                                    <input type="date" name="attendance_date" value="{{ $selectedAttendanceDate }}" min="{{ $minimumAttendanceDate }}" max="{{ $maximumAttendanceDate }}">
                                </div>
                            @endif
                            <div style="display:flex;align-items:flex-end;">
                                <button class="btn" type="submit">Load Students</button>
                            </div>
                        </div>
                    </form>
                </section>

                @if($selectedAssignment)
                    <section class="card" id="marking">
                        <h2 style="margin:0 0 8px;">Mark Attendance</h2>
                        <p class="muted" style="margin:0 0 8px;">
                            Class: <strong>{{ $selectedAssignment->schoolClass?->name }} - {{ $selectedAssignment->section?->name }}</strong>
                            | Date: <strong>{{ $selectedAttendanceDate }}</strong>
                        </p>
                        <form method="POST" action="{{ route('dashboard.teacher.store') }}">
                            @csrf
                            <input type="hidden" name="assignment_id" value="{{ $selectedAssignment->id }}">
                            @if($canUseCorrectionWindow)
                                <input type="hidden" name="attendance_date" value="{{ $selectedAttendanceDate }}">
                            @endif

                            <div class="form-grid" style="margin-bottom:10px;">
                                <div>
                                    <label>Bulk Status</label>
                                    <select id="bulkStatus">
                                        @foreach($statusTypes as $status)
                                            <option value="{{ $status }}">{{ strtoupper(str_replace('_', ' ', $status)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label>Bulk Leave Type</label>
                                    <select id="bulkLeaveType">
                                        <option value="">None</option>
                                        @foreach($leaveTypes as $leaveType)
                                            <option value="{{ $leaveType }}">{{ strtoupper($leaveType) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="display:flex;align-items:flex-end;">
                                    <button type="button" class="btn btn-outline" onclick="applyBulk()">Apply To All</button>
                                </div>
                            </div>

                            <table>
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Leave Type</th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($students as $student)
                                    @php
                                        $record = $recordsByStudent[$student->id] ?? null;
                                        $status = $record['status'] ?? 'present';
                                        $leaveType = $record['leave_type'] ?? '';
                                        $remark = $record['remark'] ?? '';
                                        $isDeviceMarked = \Illuminate\Support\Str::startsWith((string) $remark, 'Marked via device:');
                                    @endphp
                                    <tr>
                                        <td>{{ $student->student_id }}</td>
                                        <td>
                                            {{ $student->name }}
                                            @if($isDeviceMarked)
                                                <span class="badge badge-device">Marked via device</span>
                                            @endif
                                        </td>
                                        <td>
                                            <select name="records[{{ $student->id }}][status]" class="status-field">
                                                @foreach($statusTypes as $statusOption)
                                                    <option value="{{ $statusOption }}" @selected($status === $statusOption)>{{ strtoupper(str_replace('_', ' ', $statusOption)) }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="records[{{ $student->id }}][leave_type]" class="leave-field">
                                                <option value="">None</option>
                                                @foreach($leaveTypes as $leaveTypeOption)
                                                    <option value="{{ $leaveTypeOption }}" @selected($leaveType === $leaveTypeOption)>{{ strtoupper($leaveTypeOption) }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="records[{{ $student->id }}][remark]" value="{{ $remark }}" placeholder="Optional note"></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5">No students in assigned class/section.</td></tr>
                                @endforelse
                                </tbody>
                            </table>

                            @if($students->count() > 0)
                                <p style="margin:12px 0 0;">
                                    <button class="btn" type="submit" @disabled($selectedDateCalendarEvent && $selectedDateCalendarEvent->event_type === \App\Models\SchoolCalendarEvent::TYPE_HOLIDAY)>Save Attendance</button>
                                </p>
                            @endif
                        </form>
                    </section>
                @endif
            @endif
        </main>
    </div>
</div>
<script>
function applyBulk() {
    const status = document.getElementById('bulkStatus').value;
    const leaveType = document.getElementById('bulkLeaveType').value;
    document.querySelectorAll('.status-field').forEach((field) => {
        field.value = status;
    });
    document.querySelectorAll('.leave-field').forEach((field) => {
        field.value = status === 'leave' ? leaveType : '';
    });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    if (typeof Chart === 'undefined') return;
    const statusCtx = document.getElementById('teacherStatusChart');
    const trendCtx = document.getElementById('teacherTrendChart');
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
                label: 'Students',
                data: statusValues,
                backgroundColor: ['#22c55e', '#ef4444', '#f59e0b', '#0ea5e9', '#8b5cf6'],
                borderColor: '#ffffff',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Attendance % (Last 14 Days)',
                data: trendPercentages,
                borderColor: '#0f766e',
                backgroundColor: 'rgba(15,118,110,0.2)',
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

    const showBtn = document.getElementById('showTeacherCalendarBtn');
    const wrap = document.getElementById('teacherCalendarWrap');
    const grid = document.getElementById('teacherCalendarGrid');
    const title = document.getElementById('teacherCalendarTitle');
    const prevBtn = document.getElementById('teacherPrevMonthBtn');
    const nextBtn = document.getElementById('teacherNextMonthBtn');
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
