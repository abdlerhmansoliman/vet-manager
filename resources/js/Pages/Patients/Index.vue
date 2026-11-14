<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    patients: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', species: '' }),
    },
});

const search = ref(props.filters.search || '');
const species = ref(props.filters.species || '');

const handleFilter = () => {
    router.get(
        '/portal/patients',
        { search: search.value, species: species.value },
        { preserveState: true, replace: true }
    );
};
</script>

<template>
    <ClinicLayout title="Patient Directory">
        <div class="space-y-6">
            <!-- HEADER -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">Patient Directory (Level 1)</h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">Comprehensive animal registry, species records, and clinical case profiles.</p>
                </div>

                <Link
                    href="/portal/patients/create"
                    class="inline-flex items-center space-x-1.5 rtl:space-x-reverse px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 self-start sm:self-auto"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Register New Patient</span>
                </Link>
            </div>

            <!-- SEARCH & FILTER BAR -->
            <div class="p-4 rounded-2xl bg-white border border-stone-200 shadow-2xs flex flex-col sm:flex-row items-center gap-3">
                <div class="flex-1 w-full relative">
                    <input
                        v-model="search"
                        @input="handleFilter"
                        type="text"
                        placeholder="Search by name, tag, microchip, or owner name..."
                        class="w-full rounded-xl border border-stone-300 px-3.5 py-2 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    />
                </div>

                <div class="w-full sm:w-48">
                    <select
                        v-model="species"
                        @change="handleFilter"
                        class="w-full rounded-xl border border-stone-300 px-3 py-2 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    >
                        <option value="">All Species</option>
                        <option value="Canine">Canine (Dog)</option>
                        <option value="Feline">Feline (Cat)</option>
                        <option value="Equine">Equine (Horse)</option>
                        <option value="Bovine">Bovine (Cattle)</option>
                        <option value="Ovine">Ovine (Sheep/Goat)</option>
                        <option value="Avian">Avian (Bird)</option>
                        <option value="Exotic">Exotic</option>
                    </select>
                </div>
            </div>

            <!-- PATIENTS TABLE -->
            <div class="rounded-2xl bg-white border border-stone-200 shadow-2xs overflow-hidden">
                <div v-if="patients.length === 0" class="py-16 text-center text-stone-500 text-xs sm:text-sm">
                    No patients matching the specified criteria.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-start text-xs sm:text-sm">
                        <thead class="bg-stone-50 text-stone-500 font-bold uppercase tracking-wider text-[11px] border-b border-stone-200">
                            <tr>
                                <th class="py-3 px-4 text-start">Patient / Name</th>
                                <th class="py-3 px-4 text-start">Species & Breed</th>
                                <th class="py-3 px-4 text-start">Microchip / ID</th>
                                <th class="py-3 px-4 text-start">Owner / Client</th>
                                <th class="py-3 px-4 text-start">Status</th>
                                <th class="py-3 px-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="p in patients" :key="p.id" class="hover:bg-stone-50/60 transition">
                                <td class="py-3.5 px-4 font-bold text-stone-900">
                                    <Link :href="`/portal/patients/${p.id}`" class="hover:text-emerald-700 transition">
                                        {{ p.name_or_tag }}
                                    </Link>
                                    <span v-if="p.high_risk_allergies" class="ms-2 px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">
                                        Allergies
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-stone-600">
                                    {{ p.species }}
                                    <span v-if="p.breed" class="text-stone-400">({{ p.breed }})</span>
                                </td>
                                <td class="py-3.5 px-4 text-stone-500 font-mono text-xs">
                                    {{ p.microchip_number || '—' }}
                                </td>
                                <td class="py-3.5 px-4 text-stone-700">
                                    {{ p.client ? p.client.name : 'Unknown' }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <StatusBadge :status="p.status || 'active'" />
                                </td>
                                <td class="py-3.5 px-4 text-end">
                                    <Link
                                        :href="`/portal/patients/${p.id}`"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition inline-block"
                                    >
                                        View Profile &rarr;
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
