<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    patients: {
        type: Array,
        default: () => [],
    },
    species_stats: {
        type: Object,
        default: () => null,
    },
    filters: {
        type: Object,
        default: () => ({ search: '', species: '', status: '' }),
    },
});

const search = ref(props.filters.search || '');
const selectedSpecies = ref(props.filters.species || '');
const selectedStatus = ref(props.filters.status || '');

const handleFilter = () => {
    router.get(
        '/portal/patients',
        {
            search: search.value,
            species: selectedSpecies.value,
            status: selectedStatus.value,
        },
        { preserveState: true, replace: true }
    );
};

const selectSpeciesTab = (sp) => {
    selectedSpecies.value = sp;
    handleFilter();
};

const totalCount = computed(() => props.species_stats?.total ?? props.patients.length);
const canineCount = computed(() => props.species_stats?.canine ?? props.patients.filter(p => p.species === 'Canine').length);
const felineCount = computed(() => props.species_stats?.feline ?? props.patients.filter(p => p.species === 'Feline').length);
const equineCount = computed(() => props.species_stats?.equine ?? props.patients.filter(p => p.species === 'Equine').length);
const bovineCount = computed(() => props.species_stats?.bovine ?? props.patients.filter(p => p.species === 'Bovine').length);
const ovineCount = computed(() => props.species_stats?.ovine ?? props.patients.filter(p => p.species === 'Ovine').length);
const otherCount = computed(() => props.species_stats?.other ?? props.patients.filter(p => !['Canine', 'Feline', 'Equine', 'Bovine', 'Ovine'].includes(p.species)).length);
</script>

