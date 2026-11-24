<script setup>
import { watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const form = useForm({
    name: '',
    sku: '',
    base_unit: 'ml',
    packaging_factor: '1.0000',
    package_description: '',
    reorder_threshold: '10.0000',
    is_controlled: false,
    controlled_schedule: 'uncontrolled',
    description: '',
});

watch(() => form.is_controlled, (controlled) => {
    if (controlled) {
        if (!form.controlled_schedule || form.controlled_schedule === 'uncontrolled') {
            form.controlled_schedule = 'schedule_ii';
        }
    } else {
        form.controlled_schedule = 'uncontrolled';
    }
});

const submit = () => {
    if (!form.is_controlled) {
        form.controlled_schedule = 'uncontrolled';
    } else if (form.controlled_schedule === 'uncontrolled') {
        form.controlled_schedule = 'schedule_ii';
    }
    form.post('/portal/inventory');
};
</script>

<template>
    <ClinicLayout :title="$t('New Pharmaceutical Item')">
        <div class="max-w-4xl mx-auto space-y-6 pb-12">
            <!-- HEADER & BREADCRUMBS -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <nav class="flex items-center space-x-1.5 rtl:space-x-reverse text-xs text-stone-400 mb-1">
                        <Link href="/portal/inventory" class="hover:text-emerald-700 transition">{{ $t('Pharmacy & Stock') }}</Link>
                        <span>&rsaquo;</span>
                        <span class="text-stone-700 font-semibold">{{ $t('New Medication Entry') }}</span>
                    </nav>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">
                        {{ $t('Register New Drug or Clinical Product') }}
                    </h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">
                        {{ $t('Add pharmaceutical, vaccine, or consumable to clinic active inventory and eMAR.') }}
                    </p>
                </div>

                <Link
                    href="/portal/inventory"
                    class="inline-flex items-center space-x-1.5 rtl:space-x-reverse text-xs font-semibold text-stone-600 hover:text-stone-900 transition self-start sm:self-auto"
                >
                    <span class="rtl:rotate-180">&larr;</span>
                    <span>{{ $t('Back to Pharmacy') }}</span>
                </Link>
            </div>

            <!-- WORKSPACE FORM -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- SECTION 1: Product Identification -->
                <section class="bg-white rounded-2xl p-6 border border-stone-200/80 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                        <div class="flex items-center space-x-3 rtl:space-x-reverse">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 font-bold flex items-center justify-center text-sm">
                                1
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-stone-900">{{ $t('Product Identification & Classification') }}</h2>
                                <p class="text-xs text-stone-500">{{ $t('Establish legal classification, unique catalog codes, and active ingredients') }}</p>
                            </div>
                        </div>
                        <span class="text-[11px] font-bold text-rose-600 uppercase tracking-wider">{{ $t('Required *') }}</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Product Name -->
                        <div>
                            <label for="name" class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1.5">
                                {{ $t('Product / Medication Name') }} <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                :placeholder="$t('e.g. Cerenia Injectable 10mg/ml, Clavamox 62.5mg')"
                                class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600 font-medium">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- SKU / Barcode -->
                        <div>
                            <label for="sku" class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1.5">
                                {{ $t('SKU / Internal Barcode') }} <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="sku"
                                v-model="form.sku"
                                type="text"
                                required
                                placeholder="MED-CER-10MG"
                                class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 font-mono uppercase focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="form.errors.sku" class="mt-1 text-xs text-rose-600 font-medium">
                                {{ form.errors.sku }}
                            </p>
                        </div>

                        <!-- Storage Instructions & Description -->
                        <div class="sm:col-span-2">
                            <label for="description" class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1.5">
                                {{ $t('Storage Instructions & Thermal Requirements') }}
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="2"
                                :placeholder="$t('e.g. Refrigerate 2-8°C, protect from light. Not for use in dogs with active systemic infections.')"
                                class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                    </div>
                </section>

                <!-- SECTION 2: Units & Packaging -->
                <section class="bg-white rounded-2xl p-6 border border-stone-200/80 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                        <div class="flex items-center space-x-3 rtl:space-x-reverse">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 font-bold flex items-center justify-center text-sm">
                                2
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-stone-900">{{ $t('Units of Measure & Packaging Conversion') }}</h2>
                                <p class="text-xs text-stone-500">{{ $t('Base measurement unit for dosage calculation and dispensing container factor') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Base Measurement Unit -->
                        <div>
                            <label for="base_unit" class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1.5">
                                {{ $t('Base Measurement Unit') }} <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="base_unit"
                                v-model="form.base_unit"
                                required
                                class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                                <option value="ml">{{ $t('Milliliter (ml)') }}</option>
                                <option value="mg">{{ $t('Milligram (mg)') }}</option>
                                <option value="tablet">{{ $t('Tablet / Capsule') }}</option>
                                <option value="vial">{{ $t('Vial / Bottle') }}</option>
                                <option value="unit">{{ $t('Unit (IU)') }}</option>
                                <option value="drop">{{ $t('Drop') }}</option>
                            </select>
                            <p class="text-[11px] text-stone-400 mt-1">{{ $t('Exclusive unit for fractional clinical dosing') }}</p>
                        </div>

                        <!-- Package Description -->
                        <div>
                            <label for="package_description" class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1.5">
                                {{ $t('Package Description') }}
                            </label>
                            <input
                                id="package_description"
                                v-model="form.package_description"
                                type="text"
                                :placeholder="$t('e.g. 50ml Multi-dose Vial, Box of 20')"
                                class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>

                        <!-- Packaging Factor -->
                        <div>
                            <label for="packaging_factor" class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1.5">
                                {{ $t('Packaging Factor') }}
                            </label>
                            <input
                                id="packaging_factor"
                                v-model="form.packaging_factor"
                                type="number"
                                step="0.0001"
                                placeholder="1.0000"
                                class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 font-mono focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p class="text-[11px] text-stone-400 mt-1">{{ $t('Base units per commercial container') }}</p>
                        </div>
                    </div>
                </section>

                <!-- SECTION 3: Regulatory & DEA Schedule -->
                <section class="bg-white rounded-2xl p-6 border border-stone-200/80 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                        <div class="flex items-center space-x-3 rtl:space-x-reverse">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 font-bold flex items-center justify-center text-sm">
                                3
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-stone-900">{{ $t('Regulatory & Controlled Substances Schedule') }}</h2>
                                <p class="text-xs text-stone-500">{{ $t('Narcotics registry, DEA scheduling, and dual-witness audit protocols') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-stone-50 border border-stone-200 space-y-3">
                        <label class="flex items-center space-x-2.5 rtl:space-x-reverse cursor-pointer">
                            <input
                                v-model="form.is_controlled"
                                type="checkbox"
                                class="w-4 h-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500"
                            />
                            <span class="text-xs sm:text-sm font-bold text-stone-800">
                                {{ $t('Controlled Substance (Narcotic / Regulated Schedule)') }}
                            </span>
                        </label>

                        <div v-if="form.is_controlled" class="pt-2 border-t border-stone-200/60 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="controlled_schedule" class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1.5">
                                    {{ $t('DEA Schedule Classification') }}
                                </label>
                                <select
                                    id="controlled_schedule"
                                    v-model="form.controlled_schedule"
                                    class="w-full rounded-xl border border-stone-300 bg-white px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                >
                                    <option value="schedule_ii">{{ $t('Schedule II (e.g. Fentanyl, Morphine)') }}</option>
                                    <option value="schedule_iii">{{ $t('Schedule III (e.g. Ketamine, Buprenorphine)') }}</option>
                                    <option value="schedule_iv">{{ $t('Schedule IV (e.g. Diazepam, Midazolam, Tramadol)') }}</option>
                                    <option value="schedule_v">{{ $t('Schedule V (e.g. Pregabalin)') }}</option>
                                </select>
                            </div>

                            <div class="p-3 rounded-lg bg-amber-50/70 border border-amber-200 text-xs text-amber-900 flex items-start space-x-2 rtl:space-x-reverse">
                                <svg class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>{{ $t('Controlled items enforce dual-witness PIN verification during deduction and waste.') }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 4: Par Levels & Reorder Thresholds -->
                <section class="bg-white rounded-2xl p-6 border border-stone-200/80 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                        <div class="flex items-center space-x-3 rtl:space-x-reverse">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 font-bold flex items-center justify-center text-sm">
                                4
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-stone-900">{{ $t('Par Levels & Reorder Thresholds') }}</h2>
                                <p class="text-xs text-stone-500">{{ $t('Automated clinic stock deficiency monitoring') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="reorder_threshold" class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1.5">
                                {{ $t('Low Stock Reorder Threshold') }}
                            </label>
                            <input
                                id="reorder_threshold"
                                v-model="form.reorder_threshold"
                                type="number"
                                step="0.0001"
                                placeholder="10.0000"
                                class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 font-mono focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p class="text-[11px] text-stone-400 mt-1">
                                {{ $t('Triggers low stock warnings when total on-hand base units drop below this quantity.') }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ACTIONS -->
                <div class="pt-2 flex items-center justify-end space-x-3 rtl:space-x-reverse">
                    <Link
                        href="/portal/inventory"
                        class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-stone-600 hover:bg-stone-100 transition"
                    >
                        {{ $t('Cancel') }}
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 disabled:opacity-50 inline-flex items-center space-x-2 rtl:space-x-reverse"
                    >
                        <span v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        <span>{{ form.processing ? $t('Saving...') : $t('Catalog Product') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </ClinicLayout>
</template>
