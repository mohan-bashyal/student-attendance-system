<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holiday Calendar</title>
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
        .wrap { width:min(1280px,100%); margin:0 auto; padding:16px; }
        .section { background:var(--panel); border:1px solid var(--line); border-radius:14px; padding:14px; margin-bottom:12px; }
        .form-grid { display:grid; gap:10px; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); }
        label { display:block; font-size:12px; font-weight:600; color:var(--muted); margin-bottom:3px; }
        input, select { width:100%; border:1px solid #cbd5e1; border-radius:9px; padding:8px 9px; font-size:14px; background:#fff; }
        table { width:100%; border-collapse:collapse; margin-top:8px; font-size:13px; }
        th, td { text-align:left; padding:8px 6px; border-bottom:1px solid var(--line); vertical-align:top; }
        .muted { color:var(--muted); }
        .calendar-wrap { border:1px solid var(--line); border-radius:12px; padding:12px; margin:10px 0; display:none; }
        .calendar-toolbar { display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; margin-bottom:10px; }
        .calendar-grid { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:6px; }
        .calendar-head { font-size:12px; font-weight:700; color:var(--muted); text-align:center; padding:6px 4px; border-bottom:1px solid var(--line); }
        .calendar-cell { min-height:72px; border:1px solid var(--line); border-radius:8px; padding:6px; font-size:12px; background:#fff; }
        .calendar-cell.is-sunday { background:#fee2e2; border-color:#fecaca; color:#991b1b; }
        .calendar-cell.is-holiday { background:#fecaca; border-color:#f87171; color:#7f1d1d; font-weight:700; }
        .calendar-cell.is-muted { background:#f8fafc; color:#94a3b8; }
        .calendar-note { display:block; margin-top:4px; font-size:11px; font-weight:600; }
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
            <strong>Holiday / School Calendar</strong>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn" type="submit">Logout</button></form>
        </header>
        <main class="wrap">
            @if(session('status'))
                <section class="section"><strong>{{ session('status') }}</strong></section>
            @endif
            @if($errors->any())
                <section class="section" style="border-color:#fecaca;background:#fef2f2;color:#991b1b;">
                    <strong>{{ $errors->first() }}</strong>
                </section>
            @endif

            <section class="section">
                <h2 style="margin:0 0 8px;">Add Calendar Event</h2>
                <p style="margin:0 0 10px;">
                    <button type="button" id="showCalendarBtn" class="btn-outline">Show Calendar</button>
                </p>
                <div id="holidayCalendarWrap" class="calendar-wrap">
                    <div class="calendar-toolbar">
                        <button type="button" class="btn-outline" id="prevMonthBtn">Prev</button>
                        <strong id="calendarTitle">Calendar</strong>
                        <button type="button" class="btn-outline" id="nextMonthBtn">Next</button>
                    </div>
                    <div id="calendarGrid" class="calendar-grid"></div>
                </div>
                <form method="POST" action="{{ route('admin.holidays.store') }}">
                    @csrf
                    <div class="form-grid">
                        <div>
                            <label>Title</label>
                            <input type="text" name="title" required placeholder="Dashain Holiday">
                        </div>
                        <div>
                            <label>Date</label>
                            <input type="date" name="event_date" required>
                        </div>
                        <div>
                            <label>Type</label>
                            <select name="event_type" required>
                                @foreach($calendarEventTypes as $type)
                                    <option value="{{ $type }}">{{ strtoupper(str_replace('_', ' ', $type)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>Class (optional)</label>
                            <select name="school_class_id">
                                <option value="">All Classes</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>Section (optional)</label>
                            <select name="section_id">
                                <option value="">All Sections</option>
                                @foreach($sections as $section)
                                    <option value="{{ $section->id }}">{{ $section->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>Note</label>
                            <input type="text" name="note" placeholder="Optional note">
                        </div>
                    </div>
                    <p style="margin:10px 0 0;"><button type="submit" class="btn">Add Event</button></p>
                </form>
            </section>

            <section class="section">
                <h2 style="margin:0 0 8px;">Calendar Events</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Scope</th>
                            <th>Note</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($calendarEvents as $event)
                        <tr>
                            <form method="POST" action="{{ route('admin.holidays.update', $event) }}">
                                @csrf
                                @method('PATCH')
                                <td><input type="date" name="event_date" value="{{ $event->event_date?->format('Y-m-d') }}" required></td>
                                <td><input type="text" name="title" value="{{ $event->title }}" required></td>
                                <td>
                                    <select name="event_type" required>
                                        @foreach($calendarEventTypes as $type)
                                            <option value="{{ $type }}" @selected($event->event_type === $type)>{{ strtoupper(str_replace('_', ' ', $type)) }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="school_class_id" style="margin-bottom:6px;">
                                        <option value="">All Classes</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}" @selected((int) $event->school_class_id === (int) $class->id)>{{ $class->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="section_id">
                                        <option value="">All Sections</option>
                                        @foreach($sections as $section)
                                            <option value="{{ $section->id }}" @selected((int) $event->section_id === (int) $section->id)>{{ $section->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="note" value="{{ $event->note }}"></td>
                                <td>
                                    <select name="is_active" required>
                                        <option value="1" @selected($event->is_active)>Active</option>
                                        <option value="0" @selected(! $event->is_active)>Inactive</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="submit" class="btn-outline">Update</button>
                            </form>
                                    <form method="POST" action="{{ route('admin.holidays.destroy', $event) }}" style="margin-top:6px;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-outline" onclick="return confirm('Delete this calendar event?')">Delete</button>
                                    </form>
                                </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted">No calendar events added yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>
</body>
<script>
const holidayEvents = @json(
    $calendarEvents
        ->where('is_active', true)
        ->where('event_type', \App\Models\SchoolCalendarEvent::TYPE_HOLIDAY)
        ->groupBy(fn ($event) => $event->event_date?->format('Y-m-d'))
        ->map(fn ($items) => $items->pluck('title')->values()->all())
);

const showCalendarBtn = document.getElementById('showCalendarBtn');
const calendarWrap = document.getElementById('holidayCalendarWrap');
const calendarGrid = document.getElementById('calendarGrid');
const calendarTitle = document.getElementById('calendarTitle');
const prevMonthBtn = document.getElementById('prevMonthBtn');
const nextMonthBtn = document.getElementById('nextMonthBtn');

let current = new Date();
current.setDate(1);

const dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

function formatDateKey(dateObj) {
    const y = dateObj.getFullYear();
    const m = String(dateObj.getMonth() + 1).padStart(2, '0');
    const d = String(dateObj.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function renderCalendar() {
    if (!calendarGrid || !calendarTitle) return;
    calendarGrid.innerHTML = '';

    dayLabels.forEach((label) => {
        const head = document.createElement('div');
        head.className = 'calendar-head';
        head.textContent = label;
        calendarGrid.appendChild(head);
    });

    const year = current.getFullYear();
    const month = current.getMonth();
    const firstDay = new Date(year, month, 1);
    const startWeekDay = firstDay.getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysPrevMonth = new Date(year, month, 0).getDate();

    calendarTitle.textContent = firstDay.toLocaleString('en-US', { month: 'long', year: 'numeric' });

    for (let i = 0; i < startWeekDay; i++) {
        const cell = document.createElement('div');
        cell.className = 'calendar-cell is-muted';
        cell.textContent = String(daysPrevMonth - startWeekDay + i + 1);
        calendarGrid.appendChild(cell);
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const dateObj = new Date(year, month, day);
        const key = formatDateKey(dateObj);
        const sunday = dateObj.getDay() === 0;
        const holidayTitles = holidayEvents[key] || [];

        const cell = document.createElement('div');
        cell.className = 'calendar-cell';
        if (sunday) cell.classList.add('is-sunday');
        if (holidayTitles.length > 0) cell.classList.add('is-holiday');
        cell.innerHTML = `<div>${day}</div>`;

        if (holidayTitles.length > 0) {
            const note = document.createElement('span');
            note.className = 'calendar-note';
            note.textContent = holidayTitles[0];
            cell.appendChild(note);
        }

        calendarGrid.appendChild(cell);
    }
}

if (showCalendarBtn && calendarWrap) {
    showCalendarBtn.addEventListener('click', () => {
        const isOpen = calendarWrap.style.display === 'block';
        calendarWrap.style.display = isOpen ? 'none' : 'block';
        showCalendarBtn.textContent = isOpen ? 'Show Calendar' : 'Hide Calendar';
        if (!isOpen) renderCalendar();
    });
}

if (prevMonthBtn) {
    prevMonthBtn.addEventListener('click', () => {
        current = new Date(current.getFullYear(), current.getMonth() - 1, 1);
        renderCalendar();
    });
}

if (nextMonthBtn) {
    nextMonthBtn.addEventListener('click', () => {
        current = new Date(current.getFullYear(), current.getMonth() + 1, 1);
        renderCalendar();
    });
}
</script>
</html>
