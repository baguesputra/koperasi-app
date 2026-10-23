<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">

        <title inertia>Koperasi</title>
        <meta name="description" content="Aplikasi Koperasi Simpan Pinjam Karyawan modern, aman, dan terpercaya.">
        <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
        <meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet, noimageindex">

        <!-- Fonts (Preconnected & Swap) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

        <!-- PWA Fullscreen & Mobile App -->
        <meta name="theme-color" content="#f8fafc">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Koperasi">
        <meta name="apple-touch-fullscreen" content="yes">
        <meta name="format-detection" content="telephone=no">
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="apple-touch-icon" href="/images/logo.png">

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
