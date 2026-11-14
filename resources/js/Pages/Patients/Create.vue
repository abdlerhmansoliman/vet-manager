<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const props = defineProps({
    clients: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    client_id: '',
    name_or_tag: '',
    species: 'Canine',
    breed: '',
    gender: 'male',
    date_of_birth: '',
    current_weight_kg: '',
    microchip_number: '',
    high_risk_allergies: '',
    notes: '',
});

const submit = () => {
    form.post('/portal/patients');
};
</script>

<template>
    <ClinicLayout title="Register New Patient">
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">Register Patient (Level 1)</h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">Demographics, species classification, and high-risk medical alerts.</p>
                </div>

                <Link href="/portal/patients" class="text-xs font-semibold text-stone-600 hover:text-stone-900">
                    &larr; Back to Patients
                </Link>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-stone-200 shadow-2xs">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Owner / Client Selection -->
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
                            <option value="" disabled>Select registered client</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">
                                {{ c.name }} ({{ c.phone || 'No phone' }})
                            </option>
                        </select>
                        <p v-if="form.errors.client_id" class="mt-1 text-xs text-rose-600 font-medium">
                            {{ form.errors.client_id }}
                        </p>
                    </div>

                    <!-- Name & Species -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name_or_tag" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Patient Name / Animal Tag <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="name_or_tag"
                                v-model="form.name_or_tag"
                                type="text"
                                required
                                placeholder="Max, Bella, Tag #402"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="form.errors.name_or_tag" class="mt-1 text-xs text-rose-600 font-medium">
                                {{ form.errors.name_or_tag }}
                            </p>
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
                                <option value="Canine">Canine (Dog)</option>
                                <option value="Feline">Feline (Cat)</option>
                                <option value="Equine">Equine (Horse)</option>
                                <option value="Bovine">Bovine (Cattle)</option>
                                <option value="Ovine">Ovine (Sheep/Goat)</option>
                                <option value="Avian">Avian (Bird)</option>
                                <option value="Exotic">Exotic</option>
                            </select>
                            <p v-if="form.errors.species" class="mt-1 text-xs text-rose-600 font-medium">
                                {{ form.errors.species }}
                            </p>
                        </div>
                    </div>

                    <!-- Breed & Gender -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="breed" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">Breed</label>
                            <input
                                id="breed"
                                v-model="form.breed"
                                type="text"
                                placeholder="Golden Retriever, Arabian Horse, etc."
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
                    </div>

                    <!-- Weight, DOB, Microchip -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="current_weight_kg" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">Weight (kg)</label>
                            <input
                                id="current_weight_kg"
                                v-model="form.current_weight_kg"
                                type="number"
                                step="0.01"
                                placeholder="12.50"
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
                                placeholder="985141002938192"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <!-- Critical High Risk Allergies -->
                    <div class="p-4 rounded-xl bg-rose-50/60 border border-rose-200 space-y-1.5">
                        <label for="high_risk_allergies" class="block text-xs font-bold text-rose-900 uppercase tracking-wider flex items-center space-x-1.5 rtl:space-x-reverse">
                            <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>High-Risk Allergies & Drug Adverse Reactions</span>
                        </label>
                        <textarea
                            id="high_risk_allergies"
                            v-model="form.high_risk_allergies"
                            rows="2"
                            placeholder="Penicillin anaphylaxis, NSAID severe GI intolerance, etc."
                            class="w-full rounded-lg border border-rose-300 bg-white px-3 py-2 text-xs sm:text-sm text-stone-900 focus:border-rose-500 focus:outline-none focus:ring-1 focus:ring-rose-500"
                        />
                        <p class="text-[11px] text-rose-700">Documented allergies trigger persistent warning alerts across all visits and prescriptions.</p>
                    </div>

                    <div class="pt-4 border-t border-stone-100 flex items-center justify-end space-x-3 rtl:space-x-reverse">
                        <Link href="/portal/patients" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-stone-600 hover:bg-stone-100 transition">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Registering...' : 'Register Patient' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClinicLayout>
</template>
