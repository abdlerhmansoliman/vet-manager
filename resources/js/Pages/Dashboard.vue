<script setup>
import { Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';
import TriageBadge from '@/Components/TriageBadge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineProps({
    stats: {
        type: Object,
        default: () => ({
            activePatients: 0,
            openCases: 0,
            criticalCases: 0,
            lowStockAlerts: 0,
        }),
    },
    triageCases: {
        type: Array,
        default: () => [],
    },
    lowStockProducts: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <ClinicLayout title="Clinical Dashboard">
        <div class="space-y-6">
            <!-- PAGE TITLE & CLINICAL QUICK ACTIONS -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">Clinical Operations Dashboard</h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">Real-time patient census, emergency triage, and pharmaceutical inventory.</p>
                </div>

                <div class="flex items-center space-x-2 rtl:space-x-reverse">
                    <Link
                        href="/portal/patients/create"
                        class="inline-flex items-center space-x-1.5 rtl:space-x-reverse px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Register Patient</span>
                    </Link>

                    <Link
                        href="/portal/inventory/receive"
                        class="inline-flex items-center space-x-1.5 rtl:space-x-reverse px-3.5 py-2 rounded-xl text-xs font-bold text-stone-700 bg-white hover:bg-stone-50 border border-stone-200 shadow-2xs transition active:scale-95"
                    >
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                        <span>Stock Intake</span>
                    </Link>
                </div>
            </div>

            <!-- KPI METRICS GRID -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 rounded-2xl bg-white border border-stone-200 shadow-2xs">
                    <span class="text-xs font-semibold text-stone-500 uppercase tracking-wider block">Active Patients</span>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl sm:text-3xl font-extrabold text-stone-900">{{ stats.activePatients }}</span>
                        <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md">Admitted</span>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-white border border-stone-200 shadow-2xs">
                    <span class="text-xs font-semibold text-stone-500 uppercase tracking-wider block">Open Cases</span>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl sm:text-3xl font-extrabold text-stone-900">{{ stats.openCases }}</span>
                        <span class="text-[11px] font-bold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md">Encounter</span>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-white border border-rose-200 shadow-2xs" :class="{'ring-1 ring-rose-400': stats.criticalCases > 0}">
                    <span class="text-xs font-semibold text-rose-700 uppercase tracking-wider block">Critical Triage</span>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl sm:text-3xl font-extrabold text-rose-600">{{ stats.criticalCases }}</span>
                        <span class="text-[11px] font-bold text-rose-800 bg-rose-50 px-2 py-0.5 rounded-md animate-pulse">Emergency</span>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-white border border-amber-200 shadow-2xs">
                    <span class="text-xs font-semibold text-amber-700 uppercase tracking-wider block">Low Stock Alerts</span>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl sm:text-3xl font-extrabold text-amber-600">{{ stats.lowStockAlerts }}</span>
                        <span class="text-[11px] font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-md">Pharmacy</span>
                    </div>
                </div>
            </div>

            <!-- TRIAGE BOARD & INPATIENT MONITOR -->
            <div class="p-6 rounded-2xl bg-white border border-stone-200 shadow-2xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                    <div>
                        <h2 class="text-base font-bold text-stone-900">Active Clinical Triage Board</h2>
                        <p class="text-xs text-stone-500">Prioritized by triage severity: Emergency/Critical &rarr; Urgent &rarr; Routine.</p>
                    </div>

                    <Link href="/portal/patients" class="text-xs font-bold text-emerald-700 hover:underline">
                        View All Patients &rarr;
                    </Link>
                </div>

                <div v-if="triageCases.length === 0" class="py-12 text-center text-stone-500 text-xs">
                    No active triage cases at this moment. All patients are currently stabilized.
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="c in triageCases"
                        :key="c.id"
                        class="p-4 rounded-xl border border-stone-200 hover:border-emerald-300 transition flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-stone-50/40"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                <TriageBadge :priority="c.priority" />
                                <span class="text-xs font-bold text-stone-900">{{ c.case_number }}</span>
                                <span class="text-xs text-stone-400">&bull;</span>
                                <span class="text-xs font-semibold text-stone-700">
                                    {{ c.patient ? c.patient.name_or_tag : 'Unknown Patient' }}
                                    <span v-if="c.patient?.species" class="text-stone-400">({{ c.patient.species }})</span>
                                </span>
                            </div>

                            <p class="text-xs text-stone-600 line-clamp-1">
                                <strong class="text-stone-700">Complaint:</strong> {{ c.chief_complaint }}
                            </p>

                            <div class="flex items-center space-x-3 rtl:space-x-reverse text-[11px] text-stone-400 pt-0.5">
                                <span>Vet: <strong class="text-stone-600">{{ c.primary_veterinarian ? c.primary_veterinarian.name : 'Unassigned' }}</strong></span>
                                <span>Owner: <strong class="text-stone-600">{{ c.patient?.client ? c.patient.client.name : '—' }}</strong></span>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2 rtl:space-x-reverse shrink-0">
                            <StatusBadge :status="c.status" />
                            <Link
                                :href="`/portal/cases/${c.id}`"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition"
                            >
                                Open Case (L2) &rarr;
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PHARMACY LOW STOCK ALERTS -->
            <div v-if="lowStockProducts.length > 0" class="p-6 rounded-2xl bg-white border border-amber-200 shadow-2xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                    <div>
                        <h2 class="text-base font-bold text-amber-900 flex items-center space-x-2 rtl:space-x-reverse">
                            <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>Pharmacy Stock Below Reorder Threshold</span>
                        </h2>
                        <p class="text-xs text-stone-500">Batches needing immediate replenishment to prevent clinical dispensing stockouts.</p>
                    </div>

                    <Link href="/portal/inventory/receive" class="text-xs font-bold text-emerald-700 hover:underline">
                        Stock Intake &rarr;
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-start text-xs">
                        <thead class="text-stone-400 font-bold uppercase tracking-wider border-b border-stone-100 text-start">
                            <tr>
                                <th class="py-2 text-start">Product Name</th>
                                <th class="py-2 text-start">SKU</th>
                                <th class="py-2 text-start">Current Stock</th>
                                <th class="py-2 text-start">Threshold</th>
                                <th class="py-2 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="p in lowStockProducts" :key="p.id">
                                <td class="py-2.5 font-bold text-stone-900">{{ p.name }}</td>
                                <td class="py-2.5 text-stone-500">{{ p.sku }}</td>
                                <td class="py-2.5 font-bold text-rose-600">{{ p.current_stock }} {{ p.base_unit }}</td>
                                <td class="py-2.5 text-stone-500">{{ p.reorder_threshold }} {{ p.base_unit }}</td>
                                <td class="py-2.5 text-end">
                                    <Link :href="`/portal/inventory/receive?product_id=${p.id}`" class="text-emerald-700 font-semibold hover:underline">
                                        Receive &rarr;
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </ClinicLayout>
</template>
