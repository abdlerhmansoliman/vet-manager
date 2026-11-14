<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const props = defineProps({
    case: {
        type: Object,
        default: () => ({}),
    },
    clinicalCase: {
        type: Object,
        default: () => ({}),
    },
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
    statuses: {
        type: Array,
        default: () => ['open', 'in_treatment', 'observation', 'resolved', 'closed'],
    },
});

const cCase = computed(() => props.clinicalCase?.id ? props.clinicalCase : (props.case || {}));
const currentCase = props.clinicalCase?.id ? props.clinicalCase : (props.case || {});

const form = useForm({
    chief_complaint: currentCase.chief_complaint || '',
    priority: currentCase.priority || 'routine',
    case_type: currentCase.case_type || 'outpatient',
    status: currentCase.status || 'open',
    primary_veterinarian_id: currentCase.primary_veterinarian_id || '',
    provisional_diagnosis: currentCase.provisional_diagnosis || '',
    final_diagnosis: currentCase.final_diagnosis || '',
});

const submit = () => {
    form.put(`/portal/cases/${cCase.value.id}`);
};
</script>

<template>
    <ClinicLayout :title="`Edit Case: ${cCase.case_number}`">
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight font-mono">
                        Edit {{ cCase.case_number }} (Level 2)
                    </h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">
                        Update priority triage, case status, and provisional/final diagnoses.
                    </p>
                </div>

                <Link :href="`/portal/cases/${cCase.id}`" class="text-xs font-semibold text-stone-600 hover:text-stone-900">
                    &larr; Back to Case
                </Link>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-stone-200 shadow-2xs">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="chief_complaint" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            Chief Complaint <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            id="chief_complaint"
                            v-model="form.chief_complaint"
                            rows="3"
                            required
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
                        <p v-if="form.errors.chief_complaint" class="mt-1 text-xs text-rose-600 font-medium">
                            {{ form.errors.chief_complaint }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
                                <option value="inpatient">Inpatient</option>
                                <option value="field_farm_visit">Field / Farm Visit</option>
                            </select>
                        </div>

                        <div>
                            <label for="status" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Status <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="status"
                                v-model="form.status"
                                required
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                                <option value="open">Open / Active Workup</option>
                                <option value="under_observation">Under Clinical Observation</option>
                                <option value="critical_care">ICU / Critical Care</option>
                                <option value="discharged">Discharged</option>
                                <option value="closed">Closed & Archived</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="primary_veterinarian_id" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Primary Veterinarian
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
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="final_diagnosis" class="block text-xs font-bold text-emerald-800 uppercase tracking-wider">
                            Final Confirmed Diagnosis
                        </label>
                        <input
                            id="final_diagnosis"
                            v-model="form.final_diagnosis"
                            type="text"
                            placeholder="e.g. Acute Canine Parvoviral Enteritis (Confirmed via ELISA)"
                            class="mt-1.5 w-full rounded-xl border border-emerald-300 bg-emerald-50/20 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
                        <p class="text-[11px] text-stone-500 mt-1">Entering a final diagnosis automatically archives it in the patient's permanent medical history.</p>
                    </div>

                    <div class="pt-4 border-t border-stone-100 flex items-center justify-end space-x-3 rtl:space-x-reverse">
                        <Link :href="`/portal/cases/${cCase.id}`" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-stone-600 hover:bg-stone-100 transition">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Case Updates' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClinicLayout>
</template>
