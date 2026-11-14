<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const props = defineProps({
    visit: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    title: props.visit.title || '',
    visit_type: props.visit.visit_type || 'outpatient',
    reason_for_visit: props.visit.reason_for_visit || '',
    visit_date: props.visit.visit_date ? props.visit.visit_date.substring(0, 16) : '',
});

const submit = () => {
    form.put(`/portal/visits/${props.visit.id}`);
};
</script>

<template>
    <ClinicLayout :title="`Edit Visit: ${visit.title}`">
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">Edit Visit Metadata (Level 3)</h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">Update visit title, encounter classification, or reason for visit.</p>
                </div>

                <Link :href="`/portal/visits/${visit.id}`" class="text-xs font-semibold text-stone-600 hover:text-stone-900">
                    &larr; Back to Visit Workspace
                </Link>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-stone-200 shadow-2xs">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="title" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            Visit Title <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="title"
                            v-model="form.title"
                            type="text"
                            required
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
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
                                <option value="surgical_procedure">Surgical Procedure</option>
                                <option value="emergency_intake">Emergency Triage Intake</option>
                                <option value="field_visit">Field / Farm Visit</option>
                            </select>
                        </div>

                        <div>
                            <label for="visit_date" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Date & Time
                            </label>
                            <input
                                id="visit_date"
                                v-model="form.visit_date"
                                type="datetime-local"
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
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>

                    <div class="pt-4 border-t border-stone-100 flex items-center justify-end space-x-3 rtl:space-x-reverse">
                        <Link :href="`/portal/visits/${visit.id}`" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-stone-600 hover:bg-stone-100 transition">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Visit Metadata' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClinicLayout>
</template>
