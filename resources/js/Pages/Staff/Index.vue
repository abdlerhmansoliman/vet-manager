<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
    staff: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const currentTenant = computed(() => page.props.currentTenant);
const currentLocale = computed(() => page.props.locale || 'en');

// Search & Role Filters
const searchQuery = ref('');
const selectedRoleFilter = ref('all');

// Week Navigation State
const weekOffset = ref(0);

const dateFormatter = computed(() =>
    new Intl.DateTimeFormat(currentLocale.value === 'ar' ? 'ar-EG' : 'en-US', { day: 'numeric', month: 'short' })
);

const dayFormatter = computed(() =>
    new Intl.DateTimeFormat(currentLocale.value === 'ar' ? 'ar-EG' : 'en-US', { weekday: 'short' })
);

const weekDates = computed(() => {
    const today = new Date();
    const baseDate = new Date(today);
    baseDate.setDate(today.getDate() + (weekOffset.value * 7));

    // Calculate Monday of current week
    const day = baseDate.getDay();
    const diff = baseDate.getDate() - day + (day === 0 ? -6 : 1);
    const monday = new Date(baseDate.setDate(diff));

    const days = [];
    for (let i = 0; i < 7; i++) {
        const d = new Date(monday);
        d.setDate(monday.getDate() + i);
        const isCurrentToday = d.toDateString() === (new Date()).toDateString();
        days.push({
            name: dayFormatter.value.format(d),
            dateNum: d.getDate(),
            fullDate: d,
            isToday: isCurrentToday,
        });
    }
    return days;
});

const weekLabel = computed(() => {
    if (weekDates.value.length === 0) return '';
    const first = weekDates.value[0].fullDate;
    const last = weekDates.value[6].fullDate;
    return `${dateFormatter.value.format(first)} – ${dateFormatter.value.format(last)}`;
});

const resetToToday = () => {
    weekOffset.value = 0;
};

const prevWeek = () => {
    if (weekOffset.value > -52) weekOffset.value -= 1;
};

const nextWeek = () => {
    if (weekOffset.value < 52) weekOffset.value += 1;
};

// Real Role counts
const vetCount = computed(() => props.staff.filter((s) => ['veterinarian', 'doctor', 'owner'].includes(s.role?.toLowerCase())).length);
const techCount = computed(() => props.staff.filter((s) => ['vet_tech', 'technician', 'nurse'].includes(s.role?.toLowerCase())).length);
const assistantCount = computed(() => props.staff.filter((s) => ['assistant', 'receptionist', 'admin', 'intern'].includes(s.role?.toLowerCase())).length);
const activeStaffCount = computed(() => props.staff.filter((s) => s.is_active).length);
const totalActiveCases = computed(() => props.staff.reduce((sum, u) => sum + (u.active_cases_count || 0), 0));

// Filtered Staff
const filteredStaff = computed(() => {
    return props.staff.filter((u) => {
        const query = searchQuery.value.trim().toLowerCase();
        const name = (u.name ?? '').toLowerCase();
        const email = (u.email ?? '').toLowerCase();
        const specialty = (u.profile?.specialty ?? '').toLowerCase();
        const jobTitle = (u.profile?.job_title ?? '').toLowerCase();
        const license = (u.profile?.license_number ?? '').toLowerCase();

        const matchesQuery = !query ||
            name.includes(query) ||
            email.includes(query) ||
            specialty.includes(query) ||
            jobTitle.includes(query) ||
            license.includes(query);

        if (!matchesQuery) return false;

        const role = (u.role ?? '').toLowerCase();
        if (selectedRoleFilter.value === 'vets') {
            return ['veterinarian', 'doctor', 'owner'].includes(role);
        }
        if (selectedRoleFilter.value === 'techs') {
            return ['vet_tech', 'technician', 'nurse'].includes(role);
        }
        if (selectedRoleFilter.value === 'assistants') {
            return ['assistant', 'receptionist', 'admin', 'intern'].includes(role);
        }
        return true;
    });
});

// Guard against double clicks on toggleDuty
const togglingId = ref(null);
const toggleDuty = (user) => {
    if (togglingId.value) return;
    togglingId.value = user.id;
    router.post(`/portal/staff/${user.id}/toggle-duty`, {}, {
        preserveScroll: true,
        onFinish: () => {
            togglingId.value = null;
        },
    });
};

const printSchedule = () => {
    window.print();
};

