<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report</title>
    <style>
        :root { --bg:#f8fafc; --panel:#fff; --text:#0f172a; --muted:#475569; --line:#e2e8f0; --primary:#0369a1; --device-bg:#dcfce7; --device-text:#166534; }
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
        .wrap { width:min(1280px,100%); margin:0 auto; padding:16px; }
        .section { background:var(--panel); border:1px solid var(--line); border-radius:14px; padding:14px; margin-bottom:12px; }
        .form-grid { display:grid; gap:10px; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); }
        label { display:block; font-size:12px; font-weight:600; color:var(--muted); margin-bottom:3px; }
        input, select { width:100%; border:1px solid #cbd5e1; border-radius:9px; padding:8px 9px; font-size:14px; background:#fff; }
        table { width:100%; border-collapse:collapse; margin-top:8px; font-size:13px; }
        th, td { text-align:left; padding:8px 6px; border-bottom:1px solid var(--line); vertical-align:top; }
        .badge { display:inline-block; padding:3px 10px; border-radius:999px; background:#e2e8f0; font-size:12px; margin-right:6px; }
        .badge-device { background:var(--device-bg); color:var(--device-text); font-weight:700; }
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
            <strong>Attendance Report (Admin View Only)</strong>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn" type="submit">Logout</button></form>
        </header>
        <main class="wrap">
            <section class="section">
                <p class="muted" style="margin:0 0 8px;">Admin can view attendance class/section wise but cannot mark attendance.</p>
                <form method="GET" action="{{ route('admin.attendance.index') }}">
                    <div class="form-grid">
                        <div>
                            <label>Date</label>
                            <input type="date" name="attendance_date" value="{{ $selectedDate }}" required>
                        </div>
                        <div>
                            <label>Class</label>
                            <select id="attendanceClassSelect" name="school_class_id" required>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" @selected((int) $selectedClassId === (int) $class->id)>{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>Section</label>
                            <select id="attendanceSectionSelect" name="section_id" required>
                                @foreach($sections as $section)
                                    <option value="{{ $section->id }}" @selected((int) $selectedSectionId === (int) $section->id)>{{ $section->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($isAdvancedReportEnabled)
                            <div>
                                <label>Date From (Pro)</label>
                                <input type="date" name="date_from" value="{{ $selectedDateFrom }}">
                            </div>
                            <div>
                                <label>Date To (Pro)</label>
                                <input type="date" name="date_to" value="{{ $selectedDateTo }}">
                            </div>
                            <div>
                                <label>Status (Pro)</label>
                                <select name="status">
                                    <option value="">All</option>
                                    @foreach($statusTypes as $statusType)
                                        <option value="{{ $statusType }}" @selected($selectedStatus === $statusType)>{{ strtoupper(str_replace('_', ' ', $statusType)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    <p style="margin:10px 0 0;"><button class="btn" type="submit">View Attendance</button></p>
                </form>
                @if(!$isAdvancedReportEnabled)
                    <p class="muted" style="margin:8px 0 0;">Pro-only analytics (date range + status filter) are locked in BASIC plan.</p>
                @endif
                @if($selectedDateCalendarEvent)
                    @if($selectedDateCalendarEvent->event_type === \App\Models\SchoolCalendarEvent::TYPE_HOLIDAY)
                        <p class="muted" style="margin:8px 0 0;color:#b45309;">
                            {{ $selectedDate }} is marked as HOLIDAY ({{ $selectedDateCalendarEvent->title }}). No manual attendance should be recorded.
                        </p>
                    @else
                        <p class="muted" style="margin:8px 0 0;color:#0f766e;">
                            Calendar event on {{ $selectedDate }}: {{ strtoupper(str_replace('_', ' ', $selectedDateCalendarEvent->event_type)) }} - {{ $selectedDateCalendarEvent->title }}
                        </p>
                    @endif
                @endif
                @if($isEnterpriseExportEnabled)
                    <p style="margin:8px 0 0;">
                        <a class="btn" href="{{ route('admin.attendance.export', request()->query()) }}">Download CSV (Enterprise)</a>
                    </p>
                @else
                    <p class="muted" style="margin:8px 0 0;">CSV export is locked in non-enterprise plans.</p>
                @endif
            </section>

            <section class="section">
                <h2 style="margin:0 0 8px;">Status Summary</h2>
                <span class="badge">Present: {{ $summary['present'] ?? 0 }}</span>
                <span class="badge">Absent: {{ $summary['absent'] ?? 0 }}</span>
                <span class="badge">Late: {{ $summary['late'] ?? 0 }}</span>
                <span class="badge">Half-day: {{ $summary['half_day'] ?? 0 }}</span>
                <span class="badge">Leave: {{ $summary['leave'] ?? 0 }}</span>
                @if(!$session)
                    <p class="muted" style="margin:10px 0 0;">No attendance submitted for selected date/class/section.</p>
                @endif
            </section>

            <section class="section">
                <h2 style="margin:0 0 8px;">Student Attendance List</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Leave Type</th>
                            <th>Remark</th>
                            <th>Marked By</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($attendanceRows as $record)
                        @php($isDeviceMarked = \Illuminate\Support\Str::startsWith((string) ($record->remark ?? ''), 'Marked via device:'))
                        <tr>
                            <td>{{ $record->attendanceSession?->attendance_date?->format('Y-m-d') ?? $selectedDate }}</td>
                            <td>{{ $record->student?->student_id }}</td>
                            <td>{{ $record->student?->name }}</td>
                            <td>
                                @if($isDeviceMarked)
                                    <span class="badge badge-device">Marked via device</span>
                                @endif
                                {{ strtoupper(str_replace('_', ' ', $record->status)) }}
                            </td>
                            <td>{{ $record->leave_type ? strtoupper($record->leave_type) : '-' }}</td>
                            <td>{{ $record->remark ?: '-' }}</td>
                            <td>{{ $rangeMode ? ($record->attendanceSession?->markedBy?->name ?? '-') : ($session?->markedBy?->name ?? '-') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No data available.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </section>

            <section class="section">
                <h2 style="margin:0 0 8px;">Attendance Audit Logs (Enterprise)</h2>
                @if($isAuditLogsEnabled)
                    <table>
                        <thead>
                            <tr>
                                <th>Changed At</th>
                                <th>Student</th>
                                <th>Teacher</th>
                                <th>Previous</th>
                                <th>New</th>
                                <th>Changed By</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($auditLogs as $log)
                            <tr>
                                <td>{{ $log->changed_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $log->student?->student_id }} - {{ $log->student?->name }}</td>
                                <td>{{ $log->teacher?->name ?? '-' }}</td>
                                <td>{{ strtoupper((string) $log->previous_status) ?: '-' }}</td>
                                <td>{{ strtoupper((string) $log->new_status) ?: '-' }}</td>
                                <td>{{ $log->changedBy?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No audit logs available.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                @else
                    <p class="muted" style="margin:0;">Audit logs are available only in ENTERPRISE plan.</p>
                @endif
            </section>
        </main>
    </div>
</div>
</body>
<script>
const activeSectionsByClass = @json($activeSectionsByClass ?? []);
const classSelect = document.getElementById('attendanceClassSelect');
const sectionSelect = document.getElementById('attendanceSectionSelect');

if (classSelect && sectionSelect) {
    const options = Array.from(sectionSelect.querySelectorAll('option'));
    const syncSections = () => {
        const classId = classSelect.value;
        const activeSectionIds = (activeSectionsByClass[classId] || []).map(String);
        options.forEach((option) => {
            option.hidden = option.value !== '' && !activeSectionIds.includes(option.value);
        });

        if (sectionSelect.value && !activeSectionIds.includes(sectionSelect.value)) {
            sectionSelect.value = '';
        }
    };

    classSelect.addEventListener('change', syncSections);
    syncSections();
}
</script>
</html>
