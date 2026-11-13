@extends('layouts.landlord')

@section('content')
<div class="space-y-6">

    <!-- Breadcrumbs -->
    <nav class="flex items-center space-x-2 text-xs text-slate-400">
        <a href="{{ route('landlord.dashboard') }}" class="hover:text-white transition flex items-center space-x-1">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Operations Console</span>
        </a>
        <span>/</span>
        <span class="text-slate-300 font-medium">Clinics</span>
        <span>/</span>
        <span class="text-cyan-400 font-semibold">{{ $tenant->name }}</span>
    </nav>

    <!-- Clinic Header Card -->
    <div class="glass-panel rounded-2xl p-6 border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-slate-800 to-slate-700 border border-slate-700 flex items-center justify-center font-black text-2xl text-emerald-400 shadow-xl">
                {{ strtoupper(substr($tenant->name, 0, 1)) }}
            </div>
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl font-extrabold text-white tracking-tight">{{ $tenant->name }}</h1>
                    
                    @if ($tenant->isActive())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse mr-1.5"></span>
                            Active
                        </span>
                    @elseif ($tenant->isProvisioning())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-spin mr-1.5"></span>
                            Provisioning
                        </span>
                    @elseif ($tenant->isFailed())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                            Failed
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-1.5"></span>
                            Suspended
                        </span>
                    @endif

                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-slate-800 text-slate-300 border border-slate-700">
                        {{ $tenant->plan->value }} Plan
                    </span>
                </div>
                <p class="text-xs text-slate-400 font-mono mt-1 flex items-center space-x-2">
                    <span>Domain: <span class="text-emerald-400">{{ $tenant->slug }}.vetmanager.local</span></span>
                    <span>&bull;</span>
                    <span>UUID: <span class="text-slate-300 select-all">{{ $tenant->id }}</span></span>
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center space-x-2.5 self-start md:self-auto">
            <!-- Edit -->
            <a href="{{ route('landlord.tenants.edit', $tenant) }}" 
               class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-200 bg-slate-800 hover:bg-slate-700 border border-slate-700 transition">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span>Edit Configuration</span>
            </a>

            <!-- Migrate -->
            <form action="{{ route('landlord.tenants.migrate', $tenant) }}" method="POST" class="inline" onsubmit="return confirm('Execute latest migrations on {{ $tenant->database_name }}?');">
                @csrf
                <button type="submit" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-cyan-300 bg-cyan-950/60 hover:bg-cyan-900/60 border border-cyan-800/60 transition">
                    <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Run DB Migration</span>
                </button>
            </form>

            <!-- Toggle Status -->
            <form action="{{ route('landlord.tenants.toggle-status', $tenant) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                @if ($tenant->isActive())
                    <button type="submit" 
                            class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-amber-300 bg-amber-950/60 hover:bg-amber-900/60 border border-amber-800/60 transition"
                            onclick="return confirm('Suspend {{ $tenant->name }}? Users will be locked out immediately.');">
                        <svg class="w-3.5 h-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Suspend</span>
                    </button>
                @else
                    <button type="submit" 
                            class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-emerald-300 bg-emerald-950/60 hover:bg-emerald-900/60 border border-emerald-800/60 transition">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Activate</span>
                    </button>
                @endif
            </form>

            <!-- Delete -->
            <form action="{{ route('landlord.tenants.destroy', $tenant) }}" method="POST" class="inline" onsubmit="return confirm('CRITICAL: Permanently drop database \'{{ $tenant->database_name }}\' and purge clinic? This cannot be undone!');">
                @csrf
                @method('DELETE')
                <button type="submit" class="p-2 text-slate-500 hover:text-rose-400 hover:bg-slate-800 rounded-xl transition" title="Purge Clinic">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Section 1: Clinical Records & Dynamic Species Taxonomy -->
    <div class="glass-panel rounded-2xl p-6 border border-slate-800 space-y-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800/80">
            <div class="flex items-center space-x-2.5">
                <div class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-white tracking-tight">Clinical Records &amp; Species Taxonomy</h2>
                    <p class="text-xs text-slate-400">Live clinical telemetry queried from isolated physical MySQL schema.</p>
                </div>
            </div>

            @if ($clinicalMetrics['database_connected'])
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse mr-1.5"></span>
                    Isolated Schema Online
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                    Schema Offline / Unreachable
                </span>
            @endif
        </div>

        <!-- 5 Clinical & Diagnostic Media KPI Metric Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3.5">
            <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1">
                <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Registered Patients</span>
                <span class="text-3xl font-extrabold text-white font-mono">{{ $clinicalMetrics['patients_count'] }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1">
                <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Medical Cases</span>
                <span class="text-3xl font-extrabold text-emerald-400 font-mono">{{ $clinicalMetrics['cases_count'] }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1">
                <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Clinical Visits</span>
                <span class="text-3xl font-extrabold text-cyan-400 font-mono">{{ $clinicalMetrics['visits_count'] }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1">
                <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Diagnostic Media</span>
                <span class="text-3xl font-extrabold text-teal-400 font-mono">{{ $clinicalMetrics['images_count'] ?? 0 }} <span class="text-xs font-normal text-slate-400">files</span></span>
            </div>
            <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1">
                <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Configured Species</span>
                <span class="text-3xl font-extrabold text-purple-400 font-mono">{{ $clinicalMetrics['species_count'] }}</span>
            </div>
        </div>

        <!-- Dynamic Species Taxonomy Badges -->
        <div class="space-y-2 pt-2">
            <span class="text-xs font-semibold uppercase text-slate-400 tracking-wider block">
                Configured Species Taxonomy ({{ count($clinicalMetrics['species_list']) }} Species):
            </span>
            <div class="flex flex-wrap gap-2 pt-1">
                @forelse ($clinicalMetrics['species_list'] as $species)
                    @php $isDefault = $species['is_system_default']; @endphp
                    <span class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-xl text-xs font-medium {{ $isDefault ? 'bg-slate-900 border border-slate-700 text-slate-200' : 'bg-purple-950/50 border border-purple-500/40 text-purple-200' }}">
                        <span>{{ $species['name'] }}</span>
                        <span class="text-[9px] uppercase font-bold px-1.5 py-0.5 rounded {{ $isDefault ? 'bg-slate-800 text-slate-400' : 'bg-purple-500/30 text-purple-300' }}">
                            {{ $isDefault ? 'Default' : 'Custom' }}
                        </span>
                    </span>
                @empty
                    <span class="text-xs text-slate-500 italic">No species records configured in database.</span>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Section 2: Infrastructure, Database & Storage Breakdown -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Isolated Physical MySQL Database Architecture -->
        <div class="glass-panel rounded-2xl p-6 border border-slate-800 space-y-4">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 5h16M4 12c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3"/></svg>
                <span>Database Infrastructure</span>
            </h2>

            <div class="grid grid-cols-2 gap-3 text-xs pt-1">
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-slate-500 block text-[11px]">Database Schema:</span>
                    <span class="font-mono text-emerald-300 font-bold">{{ $tenant->database_name }}</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-slate-500 block text-[11px]">Engine / Driver:</span>
                    <span class="text-slate-300 font-mono">MySQL 8.0 (InnoDB)</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-slate-500 block text-[11px]">Provisioned At:</span>
                    <span class="text-slate-300 font-mono">{{ $tenant->created_at?->format('M d, Y H:i UTC') }}</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-slate-500 block text-[11px]">Tenancy Header:</span>
                    <span class="text-cyan-400 font-mono">X-Tenant-ID: {{ $tenant->slug }}</span>
                </div>
            </div>
        </div>

        <!-- Storage Quota Breakdown -->
        <div class="glass-panel rounded-2xl p-6 border border-slate-800 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center space-x-2">
                    <svg class="w-4 h-4 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 5h16"/></svg>
                    <span>Storage Quota (MinIO / Cloudflare R2)</span>
                </h2>
                <span class="text-xs text-slate-400 font-mono">{{ $clinicalMetrics['images_count'] ?? 0 }} Diagnostic Files</span>
            </div>

            <div class="space-y-3 pt-1">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400">Consumed:</span>
                    <span class="font-mono text-white font-bold">{{ $tenant->formattedStorageUsed() }} / {{ $tenant->formattedStorageLimit() }} ({{ $tenant->storageUsagePercentage() }}%)</span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-3 overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-3 rounded-full transition-all duration-500" style="width: {{ max($tenant->storageUsagePercentage(), 2) }}%"></div>
                </div>
                <p class="text-[11px] text-slate-500">Storage is isolated per-tenant with encrypted MinIO S3 presigned URL dispatchers.</p>
            </div>
        </div>

    </div>

    <!-- Section 3: Registered Staff Directory -->
    <div class="glass-panel rounded-2xl p-6 border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center space-x-2">
                <svg class="w-4 h-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Global Staff Directory ({{ $tenant->staffLookups->count() }} Registered Accounts)</span>
            </h2>
            <span class="text-xs text-slate-500 font-mono">tenant_staff_lookup</span>
        </div>

        <div class="flex flex-wrap gap-2 pt-1">
            @forelse ($tenant->staffLookups as $staff)
                <span class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-slate-300">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>{{ $staff->email }}</span>
                </span>
            @empty
                <span class="text-xs text-slate-500 italic">No staff accounts registered in central discovery lookup.</span>
            @endforelse
        </div>
    </div>

</div>
@endsection
