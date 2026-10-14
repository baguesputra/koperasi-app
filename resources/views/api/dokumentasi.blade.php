<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Dokumentasi RESTful API untuk aplikasi mobile koperasi dan integrasi">
    <title>Dokumentasi API v1 — Koperasi App</title>
    <link rel="icon" type="image/png" href="/images/logo.png">
    <style>
        :root {
            --bg-base: #FAFAFA;
            --card-bg: #FFFFFF;
            --border: #E5E7EB;
            --text-primary: #111827;
            --text-secondary: #4B5563;
            --text-muted: #6B7280;
            --navy: #0F1E36;
            --navy-light: #1A2E4D;
            --green: #1FA24C;
            --green-dark: #178A3E;
            --green-light: #E8F8EE;
            --blue: #1D4ED8;
            --blue-light: #EFF6FF;
            --red: #B91C1C;
            --red-light: #FEF2F2;
            --amber: #B45309;
            --amber-light: #FFFBEB;
            --radius: 14px;
            --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        }

        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; scroll-behavior: smooth; }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: var(--bg-base);
            color: var(--text-primary);
            margin: 0;
            min-height: 100vh;
            line-height: 1.6;
            font-size: 15px;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(255, 255, 255, 0.92);
            -webkit-backdrop-filter: blur(10px);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
        }
        .topbar.scrolled { box-shadow: 0 1px 8px rgba(15, 30, 54, 0.08); }
        .topbar-inner {
            max-width: 1180px;
            margin: 0 auto;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .topbar-brand { display: flex; align-items: center; gap: 10px; margin-right: auto; }
        .topbar img { width: 34px; height: 34px; border-radius: 9px; border: 1px solid var(--border); background: #fff; }
        .topbar-title { font-weight: 800; font-size: 15.5px; letter-spacing: -0.01em; color: var(--navy); }
        .badge-version {
            font-size: 11.5px;
            font-weight: 800;
            background: var(--green-light);
            color: var(--green-dark);
            border-radius: 999px;
            padding: 3px 10px;
            white-space: nowrap;
        }
        .topbar nav { display: flex; gap: 2px; align-items: center; flex-wrap: wrap; }
        .topbar nav a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 9px;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
        }
        .topbar nav a:hover { background: #F3F4F6; color: var(--text-primary); }
        .topbar nav a:focus-visible { outline: 2px solid var(--green); outline-offset: 1px; }
        .topbar nav a.btn-top { background: var(--green-dark); color: #fff; margin-left: 6px; }
        .topbar nav a.btn-top:hover { background: var(--green); color: #fff; }

        .layout { max-width: 1180px; margin: 0 auto; padding: 24px 20px 64px; display: grid; grid-template-columns: 232px minmax(0, 1fr); gap: 24px; align-items: start; }
        .layout main { min-width: 0; }
        [id] { scroll-margin-top: 88px; }

        .skip-link { position: absolute; left: 12px; top: -60px; z-index: 20; background: var(--navy); color: #fff; padding: 10px 16px; border-radius: 10px; text-decoration: none; font-weight: 700; }
        .skip-link:focus { top: 12px; color: #fff; }

        .sidebar { position: sticky; top: 84px; max-height: calc(100vh - 104px); overflow-y: auto; background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); padding: 14px; }
        .side-search-label { display: block; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); margin-bottom: 6px; }
        .sidebar input[type="search"] { width: 100%; font-size: 14px; padding: 10px 12px; min-height: 44px; border: 1px solid var(--border); border-radius: 10px; font-family: inherit; background: #fff; color: var(--text-primary); }
        .sidebar input[type="search"]:focus-visible { outline: 2px solid var(--green); outline-offset: 1px; border-color: var(--green); }
        .side-group { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin: 16px 4px 6px; }
        .side-link { display: flex; align-items: center; gap: 8px; padding: 8px 10px; min-height: 44px; border-radius: 8px; color: var(--text-secondary); text-decoration: none; font-weight: 600; font-size: 13.5px; }
        .side-link:hover { background: #F3F4F6; }
        .side-link:focus-visible { outline: 2px solid var(--green); outline-offset: 1px; }
        .side-link[aria-current="true"] { background: var(--green-light); color: var(--green-dark); }
        .side-link .method.mini, .side-link .mini-go { flex: none; }
        .method.mini { min-width: 46px; font-size: 10px; padding: 2px 6px; }
        .mini-go { min-width: 46px; text-align: center; font-weight: 800; color: var(--green-dark); }
        .side-link[hidden], .side-group[hidden], .card[hidden] { display: none; }
        .side-empty { font-size: 13px; color: var(--text-muted); padding: 8px 4px; }

        .back-top { position: fixed; right: 16px; bottom: 16px; z-index: 10; width: 48px; height: 48px; border-radius: 50%; border: 1px solid var(--border); background: var(--navy); color: #fff; font-size: 20px; cursor: pointer; display: none; box-shadow: var(--shadow); }
        .back-top.show { display: inline-flex; align-items: center; justify-content: center; }
        .back-top:focus-visible { outline: 2px solid var(--green); outline-offset: 2px; }

        @media (max-width: 899px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar { position: static; max-height: none; }
        }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 20px;
        }
        h1 { font-size: 22px; margin: 0 0 4px; }
        h2 { font-size: 18px; margin: 32px 0 12px; padding-top: 8px; border-top: 2px solid var(--navy); }
        h3 { font-size: 16px; margin: 0 0 8px; }
        p, li { color: var(--text-secondary); }
        .card > p:first-of-type, h1 + p { margin-top: 4px; }

        code.inline {
            font-family: ui-monospace, 'Cascadia Code', Consolas, monospace;
            font-size: 0.88em;
            background: #F3F4F6;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 1px 6px;
            word-break: break-all;
        }

        .endpoint-head { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 8px; }
        .method {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
            border-radius: 6px;
            padding: 4px 10px;
            min-width: 62px;
            text-align: center;
        }
        .method.get { background: var(--green-light); color: var(--green-dark); }
        .method.post { background: var(--blue-light); color: var(--blue); }
        .endpoint-path {
            font-family: ui-monospace, 'Cascadia Code', Consolas, monospace;
            font-size: 14px;
            font-weight: 600;
            word-break: break-all;
        }
        .chip {
            font-size: 12px;
            font-weight: 700;
            border-radius: 999px;
            padding: 2px 10px;
            white-space: nowrap;
        }
        .chip.auth { background: var(--amber-light); color: var(--amber); }
        .chip.public { background: #F3F4F6; color: var(--text-secondary); }
        .chip.session { background: #F5F3FF; color: #6D28D9; }

        table.params { width: 100%; border-collapse: collapse; font-size: 14px; margin: 12px 0; }
        table.params th, table.params td { text-align: left; padding: 8px 10px; border-bottom: 1px solid var(--border); vertical-align: top; }
        table.params th { font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); }
        .req { color: var(--red); font-weight: 700; }

        pre {
            background: var(--navy);
            color: #E5E7EB;
            border-radius: 10px;
            padding: 14px 16px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.55;
            margin: 10px 0;
            font-family: ui-monospace, 'Cascadia Code', Consolas, monospace;
        }
        .example-label { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin: 14px 0 0; }
        .copy-btn {
            font-size: 12px;
            font-weight: 700;
            border: 1px solid var(--border);
            background: #fff;
            border-radius: 8px;
            padding: 8px 12px;
            min-height: 44px;
            cursor: pointer;
            color: var(--text-secondary);
        }
        .copy-btn:hover, .copy-btn:focus-visible { border-color: var(--green); color: var(--green-dark); outline: none; }

        .status-list { list-style: none; padding: 0; margin: 8px 0 0; font-size: 14px; }
        .status-list li { padding: 3px 0; }
        .status-code {
            display: inline-block;
            font-family: ui-monospace, Consolas, monospace;
            font-weight: 700;
            font-size: 12px;
            border-radius: 6px;
            padding: 1px 8px;
            margin-right: 8px;
            min-width: 44px;
            text-align: center;
        }
        .status-code.ok { background: var(--green-light); color: var(--green-dark); }
        .status-code.err { background: var(--red-light); color: var(--red); }

        .console label { display: block; font-size: 13px; font-weight: 700; margin: 12px 0 4px; }
        .console input, .console select, .console textarea {
            width: 100%;
            font-size: 14px;
            padding: 10px 12px;
            min-height: 44px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-family: inherit;
            background: #fff;
            color: var(--text-primary);
        }
        .console input:focus-visible, .console select:focus-visible, .console textarea:focus-visible {
            outline: 2px solid var(--green);
            outline-offset: 1px;
            border-color: var(--green);
        }
        .console textarea { font-family: ui-monospace, Consolas, monospace; font-size: 13px; resize: vertical; }
        .console-row { display: grid; grid-template-columns: 110px 1fr; gap: 10px; }
        @media (max-width: 560px) { .console-row { grid-template-columns: 1fr; } }
        .btn-send {
            margin-top: 14px;
            width: 100%;
            min-height: 48px;
            font-size: 15px;
            font-weight: 800;
            color: #fff;
            background: var(--green-dark);
            border: none;
            border-radius: 12px;
            cursor: pointer;
        }
        .btn-send:hover, .btn-send:focus-visible { background: var(--green); outline: none; }
        .btn-send:disabled { opacity: 0.6; cursor: wait; }
        .hint { font-size: 13px; color: var(--text-muted); }

        footer { text-align: center; color: var(--text-muted); font-size: 13px; padding: 24px 0 8px; }
    </style>
</head>
<body>
<a class="skip-link" href="#konten">Lewati ke konten</a>
<header class="topbar" id="topbar">
    <div class="topbar-inner">
        <div class="topbar-brand">
            <img src="/images/logo.png" alt="Logo koperasi">
            <span class="topbar-title">Koperasi App API</span>
            <span class="badge-version">v1</span>
        </div>
        <nav aria-label="Navigasi dokumentasi">
            <a href="#autentikasi">Autentikasi</a>
            <a href="#anggota">Anggota</a>
            <a href="#konsol" class="btn-top">Konsol Coba</a>
        </nav>
    </div>
</header>

<div class="layout">
<aside class="sidebar" aria-label="Daftar endpoint">
    <label class="side-search-label" for="side-search">Cari endpoint</label>
    <input type="search" id="side-search" placeholder="mis. limit, login" autocomplete="off">
    <nav aria-label="Endpoint API">
        <p class="side-group">Autentikasi</p>
        <a class="side-link" href="#ep-login"><span class="method post mini">POST</span><span>login</span></a>
        <a class="side-link" href="#ep-logout"><span class="method post mini">POST</span><span>logout</span></a>
        <a class="side-link" href="#ep-user"><span class="method get mini">GET</span><span>user</span></a>
        <a class="side-link" href="#ep-sso-token"><span class="method post mini">POST</span><span>sso/token</span></a>
        <p class="side-group">Anggota</p>
        <a class="side-link" href="#ep-pinjaman-aktif"><span class="method get mini">GET</span><span>pinjaman-aktif</span></a>
        <a class="side-link" href="#ep-riwayat"><span class="method get mini">GET</span><span>riwayat-pinjaman</span></a>
        <a class="side-link" href="#ep-limit"><span class="method get mini">GET</span><span>limit</span></a>
        <a class="side-link" href="#ep-simpanan"><span class="method get mini">GET</span><span>simpanan</span></a>
        <p class="side-group">Alat</p>
        <a class="side-link" href="#konsol-card"><span class="mini-go">›</span><span>Konsol coba</span></a>
    </nav>
    <p class="side-empty" id="side-empty" hidden>Tidak ada endpoint yang cocok.</p>
</aside>
<main id="konten">
    <div class="card">
        <h1>Dokumentasi API v1</h1>
        <p>RESTful API untuk aplikasi mobile koperasi dan integrasi internal. Base URL: <code class="inline">{{ url('/api/v1') }}</code>. Semua respons berformat JSON. Batas request: 60 per menit per user/IP.</p>
    </div>

    <h2 id="autentikasi">Autentikasi</h2>

    <div class="card">
        <h3>Cara mendapatkan token</h3>
        <ul>
            <li><strong>Mode lokal</strong> (<code class="inline">AUTH_MODE=local</code>): login email + password via <code class="inline">POST /login</code>, terima token Sanctum, kirim sebagai header <code class="inline">Authorization: Bearer &lt;token&gt;</code>.</li>
            <li><strong>Mode SSO</strong> (<code class="inline">AUTH_MODE=sso</code>): login password <strong>dinonaktifkan</strong>. Selesaikan login SSO via browser, lalu tukar sesi web menjadi token via <code class="inline">POST /sso/token</code> (butuh cookie sesi + CSRF).</li>
        </ul>
        <p>Kode error umum: <code class="inline">401</code> token hilang/tidak valid, <code class="inline">403</code> login password saat mode SSO, <code class="inline">404</code> user tanpa data anggota, <code class="inline">422</code> validasi, <code class="inline">429</code> kena batas request.</p>
    </div>

    <div class="card" id="ep-login" data-method="POST" data-path="/api/v1/login" data-auth="none" data-body='{"email":"nama@koperasi.test","password":"kata-sandi"}'>
        <div class="endpoint-head">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/login</span>
            <span class="chip public">publik</span>
        </div>
        <p>Login dengan email + password (hanya mode lokal). Mengembalikan data user dan token Sanctum.</p>
        <p class="example-label">Contoh request</p>
        <pre id="ex-login-req">{
  "email": "nama@koperasi.test",
  "password": "kata-sandi"
}</pre>
        <button class="copy-btn" type="button" data-copy="ex-login-req">Salin</button>
        <p class="example-label">Contoh respons 200</p>
        <pre id="ex-login-res">{
  "user": { "id": 4, "name": "Budi Santoso", "email": "nama@koperasi.test" },
  "token": "1|abc123..."
}</pre>
        <button class="copy-btn" type="button" data-copy="ex-login-res">Salin</button>
        <ul class="status-list">
            <li><span class="status-code ok">200</span>Login berhasil</li>
            <li><span class="status-code err">403</span>Mode SSO — gunakan login browser</li>
            <li><span class="status-code err">422</span>Email/password salah atau tidak valid</li>
        </ul>
    </div>

    <div class="card" id="ep-logout" data-method="POST" data-path="/api/v1/logout" data-auth="bearer">
        <div class="endpoint-head">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/logout</span>
            <span class="chip auth">Bearer</span>
        </div>
        <p>Mencabut token yang sedang dipakai.</p>
        <p class="example-label">Contoh respons 200</p>
        <pre id="ex-logout-res">{
  "message": "Logged out successfully"
}</pre>
        <button class="copy-btn" type="button" data-copy="ex-logout-res">Salin</button>
        <ul class="status-list">
            <li><span class="status-code ok">200</span>Berhasil logout</li>
            <li><span class="status-code err">401</span>Tidak terautentikasi</li>
        </ul>
    </div>

    <div class="card" id="ep-user" data-method="GET" data-path="/api/v1/user" data-auth="bearer">
        <div class="endpoint-head">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/user</span>
            <span class="chip auth">Bearer</span>
        </div>
        <p>Mengambil data user yang sedang login (untuk cek token masih berlaku).</p>
        <ul class="status-list">
            <li><span class="status-code ok">200</span>Data user</li>
            <li><span class="status-code err">401</span>Tidak terautentikasi</li>
        </ul>
    </div>

    <div class="card" id="ep-sso-token" data-method="POST" data-path="/api/v1/sso/token" data-auth="session">
        <div class="endpoint-head">
            <span class="method post">POST</span>
            <span class="endpoint-path">/api/v1/sso/token</span>
            <span class="chip session">sesi web SSO</span>
        </div>
        <p>Menukar sesi web hasil login SSO menjadi token Sanctum (untuk aplikasi mobile). Panggil dari browser/WebView yang sudah login SSO; butuh cookie sesi + header CSRF.</p>
        <p class="example-label">Contoh respons 200</p>
        <pre id="ex-sso-res">{
  "user": { "id": 4, "name": "Budi Santoso" },
  "token": "2|def456..."
}</pre>
        <button class="copy-btn" type="button" data-copy="ex-sso-res">Salin</button>
        <ul class="status-list">
            <li><span class="status-code ok">200</span>Token diterbitkan</li>
            <li><span class="status-code err">401</span>Belum login SSO di browser</li>
        </ul>
    </div>

    <h2 id="anggota">Data anggota (milik sendiri)</h2>
    <div class="card">
        <p>Empat endpoint di bawah membaca data <strong>anggota pemilik token</strong> — tanpa parameter ID, jadi tidak bisa mengintip data anggota lain.</p>
    </div>

    <div class="card" id="ep-pinjaman-aktif" data-method="GET" data-path="/api/v1/anggota/pinjaman-aktif" data-auth="bearer">
        <div class="endpoint-head">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/anggota/pinjaman-aktif</span>
            <span class="chip auth">Bearer</span>
        </div>
        <p>Daftar pinjaman berstatus aktif milik anggota, lengkap dengan sisa angsuran dan angsuran terdekat berikutnya.</p>
        <p class="example-label">Contoh respons 200</p>
        <pre id="ex-aktif-res">{
  "data": [
    {
      "id": 1,
      "nominal": 1000000,
      "tenor_bulan": 2,
      "status": "aktif",
      "tanggal_pengajuan": "2026-08-01",
      "tanggal_pencairan": "2026-08-05",
      "sisa_angsuran": 2,
      "total_angsuran": 2,
      "sisa_total_bayar": 600000,
      "angsuran_berikutnya": {
        "cicilan_ke": 1,
        "total_bayar": 300000,
        "tanggal_jatuh_tempo": "2026-09-05"
      }
    }
  ],
  "count": 1
}</pre>
        <button class="copy-btn" type="button" data-copy="ex-aktif-res">Salin</button>
        <ul class="status-list">
            <li><span class="status-code ok">200</span>Daftar pinjaman aktif (kosong bila tidak ada)</li>
            <li><span class="status-code err">401</span>Tidak terautentikasi</li>
            <li><span class="status-code err">404</span>User tanpa data anggota</li>
        </ul>
    </div>

    <div class="card" id="ep-riwayat" data-method="GET" data-path="/api/v1/anggota/riwayat-pinjaman" data-auth="bearer">
        <div class="endpoint-head">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/anggota/riwayat-pinjaman</span>
            <span class="chip auth">Bearer</span>
        </div>
        <p>Riwayat seluruh pinjaman milik anggota (terbaru dulu), beserta jadwal angsuran dan riwayat perubahan tenor.</p>
        <table class="params">
            <thead><tr><th>Parameter</th><th>Wajib</th><th>Keterangan</th></tr></thead>
            <tbody>
                <tr><td><code class="inline">status</code></td><td>tidak</td><td>Saring status: <code class="inline">diajukan</code>, <code class="inline">approved_admin</code>, <code class="inline">approved_bendahara</code>, <code class="inline">approved_ketua</code>, <code class="inline">aktif</code>, <code class="inline">ditolak</code>, <code class="inline">lunas</code></td></tr>
                <tr><td><code class="inline">per_page</code></td><td>tidak</td><td>Jumlah per halaman, 1–100 (default 15)</td></tr>
                <tr><td><code class="inline">page</code></td><td>tidak</td><td>Nomor halaman</td></tr>
            </tbody>
        </table>
        <p class="example-label">Contoh respons 200</p>
        <pre id="ex-riwayat-res">{
  "data": [
    {
      "id": 1,
      "nominal": 1000000,
      "tenor_bulan": 2,
      "status": "lunas",
      "tanggal_pengajuan": "2026-08-01",
      "catatan_bendahara": null,
      "catatan_ketua": null,
      "angsuran": [
        { "cicilan_ke": 1, "total_bayar": 300000, "status": "lunas", "tanggal_jatuh_tempo": "2026-09-05" }
      ],
      "riwayat_perubahan": []
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 }
}</pre>
        <button class="copy-btn" type="button" data-copy="ex-riwayat-res">Salin</button>
        <ul class="status-list">
            <li><span class="status-code ok">200</span>Halaman riwayat</li>
            <li><span class="status-code err">401</span>Tidak terautentikasi</li>
            <li><span class="status-code err">422</span>Parameter tidak valid</li>
        </ul>
    </div>

    <div class="card" id="ep-limit" data-method="GET" data-path="/api/v1/anggota/limit" data-auth="bearer">
        <div class="endpoint-head">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/anggota/limit</span>
            <span class="chip auth">Bearer</span>
        </div>
        <p>Limit pinjaman milik anggota: maksimal, tersedia saat ini, boleh/tidaknya mengajukan, dan alasannya.</p>
        <p class="example-label">Contoh respons 200</p>
        <pre id="ex-limit-res">{
  "limit_maksimal": 5000000,
  "limit_tersedia": 5000000,
  "boleh": true,
  "alasan": null,
  "sisa_angsuran": 0,
  "cicilan_pokok": 0
}</pre>
        <button class="copy-btn" type="button" data-copy="ex-limit-res">Salin</button>
        <ul class="status-list">
            <li><span class="status-code ok">200</span>Data limit</li>
            <li><span class="status-code err">401</span>Tidak terautentikasi</li>
            <li><span class="status-code err">404</span>User tanpa data anggota</li>
        </ul>
    </div>

    <div class="card" id="ep-simpanan" data-method="GET" data-path="/api/v1/anggota/simpanan" data-auth="bearer">
        <div class="endpoint-head">
            <span class="method get">GET</span>
            <span class="endpoint-path">/api/v1/anggota/simpanan</span>
            <span class="chip auth">Bearer</span>
        </div>
        <p>Ringkasan dan 20 transaksi simpanan terakhir (pokok + wajib) milik anggota.</p>
        <table class="params">
            <thead><tr><th>Parameter</th><th>Wajib</th><th>Keterangan</th></tr></thead>
            <tbody>
                <tr><td><code class="inline">bulan_periode</code></td><td>tidak</td><td>Saring bulan, format <code class="inline">YYYY-MM</code> (contoh <code class="inline">2026-09</code>)</td></tr>
            </tbody>
        </table>
        <p class="example-label">Contoh respons 200</p>
        <pre id="ex-simpanan-res">{
  "ringkasan": { "total": 80000, "pokok": 50000, "wajib": 30000 },
  "data": [
    { "jenis": "pokok", "jumlah": 50000, "bulan_periode": "2026-09", "tanggal_input": "2026-09-30" }
  ]
}</pre>
        <button class="copy-btn" type="button" data-copy="ex-simpanan-res">Salin</button>
        <ul class="status-list">
            <li><span class="status-code ok">200</span>Data simpanan</li>
            <li><span class="status-code err">401</span>Tidak terautentikasi</li>
            <li><span class="status-code err">422</span>Format bulan salah</li>
        </ul>
    </div>

    <h2 id="konsol">Konsol coba</h2>
    <div class="card console" id="konsol-card">
        <p>Kirim request langsung ke API ini dari browser. Token hanya disimpan di memori halaman (hilang saat refresh) dan tidak dikirim ke mana pun selain server ini.</p>
        <label for="c-endpoint">Endpoint</label>
        <select id="c-endpoint">
            <option value="POST|/api/v1/login|none">POST /api/v1/login</option>
            <option value="POST|/api/v1/logout|bearer">POST /api/v1/logout</option>
            <option value="GET|/api/v1/user|bearer">GET /api/v1/user</option>
            <option value="GET|/api/v1/anggota/pinjaman-aktif|bearer" selected>GET /api/v1/anggota/pinjaman-aktif</option>
            <option value="GET|/api/v1/anggota/riwayat-pinjaman|bearer">GET /api/v1/anggota/riwayat-pinjaman</option>
            <option value="GET|/api/v1/anggota/limit|bearer">GET /api/v1/anggota/limit</option>
            <option value="GET|/api/v1/anggota/simpanan|bearer">GET /api/v1/anggota/simpanan</option>
        </select>
        <label for="c-token">Token Bearer (kosongkan untuk endpoint publik)</label>
        <input id="c-token" type="password" autocomplete="off" placeholder="1|abc123...">
        <div class="console-row">
            <div>
                <label for="c-query">Query</label>
                <input id="c-query" type="text" autocomplete="off" placeholder="status=lunas">
            </div>
            <div>
                <label for="c-body">Body JSON (khusus POST)</label>
                <input id="c-body" type="text" autocomplete="off" placeholder='{"email":"...","password":"..."}'>
            </div>
        </div>
        <button class="btn-send" id="c-send" type="button">Kirim request</button>
        <p class="hint" id="c-meta">Siap.</p>
        <p class="example-label">Respons</p>
        <pre id="c-response">Belum ada request.</pre>
    </div>

    <footer>Dokumentasi API v1 Koperasi App — dibuat tulis-tangan, selalu ikuti implementasi terbaru.</footer>
</main>
</div>

<button class="back-top" id="back-top" type="button" aria-label="Kembali ke atas">↑</button>

<script>
(function () {
    'use strict';

    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var el = document.getElementById(btn.getAttribute('data-copy'));
            if (!el) return;
            var done = function () { btn.textContent = 'Tersalin!'; setTimeout(function () { btn.textContent = 'Salin'; }, 1500); };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(el.textContent).then(done, done);
            } else {
                var ta = document.createElement('textarea');
                ta.value = el.textContent;
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(ta);
                done();
            }
        });
    });

    var endpoint = document.getElementById('c-endpoint');
    var token = document.getElementById('c-token');
    var query = document.getElementById('c-query');
    var body = document.getElementById('c-body');
    var send = document.getElementById('c-send');
    var meta = document.getElementById('c-meta');
    var response = document.getElementById('c-response');

    send.addEventListener('click', function () {
        var parts = endpoint.value.split('|');
        var method = parts[0];
        var path = parts[1];
        var url = path + (query.value.trim() ? '?' + query.value.trim().replace(/^\?/, '') : '');
        var headers = { 'Accept': 'application/json', 'Content-Type': 'application/json' };
        if (token.value.trim()) headers['Authorization'] = 'Bearer ' + token.value.trim();

        var options = { method: method, headers: headers, credentials: 'same-origin' };
        if (method !== 'GET' && body.value.trim()) options.body = body.value.trim();

        meta.textContent = 'Mengirim ' + method + ' ' + url + ' ...';
        response.textContent = 'Menunggu respons ...';
        send.disabled = true;

        window.fetch(url, options).then(function (res) {
            return res.text().then(function (text) {
                var out;
                try { out = JSON.stringify(JSON.parse(text), null, 2); }
                catch (e) { out = text; }
                meta.textContent = 'Status ' + res.status + ' ' + res.statusText;
                response.textContent = out;
            });
        }).catch(function (err) {
            meta.textContent = 'Gagal mengirim request.';
            response.textContent = String(err);
        }).finally(function () {
            send.disabled = false;
        });
    });

    /* Salin perintah cURL per endpoint */
    function copyString(text, btn, okLabel) {
        var done = function () { var old = btn.textContent; btn.textContent = okLabel; setTimeout(function () { btn.textContent = old; }, 1500); };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, done);
        } else {
            var ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
            done();
        }
    }

    document.querySelectorAll('.card[data-method]').forEach(function (card) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'copy-btn';
        btn.textContent = 'Salin cURL';
        btn.addEventListener('click', function () {
            var cmd = 'curl -X ' + card.getAttribute('data-method') + " '" + window.location.origin + card.getAttribute('data-path') + "'";
            if (card.getAttribute('data-auth') === 'bearer') cmd += " -H 'Authorization: Bearer <token>'";
            if (card.getAttribute('data-auth') === 'session') cmd += '  # butuh cookie sesi SSO di browser yang sama';
            var b = card.getAttribute('data-body');
            if (b) cmd += " -H 'Content-Type: application/json' -d '" + b + "'";
            copyString(cmd, btn, 'cURL tersalin!');
        });
        var head = card.querySelector('.endpoint-head');
        head.parentNode.insertBefore(btn, head.nextSibling);
    });

    /* Scrollspy: tandai link sidebar sesuai kartu yang terlihat */
    var spyLinks = {};
    document.querySelectorAll('.side-link[href^="#"]').forEach(function (a) {
        spyLinks[a.getAttribute('href').slice(1)] = a;
    });
    if ('IntersectionObserver' in window) {
        var spy = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (!en.isIntersecting) return;
                Object.keys(spyLinks).forEach(function (id) { spyLinks[id].removeAttribute('aria-current'); });
                if (spyLinks[en.target.id]) spyLinks[en.target.id].setAttribute('aria-current', 'true');
            });
        }, { rootMargin: '-35% 0px -55% 0px' });
        Object.keys(spyLinks).forEach(function (id) {
            var el = document.getElementById(id);
            if (el) spy.observe(el);
        });
    }

    /* Saring endpoint dari sidebar */
    var sideSearch = document.getElementById('side-search');
    var sideEmpty = document.getElementById('side-empty');
    var spyCards = Object.keys(spyLinks).map(function (id) { return document.getElementById(id); }).filter(Boolean);
    sideSearch.addEventListener('input', function () {
        var q = sideSearch.value.trim().toLowerCase();
        var visible = 0;
        spyCards.forEach(function (card) {
            var hit = !q || card.textContent.toLowerCase().indexOf(q) !== -1;
            card.hidden = !hit;
            if (spyLinks[card.id]) spyLinks[card.id].hidden = !hit;
            if (hit) visible++;
        });
        document.querySelectorAll('.side-group').forEach(function (g) {
            var sib = g.nextElementSibling;
            var any = false;
            while (sib && !sib.classList.contains('side-group')) {
                if (sib.classList.contains('side-link') && !sib.hidden) { any = true; break; }
                sib = sib.nextElementSibling;
            }
            g.hidden = !any;
        });
        sideEmpty.hidden = visible !== 0;
    });

    /* Kembali ke atas */
    var backTop = document.getElementById('back-top');
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var topbar = document.getElementById('topbar');
    window.addEventListener('scroll', function () {
        backTop.classList.toggle('show', window.scrollY > 600);
        topbar.classList.toggle('scrolled', window.scrollY > 8);
    }, { passive: true });
    backTop.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
    });
})();
</script>
</body>
</html>
