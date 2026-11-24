<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const props = defineProps({
    clients: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => null,
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const search = ref(props.filters.search || '');
const selectedType = ref('all');

const handleSearch = () => {
    router.get('/portal/clients', { search: search.value }, { preserveState: true, replace: true });
};

const totalClientsCount = computed(() => props.stats?.total ?? props.clients.length);
const individualCount = computed(() => props.stats?.individual ?? props.clients.filter(c => c.client_type === 'individual').length);
const farmCount = computed(() => props.stats?.farm ?? props.clients.filter(c => c.client_type === 'farm_manager' || c.client_type === 'livestock_enterprise').length);

const filteredClients = computed(() => {
    if (selectedType.value === 'all') {
        return props.clients;
    }
    return props.clients.filter(c => c.client_type === selectedType.value);
});
</script>

<template>
    <ClinicLayout :title="$t('Clients Directory & Registry')">
        <div class="space-y-6 pb-12">
            <!-- HEADER -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 rtl:space-x-reverse mb-1">
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-bold text-[11px] uppercase tracking-wider">
                            {{ $t('Client CRM') }}
                        </span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">
                        {{ $t('Clients Directory & Registry') }}
                    </h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">
                        {{ $t('Pet owners, equine stables, farm accounts, and registered animals census.') }}
                    </p>
                </div>

                <Link
                    href="/portal/clients/create"
                    class="inline-flex items-center space-x-2 rtl:space-x-reverse px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 self-start sm:self-auto"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>{{ $t('+ Register New Client') }}</span>
                </Link>
            </div>

            <!-- TELEMETRY KPI CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-5 border border-stone-200/80 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-stone-400 uppercase tracking-wider block">{{ $t('Total Accounts') }}</span>
                        <span class="text-2xl font-extrabold text-stone-900 mt-1 block">{{ totalClientsCount }}</span>
                        <span class="text-[11px] text-stone-500 mt-0.5 block">{{ $t('Registered practice clients') }}</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-stone-50 text-stone-700 flex items-center justify-center border border-stone-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-stone-200/80 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-stone-400 uppercase tracking-wider block">{{ $t('Individual Pet Owners') }}</span>
                        <span class="text-2xl font-extrabold text-stone-900 mt-1 block">{{ individualCount }}</span>
                        <span class="text-[11px] text-emerald-700 font-semibold mt-0.5 block">{{ $t('Companion animal accounts') }}</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center border border-emerald-100">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-stone-200/80 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-stone-400 uppercase tracking-wider block">{{ $t('Farms & Livestock') }}</span>
                        <span class="text-2xl font-extrabold text-stone-900 mt-1 block">{{ farmCount }}</span>
                        <span class="text-[11px] text-teal-700 font-semibold mt-0.5 block">{{ $t('Commercial & stable managers') }}</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center border border-teal-100">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- SEARCH & FILTER CONTROLS -->
            <div class="bg-white rounded-2xl p-4 border border-stone-200/80 shadow-2xs space-y-3">
                <!-- Client Type Filter Tabs -->
                <div class="flex items-center space-x-1.5 rtl:space-x-reverse overflow-x-auto pb-1">
                    <button
                        type="button"
                        @click="selectedType = 'all'"
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap',
                            selectedType === 'all'
                                ? 'bg-emerald-600 text-white shadow-2xs'
                                : 'bg-stone-50 text-stone-600 hover:bg-stone-100'
                        ]"
                    >
                        {{ $t('All Accounts') }} ({{ totalClientsCount }})
                    </button>
                    <button
                        type="button"
                        @click="selectedType = 'individual'"
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap',
                            selectedType === 'individual'
                                ? 'bg-emerald-600 text-white shadow-2xs'
                                : 'bg-stone-50 text-stone-600 hover:bg-stone-100'
                        ]"
                    >
                        {{ $t('Individual Pet Owners') }} ({{ individualCount }})
                    </button>
                    <button
                        type="button"
                        @click="selectedType = 'farm_manager'"
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap',
                            selectedType === 'farm_manager'
                                ? 'bg-emerald-600 text-white shadow-2xs'
                                : 'bg-stone-50 text-stone-600 hover:bg-stone-100'
                        ]"
                    >
                        {{ $t('Farm Managers') }}
                    </button>
                    <button
                        type="button"
                        @click="selectedType = 'livestock_enterprise'"
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap',
                            selectedType === 'livestock_enterprise'
                                ? 'bg-emerald-600 text-white shadow-2xs'
                                : 'bg-stone-50 text-stone-600 hover:bg-stone-100'
                        ]"
                    >
                        {{ $t('Livestock Enterprises') }}
                    </button>
                </div>

                <!-- Global Search Input -->
                <div class="relative">
                    <input
                        v-model="search"
                        @input="handleSearch"
                        type="text"
                        :placeholder="$t('Search by client name, primary phone, contact person, or tax number...')"
                        class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    />
                </div>
            </div>

            <!-- CLIENTS TABLE -->
            <div class="rounded-2xl bg-white border border-stone-200/80 shadow-2xs overflow-hidden">
                <div v-if="filteredClients.length === 0" class="py-16 text-center text-stone-500 text-xs sm:text-sm">
                    <p class="font-semibold">{{ $t('No client records found matching the criteria.') }}</p>
                    <Link
                        href="/portal/clients/create"
                        class="mt-3 inline-block px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition"
                    >
                        {{ $t('Register First Client') }}
                    </Link>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-start text-xs sm:text-sm">
                        <thead class="bg-stone-50 text-stone-500 font-bold uppercase tracking-wider text-[11px] border-b border-stone-200">
                            <tr>
                                <th class="py-3.5 px-4 text-start">{{ $t('Client / Account') }}</th>
                                <th class="py-3.5 px-4 text-start">{{ $t('Account Type') }}</th>
                                <th class="py-3.5 px-4 text-start">{{ $t('Contact Phone') }}</th>
                                <th class="py-3.5 px-4 text-start">{{ $t('Email / Details') }}</th>
                                <th class="py-3.5 px-4 text-start">{{ $t('Registered Patients') }}</th>
                                <th class="py-3.5 px-4 text-end">{{ $t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="c in filteredClients" :key="c.id" class="hover:bg-stone-50/70 transition">
                                <td class="py-3.5 px-4 font-bold text-stone-900">
                                    <Link :href="`/portal/clients/${c.id}`" class="hover:text-emerald-700 transition flex items-center space-x-2 rtl:space-x-reverse">
                                        <div class="w-8 h-8 rounded-lg bg-stone-100 text-stone-700 font-extrabold flex items-center justify-center text-xs shrink-0">
                                            {{ c.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <span class="block text-sm font-bold text-stone-900">{{ c.name }}</span>
                                            <span v-if="c.contact_person" class="block text-[11px] font-normal text-stone-500">
                                                {{ c.contact_person }}
                                            </span>
                                        </div>
                                    </Link>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded text-[11px] font-bold',
                                            c.client_type === 'individual'
                                                ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                                                : c.client_type === 'farm_manager'
                                                ? 'bg-teal-50 text-teal-800 border border-teal-200'
                                                : 'bg-amber-50 text-amber-800 border border-amber-200'
                                        ]"
                                    >
                                        {{
                                            c.client_type === 'individual'
                                                ? $t('Individual Pet Owner')
                                                : c.client_type === 'farm_manager'
                                                ? $t('Farm Manager')
                                                : $t('Livestock Enterprise')
                                        }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-stone-700 font-mono text-xs">
                                    {{ c.phone_primary || c.phone || '—' }}
                                </td>
                                <td class="py-3.5 px-4 text-stone-500">
                                    {{ c.email || c.physical_address || '—' }}
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-stone-800">
                                    <span class="inline-flex items-center space-x-1 rtl:space-x-reverse px-2.5 py-1 rounded-lg bg-stone-100 text-stone-700 text-xs">
                                        <svg class="w-3.5 h-3.5 text-stone-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                        <span>{{ c.patients_count || (c.patients ? c.patients.length : 0) }} {{ $t('patient(s)') }}</span>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-end">
                                    <Link
                                        :href="`/portal/clients/${c.id}`"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition inline-flex items-center space-x-1 rtl:space-x-reverse"
                                    >
                                        <span>{{ $t('View') }}</span>
                                        <span class="rtl:rotate-180">&rarr;</span>
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
