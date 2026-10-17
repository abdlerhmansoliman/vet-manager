<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Sign In &mdash; VetManager Landlord Console</title>
    <meta name="description" content="Central superadmin portal authentication for VetManager SaaS platform.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .glass-panel {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(51, 65, 85, 0.5);
        }
    </style>
</head>
<body class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans selection:bg-emerald-500 selection:text-white bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-slate-900 via-slate-950 to-black">

    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <!-- Brand Logo -->
        <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 via-teal-500 to-cyan-400 p-0.5 shadow-xl shadow-emerald-500/25 mb-4">
            <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center">
                <svg class="w-7 h-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
            </div>
        </div>

        <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
            Vet<span class="text-emerald-400">Manager</span>
        </h2>
        <div class="mt-1 flex items-center justify-center space-x-2">
            <span class="px-2.5 py-0.5 text-xs font-semibold tracking-wider uppercase rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">
                Landlord Operations Console
            </span>
        </div>
        <p class="mt-2 text-xs text-slate-400">
            Platform-wide tenancy orchestration, database instances, and quota monitoring.
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="glass-panel py-8 px-6 shadow-2xl rounded-2xl sm:px-10 border border-slate-800">

            <!-- Flash Success Message -->
            @if (session('success'))
                <div class="mb-5 p-3.5 rounded-xl bg-emerald-950/70 border border-emerald-500/30 text-emerald-200 text-xs flex items-center space-x-2">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Errors Summary -->
            @if ($errors->any())
                <div class="mb-5 p-3.5 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-200 text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <div class="flex items-center space-x-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                            <span>{{ $error }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('landlord.login.store') }}" class="space-y-5" id="form-landlord-login">
                @csrf

                <!-- Email Input -->
                <div>
                    <label for="login-email" class="block text-xs font-medium text-slate-300 mb-1">
                        Superadmin Email
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                        </div>
                        <input type="email" name="email" id="login-email" required autofocus
                               value="{{ old('email', 'admin@vetmanager.io') }}"
                               placeholder="admin@vetmanager.io"
                               class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-slate-950/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
                    </div>
                </div>

                <!-- Password Input -->
                <div>
                    <label for="login-password" class="block text-xs font-medium text-slate-300 mb-1">
                        Console Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input type="password" name="password" id="login-password" required
                               value="Password123!"
                               placeholder="••••••••••••"
                               class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-slate-950/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent font-mono transition">
                    </div>
                </div>

                <!-- Remember Me & Security Notice -->
                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center space-x-2 text-slate-300 cursor-pointer select-none">
                        <input type="checkbox" name="remember" value="1" checked
                               class="rounded bg-slate-950 border-slate-700 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-slate-900">
                        <span>Remember device</span>
                    </label>
                    <span class="text-slate-500 font-mono text-[11px]">Strict Auth Guard</span>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" id="btn-landlord-login-submit"
                            class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 hover:from-emerald-500 hover:via-teal-500 hover:to-cyan-500 shadow-lg shadow-emerald-600/30 hover:shadow-emerald-500/50 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all duration-200 cursor-pointer active:scale-[0.98]">
                        Sign In to Operations Console &rarr;
                    </button>
                </div>
            </form>

            <!-- Default Credentials Notice -->
            <div class="mt-6 pt-5 border-t border-slate-800 text-center">
                <p class="text-[11px] text-slate-500">
                    Default Superadmin: <span class="font-mono text-emerald-400">admin@vetmanager.io</span> &bull; <span class="font-mono text-slate-300">Password123!</span>
                </p>
            </div>
        </div>

        <div class="mt-6 text-center text-xs text-slate-600">
            <span>VetManager Cloud Operations &bull; Port 8080 &bull; Database-Per-Tenant Engine</span>
        </div>
    </div>

</body>
</html>