<template>
    <ClinicLayout :title="$t('Patients & Livestock Registry')">
        <div class="space-y-6 pb-12">
            <!-- HEADER -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 rtl:space-x-reverse mb-1">
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-bold text-[11px] uppercase tracking-wider">
                            {{ $t('Census Level 1') }}
                        </span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">
                        {{ $t('Patients & Livestock Registry') }}
                    </h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">
                        {{ $t('Master census of companion patients, equine athletes, and commercial livestock herds.') }}
                    </p>
                </div>

                <Link
                    href="/portal/patients/create"
                    class="inline-flex items-center space-x-2 rtl:space-x-reverse px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 self-start sm:self-auto"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>{{ $t('+ New Patient / Animal') }}</span>
                </Link>
            </div>

            <!-- SPECIES BREAKDOWN TELEMETRY RAILS -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <button
                    type="button"
                    @click="selectSpeciesTab('')"
                    :class="[
                        'p-4 rounded-2xl border text-start transition-all cursor-pointer shadow-2xs',
                        selectedSpecies === ''
                            ? 'bg-emerald-600 text-white border-emerald-600 ring-1 ring-emerald-600'
                            : 'bg-white border-stone-200/80 hover:bg-stone-50 text-stone-900'
                    ]"
                >
                    <span :class="['text-xs font-bold block uppercase tracking-wider', selectedSpecies === '' ? 'text-white/80' : 'text-stone-400']">{{ $t('All Patients') }}</span>
                    <span class="text-2xl font-extrabold block mt-1">{{ totalCount }}</span>
                    <span :class="['text-[11px] block mt-0.5', selectedSpecies === '' ? 'text-white/80' : 'text-stone-500']">{{ $t('Total Census') }}</span>
                </button>

                <button
                    type="button"
                    @click="selectSpeciesTab('Canine')"
                    :class="[
                        'p-4 rounded-2xl border text-start transition-all cursor-pointer shadow-2xs',
                        selectedSpecies === 'Canine'
                            ? 'bg-emerald-600 text-white border-emerald-600 ring-1 ring-emerald-600'
                            : 'bg-white border-stone-200/80 hover:bg-stone-50 text-stone-900'
                    ]"
                >
                    <span :class="['text-xs font-bold block uppercase tracking-wider', selectedSpecies === 'Canine' ? 'text-white/80' : 'text-stone-400']">{{ $t('Canine') }}</span>
                    <span class="text-2xl font-extrabold block mt-1">{{ canineCount }}</span>
                    <span :class="['text-[11px] block mt-0.5', selectedSpecies === 'Canine' ? 'text-white/80' : 'text-emerald-700 font-semibold']">{{ $t('Dogs') }}</span>
                </button>

                <button
                    type="button"
                    @click="selectSpeciesTab('Feline')"
                    :class="[
                        'p-4 rounded-2xl border text-start transition-all cursor-pointer shadow-2xs',
                        selectedSpecies === 'Feline'
                            ? 'bg-emerald-600 text-white border-emerald-600 ring-1 ring-emerald-600'
                            : 'bg-white border-stone-200/80 hover:bg-stone-50 text-stone-900'
                    ]"
                >
                    <span :class="['text-xs font-bold block uppercase tracking-wider', selectedSpecies === 'Feline' ? 'text-white/80' : 'text-stone-400']">{{ $t('Feline') }}</span>
                    <span class="text-2xl font-extrabold block mt-1">{{ felineCount }}</span>
                    <span :class="['text-[11px] block mt-0.5', selectedSpecies === 'Feline' ? 'text-white/80' : 'text-teal-700 font-semibold']">{{ $t('Cats') }}</span>
                </button>

                <button
                    type="button"
                    @click="selectSpeciesTab('Equine')"
                    :class="[
                        'p-4 rounded-2xl border text-start transition-all cursor-pointer shadow-2xs',
                        selectedSpecies === 'Equine'
                            ? 'bg-emerald-600 text-white border-emerald-600 ring-1 ring-emerald-600'
                            : 'bg-white border-stone-200/80 hover:bg-stone-50 text-stone-900'
                    ]"
                >
                    <span :class="['text-xs font-bold block uppercase tracking-wider', selectedSpecies === 'Equine' ? 'text-white/80' : 'text-stone-400']">{{ $t('Equine') }}</span>
                    <span class="text-2xl font-extrabold block mt-1">{{ equineCount }}</span>
                    <span :class="['text-[11px] block mt-0.5', selectedSpecies === 'Equine' ? 'text-white/80' : 'text-amber-700 font-semibold']">{{ $t('Horses') }}</span>
                </button>

                <button
                    type="button"
                    @click="selectSpeciesTab('Bovine')"
                    :class="[
                        'p-4 rounded-2xl border text-start transition-all cursor-pointer shadow-2xs',
                        selectedSpecies === 'Bovine'
                            ? 'bg-emerald-600 text-white border-emerald-600 ring-1 ring-emerald-600'
                            : 'bg-white border-stone-200/80 hover:bg-stone-50 text-stone-900'
                    ]"
                >
                    <span :class="['text-xs font-bold block uppercase tracking-wider', selectedSpecies === 'Bovine' ? 'text-white/80' : 'text-stone-400']">{{ $t('Bovine') }}</span>
                    <span class="text-2xl font-extrabold block mt-1">{{ bovineCount }}</span>
                    <span :class="['text-[11px] block mt-0.5', selectedSpecies === 'Bovine' ? 'text-white/80' : 'text-stone-500 font-semibold']">{{ $t('Cattle') }}</span>
                </button>

                <button
                    type="button"
                    @click="selectSpeciesTab('Ovine')"
                    :class="[
                        'p-4 rounded-2xl border text-start transition-all cursor-pointer shadow-2xs',
                        selectedSpecies === 'Ovine'
                            ? 'bg-emerald-600 text-white border-emerald-600 ring-1 ring-emerald-600'
                            : 'bg-white border-stone-200/80 hover:bg-stone-50 text-stone-900'
                    ]"
                >
                    <span :class="['text-xs font-bold block uppercase tracking-wider', selectedSpecies === 'Ovine' ? 'text-white/80' : 'text-stone-400']">{{ $t('Ovine / Other') }}</span>
                    <span class="text-2xl font-extrabold block mt-1">{{ ovineCount + otherCount }}</span>
                    <span :class="['text-[11px] block mt-0.5', selectedSpecies === 'Ovine' ? 'text-white/80' : 'text-stone-500 font-semibold']">{{ $t('Sheep / Exotics') }}</span>
                </button>
            </div>

            <!-- SEARCH & FILTER MATRIX -->
            <div class="bg-white rounded-2xl p-4 border border-stone-200/80 shadow-2xs grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                <div class="sm:col-span-5 relative">
                    <input
                        v-model="search"
                        @input="handleFilter"
                        type="text"
                        :placeholder="$t('Search by animal name, microchip, ear tag, or owner...')"
                        class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    />
                </div>

                <div class="sm:col-span-4">
                    <select
                        v-model="selectedSpecies"
                        @change="handleFilter"
                        class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    >
                        <option value="">{{ $t('All Species Filter') }}</option>
                        <option value="Canine">{{ $t('Canine (Dog)') }}</option>
                        <option value="Feline">{{ $t('Feline (Cat)') }}</option>
                        <option value="Equine">{{ $t('Equine (Horse)') }}</option>
                        <option value="Bovine">{{ $t('Bovine (Cattle)') }}</option>
                        <option value="Ovine">{{ $t('Ovine (Sheep/Goat)') }}</option>
                        <option value="Avian">{{ $t('Avian (Bird)') }}</option>
                        <option value="Exotic">{{ $t('Exotic') }}</option>
                    </select>
                </div>

                <div class="sm:col-span-3">
                    <select
                        v-model="selectedStatus"
                        @change="handleFilter"
                        class="w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    >
                        <option value="">{{ $t('All Clinical Statuses') }}</option>
                        <option value="active">{{ $t('Active Patient') }}</option>
                        <option value="deceased">{{ $t('Deceased') }}</option>
                        <option value="transferred">{{ $t('Transferred Practice') }}</option>
                        <option value="archived">{{ $t('Archived Record') }}</option>
                    </select>
                </div>
            </div>

            <!-- PATIENTS TABLE -->
            <div class="rounded-2xl bg-white border border-stone-200/80 shadow-2xs overflow-hidden">
                <div v-if="patients.length === 0" class="py-16 text-center text-stone-500 text-xs sm:text-sm">
                    <p class="font-semibold">{{ $t('No patient records found matching the criteria.') }}</p>
                    <Link
                        href="/portal/patients/create"
                        class="mt-3 inline-block px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition"
                    >
                        {{ $t('Register First Patient') }}
                    </Link>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-start text-xs sm:text-sm">
                        <thead class="bg-stone-50 text-stone-500 font-bold uppercase tracking-wider text-[11px] border-b border-stone-200">
                            <tr>
                                <th class="py-3.5 px-4 text-start">{{ $t('Patient / Tag') }}</th>
                                <th class="py-3.5 px-4 text-start">{{ $t('Species & Breed') }}</th>
                                <th class="py-3.5 px-4 text-start">{{ $t('Owner / Account') }}</th>
                                <th class="py-3.5 px-4 text-start">{{ $t('Weight & Identification') }}</th>
                                <th class="py-3.5 px-4 text-start">{{ $t('Clinical Status') }}</th>
                                <th class="py-3.5 px-4 text-end">{{ $t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="p in patients" :key="p.id" class="hover:bg-stone-50/70 transition">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center space-x-2.5 rtl:space-x-reverse">
                                        <div class="w-9 h-9 rounded-xl bg-stone-100 text-stone-700 font-bold flex items-center justify-center text-xs shrink-0 border border-stone-200">
                                            {{ p.name_or_tag.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="flex items-center space-x-1.5 rtl:space-x-reverse">
                                                <Link :href="`/portal/patients/${p.id}`" class="font-bold text-stone-900 hover:text-emerald-700 transition">
                                                    {{ p.name_or_tag }}
                                                </Link>
                                                <!-- High-Risk Allergy Warning Badge -->
                                                <span
                                                    v-if="p.high_risk_allergies"
                                                    class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200 inline-flex items-center space-x-1 rtl:space-x-reverse"
                                                    :title="p.high_risk_allergies"
                                                >
                                                    <svg class="w-3 h-3 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    <span>{{ $t('Allergies') }}</span>
                                                </span>
                                            </div>
                                            <span class="text-[11px] text-stone-400 capitalize block mt-0.5">
                                                {{ p.gender ? p.gender.replace('_', ' ') : $t('Unknown') }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-stone-800 block">{{ p.species }}</span>
                                    <span class="text-[11px] text-stone-500 block">{{ p.breed || $t('Mixed / Unspecified') }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <Link
                                        v-if="p.client"
                                        :href="`/portal/clients/${p.client.id}`"
                                        class="font-semibold text-stone-900 hover:text-emerald-700 transition block"
                                    >
                                        {{ p.client.name }}
                                    </Link>
                                    <span v-else class="text-stone-400">—</span>
                                    <span class="text-[11px] text-stone-400 font-mono block">
                                        {{ p.client?.phone_primary || p.client?.phone || '' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-xs text-stone-700">
                                    <div>
                                        <span v-if="p.current_weight_kg" class="font-semibold text-stone-900 font-sans">
                                            {{ p.current_weight_kg }} kg
                                        </span>
                                        <span v-else class="text-stone-400 font-sans">—</span>
                                    </div>
                                    <span v-if="p.microchip_number" class="text-[11px] text-stone-500 block">
                                        ID: {{ p.microchip_number }}
                                    </span>
                                    <span v-else-if="p.ear_tag_id" class="text-[11px] text-stone-500 block">
                                        Tag: {{ p.ear_tag_id }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <StatusBadge :status="p.status || 'active'" />
                                </td>
                                <td class="py-3.5 px-4 text-end">
                                    <Link
                                        :href="`/portal/patients/${p.id}`"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition inline-flex items-center space-x-1 rtl:space-x-reverse"
                                    >
                                        <span>{{ $t('Open Profile (L1)') }}</span>
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
