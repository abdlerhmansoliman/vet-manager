<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    selectedProductId: {
        type: String,
        default: '',
    },
});

const form = useForm({
    product_id: props.selectedProductId || '',
    batch_number: '',
    expiration_date: '',
    initial_base_quantity: '',
    cost_per_base_unit: '',
});

const submit = () => {
    form.post('/portal/inventory/receive');
};
</script>

<template>
    <ClinicLayout title="Receive Stock Batch">
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">Receive Stock Intake</h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">Log new supplier shipment with lot/batch number and expiration date.</p>
                </div>

                <Link href="/portal/inventory" class="text-xs font-semibold text-stone-600 hover:text-stone-900">
                    &larr; Back to Pharmacy
                </Link>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-stone-200 shadow-2xs">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="product_id" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            Select Product <span class="text-rose-500">*</span>
                        </label>
                        <select
                            id="product_id"
                            v-model="form.product_id"
                            required
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        >
                            <option value="" disabled>Choose pharmaceutical product...</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">
                                {{ p.name }} ({{ p.sku }}) — {{ p.base_unit }}
                            </option>
                        </select>
                        <p v-if="form.errors.product_id" class="mt-1 text-xs text-rose-600 font-medium">
                            {{ form.errors.product_id }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="batch_number" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Lot / Batch Number <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="batch_number"
                                v-model="form.batch_number"
                                type="text"
                                required
                                placeholder="LOT-2026-X88"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 font-mono focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="form.errors.batch_number" class="mt-1 text-xs text-rose-600 font-medium">
                                {{ form.errors.batch_number }}
                            </p>
                        </div>

                        <div>
                            <label for="expiration_date" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Expiration Date <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="expiration_date"
                                v-model="form.expiration_date"
                                type="date"
                                required
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="form.errors.expiration_date" class="mt-1 text-xs text-rose-600 font-medium">
                                {{ form.errors.expiration_date }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="initial_base_quantity" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Received Quantity (in base units) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="initial_base_quantity"
                                v-model="form.initial_base_quantity"
                                type="number"
                                step="0.0001"
                                required
                                placeholder="100.0000"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 font-mono focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="form.errors.initial_base_quantity" class="mt-1 text-xs text-rose-600 font-medium">
                                {{ form.errors.initial_base_quantity }}
                            </p>
                        </div>

                        <div>
                            <label for="cost_per_base_unit" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Unit Cost (Buy Price)
                            </label>
                            <input
                                id="cost_per_base_unit"
                                v-model="form.cost_per_base_unit"
                                type="number"
                                step="0.0001"
                                placeholder="1.2500"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 font-mono focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="pt-4 border-t border-stone-100 flex items-center justify-end space-x-3 rtl:space-x-reverse">
                        <Link href="/portal/inventory" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-stone-600 hover:bg-stone-100 transition">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Receiving...' : 'Record Batch Intake' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClinicLayout>
</template>
