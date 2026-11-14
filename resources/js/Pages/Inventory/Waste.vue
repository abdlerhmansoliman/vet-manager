<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    product_id: '',
    amount_base_units: '',
    reason: '',
});

const submit = () => {
    form.post('/portal/inventory/waste');
};
</script>

<template>
    <ClinicLayout title="Log Drug Waste">
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">Log Drug Waste & Discard</h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">Deduct broken, expired, or dropped vials using First-Expired First-Out audit protocol.</p>
                </div>

                <Link href="/portal/inventory" class="text-xs font-semibold text-stone-600 hover:text-stone-900">
                    &larr; Back to Pharmacy
                </Link>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-stone-200 shadow-2xs">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="product_id" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            Select Pharmaceutical Product <span class="text-rose-500">*</span>
                        </label>
                        <select
                            id="product_id"
                            v-model="form.product_id"
                            required
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        >
                            <option value="" disabled>Choose wasted medication...</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">
                                {{ p.name }} (Current: {{ p.current_stock || '0' }} {{ p.base_unit }})
                            </option>
                        </select>
                        <p v-if="form.errors.product_id" class="mt-1 text-xs text-rose-600 font-medium">
                            {{ form.errors.product_id }}
                        </p>
                    </div>

                    <div>
                        <label for="amount_base_units" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            Wasted Quantity (Base Units) <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="amount_base_units"
                            v-model="form.amount_base_units"
                            type="number"
                            step="0.0001"
                            required
                            placeholder="e.g. 1.5000"
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 font-mono focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
                        <p v-if="form.errors.amount_base_units" class="mt-1 text-xs text-rose-600 font-medium">
                            {{ form.errors.amount_base_units }}
                        </p>
                    </div>

                    <div>
                        <label for="reason" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            Clinical Justification & Waste Reason <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            id="reason"
                            v-model="form.reason"
                            rows="3"
                            required
                            placeholder="e.g. Vial dropped and shattered on treatment room floor during draw-up."
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
                        <p v-if="form.errors.reason" class="mt-1 text-xs text-rose-600 font-medium">
                            {{ form.errors.reason }}
                        </p>
                    </div>

                    <div class="pt-4 border-t border-stone-100 flex items-center justify-end space-x-3 rtl:space-x-reverse">
                        <Link href="/portal/inventory" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-stone-600 hover:bg-stone-100 transition">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-sm transition active:scale-95 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Deducting...' : 'Deduct Waste Stock' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClinicLayout>
</template>
