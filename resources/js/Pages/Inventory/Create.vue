<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const form = useForm({
    name: '',
    sku: '',
    base_unit: 'ml',
    is_controlled: false,
    controlled_schedule: 'uncontrolled',
    reorder_threshold: '10.0000',
    description: '',
});

const submit = () => {
    form.post('/portal/inventory');
};
</script>

<template>
    <ClinicLayout title="New Pharmaceutical Item">
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">Create Pharmaceutical Product</h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">Define master medication record, standard base unit, and regulatory schedule.</p>
                </div>

                <Link href="/portal/inventory" class="text-xs font-semibold text-stone-600 hover:text-stone-900">
                    &larr; Back to Pharmacy
                </Link>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-stone-200 shadow-2xs">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Product / Medication Name <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="e.g. Cerenia Injectable 10mg/ml, Clavamox 62.5mg"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600 font-medium">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label for="sku" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                SKU / Internal Code <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="sku"
                                v-model="form.sku"
                                type="text"
                                required
                                placeholder="MED-CER-10MG"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 font-mono focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="form.errors.sku" class="mt-1 text-xs text-rose-600 font-medium">
                                {{ form.errors.sku }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="base_unit" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Base Measurement Unit <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="base_unit"
                                v-model="form.base_unit"
                                required
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                                <option value="ml">Milliliter (ml)</option>
                                <option value="mg">Milligram (mg)</option>
                                <option value="tablet">Tablet / Capsule</option>
                                <option value="vial">Vial / Bottle</option>
                                <option value="unit">Unit (IU)</option>
                                <option value="drop">Drop</option>
                            </select>
                        </div>

                        <div>
                            <label for="reorder_threshold" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Low Stock Reorder Threshold
                            </label>
                            <input
                                id="reorder_threshold"
                                v-model="form.reorder_threshold"
                                type="number"
                                step="0.0001"
                                placeholder="10.0000"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-stone-50 border border-stone-200 space-y-3">
                        <label class="flex items-center text-xs font-bold text-stone-800">
                            <input v-model="form.is_controlled" type="checkbox" class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500 me-2" />
                            Controlled Substance (DEA / Narcotic Schedule)
                        </label>

                        <div v-if="form.is_controlled" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                            <div>
                                <label for="controlled_schedule" class="block text-[11px] font-bold text-stone-600">DEA Schedule</label>
                                <select
                                    id="controlled_schedule"
                                    v-model="form.controlled_schedule"
                                    class="mt-1 w-full rounded-lg border border-stone-300 px-2.5 py-1.5 text-xs text-stone-900 focus:border-emerald-500 focus:outline-none"
                                >
                                    <option value="schedule_ii">Schedule II (e.g. Fentanyl, Morphine)</option>
                                    <option value="schedule_iii">Schedule III (e.g. Ketamine, Buprenorphine)</option>
                                    <option value="schedule_iv">Schedule IV (e.g. Diazepam, Midazolam, Tramadol)</option>
                                    <option value="schedule_v">Schedule V (e.g. Pregabalin)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">Notes & Storage</label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="2"
                            placeholder="Refrigerate at 2-8°C, protect from light..."
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
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
                            {{ form.processing ? 'Cataloging...' : 'Catalog Product' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClinicLayout>
</template>
