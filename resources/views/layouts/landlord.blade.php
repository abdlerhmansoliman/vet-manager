<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Platform Management' }} — VetManager Landlord Console</title>
    <meta name="description" content="Central superadministrator console for VetManager multi-tenant veterinary platform.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        .glass-panel {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(51, 65, 85, 0.5);
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(51, 65, 85, 0.4);
        }
        .glass-card:hover {
            border-color: rgba(94, 234, 212, 0.4);
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.1);
        }
    </style>
</head>
<body class="min-h-full flex flex-col font-sans selection:bg-emerald-500 selection:text-white bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-900 via-slate-950 to-black">

    <!-- Top Navigation Header -->
    <header class="sticky top-0 z-40 border-b border-slate-800/80 glass-panel">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand Mark -->
                <div class="flex items-center space-x-4">
                    <a href="{{ route('landlord.dashboard') }}" class="flex items-center space-x-3 group" id="brand-link">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 via-teal-500 to-cyan-400 p-0.5 shadow-lg shadow-emerald-500/20 group-hover:shadow-emerald-500/40 transition-all duration-300">
                            <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-400 group-hover:scale-110 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-lg text-white tracking-tight">Vet<span class="text-emerald-400">Manager</span></span>
                                <span class="px-2 py-0.5 text-[10px] font-semibold tracking-wider uppercase rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">Landlord</span>
                            </div>
                            <span class="text-xs text-slate-400 hidden sm:block">Central Operations & Tenancy Controller</span>
                        </div>
                    </a>
                </div>

                <!-- System Health Pill Statuses -->
                <div class="hidden lg:flex items-center space-x-3 text-xs">
                    <div class="flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-slate-900/80 border border-slate-800 text-slate-300" title="Central Landlord MySQL Database">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="text-slate-400">Database:</span>
                        <span class="font-medium text-emerald-400">MySQL 8.0</span>
                    </div>

                    <div class="flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-slate-900/80 border border-slate-800 text-slate-300" title="Storage Engine (MinIO/R2)">
                        <span class="inline-flex rounded-full h-2 w-2 bg-teal-400"></span>
                        <span class="text-slate-400">Storage:</span>
                        <span class="font-medium text-teal-300">MinIO S3</span>
                    </div>

                    <div class="flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-slate-900/80 border border-slate-800 text-slate-300" title="Redis Cache & Tenancy Engine">
                        <span class="inline-flex rounded-full h-2 w-2 bg-cyan-400"></span>
                        <span class="text-slate-400">Resolver:</span>
                        <span class="font-medium text-cyan-300">DB-Per-Tenant</span>
                    </div>
                </div>

                <!-- Primary Action & User Auth Profile -->
                <div class="flex items-center space-x-3">
                    <button type="button" 
                            onclick="openProvisionModal()" 
                            id="btn-open-provision"
                            class="inline-flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 hover:from-emerald-500 hover:via-teal-500 hover:to-cyan-500 shadow-lg shadow-emerald-600/25 hover:shadow-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all duration-200 cursor-pointer active:scale-95">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Provision Clinic</span>
                    </button>

                    @if (auth('landlord')->check())
                        <div class="flex items-center space-x-3 pl-3 border-l border-slate-800">
                            <div class="hidden md:flex flex-col text-right">
                                <span class="text-xs font-semibold text-white leading-tight">
                                    {{ auth('landlord')->user()->name }}
                                </span>
                                <span class="text-[10px] text-emerald-400 uppercase tracking-wider font-semibold">
                                    {{ auth('landlord')->user()->role->label() }}
                                </span>
                            </div>

                            <form action="{{ route('landlord.logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        id="btn-landlord-logout"
                                        title="Sign out of Operations Console"
                                        class="p-2 rounded-xl text-slate-400 hover:text-rose-400 hover:bg-slate-800 border border-slate-800 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <!-- Global Flash Alerts -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6 w-full">
        @if (session('success'))
            <div id="flash-success" class="flex items-start justify-between p-4 mb-4 rounded-xl bg-emerald-950/70 border border-emerald-500/30 text-emerald-200 shadow-lg shadow-emerald-950/50 transition-all">
                <div class="flex items-center space-x-3">
                    <div class="p-1.5 bg-emerald-500/20 rounded-lg text-emerald-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-sm text-emerald-100">Operation Successful</h4>
                        <p class="text-xs text-emerald-300 mt-0.5">{{ session('success') }}</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('flash-success').remove()" class="text-emerald-400 hover:text-emerald-200 p-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        @if ($errors->any())
            <div id="flash-errors" class="flex items-start justify-between p-4 mb-4 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-200 shadow-lg shadow-rose-950/50">
                <div class="flex items-start space-x-3">
                    <div class="p-1.5 bg-rose-500/20 rounded-lg text-rose-400 mt-0.5">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-sm text-rose-100">Validation Errors Occurred</h4>
                        <ul class="text-xs text-rose-300 mt-1 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('flash-errors').remove()" class="text-rose-400 hover:text-rose-200 p-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif
    </div>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/60 py-6 mt-12 bg-slate-950/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 space-y-2 sm:space-y-0">
            <div class="flex items-center space-x-2">
                <span>VetManager Central Platform Engine &copy; {{ date('Y') }}</span>
                <span>&bull;</span>
                <span class="text-emerald-500 font-mono">PHP 8.3 &bull; Laravel 11 &bull; Strict Types</span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-slate-400">Architecture: <span class="text-slate-200">Database-Per-Tenant</span></span>
                <span class="text-slate-600">&bull;</span>
                <a href="/up" target="_blank" class="text-emerald-400 hover:underline">Platform Health</a>
            </div>
        </div>
    </footer>

    @stack('modals')
    @stack('scripts')
</body>
</html>
