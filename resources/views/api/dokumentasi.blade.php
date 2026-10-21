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
            --bg-page: #F8FAFC;
            --bg-card: #FFFFFF;
            --border: #E2E8F0;
            --border-subtle: #F1F5F9;
            --text-heading: #0F172A;
            --text-body: #334155;
            --text-muted: #64748B;
            --text-light: #94A3B8;
            
            --navy: #0F1E36;
            --navy-light: #1A2E4D;
            --navy-surface: #F8FAFC;
            
            --green: #1FA24C;
            --green-dark: #178A3E;
            --green-subtle: #E8F8EE;
            --green-border: #B7E8C8;
            
            --blue: #2563EB;
            --blue-subtle: #EFF6FF;
            --blue-border: #BFDBFE;
            
            --amber: #D97706;
            --amber-subtle: #FFFBEB;
            --amber-border: #FDE68A;
            
            --red: #DC2626;
            --red-subtle: #FEF2F2;
            --red-border: #FECACA;
            
            --purple: #7C3AED;
            --purple-subtle: #F5F3FF;
            --purple-border: #DDD6FE;

            --code-bg: #0F172A;
            --code-text: #E2E8F0;
            --code-header: #1E293B;

            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-full: 9999px;

            --shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.08), 0 1px 2px -1px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { -webkit-text-size-adjust: 100%; scroll-behavior: smooth; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg-page);
            color: var(--text-body);
            line-height: 1.6;
            font-size: 14.5px;
            min-height: 100vh;
        }

        /* Topbar Header */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.95);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            transition: box-shadow 0.2s ease;
        }
        .topbar.scrolled {
            box-shadow: var(--shadow-sm);
        }
        .topbar-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .brand-cluster {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }
        .brand-logo {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: #fff;
            padding: 2px;
            object-fit: contain;
        }
        .brand-text h1 {
            font-size: 16px;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .brand-text span {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 500;
        }
        .version-tag {
            font-size: 11px;
            font-weight: 700;
            background: var(--green-subtle);
            color: var(--green-dark);
            border: 1px solid var(--green-border);
            padding: 2px 8px;
            border-radius: var(--radius-full);
            letter-spacing: 0.02em;
        }
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-btn {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-body);
            text-decoration: none;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: #fff;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }
        .nav-btn:hover {
            background: #F1F5F9;
            color: var(--text-heading);
            border-color: #CBD5E1;
        }
        .nav-btn.primary {
            background: var(--green-dark);
            color: #fff;
            border-color: var(--green-dark);
        }
        .nav-btn.primary:hover {
            background: var(--green);
            border-color: var(--green);
        }

        /* Layout Grid */
        .layout {
            max-width: 1240px;
            margin: 0 auto;
            padding: 24px 20px 80px;
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 28px;
            align-items: start;
        }
        [id] { scroll-margin-top: 80px; }

        /* Sidebar Navigation */
        .sidebar {
            position: sticky;
            top: 76px;
            max-height: calc(100vh - 96px);
            overflow-y: auto;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 16px;
            box-shadow: var(--shadow-xs);
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }

        .search-wrapper {
            position: relative;
            margin-bottom: 14px;
        }
        .search-input {
            width: 100%;
            font-size: 13px;
            padding: 8px 30px 8px 10px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: inherit;
            background: #F8FAFC;
            color: var(--text-heading);
            transition: all 0.15s ease;
        }
        .search-input:focus {
            outline: none;
            background: #fff;
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(31, 162, 76, 0.12);
        }
        .search-kbd {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 10px;
            font-weight: 700;
            color: var(--text-light);
            background: #E2E8F0;
            border-radius: 4px;
            padding: 1px 5px;
            pointer-events: none;
        }
        .search-clear {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 14px;
            color: var(--text-muted);
            cursor: pointer;
            display: none;
        }
        .search-clear.show { display: block; }

        .filter-pills {
            display: flex;
            gap: 4px;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-subtle);
        }
        .filter-pill {
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: var(--radius-full);
            background: #F1F5F9;
            color: var(--text-muted);
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .filter-pill:hover { background: #E2E8F0; color: var(--text-heading); }
        .filter-pill.active { background: var(--navy); color: #fff; }

        .nav-category {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-light);
            margin: 14px 4px 6px;
        }
        .side-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border-radius: var(--radius-sm);
            color: var(--text-body);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.15s ease;
            position: relative;
        }
        .side-item:hover {
            background: #F8FAFC;
            color: var(--text-heading);
        }
        .side-item[aria-current="true"] {
            background: var(--green-subtle);
            color: var(--green-dark);
            font-weight: 700;
        }
        .side-item[aria-current="true"]::before {
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
            font-size: 12.5px;
            color: var(--text-muted);
            padding: 12px 6px;
            text-align: center;
        }

        /* Method Badges */
        .method-badge {
            font-family: ui-monospace, 'Cascadia Code', Consolas, monospace;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.04em;
            padding: 2px 7px;
            border-radius: 5px;
            text-align: center;
            line-height: 1.3;
            display: inline-block;
        }
        .method-badge.mini {
            font-size: 9.5px;
            padding: 1px 5px;
            min-width: 42px;
            flex-shrink: 0;
        }
        .method-badge.get {
            background: var(--green-subtle);
            color: var(--green-dark);
            border: 1px solid var(--green-border);
        }
        .method-badge.post {
            background: var(--blue-subtle);
            color: var(--blue);
            border: 1px solid var(--blue-border);
        }

        /* Main Content Cards */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-xs);
            transition: border-color 0.15s ease;
        }
        .card:hover {
            border-color: #CBD5E1;
        }
        .card-header-intro {
            border-left: 4px solid var(--green);
        }

        /* Typography */
        h2.section-heading {
            font-size: 18px;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: -0.01em;
            margin: 36px 0 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        h2.section-heading::before {
            content: '';
            width: 6px;
            height: 18px;
            border-radius: 3px;
            background: var(--navy);
            display: inline-block;
        }
        h3.card-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 8px;
        }
        p {
            color: var(--text-body);
            margin-bottom: 12px;
        }
        p:last-child { margin-bottom: 0; }

        /* Endpoint Header */
        .ep-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding-bottom: 14px;
            margin-bottom: 16px;
            border-bottom: 1px solid var(--border-subtle);
        }
        .ep-route {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .ep-path {
            font-family: ui-monospace, 'Cascadia Code', Consolas, monospace;
            font-size: 14.5px;
            font-weight: 700;
            color: var(--navy);
            word-break: break-all;
        }
        .auth-chip {
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: var(--radius-full);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .auth-chip.bearer {
            background: var(--amber-subtle);
            color: var(--amber);
            border: 1px solid var(--amber-border);
        }
        .auth-chip.public {
            background: #F1F5F9;
            color: var(--text-muted);
            border: 1px solid var(--border);
        }
        .auth-chip.session {
            background: var(--purple-subtle);
            color: var(--purple);
            border: 1px solid var(--purple-border);
        }

        .ep-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-action-sm {
            font-size: 12px;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text-body);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s ease;
        }
        .btn-action-sm:hover {
            border-color: var(--green);
            color: var(--green-dark);
            background: var(--green-subtle);
        }
        .btn-action-sm.try {
            background: var(--navy-surface);
            border-color: #CBD5E1;
            color: var(--navy);
            font-weight: 700;
        }
        .btn-action-sm.try:hover {
            background: var(--navy);
            color: #fff;
            border-color: var(--navy);
        }

        /* Description Callout */
        .description-box {
            background: #F8FAFC;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 12px 14px;
            font-size: 13.5px;
            color: var(--text-body);
            margin-bottom: 16px;
            line-height: 1.55;
        }

        /* Tables */
        table.spec-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
            margin: 14px 0 18px;
        }
        table.spec-table th {
            text-align: left;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            padding: 8px 12px;
            background: #F8FAFC;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            font-weight: 700;
        }
        table.spec-table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border-subtle);
            vertical-align: top;
            color: var(--text-body);
        }
        table.spec-table tr:hover td {
            background: #FAFAFA;
        }
        code.param-name {
            font-family: ui-monospace, 'Cascadia Code', Consolas, monospace;
            font-size: 13px;
            font-weight: 700;
            color: var(--navy);
            background: #F1F5F9;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .type-badge {
            font-size: 11px;
            color: var(--text-muted);
            font-style: italic;
        }
        .badge-req {
            font-size: 10.5px;
            font-weight: 700;
            color: var(--red);
            background: var(--red-subtle);
            border: 1px solid var(--red-border);
            padding: 1px 5px;
            border-radius: 4px;
            white-space: nowrap;
        }
        .badge-opt {
            font-size: 10.5px;
            font-weight: 600;
            color: var(--text-muted);
            background: #F1F5F9;
            border: 1px solid var(--border);
            padding: 1px 5px;
            border-radius: 4px;
            white-space: nowrap;
        }

        /* Code Block Tabs & Pre */
        .code-tabs-wrapper {
            background: var(--code-bg);
            border-radius: var(--radius-md);
            overflow: hidden;
            margin: 14px 0;
            border: 1px solid var(--border);
        }
        .code-tab-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--code-header);
            padding: 0 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            min-height: 38px;
        }
        .tab-buttons {
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
            transition: all 0.15s ease;
            border-bottom: 2px solid transparent;
        }
        .code-tab-btn:hover {
            color: #F8FAFC;
        }
        .code-tab-btn.active {
            color: #38BDF8;
            border-bottom-color: #38BDF8;
            font-weight: 700;
        }
        .btn-copy-code {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #CBD5E1;
            font-size: 11.5px;
            font-weight: 600;
            padding: 4px 9px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
        }
        .btn-copy-code:hover {
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
        }

        pre.code-block {
            padding: 14px 16px;
            color: var(--code-text);
            font-family: ui-monospace, 'Cascadia Code', Consolas, monospace;
            font-size: 12.5px;
            line-height: 1.55;
            overflow-x: auto;
            margin: 0;
            background: var(--code-bg);
        }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* Status Pills List */
        .status-badge-list {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
            list-style: none;
        }
        .status-pill {
            font-size: 12px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .status-pill.s200 { background: var(--green-subtle); color: var(--green-dark); border: 1px solid var(--green-border); }
        .status-pill.s401 { background: var(--amber-subtle); color: var(--amber); border: 1px solid var(--amber-border); }
        .status-pill.s403 { background: var(--red-subtle); color: var(--red); border: 1px solid var(--red-border); }
        .status-pill.s404 { background: #F1F5F9; color: var(--text-muted); border: 1px solid var(--border); }
        .status-pill.s422 { background: var(--red-subtle); color: var(--red); border: 1px solid var(--red-border); }

        /* Interactive Console Tester */
        .console-container {
            background: #FFFFFF;
            border: 2px solid var(--navy-light);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-md);
        }
        .console-badge {
            font-size: 11px;
            font-weight: 800;
            background: var(--navy);
            color: #fff;
            padding: 3px 9px;
            border-radius: var(--radius-full);
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .console-form-group {
            margin-bottom: 14px;
        }
        .console-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 6px;
        }
        .console-select, .console-input, .console-textarea {
            width: 100%;
            font-size: 13.5px;
            padding: 9px 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: inherit;
            background: #fff;
            color: var(--text-heading);
            transition: all 0.15s ease;
        }
        .console-select:focus, .console-input:focus, .console-textarea:focus {
            outline: none;
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(31, 162, 76, 0.12);
        }
        .console-textarea {
            font-family: ui-monospace, 'Cascadia Code', Consolas, monospace;
            font-size: 12.5px;
            resize: vertical;
            min-height: 70px;
        }
        .console-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        @media (max-width: 640px) {
            .console-row-2 { grid-template-columns: 1fr; }
        }
        .console-presets {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 6px;
        }
        .preset-chip {
            font-size: 11px;
            font-weight: 600;
            color: var(--navy);
            background: #F1F5F9;
            border: 1px solid var(--border);
            padding: 2px 7px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .preset-chip:hover {
            background: var(--green-subtle);
            border-color: var(--green);
            color: var(--green-dark);
        }

        .btn-send-request {
            width: 100%;
            padding: 11px 18px;
            font-size: 14.5px;
            font-weight: 800;
            color: #fff;
            background: var(--green-dark);
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.15s ease;
            box-shadow: var(--shadow-sm);
        }
        .btn-send-request:hover {
            background: var(--green);
            box-shadow: var(--shadow-md);
        }
        .btn-send-request:disabled {
            opacity: 0.6;
            cursor: wait;
        }

        .console-result-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
            padding: 10px 14px;
            background: #F8FAFC;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 600;
            margin-top: 14px;
        }
        .meta-status-pill {
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 800;
        }
        .meta-status-pill.success { background: var(--green-subtle); color: var(--green-dark); }
        .meta-status-pill.error { background: var(--red-subtle); color: var(--red); }
        .meta-status-pill.pending { background: #F1F5F9; color: var(--text-muted); }

        /* Floating Back to Top */
        .back-top {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 40;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: var(--navy);
            color: #fff;
            font-size: 18px;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-md);
            transition: transform 0.15s ease;
        }
        .back-top.show { display: inline-flex; }
        .back-top:hover { transform: translateY(-2px); }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--navy);
            color: #fff;
            padding: 10px 18px;
            border-radius: var(--radius-full);
            font-size: 13px;
            font-weight: 700;
            box-shadow: var(--shadow-lg);
            z-index: 100;
            opacity: 0;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        /* Footer */
        footer.docs-footer {
            text-align: center;
            padding: 32px 0 16px;
            color: var(--text-muted);
            font-size: 13px;
            border-top: 1px solid var(--border);
            margin-top: 40px;
        }

        @media (max-width: 960px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar { position: static; max-height: none; }
        }
    </style>
