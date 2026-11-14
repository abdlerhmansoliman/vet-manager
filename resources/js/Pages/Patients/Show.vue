<script setup>
import { Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';
import TriageBadge from '@/Components/TriageBadge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import MedicalAlertBanner from '@/Components/MedicalAlertBanner.vue';

const props = defineProps({
    patient: {
        type: Object,
        required: true,
    },
    cases: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <ClinicLayout :title="`Patient: ${patient.name_or_tag}`">
        <div class="space-y-6">
            <!-- LEVEL 1 HEADER & PRIMARY CTA -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2.5 rtl:space-x-reverse">
                        <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">
                            {{ patient.name_or_tag }}
                        </h1>
                        <StatusBadge :status="patient.status || 'active'" />
                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-stone-100 text-stone-600 border border-stone-200">
                            Level 1 Profile
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">
                        {{ patient.species }} &bull; {{ patient.breed || 'Mixed Breed' }} &bull;
                        Owner: <strong class="text-stone-700">{{ patient.client?.name || 'Unknown' }}</strong>
                    </p>
                </div>

                <div class="flex items-center space-x-2.5 rtl:space-x-reverse self-start sm:self-auto">
                    <Link
                        :href="`/portal/patients/${patient.id}/edit`"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold text-stone-700 bg-white hover:bg-stone-50 border border-stone-200 shadow-2xs transition active:scale-95"
                    >
                        Edit Demographics
                    </Link>

                    <!-- PRIMARY CTA FOR LEVEL 1: CREATE NEW CASE -->
                    <Link
                        :href="`/portal/cases/create?patient_id=${patient.id}`"
                        class="inline-flex items-center space-x-1.5 rtl:space-x-reverse px-4 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Create New Case</span>
                    </Link>
                </div>
            </div>

            <!-- MEDICAL ALERT BANNER (High-Risk Allergies) -->
            <MedicalAlertBanner :allergies="patient.high_risk_allergies" />

            <!-- DEMOGRAPHICS & CLINICAL SUMMARY CARD -->
            <div class="p-6 rounded-2xl bg-white border border-stone-200 shadow-2xs">
                <h3 class="text-xs font-bold uppercase tracking-wider text-stone-400 mb-4 pb-2 border-b border-stone-100">
                    Patient Demographics & Identification
                </h3>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="text-stone-400 font-medium block">Species / Breed</span>
                        <span class="font-bold text-stone-800 text-sm mt-0.5 block">
                            {{ patient.species }}
                            <span v-if="patient.breed" class="text-xs font-normal text-stone-500">({{ patient.breed }})</span>
                        </span>
                    </div>

                    <div>
                        <span class="text-stone-400 font-medium block">Gender</span>
                        <span class="font-bold text-stone-800 text-sm mt-0.5 block capitalize">
                            {{ patient.gender?.replace('_', ' ') || 'Unknown' }}
                        </span>
                    </div>

                    <div>
                        <span class="text-stone-400 font-medium block">Current Weight</span>
                        <span class="font-bold text-stone-800 text-sm mt-0.5 block">
                            {{ patient.current_weight_kg ? `${patient.current_weight_kg} kg` : 'Not recorded' }}
                        </span>
                    </div>

                    <div>
                        <span class="text-stone-400 font-medium block">Microchip #</span>
                        <span class="font-mono text-stone-700 text-sm mt-0.5 block">
                            {{ patient.microchip_number || '—' }}
                        </span>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-stone-100 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-stone-400 font-medium block">Owner / Client Information</span>
                        <span class="font-bold text-stone-900 block mt-0.5">{{ patient.client?.name || '—' }}</span>
                        <span class="text-stone-500 block">{{ patient.client?.phone || 'No phone' }} &bull; {{ patient.client?.email || 'No email' }}</span>
                    </div>

                    <div v-if="patient.notes">
                        <span class="text-stone-400 font-medium block">General Profile Notes</span>
                        <p class="text-stone-600 mt-0.5">{{ patient.notes }}</p>
                    </div>
                </div>
            </div>

            <!-- LEVEL 1 EXCLUSIVE CONTENT: CLINICAL CASES ONLY -->
            <!-- (Strictly ZERO visits, ZERO SOAP notes, and ZERO media galleries allowed here!) -->
            <div class="p-6 rounded-2xl bg-white border border-stone-200 shadow-2xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                    <div>
                        <h2 class="text-base font-bold text-stone-900">
                            Clinical Cases ({{ cases.length }})
                        </h2>
                        <p class="text-xs text-stone-500">
                            Medical problem umbrellas for this patient. Click a case to view its encounters timeline (Level 2).
                        </p>
                    </div>

                    <Link
                        :href="`/portal/cases/create?patient_id=${patient.id}`"
                        class="inline-flex items-center space-x-1.5 rtl:space-x-reverse px-3 py-1.5 rounded-lg text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Case</span>
                    </Link>
                </div>

                <div v-if="cases.length === 0" class="py-12 text-center text-stone-500">
                    <svg class="w-10 h-10 mx-auto text-stone-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-xs font-semibold">No clinical cases opened for this patient yet.</p>
                    <Link
                        :href="`/portal/cases/create?patient_id=${patient.id}`"
                        class="mt-3 inline-block px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition"
                    >
                        Create First Case
                    </Link>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="c in cases"
                        :key="c.id"
                        class="p-4 rounded-xl border border-stone-200 bg-stone-50/40 hover:border-emerald-300 hover:bg-white transition flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 group"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                <TriageBadge :priority="c.priority" />
                                <span class="text-xs font-bold text-stone-900 font-mono">{{ c.case_number }}</span>
                                <span class="text-xs text-stone-400">&bull;</span>
                                <span class="text-xs font-semibold text-stone-600 uppercase">{{ c.case_type || 'Outpatient' }}</span>
                            </div>

                            <h4 class="text-sm font-bold text-stone-900 group-hover:text-emerald-700 transition">
                                {{ c.chief_complaint }}
                            </h4>

                            <div class="flex items-center space-x-3 rtl:space-x-reverse text-[11px] text-stone-500 pt-0.5">
                                <span v-if="c.provisional_diagnosis">
                                    Dx: <strong>{{ c.provisional_diagnosis }}</strong>
                                </span>
                                <span>
                                    Vet: <strong>{{ c.primary_veterinarian ? c.primary_veterinarian.name : 'Unassigned' }}</strong>
                                </span>
                                <span>
                                    Visits: <strong>{{ c.visits_count || (c.visits ? c.visits.length : 0) }}</strong>
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2 rtl:space-x-reverse shrink-0">
                            <StatusBadge :status="c.status" />
                            <Link
                                :href="`/portal/cases/${c.id}`"
                                class="px-3.5 py-1.5 rounded-lg text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition"
                            >
                                Open Case (L2) &rarr;
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClinicLayout>
</template>
