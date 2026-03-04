<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
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
        table { width:100%; border-collapse:collapse; margin-top:8px; font-size:13px; }
        th, td { text-align:left; padding:9px 7px; border-bottom:1px solid var(--line); vertical-align:top; }
        .thumb { width:40px; height:40px; object-fit:cover; border-radius:8px; border:1px solid var(--line); }
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
            <strong>Student Management</strong>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn" type="submit">Logout</button></form>
        </header>
        <main class="wrap">
            @if (session('status')) <div class="status">{{ session('status') }}</div> @endif
            @if ($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif
            <div class="two">
                <section class="section">
                    <div class="section-head">
                        <h2 style="margin:0;">Students Listing</h2>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <a class="btn" href="#add-student">+ Add Student</a>
                            @if($featureFlags['student_import'] ?? false)
                                <a class="btn btn-outline" href="#import-students">+ Import CSV</a>
                            @endif
                        </div>
                    </div>
                    <form method="GET" action="{{ route('admin.students.index') }}" style="margin-top:10px;">
                        <div class="form-grid">
                            <div>
                                <label>Filter by Class</label>
                                <select id="studentFilterClassSelect" class="class-select" name="school_class_id">
                                    <option value="">All Classes</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" @selected((int) ($selectedClassId ?? 0) === (int) $class->id)>{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label>Filter by Section</label>
                                <select id="studentFilterSectionSelect" class="section-select" name="section_id">
                                    <option value="">All Sections</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section->id }}" @selected((int) ($selectedSectionId ?? 0) === (int) $section->id)>{{ $section->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="display:flex;align-items:flex-end;gap:8px;">
                                <button class="btn btn-outline" type="submit">Apply Filter</button>
                                <a class="btn btn-outline" href="{{ route('admin.students.index') }}">Reset</a>
                            </div>
                        </div>
                    </form>
                    <table>
                        <thead><tr><th>ID</th><th>Student</th><th>Class/Section</th><th>Action</th></tr></thead>
                        <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td>{{ $student->student_id }}</td>
                                <td>
                                    @if($student->photo_path)<img class="thumb" src="{{ asset('storage/'.$student->photo_path) }}" alt="student-photo">@endif
                                    <div><strong>{{ $student->name }}</strong></div>
                                    <div>{{ $student->email ?? '-' }}</div>
                                    <div>Device ID: {{ $student->device_identifier ?? '-' }}</div>
                                </td>
                                <td>{{ $student->schoolClass?->name ?? '-' }} / {{ $student->section?->name ?? '-' }}</td>
                                <td>
                                    <details style="margin-bottom:6px;">
                                        <summary class="btn btn-outline" style="display:inline-block;">Edit</summary>
                                        <form method="POST" action="{{ route('admin.students.update', $student) }}" enctype="multipart/form-data" style="margin-top:6px;">
                                            @csrf @method('PATCH')
                                            <div class="form-grid">
                                                <input name="name" value="{{ $student->name }}" required>
                                                <input type="email" name="email" value="{{ $student->email }}">
                                                <input name="device_identifier" value="{{ $student->device_identifier }}" placeholder="Device/Card ID">
                                                <input name="phone" value="{{ $student->phone }}">
                                                <select class="class-select" name="school_class_id"><option value="">Class</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected($student->school_class_id === $class->id)>{{ $class->name }}</option>@endforeach</select>
                                                <select class="section-select" name="section_id"><option value="">Section</option>@foreach($sections as $section)<option value="{{ $section->id }}" @selected($student->section_id === $section->id)>{{ $section->name }}</option>@endforeach</select>
                                                <input type="date" name="date_of_birth" value="{{ optional($student->date_of_birth)->format('Y-m-d') }}">
                                                <select name="gender"><option value="">Gender</option><option value="Male" @selected($student->gender==='Male')>Male</option><option value="Female" @selected($student->gender==='Female')>Female</option><option value="Other" @selected($student->gender==='Other')>Other</option></select>
                                                <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">
                                            </div>
                                            <p style="margin:6px 0 0;"><button type="submit" class="btn btn-outline">Save</button></p>
                                        </form>
                                    </details>
                                    <form method="POST" action="{{ route('admin.students.destroy', $student) }}" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button class="btn-danger" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">No students.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </section>
                <section class="section">
                    <h3 id="add-student">+ Add Student</h3>
                    <form method="POST" action="{{ route('admin.students.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-grid">
                            <div><label>Name</label><input name="name" required></div>
                            <div><label>Email</label><input type="email" name="email"></div>
                            <div><label>Device/Card ID</label><input name="device_identifier" placeholder="Optional"></div>
                            <div><label>Phone</label><input name="phone"></div>
                            <div><label>Class</label><select id="studentClassSelect" class="class-select" name="school_class_id"><option value="">Select</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></div>
                            <div><label>Section</label><select id="studentSectionSelect" class="section-select" name="section_id"><option value="">Select</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->name }}</option>@endforeach</select></div>
                            <div><label>DOB</label><input type="date" name="date_of_birth"></div>
                            <div><label>Gender</label><select name="gender"><option value="">Select</option><option>Male</option><option>Female</option><option>Other</option></select></div>
                            <div><label>Photo</label><input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp"></div>
                        </div>
                        <p style="margin:8px 0 0;"><button class="btn" type="submit">Add Student (Auto ID)</button></p>
                    </form>
                    @if($featureFlags['student_import'] ?? false)
                        <h3 id="import-students" style="margin-top:14px;">+ Import Students CSV</h3>
                        <form method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-grid"><div><label>CSV File</label><input type="file" name="file" accept=".csv,.txt" required></div></div>
                            <p style="margin:8px 0 0;"><button class="btn" type="submit">Import Students</button></p>
                        </form>
                    @else
                        <p class="error" style="margin-top:14px;">CSV import is locked in BASIC plan.</p>
                    @endif
                </section>
            </div>
        </main>
    </div>
</div>
</body>
<script>
const activeSectionsByClass = @json($activeSectionsByClass ?? []);

const bindClassSectionFilter = (root) => {
    const classSelect = root.querySelector('.class-select');
    const sectionSelect = root.querySelector('.section-select');
    if (!classSelect || !sectionSelect) {
        return;
    }

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
};

document.querySelectorAll('form').forEach((form) => bindClassSectionFilter(form));
</script>
</html>
