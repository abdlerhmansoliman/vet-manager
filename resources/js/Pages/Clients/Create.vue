<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const form = useForm({
    client_type: 'individual',
    name: '',
    contact_person: '',
    phone_primary: '',
    phone_secondary: '',
    email: '',
    physical_address: '',
    tax_number: '',
    notes: '',
});

const submit = () => {
    form.post('/portal/clients');
};
</script>

<template>
    <ClinicLayout :title="$t('Register Client')">
        <div class="max-w-4xl mx-auto space-y-6 pb-12">
            <!-- HEADER & BREADCRUMBS -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <nav class="flex items-center space-x-1.5 rtl:space-x-reverse text-xs text-stone-400 mb-1">
                        <Link href="/portal/clients" class="hover:text-emerald-700 transition">{{ $t('Clients / Owners') }}</Link>
                        <span>&rsaquo;</span>
                        <span class="text-stone-700 font-semibold">{{ $t('Register Client') }}</span>
                    </nav>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">
                        {{ $t('Register New Client / Account') }}
                    </h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">
                        {{ $t('Bilingual patient-linked CRM profile with automated compliance validation.') }}
                    </p>
                </div>

                <Link
                    href="/portal/clients"
                    class="inline-flex items-center space-x-1.5 rtl:space-x-reverse text-xs font-semibold text-stone-600 hover:text-stone-900 transition self-start sm:self-auto"
                >
                    <span class="rtl:rotate-180">&larr;</span>
                    <span>{{ $t('Back to Clients') }}</span>
                </Link>
            </div>

            <!-- REGISTRATION FORM -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- SECTION 1: Client Classification -->
                <section class="bg-white rounded-2xl p-6 border border-stone-200/80 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                        <div class="flex items-center space-x-3 rtl:space-x-reverse">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 font-bold flex items-center justify-center text-sm">
                                1
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-stone-900">{{ $t('Client Classification') }}</h2>
                                <p class="text-xs text-stone-500">{{ $t('Determines species profiles, billing tiers, and clinical protocols') }}</p>
                            </div>
                        </div>
                        <span class="text-[11px] font-bold text-rose-600 uppercase tracking-wider">{{ $t('Required *') }}</span>
                    </div>

                    <!-- Classification Radio Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Individual Pet Owner -->
                        <label
                            :class="[
                                'relative p-4 rounded-xl border transition-all cursor-pointer flex flex-col justify-between space-y-3',
                                form.client_type === 'individual'
                                    ? 'border-emerald-600 bg-emerald-50/40 ring-1 ring-emerald-600'
                                    : 'border-stone-200 bg-stone-50/40 hover:bg-stone-50 hover:border-stone-300'
                            ]"
                        >
                            <input
                                v-model="form.client_type"
                                type="radio"
                                value="individual"
                                class="sr-only"
                            />
                            <div class="flex items-center justify-between">
                                <div class="w-9 h-9 rounded-lg bg-white border border-stone-200 flex items-center justify-center text-emerald-700 shadow-2xs">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </div>
                                <span v-if="form.client_type === 'individual'" class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                            </div>
                            <div>
                                <span class="font-bold text-xs sm:text-sm text-stone-900 block">{{ $t('Individual Pet Owner') }}</span>
                                <p class="text-[11px] text-stone-500 mt-1 leading-snug">
                                    {{ $t('Dogs, cats, avian, exotic companion animals. Outpatient clinical billing.') }}
                                </p>
                            </div>
                            <span class="text-[10px] font-semibold text-stone-400 uppercase tracking-wider">{{ $t('Standard Account') }}</span>
                        </label>

                        <!-- Farm Manager -->
                        <label
                            :class="[
                                'relative p-4 rounded-xl border transition-all cursor-pointer flex flex-col justify-between space-y-3',
                                form.client_type === 'farm_manager'
                                    ? 'border-emerald-600 bg-emerald-50/40 ring-1 ring-emerald-600'
                                    : 'border-stone-200 bg-stone-50/40 hover:bg-stone-50 hover:border-stone-300'
                            ]"
                        >
                            <input
                                v-model="form.client_type"
                                type="radio"
                                value="farm_manager"
                                class="sr-only"
                            />
                            <div class="flex items-center justify-between">
                                <div class="w-9 h-9 rounded-lg bg-white border border-stone-200 flex items-center justify-center text-teal-700 shadow-2xs">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                </div>
                                <span v-if="form.client_type === 'farm_manager'" class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                            </div>
                            <div>
                                <span class="font-bold text-xs sm:text-sm text-stone-900 block">{{ $t('Farm Manager') }}</span>
                                <p class="text-[11px] text-stone-500 mt-1 leading-snug">
                                    {{ $t('Equine stables, camel racing barns, hobby farms, pedigree breeding centers.') }}
                                </p>
                            </div>
                            <span class="text-[10px] font-semibold text-stone-400 uppercase tracking-wider">{{ $t('Herd Tracking') }}</span>
                        </label>

                        <!-- Livestock Enterprise -->
                        <label
                            :class="[
                                'relative p-4 rounded-xl border transition-all cursor-pointer flex flex-col justify-between space-y-3',
                                form.client_type === 'livestock_enterprise'
                                    ? 'border-emerald-600 bg-emerald-50/40 ring-1 ring-emerald-600'
                                    : 'border-stone-200 bg-stone-50/40 hover:bg-stone-50 hover:border-stone-300'
                            ]"
                        >
                            <input
                                v-model="form.client_type"
                                type="radio"
                                value="livestock_enterprise"
                                class="sr-only"
                            />
                            <div class="flex items-center justify-between">
                                <div class="w-9 h-9 rounded-lg bg-white border border-stone-200 flex items-center justify-center text-amber-700 shadow-2xs">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <span v-if="form.client_type === 'livestock_enterprise'" class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                            </div>
                            <div>
                                <span class="font-bold text-xs sm:text-sm text-stone-900 block">{{ $t('Livestock Enterprise') }}</span>
                                <p class="text-[11px] text-stone-500 mt-1 leading-snug">
                                    {{ $t('Commercial dairy, industrial feedlots, sheep/goat herds, veterinary supply credits.') }}
                                </p>
                            </div>
                            <span class="text-[10px] font-semibold text-stone-400 uppercase tracking-wider">{{ $t('Corporate Invoicing') }}</span>
                        </label>
                    </div>
                </section>

                <!-- SECTION 2: Primary Identity -->
                <section class="bg-white rounded-2xl p-6 border border-stone-200/80 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                        <div class="flex items-center space-x-3 rtl:space-x-reverse">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 font-bold flex items-center justify-center text-sm">
                                2
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-stone-900">{{ $t('Primary Identity') }}</h2>
                                <p class="text-xs text-stone-500">{{ $t('Legal entity & administrative title') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Full Name / Business Name (Required) -->
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1.5">
                                {{ $t('Full Client / Business Name') }} <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                :placeholder="$t('e.g. Johnathan Davis, Al-Rawabi Stables, Greenfield Dairy')"
                                class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600 font-medium">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Contact Person (Optional) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="contact_person" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                    {{ $t('Contact Person / Representative') }}
                                </label>
                                <span class="text-[10px] text-stone-400 uppercase font-semibold">{{ $t('Optional') }}</span>
                            </div>
                            <input
                                id="contact_person"
                                v-model="form.contact_person"
                                type="text"
                                :placeholder="$t('e.g. Dr. Rashid Al-Hajri (Operations Director)')"
                                class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>

                        <!-- Tax / Commercial Reg Number (Optional) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="tax_number" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                    {{ $t('Tax / Commercial Reg Number (TRN)') }}
                                </label>
                                <span class="text-[10px] text-stone-400 uppercase font-semibold">{{ $t('Optional') }}</span>
                            </div>
                            <input
                                id="tax_number"
                                v-model="form.tax_number"
                                type="text"
                                placeholder="VAT-109283-TR"
                                class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 font-mono focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                    </div>
                </section>

                <!-- SECTION 3: Communications & Contact Details -->
                <section class="bg-white rounded-2xl p-6 border border-stone-200/80 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                        <div class="flex items-center space-x-3 rtl:space-x-reverse">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 font-bold flex items-center justify-center text-sm">
                                3
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-stone-900">{{ $t('Communications & Contact Details') }}</h2>
                                <p class="text-xs text-stone-500">{{ $t('Emergency triage & automated clinical notifications') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Primary Phone (Required) -->
                        <div>
                            <label for="phone_primary" class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1.5">
                                {{ $t('Primary Mobile Phone') }} <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="phone_primary"
                                v-model="form.phone_primary"
                                type="tel"
                                required
                                placeholder="+971 50 123 4567"
                                class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 font-mono focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="form.errors.phone_primary" class="mt-1 text-xs text-rose-600 font-medium">
                                {{ form.errors.phone_primary }}
                            </p>
                        </div>

                        <!-- Secondary Phone (Optional) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="phone_secondary" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                    {{ $t('Secondary / Night Duty Phone') }}
                                </label>
                                <span class="text-[10px] text-stone-400 uppercase font-semibold">{{ $t('Optional') }}</span>
                            </div>
                            <input
                                id="phone_secondary"
                                v-model="form.phone_secondary"
                                type="tel"
                                placeholder="+971 55 987 6543"
                                class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 font-mono focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>

                        <!-- Official Email Address (Optional) -->
                        <div class="sm:col-span-2">
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="email" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                    {{ $t('Official Email Address') }}
                                </label>
                                <span class="text-[10px] text-stone-400 uppercase font-semibold">{{ $t('Optional') }}</span>
                            </div>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="client@domain.com"
                                class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                    </div>
                </section>

                <!-- SECTION 4: Physical Location & Handling Notes -->
                <section class="bg-white rounded-2xl p-6 border border-stone-200/80 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                        <div class="flex items-center space-x-3 rtl:space-x-reverse">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 font-bold flex items-center justify-center text-sm">
                                4
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-stone-900">{{ $t('Physical Location & Handling Notes') }}</h2>
                                <p class="text-xs text-stone-500">{{ $t('Ambulatory dispatch & biosecurity precautions') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- Physical Address -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="physical_address" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                    {{ $t('Physical Address / Farm Location') }}
                                </label>
                                <span class="text-[10px] text-stone-400 uppercase font-semibold">{{ $t('Optional') }}</span>
                            </div>
                            <input
                                id="physical_address"
                                v-model="form.physical_address"
                                type="text"
                                :placeholder="$t('e.g. Sector 4, Plot 108, Al Marmoum Camel Race Track')"
                                class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>

                        <!-- Internal Notes -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="notes" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                    {{ $t('Internal Account Notes & Biosecurity Instructions') }}
                                </label>
                                <span class="text-[10px] text-stone-400 uppercase font-semibold">{{ $t('Optional') }}</span>
                            </div>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                :placeholder="$t('e.g. Biosecurity gate access code required. Special veterinary handling: Stallion nervous during venipuncture.')"
                                class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                    </div>
                </section>

                <!-- FORM ACTIONS -->
                <div class="pt-2 flex items-center justify-end space-x-3 rtl:space-x-reverse">
                    <Link
                        href="/portal/clients"
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
                        <span>{{ form.processing ? $t('Saving...') : $t('Register Client') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </ClinicLayout>
</template>
