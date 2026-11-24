<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';
import TriageBadge from '@/Components/TriageBadge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
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

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const currentTenant = computed(() => page.props.currentTenant || page.props.tenant);

// 1. Live Time Formatting
const currentTime = ref('');
const currentHour = ref(new Date().getHours());
const updateTime = () => {
    try {
        const locale = page.props.locale || 'en';
        const now = new Date();
        currentHour.value = now.getHours();
        currentTime.value = now.toLocaleTimeString(locale === 'ar' ? 'ar-EG' : 'en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true,
        });
    } catch {
        currentTime.value = '';
    }
};

let timeInterval = null;
onMounted(() => {
    updateTime();
    timeInterval = setInterval(updateTime, 30000);
});

onUnmounted(() => {
    if (timeInterval) clearInterval(timeInterval);
});

// 2. Greeting Computation
const greetingText = computed(() => {
    const hour = currentHour.value;
    const key = hour < 12 ? 'Good morning, :name' : hour < 18 ? 'Good afternoon, :name' : 'Good evening, :name';
    return trans(key, { name: authUser.value?.name || trans('Doctor') });
});

// 3. Triage Priority Filter
const activeTriageFilter = ref('all');

const emergencyCount = computed(() => props.triageCases.filter(c => c.priority === 'emergency_critical').length);
const urgentCount = computed(() => props.triageCases.filter(c => c.priority === 'urgent').length);
const routineCount = computed(() => props.triageCases.filter(c => c.priority === 'routine').length);

const filteredTriageCases = computed(() => {
    if (activeTriageFilter.value === 'emergency') {
        return props.triageCases.filter(c => c.priority === 'emergency_critical');
    }
    if (activeTriageFilter.value === 'urgent') {
        return props.triageCases.filter(c => c.priority === 'urgent');
    }
    if (activeTriageFilter.value === 'routine') {
        return props.triageCases.filter(c => c.priority === 'routine');
    }
    return props.triageCases;
});

// 4. Pharmacy Formulary Search
const pharmacySearch = ref('');
const filteredLowStock = computed(() => {
    if (!pharmacySearch.value || !pharmacySearch.value.trim()) return props.lowStockProducts;
    const q = pharmacySearch.value.trim().toLowerCase();
    return props.lowStockProducts.filter(p =>
        (p.name ?? '').toLowerCase().includes(q) ||
        ((p.sku ?? '').toLowerCase().includes(q))
    );
});
</script>