</head>
<body>

<a class="skip-link" href="#konten" style="position:absolute;left:-999px;top:auto;width:1px;height:1px;overflow:hidden;">Lewati ke konten</a>

<!-- Sticky Navigation Header -->
<header class="topbar" id="topbar">
    <div class="topbar-container">
        <a href="{{ url('/api/documentation') }}" class="brand-cluster">
            <img src="/images/logo.png" alt="Logo Koperasi" class="brand-logo" onerror="this.style.display='none'">
            <div class="brand-text">
                <h1>Koperasi App API</h1>
                <span>Dokumentasi Resmi Integrasi & Mobile v1</span>
            </div>
            <span class="version-tag">v1.0 • Sanctum</span>
        </a>
        <div class="topbar-actions">
            <a href="#autentikasi" class="nav-btn">Autentikasi</a>
            <a href="#anggota" class="nav-btn">Data Anggota</a>
            <a href="#konsol" class="nav-btn primary">Konsol coba ⚡</a>
        </div>
    </div>
</header>

<div class="layout">
    <!-- Left Navigation Sidebar -->
    <aside class="sidebar" aria-label="Daftar endpoint">
        <label for="side-search" style="display:block;font-size:12px;font-weight:700;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.04em;">Cari endpoint</label>
        <div class="search-wrapper">
            <input type="search" id="side-search" class="search-input" placeholder="Cari endpoint (mis. limit, login)..." autocomplete="off">
            <span class="search-kbd" id="search-kbd">/</span>
            <button class="search-clear" id="search-clear" type="button" aria-label="Bersihkan pencarian">×</button>
        </div>

        <div class="filter-pills" id="method-filters">
            <button type="button" class="filter-pill active" data-filter="all">Semua (8)</button>
            <button type="button" class="filter-pill" data-filter="GET">GET (5)</button>
            <button type="button" class="filter-pill" data-filter="POST">POST (3)</button>
        </div>

        <nav aria-label="Menu navigasi">
            <div class="nav-category">Panduan Umum</div>
            <a class="side-item" href="#overview">
                <span>Ringkasan Arsitektur</span>
            </a>
            <a class="side-item" href="#autentikasi">
                <span>Autentikasi & Header</span>
            </a>
            <a class="side-item" href="#standar-error">
                <span>Format Error & Limit</span>
            </a>

            <div class="nav-category">Autentikasi (4)</div>
            <a class="side-item" href="#ep-login" data-method="POST">
                <span class="method-badge post mini">POST</span>
                <span>/login</span>
            </a>
            <a class="side-item" href="#ep-logout" data-method="POST">
                <span class="method-badge post mini">POST</span>
                <span>/logout</span>
            </a>
            <a class="side-item" href="#ep-user" data-method="GET">
                <span class="method-badge get mini">GET</span>
                <span>/user</span>
            </a>
            <a class="side-item" href="#ep-sso-token" data-method="POST">
                <span class="method-badge post mini">POST</span>
                <span>/sso/token</span>
            </a>

            <div class="nav-category">Data Anggota Mandiri (4)</div>
            <a class="side-item" href="#ep-pinjaman-aktif" data-method="GET">
                <span class="method-badge get mini">GET</span>
                <span>/pinjaman-aktif</span>
            </a>
            <a class="side-item" href="#ep-riwayat" data-method="GET">
                <span class="method-badge get mini">GET</span>
                <span>/riwayat-pinjaman</span>
            </a>
            <a class="side-item" href="#ep-limit" data-method="GET">
                <span class="method-badge get mini">GET</span>
                <span>/limit</span>
            </a>
            <a class="side-item" href="#ep-simpanan" data-method="GET">
                <span class="method-badge get mini">GET</span>
                <span>/simpanan</span>
            </a>

            <div class="nav-category">Alat Uji</div>
            <a class="side-item" href="#konsol">
                <span style="color:var(--green-dark);font-weight:800;">⚡</span>
                <span>Konsol coba</span>
            </a>
        </nav>
        <p class="side-empty" id="side-empty" hidden>Tidak ada endpoint yang cocok dengan pencarian.</p>
    </aside>

    <!-- Main Content Area -->
    <main id="konten">
        <!-- Hero Overview -->
        <div class="card card-header-intro" id="overview">
            <h1 style="font-size:24px;font-weight:800;color:var(--navy);letter-spacing:-0.02em;margin-bottom:8px;">
                Dokumentasi API v1
            </h1>
            <p style="font-size:15px;color:var(--text-body);margin-bottom:14px;">
                API resmi Koperasi App yang dirancang ramping, aman, dan berkecepatan tinggi untuk aplikasi mobile (PWA), portal web, serta integrasi internal. Seluruh endpoint mengembalikan respons terstandarisasi JSON.
            </p>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 14px;background:#F8FAFC;border:1px solid var(--border);border-radius:var(--radius-md);">
                <div style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Base URL:</div>
                <code style="font-family:ui-monospace,Consolas,monospace;font-size:13.5px;font-weight:700;color:var(--navy);background:#fff;padding:4px 8px;border-radius:6px;border:1px solid var(--border);" id="base-url-text">{{ url('/api/v1') }}</code>
                <button class="btn-action-sm" type="button" onclick="salinTeks('{{ url('/api/v1') }}', 'Base URL disalin!')">
                    📋 Salin Base URL
                </button>
                <div style="margin-left:auto;display:flex;gap:8px;">
                    <span class="auth-chip" style="background:#F1F5F9;border:1px solid var(--border);">Rate limit: 60 req/menit</span>
                    <span class="auth-chip" style="background:var(--green-subtle);color:var(--green-dark);border:1px solid var(--green-border);">Mode: {{ config('auth.mode', 'local') }}</span>
                </div>
            </div>
        </div>

        <!-- Arsitektur Keamanan & Self-Scoped -->
        <div class="card">
            <h3 class="card-title">Prinsip Arsitektur: Self-Scoped & Zero-IDOR Protection</h3>
            <p>
                Seluruh endpoint di bawah naungan <code class="inline">/api/v1/anggota/*</code> menerapkan prinsip <strong>Self-Scoped Context</strong>. Artinya, data anggota diambil secara otomatis dari relasi akun yang sedang diautentikasi oleh token (<code class="inline">auth()-&gt;user()-&gt;anggota</code>).
            </p>
            <div class="description-box" style="border-left:4px solid var(--blue);">
                🔒 <strong>Proteksi Anti-IDOR (Insecure Direct Object Reference):</strong> Endpoint tidak menerima parameter <code class="inline">anggota_id</code>. Pengguna aplikasi tidak akan pernah bisa memanipulasi parameter URL untuk mengintip maupun menyalahgunakan data pinjaman, simpanan, atau limit milik anggota lain.
            </div>
        </div>

        <!-- Autentikasi -->
        <h2 class="section-heading" id="autentikasi">Autentikasi & Header Permintaan</h2>
        <div class="card">
            <h3 class="card-title">Mekanisme Autentikasi (Laravel Sanctum)</h3>
            <p>
                Sistem mendukung dua mode operasional autentikasi tergantung konfigurasi lingkungan (<code class="inline">AUTH_MODE</code>):
            </p>
            <ul style="padding-left:20px;margin-bottom:14px;color:var(--text-body);font-size:14px;">
                <li style="margin-bottom:8px;">
                    <strong>Mode Lokal (<code class="inline">AUTH_MODE=local</code>):</strong> Digunakan untuk pengembangan lokal atau deployment tanpa SSO. Login menggunakan email dan password via endpoint <code class="inline">POST /api/v1/login</code> untuk mendapatkan token Bearer Sanctum.
                </li>
                <li>
                    <strong>Mode SSO Gate (<code class="inline">AUTH_MODE=sso</code>):</strong> Digunakan pada ekosistem produksi terpadu. Login password langsung dinonaktifkan demi kepatuhan keamanan terpusat. Pengguna melakukan autentikasi SSO via peramban / WebView, lalu sesi peramban ditukar menjadi token API via <code class="inline">POST /api/v1/sso/token</code>.
                </li>
            </ul>

            <h4 style="font-size:13px;font-weight:700;color:var(--navy);margin:16px 0 6px;text-transform:uppercase;letter-spacing:0.04em;">Header Standar yang Diperlukan:</h4>
            <table class="spec-table">
                <thead>
                    <tr><th>Header</th><th>Wajib</th><th>Nilai Contoh</th><th>Keterangan</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code class="param-name">Accept</code></td>
                        <td><span class="badge-req">Wajib</span></td>
                        <td><code class="inline">application/json</code></td>
                        <td>Memastikan seluruh respons dikembalikan dalam bentuk objek JSON.</td>
                    </tr>
                    <tr>
                        <td><code class="param-name">Content-Type</code></td>
                        <td><span class="badge-req">Wajib (POST)</span></td>
                        <td><code class="inline">application/json</code></td>
                        <td>Diperlukan saat mengirimkan payload JSON di body request.</td>
                    </tr>
                    <tr>
                        <td><code class="param-name">Authorization</code></td>
                        <td><span class="badge-req">Wajib (Auth)</span></td>
                        <td><code class="inline">Bearer 1|abc123def456...</code></td>
                        <td>Token otorisasi Sanctum yang diperoleh setelah login/tukar token.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Standar Error & Rate Limiting -->
        <h2 class="section-heading" id="standar-error">Standar Error & Batas Permintaan</h2>
        <div class="card">
            <h3 class="card-title">Kode Status HTTP & Envelope Kesalahan</h3>
            <p>
                Setiap kesalahan validasi atau penolakan akses mengikuti format standar envelope Laravel:
            </p>
            <table class="spec-table">
                <thead>
                    <tr><th>Status</th><th>Arti</th><th>Skenario Penggunaan</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="status-pill s200">200 OK</span></td>
                        <td>Berhasil</td>
                        <td>Permintaan sukses diproses dan data dikembalikan sesuai format.</td>
                    </tr>
                    <tr>
                        <td><span class="status-pill s401">401 Unauthorized</span></td>
                        <td>Tidak Terotentikasi</td>
                        <td>Token Bearer tidak disertakan, tidak valid, atau telah dicabut (logout).</td>
                    </tr>
                    <tr>
                        <td><span class="status-pill s403">403 Forbidden</span></td>
                        <td>Akses Ditolak</td>
                        <td>Memanggil endpoint <code class="inline">/login</code> ketika sistem berjalan dalam mode SSO.</td>
                    </tr>
                    <tr>
                        <td><span class="status-pill s404">404 Not Found</span></td>
                        <td>Tidak Ditemukan</td>
                        <td>Pengguna terautentikasi tetapi belum memiliki profil data anggota koperasi.</td>
                    </tr>
                    <tr>
                        <td><span class="status-pill s422">422 Unprocessable</span></td>
                        <td>Validasi Gagal</td>
                        <td>Format email, tanggal, atau nilai parameter tidak memenuhi aturan validasi.</td>
                    </tr>
                    <tr>
                        <td><span class="status-pill s403" style="background:#FFF1F2;color:#BE123C;border-color:#FDA4AF;">429 Too Many</span></td>
                        <td>Rate Limit Exceeded</td>
                        <td>Melebihi kuota 60 request per menit. Header <code class="inline">Retry-After</code> menyatakan sisa detik tunggu.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ==================== ENDPOINT AUTENTIKASI ==================== -->
        <h2 class="section-heading">Endpoint Autentikasi</h2>

        <!-- Endpoint: POST /login -->
        <div class="card" id="ep-login" data-method="POST" data-path="/api/v1/login" data-auth="none" data-body='{"email":"nama@koperasi.test","password":"kata-sandi"}'>
            <div class="ep-header">
                <div class="ep-route">
                    <span class="method-badge post">POST</span>
                    <span class="ep-path">/api/v1/login</span>
                    <span class="auth-chip public">Publik (Guest)</span>
                </div>
                <div class="ep-actions">
                    <button class="btn-action-sm" type="button" onclick="salinUrl('POST', '/api/v1/login')">📋 Salin URL</button>
                    <button class="btn-action-sm try" type="button" onclick="isiKonsol('POST', '/api/v1/login', '', '{\n  &quot;email&quot;: &quot;nama@koperasi.test&quot;,\n  &quot;password&quot;: &quot;password&quot;\n}', '')">Coba di Konsol ⚡</button>
                </div>
            </div>

            <div class="description-box">
                Otentikasi pengguna menggunakan email dan kata sandi untuk menerbitkan token Sanctum (berlaku khusus saat <code class="inline">AUTH_MODE=local</code>). Jika dijalankan pada mode SSO, server otomatis menolak dengan respons 403 Forbidden.
            </div>

            <h4 style="font-size:12.5px;font-weight:700;color:var(--text-heading);margin-bottom:6px;">Payload Body (JSON):</h4>
            <table class="spec-table">
                <thead><tr><th>Field</th><th>Tipe</th><th>Ketentuan</th><th>Keterangan</th></tr></thead>
                <tbody>
                    <tr>
                        <td><code class="param-name">email</code></td>
                        <td><span class="type-badge">string</span></td>
                        <td><span class="badge-req">Wajib</span></td>
                        <td>Alamat email terdaftar anggota koperasi.</td>
                    </tr>
                    <tr>
                        <td><code class="param-name">password</code></td>
                        <td><span class="type-badge">string</span></td>
                        <td><span class="badge-req">Wajib</span></td>
                        <td>Kata sandi akun pengguna.</td>
                    </tr>
                </tbody>
            </table>

            <div class="code-tabs-wrapper">
                <div class="code-tab-bar">
                    <div class="tab-buttons">
                        <button class="code-tab-btn active" type="button" onclick="gantiTab(this, 'login-req-curl')">cURL</button>
                        <button class="code-tab-btn" type="button" onclick="gantiTab(this, 'login-req-js')">JavaScript (Fetch)</button>
                        <button class="code-tab-btn" type="button" onclick="gantiTab(this, 'login-req-php')">PHP</button>
                    </div>
                    <button class="btn-copy-code" type="button" onclick="salinKodeDariTab(this)">Salin</button>
                </div>
                <div class="tab-panel active" id="login-req-curl">
                    <pre class="code-block">curl -X POST "{{ url('/api/v1/login') }}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "nama@koperasi.test",
    "password": "kata-sandi"
  }'</pre>
                </div>
                <div class="tab-panel" id="login-req-js">
                    <pre class="code-block">const response = await fetch("{{ url('/api/v1/login') }}", {
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
const data = await response.json();
console.log("Token:", data.token);</pre>
                </div>
                <div class="tab-panel" id="login-req-php">
                    <pre class="code-block">use Illuminate\Support\Facades\Http;

$response = Http::acceptJson()->post("{{ url('/api/v1/login') }}", [
    'email' => 'nama@koperasi.test',
    'password' => 'kata-sandi',
]);

$token = $response->json('token');</pre>
                </div>
            </div>

            <div class="code-tabs-wrapper">
                <div class="code-tab-bar">
                    <div class="tab-buttons">
                        <button class="code-tab-btn active" type="button" onclick="gantiTab(this, 'login-res-200')">Respons 200 OK</button>
                        <button class="code-tab-btn" type="button" onclick="gantiTab(this, 'login-res-403')">Respons 403 (SSO)</button>
                        <button class="code-tab-btn" type="button" onclick="gantiTab(this, 'login-res-422')">Respons 422 (Salah)</button>
                    </div>
                    <button class="btn-copy-code" type="button" onclick="salinKodeDariTab(this)">Salin</button>
                </div>
                <div class="tab-panel active" id="login-res-200">
                    <pre class="code-block">{
  "user": {
    "id": 4,
    "name": "Budi Santoso",
    "email": "nama@koperasi.test",
    "created_at": "2026-01-15T08:00:00.000000Z",
    "updated_at": "2026-09-20T10:30:00.000000Z"
  },
  "token": "1|qX9u7mK..."
}</pre>
                </div>
                <div class="tab-panel" id="login-res-403">
                    <pre class="code-block">{
  "message": "Login password dinonaktifkan. Gunakan login SSO via browser."
}</pre>
                </div>
                <div class="tab-panel" id="login-res-422">
                    <pre class="code-block">{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": [
      "The provided credentials are incorrect."
    ]
  }
}</pre>
                </div>
            </div>
        </div>

        <!-- Endpoint: POST /logout -->
        <div class="card" id="ep-logout" data-method="POST" data-path="/api/v1/logout" data-auth="bearer">
            <div class="ep-header">
                <div class="ep-route">
                    <span class="method-badge post">POST</span>
                    <span class="ep-path">/api/v1/logout</span>
                    <span class="auth-chip bearer">Bearer Token</span>
                </div>
                <div class="ep-actions">
                    <button class="btn-action-sm" type="button" onclick="salinUrl('POST', '/api/v1/logout')">📋 Salin URL</button>
                    <button class="btn-action-sm try" type="button" onclick="isiKonsol('POST', '/api/v1/logout', '', '', 'bearer')">Coba di Konsol ⚡</button>
                </div>
            </div>

            <div class="description-box">
                Mencabut dan menghapus token Sanctum yang sedang digunakan pada sesi ini dari database. Setelah dipanggil, token tersebut tidak dapat digunakan kembali.
            </div>

            <div class="code-tabs-wrapper">
                <div class="code-tab-bar">
                    <div class="tab-buttons">
                        <button class="code-tab-btn active" type="button" onclick="gantiTab(this, 'logout-res-200')">Respons 200 OK</button>
                        <button class="code-tab-btn" type="button" onclick="gantiTab(this, 'logout-res-401')">Respons 401 Unauthorized</button>
                    </div>
                    <button class="btn-copy-code" type="button" onclick="salinKodeDariTab(this)">Salin</button>
                </div>
                <div class="tab-panel active" id="logout-res-200">
                    <pre class="code-block">{
  "message": "Logged out successfully"
}</pre>
                </div>
                <div class="tab-panel" id="logout-res-401">
                    <pre class="code-block">{
  "message": "Unauthenticated."
}</pre>
                </div>
            </div>
        </div>

        <!-- Endpoint: GET /user -->
        <div class="card" id="ep-user" data-method="GET" data-path="/api/v1/user" data-auth="bearer">
            <div class="ep-header">
                <div class="ep-route">
                    <span class="method-badge get">GET</span>
                    <span class="ep-path">/api/v1/user</span>
                    <span class="auth-chip bearer">Bearer Token</span>
                </div>
                <div class="ep-actions">
                    <button class="btn-action-sm" type="button" onclick="salinUrl('GET', '/api/v1/user')">📋 Salin URL</button>
                    <button class="btn-action-sm try" type="button" onclick="isiKonsol('GET', '/api/v1/user', '', '', 'bearer')">Coba di Konsol ⚡</button>
                </div>
            </div>

            <div class="description-box">
                Mengambil data profil pengguna yang sedang login berdasarkan token Sanctum. Berguna untuk memvalidasi apakah token masih aktif atau untuk menampilkan nama/email pengguna di header aplikasi mobile.
            </div>

            <div class="code-tabs-wrapper">
                <div class="code-tab-bar">
                    <div class="tab-buttons">
                        <button class="code-tab-btn active" type="button" onclick="gantiTab(this, 'user-res-200')">Respons 200 OK</button>
                        <button class="code-tab-btn" type="button" onclick="gantiTab(this, 'user-res-401')">Respons 401</button>
                    </div>
                    <button class="btn-copy-code" type="button" onclick="salinKodeDariTab(this)">Salin</button>
                </div>
                <div class="tab-panel active" id="user-res-200">
                    <pre class="code-block">{
  "id": 4,
  "name": "Budi Santoso",
  "email": "nama@koperasi.test",
  "email_verified_at": null,
  "created_at": "2026-01-15T08:00:00.000000Z",
  "updated_at": "2026-09-20T10:30:00.000000Z"
}</pre>
                </div>
                <div class="tab-panel" id="user-res-401">
                    <pre class="code-block">{
  "message": "Unauthenticated."
}</pre>
                </div>
            </div>
        </div>

        <!-- Endpoint: POST /sso/token -->
        <div class="card" id="ep-sso-token" data-method="POST" data-path="/api/v1/sso/token" data-auth="session">
            <div class="ep-header">
                <div class="ep-route">
                    <span class="method-badge post">POST</span>
                    <span class="ep-path">/api/v1/sso/token</span>
                    <span class="auth-chip session">Sesi Web SSO + CSRF</span>
                </div>
                <div class="ep-actions">
                    <button class="btn-action-sm" type="button" onclick="salinUrl('POST', '/api/v1/sso/token')">📋 Salin URL</button>
                    <button class="btn-action-sm try" type="button" onclick="isiKonsol('POST', '/api/v1/sso/token', '', '', 'session')">Coba di Konsol ⚡</button>
                </div>
            </div>

            <div class="description-box">
                <strong>Jembatan Otentikasi Mobile SSO:</strong> Menukar sesi login web SSO yang berhasil di peramban / WebView menjadi token Sanctum (<code class="inline">sso_token</code>). Dipanggil dengan menyertakan cookie sesi web dan header <code class="inline">X-CSRF-TOKEN</code>.
            </div>

            <div class="code-tabs-wrapper">
                <div class="code-tab-bar">
                    <div class="tab-buttons">
                        <button class="code-tab-btn active" type="button" onclick="gantiTab(this, 'sso-res-200')">Respons 200 OK</button>
                        <button class="code-tab-btn" type="button" onclick="gantiTab(this, 'sso-res-401')">Respons 401 Unauthorized</button>
                    </div>
                    <button class="btn-copy-code" type="button" onclick="salinKodeDariTab(this)">Salin</button>
                </div>
                <div class="tab-panel active" id="sso-res-200">
                    <pre class="code-block">{
  "user": {
    "id": 4,
    "name": "Budi Santoso",
    "email": "nama@koperasi.test"
  },
  "token": "2|def456ghi789..."
}</pre>
                </div>
                <div class="tab-panel" id="sso-res-401">
                    <pre class="code-block">{
  "message": "Unauthenticated."
}</pre>
                </div>
            </div>
        </div>

        <!-- ==================== ENDPOINT DATA ANGGOTA ==================== -->
        <h2 class="section-heading" id="anggota">Data Anggota (Self-Scoped)</h2>

        <!-- Endpoint: GET /anggota/pinjaman-aktif -->
        <div class="card" id="ep-pinjaman-aktif" data-method="GET" data-path="/api/v1/anggota/pinjaman-aktif" data-auth="bearer">
            <div class="ep-header">
                <div class="ep-route">
                    <span class="method-badge get">GET</span>
                    <span class="ep-path">/api/v1/anggota/pinjaman-aktif</span>
                    <span class="auth-chip bearer">Bearer Token</span>
                </div>
                <div class="ep-actions">
                    <button class="btn-action-sm" type="button" onclick="salinUrl('GET', '/api/v1/anggota/pinjaman-aktif')">📋 Salin URL</button>
                    <button class="btn-action-sm try" type="button" onclick="isiKonsol('GET', '/api/v1/anggota/pinjaman-aktif', '', '', 'bearer')">Coba di Konsol ⚡</button>
                </div>
            </div>

            <div class="description-box">
                Mengembalikan daftar pinjaman berstatus <strong>aktif</strong> milik anggota yang sedang login. Otomatis menghitung agregat sisa cicilan berjalan, sisa total kewajiban bayar, serta rincian angsuran terdekat berikutnya.
            </div>

            <div class="code-tabs-wrapper">
                <div class="code-tab-bar">
                    <div class="tab-buttons">
                        <button class="code-tab-btn active" type="button" onclick="gantiTab(this, 'aktif-res-200')">Respons 200 OK</button>
                        <button class="code-tab-btn" type="button" onclick="gantiTab(this, 'aktif-res-404')">Respons 404 (Bukan Anggota)</button>
                    </div>
                    <button class="btn-copy-code" type="button" onclick="salinKodeDariTab(this)">Salin</button>
                </div>
                <div class="tab-panel active" id="aktif-res-200">
                    <pre class="code-block">{
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
                <div class="tab-panel" id="aktif-res-404">
                    <pre class="code-block">{
  "message": "Anggota not found for user"
}</pre>
                </div>
            </div>
        </div>

        <!-- Endpoint: GET /anggota/riwayat-pinjaman -->
        <div class="card" id="ep-riwayat" data-method="GET" data-path="/api/v1/anggota/riwayat-pinjaman" data-auth="bearer">
            <div class="ep-header">
                <div class="ep-route">
                    <span class="method-badge get">GET</span>
                    <span class="ep-path">/api/v1/anggota/riwayat-pinjaman</span>
                    <span class="auth-chip bearer">Bearer Token</span>
                </div>
                <div class="ep-actions">
                    <button class="btn-action-sm" type="button" onclick="salinUrl('GET', '/api/v1/anggota/riwayat-pinjaman')">📋 Salin URL</button>
                    <button class="btn-action-sm try" type="button" onclick="isiKonsol('GET', '/api/v1/anggota/riwayat-pinjaman', 'status=lunas&per_page=10', '', 'bearer')">Coba di Konsol ⚡</button>
                </div>
            </div>

            <div class="description-box">
                Mengambil seluruh rekaman riwayat pinjaman milik anggota (diurutkan dari yang terbaru), lengkap dengan jadwal angsuran, status pelunasan, catatan pengurus, dan riwayat perubahan tenor (percepatan/perpanjangan).
            </div>

            <h4 style="font-size:12.5px;font-weight:700;color:var(--text-heading);margin-bottom:6px;">Query Parameters (Opsional):</h4>
            <table class="spec-table">
                <thead><tr><th>Parameter</th><th>Tipe</th><th>Ketentuan</th><th>Keterangan</th></tr></thead>
                <tbody>
                    <tr>
                        <td><code class="param-name">status</code></td>
                        <td><span class="type-badge">string</span></td>
                        <td><span class="badge-opt">Opsional</span></td>
                        <td>Pilihan filter: <code class="inline">diajukan</code>, <code class="inline">approved_admin</code>, <code class="inline">approved_bendahara</code>, <code class="inline">approved_ketua</code>, <code class="inline">aktif</code>, <code class="inline">ditolak</code>, <code class="inline">lunas</code>.</td>
                    </tr>
                    <tr>
                        <td><code class="param-name">per_page</code></td>
                        <td><span class="type-badge">integer</span></td>
                        <td><span class="badge-opt">Opsional</span></td>
                        <td>Batas data per halaman (1 s.d. 100, bawaan: 15).</td>
                    </tr>
                    <tr>
                        <td><code class="param-name">page</code></td>
                        <td><span class="type-badge">integer</span></td>
                        <td><span class="badge-opt">Opsional</span></td>
                        <td>Nomor halaman paginasi data.</td>
                    </tr>
                </tbody>
            </table>

            <div class="code-tabs-wrapper">
                <div class="code-tab-bar">
                    <div class="tab-buttons">
                        <button class="code-tab-btn active" type="button" onclick="gantiTab(this, 'riwayat-res-200')">Respons 200 OK</button>
                    </div>
                    <button class="btn-copy-code" type="button" onclick="salinKodeDariTab(this)">Salin</button>
                </div>
                <div class="tab-panel active" id="riwayat-res-200">
                    <pre class="code-block">{
  "data": [
    {
      "id": 12,
      "nominal": 5000000,
      "tenor_bulan": 10,
      "status": "aktif",
      "tanggal_pengajuan": "2026-05-10",
      "catatan_bendahara": "Dokumen verifikasi payroll valid",
      "catatan_ketua": "Disetujui",
      "angsuran": [
        {
          "cicilan_ke": 1,
          "total_bayar": 550000,
          "status": "lunas",
          "tanggal_jatuh_tempo": "2026-06-12"
        }
      ],
      "riwayat_perubahan": [
        {
          "tipe": "percepat",
          "tenor_lama": 12,
          "tenor_baru": 10,
          "tanggal": "2026-07-15"
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}</pre>
                </div>
            </div>
        </div>

        <!-- Endpoint: GET /anggota/limit -->
        <div class="card" id="ep-limit" data-method="GET" data-path="/api/v1/anggota/limit" data-auth="bearer">
            <div class="ep-header">
                <div class="ep-route">
                    <span class="method-badge get">GET</span>
                    <span class="ep-path">/api/v1/anggota/limit</span>
                    <span class="auth-chip bearer">Bearer Token</span>
                </div>
                <div class="ep-actions">
                    <button class="btn-action-sm" type="button" onclick="salinUrl('GET', '/api/v1/anggota/limit')">📋 Salin URL</button>
                    <button class="btn-action-sm try" type="button" onclick="isiKonsol('GET', '/api/v1/anggota/limit', '', '', 'bearer')">Coba di Konsol ⚡</button>
                </div>
            </div>

            <div class="description-box">
                Mengambil kalkulasi limit kredit anggota melalui <code class="inline">EligibilitasPinjamanService</code>. Menginformasikan batas limit maksimal, sisa limit yang boleh diajukan saat ini, status kelayakan (<code class="inline">boleh</code>), dan alasan pencegahan jika belum memenuhi kualifikasi.
            </div>

            <div class="code-tabs-wrapper">
                <div class="code-tab-bar">
                    <div class="tab-buttons">
                        <button class="code-tab-btn active" type="button" onclick="gantiTab(this, 'limit-res-200')">Respons 200 OK</button>
                    </div>
                    <button class="btn-copy-code" type="button" onclick="salinKodeDariTab(this)">Salin</button>
                </div>
                <div class="tab-panel active" id="limit-res-200">
                    <pre class="code-block">{
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

        <!-- Endpoint: GET /anggota/simpanan -->
        <div class="card" id="ep-simpanan" data-method="GET" data-path="/api/v1/anggota/simpanan" data-auth="bearer">
            <div class="ep-header">
                <div class="ep-route">
                    <span class="method-badge get">GET</span>
                    <span class="ep-path">/api/v1/anggota/simpanan</span>
                    <span class="auth-chip bearer">Bearer Token</span>
                </div>
                <div class="ep-actions">
                    <button class="btn-action-sm" type="button" onclick="salinUrl('GET', '/api/v1/anggota/simpanan')">📋 Salin URL</button>
                    <button class="btn-action-sm try" type="button" onclick="isiKonsol('GET', '/api/v1/anggota/simpanan', 'bulan_periode=2026-09', '', 'bearer')">Coba di Konsol ⚡</button>
                </div>
            </div>

            <div class="description-box">
                Menyajikan ringkasan saldo simpanan pokok & wajib anggota beserta 20 transaksi mutasi setoran simpanan terbaru. Dapat disaring per bulan periode tertentu.
            </div>

            <h4 style="font-size:12.5px;font-weight:700;color:var(--text-heading);margin-bottom:6px;">Query Parameters (Opsional):</h4>
            <table class="spec-table">
                <thead><tr><th>Parameter</th><th>Tipe</th><th>Ketentuan</th><th>Keterangan</th></tr></thead>
                <tbody>
                    <tr>
                        <td><code class="param-name">bulan_periode</code></td>
                        <td><span class="type-badge">string (YYYY-MM)</span></td>
                        <td><span class="badge-opt">Opsional</span></td>
                        <td>Menyaring mutasi pada bulan tertentu (contoh: <code class="inline">2026-09</code>).</td>
                    </tr>
                </tbody>
            </table>

            <div class="code-tabs-wrapper">
                <div class="code-tab-bar">
                    <div class="tab-buttons">
                        <button class="code-tab-btn active" type="button" onclick="gantiTab(this, 'simpanan-res-200')">Respons 200 OK</button>
                    </div>
                    <button class="btn-copy-code" type="button" onclick="salinKodeDariTab(this)">Salin</button>
                </div>
                <div class="tab-panel active" id="simpanan-res-200">
                    <pre class="code-block">{
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
    },
    {
      "jenis": "wajib",
      "jumlah": 50000,
      "bulan_periode": "2026-08",
      "tanggal_input": "2026-08-28"
    }
  ]
}</pre>
                </div>
            </div>
        </div>

        <!-- ==================== KONSOL COBA-COBA INTERAKTIF ==================== -->
        <h2 class="section-heading" id="konsol">Konsol Coba Interaktif</h2>
        <div class="console-container" id="konsol-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <h3 style="font-size:17px;font-weight:800;color:var(--navy);">
                    Interactive API Tester
                </h3>
                <span class="console-badge">Langsung Dari Peramban</span>
            </div>
            <p style="font-size:13.5px;color:var(--text-muted);margin-bottom:18px;">
                Uji langsung endpoint RESTful API di atas. Token yang Anda masukkan otomatis disimpan aman di memori sesi peramban (<code class="inline">sessionStorage</code>) sehingga tidak hilang saat merefresh halaman.
            </p>

            <div class="console-form-group">
                <label class="console-label" for="c-endpoint">
                    <span>Pilih Target Endpoint</span>
                </label>
                <select id="c-endpoint" class="console-select">
                    <option value="POST|/api/v1/login|none">POST /api/v1/login — Login lokal email & kata sandi</option>
                    <option value="POST|/api/v1/logout|bearer">POST /api/v1/logout — Cabut token aktif</option>
                    <option value="GET|/api/v1/user|bearer">GET /api/v1/user — Data user terotentikasi</option>
                    <option value="POST|/api/v1/sso/token|session">POST /api/v1/sso/token — Tukar sesi web SSO ke token</option>
                    <option value="GET|/api/v1/anggota/pinjaman-aktif|bearer" selected>GET /api/v1/anggota/pinjaman-aktif — Daftar pinjaman aktif</option>
                    <option value="GET|/api/v1/anggota/riwayat-pinjaman|bearer">GET /api/v1/anggota/riwayat-pinjaman — Riwayat pinjaman & tenor</option>
                    <option value="GET|/api/v1/anggota/limit|bearer">GET /api/v1/anggota/limit — Cek eligibilitas & limit kredit</option>
                    <option value="GET|/api/v1/anggota/simpanan|bearer">GET /api/v1/anggota/simpanan — Saldo & mutasi simpanan</option>
                </select>
            </div>

            <div class="console-form-group">
                <label class="console-label" for="c-token">
                    <span>Token Bearer (Sanctum)</span>
                    <span style="font-size:11px;font-weight:normal;color:var(--text-light);">Kosongkan untuk endpoint publik</span>
                </label>
                <div style="display:flex;gap:8px;">
                    <input id="c-token" class="console-input" type="password" placeholder="1|abc123def456..." autocomplete="off">
                    <button class="btn-action-sm" type="button" id="btn-toggle-token" onclick="toggleLihatToken()">👁️</button>
                    <button class="btn-action-sm" type="button" onclick="hapusTokenSesi()">Hapus</button>
                </div>
                <div class="console-presets">
                    <span style="font-size:11px;color:var(--text-muted);font-weight:600;">Preset Cepat:</span>
                    <button type="button" class="preset-chip" onclick="pakaiContohLogin()">Isi Contoh Akun Test</button>
                    @if(auth()->check())
                        <button type="button" class="preset-chip" onclick="pakaiSesiLoginWeb()">Pakai Sesi Login Web ({{ auth()->user()->name }})</button>
                    @endif
                </div>
            </div>

            <div class="console-row-2">
                <div class="console-form-group">
                    <label class="console-label" for="c-query">
                        <span>Query Parameters</span>
                    </label>
                    <input id="c-query" class="console-input" type="text" placeholder="mis. status=lunas&per_page=5" autocomplete="off">
                </div>
                <div class="console-form-group">
                    <label class="console-label" for="c-body">
                        <span>Payload Body JSON (khusus POST)</span>
                    </label>
                    <textarea id="c-body" class="console-textarea" placeholder='{"email":"...","password":"..."}'></textarea>
                </div>
            </div>

            <button class="btn-send-request" id="c-send" type="button">
                <span>Kirim Permintaan API</span>
                <span id="c-spinner" style="display:none;">⏳</span>
            </button>

            <!-- Result Box -->
            <div class="console-result-meta" id="c-meta-bar">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span>Status:</span>
                    <span class="meta-status-pill pending" id="c-status-pill">Siap Kirim</span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;color:var(--text-muted);">
                    <span id="c-latency">Latency: -</span>
                    <span id="c-size">Ukuran: -</span>
                    <button class="btn-action-sm" type="button" id="btn-copy-response" onclick="salinHasilKonsol()" style="padding:2px 8px;font-size:11px;">Salin JSON</button>
                </div>
            </div>

            <pre class="code-block" id="c-response" style="border-radius:var(--radius-sm);margin-top:8px;max-height:400px;">// Klik tombol "Kirim Permintaan API" untuk melihat respons di sini...</pre>
        </div>

        <footer class="docs-footer">
            Dokumentasi RESTful API v1 Koperasi App • Dibuat khusus untuk arsitektur internal berkecepatan tinggi.
        </footer>
    </main>
</div>

<!-- Floating Back to Top Button -->
<button class="back-top" id="back-top" type="button" aria-label="Kembali ke atas">↑</button>

<!-- Toast Notification -->
<div class="toast" id="toast-notif">
    <span id="toast-icon">✓</span>
    <span id="toast-msg">Tersalin!</span>
</div>

<script>
(function() {
    'use strict';

    // 1. Inisialisasi token dari sessionStorage agar user tidak perlu paste ulang saat refresh
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

    // 2. Tab switcher per blok kode
    window.gantiTab = function(btn, panelId) {
        var wrapper = btn.closest('.code-tabs-wrapper');
        if (!wrapper) return;
        wrapper.querySelectorAll('.code-tab-btn').forEach(function(b) { b.classList.remove('active'); });
        wrapper.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.remove('active'); });
        btn.classList.add('active');
        var panel = wrapper.querySelector('#' + panelId);
        if (panel) panel.classList.add('active');
    };

    // 3. Salin kode dari tab aktif
    window.salinKodeDariTab = function(btn) {
        var wrapper = btn.closest('.code-tabs-wrapper');
        if (!wrapper) return;
        var activePanel = wrapper.querySelector('.tab-panel.active');
        if (!activePanel) return;
        var text = activePanel.textContent.trim();
        salinKeClipboard(text, function() {
            var old = btn.textContent;
            btn.textContent = 'Tersalin ✓';
            setTimeout(function() { btn.textContent = old; }, 1500);
        });
    };

    // 4. Salin URL lengkap
    window.salinUrl = function(method, path) {
        var full = window.location.origin + path;
        salinKeClipboard(full, function() {
            tampilkanToast('URL ' + method + ' tersalin ke clipboard!');
        });
    };

    // 5. Salin Teks Umum
    window.salinTeks = function(str, msg) {
        salinKeClipboard(str, function() {
            tampilkanToast(msg || 'Tersalin!');
        });
    };

    function salinKeClipboard(text, cb) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(cb, cb);
        } else {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
            if (cb) cb();
        }
    }

    // 6. Toast helper
    function tampilkanToast(msg) {
        var toast = document.getElementById('toast-notif');
        var msgEl = document.getElementById('toast-msg');
        if (!toast || !msgEl) return;
        msgEl.textContent = msg;
        toast.classList.add('show');
        setTimeout(function() { toast.classList.remove('show'); }, 2200);
    }

    // 7. Toggle lihat token
    window.toggleLihatToken = function() {
        if (!tokenInput) return;
        var btn = document.getElementById('btn-toggle-token');
        if (tokenInput.type === 'password') {
            tokenInput.type = 'text';
            btn.textContent = '🔒';
        } else {
            tokenInput.type = 'password';
            btn.textContent = '👁️';
        }
    };

    // 8. Hapus token sesi
    window.hapusTokenSesi = function() {
        if (!tokenInput) return;
        tokenInput.value = '';
        sessionStorage.removeItem('koperasi_api_docs_token');
        tampilkanToast('Token sesi dihapus.');
    };

    // 9. Preset coba cepat
    window.pakaiContohLogin = function() {
        var epSelect = document.getElementById('c-endpoint');
        var bodyInput = document.getElementById('c-body');
        var queryInput = document.getElementById('c-query');
        epSelect.value = 'POST|/api/v1/login|none';
        bodyInput.value = JSON.stringify({ email: "nama@koperasi.test", password: "kata-sandi" }, null, 2);
        queryInput.value = '';
        tampilkanToast('Preset login lokal diisikan ke konsol.');
    };

    window.pakaiSesiLoginWeb = function() {
        var epSelect = document.getElementById('c-endpoint');
        epSelect.value = 'POST|/api/v1/sso/token|session';
        tampilkanToast('Preset SSO token diisikan ke konsol.');
    };

    // 10. Shortcut "Coba di Konsol" dari kartu
    window.isiKonsol = function(method, path, query, body, authType) {
        var epSelect = document.getElementById('c-endpoint');
        var bodyInput = document.getElementById('c-body');
        var queryInput = document.getElementById('c-query');
        var konsol = document.getElementById('konsol');

        var val = method + '|' + path + '|' + (authType || 'none');
        // Cari option yang cocok
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

    // 11. Salin hasil respons konsol
    window.salinHasilKonsol = function() {
        var resEl = document.getElementById('c-response');
        if (!resEl) return;
        salinKeClipboard(resEl.textContent, function() {
            tampilkanToast('Respons JSON tersalin!');
        });
    };

    // 12. Eksekusi Permintaan Konsol Interaktif
    var sendBtn = document.getElementById('c-send');
    var epSelect = document.getElementById('c-endpoint');
    var queryInput = document.getElementById('c-query');
    var bodyInput = document.getElementById('c-body');
    var statusPill = document.getElementById('c-status-pill');
    var latencyEl = document.getElementById('c-latency');
    var sizeEl = document.getElementById('c-size');
    var responseEl = document.getElementById('c-response');
    var spinner = document.getElementById('c-spinner');

    if (sendBtn) {
        sendBtn.addEventListener('click', function() {
            var parts = epSelect.value.split('|');
            var method = parts[0];
            var path = parts[1];
            var q = queryInput.value.trim().replace(/^\?/, '');
            var url = path + (q ? '?' + q : '');
            
            var headers = {
                'Accept': 'application/json'
            };
            if (method !== 'GET') {
                headers['Content-Type'] = 'application/json';
            }

            var tok = tokenInput.value.trim();
            if (tok) {
                headers['Authorization'] = 'Bearer ' + tok;
            }

            // Jika endpoint sso/token, tambahkan CSRF jika ada
            if (path.indexOf('sso/token') !== -1) {
                var metaCsrf = document.querySelector('meta[name="csrf-token"]');
                if (metaCsrf) headers['X-CSRF-TOKEN'] = metaCsrf.getAttribute('content');
            }

            var options = {
                method: method,
                headers: headers,
                credentials: 'same-origin'
            };

            if (method !== 'GET' && bodyInput.value.trim()) {
                options.body = bodyInput.value.trim();
            }

            sendBtn.disabled = true;
            spinner.style.display = 'inline';
            statusPill.textContent = 'Mengirim...';
            statusPill.className = 'meta-status-pill pending';
            responseEl.textContent = '// Menunggu respons dari server...';

            var startTime = performance.now();

            fetch(url, options).then(function(res) {
                var elapsed = Math.round(performance.now() - startTime);
                latencyEl.textContent = 'Latency: ' + elapsed + ' ms';

                return res.text().then(function(text) {
                    var len = text ? (text.length / 1024).toFixed(1) + ' KB' : '0 KB';
                    sizeEl.textContent = 'Ukuran: ' + len;

                    statusPill.textContent = res.status + ' ' + (res.statusText || '');
                    if (res.ok) {
                        statusPill.className = 'meta-status-pill success';
                    } else {
                        statusPill.className = 'meta-status-pill error';
                    }

                    try {
                        var json = JSON.parse(text);
                        responseEl.textContent = JSON.stringify(json, null, 2);

                        // Auto simpan token jika ini respons login berhasil
                        if (json.token && !tok) {
                            tokenInput.value = json.token;
                            sessionStorage.setItem('koperasi_api_docs_token', json.token);
                            tampilkanToast('Token otomatis tersimpan di sesi peramban!');
                        }
                    } catch (e) {
                        responseEl.textContent = text || '// (Respons kosong)';
                    }
                });
            }).catch(function(err) {
                statusPill.textContent = 'Gagal Terhubung';
                statusPill.className = 'meta-status-pill error';
                responseEl.textContent = String(err);
            }).finally(function() {
                sendBtn.disabled = false;
                spinner.style.display = 'none';
            });
        });
    }

    // 13. Search & Filter Sidebar
    var searchInput = document.getElementById('side-search');
    var clearBtn = document.getElementById('search-clear');
    var sideLinks = document.querySelectorAll('.side-item[data-method]');
    var sideCategories = document.querySelectorAll('.nav-category');
    var sideEmpty = document.getElementById('side-empty');
    var currentFilter = 'all';

    function filterSidebar() {
        var q = searchInput.value.trim().toLowerCase();
        clearBtn.classList.toggle('show', q.length > 0);
        var visibleCount = 0;

        sideLinks.forEach(function(item) {
            var text = item.textContent.toLowerCase();
            var method = item.getAttribute('data-method') || '';
            var matchesMethod = (currentFilter === 'all' || method.toUpperCase() === currentFilter.toUpperCase());
            var matchesQuery = !q || text.indexOf(q) !== -1 || (item.getAttribute('href') || '').toLowerCase().indexOf(q) !== -1;

            if (matchesMethod && matchesQuery) {
                item.style.display = 'flex';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        sideEmpty.hidden = (visibleCount > 0);
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterSidebar);
    }
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            filterSidebar();
            searchInput.focus();
        });
    }

    // Shortcut "/" to search
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

    // Method filter buttons
    var filterButtons = document.querySelectorAll('#method-filters .filter-pill');
    filterButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            filterButtons.forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            currentFilter = btn.getAttribute('data-filter') || 'all';
            filterSidebar();
        });
    });

    // 14. Scrollspy Navigation
    var spySections = document.querySelectorAll('[id]');
    var navLinks = document.querySelectorAll('.side-item[href^="#"]');
    var navMap = {};
    navLinks.forEach(function(link) {
        var id = link.getAttribute('href').slice(1);
        navMap[id] = link;
    });

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var id = entry.target.id;
                    if (navMap[id]) {
                        navLinks.forEach(function(l) { l.removeAttribute('aria-current'); });
                        navMap[id].setAttribute('aria-current', 'true');
                    }
                }
            });
        }, { rootMargin: '-20% 0px -60% 0px' });

        spySections.forEach(function(sec) {
            if (navMap[sec.id]) observer.observe(sec);
        });
    }

    // 15. Back to Top & Topbar Scroll
    var backTop = document.getElementById('back-top');
    var topbar = document.getElementById('topbar');
    window.addEventListener('scroll', function() {
        var y = window.scrollY;
        if (backTop) backTop.classList.toggle('show', y > 400);
        if (topbar) topbar.classList.toggle('scrolled', y > 10);
    }, { passive: true });

    if (backTop) {
        backTop.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
})();
</script>
</body>
</html>
