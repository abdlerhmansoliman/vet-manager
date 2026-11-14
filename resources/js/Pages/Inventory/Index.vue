<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const search = ref(props.filters.search || '');

const handleSearch = () => {
    router.get('/portal/inventory', { search: search.value }, { preserveState: true, replace: true });
};
</script>

<template>
    <ClinicLayout title="Pharmacy & Stock Inventory">
        <div class="space-y-6">
            <!-- HEADER -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">Pharmacy & Stock Catalog</h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">Pharmaceutical ledger, batch tracking, FEFO dispensing, and waste deduction.</p>
                </div>

                <div class="flex items-center space-x-2 rtl:space-x-reverse self-start sm:self-auto">
                    <Link
                        href="/portal/inventory/create"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold text-stone-700 bg-white hover:bg-stone-50 border border-stone-200 shadow-2xs transition active:scale-95"
                    >
                        + New Drug / Item
                    </Link>

                    <Link
                        href="/portal/inventory/receive"
                        class="inline-flex items-center space-x-1.5 rtl:space-x-reverse px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                        <span>Stock Intake</span>
                    </Link>

                    <Link
                        href="/portal/inventory/waste"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition active:scale-95"
                    >
                        Log Waste
                    </Link>
                </div>
            </div>

            <!-- SEARCH -->
            <div class="p-4 rounded-2xl bg-white border border-stone-200 shadow-2xs">
                <input
                    v-model="search"
                    @input="handleSearch"
                    type="text"
                    placeholder="Search inventory by product name, SKU, or active ingredients..."
                    class="w-full rounded-xl border border-stone-300 px-3.5 py-2 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                />
            </div>

            <!-- INVENTORY TABLE -->
            <div class="rounded-2xl bg-white border border-stone-200 shadow-2xs overflow-hidden">
                <div v-if="products.length === 0" class="py-16 text-center text-stone-500 text-xs sm:text-sm">
                    No products cataloged in the pharmacy yet.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-start text-xs sm:text-sm">
                        <thead class="bg-stone-50 text-stone-500 font-bold uppercase tracking-wider text-[11px] border-b border-stone-200">
                            <tr>
                                <th class="py-3 px-4 text-start">Product & SKU</th>
                                <th class="py-3 px-4 text-start">Base Unit</th>
                                <th class="py-3 px-4 text-start">Current Balance</th>
                                <th class="py-3 px-4 text-start">Reorder Point</th>
                                <th class="py-3 px-4 text-start">Active Batches</th>
                                <th class="py-3 px-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="p in products" :key="p.id" class="hover:bg-stone-50/60 transition">
                                <td class="py-3.5 px-4 font-bold text-stone-900">
                                    <span class="block">{{ p.name }}</span>
                                    <span class="font-mono text-[11px] text-stone-400 font-normal">{{ p.sku }}</span>
                                    <span v-if="p.is_controlled" class="ms-1.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900">
                                        DEA S-{{ p.controlled_schedule }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-stone-600 font-medium">
                                    {{ p.base_unit }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold" :class="[parseFloat(p.current_stock || 0) <= parseFloat(p.reorder_threshold || 0) ? 'text-rose-600' : 'text-stone-900']">
                                    {{ p.current_stock || '0.0000' }} {{ p.base_unit }}
                                </td>
                                <td class="py-3.5 px-4 text-stone-500 font-mono text-xs">
                                    {{ p.reorder_threshold || '—' }} {{ p.base_unit }}
                                </td>
                                <td class="py-3.5 px-4 text-stone-600">
                                    <span class="px-2 py-0.5 rounded-md bg-stone-100 text-stone-700 text-xs font-bold">
                                        {{ p.batches_count || (p.batches ? p.batches.length : 0) }} batch(es)
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-end space-x-1.5 rtl:space-x-reverse">
                                    <Link
                                        :href="`/portal/inventory/receive?product_id=${p.id}`"
                                        class="px-2.5 py-1 rounded text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition inline-block"
                                    >
                                        + Intake
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
