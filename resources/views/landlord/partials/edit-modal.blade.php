<!-- Edit Clinic Plan & Quota Modal -->
<div id="modal-edit" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="edit-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity" onclick="closeEditModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-slate-900 border border-slate-700/80 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full glass-panel">
            <div class="px-6 pt-6 pb-4 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-2 rounded-xl bg-amber-500/20 border border-amber-500/30 text-amber-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white tracking-tight" id="edit-title">Edit Clinic Configuration</h3>
                        <p class="text-xs text-slate-400">Modify legal name, subscription tier, or adjust storage ceiling.</p>
                    </div>
                </div>
                <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="form-edit-tenant" method="POST" action="" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="edit-name" class="block text-xs font-medium text-slate-300 mb-1">Clinic Legal Name</label>
                    <input type="text" name="name" id="edit-name" required
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/80 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                </div>

                <div>
                    <label for="edit-plan" class="block text-xs font-medium text-slate-300 mb-1">Subscription Tier</label>
                    <select name="plan" id="edit-plan" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/80 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        @foreach ($availablePlans as $plan)
                            <option value="{{ $plan->value }}">{{ ucfirst($plan->value) }} ({{ round($plan->defaultStorageBytes() / 1073741824) }} GB default)</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="edit-storage" class="block text-xs font-medium text-slate-300 mb-1">Storage Quota Ceiling (GB)</label>
                    <input type="number" name="storage_limit_gb" id="edit-storage" min="1" max="2000" required
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/80 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                </div>

                <div class="pt-4 border-t border-slate-800 flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-lg shadow-emerald-600/30 transition">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
