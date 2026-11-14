<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'VetManager') }}</title>

    <!-- Preconnect for Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --font-latin: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            --font-arabic: 'Cairo', ui-sans-serif, system-ui, sans-serif;
        }
        [dir="rtl"] {
            font-family: var(--font-arabic);
        }
        [dir="ltr"] {
            font-family: var(--font-latin);
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="bg-[#FAF8F5] text-stone-900 antialiased selection:bg-emerald-500 selection:text-white min-h-screen">
    @inertia
</body>
</html>
