<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Dokumentasi RESTful API Koperasi App v1 untuk integrasi mobile PWA, Gate SSO, dan layanan internal.">
    <title>Dokumentasi API v1 — Koperasi App</title>
    <link rel="icon" type="image/png" href="/images/logo.png">
    <style>
        :root {
            --bg-page: #FFFFFF;
            --bg-sidebar: #FAFAFA;
            --bg-surface: #F8FAFC;
            --bg-subtle: #F1F5F9;
            --border-light: #F1F5F9;
            --border: #E2E8F0;
            --border-dark: #CBD5E1;

            --text-heading: #0F172A;
            --text-body: #334155;
            --text-muted: #64748B;
            --text-light: #94A3B8;

            --navy: #0F1E36;
            --navy-light: #1A2E4D;
            --navy-surface: #0B132B;

            --green: #1FA24C;
            --green-dark: #178A3E;
            --green-subtle: #E8F8EE;
            --green-border: #B7E8C8;

            --blue: #2563EB;
            --blue-subtle: #EFF6FF;
            --blue-border: #BFDBFE;

            --code-bg: #0B132B;
            --code-header: #111C38;
            --code-text: #E2E8F0;

            --radius-xs: 4px;
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-full: 9999px;

            --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            --font-mono: ui-monospace, 'SFMono-Regular', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-text-size-adjust: 100%; scroll-behavior: smooth; }
        body {
            font-family: var(--font-sans);
            background: var(--bg-page);
            color: var(--text-body);
            line-height: 1.6;
            font-size: 14px;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* Topbar Header */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            background: rgba(255, 255, 255, 0.96);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
        }
        .topbar-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }
        .brand-logo {
            width: 30px;
            height: 30px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            object-fit: contain;
            background: #fff;
        }
        .brand-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: -0.02em;
        }
        .brand-badge {
            font-size: 11px;
            font-weight: 700;
            background: var(--bg-subtle);
            color: var(--text-muted);
            padding: 2px 7px;
            border-radius: var(--radius-full);
            border: 1px solid var(--border);
        }
        .topbar-nav {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .topbar-link {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            transition: all 0.15s ease;
        }
        .topbar-link:hover {
            color: var(--navy);
            background: var(--bg-surface);
        }
        .topbar-btn {
            font-size: 12.5px;
            font-weight: 700;
            color: #fff;
            background: var(--navy);
            text-decoration: none;
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            transition: all 0.15s ease;
            margin-left: 6px;
        }
        .topbar-btn:hover {
            background: var(--navy-light);
        }

        /* Two-Column Corporate Layout */
        .layout {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            min-height: calc(100vh - 60px);
        }
        [id] { scroll-margin-top: 80px; }

        /* Left Navigation Sidebar */
        .sidebar {
            position: sticky;
            top: 60px;
            height: calc(100vh - 60px);
            overflow-y: auto;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            padding: 24px 18px 40px;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 4px; }

        .search-container {
            position: relative;
            margin-bottom: 20px;
        }
        .search-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-light);
            margin-bottom: 6px;
        }
        .search-box {
            position: relative;
            display: flex;
            align-items: center;
        }
        .search-box input {
            width: 100%;
            font-size: 12.5px;
            font-family: inherit;
            padding: 7px 30px 7px 10px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: #fff;
            color: var(--text-heading);
            transition: all 0.15s ease;
        }
        .search-box input:focus {
            outline: none;
            border-color: var(--navy);
            box-shadow: 0 0 0 2px rgba(15, 30, 54, 0.08);
        }
        .search-kbd {
            position: absolute;
            right: 8px;
            font-size: 10px;
            font-weight: 600;
            color: var(--text-light);
            background: var(--bg-subtle);
            border: 1px solid var(--border);
            padding: 1px 4px;
            border-radius: 3px;
            pointer-events: none;
        }
        .search-clear {
            position: absolute;
            right: 8px;
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 14px;
            cursor: pointer;
            display: none;
        }
        .search-clear.show { display: block; }

        .sidebar-section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-light);
            margin: 20px 8px 6px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: var(--radius-sm);
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.12s ease;
            position: relative;
        }
        .nav-item:hover {
            color: var(--navy);
            background: rgba(15, 30, 54, 0.04);
        }
        .nav-item[aria-current="true"] {
            color: var(--navy);
            background: #fff;
            font-weight: 700;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }
        .nav-item[aria-current="true"]::before {
            content: '';
            position: absolute;
            left: 0;
            top: 6px;
            bottom: 6px;
            width: 3px;
            border-radius: 2px;
            background: var(--green);
        }

        .side-empty {
            font-size: 12px;
            color: var(--text-light);
            padding: 16px 8px;
            text-align: center;
        }

        /* HTTP Method Tags (Minimalist Corporate) */
        .method-tag {
            font-family: var(--font-mono);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.02em;
            padding: 2px 6px;
            border-radius: 4px;
            line-height: 1.2;
            display: inline-block;
            text-align: center;
            min-width: 44px;
            flex-shrink: 0;
        }
        .method-tag.get {
            background: var(--green-subtle);
            color: var(--green-dark);
            border: 1px solid var(--green-border);
        }
        .method-tag.post {
            background: var(--bg-subtle);
            color: var(--navy);
            border: 1px solid var(--border);
        }

        /* Right Content Canvas */
        .canvas {
            padding: 40px 48px 100px;
            max-width: 960px;
        }

        /* Typography & Headings */
        h1.doc-title {
            font-size: 26px;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: -0.03em;
            line-height: 1.2;
            margin-bottom: 8px;
        }
        .doc-lead {
            font-size: 14.5px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 24px;
        }
        h2.doc-h2 {
            font-size: 19px;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: -0.02em;
            margin: 48px 0 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }
        h3.doc-h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-heading);
            margin: 24px 0 8px;
        }
        p {
            margin-bottom: 14px;
            color: var(--text-body);
        }
        p:last-child { margin-bottom: 0; }

        /* Meta Banner */
        .meta-strip {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            padding: 12px 16px;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            margin-bottom: 36px;
            font-size: 13px;
        }
        .meta-strip-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .meta-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-light);
        }
        .meta-value {
            font-family: var(--font-mono);
            font-weight: 600;
            color: var(--navy);
            background: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid var(--border);
        }

        /* Note Callout (Minimalist) */
        .callout {
            padding: 14px 18px;
            border-left: 3px solid var(--navy);
            background: var(--bg-surface);
            border-radius: 0 var(--radius-md) var(--radius-md) 0;
            margin: 18px 0;
            font-size: 13.5px;
        }
        .callout.info {
            border-left-color: var(--green);
        }
        .callout-title {
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 4px;
            font-size: 13px;
        }

        /* Endpoint Section (Continuous Canvas, Not Boxed Cards) */
        .endpoint-section {
            padding-bottom: 48px;
            margin-bottom: 48px;
            border-bottom: 1px solid var(--border-light);
        }
        .endpoint-section:last-of-type {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .endpoint-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .endpoint-identity {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .endpoint-path-text {
            font-family: var(--font-mono);
            font-size: 15px;
            font-weight: 700;
            color: var(--navy);
        }
        .auth-tag {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            background: var(--bg-subtle);
            padding: 2px 8px;
            border-radius: var(--radius-full);
            border: 1px solid var(--border);
        }
        .auth-tag.token {
            color: var(--navy);
            background: #fff;
            border-color: var(--border-dark);
            font-weight: 700;
        }

        .btn-inline {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            background: #fff;
            border: 1px solid var(--border);
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.12s ease;
            text-decoration: none;
        }
        .btn-inline:hover {
            color: var(--navy);
            border-color: var(--border-dark);
            background: var(--bg-surface);
        }
        .btn-inline.accent {
            color: var(--green-dark);
            border-color: var(--green-border);
            background: var(--green-subtle);
        }
        .btn-inline.accent:hover {
            background: #D8F2E2;
        }

        .endpoint-desc {
            font-size: 14px;
            color: var(--text-body);
            line-height: 1.6;
            margin-bottom: 16px;
        }

        /* Clean Parameter Table */
        table.clean-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin: 14px 0 20px;
        }
        table.clean-table th {
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-light);
            padding: 8px 12px;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            font-weight: 700;
            background: var(--bg-surface);
        }
        table.clean-table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border-light);
            vertical-align: top;
            color: var(--text-body);
        }
        code.field-name {
            font-family: var(--font-mono);
            font-size: 12.5px;
            font-weight: 700;
            color: var(--navy);
        }
        .field-type {
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--text-light);
            margin-left: 4px;
        }
        .req-pill {
            font-size: 10px;
            font-weight: 700;
            color: #DC2626;
            background: #FEF2F2;
            padding: 1px 5px;
            border-radius: 3px;
            display: inline-block;
        }
        .opt-pill {
            font-size: 10px;
            font-weight: 600;
            color: var(--text-light);
            background: var(--bg-subtle);
            padding: 1px 5px;
            border-radius: 3px;
            display: inline-block;
        }

        /* Code Snippet Pane (Corporate Dark) */
        .code-container {
            background: var(--code-bg);
            border-radius: var(--radius-md);
            overflow: hidden;
            margin: 16px 0 24px;
            border: 1px solid #1E293B;
        }
        .code-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 12px;
            background: var(--code-header);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            min-height: 36px;
        }
        .code-tab-nav {
            display: flex;
            gap: 2px;
        }
        .code-tab-btn {
            background: none;
            border: none;
            color: #94A3B8;
            font-size: 12px;
            font-weight: 600;
            padding: 8px 12px;
            cursor: pointer;
            transition: all 0.12s ease;
            border-bottom: 2px solid transparent;
        }
        .code-tab-btn:hover { color: #fff; }
        .code-tab-btn.active {
            color: #38BDF8;
            border-bottom-color: #38BDF8;
            font-weight: 700;
        }
        .btn-copy-action {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #CBD5E1;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.12s ease;
        }
        .btn-copy-action:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        pre.code-view {
            padding: 14px 16px;
            color: var(--code-text);
            font-family: var(--font-mono);
            font-size: 12.5px;
            line-height: 1.55;
            overflow-x: auto;
            margin: 0;
            background: var(--code-bg);
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* Status Codes List */
        .status-row {
            display: flex;
            align-items: baseline;
            gap: 12px;
            font-size: 13px;
            padding: 4px 0;
        }
        .status-num {
            font-family: var(--font-mono);
            font-weight: 700;
            font-size: 11.5px;
            min-width: 40px;
        }
        .status-num.ok { color: var(--green-dark); }
        .status-num.err { color: #DC2626; }
        .status-desc { color: var(--text-muted); }

        /* Minimalist Interactive Console */
        .console-panel {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-top: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .form-row {
            margin-bottom: 14px;
        }
        .form-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 6px;
        }
        .form-control {
            width: 100%;
            font-size: 13px;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: inherit;
            background: #fff;
            color: var(--text-heading);
            transition: all 0.15s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--navy);
            box-shadow: 0 0 0 2px rgba(15, 30, 54, 0.08);
        }
        textarea.form-control {
            font-family: var(--font-mono);
            font-size: 12px;
            resize: vertical;
            min-height: 60px;
        }
        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        @media (max-width: 640px) {
            .form-grid-2 { grid-template-columns: 1fr; }
        }

        .btn-submit-test {
            width: 100%;
            padding: 10px 16px;
            font-size: 13.5px;
            font-weight: 700;
            color: #fff;
            background: var(--navy);
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .btn-submit-test:hover { background: var(--navy-light); }
        .btn-submit-test:disabled { opacity: 0.6; cursor: wait; }

        .console-meta-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 12px;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            margin-top: 14px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-indicator {
            padding: 1px 6px;
            border-radius: 3px;
            font-family: var(--font-mono);
            font-weight: 700;
        }
        .status-indicator.success { background: var(--green-subtle); color: var(--green-dark); }
        .status-indicator.error { background: #FEF2F2; color: #DC2626; }
        .status-indicator.waiting { background: var(--bg-subtle); color: var(--text-light); }

        /* Toast Feedback */
        .toast-bubble {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 50;
            background: var(--navy);
            color: #fff;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-size: 12.5px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.2s ease;
            pointer-events: none;
        }
        .toast-bubble.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Responsive Breakpoints */
        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; border-right: none; border-bottom: 1px solid var(--border); }
            .canvas { padding: 24px 20px 60px; }
        }
    </style>
</head>
<body>

<!-- Header Topbar -->
<header class="topbar">
    <div class="topbar-container">
        <a href="{{ url('/api/documentation') }}" class="brand-link">
            <img src="/images/logo.png" alt="Logo Koperasi" class="brand-logo" onerror="this.style.display='none'">
            <span class="brand-title">Koperasi App</span>
            <span class="brand-badge">API v1</span>
        </a>
        <nav class="topbar-nav">
            <a href="#autentikasi" class="topbar-link">Autentikasi</a>
            <a href="#anggota" class="topbar-link">Data Anggota</a>
            <a href="#konsol" class="topbar-btn">Konsol coba</a>
        </nav>
    </div>
</header>

<div class="layout">
    <!-- Left Navigation Sidebar -->
    <aside class="sidebar" aria-label="Daftar endpoint">
        <div class="search-container">
            <label for="side-search" class="search-label">Cari endpoint</label>
            <div class="search-box">
                <input type="search" id="side-search" placeholder="Cari endpoint..." autocomplete="off">
                <span class="search-kbd" id="search-kbd">/</span>
                <button class="search-clear" id="search-clear" type="button" aria-label="Bersihkan">×</button>
            </div>
        </div>

        <nav aria-label="Menu navigasi dokumen">
            <div class="sidebar-section-title">Panduan Umum</div>
            <a class="nav-item" href="#overview">Ringkasan Arsitektur</a>
            <a class="nav-item" href="#autentikasi">Autentikasi & Header</a>
            <a class="nav-item" href="#standar-error">Standar Status & Error</a>

            <div class="sidebar-section-title">Autentikasi</div>
            <a class="nav-item" href="#ep-login" data-method="POST">
                <span class="method-tag post">POST</span>
                <span>/login</span>
            </a>
            <a class="nav-item" href="#ep-logout" data-method="POST">
                <span class="method-tag post">POST</span>
                <span>/logout</span>
            </a>
            <a class="nav-item" href="#ep-user" data-method="GET">
                <span class="method-tag get">GET</span>
                <span>/user</span>
            </a>
            <a class="nav-item" href="#ep-sso-token" data-method="POST">
                <span class="method-tag post">POST</span>
                <span>/sso/token</span>
            </a>

            <div class="sidebar-section-title">Data Anggota</div>
            <a class="nav-item" href="#ep-pinjaman-aktif" data-method="GET">
                <span class="method-tag get">GET</span>
                <span>/pinjaman-aktif</span>
            </a>
            <a class="nav-item" href="#ep-riwayat" data-method="GET">
                <span class="method-tag get">GET</span>
                <span>/riwayat-pinjaman</span>
            </a>
            <a class="nav-item" href="#ep-limit" data-method="GET">
                <span class="method-tag get">GET</span>
                <span>/limit</span>
            </a>
            <a class="nav-item" href="#ep-simpanan" data-method="GET">
                <span class="method-tag get">GET</span>
                <span>/simpanan</span>
            </a>

            <div class="sidebar-section-title">Pengujian</div>
            <a class="nav-item" href="#konsol">Konsol coba</a>
        </nav>
        <p class="side-empty" id="side-empty" hidden>Tidak ada endpoint yang cocok.</p>
    </aside>

    <!-- Right Reading Canvas (Continuous Document) -->
    <main class="canvas" id="konten">
        <!-- Overview Header -->
        <section id="overview" style="margin-bottom: 40px;">
            <h1 class="doc-title">Dokumentasi API v1</h1>
            <p class="doc-lead">
                Spesifikasi RESTful API resmi untuk integrasi aplikasi mobile (PWA), portal anggota, dan sinkronisasi layanan internal Koperasi.
            </p>

            <div class="meta-strip">
                <div class="meta-strip-item">
                    <span class="meta-label">Base URL</span>
                    <span class="meta-value">{{ url('/api/v1') }}</span>
                    <button class="btn-inline" type="button" onclick="salin('{{ url('/api/v1') }}', 'Base URL disalin')">Salin</button>
                </div>
                <div class="meta-strip-item" style="margin-left: auto;">
                    <span class="meta-label">Auth Mode</span>
                    <span class="meta-value">{{ config('auth.mode', 'local') }}</span>
                    <span class="meta-label" style="margin-left: 8px;">Rate Limit</span>
                    <span class="meta-value">60 req/min</span>
                </div>
            </div>

            <h3 class="doc-h3">Prinsip Arsitektur: Self-Scoped Context & Anti-IDOR</h3>
            <p>
                Seluruh endpoint di bawah naungan <code style="font-family:var(--font-mono);font-size:12.5px;color:var(--navy);">/api/v1/anggota/*</code> menerapkan prinsip <strong>Self-Scoped Context</strong>. Data anggota diambil langsung dari token otentikasi akun yang sedang aktif (<code style="font-family:var(--font-mono);font-size:12.5px;">auth()-&gt;user()-&gt;anggota</code>).
            </p>
            <div class="callout info">
                <div class="callout-title">Keamanan Tanpa Parameter ID</div>
                Endpoint tidak menerima parameter <code style="font-family:var(--font-mono);">anggota_id</code>. Desain ini menutup celah Insecure Direct Object Reference (IDOR) secara arsitektural sehingga data pinjaman, simpanan, dan limit anggota terlindungi dari manipulasi parameter.
            </div>
        </section>

        <!-- Autentikasi -->
        <section id="autentikasi" style="margin-bottom: 48px;">
            <h2 class="doc-h2">Autentikasi & Header</h2>
            <p>
                Sistem otentikasi menggunakan <strong>Laravel Sanctum Bearer Token</strong> dengan dua mode kerja sesuai konfigurasi server:
            </p>
            <ul style="padding-left: 20px; margin-bottom: 16px; color: var(--text-body); font-size: 13.5px;">
                <li style="margin-bottom: 6px;">
                    <strong>Mode Lokal (<code style="font-family:var(--font-mono);">AUTH_MODE=local</code>):</strong> Login email dan password melalui endpoint <code style="font-family:var(--font-mono);">POST /api/v1/login</code> untuk menerbitkan token Bearer.
                </li>
                <li>
                    <strong>Mode SSO (<code style="font-family:var(--font-mono);">AUTH_MODE=sso</code>):</strong> Login kata sandi dinonaktifkan demi kepatuhan Gate IdP. Pengguna masuk melalui peramban, lalu menukar sesi web menjadi token API melalui <code style="font-family:var(--font-mono);">POST /api/v1/sso/token</code>.
                </li>
            </ul>

            <h3 class="doc-h3">Header Permintaan Standar</h3>
            <table class="clean-table">
                <thead>
                    <tr><th>Header</th><th>Status</th><th>Contoh</th><th>Keterangan</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code class="field-name">Accept</code></td>
                        <td><span class="req-pill">Wajib</span></td>
                        <td><code style="font-family:var(--font-mono);">application/json</code></td>
                        <td>Memastikan respons dikembalikan dalam format standar JSON.</td>
                    </tr>
                    <tr>
                        <td><code class="field-name">Content-Type</code></td>
                        <td><span class="req-pill">Wajib (POST)</span></td>
                        <td><code style="font-family:var(--font-mono);">application/json</code></td>
                        <td>Diperlukan saat mengirimkan body payload JSON.</td>
                    </tr>
                    <tr>
                        <td><code class="field-name">Authorization</code></td>
                        <td><span class="req-pill">Wajib (Auth)</span></td>
                        <td><code style="font-family:var(--font-mono);">Bearer 1|abc123...</code></td>
                        <td>Token otentikasi Sanctum yang masih berlaku.</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Standar Status & Error -->
        <section id="standar-error" style="margin-bottom: 48px;">
            <h2 class="doc-h2">Standar Status & Penanganan Error</h2>
            <p>
                Respon kesalahan menggunakan kode status HTTP standar dengan format pesan terstruktur:
            </p>
            <div style="background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 12px 16px; margin: 12px 0 20px;">
                <div class="status-row">
                    <span class="status-num ok">200</span>
                    <span class="status-desc">Permintaan berhasil diproses.</span>
                </div>
                <div class="status-row">
                    <span class="status-num err">401</span>
                    <span class="status-desc">Tidak terotentikasi (token tidak disertakan atau sudah dicabut).</span>
                </div>
                <div class="status-row">
                    <span class="status-num err">403</span>
                    <span class="status-desc">Akses ditolak (misalnya memanggil login password saat mode SSO aktif).</span>
                </div>
                <div class="status-row">
                    <span class="status-num err">404</span>
                    <span class="status-desc">Pengguna belum memiliki profil data anggota koperasi.</span>
                </div>
                <div class="status-row">
                    <span class="status-num err">422</span>
                    <span class="status-desc">Validasi parameter atau payload gagal.</span>
                </div>
                <div class="status-row">
                    <span class="status-num err">429</span>
                    <span class="status-desc">Melebihi batas rate limit (60 request per menit).</span>
                </div>
            </div>
        </section>

        <!-- ==================== ENDPOINT AUTENTIKASI ==================== -->
        <h2 class="doc-h2">Endpoint Autentikasi</h2>

        <!-- POST /login -->
        <div class="endpoint-section" id="ep-login" data-method="POST">
            <div class="endpoint-title-row">
                <div class="endpoint-identity">
                    <span class="method-tag post">POST</span>
                    <span class="endpoint-path-text">/api/v1/login</span>
                    <span class="auth-tag">Publik (Mode Lokal)</span>
                </div>
                <div style="display: flex; gap: 6px;">
                    <button class="btn-inline" type="button" onclick="salin('{{ url('/api/v1/login') }}', 'URL disalin')">Salin URL</button>
                    <button class="btn-inline accent" type="button" onclick="siapkanUji('POST', '/api/v1/login', '', '{\n  &quot;email&quot;: &quot;nama@koperasi.test&quot;,\n  &quot;password&quot;: &quot;password&quot;\n}', 'none')">Uji Endpoint</button>
                </div>
            </div>

            <p class="endpoint-desc">
                Otentikasi pengguna via email dan kata sandi untuk menerbitkan token Sanctum (hanya aktif saat mode lokal). Jika sistem berjalan dalam mode SSO, server mengembalikan 403 Forbidden.
            </p>

            <h4 style="font-size: 12px; font-weight: 700; color: var(--text-heading); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Request Body</h4>
            <table class="clean-table">
                <thead><tr><th>Field</th><th>Tipe</th><th>Status</th><th>Keterangan</th></tr></thead>
                <tbody>
                    <tr>
                        <td><code class="field-name">email</code></td>
                        <td><span class="field-type">string</span></td>
                        <td><span class="req-pill">Wajib</span></td>
                        <td>Alamat email terdaftar akun anggota koperasi.</td>
                    </tr>
                    <tr>
                        <td><code class="field-name">password</code></td>
                        <td><span class="field-type">string</span></td>
                        <td><span class="req-pill">Wajib</span></td>
                        <td>Kata sandi akun pengguna.</td>
                    </tr>
                </tbody>
            </table>

            <div class="code-container">
                <div class="code-header-bar">
                    <div class="code-tab-nav">
                        <button class="code-tab-btn active" type="button" onclick="tab(this, 'login-req-curl')">cURL</button>
                        <button class="code-tab-btn" type="button" onclick="tab(this, 'login-req-js')">JavaScript</button>
                        <button class="code-tab-btn" type="button" onclick="tab(this, 'login-req-php')">PHP</button>
                        <button class="code-tab-btn" type="button" onclick="tab(this, 'login-res-200')">Response 200</button>
                    </div>
                    <button class="btn-copy-action" type="button" onclick="salinTab(this)">Salin</button>
                </div>
                <div class="tab-content active" id="login-req-curl">
                    <pre class="code-view">curl -X POST "{{ url('/api/v1/login') }}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "nama@koperasi.test",
    "password": "kata-sandi"
  }'</pre>
                </div>
                <div class="tab-content" id="login-req-js">
                    <pre class="code-view">const response = await fetch("{{ url('/api/v1/login') }}", {
  method: "POST",
  headers: {
    "Accept": "application/json",
    "Content-Type": "application/json"
  },
  body: JSON.stringify({
    email: "nama@koperasi.test",
    password: "kata-sandi"
  })
});
const data = await response.json();</pre>
                </div>
                <div class="tab-content" id="login-req-php">
                    <pre class="code-view">use Illuminate\Support\Facades\Http;

$response = Http::acceptJson()->post("{{ url('/api/v1/login') }}", [
    'email' => 'nama@koperasi.test',
    'password' => 'kata-sandi',
]);</pre>
                </div>
                <div class="tab-content" id="login-res-200">
                    <pre class="code-view">{
  "user": {
    "id": 4,
    "name": "Budi Santoso",
    "email": "nama@koperasi.test"
  },
  "token": "1|qX9u7mK..."
}</pre>
                </div>
            </div>
        </div>

        <!-- POST /logout -->
        <div class="endpoint-section" id="ep-logout" data-method="POST">
            <div class="endpoint-title-row">
                <div class="endpoint-identity">
                    <span class="method-tag post">POST</span>
                    <span class="endpoint-path-text">/api/v1/logout</span>
                    <span class="auth-tag token">Bearer Token</span>
                </div>
                <div style="display: flex; gap: 6px;">
                    <button class="btn-inline" type="button" onclick="salin('{{ url('/api/v1/logout') }}', 'URL disalin')">Salin URL</button>
                    <button class="btn-inline accent" type="button" onclick="siapkanUji('POST', '/api/v1/logout', '', '', 'bearer')">Uji Endpoint</button>
                </div>
            </div>

            <p class="endpoint-desc">
                Mencabut dan menghapus token Sanctum yang sedang aktif dari database.
            </p>

            <div class="code-container">
                <div class="code-header-bar">
                    <div class="code-tab-nav">
                        <button class="code-tab-btn active" type="button" onclick="tab(this, 'logout-res')">Response 200</button>
                    </div>
                    <button class="btn-copy-action" type="button" onclick="salinTab(this)">Salin</button>
                </div>
                <div class="tab-content active" id="logout-res">
                    <pre class="code-view">{
  "message": "Logged out successfully"
}</pre>
                </div>
            </div>
        </div>

        <!-- GET /user -->
        <div class="endpoint-section" id="ep-user" data-method="GET">
            <div class="endpoint-title-row">
                <div class="endpoint-identity">
                    <span class="method-tag get">GET</span>
                    <span class="endpoint-path-text">/api/v1/user</span>
                    <span class="auth-tag token">Bearer Token</span>
                </div>
                <div style="display: flex; gap: 6px;">
                    <button class="btn-inline" type="button" onclick="salin('{{ url('/api/v1/user') }}', 'URL disalin')">Salin URL</button>
                    <button class="btn-inline accent" type="button" onclick="siapkanUji('GET', '/api/v1/user', '', '', 'bearer')">Uji Endpoint</button>
                </div>
            </div>

            <p class="endpoint-desc">
                Mengambil informasi akun pengguna yang sedang login berdasarkan token Sanctum yang disertakan.
            </p>

            <div class="code-container">
                <div class="code-header-bar">
                    <div class="code-tab-nav">
                        <button class="code-tab-btn active" type="button" onclick="tab(this, 'user-res')">Response 200</button>
                    </div>
                    <button class="btn-copy-action" type="button" onclick="salinTab(this)">Salin</button>
                </div>
                <div class="tab-content active" id="user-res">
                    <pre class="code-view">{
  "id": 4,
  "name": "Budi Santoso",
  "email": "nama@koperasi.test",
  "created_at": "2026-01-15T08:00:00.000000Z"
}</pre>
                </div>
            </div>
        </div>

        <!-- POST /sso/token -->
        <div class="endpoint-section" id="ep-sso-token" data-method="POST">
            <div class="endpoint-title-row">
                <div class="endpoint-identity">
                    <span class="method-tag post">POST</span>
                    <span class="endpoint-path-text">/api/v1/sso/token</span>
                    <span class="auth-tag">Sesi Web SSO + CSRF</span>
                </div>
                <div style="display: flex; gap: 6px;">
                    <button class="btn-inline" type="button" onclick="salin('{{ url('/api/v1/sso/token') }}', 'URL disalin')">Salin URL</button>
                    <button class="btn-inline accent" type="button" onclick="siapkanUji('POST', '/api/v1/sso/token', '', '', 'session')">Uji Endpoint</button>
                </div>
            </div>

            <p class="endpoint-desc">
                Menukar sesi web login SSO di peramban / WebView menjadi token Sanctum (<code style="font-family:var(--font-mono);">sso_token</code>). Dipanggil dengan cookie sesi dan header CSRF.
            </p>

            <div class="code-container">
                <div class="code-header-bar">
                    <div class="code-tab-nav">
                        <button class="code-tab-btn active" type="button" onclick="tab(this, 'sso-res')">Response 200</button>
                    </div>
                    <button class="btn-copy-action" type="button" onclick="salinTab(this)">Salin</button>
                </div>
                <div class="tab-content active" id="sso-res">
                    <pre class="code-view">{
  "user": {
    "id": 4,
    "name": "Budi Santoso"
  },
  "token": "2|def456ghi789..."
}</pre>
                </div>
            </div>
        </div>

        <!-- ==================== ENDPOINT DATA ANGGOTA ==================== -->
        <h2 class="doc-h2" id="anggota">Endpoint Data Anggota</h2>

        <!-- GET /anggota/pinjaman-aktif -->
        <div class="endpoint-section" id="ep-pinjaman-aktif" data-method="GET">
            <div class="endpoint-title-row">
                <div class="endpoint-identity">
                    <span class="method-tag get">GET</span>
                    <span class="endpoint-path-text">/api/v1/anggota/pinjaman-aktif</span>
                    <span class="auth-tag token">Bearer Token</span>
                </div>
                <div style="display: flex; gap: 6px;">
                    <button class="btn-inline" type="button" onclick="salin('{{ url('/api/v1/anggota/pinjaman-aktif') }}', 'URL disalin')">Salin URL</button>
                    <button class="btn-inline accent" type="button" onclick="siapkanUji('GET', '/api/v1/anggota/pinjaman-aktif', '', '', 'bearer')">Uji Endpoint</button>
                </div>
            </div>

            <p class="endpoint-desc">
                Mengambil daftar seluruh pinjaman berstatus aktif milik anggota yang sedang login, lengkap dengan agregat sisa angsuran dan jadwal cicilan terdekat berikutnya.
            </p>

            <div class="code-container">
                <div class="code-header-bar">
                    <div class="code-tab-nav">
                        <button class="code-tab-btn active" type="button" onclick="tab(this, 'aktif-res')">Response 200</button>
                    </div>
                    <button class="btn-copy-action" type="button" onclick="salinTab(this)">Salin</button>
                </div>
                <div class="tab-content active" id="aktif-res">
                    <pre class="code-view">{
  "data": [
    {
      "id": 12,
      "nominal": 5000000,
      "tenor_bulan": 10,
      "status": "aktif",
      "tanggal_pengajuan": "2026-05-10",
      "tanggal_pencairan": "2026-05-12",
      "sisa_angsuran": 6,
      "total_angsuran": 10,
      "sisa_total_bayar": 3300000,
      "angsuran_berikutnya": {
        "cicilan_ke": 5,
        "total_bayar": 550000,
        "tanggal_jatuh_tempo": "2026-10-12"
      }
    }
  ],
  "count": 1
}</pre>
                </div>
            </div>
        </div>

        <!-- GET /anggota/riwayat-pinjaman -->
        <div class="endpoint-section" id="ep-riwayat" data-method="GET">
            <div class="endpoint-title-row">
                <div class="endpoint-identity">
                    <span class="method-tag get">GET</span>
                    <span class="endpoint-path-text">/api/v1/anggota/riwayat-pinjaman</span>
                    <span class="auth-tag token">Bearer Token</span>
                </div>
                <div style="display: flex; gap: 6px;">
                    <button class="btn-inline" type="button" onclick="salin('{{ url('/api/v1/anggota/riwayat-pinjaman') }}', 'URL disalin')">Salin URL</button>
                    <button class="btn-inline accent" type="button" onclick="siapkanUji('GET', '/api/v1/anggota/riwayat-pinjaman', 'status=lunas&per_page=10', '', 'bearer')">Uji Endpoint</button>
                </div>
            </div>

            <p class="endpoint-desc">
                Daftar riwayat pinjaman anggota (terbaru lebih dahulu), mencakup rincian jadwal angsuran, catatan pengurus, dan rekam jejak perubahan tenor (percepatan/perpanjangan).
            </p>

            <h4 style="font-size: 12px; font-weight: 700; color: var(--text-heading); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Query Parameters</h4>
            <table class="clean-table">
                <thead><tr><th>Parameter</th><th>Tipe</th><th>Status</th><th>Keterangan</th></tr></thead>
                <tbody>
                    <tr>
                        <td><code class="field-name">status</code></td>
                        <td><span class="field-type">string</span></td>
                        <td><span class="opt-pill">Opsional</span></td>
                        <td>Filter status: <code style="font-family:var(--font-mono);">diajukan</code>, <code style="font-family:var(--font-mono);">approved_admin</code>, <code style="font-family:var(--font-mono);">approved_bendahara</code>, <code style="font-family:var(--font-mono);">approved_ketua</code>, <code style="font-family:var(--font-mono);">aktif</code>, <code style="font-family:var(--font-mono);">ditolak</code>, <code style="font-family:var(--font-mono);">lunas</code>.</td>
                    </tr>
                    <tr>
                        <td><code class="field-name">per_page</code></td>
                        <td><span class="field-type">integer</span></td>
                        <td><span class="opt-pill">Opsional</span></td>
                        <td>Jumlah data per halaman (1 s.d. 100, default: 15).</td>
                    </tr>
                    <tr>
                        <td><code class="field-name">page</code></td>
                        <td><span class="field-type">integer</span></td>
                        <td><span class="opt-pill">Opsional</span></td>
                        <td>Nomor halaman paginasi.</td>
                    </tr>
                </tbody>
            </table>

            <div class="code-container">
                <div class="code-header-bar">
                    <div class="code-tab-nav">
                        <button class="code-tab-btn active" type="button" onclick="tab(this, 'riwayat-res')">Response 200</button>
                    </div>
                    <button class="btn-copy-action" type="button" onclick="salinTab(this)">Salin</button>
                </div>
                <div class="tab-content active" id="riwayat-res">
                    <pre class="code-view">{
  "data": [
    {
      "id": 12,
      "nominal": 5000000,
      "tenor_bulan": 10,
      "status": "lunas",
      "tanggal_pengajuan": "2026-05-10",
      "catatan_bendahara": "Lunas via payroll",
      "angsuran": [
        { "cicilan_ke": 1, "total_bayar": 550000, "status": "lunas" }
      ],
      "riwayat_perubahan": []
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 }
}</pre>
                </div>
            </div>
        </div>

        <!-- GET /anggota/limit -->
        <div class="endpoint-section" id="ep-limit" data-method="GET">
            <div class="endpoint-title-row">
                <div class="endpoint-identity">
                    <span class="method-tag get">GET</span>
                    <span class="endpoint-path-text">/api/v1/anggota/limit</span>
                    <span class="auth-tag token">Bearer Token</span>
                </div>
                <div style="display: flex; gap: 6px;">
                    <button class="btn-inline" type="button" onclick="salin('{{ url('/api/v1/anggota/limit') }}', 'URL disalin')">Salin URL</button>
                    <button class="btn-inline accent" type="button" onclick="siapkanUji('GET', '/api/v1/anggota/limit', '', '', 'bearer')">Uji Endpoint</button>
                </div>
            </div>

            <p class="endpoint-desc">
                Mengambil kalkulasi eligibilitas limit pinjaman anggota melalui <code style="font-family:var(--font-mono);">EligibilitasPinjamanService</code>, mencakup limit maksimal, sisa limit tersedia, dan status kelayakan pengajuan baru.
            </p>

            <div class="code-container">
                <div class="code-header-bar">
                    <div class="code-tab-nav">
                        <button class="code-tab-btn active" type="button" onclick="tab(this, 'limit-res')">Response 200</button>
                    </div>
                    <button class="btn-copy-action" type="button" onclick="salinTab(this)">Salin</button>
                </div>
                <div class="tab-content active" id="limit-res">
                    <pre class="code-view">{
  "limit_maksimal": 10000000,
  "limit_tersedia": 6700000,
  "boleh": true,
  "alasan": null,
  "sisa_angsuran": 6,
  "cicilan_pokok": 500000
}</pre>
                </div>
            </div>
        </div>

        <!-- GET /anggota/simpanan -->
        <div class="endpoint-section" id="ep-simpanan" data-method="GET">
            <div class="endpoint-title-row">
                <div class="endpoint-identity">
                    <span class="method-tag get">GET</span>
                    <span class="endpoint-path-text">/api/v1/anggota/simpanan</span>
                    <span class="auth-tag token">Bearer Token</span>
                </div>
                <div style="display: flex; gap: 6px;">
                    <button class="btn-inline" type="button" onclick="salin('{{ url('/api/v1/anggota/simpanan') }}', 'URL disalin')">Salin URL</button>
                    <button class="btn-inline accent" type="button" onclick="siapkanUji('GET', '/api/v1/anggota/simpanan', 'bulan_periode=2026-09', '', 'bearer')">Uji Endpoint</button>
                </div>
            </div>

            <p class="endpoint-desc">
                Ringkasan akumulasi saldo simpanan pokok dan wajib anggota beserta 20 mutasi setoran simpanan terbaru.
            </p>

            <h4 style="font-size: 12px; font-weight: 700; color: var(--text-heading); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Query Parameters</h4>
            <table class="clean-table">
                <thead><tr><th>Parameter</th><th>Tipe</th><th>Status</th><th>Keterangan</th></tr></thead>
                <tbody>
                    <tr>
                        <td><code class="field-name">bulan_periode</code></td>
                        <td><span class="field-type">string (YYYY-MM)</span></td>
                        <td><span class="opt-pill">Opsional</span></td>
                        <td>Menyaring mutasi pada bulan tertentu (contoh: <code style="font-family:var(--font-mono);">2026-09</code>).</td>
                    </tr>
                </tbody>
            </table>

            <div class="code-container">
                <div class="code-header-bar">
                    <div class="code-tab-nav">
                        <button class="code-tab-btn active" type="button" onclick="tab(this, 'simpanan-res')">Response 200</button>
                    </div>
                    <button class="btn-copy-action" type="button" onclick="salinTab(this)">Salin</button>
                </div>
                <div class="tab-content active" id="simpanan-res">
                    <pre class="code-view">{
  "ringkasan": {
    "total": 1550000,
    "pokok": 50000,
    "wajib": 1500000
  },
  "data": [
    {
      "jenis": "wajib",
      "jumlah": 50000,
      "bulan_periode": "2026-09",
      "tanggal_input": "2026-09-28"
    }
  ]
}</pre>
                </div>
            </div>
        </div>

        <!-- ==================== KONSOL COBA ==================== -->
        <section id="konsol" style="margin-top: 56px;">
            <h2 class="doc-h2">Konsol coba</h2>
            <p style="color: var(--text-muted); font-size: 13.5px; margin-bottom: 16px;">
                Panel pengujian langsung dari peramban. Token Bearer disimpan aman di memori sesi peramban (<code style="font-family:var(--font-mono);">sessionStorage</code>) sehingga tetap bertahan saat merefresh halaman.
            </p>

            <div class="console-panel">
                <div class="form-row">
                    <label class="form-label" for="c-endpoint">Endpoint Target</label>
                    <select id="c-endpoint" class="form-control">
                        <option value="POST|/api/v1/login|none">POST /api/v1/login — Login email & kata sandi</option>
                        <option value="POST|/api/v1/logout|bearer">POST /api/v1/logout — Cabut token aktif</option>
                        <option value="GET|/api/v1/user|bearer">GET /api/v1/user — Data pengguna aktif</option>
                        <option value="POST|/api/v1/sso/token|session">POST /api/v1/sso/token — Tukar sesi web SSO</option>
                        <option value="GET|/api/v1/anggota/pinjaman-aktif|bearer" selected>GET /api/v1/anggota/pinjaman-aktif — Pinjaman aktif</option>
                        <option value="GET|/api/v1/anggota/riwayat-pinjaman|bearer">GET /api/v1/anggota/riwayat-pinjaman — Riwayat pinjaman</option>
                        <option value="GET|/api/v1/anggota/limit|bearer">GET /api/v1/anggota/limit — Cek eligibilitas & limit</option>
                        <option value="GET|/api/v1/anggota/simpanan|bearer">GET /api/v1/anggota/simpanan — Ringkasan simpanan</option>
                    </select>
                </div>

                <div class="form-row">
                    <label class="form-label" for="c-token">
                        <span>Token Bearer (Sanctum)</span>
                        <span style="font-weight:normal;color:var(--text-light);font-size:11px;">Kosongkan bila endpoint publik</span>
                    </label>
                    <div style="display: flex; gap: 8px;">
                        <input id="c-token" class="form-control" type="password" placeholder="1|abc123def456..." autocomplete="off">
                        <button class="btn-inline" type="button" id="btn-toggle-token" onclick="toggleToken()">Lihat</button>
                        <button class="btn-inline" type="button" onclick="hapusToken()">Hapus</button>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-row">
                        <label class="form-label" for="c-query">Query Parameters</label>
                        <input id="c-query" class="form-control" type="text" placeholder="mis. status=lunas&per_page=5" autocomplete="off">
                    </div>
                    <div class="form-row">
                        <label class="form-label" for="c-body">Request Body (JSON)</label>
                        <textarea id="c-body" class="form-control" placeholder='{"email":"...","password":"..."}'></textarea>
                    </div>
                </div>

                <button class="btn-submit-test" id="c-send" type="button">Kirim Permintaan</button>

                <div class="console-meta-line">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span>Status:</span>
                        <span class="status-indicator waiting" id="c-status-badge">Siap</span>
                    </div>
                    <div style="display: flex; gap: 12px; color: var(--text-light); font-family: var(--font-mono); font-size: 11px;">
                        <span id="c-latency">- ms</span>
                        <span id="c-size">- KB</span>
                        <button class="btn-inline" type="button" onclick="salinHasil()" style="padding: 1px 6px; font-size: 11px;">Salin JSON</button>
                    </div>
                </div>

                <pre class="code-view" id="c-response" style="border-radius: var(--radius-sm); margin-top: 8px; max-height: 360px; font-size: 12px;">// Hasil respons server akan ditampilkan di sini...</pre>
            </div>
        </section>

        <footer style="margin-top: 80px; padding-top: 24px; border-top: 1px solid var(--border-light); font-size: 12px; color: var(--text-light); text-align: center;">
            Koperasi App RESTful API Documentation • Versi 1.0
        </footer>
    </main>
</div>

<!-- Toast Feedback -->
<div class="toast-bubble" id="toast-bubble">Tersalin ke clipboard</div>

<script>
(function() {
    'use strict';

    // 1. Token session storage persistence
    var tokenInput = document.getElementById('c-token');
    var savedToken = sessionStorage.getItem('koperasi_api_docs_token');
    if (savedToken && tokenInput) {
        tokenInput.value = savedToken;
    }
    if (tokenInput) {
        tokenInput.addEventListener('input', function() {
            sessionStorage.setItem('koperasi_api_docs_token', tokenInput.value.trim());
        });
    }

    // 2. Tab switcher
    window.tab = function(btn, panelId) {
        var container = btn.closest('.code-container');
        if (!container) return;
        container.querySelectorAll('.code-tab-btn').forEach(function(b) { b.classList.remove('active'); });
        container.querySelectorAll('.tab-content').forEach(function(p) { p.classList.remove('active'); });
        btn.classList.add('active');
        var panel = container.querySelector('#' + panelId);
        if (panel) panel.classList.add('active');
    };

    // 3. Salin tab aktif
    window.salinTab = function(btn) {
        var container = btn.closest('.code-container');
        if (!container) return;
        var active = container.querySelector('.tab-content.active');
        if (!active) return;
        salin(active.textContent.trim(), 'Kode disalin');
    };

    // 4. Salin umum + toast
    window.salin = function(str, msg) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(str).then(function() { toast(msg || 'Tersalin'); });
        } else {
            var ta = document.createElement('textarea');
            ta.value = str;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
            toast(msg || 'Tersalin');
        }
    };

    function toast(msg) {
        var bubble = document.getElementById('toast-bubble');
        if (!bubble) return;
        bubble.textContent = msg;
        bubble.classList.add('show');
        setTimeout(function() { bubble.classList.remove('show'); }, 2000);
    }

    // 5. Toggle & Hapus Token
    window.toggleToken = function() {
        if (!tokenInput) return;
        var btn = document.getElementById('btn-toggle-token');
        if (tokenInput.type === 'password') {
            tokenInput.type = 'text';
            btn.textContent = 'Tutup';
        } else {
            tokenInput.type = 'password';
            btn.textContent = 'Lihat';
        }
    };

    window.hapusToken = function() {
        if (!tokenInput) return;
        tokenInput.value = '';
        sessionStorage.removeItem('koperasi_api_docs_token');
        toast('Token sesi dihapus');
    };

    // 6. Siapkan Uji Konsol
    window.siapkanUji = function(method, path, query, body, authType) {
        var epSelect = document.getElementById('c-endpoint');
        var queryInput = document.getElementById('c-query');
        var bodyInput = document.getElementById('c-body');
        var konsol = document.getElementById('konsol');

        for (var i = 0; i < epSelect.options.length; i++) {
            if (epSelect.options[i].value.indexOf(path) !== -1) {
                epSelect.selectedIndex = i;
                break;
            }
        }
        queryInput.value = query || '';
        bodyInput.value = body || '';

        if (konsol) {
            konsol.scrollIntoView({ behavior: 'smooth' });
        }
    };

    window.salinHasil = function() {
        var resEl = document.getElementById('c-response');
        if (resEl) salin(resEl.textContent, 'JSON disalin');
    };

    // 7. Eksekusi Test Konsol
    var sendBtn = document.getElementById('c-send');
    var epSelect = document.getElementById('c-endpoint');
    var queryInput = document.getElementById('c-query');
    var bodyInput = document.getElementById('c-body');
    var statusBadge = document.getElementById('c-status-badge');
    var latencyEl = document.getElementById('c-latency');
    var sizeEl = document.getElementById('c-size');
    var responseEl = document.getElementById('c-response');

    if (sendBtn) {
        sendBtn.addEventListener('click', function() {
            var parts = epSelect.value.split('|');
            var method = parts[0];
            var path = parts[1];
            var q = queryInput.value.trim().replace(/^\?/, '');
            var url = path + (q ? '?' + q : '');

            var headers = { 'Accept': 'application/json' };
            if (method !== 'GET') headers['Content-Type'] = 'application/json';

            var tok = tokenInput.value.trim();
            if (tok) headers['Authorization'] = 'Bearer ' + tok;

            var options = { method: method, headers: headers, credentials: 'same-origin' };
            if (method !== 'GET' && bodyInput.value.trim()) options.body = bodyInput.value.trim();

            sendBtn.disabled = true;
            statusBadge.textContent = 'Mengirim...';
            statusBadge.className = 'status-indicator waiting';
            responseEl.textContent = '// Menghubungi server...';

            var t0 = performance.now();

            fetch(url, options).then(function(res) {
                var elapsed = Math.round(performance.now() - t0);
                latencyEl.textContent = elapsed + ' ms';

                return res.text().then(function(text) {
                    var len = text ? (text.length / 1024).toFixed(1) + ' KB' : '0 KB';
                    sizeEl.textContent = len;

                    statusBadge.textContent = res.status + ' ' + (res.statusText || '');
                    statusBadge.className = 'status-indicator ' + (res.ok ? 'success' : 'error');

                    try {
                        var json = JSON.parse(text);
                        responseEl.textContent = JSON.stringify(json, null, 2);
                        if (json.token && !tok) {
                            tokenInput.value = json.token;
                            sessionStorage.setItem('koperasi_api_docs_token', json.token);
                            toast('Token login tersimpan di sesi');
                        }
                    } catch (e) {
                        responseEl.textContent = text || '// (Respons kosong)';
                    }
                });
            }).catch(function(err) {
                statusBadge.textContent = 'Gagal';
                statusBadge.className = 'status-indicator error';
                responseEl.textContent = String(err);
            }).finally(function() {
                sendBtn.disabled = false;
            });
        });
    }

    // 8. Search & Sidebar Filter
    var searchInput = document.getElementById('side-search');
    var clearBtn = document.getElementById('search-clear');
    var sideLinks = document.querySelectorAll('.nav-item[data-method]');
    var sideEmpty = document.getElementById('side-empty');

    function runSearch() {
        var q = searchInput.value.trim().toLowerCase();
        clearBtn.classList.toggle('show', q.length > 0);
        var visible = 0;

        sideLinks.forEach(function(item) {
            var text = item.textContent.toLowerCase();
            var matches = !q || text.indexOf(q) !== -1;
            item.style.display = matches ? 'flex' : 'none';
            if (matches) visible++;
        });

        sideEmpty.hidden = (visible > 0);
    }

    if (searchInput) searchInput.addEventListener('input', runSearch);
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            runSearch();
            searchInput.focus();
        });
    }

    window.addEventListener('keydown', function(e) {
        if (e.key === '/' && document.activeElement !== searchInput && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
        if (e.key === 'Escape' && document.activeElement === searchInput) {
            searchInput.blur();
        }
    });

    // 9. Scrollspy
    var spySections = document.querySelectorAll('[id]');
    var navLinks = document.querySelectorAll('.nav-item[href^="#"]');
    var navMap = {};
    navLinks.forEach(function(link) {
        navMap[link.getAttribute('href').slice(1)] = link;
    });

    if ('IntersectionObserver' in window) {
        var obs = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var id = entry.target.id;
                    if (navMap[id]) {
                        navLinks.forEach(function(l) { l.removeAttribute('aria-current'); });
                        navMap[id].setAttribute('aria-current', 'true');
                    }
                }
            });
        }, { rootMargin: '-20% 0px -65% 0px' });

        spySections.forEach(function(sec) {
            if (navMap[sec.id]) obs.observe(sec);
        });
    }
})();
</script>
</body>
</html>
