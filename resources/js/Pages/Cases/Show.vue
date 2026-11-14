<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';
import TriageBadge from '@/Components/TriageBadge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import VisitCard from '@/Components/VisitCard.vue';
import MedicalAlertBanner from '@/Components/MedicalAlertBanner.vue';

const props = defineProps({
    case: {
        type: Object,
        default: () => null,
    },
    clinicalCase: {
        type: Object,
        default: () => null,
    },
    patient: {
        type: Object,
        required: true,
    },
    visits: {
        type: Array,
        default: () => [],
    },
    diagnoses: {
        type: Array,
        default: () => [],
    },
});

const cCase = computed(() => props.clinicalCase || props.case || {});
</script>

<template>
    <ClinicLayout :title="`Case: ${cCase.case_number || ''}`">
        <div class="space-y-6">
            <!-- LEVEL 2 HEADER & PRIMARY CTA -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2.5 rtl:space-x-reverse">
                        <Link :href="`/portal/patients/${patient.id}`" class="text-xs font-semibold text-emerald-700 hover:underline">
                            &larr; {{ patient.name_or_tag }}
                        </Link>
                        <span class="text-stone-300">/</span>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight font-mono">
                            {{ cCase.case_number }}
                        </h1>
                        <TriageBadge :priority="cCase.priority || 'routine'" />
                        <StatusBadge :status="cCase.status || 'open'" />
                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                            Level 2 Case
                        </span>
                    </div>

                    <p class="text-xs sm:text-sm text-stone-500 mt-1">
                        Problem umbrella for <strong>{{ patient.name_or_tag }}</strong> ({{ patient.species }}) &bull;
                        Attending: <strong>{{ cCase.primary_veterinarian?.name || 'Unassigned' }}</strong>
                    </p>
                </div>

                <div class="flex items-center space-x-2.5 rtl:space-x-reverse self-start sm:self-auto">
                    <Link
                        :href="`/portal/cases/${cCase.id}/edit`"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold text-stone-700 bg-white hover:bg-stone-50 border border-stone-200 shadow-2xs transition active:scale-95"
                    >
                        Edit Case & Diagnoses
                    </Link>

                    <!-- PRIMARY CTA FOR LEVEL 2: START NEW VISIT -->
                    <Link
                        :href="`/portal/visits/create?case_id=${cCase.id}`"
                        class="inline-flex items-center space-x-1.5 rtl:space-x-reverse px-4 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Start New Visit</span>
                    </Link>
                </div>
            </div>

            <!-- PATIENT ALLERGY WARNING (Inherited from Level 1) -->
            <MedicalAlertBanner :allergies="patient.high_risk_allergies" />

            <!-- CASE SUMMARY & DIAGNOSTIC UMBRELLA CARD -->
            <div class="p-6 rounded-2xl bg-white border border-stone-200 shadow-2xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-stone-400">
                        Case Medical Summary & Diagnostic Findings
                    </h3>
                    <span class="text-xs text-stone-500">
                        Encounter Type: <strong class="uppercase text-stone-700">{{ cCase.case_type || 'Outpatient' }}</strong>
                    </span>
                </div>

                <div class="space-y-2">
                    <span class="text-xs font-bold text-stone-700 block">Chief Complaint:</span>
                    <p class="text-xs sm:text-sm text-stone-800 bg-stone-50/70 p-3 rounded-xl border border-stone-200/60">
                        {{ cCase.chief_complaint }}
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div class="p-3.5 rounded-xl bg-amber-50/50 border border-amber-200/80">
                        <span class="text-[11px] font-bold uppercase text-amber-800 block">Provisional Diagnosis</span>
                        <p class="text-xs sm:text-sm font-semibold text-stone-900 mt-1">
                            {{ cCase.provisional_diagnosis || 'Differential diagnosis pending diagnostic workup.' }}
                        </p>
                    </div>

                    <div class="p-3.5 rounded-xl bg-emerald-50/50 border border-emerald-200/80">
                        <span class="text-[11px] font-bold uppercase text-emerald-800 block">Final Confirmed Diagnosis</span>
                        <p class="text-xs sm:text-sm font-semibold text-stone-900 mt-1">
                            {{ cCase.final_diagnosis || 'Not yet finalized by attending clinician.' }}
                        </p>
                    </div>
                </div>

                <!-- Structured Diagnoses Log -->
                <div v-if="diagnoses.length > 0" class="pt-3 border-t border-stone-100">
                    <span class="text-xs font-bold text-stone-700 block mb-2">Diagnostic Problem List:</span>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="d in diagnoses"
                            :key="d.id"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-stone-100 text-stone-800 border border-stone-200 flex items-center space-x-1.5 rtl:space-x-reverse"
                        >
                            <span>{{ d.diagnosis || d.diagnosis_name }}</span>
                            <span class="text-[10px] text-stone-500 font-normal">({{ d.status || 'Active' }})</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- LEVEL 2 EXCLUSIVE CONTENT: VISITS TIMELINE AS COMPACT TILES -->
            <!-- (Strictly ZERO media galleries allowed here! Media is strictly Level 3) -->
            <div class="p-6 rounded-2xl bg-white border border-stone-200 shadow-2xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                    <div>
                        <h2 class="text-base font-bold text-stone-900">
                            Encounters Timeline ({{ visits.length }})
                        </h2>
                        <p class="text-xs text-stone-500">
                            Chronological visits belonging to this case. Open a visit for SOAP exam, medication dosing, and diagnostic media.
                        </p>
                    </div>

                    <Link
                        :href="`/portal/visits/create?case_id=${cCase.id}`"
                        class="inline-flex items-center space-x-1.5 rtl:space-x-reverse px-3 py-1.5 rounded-lg text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Visit</span>
                    </Link>
                </div>

                <div v-if="visits.length === 0" class="py-12 text-center text-stone-500">
                    <svg class="w-10 h-10 mx-auto text-stone-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="text-xs font-semibold">No visits recorded for this case yet.</p>
                    <Link
                        :href="`/portal/visits/create?case_id=${cCase.id}`"
                        class="mt-3 inline-block px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition"
                    >
                        Start First Visit (Level 3)
                    </Link>
                </div>

                <div v-else class="space-y-3">
                    <VisitCard v-for="visit in visits" :key="visit.id" :visit="visit" />
                </div>
            </div>
        </div>
    </ClinicLayout>
</template>
