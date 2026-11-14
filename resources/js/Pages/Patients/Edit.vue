<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const props = defineProps({
    patient: {
        type: Object,
        required: true,
    },
    clients: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    client_id: props.patient.client_id || '',
    name_or_tag: props.patient.name_or_tag || '',
    species: props.patient.species || 'Canine',
    breed: props.patient.breed || '',
    gender: props.patient.gender || 'male',
    date_of_birth: props.patient.date_of_birth ? props.patient.date_of_birth.substring(0, 10) : '',
    current_weight_kg: props.patient.current_weight_kg || '',
    microchip_number: props.patient.microchip_number || '',
    high_risk_allergies: props.patient.high_risk_allergies || '',
    status: props.patient.status || 'active',
    notes: props.patient.notes || '',
});

const submit = () => {
    form.put(`/portal/patients/${props.patient.id}`);
};
</script>

<template>
    <ClinicLayout :title="`Edit Patient: ${patient.name_or_tag}`">
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">Edit Demographics (Level 1)</h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">Update patient details, allergy warnings, and client relationship.</p>
                </div>

                <Link :href="`/portal/patients/${patient.id}`" class="text-xs font-semibold text-stone-600 hover:text-stone-900">
                    &larr; Back to Patient Profile
                </Link>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-stone-200 shadow-2xs">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="client_id" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            Patient Owner / Client <span class="text-rose-500">*</span>
                        </label>
                        <select
                            id="client_id"
                            v-model="form.client_id"
                            required
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        >
                            <option v-for="c in clients" :key="c.id" :value="c.id">
                                {{ c.name }} ({{ c.phone || 'No phone' }})
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name_or_tag" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Patient Name / Tag <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="name_or_tag"
                                v-model="form.name_or_tag"
                                type="text"
                                required
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label for="species" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Species <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="species"
                                v-model="form.species"
                                required
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                                <option value="Canine">Canine</option>
                                <option value="Feline">Feline</option>
                                <option value="Equine">Equine</option>
                                <option value="Bovine">Bovine</option>
                                <option value="Ovine">Ovine</option>
                                <option value="Avian">Avian</option>
                                <option value="Exotic">Exotic</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="breed" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">Breed</label>
                            <input
                                id="breed"
                                v-model="form.breed"
                                type="text"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label for="gender" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">Gender</label>
                            <select
                                id="gender"
                                v-model="form.gender"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="neutered_male">Neutered Male</option>
                                <option value="spayed_female">Spayed Female</option>
                                <option value="unknown">Unknown</option>
                            </select>
                        </div>

                        <div>
                            <label for="status" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">Status</label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                                <option value="active">Active</option>
                                <option value="deceased">Deceased</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="current_weight_kg" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">Weight (kg)</label>
                            <input
                                id="current_weight_kg"
                                v-model="form.current_weight_kg"
                                type="number"
                                step="0.01"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label for="date_of_birth" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">Date of Birth</label>
                            <input
                                id="date_of_birth"
                                v-model="form.date_of_birth"
                                type="date"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label for="microchip_number" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">Microchip #</label>
                            <input
                                id="microchip_number"
                                v-model="form.microchip_number"
                                type="text"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-rose-50/60 border border-rose-200 space-y-1.5">
                        <label for="high_risk_allergies" class="block text-xs font-bold text-rose-900 uppercase tracking-wider">
                            High-Risk Allergies & Drug Adverse Reactions
                        </label>
                        <textarea
                            id="high_risk_allergies"
                            v-model="form.high_risk_allergies"
                            rows="2"
                            class="w-full rounded-lg border border-rose-300 bg-white px-3 py-2 text-xs sm:text-sm text-stone-900 focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500"
                        />
                    </div>

                    <div class="pt-4 border-t border-stone-100 flex items-center justify-end space-x-3 rtl:space-x-reverse">
                        <Link :href="`/portal/patients/${patient.id}`" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-stone-600 hover:bg-stone-100 transition">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Demographics' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClinicLayout>
</template>
