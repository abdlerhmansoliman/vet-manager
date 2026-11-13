@extends('layouts.landlord')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <!-- Breadcrumbs -->
    <nav class="flex items-center space-x-2 text-xs text-slate-400">
        <a href="{{ route('landlord.dashboard') }}" class="hover:text-white transition flex items-center space-x-1">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Operations Console</span>
        </a>
        <span>/</span>
        <a href="{{ route('landlord.tenants.show', $tenant) }}" class="hover:text-white transition">{{ $tenant->name }}</a>
        <span>/</span>
        <span class="text-amber-400 font-semibold">Edit Configuration</span>
    </nav>

    <!-- Header Card -->
    <div class="flex items-center space-x-3.5 pb-4 border-b border-slate-800">
        <div class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400">
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
        </div>
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Edit Clinic Configuration</h1>
            <p class="text-xs text-slate-400 mt-0.5">Modify practice display name, adjust subscription tier, or expand storage quota ceiling.</p>
        </div>
    </div>

    <!-- Edit Form Card -->
    <div class="glass-panel rounded-2xl p-6 border border-slate-800">
        <form action="{{ route('landlord.tenants.update', $tenant) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Legal Name -->
            <div>
                <label for="edit-name" class="block text-xs font-medium text-slate-300 mb-1.5">Clinic Legal Name</label>
                <input type="text" name="name" id="edit-name" required value="{{ old('name', $tenant->name) }}"
                       class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
            </div>

            <!-- Readonly Subdomain / Database info -->
            <div class="grid grid-cols-2 gap-3 p-3.5 rounded-xl bg-slate-950/70 border border-slate-800/80 text-xs">
                <div>
                    <span class="text-slate-500 block text-[11px]">Subdomain Identifier:</span>
                    <span class="font-mono text-emerald-400">{{ $tenant->slug }}.vetmanager.local</span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">MySQL Database Schema:</span>
                    <span class="font-mono text-cyan-300">{{ $tenant->database_name }}</span>
                </div>
            </div>

            <!-- Subscription Tier -->
            <div>
                <label for="edit-plan" class="block text-xs font-medium text-slate-300 mb-1.5">Subscription Plan Tier</label>
                <select name="plan" id="edit-plan" class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    @foreach ($availablePlans as $plan)
                        <option value="{{ $plan->value }}" {{ (old('plan', $tenant->plan->value) === $plan->value) ? 'selected' : '' }}>
                            {{ ucfirst($plan->value) }} ({{ round($plan->defaultStorageBytes() / 1073741824) }} GB default cap)
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Storage Quota Ceiling -->
            <div>
                <label for="edit-storage" class="block text-xs font-medium text-slate-300 mb-1.5">Storage Quota Ceiling (GB)</label>
                <input type="number" name="storage_limit_gb" id="edit-storage" min="1" max="2000" required 
                       value="{{ old('storage_limit_gb', round($tenant->storage_limit_bytes / 1073741824)) }}"
                       class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                <p class="text-[11px] text-slate-500 mt-1">Current consumption: {{ $tenant->formattedStorageUsed() }} ({{ $tenant->storageUsagePercentage() }}%)</p>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800/80">
                <a href="{{ route('landlord.tenants.show', $tenant) }}" class="px-5 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-lg shadow-emerald-600/30 transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
