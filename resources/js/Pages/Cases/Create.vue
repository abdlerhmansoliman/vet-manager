<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const props = defineProps({
    patient: {
        type: Object,
        required: true,
    },
    vets: {
        type: Array,
        default: () => [],
    },
    priorities: {
        type: Array,
        default: () => ['routine', 'urgent', 'emergency_critical'],
    },
    caseTypes: {
        type: Array,
        default: () => ['outpatient', 'inpatient', 'field_farm_visit'],
    },
});

const form = useForm({
    patient_id: props.patient.id,
    chief_complaint: '',
    priority: 'routine',
    case_type: 'outpatient',
    primary_veterinarian_id: '',
    provisional_diagnosis: '',
});

const submit = () => {
    form.post('/portal/cases');
};
</script>

<template>
    <ClinicLayout :title="`Open Case: ${patient.name_or_tag}`">
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">Open Clinical Case (Level 2)</h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">
                        New problem umbrella for <strong>{{ patient.name_or_tag }}</strong> ({{ patient.species }}).
                    </p>
                </div>

                <Link :href="`/portal/patients/${patient.id}`" class="text-xs font-semibold text-stone-600 hover:text-stone-900">
                    &larr; Back to Patient
                </Link>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-stone-200 shadow-2xs">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Chief Complaint -->
                    <div>
                        <label for="chief_complaint" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            Chief Complaint & Presenting Problem <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            id="chief_complaint"
                            v-model="form.chief_complaint"
                            rows="3"
                            required
                            placeholder="e.g. Acute lethargy, vomiting yellow bile twice this morning, refusing food..."
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
                        <p v-if="form.errors.chief_complaint" class="mt-1 text-xs text-rose-600 font-medium">
                            {{ form.errors.chief_complaint }}
                        </p>
                    </div>

                    <!-- Priority & Case Type -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="priority" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Triage Priority <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="priority"
                                v-model="form.priority"
                                required
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                                <option value="routine">Routine</option>
                                <option value="urgent">Urgent</option>
                                <option value="emergency_critical">Critical / Emergency</option>
                            </select>
                        </div>

                        <div>
                            <label for="case_type" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Encounter Type <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="case_type"
                                v-model="form.case_type"
                                required
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                                <option value="outpatient">Outpatient</option>
                                <option value="inpatient">Inpatient / Hospitalized</option>
                                <option value="field_farm_visit">Field / Farm Visit</option>
                            </select>
                        </div>
                    </div>

                    <!-- Attending Doctor & Provisional Diagnosis -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="primary_veterinarian_id" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Attending Veterinarian
                            </label>
                            <select
                                id="primary_veterinarian_id"
                                v-model="form.primary_veterinarian_id"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                                <option value="">Assign attending doctor...</option>
                                <option v-for="vet in vets" :key="vet.id" :value="vet.id">
                                    {{ vet.name }} ({{ vet.role }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="provisional_diagnosis" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Provisional / Working Diagnosis
                            </label>
                            <input
                                id="provisional_diagnosis"
                                v-model="form.provisional_diagnosis"
                                type="text"
                                placeholder="e.g. Acute Gastroenteritis, R/O Foreign Body"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
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
                            {{ form.processing ? 'Opening Case...' : 'Open Clinical Case' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClinicLayout>
</template>
