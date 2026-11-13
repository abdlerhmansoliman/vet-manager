@extends('layouts.landlord')

@section('content')
<div class="space-y-8">

    <!-- Hero Title & Quick Stats Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight flex items-center space-x-3">
                <span>Multi-Clinic Operations Console</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    {{ $metrics['total_clinics'] }} Registered
                </span>
            </h1>
            <p class="text-sm text-slate-400 mt-1 max-w-2xl">
                Superadmin control plane orchestrating isolated MySQL tenant databases, Cloudflare R2 / MinIO storage boundaries, dynamic species taxonomies, and global staff routing discovery.
            </p>
        </div>
        <div class="flex items-center space-x-3">
            <!-- Batch Migrate All Tenant DBs -->
            <form action="{{ route('landlord.tenants.migrate-all') }}" method="POST" class="inline" onsubmit="return confirm('Execute latest database migrations across ALL active tenant databases?');">
                @csrf
                <button type="submit" id="btn-migrate-all" class="inline-flex items-center space-x-2 px-3.5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-slate-200 bg-slate-900/90 hover:bg-slate-800 border border-slate-700 hover:border-cyan-500/50 shadow-md transition cursor-pointer">
                    <svg class="w-4 h-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Migrate All DBs</span>
                </button>
            </form>

            <a href="{{ route('landlord.tenants.create') }}" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 hover:from-emerald-500 hover:to-cyan-500 shadow-lg shadow-emerald-500/20 transition cursor-pointer">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Provision Clinic</span>
            </a>
        </div>
    </div>

    <!-- 5 KPI Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
        
        <!-- Card 1: Total Practice Count -->
        <div class="glass-card rounded-2xl p-5 relative overflow-hidden transition-all duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Clinics</span>
                <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline space-x-2">
                <span class="text-3xl font-extrabold text-white">{{ $metrics['total_clinics'] }}</span>
                <span class="text-xs text-slate-400">Practices</span>
            </div>
            <div class="mt-3 flex items-center space-x-3 text-xs">
                <span class="inline-flex items-center text-emerald-400 font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5"></span>
                    {{ $metrics['active_clinics'] }} Active
                </span>
                <span class="text-slate-600">&bull;</span>
                <span class="inline-flex items-center text-amber-400 font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-1.5"></span>
                    {{ $metrics['suspended_clinics'] }} Suspended
                </span>
            </div>
        </div>

        <!-- Card 2: Global Staff Accounts -->
        <div class="glass-card rounded-2xl p-5 relative overflow-hidden transition-all duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Global Staff</span>
                <div class="p-2.5 rounded-xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline space-x-2">
                <span class="text-3xl font-extrabold text-white">{{ $metrics['total_staff'] }}</span>
                <span class="text-xs text-slate-400">Indexed Users</span>
            </div>
            <div class="mt-3 text-xs text-slate-400 flex items-center space-x-1.5">
                <span class="text-cyan-400 font-mono">tenant_staff_lookup</span>
                <span>discovery index</span>
            </div>
        </div>

        <!-- Card 3: Storage Allocation -->
        <div class="glass-card rounded-2xl p-5 relative overflow-hidden transition-all duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Allocated Storage</span>
                <div class="p-2.5 rounded-xl bg-teal-500/10 text-teal-400 border border-teal-500/20">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 5h16M4 12c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3"/></svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline space-x-2">
                <span class="text-3xl font-extrabold text-white">{{ round($metrics['total_storage_limit_bytes'] / 1073741824) }}</span>
                <span class="text-xs text-slate-400">GB Cloud Cap</span>
            </div>
            <div class="mt-3">
                @php
                    $storagePercent = $metrics['total_storage_limit_bytes'] > 0 
                        ? round(($metrics['total_storage_used_bytes'] / $metrics['total_storage_limit_bytes']) * 100, 1) 
                        : 0;
                @endphp
                <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-gradient-to-r from-teal-400 to-emerald-400 h-1.5 rounded-full" style="width: {{ max($storagePercent, 2) }}%"></div>
                </div>
                <div class="flex justify-between items-center text-[11px] text-slate-400 mt-1.5">
                    <span>{{ round($metrics['total_storage_used_bytes'] / 1048576, 1) }} MB Used</span>
                    <span>{{ $storagePercent }}%</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Clinical Footprint -->
        <div class="glass-card rounded-2xl p-5 relative overflow-hidden transition-all duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Clinical Volume</span>
                <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline space-x-2">
                <span class="text-3xl font-extrabold text-white">{{ $metrics['total_patients'] ?? 0 }}</span>
                <span class="text-xs text-slate-400">Patients</span>
            </div>
            <div class="mt-3 flex items-center space-x-3 text-xs">
                <span class="inline-flex items-center text-cyan-400 font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 mr-1.5"></span>
                    {{ $metrics['total_visits'] ?? 0 }} Visits
                </span>
                <span class="text-slate-600">&bull;</span>
                <span class="inline-flex items-center text-purple-400 font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-400 mr-1.5"></span>
                    {{ $metrics['total_species'] ?? 0 }} Species
                </span>
            </div>
        </div>

        <!-- Card 5: Tier Plan Distribution -->
        <div class="glass-card rounded-2xl p-5 relative overflow-hidden transition-all duration-300">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Subscription Tiers</span>
                <div class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                <div class="p-2 rounded-lg bg-slate-950/60 border border-slate-800 flex items-center justify-between">
                    <span class="text-slate-400">Hospital</span>
                    <span class="font-bold text-indigo-400 font-mono">{{ $metrics['plan_counts']['hospital'] ?? 0 }}</span>
                </div>
                <div class="p-2 rounded-lg bg-slate-950/60 border border-slate-800 flex items-center justify-between">
                    <span class="text-slate-400">Clinic</span>
                    <span class="font-bold text-emerald-400 font-mono">{{ $metrics['plan_counts']['clinic'] ?? 0 }}</span>
                </div>
                <div class="p-2 rounded-lg bg-slate-950/60 border border-slate-800 flex items-center justify-between">
                    <span class="text-slate-400">Solo</span>
                    <span class="font-bold text-cyan-400 font-mono">{{ $metrics['plan_counts']['solo'] ?? 0 }}</span>
                </div>
                <div class="p-2 rounded-lg bg-slate-950/60 border border-slate-800 flex items-center justify-between">
                    <span class="text-slate-400">Enterprise</span>
                    <span class="font-bold text-purple-400 font-mono">{{ $metrics['plan_counts']['enterprise'] ?? 0 }}</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Tenant Registry & Search Section -->
    <div class="glass-panel rounded-2xl overflow-hidden shadow-2xl border border-slate-800">
        
        <!-- Filter Bar -->
        <div class="p-4 sm:p-6 border-b border-slate-800/80 bg-slate-900/60 flex flex-col md:flex-row md:items-center justify-between gap-4">
            
            <!-- Status Navigation Tabs -->
            <div class="flex items-center space-x-1.5 p-1 rounded-xl bg-slate-950/80 border border-slate-800 self-start">
                <a href="{{ route('landlord.dashboard', array_merge(request()->query(), ['status' => 'all'])) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $currentStatus === 'all' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-400 hover:text-white' }}">
                    All Clinics ({{ $metrics['total_clinics'] }})
                </a>
                <a href="{{ route('landlord.dashboard', array_merge(request()->query(), ['status' => 'active'])) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $currentStatus === 'active' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-400 hover:text-white' }}">
                    Active ({{ $metrics['active_clinics'] }})
                </a>
                <a href="{{ route('landlord.dashboard', array_merge(request()->query(), ['status' => 'suspended'])) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $currentStatus === 'suspended' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-400 hover:text-white' }}">
                    Suspended ({{ $metrics['suspended_clinics'] }})
                </a>
                @if (($metrics['provisioning_clinics'] ?? 0) > 0)
                    <a href="{{ route('landlord.dashboard', array_merge(request()->query(), ['status' => 'provisioning'])) }}"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $currentStatus === 'provisioning' ? 'bg-cyan-500 text-white shadow-sm' : 'text-slate-400 hover:text-white' }}">
                        Provisioning ({{ $metrics['provisioning_clinics'] }})
                    </a>
                @endif
                @if (($metrics['failed_clinics'] ?? 0) > 0)
                    <a href="{{ route('landlord.dashboard', array_merge(request()->query(), ['status' => 'failed'])) }}"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $currentStatus === 'failed' ? 'bg-rose-500 text-white shadow-sm' : 'text-slate-400 hover:text-white' }}">
                        Failed ({{ $metrics['failed_clinics'] }})
                    </a>
                @endif
            </div>

            <!-- Search & Plan Filter Form -->
            <form method="GET" action="{{ route('landlord.dashboard') }}" class="flex items-center space-x-3 w-full md:w-auto">
                <input type="hidden" name="status" value="{{ $currentStatus }}">

                <!-- Plan Select -->
                <select name="plan" onchange="this.form.submit()"
                        class="px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="all" {{ $currentPlan === 'all' ? 'selected' : '' }}>All Plans</option>
                    @foreach ($availablePlans as $plan)
                        <option value="{{ $plan->value }}" {{ $currentPlan === $plan->value ? 'selected' : '' }}>
                            {{ ucfirst($plan->value) }}
                        </option>
                    @endforeach
                </select>

                <!-- Search Input -->
                <div class="relative flex-1 md:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="search" value="{{ $searchQuery }}" placeholder="Search clinics, slug, DB..."
                           class="w-full pl-9 pr-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @if ($searchQuery)
                        <a href="{{ route('landlord.dashboard', ['status' => $currentStatus, 'plan' => $currentPlan]) }}" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-white">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                </div>

                <button type="submit" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-xl transition">
                    Filter
                </button>
            </form>
        </div>

        <!-- Clinics Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-950/40 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Clinic & Domain</th>
                        <th class="py-3.5 px-6">Isolated Database</th>
                        <th class="py-3.5 px-6">Tier Plan</th>
                        <th class="py-3.5 px-6">Storage Quota</th>
                        <th class="py-3.5 px-6 text-center">Staff Members</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-sm">
                    @forelse ($tenants as $tenant)
                        <tr class="hover:bg-slate-900/40 transition-colors group">
                            <!-- Clinic Name & Subdomain -->
                            <td class="py-4 px-6">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-slate-800 to-slate-700 border border-slate-700 flex items-center justify-center font-bold text-emerald-400 text-sm shadow">
                                        {{ strtoupper(substr($tenant->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-white group-hover:text-emerald-300 transition-colors flex items-center space-x-1.5">
                                            <a href="{{ route('landlord.tenants.show', $tenant) }}" class="hover:underline">
                                                {{ $tenant->name }}
                                            </a>
                                        </div>
                                        <div class="text-xs text-slate-400 font-mono flex items-center space-x-1 mt-0.5">
                                            <span>{{ $tenant->slug }}.vetmanager.local</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Database Name -->
                            <td class="py-4 px-6">
                                <div class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800/80 font-mono text-xs text-cyan-300">
                                    <svg class="w-3.5 h-3.5 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 5h16"/></svg>
                                    <span>{{ $tenant->database_name }}</span>
                                </div>
                            </td>

                            <!-- Tier Plan -->
                            <td class="py-4 px-6">
                                @php
                                    $planStyles = match($tenant->plan->value) {
                                        'hospital' => 'bg-indigo-500/10 text-indigo-300 border-indigo-500/30',
                                        'clinic' => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30',
                                        'solo' => 'bg-cyan-500/10 text-cyan-300 border-cyan-500/30',
                                        'enterprise' => 'bg-purple-500/10 text-purple-300 border-purple-500/30',
                                        default => 'bg-slate-500/10 text-slate-300 border-slate-500/30',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider border {{ $planStyles }}">
                                    {{ $tenant->plan->value }}
                                </span>
                            </td>

                            <!-- Storage Quota -->
                            <td class="py-4 px-6 min-w-[160px]">
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-300 font-mono">{{ $tenant->formattedStorageUsed() }}</span>
                                        <span class="text-slate-500 font-mono">/ {{ $tenant->formattedStorageLimit() }}</span>
                                    </div>
                                    <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-1.5 rounded-full" style="width: {{ max($tenant->storageUsagePercentage(), 2) }}%"></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Staff Members Count -->
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-xs font-medium text-slate-300" title="{{ $tenant->staff_lookups_count }} accounts registered">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span>{{ $tenant->staff_lookups_count }}</span>
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="py-4 px-6">
                                @if ($tenant->isActive())
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse mr-1.5"></span>
                                        Active
                                    </span>
                                @elseif ($tenant->isProvisioning())
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-spin mr-1.5"></span>
                                        Provisioning
                                    </span>
                                @elseif ($tenant->isFailed())
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        Failed
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-1.5"></span>
                                        Suspended
                                    </span>
                                @endif
                            </td>

                            <!-- Actions Toolbar -->
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    
                                    <!-- Inspect Details Page Link -->
                                    <a href="{{ route('landlord.tenants.show', $tenant) }}" 
                                       class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition" 
                                       title="Inspect Clinic & Clinical Records">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>

                                    <!-- Edit Configuration Page Link -->
                                    <a href="{{ route('landlord.tenants.edit', $tenant) }}" 
                                       class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition" 
                                       title="Edit Plan / Storage Ceiling">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>

                                    <!-- Migrate DB -->
                                    <form action="{{ route('landlord.tenants.migrate', $tenant) }}" method="POST" class="inline" onsubmit="return confirm('Execute latest migrations on {{ $tenant->database_name }}?');">
                                        @csrf
                                        <button type="submit" class="p-2 text-slate-400 hover:text-cyan-400 hover:bg-slate-800 rounded-lg transition" title="Run Migrations on Tenant DB">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        </button>
                                    </form>

                                    <!-- Toggle Status (Suspend / Activate) -->
                                    <form action="{{ route('landlord.tenants.toggle-status', $tenant) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        @if ($tenant->isActive())
                                            <button type="submit" 
                                                    class="p-2 text-slate-400 hover:text-amber-400 hover:bg-slate-800 rounded-lg transition" 
                                                    title="Suspend Clinic"
                                                    onclick="return confirm('Suspend {{ $tenant->name }}? Clinic users will be locked out immediately.');">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                        @else
                                            <button type="submit" 
                                                    class="p-2 text-slate-400 hover:text-emerald-400 hover:bg-slate-800 rounded-lg transition" 
                                                    title="Activate Clinic">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                        @endif
                                    </form>

                                    <!-- Delete / Purge -->
                                    <form action="{{ route('landlord.tenants.destroy', $tenant) }}" method="POST" class="inline" onsubmit="return confirm('WARNING: Are you absolutely sure you want to permanently delete \'{{ $tenant->name }}\' and DROP the database \'{{ $tenant->database_name }}\'? This action is irreversible!');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-slate-500 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition" title="Drop Database & Purge Clinic">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 px-6 text-center">
                                <div class="max-w-sm mx-auto space-y-3">
                                    <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-500">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    </div>
                                    <h4 class="text-base font-bold text-white">No clinics match current criteria</h4>
                                    <p class="text-xs text-slate-400">Try adjusting your search terms or filters, or provision a new clinical practice.</p>
                                    <a href="{{ route('landlord.tenants.create') }}" class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-400 hover:text-emerald-300 bg-emerald-500/10 border border-emerald-500/20">
                                        <span>+ Provision Clinic</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($tenants->hasPages())
            <div class="p-4 sm:p-6 border-t border-slate-800/80 bg-slate-900/40">
                {{ $tenants->links() }}
            </div>
        @endif

    </div>

</div>
@endsection
