<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class & Section CRUD</title>
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
        input { width:100%; border:1px solid #cbd5e1; border-radius:9px; padding:8px 9px; font-size:14px; }
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
            <strong>Class & Section CRUD</strong>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn" type="submit">Logout</button></form>
        </header>
        <main class="wrap">
            @if (session('status')) <div class="status">{{ session('status') }}</div> @endif
            @if ($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif
            <div class="two">
                <section class="section">
                    <div class="section-head">
                        <h2 style="margin:0;">Class Listing</h2>
                        <a class="btn" href="#add-class">+ Add Class</a>
                    </div>
                    <table>
                        <thead><tr><th>Class</th><th>Order</th><th>Action</th></tr></thead>
                        <tbody>
                        @forelse($classes as $class)
                            <tr>
                                <td>{{ $class->name }}</td>
                                <td>{{ $class->display_order ?? '-' }}</td>
                                <td>
                                    <details style="margin-bottom:6px;">
                                        <summary class="btn btn-outline" style="display:inline-block;">Edit</summary>
                                        <form method="POST" action="{{ route('admin.classes.update', $class) }}" style="margin-top:6px;">
                                            @csrf @method('PATCH')
                                            <div class="form-grid">
                                                <input name="name" value="{{ $class->name }}" required>
                                                <input type="number" name="display_order" min="1" value="{{ $class->display_order }}">
                                            </div>
                                            <p style="margin:6px 0 0;"><button type="submit" class="btn btn-outline">Save</button></p>
                                        </form>
                                    </details>
                                    <form method="POST" action="{{ route('admin.classes.destroy', $class) }}" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No classes.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    <h3 id="add-class" style="margin-top:14px;">+ Add Class</h3>
                    <form method="POST" action="{{ route('admin.classes.store') }}">
                        @csrf
                        <div class="form-grid">
                            <div><label>Class Name</label><input name="name" placeholder="Grade 1" required></div>
                            <div><label>Display Order</label><input type="number" name="display_order" min="1"></div>
                        </div>
                        <p style="margin:8px 0 0;"><button class="btn" type="submit">Add</button></p>
                    </form>
                </section>
                <section class="section">
                    <div class="section-head">
                        <h2 style="margin:0;">Section Listing</h2>
                        <a class="btn" href="#add-section">+ Add Section</a>
                    </div>
                    <table>
                        <thead><tr><th>Section</th><th>Action</th></tr></thead>
                        <tbody>
                        @forelse($sections as $section)
                            <tr>
                                <td>{{ $section->name }}</td>
                                <td>
                                    <details style="margin-bottom:6px;">
                                        <summary class="btn btn-outline" style="display:inline-block;">Edit</summary>
                                        <form method="POST" action="{{ route('admin.sections.update', $section) }}" style="margin-top:6px;">
                                            @csrf @method('PATCH')
                                            <div class="form-grid"><input name="name" value="{{ $section->name }}" required></div>
                                            <p style="margin:6px 0 0;"><button type="submit" class="btn btn-outline">Save</button></p>
                                        </form>
                                    </details>
                                    <form method="POST" action="{{ route('admin.sections.destroy', $section) }}" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2">No sections.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    <h3 id="add-section" style="margin-top:14px;">+ Add Section</h3>
                    <form method="POST" action="{{ route('admin.sections.store') }}">
                        @csrf
                        <div class="form-grid">
                            <div><label>Section Name</label><input name="name" placeholder="A" required></div>
                        </div>
                        <p style="margin:8px 0 0;"><button class="btn" type="submit">Add</button></p>
                    </form>
                </section>
            </div>
            <section class="section">
                <div class="section-head">
                    <h2 style="margin:0;">Class-Section Active Mapping</h2>
                    <a class="btn" href="#map-class-section">+ Map Section To Class</a>
                </div>
                <table>
                    <thead><tr><th>Class</th><th>Section</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    @forelse($classSectionMappings as $mapping)
                        <tr>
                            <td>{{ $mapping->schoolClass?->name }}</td>
                            <td>{{ $mapping->section?->name }}</td>
                            <td>{{ $mapping->is_active ? 'Active' : 'Inactive' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.class_section_mappings.status', $mapping) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-outline">{{ $mapping->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No class-section mappings yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <h3 id="map-class-section" style="margin-top:14px;">+ Map Section To Class</h3>
                <form method="POST" action="{{ route('admin.class_section_mappings.store') }}">
                    @csrf
                    <div class="form-grid">
                        <div><label>Class</label><select name="school_class_id" required><option value="">Select</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></div>
                        <div><label>Section</label><select name="section_id" required><option value="">Select</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->name }}</option>@endforeach</select></div>
                        <div style="display:flex;align-items:flex-end;">
                            <label style="display:flex;gap:8px;align-items:center;margin:0;"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                        </div>
                    </div>
                    <p style="margin:8px 0 0;"><button class="btn" type="submit">Save Mapping</button></p>
                </form>
            </section>
        </main>
    </div>
</div>
</body>
</html>
