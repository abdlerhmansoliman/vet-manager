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
});

const cCase = computed(() => props.clinicalCase?.id ? props.clinicalCase : (props.case || {}));
const currentCase = props.clinicalCase?.id ? props.clinicalCase : (props.case || {});

const form = useForm({
    case_id: currentCase.id,
    title: '',
    visit_type: currentCase.case_type || 'outpatient',
    visit_date: new Date().toISOString().slice(0, 16),
    reason_for_visit: '',
});

const submit = () => {
    form.post('/portal/visits');
};
</script>

<template>
    <ClinicLayout :title="`Start Visit: ${cCase.case_number}`">
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">Start Clinical Visit (Level 3)</h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">
                        New clinical encounter under Case <strong>{{ cCase.case_number }}</strong>.
                    </p>
                </div>

                <Link :href="`/portal/cases/${cCase.id}`" class="text-xs font-semibold text-stone-600 hover:text-stone-900">
                    &larr; Back to Case
                </Link>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-stone-200 shadow-2xs">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="title" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            Visit Title / Encounter Label <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="title"
                            v-model="form.title"
                            type="text"
                            required
                            placeholder="e.g. Initial Emergency Intake & Physical Exam, Day 2 Recheck"
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-rose-600 font-medium">
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="visit_type" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Visit Type <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="visit_type"
                                v-model="form.visit_type"
                                required
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                                <option value="outpatient">Outpatient Consultation</option>
                                <option value="inpatient_rounds">Inpatient Clinical Rounds</option>
                                <option value="surgical_procedure">Surgical Procedure / Anesthesia</option>
                                <option value="emergency_intake">Emergency Triage Intake</option>
                                <option value="field_visit">Field / Farm Visit</option>
                            </select>
                        </div>

                        <div>
                            <label for="visit_date" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Date & Time <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="visit_date"
                                v-model="form.visit_date"
                                type="datetime-local"
                                required
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="reason_for_visit" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            Clinical Reason for Visit
                        </label>
                        <textarea
                            id="reason_for_visit"
                            v-model="form.reason_for_visit"
                            rows="3"
                            placeholder="Primary clinical objective for this specific encounter..."
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
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
                            {{ form.processing ? 'Opening...' : 'Start Clinical Visit' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClinicLayout>
</template>