const formatRoleTitle = (u) => {
    if (u.profile?.specialty) return u.profile.specialty;
    if (u.profile?.job_title) return u.profile.job_title;
    if (u.role === 'owner') return trans('Medical Director • DVM');
    if (u.role === 'veterinarian') return trans('Clinical Veterinarian • DVM');
    if (u.role === 'vet_tech') return trans('Licensed Veterinary Technician');
    if (u.role === 'receptionist') return trans('Front Desk & Patient Triage');
    if (u.role === 'admin') return trans('Practice Administrator');
    return u.role ? trans(u.role) : trans('Staff Member');
};
</script>

<template>
    <ClinicLayout :title="$t('Medical Staff & Duty Schedule')" :fullWidth="true">
        <div class="space-y-8 print:space-y-4">
            <!-- 1. HEADER & OPERATIONS TOOLBAR -->
            <header class="flex flex-col gap-4 sm:gap-6">
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-teal-50 text-teal-700 shadow-2xs">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </span>
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-stone-900 tracking-tight">
                                {{ $t('Medical Staff & Duty Schedule') }}
                            </h1>
                        </div>
                        <p class="text-xs sm:text-sm text-stone-500 flex items-center flex-wrap gap-2">
                            <span class="font-bold text-stone-700">{{ currentTenant?.name || $t('VetManager Medical Center') }}</span>
                            <span class="inline-block w-1 h-1 rounded-full bg-stone-300" />
                            <span class="font-bold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-full text-xs">
                                {{ $t(':count Active Medical Personnel', { count: activeStaffCount }) }}
                            </span>
                            <span class="inline-block w-1 h-1 rounded-full bg-stone-300" />
                            <span>{{ $t('Department of Surgery, ER & Internal Medicine') }}</span>
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center flex-wrap gap-2.5 print:hidden">
                        <button
                            type="button"
                            @click="printSchedule"
                            class="inline-flex items-center gap-2 h-11 px-4 rounded-xl bg-white border border-stone-200 text-stone-700 font-bold text-xs shadow-2xs hover:bg-stone-50 hover:text-stone-900 transition active:scale-95"
                        >
                            <svg class="w-4 h-4 text-stone-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>{{ $t('Export Schedule') }}</span>
                            <span class="px-1.5 py-0.5 rounded bg-stone-100 text-[10px] text-stone-500 font-mono">{{ $t('PDF') }}</span>
                        </button>

                        <Link
                            href="/portal/staff/create"
                            class="inline-flex items-center gap-2 h-11 px-4 rounded-xl bg-teal-700 text-white font-bold text-xs shadow-sm hover:bg-teal-800 transition active:scale-95"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            <span>{{ $t('+ Add Staff Member') }}</span>
                        </Link>
                    </div>
                </div>

                <!-- 2. FILTER RIBBON & CALENDAR NAVIGATION STRIP -->
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4 p-3 sm:p-4 rounded-2xl bg-white border border-stone-200/90 shadow-2xs print:hidden">
                    <!-- Search & Role Category Tabs -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                        <div class="relative min-w-[240px] max-w-sm">
                            <svg class="w-4 h-4 absolute start-3.5 top-1/2 -translate-y-1/2 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="searchQuery"
                                type="text"
                                :placeholder="$t('Filter by clinician name, license #...')"
                                class="w-full h-10 ps-9.5 pe-3 rounded-xl bg-stone-50/80 border border-stone-200 text-xs text-stone-900 placeholder:text-stone-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition"
                            />
                        </div>

                        <!-- Role Tabs -->
                        <div class="flex items-center gap-1 overflow-x-auto p-1 rounded-xl bg-stone-100/80 border border-stone-200/60">
                            <button
                                type="button"
                                @click="selectedRoleFilter = 'all'"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap"
                                :class="[selectedRoleFilter === 'all' ? 'bg-teal-700 text-white shadow-2xs' : 'text-stone-600 hover:text-stone-900 hover:bg-white/60']"
                            >
                                {{ $t('All Staff') }} ({{ staff.length }})
                            </button>
                            <button
                                type="button"
                                @click="selectedRoleFilter = 'vets'"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap"
                                :class="[selectedRoleFilter === 'vets' ? 'bg-teal-700 text-white shadow-2xs' : 'text-stone-600 hover:text-stone-900 hover:bg-white/60']"
                            >
                                {{ $t('Veterinarians') }} ({{ vetCount }})
                            </button>
                            <button
                                type="button"
                                @click="selectedRoleFilter = 'techs'"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap"
                                :class="[selectedRoleFilter === 'techs' ? 'bg-teal-700 text-white shadow-2xs' : 'text-stone-600 hover:text-stone-900 hover:bg-white/60']"
                            >
                                {{ $t('LVTs & Techs') }} ({{ techCount }})
                            </button>
                            <button
                                type="button"
                                @click="selectedRoleFilter = 'assistants'"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap"
                                :class="[selectedRoleFilter === 'assistants' ? 'bg-teal-700 text-white shadow-2xs' : 'text-stone-600 hover:text-stone-900 hover:bg-white/60']"
                            >
                                {{ $t('Assistants & Staff') }} ({{ assistantCount }})
                            </button>
                        </div>
                    </div>

                    <!-- Week Navigator Strip -->
                    <div class="flex items-center justify-between sm:justify-end gap-2 pt-2 lg:pt-0 border-t border-stone-100 lg:border-t-0">
                        <div class="flex items-center gap-1 bg-stone-100/80 p-1 rounded-xl border border-stone-200/60">
                            <button
                                type="button"
                                @click="prevWeek"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-stone-600 hover:bg-white hover:text-stone-900 transition shadow-2xs rtl:rotate-180"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <span class="px-2.5 font-bold text-xs text-stone-800 whitespace-nowrap flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-teal-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>{{ $t('Week of :range', { range: weekLabel }) }}</span>
                            </span>
                            <button
                                type="button"
                                @click="nextWeek"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-stone-600 hover:bg-white hover:text-stone-900 transition shadow-2xs rtl:rotate-180"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                        <button
                            type="button"
                            @click="resetToToday"
                            class="px-3 py-1.5 rounded-xl bg-stone-200/70 hover:bg-stone-300/80 font-bold text-xs text-stone-700 transition"
                        >
                            {{ $t('Today') }}
                        </button>
                    </div>
                </div>
            </header>

            <!-- 3. LIVE STATUS & CLINICIAN ROSTER GRID -->
            <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5">
                <article
                    v-for="u in filteredStaff"
                    :key="u.id"
                    class="p-4 sm:p-5 rounded-2xl bg-white border border-stone-200/80 shadow-2xs flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden group"
                >
                    <!-- Background Soft Glow -->
                    <div class="absolute -end-6 -top-6 w-24 h-24 rounded-full bg-teal-500/5 group-hover:scale-125 transition-transform duration-500 pointer-events-none" />

                    <div>
                        <!-- Clinician Profile Top Info -->
                        <div class="flex items-start justify-between gap-3 mb-3.5">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="relative shrink-0">
                                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-teal-700 to-emerald-800 text-white font-extrabold flex items-center justify-center text-sm shadow-sm ring-2 ring-teal-600/20">
                                        {{ (u.name ?? 'V').charAt(0).toUpperCase() }}
                                    </div>
                                    <!-- Online Pulse Dot -->
                                    <span
                                        class="absolute -bottom-1 -end-1 w-3.5 h-3.5 rounded-full ring-2 ring-white flex items-center justify-center"
                                        :class="[u.is_active ? 'bg-emerald-500' : 'bg-stone-300']"
                                    >
                                        <span v-if="u.is_active" class="w-1.5 h-1.5 rounded-full bg-white animate-pulse" />
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <h2 class="font-extrabold text-sm sm:text-base text-stone-900 leading-tight truncate">
                                        {{ u.name }}
                                    </h2>
                                    <p class="text-xs text-teal-700 font-bold truncate mt-0.5">
                                        {{ formatRoleTitle(u) }}
                                    </p>
                                    <p class="text-[11px] text-stone-400 font-mono truncate">
                                        {{ u.email }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Duty Status Pill -->
                        <div class="mb-3.5">
                            <span
                                v-if="u.is_active"
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200/80 text-[11px] font-bold"
                            >
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                                <span>{{ $t('On Duty') }}</span>
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-stone-100 text-stone-600 border border-stone-200 text-[11px] font-bold"
                            >
                                <span class="w-2 h-2 rounded-full bg-stone-400" />
                                <span>{{ $t('Off Duty') }}</span>
                            </span>
                        </div>

                        <!-- Today's Caseload Metric -->
                        <div class="space-y-1.5 p-2.5 rounded-xl bg-stone-50 border border-stone-100 mb-3.5">
                            <div class="flex items-center justify-between text-stone-500 text-[11px] font-bold">
                                <span>{{ $t("Today's Caseload") }}</span>
                                <span class="text-stone-800">
                                    {{ $t(':count active cases', { count: u.active_cases_count }) }}
                                </span>
                            </div>
                            <div class="w-full bg-stone-200 rounded-full h-1.5 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="[u.active_cases_count > 6 ? 'bg-rose-500' : u.active_cases_count > 2 ? 'bg-teal-600' : 'bg-emerald-500']"
                                    :style="{ width: `${Math.min(100, (u.active_cases_count / 8) * 100)}%` }"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Footer: Real License tag & Toggle Duty Button -->
                    <div class="pt-3 border-t border-stone-100 flex items-center justify-between gap-2">
                        <span class="text-[11px] font-mono text-stone-400 font-bold truncate">
                            {{ u.profile?.license_number ? `#${u.profile.license_number}` : $t('No license on file') }}
                        </span>

                        <button
                            type="button"
                            :disabled="togglingId === u.id"
                            @click="toggleDuty(u)"
                            class="px-2.5 py-1 rounded-lg text-xs font-bold transition active:scale-95 border disabled:opacity-50"
                            :class="[
                                u.is_active
                                    ? 'bg-stone-100 text-stone-700 hover:bg-stone-200 border-stone-200'
                                    : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border-emerald-200'
                            ]"
                        >
                            {{ togglingId === u.id ? $t('Updating...') : (u.is_active ? $t('Set Off Duty') : $t('Set On Duty')) }}
                        </button>
                    </div>
                </article>

                <!-- Empty State -->
                <div v-if="filteredStaff.length === 0" class="col-span-full py-16 text-center rounded-2xl bg-white border border-stone-200">
                    <svg class="w-12 h-12 text-stone-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <p class="text-xs sm:text-sm font-bold text-stone-600">
                        {{ $t('No staff members found matching the selected filter.') }}
                    </p>
                </div>
            </section>

            <!-- 4. REAL CLINICAL TELEMETRY & CAPACITY OVERVIEW -->
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-5 print:hidden">
                <!-- Panel 1: Live On-Duty Roster Capacity -->
                <div class="p-5 rounded-2xl bg-white border border-stone-200/90 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-teal-50 text-teal-700">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </span>
                            <h3 class="font-extrabold text-sm text-stone-900">
                                {{ $t('Shift Coverage Capacity') }}
                            </h3>
                        </div>
                        <span class="text-xs font-extrabold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-full">
                            {{ activeStaffCount }} / {{ staff.length }} {{ $t('On Duty') }}
                        </span>
                    </div>

                    <div class="space-y-2">
                        <div class="w-full bg-stone-100 rounded-full h-2 overflow-hidden">
                            <div
                                class="bg-teal-600 h-full rounded-full transition-all duration-500"
                                :style="{ width: `${staff.length > 0 ? (activeStaffCount / staff.length) * 100 : 0}%` }"
                            />
                        </div>
                        <p class="text-xs text-stone-500 leading-relaxed">
                            {{ $t('Clinicians currently available for consultations and emergency intake.') }}
                        </p>
                    </div>
                </div>

                <!-- Panel 2: Total Active Caseload -->
                <div class="p-5 rounded-2xl bg-white border border-stone-200/90 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-700">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </span>
                            <h3 class="font-extrabold text-sm text-stone-900">
                                {{ $t('Clinical Caseload') }}
                            </h3>
                        </div>
                        <span class="text-xs font-extrabold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                            {{ $t(':count active cases', { count: totalActiveCases }) }}
                        </span>
                    </div>

                    <div class="space-y-2">
                        <div class="w-full bg-stone-100 rounded-full h-2 overflow-hidden">
                            <div
                                class="bg-emerald-500 h-full rounded-full transition-all duration-500"
                                :style="{ width: `${Math.min(100, totalActiveCases * 10)}%` }"
                            />
                        </div>
                        <p class="text-xs text-stone-500 leading-relaxed">
                            {{ $t('Open cases distributed among attending veterinary staff.') }}
                        </p>
                    </div>
                </div>

                <!-- Panel 3: Department Roles Breakdown -->
                <div class="p-5 rounded-2xl bg-white border border-stone-200/90 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-amber-50 text-amber-700">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </span>
                            <h3 class="font-extrabold text-sm text-stone-900">
                                {{ $t('Department Breakdown') }}
                            </h3>
                        </div>
                        <span class="text-xs font-bold text-stone-500">
                            {{ staff.length }} {{ $t('Total') }}
                        </span>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex items-center justify-between p-2 rounded-lg bg-stone-50">
                            <span class="font-bold text-stone-700">{{ $t('Veterinarians') }}</span>
                            <span class="font-mono font-bold text-teal-800">{{ vetCount }}</span>
                        </div>
                        <div class="flex items-center justify-between p-2 rounded-lg bg-stone-50">
                            <span class="font-bold text-stone-700">{{ $t('LVTs & Techs') }}</span>
                            <span class="font-mono font-bold text-emerald-800">{{ techCount }}</span>
                        </div>
                        <div class="flex items-center justify-between p-2 rounded-lg bg-stone-50">
                            <span class="font-bold text-stone-700">{{ $t('Assistants & Staff') }}</span>
                            <span class="font-mono font-bold text-amber-800">{{ assistantCount }}</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </ClinicLayout>
</template>
