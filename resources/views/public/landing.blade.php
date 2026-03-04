<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AttendPro SaaS</title>
    <style>
        :root {
            --bg:#f5f7fb;
            --panel:#ffffff;
            --text:#0f172a;
            --muted:#475569;
            --line:#e2e8f0;
            --primary:#0f766e;
            --primary-dark:#115e59;
            --accent:#1d4ed8;
            --soft:#ecfeff;
        }
        * { box-sizing:border-box; }
        body {
            margin:0;
            font-family:"Segoe UI","Manrope",sans-serif;
            color:var(--text);
            background:
                radial-gradient(1000px 500px at -10% -10%, #99f6e4 0%, transparent 50%),
                radial-gradient(900px 450px at 110% -5%, #bfdbfe 0%, transparent 48%),
                var(--bg);
        }
        .container { width:min(1240px, 100%); margin:0 auto; padding:18px; }
        .nav {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            padding:8px 0;
        }
        .brand { font-size:24px; font-weight:800; letter-spacing:0.2px; }
        .brand small { font-size:12px; font-weight:700; color:var(--muted); margin-left:8px; }
        .nav-actions { display:flex; gap:8px; }
        .btn, button {
            border:0;
            border-radius:12px;
            padding:10px 14px;
            font-weight:700;
            cursor:pointer;
        }
        .btn {
            background:var(--primary);
            color:#fff;
            text-decoration:none;
        }
        .btn:hover { background:var(--primary-dark); }
        .btn-outline {
            background:#fff;
            color:var(--text);
            border:1px solid var(--line);
            text-decoration:none;
        }
        .hero {
            margin-top:14px;
            display:grid;
            gap:14px;
            grid-template-columns:1.2fr 1fr;
        }
        .card {
            background:var(--panel);
            border:1px solid var(--line);
            border-radius:18px;
            padding:18px;
            box-shadow:0 8px 28px rgba(15, 23, 42, 0.06);
        }
        .hero-main {
            background:
                linear-gradient(125deg, rgba(15,118,110,0.12), rgba(29,78,216,0.12)),
                #fff;
        }
        .pill {
            display:inline-flex;
            align-items:center;
            gap:8px;
            border-radius:999px;
            padding:6px 11px;
            background:#e6fffa;
            border:1px solid #99f6e4;
            font-size:12px;
            font-weight:800;
            color:#0f766e;
            margin-bottom:10px;
        }
        h1 { margin:0; font-size:42px; line-height:1.12; }
        p { margin:0; color:var(--muted); }
        .hero-copy { margin-top:10px; font-size:16px; line-height:1.6; }
        .hero-list {
            margin:12px 0 0;
            padding-left:18px;
            display:grid;
            gap:8px;
            color:#334155;
        }
        .hero-cta { margin-top:16px; display:flex; flex-wrap:wrap; gap:10px; }
        .mini-stats {
            margin-top:14px;
            display:grid;
            gap:8px;
            grid-template-columns:repeat(3, minmax(0, 1fr));
        }
        .mini-stat {
            border:1px solid var(--line);
            background:#fff;
            border-radius:12px;
            padding:10px;
        }
        .mini-stat strong { font-size:22px; }
        .mini-stat span { display:block; margin-top:2px; color:var(--muted); font-size:12px; }
        .feature-grid {
            margin-top:16px;
            display:grid;
            gap:12px;
            grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));
        }
        .feature h3 { margin:0 0 8px; font-size:17px; }
        .feature p { font-size:14px; line-height:1.55; }
        .demo {
            margin-top:16px;
            display:grid;
            gap:12px;
            grid-template-columns:1.15fr 1fr;
        }
        .video-wrap {
            border:1px solid var(--line);
            border-radius:14px;
            overflow:hidden;
            background:#000;
            aspect-ratio:16 / 9;
        }
        .video-wrap iframe {
            width:100%;
            height:100%;
            border:0;
            display:block;
        }
        .video-placeholder {
            width:100%;
            height:100%;
            display:flex;
            flex-direction:column;
            justify-content:center;
            align-items:center;
            text-align:center;
            background:linear-gradient(135deg, #0f172a, #1e293b);
            color:#cbd5e1;
            padding:14px;
        }
        .video-placeholder strong { color:#fff; margin-bottom:6px; }
        .pricing {
            margin-top:16px;
            display:grid;
            gap:12px;
            grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));
        }
        .plan {
            position:relative;
            overflow:hidden;
        }
        .plan::after {
            content:"";
            position:absolute;
            right:-35px;
            top:-35px;
            width:120px;
            height:120px;
            border-radius:999px;
            background:rgba(148, 163, 184, 0.12);
        }
        .tag {
            display:inline-block;
            padding:4px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:800;
            margin-bottom:8px;
        }
        .tag-basic { background:#dbeafe; color:#1e3a8a; }
        .tag-pro { background:#dcfce7; color:#166534; }
        .tag-enterprise { background:#fef3c7; color:#92400e; }
        .price { margin:8px 0; font-size:34px; font-weight:800; }
        .price small { font-size:14px; color:var(--muted); font-weight:600; }
        .features { margin:10px 0 14px; padding-left:18px; min-height:120px; color:#334155; display:grid; gap:7px; }
        .stripe-note {
            margin-top:14px;
            border:1px dashed #94a3b8;
            border-radius:12px;
            padding:10px;
            color:#334155;
            font-size:14px;
            background:#f8fafc;
        }
        .status { margin-top:12px; border-radius:12px; padding:10px 12px; border:1px solid #bbf7d0; background:#f0fdf4; color:#166534; }
        .error { margin-top:12px; border-radius:12px; padding:10px 12px; border:1px solid #fecaca; background:#fef2f2; color:#991b1b; }
        .foot { margin-top:14px; color:var(--muted); font-size:13px; }
        @media (max-width:1020px) {
            .hero, .demo { grid-template-columns:1fr; }
            h1 { font-size:34px; }
        }
    </style>
</head>
<body>
<div class="container">
    <header class="nav">
        <div class="brand">AttendPro <small>School Attendance SaaS</small></div>
        <div class="nav-actions">
            <a class="btn-outline" href="{{ route('login') }}">Login</a>
        </div>
    </header>

    <section class="hero">
        <article class="card hero-main">
            <div class="pill">LIVE READY PLATFORM</div>
            <h1>Modern Attendance System Built For Schools And Class Teachers</h1>
            <p class="hero-copy">
                One platform for daily attendance, class teacher workflow, student-parent portals, and subscription-based controls.
                Start with the right plan and scale to enterprise-level automation.
            </p>
            <ul class="hero-list">
                <li>Class teacher based workflow with admin verification controls</li>
                <li>Student and parent portals with attendance insights</li>
                <li>Enterprise device attendance integration support</li>
                <li>Secure role-based access and audit-ready architecture</li>
            </ul>
            <div class="hero-cta">
                <a class="btn" href="#pricing">Choose Plan</a>
                <a class="btn-outline" href="#demo">Watch Live Demo</a>
            </div>
            <div class="mini-stats">
                <div class="mini-stat"><strong>3</strong><span>Plans</span></div>
                <div class="mini-stat"><strong>Role</strong><span>Based Access</span></div>
                <div class="mini-stat"><strong>API</strong><span>Device Support</span></div>
            </div>
        </article>

        <article class="card">
            <h2 style="margin:0 0 8px;">What You Get</h2>
            <div class="feature-grid" style="margin-top:0;">
                <article class="feature">
                    <h3>Admin Control Center</h3>
                    <p>Manage classes, sections, teachers, students, approvals, attendance reports and plan-level controls.</p>
                </article>
                <article class="feature">
                    <h3>Teacher Productivity</h3>
                    <p>Fast class attendance marking, bulk status, correction windows and request-based student operations.</p>
                </article>
                <article class="feature">
                    <h3>Student + Parent Portal</h3>
                    <p>Attendance history, monthly trends, percentage visibility, and alert-driven engagement.</p>
                </article>
                <article class="feature">
                    <h3>Enterprise Automation</h3>
                    <p>Device API integration, audit logs, export tools, and advanced operational coverage.</p>
                </article>
            </div>
        </article>
    </section>

    <section class="demo" id="demo">
        <article class="card">
            <h2 style="margin:0 0 8px;">Live Demo Video</h2>
            <p>Use this section to showcase complete software walkthrough for new schools.</p>
            <div class="video-wrap" style="margin-top:10px;">
                @if(!empty($demoVideoUrl))
                    <iframe src="{{ $demoVideoUrl }}" allowfullscreen></iframe>
                @else
                    <div class="video-placeholder">
                        <strong>Demo Video Placeholder</strong>
                        <span>Set <code>DEMO_VIDEO_URL</code> in <code>.env</code> to show YouTube/Vimeo embed here.</span>
                    </div>
                @endif
            </div>
        </article>
        <article class="card">
            <h2 style="margin:0 0 8px;">Onboarding Flow</h2>
            <ol style="margin:0; padding-left:18px; color:#334155; display:grid; gap:8px;">
                <li>Select plan and click <strong>Pay With Stripe</strong>.</li>
                <li>Complete payment (or test bypass when Stripe key not configured).</li>
                <li>Admin registration form unlocks automatically.</li>
                <li>Create school profile and admin account.</li>
                <li>Start setup and go live with class-wise attendance.</li>
            </ol>
            <div class="stripe-note">
                Stripe payment is integrated. In test mode (without key), registration opens directly for development testing.
            </div>
        </article>
    </section>

    <section id="pricing" class="pricing">
        <article class="card plan">
            <span class="tag tag-basic">BASIC</span>
            <h3 style="margin:0;">Basic Plan</h3>
            <p class="price">$29 <small>/ month</small></p>
            <ul class="features">
                <li>Daily attendance marking workflow</li>
                <li>Teacher + student + class management</li>
                <li>Student portal summary access</li>
                <li>Up to 500 students</li>
            </ul>
            <form method="POST" action="{{ route('public.checkout') }}">
                @csrf
                <input type="hidden" name="plan" value="basic">
                <button type="submit" class="btn" style="width:100%;">Pay With Stripe</button>
            </form>
        </article>

        <article class="card plan">
            <span class="tag tag-pro">PRO</span>
            <h3 style="margin:0;">Pro Plan</h3>
            <p class="price">$69 <small>/ month</small></p>
            <ul class="features">
                <li>Everything in Basic</li>
                <li>Advanced attendance filters/reports</li>
                <li>Parent alerts + admin notifications</li>
                <li>Up to 2,000 students</li>
            </ul>
            <form method="POST" action="{{ route('public.checkout') }}">
                @csrf
                <input type="hidden" name="plan" value="pro">
                <button type="submit" class="btn" style="width:100%;">Pay With Stripe</button>
            </form>
        </article>

        <article class="card plan">
            <span class="tag tag-enterprise">ENTERPRISE</span>
            <h3 style="margin:0;">Enterprise Plan</h3>
            <p class="price">$149 <small>/ month</small></p>
            <ul class="features">
                <li>Everything in Pro</li>
                <li>Device/biometric attendance API</li>
                <li>Audit logs + advanced exports</li>
                <li>Up to 10,000 students</li>
            </ul>
            <form method="POST" action="{{ route('public.checkout') }}">
                @csrf
                <input type="hidden" name="plan" value="enterprise">
                <button type="submit" class="btn" style="width:100%;">Pay With Stripe</button>
            </form>
        </article>
    </section>

    @if(session('status'))
        <section class="status">{{ session('status') }}</section>
    @endif

    @if($errors->any())
        <section class="error">{{ $errors->first() }}</section>
    @endif

    <p class="foot">
        Note: Plan prices are starter prices for demo setup. You can adjust pricing and tax logic anytime.
    </p>
</div>
</body>
</html>