<template>
    <ClinicLayout :title="$t('Clinical Command Dashboard')" :full-width="true">
        <div class="space-y-6 text-[#1b1c1a]">
            <!-- 1. TOP HEADER & CLINIC AT-A-GLANCE BANNER -->
            <section class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-stone-200/90 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)]">
                <div class="space-y-1.5 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-[#a6f2d1] text-[#002116] text-[11px] font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#17684e] animate-pulse"></span>
                            <span>{{ $t('Live Clinic Ingress Active') }}</span>
                        </span>

                        <span class="text-stone-300">•</span>

                        <span v-if="currentTime" class="text-stone-600 text-xs font-semibold flex items-center gap-1.5 font-mono">
                            <svg class="w-3.5 h-3.5 shrink-0 text-[#00685f]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ currentTime }}</span>
                        </span>

                        <span class="text-stone-300">•</span>

                        <span class="text-stone-600 text-xs font-medium flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0 text-[#17684e]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span class="truncate">{{ currentTenant?.name || $t('VetManager Medical Center') }}</span>
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-black text-stone-900 tracking-tight">
                        {{ greetingText }}
                    </h1>

                    <p class="text-xs sm:text-sm text-stone-500">
                        {{ $t('Real-time clinical patient census, emergency triage queue, and pharmacy stock monitoring.') }}
                    </p>
                </div>

                <!-- Right Quick CTAs -->
                <div class="flex flex-wrap items-center gap-2.5 pt-2 lg:pt-0 shrink-0">
                    <Link
                        href="/portal/patients/create"
                        class="inline-flex items-center gap-1.5 h-10 px-4 rounded-xl bg-white text-[#00685f] hover:bg-stone-50 border border-stone-200 shadow-xs text-xs font-bold transition active:scale-95"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <span>{{ $t('+ Register Patient') }}</span>
                    </Link>

                    <Link
                        href="/portal/inventory/receive"
                        class="inline-flex items-center gap-1.5 h-10 px-4 rounded-xl bg-white text-stone-700 hover:bg-stone-50 border border-stone-200 shadow-xs text-xs font-bold transition active:scale-95"
                    >
                        <svg class="w-4 h-4 shrink-0 text-[#00685f]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span>{{ $t('Stock Intake') }}</span>
                    </Link>

                    <Link
                        href="/portal/cases/create"
                        class="inline-flex items-center gap-1.5 h-10 px-4 rounded-xl bg-[#00685f] text-white hover:bg-[#005049] shadow-sm text-xs font-bold transition active:scale-95"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>{{ $t('+ Open Case') }}</span>
                    </Link>
                </div>
            </section>

            <!-- 2. 4 HOSPITAL AT-A-GLANCE KPI CARDS -->
            <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <!-- KPI 1: Active Patients -->
                <div class="p-5 rounded-2xl bg-white border border-stone-200/90 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)] flex flex-col justify-between gap-3 overflow-hidden">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <span class="text-[11px] font-bold text-stone-500 uppercase tracking-wider block truncate">
                                {{ $t('Admitted Patients') }}
                            </span>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span class="text-3xl font-black text-stone-900 font-mono">
                                    {{ stats.activePatients }}
                                </span>
                                <span class="text-xs text-stone-500 font-medium">
                                    {{ $t('Registered') }}
                                </span>
                            </div>
                        </div>

                        <div class="w-11 h-11 shrink-0 rounded-xl bg-[#00685f]/10 text-[#00685f] flex items-center justify-center overflow-hidden">
                            <svg class="w-6 h-6 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <circle cx="4.5" cy="9.5" r="2.5"/><circle cx="9" cy="5.5" r="2.5"/><circle cx="15" cy="5.5" r="2.5"/><circle cx="19.5" cy="9.5" r="2.5"/>
                                <path d="M17.34 14.86c-.87-1.02-1.6-1.89-2.48-2.49-.64-.44-1.52-.87-2.86-.87s-2.22.43-2.86.87c-.88.6-1.61 1.47-2.48 2.49-1.25 1.47-2.16 3.19-1.16 4.96.9 1.6 2.82 1.68 4.24 1.68.79 0 1.54-.04 2.26-.04.72 0 1.47.04 2.26.04 1.42 0 3.34-.08 4.24-1.68 1-1.77.09-3.49-1.16-4.96z"/>
                            </svg>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-stone-100 flex items-center justify-between text-xs">
                        <span class="text-stone-500 font-medium">{{ $t('Patient Census') }}</span>
                        <Link href="/portal/patients" class="text-xs font-bold text-[#00685f] hover:underline inline-flex items-center gap-1">
                            <span>{{ $t('Registry') }}</span>
                            <svg class="w-3.5 h-3.5 shrink-0 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </div>

                <!-- KPI 2: Open Cases -->
                <div class="p-5 rounded-2xl bg-white border border-stone-200/90 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)] flex flex-col justify-between gap-3 overflow-hidden">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <span class="text-[11px] font-bold text-stone-500 uppercase tracking-wider block truncate">
                                {{ $t('Active Cases (L2)') }}
                            </span>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span class="text-3xl font-black text-stone-900 font-mono">
                                    {{ stats.openCases }}
                                </span>
                                <span class="text-xs text-teal-700 font-bold bg-teal-50 px-2 py-0.5 rounded-md border border-teal-200">
                                    {{ $t('Under Care') }}
                                </span>
                            </div>
                        </div>

                        <div class="w-11 h-11 shrink-0 rounded-xl bg-[#17684e]/10 text-[#17684e] flex items-center justify-center overflow-hidden">
                            <svg class="w-6 h-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zM9 16l2 2 4-4" />
                            </svg>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-stone-100 flex items-center justify-between text-xs">
                        <span class="text-stone-500 font-medium">{{ $t('Episodes Under Treatment') }}</span>
                        <span class="text-xs font-bold text-[#17684e]">{{ $t('Active Inpatients') }}</span>
                    </div>
                </div>

                <!-- KPI 3: Critical Triage (Warm Coral Highlight) -->
                <div
                    class="p-5 rounded-2xl border shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)] flex flex-col justify-between gap-3 transition overflow-hidden"
                    :class="stats.criticalCases > 0 ? 'bg-rose-50/70 border-rose-300 ring-2 ring-rose-400/20' : 'bg-white border-stone-200/90'"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider block truncate"
                                :class="stats.criticalCases > 0 ? 'text-rose-800' : 'text-stone-500'"
                            >
                                {{ $t('Critical Emergency Triage') }}
                            </span>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span
                                    class="text-3xl font-black font-mono"
                                    :class="stats.criticalCases > 0 ? 'text-rose-700' : 'text-stone-900'"
                                >
                                    {{ stats.criticalCases }}
                                </span>
                                <span
                                    v-if="stats.criticalCases > 0"
                                    class="text-xs text-rose-800 font-bold bg-rose-100 px-2 py-0.5 rounded-md animate-pulse border border-rose-300"
                                >
                                    {{ $t('Immediate Attention') }}
                                </span>
                                <span v-else class="text-xs text-stone-500 font-medium">{{ $t('None') }}</span>
                            </div>
                        </div>

                        <div
                            class="w-11 h-11 shrink-0 rounded-xl flex items-center justify-center shadow-xs overflow-hidden"
                            :class="stats.criticalCases > 0 ? 'bg-rose-600 text-white' : 'bg-stone-100 text-stone-500'"
                        >
                            <svg class="w-6 h-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-stone-100 flex items-center justify-between text-xs">
                        <span class="text-stone-500 font-medium">{{ $t('Priority 1 Patients') }}</span>
                        <span
                            class="font-bold text-xs"
                            :class="stats.criticalCases > 0 ? 'text-rose-700' : 'text-stone-600'"
                        >
                            {{ stats.criticalCases > 0 ? $t('STAT Action Required') : $t('Stabilized') }}
                        </span>
                    </div>
                </div>

                <!-- KPI 4: Low Stock Alerts -->
                <div class="p-5 rounded-2xl bg-white border border-stone-200/90 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)] flex flex-col justify-between gap-3 overflow-hidden">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider block truncate">
                                {{ $t('Pharmacy Low Stock') }}
                            </span>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span class="text-3xl font-black text-amber-700 font-mono">
                                    {{ stats.lowStockAlerts }}
                                </span>
                                <span class="text-xs text-amber-900 font-bold bg-amber-100 px-2 py-0.5 rounded-md border border-amber-300">
                                    {{ $t('Below Threshold') }}
                                </span>
                            </div>
                        </div>

                        <div class="w-11 h-11 shrink-0 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center overflow-hidden">
                            <svg class="w-6 h-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-stone-100 flex items-center justify-between text-xs">
                        <span class="text-stone-500 font-medium">{{ $t('Inventory Reorders') }}</span>
                        <Link href="/portal/inventory/receive" class="text-xs font-bold text-amber-800 hover:underline inline-flex items-center gap-1">
                            <span>{{ $t('Restock') }}</span>
                            <svg class="w-3 h-3 shrink-0 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- 3. MAIN 2-COLUMN COCKPIT (8 cols Left / 4 cols Right) -->
            <section class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- ========================================================
                     LEFT MAIN COLUMN (8 cols): Triage Board & Active Encounters
                     ======================================================== -->
                <div class="lg:col-span-8 flex flex-col gap-6 min-w-0">
                    <!-- SECTION A: LIVE PATIENT TRIAGE & ACTIVE CASES -->
                    <div class="p-6 rounded-2xl bg-white border border-stone-200/90 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)] space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-stone-100">
                            <div class="flex items-center gap-2">
                                <div class="w-2.5 h-2.5 rounded-full bg-[#00685f] animate-pulse shrink-0"></div>
                                <h2 class="text-base font-extrabold text-stone-900">
                                    {{ $t('Live Patient Triage & Active Encounters') }}
                                </h2>
                            </div>

                            <!-- Filter Pills -->
                            <div class="flex items-center gap-1 p-1 bg-[#f5f3f0] rounded-xl text-xs flex-wrap font-semibold">
                                <button
                                    type="button"
                                    @click="activeTriageFilter = 'all'"
                                    class="px-3 py-1 rounded-lg transition"
                                    :class="activeTriageFilter === 'all' ? 'bg-white text-[#00685f] shadow-2xs font-bold' : 'text-stone-600 hover:text-stone-900'"
                                >
                                    {{ $t('All') }} ({{ triageCases.length }})
                                </button>
                                <button
                                    type="button"
                                    @click="activeTriageFilter = 'emergency'"
                                    class="px-3 py-1 rounded-lg transition"
                                    :class="activeTriageFilter === 'emergency' ? 'bg-rose-600 text-white shadow-2xs font-bold' : 'text-rose-700 hover:text-rose-900'"
                                >
                                    {{ $t('Critical') }} ({{ emergencyCount }})
                                </button>
                                <button
                                    type="button"
                                    @click="activeTriageFilter = 'urgent'"
                                    class="px-3 py-1 rounded-lg transition"
                                    :class="activeTriageFilter === 'urgent' ? 'bg-amber-500 text-white shadow-2xs font-bold' : 'text-amber-800 hover:text-amber-950'"
                                >
                                    {{ $t('Urgent') }} ({{ urgentCount }})
                                </button>
                                <button
                                    type="button"
                                    @click="activeTriageFilter = 'routine'"
                                    class="px-3 py-1 rounded-lg transition"
                                    :class="activeTriageFilter === 'routine' ? 'bg-emerald-600 text-white shadow-2xs font-bold' : 'text-stone-600 hover:text-stone-900'"
                                >
                                    {{ $t('Routine') }} ({{ routineCount }})
                                </button>
                            </div>
                        </div>

                        <!-- Cases List -->
                        <div v-if="filteredTriageCases.length === 0" class="py-12 text-center text-stone-400 text-xs">
                            <svg class="w-8 h-8 text-stone-300 mx-auto mb-1.5 block shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $t('No active clinical cases currently under this triage category.') }}</span>
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="c in filteredTriageCases"
                                :key="c.id"
                                class="p-4 rounded-xl border border-stone-200/90 hover:border-[#00685f]/50 hover:shadow-xs transition bg-[#f5f3f0]/40 flex flex-col md:flex-row md:items-center justify-between gap-4 group"
                            >
                                <div class="flex items-start md:items-center gap-3.5 min-w-0 flex-1">
                                    <!-- Pet Avatar -->
                                    <div
                                        class="w-12 h-12 rounded-2xl flex items-center justify-center text-base shrink-0 border overflow-hidden"
                                        :class="c.priority === 'emergency_critical' ? 'bg-rose-100 text-rose-700 border-rose-300' : 'bg-white text-[#00685f] border-stone-200'"
                                    >
                                        <svg class="w-6 h-6 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                            <circle cx="4.5" cy="9.5" r="2.5"/><circle cx="9" cy="5.5" r="2.5"/><circle cx="15" cy="5.5" r="2.5"/><circle cx="19.5" cy="9.5" r="2.5"/>
                                            <path d="M17.34 14.86c-.87-1.02-1.6-1.89-2.48-2.49-.64-.44-1.52-.87-2.86-.87s-2.22.43-2.86.87c-.88.6-1.61 1.47-2.48 2.49-1.25 1.47-2.16 3.19-1.16 4.96.9 1.6 2.82 1.68 4.24 1.68.79 0 1.54-.04 2.26-.04.72 0 1.47.04 2.26.04 1.42 0 3.34-.08 4.24-1.68 1-1.77.09-3.49-1.16-4.96z"/>
                                        </svg>
                                    </div>

                                    <div class="flex flex-col min-w-0 gap-1 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-extrabold text-stone-900 text-sm group-hover:text-[#00685f] transition-colors">
                                                {{ c.patient?.name_or_tag || $t('Unknown Patient') }}
                                            </span>
                                            <span v-if="c.patient?.species" class="px-2 py-0.5 rounded-full bg-stone-100 text-stone-600 text-[10px] font-semibold">
                                                {{ c.patient.species }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded bg-white text-stone-700 text-[10px] font-mono font-bold border border-stone-200">
                                                {{ $t('Case: :number', { number: c.case_number }) }}
                                            </span>
                                            <TriageBadge :priority="c.priority" />
                                            <StatusBadge :status="c.status" />
                                        </div>

                                        <p class="text-xs text-stone-800 font-medium line-clamp-1 leading-relaxed">
                                            <strong class="text-stone-500 uppercase text-[10px] me-1">
                                                {{ $t('Chief Complaint:') }}
                                            </strong>
                                            <span>{{ c.chief_complaint || $t('No complaint details provided.') }}</span>
                                        </p>

                                        <div class="flex items-center gap-4 text-[11px] text-stone-500 flex-wrap pt-0.5">
                                            <span class="inline-flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 shrink-0 text-[#00685f]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v4a3 3 0 006 0V3m-9 7a6 6 0 0012 0V9a1 1 0 00-1-1h-1a1 1 0 00-1 1v1a4 4 0 01-8 0V9a1 1 0 00-1-1H4a1 1 0 00-1 1v1zm9 6a3 3 0 11-6 0v-2a1 1 0 011-1h4a1 1 0 011 1v2z" />
                                                </svg>
                                                <span>{{ $t('Attending:') }}</span>
                                                <strong class="text-stone-700">{{ c.primary_veterinarian?.name || $t('Unassigned') }}</strong>
                                            </span>
                                            <span class="inline-flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 shrink-0 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                <span>{{ $t('Owner:') }}</span>
                                                <strong class="text-stone-700">{{ c.patient?.client?.name || $t('Walk-in') }}</strong>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-2 shrink-0 self-end md:self-center">
                                    <Link
                                        :href="`/portal/cases/${c.id}`"
                                        class="px-3.5 py-2 rounded-xl bg-white hover:bg-stone-50 border border-stone-200 text-stone-700 text-xs font-bold shadow-2xs transition active:scale-95 inline-flex items-center gap-1.5"
                                    >
                                        <svg class="w-4 h-4 shrink-0 text-stone-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                                        </svg>
                                        <span>{{ $t('Case (L2)') }}</span>
                                    </Link>

                                    <Link
                                        :href="`/portal/visits/create?case_id=${c.id}`"
                                        class="px-3.5 py-2 rounded-xl bg-[#00685f] hover:bg-[#005049] text-white text-xs font-bold shadow-2xs transition active:scale-95 inline-flex items-center gap-1.5"
                                    >
                                        <svg class="w-4 h-4 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                        </svg>
                                        <span>{{ $t('+ Encounter') }}</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================
                     RIGHT SIDEBAR COLUMN (4 cols): Alerts, Formulary & Actions
                     ======================================================== -->
                <div class="lg:col-span-4 flex flex-col gap-6 min-w-0">
                    <!-- WIDGET 1: PHARMACY STOCK BELOW REORDER THRESHOLD -->
                    <div class="p-6 rounded-2xl bg-white border border-stone-200/90 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)] space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-stone-100">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <h3 class="text-sm font-extrabold text-stone-900">
                                    {{ $t('Pharmacy Low Stock Alert') }}
                                </h3>
                            </div>
                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-900 text-[10px] font-bold">
                                {{ $t(':count Alerts', { count: lowStockProducts.length }) }}
                            </span>
                        </div>

                        <!-- Searchable Formulary Filter -->
                        <div class="relative">
                            <svg class="w-4 h-4 shrink-0 absolute start-2.5 top-1/2 -translate-y-1/2 text-stone-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="pharmacySearch"
                                type="text"
                                :placeholder="$t('Filter medication or SKU...')"
                                class="w-full h-9 ps-8 pe-3 bg-[#f5f3f0] rounded-xl text-xs text-stone-900 placeholder:text-stone-400 focus:outline-none focus:bg-white focus:border-[#00685f] border border-transparent transition"
                            />
                        </div>

                        <!-- Empty state -->
                        <div v-if="filteredLowStock.length === 0" class="py-6 text-center text-xs text-stone-400">
                            {{ pharmacySearch ? $t('No matching products found.') : $t('All medications are adequately stocked above threshold.') }}
                        </div>

                        <!-- Low Stock Items List -->
                        <div v-else class="space-y-2">
                            <div
                                v-for="p in filteredLowStock"
                                :key="p.id"
                                class="p-3 rounded-xl bg-[#f5f3f0] border border-stone-200/60 flex items-center justify-between gap-3"
                            >
                                <div class="flex flex-col min-w-0">
                                    <span class="font-bold text-xs text-stone-900 truncate">{{ p.name ?? '' }}</span>
                                    <span class="text-[10px] text-stone-500 font-mono">{{ $t('SKU: :code', { code: p.sku || $t('N/A') }) }}</span>
                                </div>

                                <div class="flex flex-col items-end shrink-0">
                                    <span class="font-mono text-xs font-black text-rose-600">
                                        {{ p.current_stock }} {{ p.base_unit }}
                                    </span>
                                    <span v-if="p.reorder_threshold != null" class="text-[10px] text-stone-400 font-mono">
                                        {{ $t('Min: :qty', { qty: p.reorder_threshold }) }}
                                    </span>
                                    <Link
                                        :href="`/portal/inventory/receive?product_id=${p.id}`"
                                        class="inline-flex items-center gap-1 text-[11px] font-bold text-[#00685f] hover:underline mt-0.5"
                                    >
                                        <span>{{ $t('Restock') }}</span>
                                        <svg class="w-3 h-3 shrink-0 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div class="pt-1 flex justify-end">
                            <Link
                                href="/portal/inventory/receive"
                                class="w-full text-center py-2 px-3 rounded-xl bg-[#00685f]/10 text-[#00685f] hover:bg-[#00685f]/20 text-xs font-bold transition inline-flex items-center justify-center gap-1.5"
                            >
                                <span>{{ $t('Open Stock Intake Portal') }}</span>
                                <svg class="w-3.5 h-3.5 shrink-0 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>

                    <!-- WIDGET 2: QUICK ACTION CENTER -->
                    <div class="p-6 rounded-2xl bg-white border border-stone-200/90 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)] space-y-3">
                        <h3 class="text-sm font-extrabold text-stone-900 pb-2 border-b border-stone-100">
                            {{ $t('Quick Clinical Action Launcher') }}
                        </h3>

                        <div class="grid grid-cols-2 gap-2 text-xs font-bold">
                            <Link
                                href="/portal/patients/create"
                                class="p-3 rounded-xl bg-[#f5f3f0] hover:bg-[#00685f] hover:text-white text-stone-800 flex flex-col items-center justify-center gap-1.5 transition text-center group shadow-2xs"
                            >
                                <svg class="w-5 h-5 shrink-0 text-[#00685f] group-hover:text-white transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                <span>{{ $t('Register Pet') }}</span>
                            </Link>

                            <Link
                                href="/portal/cases/create"
                                class="p-3 rounded-xl bg-[#f5f3f0] hover:bg-[#00685f] hover:text-white text-stone-800 flex flex-col items-center justify-center gap-1.5 transition text-center group shadow-2xs"
                            >
                                <svg class="w-5 h-5 shrink-0 text-[#00685f] group-hover:text-white transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <span>{{ $t('New Case') }}</span>
                            </Link>

                            <Link
                                href="/portal/inventory/receive"
                                class="p-3 rounded-xl bg-[#f5f3f0] hover:bg-[#00685f] hover:text-white text-stone-800 flex flex-col items-center justify-center gap-1.5 transition text-center group shadow-2xs"
                            >
                                <svg class="w-5 h-5 shrink-0 text-[#00685f] group-hover:text-white transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span>{{ $t('Intake Stock') }}</span>
                            </Link>

                            <Link
                                href="/portal/inventory/waste"
                                class="p-3 rounded-xl bg-[#f5f3f0] hover:bg-rose-600 hover:text-white text-stone-800 flex flex-col items-center justify-center gap-1.5 transition text-center group shadow-2xs"
                            >
                                <svg class="w-5 h-5 shrink-0 text-rose-600 group-hover:text-white transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>{{ $t('Stock Waste') }}</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </ClinicLayout>
</template>
