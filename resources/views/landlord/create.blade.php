@extends('layouts.landlord')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Breadcrumbs -->
    <nav class="flex items-center space-x-2 text-xs text-slate-400">
        <a href="{{ route('landlord.dashboard') }}" class="hover:text-white transition flex items-center space-x-1">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Operations Console</span>
        </a>
        <span>/</span>
        <span class="text-emerald-400 font-semibold">Provision New Clinic</span>
    </nav>

    <!-- Page Header -->
    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
        <div class="flex items-center space-x-3.5">
            <div class="p-3 rounded-2xl bg-gradient-to-tr from-emerald-600/20 to-teal-500/20 border border-emerald-500/30 text-emerald-400">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Provision Veterinary Clinic</h1>
                <p class="text-xs text-slate-400 mt-0.5">Initialize a brand-new practice with isolated MySQL schema, default species taxonomy, and initial clinic owner.</p>
            </div>
        </div>

        <a href="{{ route('landlord.dashboard') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-900 border border-slate-800 hover:bg-slate-800 transition">
            Back to Dashboard
        </a>
    </div>

    <!-- Provisioning Form -->
    <form action="{{ route('landlord.tenants.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Section 1: Practice Legal Identity & Subdomain -->
        <div class="glass-panel rounded-2xl p-6 border border-slate-800 space-y-4">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span>1. Practice Identity & Domain Isolation</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                <div>
                    <label for="provision-name" class="block text-xs font-medium text-slate-300 mb-1.5">Clinic Legal Name</label>
                    <input type="text" name="name" id="provision-name" required value="{{ old('name') }}"
                           placeholder="e.g. Royal Equine & Falcon Hospital" 
                           class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                </div>

                <div>
                    <label for="provision-slug" class="block text-xs font-medium text-slate-300 mb-1.5">Subdomain (System Identifier)</label>
                    <div class="flex rounded-xl shadow-sm">
                        <input type="text" name="slug" id="provision-slug" required value="{{ old('slug') }}"
                               placeholder="e.g. royalequine" 
                               class="flex-1 min-w-0 px-4 py-2.5 rounded-l-xl bg-slate-950 border border-slate-800 text-emerald-400 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        <span class="inline-flex items-center px-3.5 rounded-r-xl border border-l-0 border-slate-800 bg-slate-900 text-slate-400 text-xs font-mono">
                            .vetmanager.local
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-950/70 border border-slate-800/80 flex items-center justify-between text-xs">
                <span class="text-slate-400">Isolated Physical MySQL Schema Target:</span>
                <span id="preview-dbname" class="font-mono text-cyan-300 font-bold">vet_tenant_[slug]</span>
            </div>
        </div>

        <!-- Section 2: Subscription Plan & Cloud Storage -->
        <div class="glass-panel rounded-2xl p-6 border border-slate-800 space-y-4">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                <span>2. Subscription Plan & Storage Quota</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 pt-1">
                @foreach ($availablePlans as $plan)
                    @php
                        $defaultGb = round($plan->defaultStorageBytes() / 1073741824);
                        $isDefault = $plan->value === 'clinic';
                    @endphp
                    <label class="cursor-pointer border border-slate-800 rounded-xl p-4 bg-slate-950/50 hover:border-emerald-500/60 transition flex flex-col justify-between space-y-3 relative group">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-white uppercase tracking-wider">{{ ucfirst($plan->value) }}</span>
                            <input type="radio" name="plan" value="{{ $plan->value }}" {{ (old('plan', 'clinic') === $plan->value) ? 'checked' : '' }}
                                   onchange="updateDefaultStorage({{ $defaultGb }})" 
                                   class="text-emerald-500 focus:ring-emerald-500 bg-slate-900">
                        </div>
                        <div>
                            <span class="block text-2xl font-extrabold text-white font-mono">{{ $defaultGb }} <span class="text-xs font-normal text-slate-400">GB</span></span>
                            <span class="block text-[11px] text-slate-400 mt-0.5">Cloud Storage Cap</span>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="pt-2">
                <label for="provision-storage" class="block text-xs font-medium text-slate-300 mb-1.5">Custom Storage Ceiling (GB)</label>
                <input type="number" name="storage_limit_gb" id="provision-storage" min="1" max="2000" value="{{ old('storage_limit_gb', 20) }}"
                       class="w-full sm:w-48 px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
            </div>
        </div>

        <!-- Section 3: Initial Clinic Owner -->
        <div class="glass-panel rounded-2xl p-6 border border-slate-800 space-y-4">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                <span>3. Initial Practice Owner & Discovery Authentication</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                <div>
                    <label for="provision-owner-name" class="block text-xs font-medium text-slate-300 mb-1.5">Owner Full Name</label>
                    <input type="text" name="owner_name" id="provision-owner-name" required value="{{ old('owner_name') }}"
                           placeholder="Dr. Eleanor Vance" 
                           class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                </div>

                <div>
                    <label for="provision-owner-email" class="block text-xs font-medium text-slate-300 mb-1.5">Owner Email (Global Discovery Lookup)</label>
                    <input type="email" name="owner_email" id="provision-owner-email" required value="{{ old('owner_email') }}"
                           placeholder="director@clinic.com" 
                           class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                </div>
            </div>

            <div>
                <label for="provision-owner-password" class="block text-xs font-medium text-slate-300 mb-1.5">Temporary Initial Password</label>
                <input type="password" name="owner_password" id="provision-owner-password" required minlength="8"
                       value="SecretClinic123!" 
                       class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                <p class="text-[11px] text-slate-500 mt-1">Owner will be prompted to change credentials upon initial mobile app login.</p>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end space-x-3 pt-2">
            <a href="{{ route('landlord.dashboard') }}" class="px-5 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition">
                Cancel
            </a>
            <button type="submit" id="btn-submit-provision" class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 hover:from-emerald-500 hover:to-cyan-500 shadow-lg shadow-emerald-600/30 transition flex items-center space-x-2">
                <span>Provision Practice & Initialize Database</span>
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const nameInput = document.getElementById('provision-name');
    const slugInput = document.getElementById('provision-slug');
    const previewDb = document.getElementById('preview-dbname');

    if (nameInput && slugInput && previewDb) {
        nameInput.addEventListener('input', function() {
            if (!slugInput.dataset.manual) {
                const slug = this.value.toLowerCase()
                    .replace(/[^a-z0-9]/g, '')
                    .substring(0, 20);
                slugInput.value = slug;
                previewDb.innerText = 'vet_tenant_' + (slug || '[slug]');
            }
        });

        slugInput.addEventListener('input', function() {
            this.dataset.manual = 'true';
            const cleanSlug = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '');
            previewDb.innerText = 'vet_tenant_' + (cleanSlug || '[slug]');
        });
    }

    function updateDefaultStorage(gb) {
        const storageInput = document.getElementById('provision-storage');
        if (storageInput) {
            storageInput.value = gb;
        }
    }
</script>
@endpush
