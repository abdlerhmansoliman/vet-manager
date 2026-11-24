<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const props = defineProps({
    products: {
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
const selectedFilter = ref('all');

const handleSearch = () => {
    router.get('/portal/inventory', { search: search.value }, { preserveState: true, replace: true });
};

const totalProductsCount = computed(() => props.stats?.total ?? props.products.length);
const controlledCount = computed(() => props.stats?.controlled ?? props.products.filter(p => p.is_controlled).length);
const lowStockCount = computed(() => props.stats?.low_stock ?? props.products.filter(p => parseFloat(p.current_stock || 0) <= parseFloat(p.reorder_threshold || 0)).length);

const filteredProducts = computed(() => {
    if (selectedFilter.value === 'controlled') {
        return props.products.filter(p => p.is_controlled);
    }
    if (selectedFilter.value === 'low_stock') {
        return props.products.filter(p => parseFloat(p.current_stock || 0) <= parseFloat(p.reorder_threshold || 0));
    }
    return props.products;
});
</script>

<template>
    <ClinicLayout :title="$t('Pharmacy & Stock Catalog')">
        <div class="space-y-6 pb-12">
            <!-- HEADER -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 rtl:space-x-reverse mb-1">
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-bold text-[11px] uppercase tracking-wider">
                            {{ $t('eMAR & Inventory') }}
                        </span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">
                        {{ $t('Pharmacy & Stock Catalog') }}
                    </h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">
                        {{ $t('Pharmaceutical ledger, batch tracking, FEFO dispensing, and waste deduction.') }}
                    </p>
                </div>

                <div class="flex items-center space-x-2 rtl:space-x-reverse self-start sm:self-auto flex-wrap gap-y-2">
                    <Link
                        href="/portal/inventory/create"
                        class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold text-stone-700 bg-white hover:bg-stone-50 border border-stone-200 shadow-2xs transition active:scale-95"
                    >
                        {{ $t('+ New Drug / Item') }}
                    </Link>

                    <Link
                        href="/portal/inventory/receive"
                        class="inline-flex items-center space-x-1.5 rtl:space-x-reverse px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                        <span>{{ $t('Stock Intake') }}</span>
                    </Link>

                    <Link
                        href="/portal/inventory/waste"
                        class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition active:scale-95"
                    >
                        {{ $t('Log Waste') }}
                    </Link>
                </div>
            </div>

            <!-- TELEMETRY KPI CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl p-5 border border-stone-200/80 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-stone-400 uppercase tracking-wider block">{{ $t('Total Formulary SKUs') }}</span>
                        <span class="text-2xl font-extrabold text-stone-900 mt-1 block">{{ totalProductsCount }}</span>
                        <span class="text-[11px] text-stone-500 mt-0.5 block">{{ $t('Active medications & supplies') }}</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-stone-50 text-stone-700 flex items-center justify-center border border-stone-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-stone-200/80 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-stone-400 uppercase tracking-wider block">{{ $t('Low Stock Deficits') }}</span>
                        <span class="text-2xl font-extrabold text-rose-600 mt-1 block">{{ lowStockCount }}</span>
                        <span class="text-[11px] text-rose-700 font-semibold mt-0.5 block">{{ $t('Below reorder threshold') }}</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center border border-rose-100">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-stone-200/80 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-stone-400 uppercase tracking-wider block">{{ $t('Controlled Substances') }}</span>
                        <span class="text-2xl font-extrabold text-amber-700 mt-1 block">{{ controlledCount }}</span>
                        <span class="text-[11px] text-amber-800 font-semibold mt-0.5 block">{{ $t('DEA Schedule II-V registered') }}</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center border border-amber-100">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- SEARCH & FILTER MATRIX -->
            <div class="bg-white rounded-2xl p-4 border border-stone-200/80 shadow-2xs space-y-3">
                <div class="flex items-center space-x-1.5 rtl:space-x-reverse overflow-x-auto pb-1">
                    <button
                        type="button"
                        @click="selectedFilter = 'all'"
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap',
                            selectedFilter === 'all'
                                ? 'bg-emerald-600 text-white shadow-2xs'
                                : 'bg-stone-50 text-stone-600 hover:bg-stone-100'
                        ]"
                    >
                        {{ $t('All Formulary') }} ({{ totalProductsCount }})
                    </button>
                    <button
                        type="button"
                        @click="selectedFilter = 'controlled'"
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap',
                            selectedFilter === 'controlled'
                                ? 'bg-emerald-600 text-white shadow-2xs'
                                : 'bg-stone-50 text-stone-600 hover:bg-stone-100'
                        ]"
                    >
                        {{ $t('Controlled (DEA)') }} ({{ controlledCount }})
                    </button>
                    <button
                        type="button"
                        @click="selectedFilter = 'low_stock'"
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap',
                            selectedFilter === 'low_stock'
                                ? 'bg-rose-600 text-white shadow-2xs'
                                : 'bg-stone-50 text-stone-600 hover:bg-stone-100'
                        ]"
                    >
                        {{ $t('Low Stock Alert') }} ({{ lowStockCount }})
                    </button>
                </div>

                <div class="relative">
                    <input
                        v-model="search"
                        @input="handleSearch"
                        type="text"
                        :placeholder="$t('Search inventory by product name, SKU, or active ingredients...')"
                        class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    />
                </div>
            </div>

            <!-- INVENTORY TABLE -->
            <div class="rounded-2xl bg-white border border-stone-200/80 shadow-2xs overflow-hidden">
                <div v-if="filteredProducts.length === 0" class="py-16 text-center text-stone-500 text-xs sm:text-sm">
                    <p class="font-semibold">{{ $t('No products cataloged in the pharmacy yet.') }}</p>
                    <Link
                        href="/portal/inventory/create"
                        class="mt-3 inline-block px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition"
                    >
                        {{ $t('+ New Drug / Item') }}
                    </Link>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-start text-xs sm:text-sm">
                        <thead class="bg-stone-50 text-stone-500 font-bold uppercase tracking-wider text-[11px] border-b border-stone-200">
                            <tr>
                                <th class="py-3.5 px-4 text-start">{{ $t('Product & SKU') }}</th>
                                <th class="py-3.5 px-4 text-start">{{ $t('Base Unit') }}</th>
                                <th class="py-3.5 px-4 text-start">{{ $t('Current Balance') }}</th>
                                <th class="py-3.5 px-4 text-start">{{ $t('Reorder Point') }}</th>
                                <th class="py-3.5 px-4 text-start">{{ $t('Active Batches') }}</th>
                                <th class="py-3.5 px-4 text-end">{{ $t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="p in filteredProducts" :key="p.id" class="hover:bg-stone-50/70 transition">
                                <td class="py-3.5 px-4 font-bold text-stone-900">
                                    <div class="flex items-center space-x-2.5 rtl:space-x-reverse">
                                        <div class="w-8 h-8 rounded-lg bg-stone-100 text-stone-700 font-bold flex items-center justify-center text-xs shrink-0 border border-stone-200">
                                            <svg class="w-4 h-4 text-stone-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="flex items-center space-x-1.5 rtl:space-x-reverse">
                                                <span class="block text-sm font-bold text-stone-900">{{ p.name }}</span>
                                                <span v-if="p.is_controlled" class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-200">
                                                    DEA S-{{ p.controlled_schedule }}
                                                </span>
                                            </div>
                                            <span class="font-mono text-[11px] text-stone-400 font-normal block">{{ p.sku }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-stone-600 font-semibold capitalize">
                                    {{ p.base_unit }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold" :class="[parseFloat(p.current_stock || 0) <= parseFloat(p.reorder_threshold || 0) ? 'text-rose-600' : 'text-stone-900']">
                                    <div class="flex items-center space-x-1.5 rtl:space-x-reverse">
                                        <span>{{ p.current_stock || '0.0000' }} {{ p.base_unit }}</span>
                                        <span v-if="parseFloat(p.current_stock || 0) <= parseFloat(p.reorder_threshold || 0)" class="w-2 h-2 rounded-full bg-rose-600 animate-pulse"></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-stone-500 font-mono text-xs">
                                    {{ p.reorder_threshold || '—' }} {{ p.base_unit }}
                                </td>
                                <td class="py-3.5 px-4 text-stone-600">
                                    <span class="px-2 py-0.5 rounded-md bg-stone-100 text-stone-700 text-xs font-bold">
                                        {{ p.batches_count || (p.batches ? p.batches.length : 0) }} {{ $t('batch(es)') }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-end space-x-1.5 rtl:space-x-reverse">
                                    <Link
                                        :href="`/portal/inventory/receive?product_id=${p.id}`"
                                        class="px-2.5 py-1 rounded-lg text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition inline-block"
                                    >
                                        {{ $t('+ Intake') }}
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
