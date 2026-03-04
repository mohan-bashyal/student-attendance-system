<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Management</title>
    <style>
        :root { --bg:#f8fafc; --panel:#fff; --text:#0f172a; --muted:#475569; --line:#e2e8f0; --primary:#0369a1; --danger:#b91c1c; }
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
        .btn-outline { background:#fff; border:1px solid var(--line); color:var(--text); }
        .btn-danger { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
        .wrap { width:min(1240px,100%); margin:0 auto; padding:16px; }
        .section { background:var(--panel); border:1px solid var(--line); border-radius:14px; padding:14px; margin-bottom:12px; }
        .two { display:grid; gap:12px; grid-template-columns:repeat(auto-fit,minmax(360px,1fr)); }
        .form-grid { display:grid; gap:8px; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); }
        .section-head { display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
        label { display:block; font-size:12px; font-weight:600; color:var(--muted); margin-bottom:3px; }
        input, select { width:100%; border:1px solid #cbd5e1; border-radius:9px; padding:8px 9px; font-size:14px; }
        input[type="checkbox"] { width:auto; }
        table { width:100%; border-collapse:collapse; margin-top:8px; font-size:13px; }
        th, td { text-align:left; padding:9px 7px; border-bottom:1px solid var(--line); vertical-align:top; }
        .status { border:1px solid #bbf7d0; background:#f0fdf4; color:#166534; padding:10px 12px; border-radius:10px; margin-bottom:10px; }
        .error { border:1px solid #fecaca; background:#fef2f2; color:#991b1b; padding:10px 12px; border-radius:10px; margin-bottom:10px; }
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
            <strong>Teacher Management</strong>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn" type="submit">Logout</button></form>
        </header>
        <main class="wrap">
            @if (session('status')) <div class="status">{{ session('status') }}</div> @endif
            @if (session('generated_teacher_credentials'))
                @php($credentials = session('generated_teacher_credentials'))
                <div class="status">
                    <strong>Class Teacher One-Time Credentials</strong><br>
                    Name: {{ $credentials['teacher_name'] }}<br>
                    Email: {{ $credentials['teacher_email'] }}<br>
                    Password: <code>{{ $credentials['one_time_password'] }}</code><br>
                    Ask teacher to login and immediately change password.
                </div>
            @endif
            @if ($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif
            <div class="two">
                <section class="section">
                    <div class="section-head">
                        <h2 style="margin:0;">Teachers Listing</h2>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <a class="btn" href="#add-teacher">+ Add Teacher</a>
                            @if($featureFlags['teacher_import'] ?? false)
                                <a class="btn btn-outline" href="#import-teachers">+ Import CSV</a>
                            @endif
                        </div>
                    </div>
                    <table>
                        <thead><tr><th>Teacher</th><th>Action</th></tr></thead>
                        <tbody>
                        @forelse($teachers as $teacher)
                            <tr>
                                <td><strong>{{ $teacher->name }}</strong><br>{{ $teacher->email }}</td>
                                <td>
                                    <details style="margin-bottom:6px;">
                                        <summary class="btn btn-outline" style="display:inline-block;">Edit</summary>
                                        <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}" style="margin-top:6px;">
                                            @csrf @method('PATCH')
                                            <div class="form-grid">
                                                <input name="name" value="{{ $teacher->name }}" required>
                                                <input type="email" name="email" value="{{ $teacher->email }}" required>
                                                <input name="phone" value="{{ $teacher->phone }}">
                                            </div>
                                            <p style="margin:6px 0 0;"><button class="btn btn-outline" type="submit">Save</button></p>
                                        </form>
                                    </details>
                                    <form method="POST" action="{{ route('admin.teachers.attendance_access', $teacher) }}" style="margin-bottom:6px;">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="has_attendance_access" value="{{ $teacher->has_attendance_access ? 0 : 1 }}">
                                        <button class="btn btn-outline" type="submit">{{ $teacher->has_attendance_access ? 'Disable Attendance' : 'Enable Attendance' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.teachers.destroy', $teacher) }}">
                                        @csrf @method('DELETE')
                                        <button class="btn-danger" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2">No teachers.</td></tr>
                        @endforelse
                        </tbody>
                    </table>

                    <div class="section-head" style="margin-top:14px;">
                        <h3 style="margin:0;">Class Teacher Assignments</h3>
                        <a class="btn" href="#assign-class-teacher">+ Assign Class Teacher</a>
                    </div>
                    <table>
                        <thead><tr><th>Class</th><th>Section</th><th>Teacher</th><th>Action</th></tr></thead>
                        <tbody>
                        @forelse($classTeacherAssignments as $assignment)
                            <tr>
                                <td>{{ $assignment->schoolClass?->name }}</td>
                                <td>{{ $assignment->section?->name }}</td>
                                <td>{{ $assignment->teacher?->name }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.class_teachers.generate_password', $assignment) }}" style="margin-bottom:6px;">
                                        @csrf
                                        <button class="btn btn-outline" type="submit">Generate One-Time Password</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.class_teachers.destroy', $assignment) }}">
                                        @csrf @method('DELETE')
                                        <button class="btn-danger" type="submit">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">No class teacher assignments.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </section>
                <section class="section">
                    <h3 id="add-teacher">+ Add Teacher</h3>
                    <form method="POST" action="{{ route('admin.teachers.store') }}">
                        @csrf
                        <div class="form-grid">
                            <div><label>Name</label><input name="name" required></div>
                            <div><label>Email</label><input type="email" name="email" required></div>
                            <div><label>Phone</label><input name="phone"></div>
                            <div style="display:flex;align-items:flex-end;">
                                <label style="display:flex;gap:8px;align-items:center;margin:0;"><input type="checkbox" name="has_attendance_access" value="1" checked> Attendance Access</label>
                            </div>
                        </div>
                        <p style="margin:8px 0 0;"><button class="btn" type="submit">Add</button></p>
                    </form>

                    @if($featureFlags['teacher_import'] ?? false)
                        <h3 id="import-teachers" style="margin-top:14px;">+ Import Teachers CSV</h3>
                        <form method="POST" action="{{ route('admin.teachers.import') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-grid">
                                <div><label>CSV File</label><input type="file" name="file" accept=".csv,.txt" required></div>
                            </div>
                            <p style="margin:8px 0 0;"><button class="btn" type="submit">Import Teachers</button></p>
                        </form>
                        <p style="margin:8px 0 0;color:#475569;font-size:13px;">
                            CSV headers: <code>name,email,phone,has_attendance_access</code> (attendance access: yes/no or 1/0)
                        </p>
                    @else
                        <p class="error" style="margin-top:14px;">Teacher CSV import is locked in BASIC plan.</p>
                    @endif

                    <h3 id="assign-class-teacher" style="margin-top:14px;">+ Assign Class Teacher</h3>
                    <form method="POST" action="{{ route('admin.class_teachers.store') }}">
                        @csrf
                        <div class="form-grid">
                            <div><label>Teacher</label><select name="teacher_id" required><option value="">Select</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select></div>
                            <div><label>Class</label><select id="classTeacherClassSelect" name="school_class_id" required><option value="">Select</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></div>
                            <div><label>Section</label><select id="classTeacherSectionSelect" name="section_id" required><option value="">Select</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->name }}</option>@endforeach</select></div>
                        </div>
                        <p style="margin:8px 0 0;"><button class="btn" type="submit">Assign</button></p>
                    </form>
                </section>
            </div>
        </main>
    </div>
</div>
</body>
<script>
const activeSectionsByClass = @json($activeSectionsByClass ?? []);
const classSelect = document.getElementById('classTeacherClassSelect');
const sectionSelect = document.getElementById('classTeacherSectionSelect');

if (classSelect && sectionSelect) {
    const options = Array.from(sectionSelect.querySelectorAll('option'));
    const syncSections = () => {
        const classId = classSelect.value;
        const activeSectionIds = (activeSectionsByClass[classId] || []).map(String);
        options.forEach((option) => {
            if (option.value === '') {
                option.hidden = false;
                return;
            }
            option.hidden = !activeSectionIds.includes(option.value);
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
