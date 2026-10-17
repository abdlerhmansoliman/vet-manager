<!-- Provision Clinic Modal -->
<div id="modal-provision" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity" onclick="closeProvisionModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal Dialog -->
        <div class="inline-block align-bottom bg-slate-900 border border-slate-700/80 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full glass-panel">
            <div class="px-6 pt-6 pb-4 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-2.5 rounded-xl bg-gradient-to-tr from-emerald-500/20 to-teal-500/20 border border-emerald-500/30 text-emerald-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white tracking-tight" id="modal-title">Provision New Clinic</h3>
                        <p class="text-xs text-slate-400">Creates an isolated physical database, runs migrations, and configures owner access.</p>
                    </div>
                </div>
                <button type="button" onclick="closeProvisionModal()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('landlord.tenants.store') }}" method="POST" id="form-provision-tenant" class="p-6 space-y-5">
                @csrf

                <!-- Section: Clinic Identity -->
                <div class="space-y-4">
                    <h4 class="text-xs font-semibold text-emerald-400 uppercase tracking-wider flex items-center space-x-2">
                        <span>1. Clinic Identity & Subdomain</span>
                    </h4>
                    
                    <div>
                        <label for="provision-name" class="block text-xs font-medium text-slate-300 mb-1">Clinic Legal / Brand Name</label>
                        <input type="text" name="name" id="provision-name" required value="{{ old('name') }}"
                               placeholder="e.g. Metropolitan Animal Hospital" 
                               oninput="autoGenerateSlug(this.value)"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
                    </div>

                    <div>
                        <label for="provision-slug" class="block text-xs font-medium text-slate-300 mb-1">Subdomain Slug (Unique Identifier)</label>
                        <div class="flex rounded-xl shadow-sm">
                            <input type="text" name="slug" id="provision-slug" required value="{{ old('slug') }}"
                                   placeholder="e.g. metropolitan-vet" 
                                   class="flex-1 min-w-0 px-3.5 py-2.5 rounded-l-xl bg-slate-950/80 border border-slate-700 text-emerald-400 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
                            <span class="inline-flex items-center px-3 rounded-r-xl border border-l-0 border-slate-700 bg-slate-800/80 text-slate-400 text-xs font-mono">
                                .vetmanager.local
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Dedicated DB Schema will be named: <span id="preview-dbname" class="text-teal-400 font-mono">vet_tenant_[slug]</span></p>
                    </div>
                </div>

                <!-- Section: Plan & Storage -->
                <div class="space-y-4 pt-3 border-t border-slate-800">
                    <h4 class="text-xs font-semibold text-emerald-400 uppercase tracking-wider flex items-center space-x-2">
                        <span>2. Subscription Plan & Storage Quota</span>
                    </h4>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer border border-slate-700 rounded-xl p-3 bg-slate-950/50 hover:border-emerald-500/60 transition flex items-center space-x-3">
                            <input type="radio" name="plan" value="clinic" checked onchange="updateDefaultStorage(20)" class="text-emerald-500 focus:ring-emerald-500 bg-slate-900">
                            <div>
                                <span class="block text-xs font-bold text-white">Clinic (Pro)</span>
                                <span class="block text-[11px] text-slate-400">20 GB Cloud Storage</span>
                            </div>
                        </label>

                        <label class="cursor-pointer border border-slate-700 rounded-xl p-3 bg-slate-950/50 hover:border-emerald-500/60 transition flex items-center space-x-3">
                            <input type="radio" name="plan" value="hospital" onchange="updateDefaultStorage(100)" class="text-emerald-500 focus:ring-emerald-500 bg-slate-900">
                            <div>
                                <span class="block text-xs font-bold text-white">Hospital</span>
                                <span class="block text-[11px] text-slate-400">100 GB Cloud Storage</span>
                            </div>
                        </label>

                        <label class="cursor-pointer border border-slate-700 rounded-xl p-3 bg-slate-950/50 hover:border-emerald-500/60 transition flex items-center space-x-3">
                            <input type="radio" name="plan" value="solo" onchange="updateDefaultStorage(5)" class="text-emerald-500 focus:ring-emerald-500 bg-slate-900">
                            <div>
                                <span class="block text-xs font-bold text-white">Solo Practice</span>
                                <span class="block text-[11px] text-slate-400">5 GB Cloud Storage</span>
                            </div>
                        </label>

                        <label class="cursor-pointer border border-slate-700 rounded-xl p-3 bg-slate-950/50 hover:border-emerald-500/60 transition flex items-center space-x-3">
                            <input type="radio" name="plan" value="enterprise" onchange="updateDefaultStorage(500)" class="text-emerald-500 focus:ring-emerald-500 bg-slate-900">
                            <div>
                                <span class="block text-xs font-bold text-white">Enterprise</span>
                                <span class="block text-[11px] text-slate-400">500 GB Cloud Storage</span>
                            </div>
                        </label>
                    </div>

                    <div>
                        <label for="provision-storage" class="block text-xs font-medium text-slate-300 mb-1">Allocated Storage Quota (GB)</label>
                        <input type="number" name="storage_limit_gb" id="provision-storage" min="1" max="2000" value="20"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/80 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    </div>
                </div>

                <!-- Section: Initial Clinic Owner -->
                <div class="space-y-4 pt-3 border-t border-slate-800">
                    <h4 class="text-xs font-semibold text-emerald-400 uppercase tracking-wider flex items-center space-x-2">
                        <span>3. Initial Clinic Owner / Administrator</span>
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="provision-owner-name" class="block text-xs font-medium text-slate-300 mb-1">Owner Full Name</label>
                            <input type="text" name="owner_name" id="provision-owner-name" required value="{{ old('owner_name') }}"
                                   placeholder="Dr. Eleanor Vance" 
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        </div>
                        <div>
                            <label for="provision-owner-email" class="block text-xs font-medium text-slate-300 mb-1">Owner Email (Discovery Login)</label>
                            <input type="email" name="owner_email" id="provision-owner-email" required value="{{ old('owner_email') }}"
                                   placeholder="director@metrovets.com" 
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/80 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        </div>
                    </div>

                    <div>
                        <label for="provision-owner-password" class="block text-xs font-medium text-slate-300 mb-1">Initial Temporary Password</label>
                        <input type="password" name="owner_password" id="provision-owner-password" required minlength="8"
                               value="SecretClinic123!" 
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/80 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono transition">
                    </div>
                </div>

                <!-- Actions -->
                <div class="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeProvisionModal()" class="px-4 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition">
                        Cancel
                    </button>
                    <button type="submit" id="btn-submit-provision" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-lg shadow-emerald-600/30 transition flex items-center space-x-2">
                        <span>Provision & Migrate Database</span>
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
