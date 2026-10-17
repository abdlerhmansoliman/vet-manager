<!-- Inspect Tenant Details Modal -->
<div id="modal-inspect" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="inspect-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity" onclick="closeInspectModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-slate-900 border border-slate-700/80 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full glass-panel">
            <div class="px-6 pt-6 pb-4 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-2.5 rounded-xl bg-cyan-500/20 border border-cyan-500/30 text-cyan-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white tracking-tight" id="inspect-title">Clinic Infrastructure Inspector</h3>
                        <p class="text-xs text-slate-400">Isolated database connection parameters, storage utilization, and staff discovery lookup.</p>
                    </div>
                </div>
                <button type="button" onclick="closeInspectModal()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6 space-y-6" id="inspect-content">
                <!-- Loading State -->
                <div id="inspect-loading" class="py-12 flex flex-col items-center justify-center space-y-3">
                    <div class="w-8 h-8 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-xs text-slate-400">Querying central landlord registry...</p>
                </div>

                <!-- Data Container -->
                <div id="inspect-data" class="hidden space-y-5">
                    <!-- Top Summary Card -->
                    <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 flex items-center justify-between">
                        <div>
                            <div class="flex items-center space-x-2">
                                <h4 class="text-base font-bold text-white" id="inspect-name"></h4>
                                <span id="inspect-status-badge" class="px-2 py-0.5 text-[10px] font-semibold uppercase rounded-full"></span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Subdomain: <span class="text-emerald-400 font-mono" id="inspect-slug"></span> &bull; Plan: <span class="text-cyan-300 font-semibold uppercase" id="inspect-plan"></span>
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-[11px] text-slate-500 block">Tenant UUID</span>
                            <span class="text-xs font-mono text-slate-300 select-all" id="inspect-uuid"></span>
                        </div>
                    </div>

                    <!-- Database Architecture -->
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-2">
                        <h5 class="text-xs font-semibold uppercase text-slate-400 tracking-wider flex items-center space-x-1.5">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 5h16M4 12c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3"/></svg>
                            <span>Isolated Physical Database</span>
                        </h5>
                        <div class="grid grid-cols-2 gap-3 text-xs pt-1">
                            <div>
                                <span class="text-slate-500 block text-[11px]">Database Schema Name:</span>
                                <span class="font-mono text-emerald-300" id="inspect-dbname"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">Connection Driver:</span>
                                <span class="text-slate-300 font-mono">MySQL 8.0 (InnoDB Dynamic)</span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">Provisioned Date:</span>
                                <span class="text-slate-300 font-mono" id="inspect-created-at"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">Routing Header:</span>
                                <span class="text-slate-300 font-mono">X-Tenant-ID: <span id="inspect-header-hint" class="text-cyan-400"></span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Storage Quota Breakdown -->
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-xs font-semibold uppercase text-slate-400 tracking-wider">Cloudflare R2 / MinIO Storage Quota</span>
                            <span class="font-mono text-slate-300" id="inspect-storage-label"></span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-2.5 overflow-hidden">
                            <div id="inspect-storage-bar" class="bg-gradient-to-r from-emerald-500 to-teal-400 h-2.5 rounded-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Registered Staff Directory -->
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-2">
                        <div class="flex items-center justify-between">
                            <h5 class="text-xs font-semibold uppercase text-slate-400 tracking-wider">
                                Global Staff Index (<span id="inspect-staff-count" class="text-emerald-400"></span> Users)
                            </h5>
                            <span class="text-[11px] text-slate-500">tenant_staff_lookup</span>
                        </div>
                        <div id="inspect-staff-list" class="flex flex-wrap gap-2 pt-1 max-h-40 overflow-y-auto">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-slate-800 flex items-center justify-end space-x-3 bg-slate-950/40">
                <button type="button" onclick="closeInspectModal()" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
