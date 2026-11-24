<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';
import TriageBadge from '@/Components/TriageBadge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import MedicalAlertBanner from '@/Components/MedicalAlertBanner.vue';

const props = defineProps({
    visit: {
        type: Object,
        required: true,
    },
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
    visits: {
        type: Array,
        default: () => [],
    },
    diagnoses: {
        type: Array,
        default: () => [],
    },
    latestNote: {
        type: Object,
        default: () => null,
    },
    vitalHistory: {
        type: Array,
        default: () => [],
    },
    administeredDoses: {
        type: Array,
        default: () => [],
    },
    mediaImages: {
        type: Array,
        default: () => [],
    },
    products: {
        type: Array,
        default: () => [],
    },
});

const cCase = computed(() => (props.clinicalCase?.id ? props.clinicalCase : props.case || {}));
const caseStatus = computed(() => cCase.value.status?.value ?? cCase.value.status);
const patientStatus = computed(() => props.patient.status?.value ?? props.patient.status);

// 1. SOAP Examination Form
const soapForm = useForm({
    subjective: props.latestNote?.subjective || props.latestNote?.subjective_notes || '',
    objective: props.latestNote?.objective || props.latestNote?.objective_notes || '',
    assessment: props.latestNote?.assessment || props.latestNote?.assessment_notes || '',
    plan: props.latestNote?.plan || props.latestNote?.plan_notes || '',
    temperature_c: props.latestNote?.temperature_c ?? '',
    heart_rate_bpm: props.latestNote?.heart_rate_bpm ?? '',
    respiratory_rate: props.latestNote?.respiratory_rate ?? '',
    weight_kg: props.latestNote?.weight_kg ?? '',
    systolic_bp: props.latestNote?.systolic_bp ?? '',
    diastolic_bp: props.latestNote?.diastolic_bp ?? '',
});

const isSoapSaved = ref(false);
let soapSavedTimeout = null;

const saveSoap = () => {
    soapForm.post(`/portal/visits/${props.visit.id}/soap`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            soapForm.defaults();
            isSoapSaved.value = true;
            if (soapSavedTimeout) clearTimeout(soapSavedTimeout);
            soapSavedTimeout = setTimeout(() => {
                isSoapSaved.value = false;
            }, 3000);
        },
    });
};

// 2. Medication Administration (eMAR) Form
const isDoseModalOpen = ref(false);
const doseForm = useForm({
    product_id: '',
    dose_amount_base_units: '',
    route_administered: 'SC',
    clinical_notes: '',
});

const closeDose = () => {
    if (doseForm.processing) return;
    doseForm.reset();
    doseForm.clearErrors();
    isDoseModalOpen.value = false;
};

const administerDose = () => {
    doseForm.post(`/portal/visits/${props.visit.id}/dose`, {
        preserveScroll: true,
        onSuccess: () => {
            closeDose();
        },
    });
};

// 3. MinIO S3 Media Upload Form
const isMediaModalOpen = ref(false);
const mediaForm = useForm({
    image: null,
    title: '',
});

const handleFileSelect = (e) => {
    if (e.target.files.length > 0) {
        mediaForm.image = e.target.files[0];
    }
};

const closeMedia = () => {
    if (mediaForm.processing) return;
    mediaForm.reset();
    mediaForm.clearErrors();
    isMediaModalOpen.value = false;
};

const uploadMedia = () => {
    if (!mediaForm.image) return;
    mediaForm.post(`/portal/visits/${props.visit.id}/media`, {
        preserveScroll: true,
        onSuccess: () => {
            closeMedia();
        },
    });
};

// 4. Digital Signing & Locking
const isSigning = ref(false);
const signAndLock = () => {
    if (soapForm.isDirty) {
        alert(trans('You have unsaved changes in the SOAP examination form. Please save changes before signing and locking the record.'));
        return;
    }

    if (
        confirm(
            trans('Are you sure you want to digitally sign and lock this clinical visit? All examination notes and dose records will be permanently locked against future edits.')
        )
    ) {
        isSigning.value = true;
        router.post(
            `/portal/visits/${props.visit.id}/sign`,
            {},
            {
                preserveScroll: true,
                onFinish: () => (isSigning.value = false),
            }
        );
    }
};

// 5. Lightbox Preview Modal
const activePreviewImage = ref(null);

// 6. Dynamic Weight Sparkline
const weightSparkline = computed(() => {
    const weights = props.vitalHistory
        .map((n) => parseFloat(n.weight_kg))
        .filter((w) => !isNaN(w) && w > 0);

    if (weights.length < 2) return null;

    const min = Math.min(...weights);
    const max = Math.max(...weights);
    const range = max - min || 1;
    const width = 160;
    const height = 28;
    const padding = 4;

    const points = weights.map((w, idx) => {
        const x = padding + (idx / (weights.length - 1)) * (width - 2 * padding);
        const y = height - padding - ((w - min) / range) * (height - 2 * padding);
        return `${x.toFixed(1)},${y.toFixed(1)}`;
    });

    return {
        path: `M${points.join(' L')}`,
        lastX: points[points.length - 1].split(',')[0],
        lastY: points[points.length - 1].split(',')[1],
    };
});

const otherVisits = computed(() => props.visits.filter((v) => v.id !== props.visit.id));

const GENDER_MAP = {
    male: 'Male',
    female: 'Female',
    neutered_male: 'Neutered Male',
    spayed_female: 'Spayed Female',
    intact_male: 'Intact Male',
    intact_female: 'Intact Female',
};

const getGenderLabel = (gender) => {
    const val = gender?.value ?? gender;
    if (!val) return trans('Unknown');
    const normalized = String(val).toLowerCase();
    return GENDER_MAP[normalized] ? trans(GENDER_MAP[normalized]) : trans('Unknown');
};

const VISIT_TYPE_MAP = {
    routine_checkup: 'Routine Checkup',
    emergency: 'Emergency',
    surgery: 'Surgery',
    follow_up: 'Follow-up',
    vaccination: 'Vaccination',
    dental: 'Dental',
    telemedicine: 'Telemedicine',
    recheck: 'Recheck',
    exam: 'Exam',
    other: 'Other',
};

const formatVisitType = (type) => {
    if (!type) return trans('Visit');
    const normalized = String(type).toLowerCase();
    return VISIT_TYPE_MAP[normalized] ? trans(VISIT_TYPE_MAP[normalized]) : trans('Visit');
};

const DIAGNOSIS_STATUS_MAP = {
    active: 'Active',
    provisional: 'Provisional',
    confirmed: 'Confirmed',
    differential: 'Differential',
    resolved: 'Resolved',
    rule_out: 'Rule Out',
    chronic: 'Chronic',
};

const getDiagnosisStatusLabel = (status) => {
    const val = typeof status === 'object' ? (status?.value ?? 'active') : (status || 'active');
    const normalized = String(val).toLowerCase();
    return DIAGNOSIS_STATUS_MAP[normalized] ? trans(DIAGNOSIS_STATUS_MAP[normalized]) : trans('Active');
};

// Helpers
const printRecord = () => {
    window.print();
};

const formatTime = (dateStr) => {
    if (!dateStr) return '—';
    try {
        return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch {
        return dateStr;
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    try {
        return new Date(dateStr).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
    } catch {
        return dateStr;
    }
};

const handleKeyDown = (e) => {
    if (e.key === 'Escape') {
        if (activePreviewImage.value) {
            activePreviewImage.value = null;
        } else if (isDoseModalOpen.value && !doseForm.processing) {
            closeDose();
        } else if (isMediaModalOpen.value && !mediaForm.processing) {
            closeMedia();
        }
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
    if (soapSavedTimeout) clearTimeout(soapSavedTimeout);
});
</script>

<template>
    <ClinicLayout :title="$t('Encounter: :title', { title: visit.title })" :full-width="true">
        <div class="space-y-6 text-[#1b1c1a]">
            <!-- Draft Print Watermark -->
            <div v-if="!visit.is_signed" class="hidden print:block p-3 border-2 border-dashed border-amber-600 text-amber-900 text-center font-bold text-sm mb-4">
                {{ $t('DRAFT CLINICAL ENCOUNTER — NOT YET DIGITALLY SIGNED') }}
            </div>

            <!-- 1. TOP CONTEXT, BREADCRUMBS & PATIENT MASTHEAD -->
            <section class="flex flex-col gap-4">
                <!-- Breadcrumb Bar & Primary CTAs -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <nav class="flex items-center gap-2 text-xs text-stone-500 flex-wrap font-medium">
                        <Link href="/portal/patients" class="hover:text-[#00685f] transition-colors">{{ $t('Patients') }}</Link>
                        <span class="material-symbols-outlined text-[16px] text-stone-300 rtl:rotate-180" aria-hidden="true">chevron_right</span>
                        <span class="text-stone-600">{{ patient.species }}</span>
                        <span class="material-symbols-outlined text-[16px] text-stone-300 rtl:rotate-180" aria-hidden="true">chevron_right</span>
                        <Link :href="`/portal/patients/${patient.id}`" class="text-stone-800 font-semibold hover:text-[#00685f]">
                            {{ patient.name_or_tag }} (#{{ patient.microchip_number ? String(patient.microchip_number).slice(-4) : String(patient.id).slice(0, 4) }})
                        </Link>
                        <span class="material-symbols-outlined text-[16px] text-stone-300 rtl:rotate-180" aria-hidden="true">chevron_right</span>
                        <Link :href="`/portal/cases/${cCase.id}`" class="text-[#00685f] font-semibold hover:underline">
                            {{ $t('Case: :number', { number: cCase.case_number }) }}
                        </Link>
                        <span class="material-symbols-outlined text-[16px] text-stone-300 rtl:rotate-180" aria-hidden="true">chevron_right</span>
                        <span class="text-stone-900 font-bold bg-[#efeeeb] px-2 py-0.5 rounded-md">
                            {{ visit.title }}
                        </span>
                    </nav>

                    <div class="flex items-center gap-2 flex-wrap">
                        <button
                            type="button"
                            @click="printRecord"
                            class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-xl bg-white text-[#00685f] hover:bg-stone-50 border border-stone-200 shadow-xs text-xs font-semibold transition print:hidden"
                        >
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">print</span>
                            <span>{{ $t('Print Record') }}</span>
                        </button>

                        <Link
                            :href="`/portal/cases/create?patient_id=${patient.id}`"
                            class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-xl bg-white text-[#00685f] hover:bg-stone-50 border border-stone-200 shadow-xs text-xs font-semibold transition print:hidden"
                        >
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">add_circle</span>
                            <span>{{ $t('+ New Case') }}</span>
                        </Link>

                        <Link
                            v-if="caseStatus !== 'closed' && caseStatus !== 'resolved'"
                            :href="`/portal/visits/create?case_id=${cCase.id}`"
                            class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-xl bg-[#00685f] text-white hover:bg-[#005049] shadow-sm text-xs font-bold transition active:scale-95 print:hidden"
                        >
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">edit_note</span>
                            <span>{{ $t('+ New Encounter') }}</span>
                        </Link>
                    </div>
                </div>

                <!-- Critical Allergy Warning Banner (Shared Component) -->
                <MedicalAlertBanner :allergies="patient.high_risk_allergies" />

                <!-- Patient Master Card -->
                <div class="p-5 rounded-2xl bg-white border border-stone-200/90 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)] flex flex-col xl:flex-row xl:items-center justify-between gap-5">
                    <div class="flex items-start md:items-center gap-4 min-w-0">
                        <!-- Patient Avatar -->
                        <div class="relative flex-shrink-0">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-[#00685f]/15 to-[#17684e]/20 border-2 border-[#00685f]/30 flex items-center justify-center text-[#00685f] shadow-sm overflow-hidden">
                                <span class="material-symbols-outlined text-4xl" aria-hidden="true">pets</span>
                            </div>
                        </div>

                        <!-- Demographics Info -->
                        <div class="flex flex-col min-w-0 gap-1">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h1 class="text-xl sm:text-2xl font-black text-stone-900 tracking-tight">
                                    {{ patient.name_or_tag }}
                                </h1>
                                <span class="px-2.5 py-0.5 rounded-full bg-[#efeeeb] text-xs font-mono font-bold text-stone-700">
                                    #{{ patient.microchip_number ? String(patient.microchip_number).slice(-6) : String(patient.id).slice(0, 6) }}
                                </span>
                                <span v-if="patientStatus" class="px-2.5 py-0.5 rounded-full bg-[#a6f2d1] text-[#002116] text-[11px] font-bold flex items-center gap-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#17684e]"></span>
                                    {{ String(patientStatus).toUpperCase() }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full bg-sky-50 text-sky-800 border border-sky-200 text-[11px] font-bold">
                                    {{ $t('Level 3 Encounter Workspace') }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2 text-xs sm:text-sm text-stone-600 flex-wrap">
                                <span class="font-semibold text-stone-800">{{ patient.species }}</span>
                                <span>•</span>
                                <span class="text-stone-700">{{ patient.breed ?? $t('Breed not recorded') }}</span>
                                <span>•</span>
                                <span>{{ patient.age ? $t(':age yrs', { age: patient.age }) : (patient.date_of_birth ? formatDate(patient.date_of_birth) : $t('Age N/A')) }}</span>
                                <span>•</span>
                                <span class="capitalize">{{ getGenderLabel(patient.gender) }}</span>
                                <span>•</span>
                                <span class="font-extrabold text-[#00685f]">
                                    {{ patient.current_weight_kg ? $t(':weight kg', { weight: patient.current_weight_kg }) : $t('Weight N/A') }}
                                </span>
                            </div>

                            <div class="flex items-center gap-4 text-[11px] text-stone-500 mt-0.5 flex-wrap font-medium">
                                <span class="inline-flex items-center gap-1 font-mono">
                                    <span class="material-symbols-outlined text-[15px] text-stone-400" aria-hidden="true">fingerprint</span>
                                    {{ $t('Microchip:') }} <strong>{{ patient.microchip_number || $t('Not chipped') }}</strong>
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[15px] text-[#17684e]" aria-hidden="true">health_and_safety</span>
                                    {{ $t('Encounter:') }} <strong class="text-stone-700">{{ visit.is_signed ? $t('Finalized & Locked') : $t('In Progress') }}</strong>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Owner / Pet Parent Card -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 p-3.5 rounded-xl bg-[#f5f3f0] border border-stone-200/70 xl:w-auto w-full">
                        <div class="flex flex-col min-w-[190px]">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-stone-400">
                                {{ $t('Primary Pet Parent') }}
                            </span>
                            <span class="text-xs font-bold text-stone-900 mt-0.5">
                                {{ patient.client?.name ?? $t('No client on file') }}
                            </span>
                            <span class="text-[11px] text-stone-500 font-mono">
                                {{ patient.client?.phone || $t('No phone recorded') }}
                            </span>
                        </div>

                        <div class="flex sm:flex-col gap-2 w-full sm:w-auto">
                            <a
                                v-if="patient.client?.phone"
                                :href="`tel:${patient.client.phone}`"
                                class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg bg-white text-[#00685f] hover:bg-stone-50 border border-stone-200 text-xs font-bold shadow-2xs transition"
                            >
                                <span class="material-symbols-outlined text-[15px]" aria-hidden="true">call</span>
                                <span>{{ $t('Call') }}</span>
                            </a>
                            <Link
                                v-if="patient.client?.id"
                                :href="`/portal/clients/${patient.client.id}`"
                                class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg bg-white text-stone-700 hover:bg-stone-50 border border-stone-200 text-xs font-semibold shadow-2xs transition"
                            >
                                <span class="material-symbols-outlined text-[15px]" aria-hidden="true">person</span>
                                <span>{{ $t('Account') }}</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. MAIN 3-COLUMN CLINICAL HIERARCHY GRID -->
            <section class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
                <!-- ========================================================
                     LEFT COLUMN (3 cols desktop): Telemetry & Baseline
                     ======================================================== -->
                <div class="xl:col-span-3 flex flex-col gap-6">
                    <!-- Recorded Vital Signs Card (Values from persisted note only) -->
                    <div class="p-5 rounded-2xl bg-white border border-stone-200/90 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)] flex flex-col gap-4">
                        <div class="flex items-center justify-between pb-2 border-b border-stone-100">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[#00685f] text-[20px]" aria-hidden="true">ecg_heart</span>
                                <h3 class="text-sm font-extrabold text-stone-900">{{ $t('Recorded Vitals') }}</h3>
                            </div>
                            <span class="text-[11px] text-stone-400 font-medium">
                                {{ latestNote?.created_at ? formatTime(latestNote.created_at) : $t('No exam yet') }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5">
                            <!-- Temp -->
                            <div class="p-3 rounded-xl bg-[#f5f3f0] flex flex-col">
                                <span class="text-[10px] font-bold text-stone-500 uppercase">{{ $t('Temp') }}</span>
                                <div class="flex items-baseline gap-1 mt-0.5">
                                    <span class="text-lg font-black text-stone-900 font-mono">
                                        {{ latestNote?.temperature_c != null ? latestNote.temperature_c : '—' }}
                                    </span>
                                    <span v-if="latestNote?.temperature_c != null" class="text-[10px] text-stone-500">°C</span>
                                </div>
                            </div>

                            <!-- Heart Rate -->
                            <div class="p-3 rounded-xl bg-[#f5f3f0] flex flex-col">
                                <span class="text-[10px] font-bold text-stone-500 uppercase">{{ $t('Heart Rate') }}</span>
                                <div class="flex items-baseline gap-1 mt-0.5">
                                    <span class="text-lg font-black text-stone-900 font-mono">
                                        {{ latestNote?.heart_rate_bpm != null ? latestNote.heart_rate_bpm : '—' }}
                                    </span>
                                    <span v-if="latestNote?.heart_rate_bpm != null" class="text-[10px] text-stone-500">bpm</span>
                                </div>
                            </div>

                            <!-- Respiratory Rate -->
                            <div class="p-3 rounded-xl bg-[#f5f3f0] flex flex-col">
                                <span class="text-[10px] font-bold text-stone-500 uppercase">{{ $t('Resp Rate') }}</span>
                                <div class="flex items-baseline gap-1 mt-0.5">
                                    <span class="text-lg font-black text-stone-900 font-mono">
                                        {{ latestNote?.respiratory_rate != null ? latestNote.respiratory_rate : '—' }}
                                    </span>
                                    <span v-if="latestNote?.respiratory_rate != null" class="text-[10px] text-stone-500">bpm</span>
                                </div>
                            </div>

                            <!-- Blood Pressure -->
                            <div class="p-3 rounded-xl bg-[#f5f3f0] flex flex-col">
                                <span class="text-[10px] font-bold text-stone-500 uppercase">{{ $t('Blood Pressure') }}</span>
                                <div class="flex items-baseline gap-1 mt-0.5">
                                    <span class="text-sm font-black text-stone-900 font-mono">
                                        {{ latestNote?.systolic_bp != null ? `${latestNote.systolic_bp}/${latestNote.diastolic_bp ?? '—'}` : '—' }}
                                    </span>
                                    <span v-if="latestNote?.systolic_bp != null" class="text-[10px] text-stone-500">mmHg</span>
                                </div>
                            </div>
                        </div>

                        <!-- Weight History & Dynamic Sparkline -->
                        <div class="p-3.5 rounded-xl bg-[#f5f3f0] flex flex-col gap-1.5 mt-1 border border-stone-200/50">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-stone-700">{{ $t('Weight Record') }}</span>
                                <span class="text-[10px] font-bold text-[#17684e] bg-[#a6f2d1] px-1.5 py-0.5 rounded">
                                    {{ latestNote?.weight_kg ? $t(':weight kg', { weight: latestNote.weight_kg }) : (patient.current_weight_kg ? $t(':weight kg', { weight: patient.current_weight_kg }) : $t('Not recorded')) }}
                                </span>
                            </div>

                            <!-- Dynamic SVG Sparkline if 2+ historical points exist -->
                            <div v-if="weightSparkline" class="h-9 w-full flex items-end pt-1">
                                <svg class="w-full h-7 overflow-visible text-[#00685f]" fill="none" viewBox="0 0 160 28">
                                    <path
                                        :d="weightSparkline.path"
                                        stroke="currentColor"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2.5"
                                    ></path>
                                    <circle class="fill-[#00685f]" :cx="weightSparkline.lastX" :cy="weightSparkline.lastY" r="3.5"></circle>
                                </svg>
                            </div>
                            <span v-if="vitalHistory.length > 0" class="text-[10px] text-stone-400 text-end">
                                {{ $t(':count recorded reading(s)', { count: vitalHistory.length }) }}
                            </span>
                        </div>
                    </div>

                    <!-- Medical Problem Diagnoses List -->
                    <div class="p-5 rounded-2xl bg-white border border-stone-200/90 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)] flex flex-col gap-3">
                        <div class="flex items-center justify-between pb-2 border-b border-stone-100">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[#00685f] text-[18px]" aria-hidden="true">fact_check</span>
                                <h3 class="text-sm font-extrabold text-stone-900">{{ $t('Problem List') }}</h3>
                            </div>
                            <span class="text-[10px] font-bold text-stone-500 bg-stone-100 px-2 py-0.5 rounded-full">
                                {{ $t(':count Diagnoses', { count: diagnoses.length }) }}
                            </span>
                        </div>

                        <div v-if="diagnoses.length === 0" class="py-4 text-center text-xs text-stone-400">
                            {{ $t('No additional diagnoses recorded on case yet.') }}
                        </div>

                        <div v-else class="space-y-2 text-xs">
                            <div
                                v-for="d in diagnoses"
                                :key="d.id"
                                class="p-2.5 rounded-xl bg-[#f5f3f0] flex items-center justify-between"
                            >
                                <span class="font-bold text-stone-800">{{ d.diagnosis }}</span>
                                <span class="text-[10px] font-semibold text-stone-500 capitalize bg-white px-2 py-0.5 rounded border border-stone-200">
                                    {{ getDiagnosisStatusLabel(d.status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Patient Medical History & Notes -->
                    <div v-if="patient.notes || patient.history" class="p-5 rounded-2xl bg-white border border-stone-200/90 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)] flex flex-col gap-3">
                        <h3 class="text-sm font-extrabold text-stone-900 pb-2 border-b border-stone-100">
                            {{ $t('Patient Baseline Notes') }}
                        </h3>
                        <p v-if="patient.history" class="text-xs text-stone-700 leading-relaxed bg-[#f5f3f0] p-3 rounded-xl border border-stone-200/60">
                            <strong class="block text-[10px] uppercase text-stone-400 mb-0.5">{{ $t('Clinical History:') }}</strong>
                            {{ patient.history }}
                        </p>
                        <p v-if="patient.notes" class="text-xs text-stone-700 leading-relaxed bg-[#f5f3f0] p-3 rounded-xl border border-stone-200/60">
                            <strong class="block text-[10px] uppercase text-stone-400 mb-0.5">{{ $t('General Care Notes:') }}</strong>
                            {{ patient.notes }}
                        </p>
                    </div>

                    <!-- Attending Veterinarian -->
                    <div class="p-5 rounded-2xl bg-white border border-stone-200/90 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)] flex flex-col gap-3">
                        <h3 class="text-sm font-extrabold text-stone-900 pb-2 border-b border-stone-100">
                            {{ $t('Attending Medical Staff') }}
                        </h3>

                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#00685f]/10 text-[#00685f] font-black flex items-center justify-center text-sm border border-[#00685f]/20">
                                {{ (cCase.primary_veterinarian?.name || 'V').charAt(0).toUpperCase() }}
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="text-xs font-bold text-stone-900 truncate">
                                    {{ cCase.primary_veterinarian?.name ?? $t('Unassigned Attending Vet') }}
                                </span>
                                <span class="text-[11px] font-semibold text-[#00685f]">{{ $t('Case Clinician') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================
                     CENTER COLUMN (6 cols desktop): Clinical Case Hub & Encounters
                     ======================================================== -->
                <div class="xl:col-span-6 flex flex-col gap-6">
                    <!-- Case Master Card (Tier 2 Problem Umbrella) -->
                    <div class="p-5 rounded-2xl bg-white border border-stone-200/90 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)] flex flex-col gap-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-stone-100">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-teal-50 text-teal-800 border border-teal-200">
                                    {{ $t('Level 2 Case') }}
                                </span>
                                <h2 class="text-base font-black text-stone-900 font-mono tracking-tight">
                                    {{ cCase.case_number }}
                                </h2>
                                <TriageBadge v-if="cCase.priority" :priority="cCase.priority" />
                                <StatusBadge v-if="caseStatus" :status="caseStatus" />
                            </div>

                            <Link
                                :href="`/portal/cases/${cCase.id}/edit`"
                                class="text-xs font-bold text-[#00685f] hover:underline inline-flex items-center gap-1"
                            >
                                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">edit</span>
                                <span>{{ $t('Edit Case') }}</span>
                            </Link>
                        </div>

                        <!-- Chief Complaint -->
                        <div class="space-y-1">
                            <span class="text-[11px] font-bold text-stone-500 uppercase tracking-wider block">
                                {{ $t('Chief Medical Complaint') }}
                            </span>
                            <p class="text-xs sm:text-sm text-stone-900 font-medium bg-[#f5f3f0] p-3 rounded-xl border border-stone-200/60 leading-relaxed">
                                {{ cCase.chief_complaint || $t('No complaint details provided.') }}
                            </p>
                        </div>

                        <!-- Provisional & Confirmed Diagnoses -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <div class="p-3 rounded-xl bg-amber-50/70 border border-amber-200/70 flex flex-col">
                                <span class="text-[10px] font-bold uppercase text-amber-800 tracking-wider">{{ $t('Provisional Diagnosis') }}</span>
                                <p class="text-xs font-bold text-stone-900 mt-1">
                                    {{ cCase.provisional_diagnosis || $t('Differential diagnosis pending diagnostic workup.') }}
                                </p>
                            </div>

                            <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-200/70 flex flex-col">
                                <span class="text-[10px] font-bold uppercase text-emerald-800 tracking-wider">{{ $t('Final Confirmed Diagnosis') }}</span>
                                <p class="text-xs font-bold text-stone-900 mt-1">
                                    {{ cCase.final_diagnosis || $t('Awaiting attending clinician sign-off.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Diagnostic Imagery Gallery (This Encounter Workspace - Level 3 S3 Assets) -->
                    <div class="p-5 rounded-2xl bg-white border border-stone-200/90 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)] flex flex-col gap-4">
                        <div class="flex items-center justify-between pb-2 border-b border-stone-100">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[#00685f] text-[20px]" aria-hidden="true">radiology</span>
                                <h3 class="text-sm font-extrabold text-stone-900">{{ $t('Encounter Diagnostic Media & Radiographs (S3)') }}</h3>
                            </div>

                            <button
                                v-if="!visit.is_signed"
                                type="button"
                                @click="isMediaModalOpen = true"
                                class="inline-flex items-center gap-1 text-xs font-bold text-[#00685f] hover:underline"
                            >
                                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">upload_file</span>
                                <span>{{ $t('Upload DICOM / Media') }}</span>
                            </button>
                        </div>

                        <!-- Empty state -->
                        <div v-if="mediaImages.length === 0" class="py-8 text-center text-xs text-stone-400 bg-[#f5f3f0] rounded-xl border border-dashed border-stone-300">
                            <span class="material-symbols-outlined text-3xl text-stone-300 mb-1 block" aria-hidden="true">image_not_supported</span>
                            {{ $t('No diagnostic radiographs or ultrasound images uploaded for this visit encounter yet.') }}
                        </div>

                        <!-- Media Gallery Grid -->
                        <div v-else class="grid grid-cols-2 gap-3">
                            <button
                                v-for="img in mediaImages"
                                :key="img.id"
                                type="button"
                                @click="activePreviewImage = img"
                                :aria-label="img.title || $t('Diagnostic Media')"
                                class="group relative rounded-xl overflow-hidden bg-black aspect-video cursor-pointer border border-stone-200 shadow-xs text-left w-full focus:outline-none focus:ring-2 focus:ring-[#00685f]"
                            >
                                <img
                                    :src="img.file_url || (img.file_path ? `/storage/${img.file_path}` : '')"
                                    :alt="img.title || $t('Diagnostic Media')"
                                    class="w-full h-full object-cover opacity-85 group-hover:opacity-100 group-hover:scale-105 transition-all duration-300"
                                    loading="lazy"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/20 to-transparent"></div>
                                <div class="absolute bottom-2 left-2.5 right-2.5 flex items-center justify-between text-white text-xs">
                                    <span class="font-bold truncate max-w-[70%]">{{ img.title || $t('Diagnostic Study') }}</span>
                                    <span class="text-[10px] bg-black/60 px-1.5 py-0.5 rounded font-mono">
                                        {{ formatDate(img.created_at) }}
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Associated Encounters & Clinical Visits (Tier 3) -->
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[#00685f] text-[22px]" aria-hidden="true">event_note</span>
                                <h3 class="text-sm font-extrabold text-stone-900">{{ $t('Encounter Workspace') }}</h3>
                            </div>

                            <Link
                                v-if="!visit.is_signed"
                                :href="`/portal/visits/${visit.id}/edit`"
                                class="px-2.5 py-1 rounded-lg text-xs font-bold text-stone-700 bg-stone-100 hover:bg-stone-200 transition"
                            >
                                {{ $t('Edit Metadata') }}
                            </Link>
                        </div>

                        <!-- ACTIVE VISIT with Interactive SOAP Note -->
                        <div class="p-5 rounded-2xl bg-white border-2 border-[#00685f]/30 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.08)] flex flex-col gap-4 ring-4 ring-[#00685f]/5">
                            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-stone-100">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="px-2.5 py-0.5 rounded-full bg-[#00685f] text-white text-[11px] font-black uppercase tracking-wider">
                                        {{ $t('Current Encounter') }}
                                    </span>
                                    <h4 class="text-base font-extrabold text-stone-900">
                                        {{ visit.title }}
                                    </h4>
                                    <span class="text-xs font-semibold text-stone-500 capitalize">
                                        ({{ formatVisitType(visit.visit_type) }})
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="visit.is_signed"
                                        class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-900 border border-emerald-300 text-xs font-bold inline-flex items-center gap-1"
                                    >
                                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">verified</span>
                                        {{ $t('Signed by :name', { name: visit.signed_by || $t('Doctor') }) }}
                                    </span>
                                    <span
                                        v-else
                                        class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-900 border border-amber-200 text-xs font-bold"
                                    >
                                        {{ $t('In Progress') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Interactive SOAP Form -->
                            <form @submit.prevent="saveSoap" class="space-y-4">
                                <!-- Vital Signs Quick Input Bar -->
                                <div class="p-3.5 rounded-xl bg-[#f5f3f0] border border-stone-200/80 space-y-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-stone-500 block">
                                        {{ $t('Encounter Examination Vital Signs Entry') }}
                                    </span>

                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2 text-xs">
                                        <div>
                                            <label for="temperature_c" class="block text-[10px] font-bold text-stone-600">{{ $t('Temp (°C)') }}</label>
                                            <input
                                                id="temperature_c"
                                                v-model="soapForm.temperature_c"
                                                :disabled="visit.is_signed"
                                                type="number"
                                                step="0.1"
                                                placeholder="38.5"
                                                class="mt-1 w-full rounded-lg border border-stone-300 bg-white px-2 py-1.5 text-xs text-stone-900 focus:border-[#00685f] focus:outline-none disabled:bg-stone-100 font-mono"
                                            />
                                            <p v-if="soapForm.errors.temperature_c" class="text-[10px] text-rose-600 font-semibold mt-0.5">{{ soapForm.errors.temperature_c }}</p>
                                        </div>

                                        <div>
                                            <label for="heart_rate_bpm" class="block text-[10px] font-bold text-stone-600">{{ $t('Heart Rate') }}</label>
                                            <input
                                                id="heart_rate_bpm"
                                                v-model="soapForm.heart_rate_bpm"
                                                :disabled="visit.is_signed"
                                                type="number"
                                                placeholder="110"
                                                class="mt-1 w-full rounded-lg border border-stone-300 bg-white px-2 py-1.5 text-xs text-stone-900 focus:border-[#00685f] focus:outline-none disabled:bg-stone-100 font-mono"
                                            />
                                            <p v-if="soapForm.errors.heart_rate_bpm" class="text-[10px] text-rose-600 font-semibold mt-0.5">{{ soapForm.errors.heart_rate_bpm }}</p>
                                        </div>

                                        <div>
                                            <label for="respiratory_rate" class="block text-[10px] font-bold text-stone-600">{{ $t('Resp Rate') }}</label>
                                            <input
                                                id="respiratory_rate"
                                                v-model="soapForm.respiratory_rate"
                                                :disabled="visit.is_signed"
                                                type="number"
                                                placeholder="24"
                                                class="mt-1 w-full rounded-lg border border-stone-300 bg-white px-2 py-1.5 text-xs text-stone-900 focus:border-[#00685f] focus:outline-none disabled:bg-stone-100 font-mono"
                                            />
                                            <p v-if="soapForm.errors.respiratory_rate" class="text-[10px] text-rose-600 font-semibold mt-0.5">{{ soapForm.errors.respiratory_rate }}</p>
                                        </div>

                                        <div>
                                            <label for="weight_kg" class="block text-[10px] font-bold text-stone-600">{{ $t('Weight (kg)') }}</label>
                                            <input
                                                id="weight_kg"
                                                v-model="soapForm.weight_kg"
                                                :disabled="visit.is_signed"
                                                type="number"
                                                step="0.01"
                                                :placeholder="patient.current_weight_kg ? String(patient.current_weight_kg) : '28.4'"
                                                class="mt-1 w-full rounded-lg border border-stone-300 bg-white px-2 py-1.5 text-xs text-stone-900 focus:border-[#00685f] focus:outline-none disabled:bg-stone-100 font-mono"
                                            />
                                            <p v-if="soapForm.errors.weight_kg" class="text-[10px] text-rose-600 font-semibold mt-0.5">{{ soapForm.errors.weight_kg }}</p>
                                        </div>

                                        <div>
                                            <label for="systolic_bp" class="block text-[10px] font-bold text-stone-600">{{ $t('Systolic BP') }}</label>
                                            <input
                                                id="systolic_bp"
                                                v-model="soapForm.systolic_bp"
                                                :disabled="visit.is_signed"
                                                type="number"
                                                placeholder="120"
                                                class="mt-1 w-full rounded-lg border border-stone-300 bg-white px-2 py-1.5 text-xs text-stone-900 focus:border-[#00685f] focus:outline-none disabled:bg-stone-100 font-mono"
                                            />
                                            <p v-if="soapForm.errors.systolic_bp" class="text-[10px] text-rose-600 font-semibold mt-0.5">{{ soapForm.errors.systolic_bp }}</p>
                                        </div>

                                        <div>
                                            <label for="diastolic_bp" class="block text-[10px] font-bold text-stone-600">{{ $t('Diastolic BP') }}</label>
                                            <input
                                                id="diastolic_bp"
                                                v-model="soapForm.diastolic_bp"
                                                :disabled="visit.is_signed"
                                                type="number"
                                                placeholder="75"
                                                class="mt-1 w-full rounded-lg border border-stone-300 bg-white px-2 py-1.5 text-xs text-stone-900 focus:border-[#00685f] focus:outline-none disabled:bg-stone-100 font-mono"
                                            />
                                            <p v-if="soapForm.errors.diastolic_bp" class="text-[10px] text-rose-600 font-semibold mt-0.5">{{ soapForm.errors.diastolic_bp }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Subjective (S) -->
                                <div class="p-3.5 rounded-xl bg-[#f5f3f0] border border-stone-200/60 flex flex-col gap-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="h-6 w-6 rounded-md bg-[#00685f] text-white flex items-center justify-center text-xs font-bold">
                                            S
                                        </span>
                                        <label for="subjective" class="text-xs font-bold text-stone-800 uppercase tracking-wide">
                                            {{ $t('Subjective Observations & History') }}
                                        </label>
                                    </div>
                                    <textarea
                                        id="subjective"
                                        v-model="soapForm.subjective"
                                        :disabled="visit.is_signed"
                                        rows="2"
                                        :placeholder="$t('Patient demeanor, appetite, water intake, owner observations...')"
                                        class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs sm:text-sm text-stone-900 focus:border-[#00685f] focus:outline-none disabled:bg-stone-100 leading-relaxed"
                                    ></textarea>
                                    <p v-if="soapForm.errors.subjective" class="text-xs text-rose-600 font-semibold mt-1">{{ soapForm.errors.subjective }}</p>
                                </div>

                                <!-- Objective (O) -->
                                <div class="p-3.5 rounded-xl bg-[#f5f3f0] border border-stone-200/60 flex flex-col gap-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="h-6 w-6 rounded-md bg-[#00685f] text-white flex items-center justify-center text-xs font-bold">
                                            O
                                        </span>
                                        <label for="objective" class="text-xs font-bold text-stone-800 uppercase tracking-wide">
                                            {{ $t('Objective Physical Examination Findings') }}
                                        </label>
                                    </div>
                                    <textarea
                                        id="objective"
                                        v-model="soapForm.objective"
                                        :disabled="visit.is_signed"
                                        rows="2"
                                        :placeholder="$t('Auscultation, palpation, mucous membranes, CRT, gait evaluation...')"
                                        class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs sm:text-sm text-stone-900 focus:border-[#00685f] focus:outline-none disabled:bg-stone-100 leading-relaxed"
                                    ></textarea>
                                    <p v-if="soapForm.errors.objective" class="text-xs text-rose-600 font-semibold mt-1">{{ soapForm.errors.objective }}</p>
                                </div>

                                <!-- Assessment (A) -->
                                <div class="p-3.5 rounded-xl bg-[#f5f3f0] border border-stone-200/60 flex flex-col gap-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="h-6 w-6 rounded-md bg-[#a53b29] text-white flex items-center justify-center text-xs font-bold">
                                            A
                                        </span>
                                        <label for="assessment" class="text-xs font-bold text-stone-800 uppercase tracking-wide">
                                            {{ $t('Assessment & Differential Evaluation') }}
                                        </label>
                                    </div>
                                    <textarea
                                        id="assessment"
                                        v-model="soapForm.assessment"
                                        :disabled="visit.is_signed"
                                        rows="2"
                                        :placeholder="$t('Clinical impression, diagnosis evaluation, treatment response...')"
                                        class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs sm:text-sm text-stone-900 focus:border-[#00685f] focus:outline-none disabled:bg-stone-100 leading-relaxed"
                                    ></textarea>
                                    <p v-if="soapForm.errors.assessment" class="text-xs text-rose-600 font-semibold mt-1">{{ soapForm.errors.assessment }}</p>
                                </div>

                                <!-- Plan (P) -->
                                <div class="p-3.5 rounded-xl bg-[#f5f3f0] border border-stone-200/60 flex flex-col gap-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="h-6 w-6 rounded-md bg-[#17684e] text-white flex items-center justify-center text-xs font-bold">
                                            P
                                        </span>
                                        <label for="plan" class="text-xs font-bold text-stone-800 uppercase tracking-wide">
                                            {{ $t('Plan & Medical Protocol') }}
                                        </label>
                                    </div>
                                    <textarea
                                        id="plan"
                                        v-model="soapForm.plan"
                                        :disabled="visit.is_signed"
                                        rows="2"
                                        :placeholder="$t('Medications, dosing protocols, client communication, recheck schedule...')"
                                        class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs sm:text-sm text-stone-900 focus:border-[#00685f] focus:outline-none disabled:bg-stone-100 leading-relaxed"
                                    ></textarea>
                                    <p v-if="soapForm.errors.plan" class="text-xs text-rose-600 font-semibold mt-1">{{ soapForm.errors.plan }}</p>
                                </div>

                                <!-- Action Buttons -->
                                <div class="pt-2 flex flex-wrap items-center justify-between gap-3">
                                    <span v-if="isSoapSaved" class="text-xs font-bold text-[#17684e] flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">check_circle</span>
                                        {{ $t('SOAP notes saved successfully!') }}
                                    </span>
                                    <span v-else class="text-xs text-stone-400">
                                        {{ $t('Ensure clinical accuracy prior to signing and locking.') }}
                                    </span>

                                    <div class="flex items-center gap-2 ms-auto">
                                        <button
                                            v-if="!visit.is_signed"
                                            type="submit"
                                            :disabled="soapForm.processing"
                                            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-[#00685f] hover:bg-[#005049] shadow-sm transition active:scale-95 disabled:opacity-50"
                                        >
                                            {{ soapForm.processing ? $t('Saving...') : $t('Save SOAP Examination') }}
                                        </button>

                                        <button
                                            v-if="!visit.is_signed"
                                            type="button"
                                            @click="signAndLock"
                                            :disabled="isSigning || soapForm.processing || doseForm.processing || mediaForm.processing"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-stone-900 hover:bg-black shadow-sm transition active:scale-95 disabled:opacity-50"
                                        >
                                            <span class="material-symbols-outlined text-[16px] text-emerald-400" aria-hidden="true">lock</span>
                                            <span>{{ isSigning ? $t('Signing...') : $t('Sign & Lock Record') }}</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Previous Visits Timeline -->
                        <div v-if="otherVisits.length > 0" class="space-y-3 pt-2">
                            <span class="text-xs font-bold text-stone-400 uppercase tracking-wider block">
                                {{ $t('Other Encounters Under This Case') }}
                            </span>

                            <div class="space-y-2">
                                <Link
                                    v-for="v in otherVisits"
                                    :key="v.id"
                                    :href="`/portal/visits/${v.id}`"
                                    class="p-3.5 rounded-xl bg-white border border-stone-200/80 hover:border-[#00685f] hover:shadow-xs transition flex items-center justify-between gap-3 group"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-stone-100 text-stone-600 group-hover:bg-[#00685f]/10 group-hover:text-[#00685f] flex items-center justify-center transition">
                                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">history_edu</span>
                                        </div>
                                        <div>
                                            <h5 class="text-xs font-bold text-stone-900 group-hover:text-[#00685f]">
                                                {{ v.title }}
                                            </h5>
                                            <span class="text-[11px] text-stone-500 font-mono">
                                                {{ formatDate(v.visit_date || v.created_at) }} • {{ formatVisitType(v.visit_type) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span v-if="v.is_signed" class="text-[10px] font-bold text-[#17684e] bg-[#a6f2d1] px-2 py-0.5 rounded-full flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[12px]" aria-hidden="true">lock</span>
                                            {{ $t('Signed') }}
                                        </span>
                                        <span class="text-xs font-bold text-stone-400 group-hover:text-[#00685f] rtl:rotate-180">
                                            &rarr;
                                        </span>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================
                     RIGHT COLUMN (3 cols desktop): Orders & eMAR
                     ======================================================== -->
                <div class="xl:col-span-3 flex flex-col gap-6">
                    <!-- Fast Order Launcher -->
                    <div v-if="!visit.is_signed" class="p-5 rounded-2xl bg-white border border-stone-200/90 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)] flex flex-col gap-3">
                        <span class="text-sm font-extrabold text-stone-900">{{ $t('Quick Clinical Order') }}</span>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <button
                                type="button"
                                @click="isDoseModalOpen = true"
                                class="h-12 rounded-xl bg-[#f5f3f0] hover:bg-[#00685f] hover:text-white text-stone-800 font-bold flex items-center justify-center gap-1.5 transition-all shadow-2xs group"
                            >
                                <span class="material-symbols-outlined text-[18px] text-[#00685f] group-hover:text-white" aria-hidden="true">medication</span>
                                <span>{{ $t('+ Rx Med') }}</span>
                            </button>

                            <button
                                type="button"
                                @click="isMediaModalOpen = true"
                                class="h-12 rounded-xl bg-[#f5f3f0] hover:bg-[#00685f] hover:text-white text-stone-800 font-bold flex items-center justify-center gap-1.5 transition-all shadow-2xs group"
                            >
                                <span class="material-symbols-outlined text-[18px] text-[#00685f] group-hover:text-white" aria-hidden="true">radiology</span>
                                <span>{{ $t('+ Imaging') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- eMAR Clinic Administration Log (Today) with Stock FEFO Deduction -->
                    <div class="p-5 rounded-2xl bg-white border border-stone-200/90 shadow-[0_4px_16px_-2px_rgba(120,113,108,0.06)] flex flex-col gap-3">
                        <div class="flex items-center justify-between pb-2 border-b border-stone-100">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[#00685f] text-[20px]" aria-hidden="true">vaccines</span>
                                <h3 class="text-sm font-extrabold text-stone-900">{{ $t('eMAR Daily Log') }}</h3>
                            </div>
                            <button
                                v-if="!visit.is_signed"
                                type="button"
                                @click="isDoseModalOpen = true"
                                class="text-xs font-bold text-[#00685f] hover:underline"
                            >
                                {{ $t('+ Administer') }}
                            </button>
                        </div>

                        <!-- Empty state -->
                        <div v-if="administeredDoses.length === 0" class="py-6 text-center text-xs text-stone-400">
                            {{ $t('No medications administered during this visit yet.') }}
                        </div>

                        <!-- Doses List -->
                        <div v-else class="space-y-2 text-xs">
                            <div
                                v-for="d in administeredDoses"
                                :key="d.id"
                                class="p-2.5 rounded-xl bg-[#f5f3f0] flex flex-col gap-0.5 border border-stone-200/60"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-stone-900">{{ d.product?.name ?? $t('Unknown Medication') }}</span>
                                    <span class="text-[11px] font-bold text-[#17684e] font-mono">
                                        {{ formatTime(d.created_at) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-[11px] text-stone-500">
                                    <span class="font-mono font-semibold text-[#00685f]">
                                        {{ d.dose_amount_base_units }} {{ d.product?.base_unit || '' }} ({{ d.route_administered ?? '—' }})
                                    </span>
                                    <span>{{ d.administered_by?.name || $t('Staff') }}</span>
                                </div>
                                <span v-if="d.clinical_notes" class="text-[10px] text-stone-400 italic">
                                    {{ d.clinical_notes }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- MODAL: ADMINISTER MEDICATION DOSE (eMAR) -->
        <div
            v-if="isDoseModalOpen && !visit.is_signed"
            role="dialog"
            aria-modal="true"
            aria-labelledby="dose-modal-title"
            class="fixed inset-0 z-50 bg-stone-900/50 backdrop-blur-xs flex items-center justify-center p-4 animate-fade-in"
            @click.self="closeDose"
        >
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-stone-200 p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#00685f] text-2xl" aria-hidden="true">vaccines</span>
                        <h3 id="dose-modal-title" class="text-base font-extrabold text-stone-900">{{ $t('Administer Medication Dose') }}</h3>
                    </div>
                    <button type="button" @click="closeDose" :aria-label="$t('Close modal')" class="text-stone-400 hover:text-stone-700">
                        <span class="material-symbols-outlined text-xl" aria-hidden="true">close</span>
                    </button>
                </div>

                <!-- Allergy Warning inside Dose Modal -->
                <MedicalAlertBanner :allergies="patient.high_risk_allergies" />

                <form @submit.prevent="administerDose" class="space-y-4">
                    <div>
                        <label for="modal_product_id" class="block text-xs font-bold text-stone-700">{{ $t('Select Pharmaceutical Product *') }}</label>
                        <select
                            id="modal_product_id"
                            v-model="doseForm.product_id"
                            required
                            class="mt-1 w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-900 focus:border-[#00685f] focus:outline-none"
                        >
                            <option value="" disabled>{{ $t('Select medication from pharmacy...') }}</option>
                            <option
                                v-for="p in products"
                                :key="p.id"
                                :value="p.id"
                                :disabled="(p.total_stock ?? p.current_stock ?? 0) <= 0"
                            >
                                {{ p.name }} ({{ $t(':qty :unit available', { qty: p.total_stock ?? p.current_stock ?? 0, unit: p.base_unit }) }}){{ (p.total_stock ?? p.current_stock ?? 0) <= 0 ? ' - ' + $t('Out of Stock') : '' }}
                            </option>
                        </select>
                        <p v-if="doseForm.errors.product_id" class="text-xs text-rose-600 mt-1 font-semibold">
                            {{ doseForm.errors.product_id }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="modal_dose_amount" class="block text-xs font-bold text-stone-700">{{ $t('Dose Quantity *') }}</label>
                            <input
                                id="modal_dose_amount"
                                v-model="doseForm.dose_amount_base_units"
                                type="number"
                                step="0.0001"
                                required
                                :placeholder="$t('e.g. 2.5000')"
                                class="mt-1 w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-900 focus:border-[#00685f] focus:outline-none font-mono"
                            />
                            <p v-if="doseForm.errors.dose_amount_base_units" class="text-xs text-rose-600 mt-1 font-semibold">
                                {{ doseForm.errors.dose_amount_base_units }}
                            </p>
                        </div>

                        <div>
                            <label for="modal_route" class="block text-xs font-bold text-stone-700">{{ $t('Administration Route') }}</label>
                            <select
                                id="modal_route"
                                v-model="doseForm.route_administered"
                                class="mt-1 w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-900 focus:border-[#00685f] focus:outline-none"
                            >
                                <option value="SC">{{ $t('Subcutaneous (SC)') }}</option>
                                <option value="IV">{{ $t('Intravenous (IV)') }}</option>
                                <option value="IM">{{ $t('Intramuscular (IM)') }}</option>
                                <option value="PO">{{ $t('Oral (PO)') }}</option>
                                <option value="Topical">{{ $t('Topical') }}</option>
                                <option value="Inhalation">{{ $t('Inhalation') }}</option>
                            </select>
                            <p v-if="doseForm.errors.route_administered" class="text-xs text-rose-600 mt-1 font-semibold">
                                {{ doseForm.errors.route_administered }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label for="modal_notes" class="block text-xs font-bold text-stone-700">{{ $t('Administration Notes') }}</label>
                        <input
                            id="modal_notes"
                            v-model="doseForm.clinical_notes"
                            type="text"
                            :placeholder="$t('e.g. Tolerated without adverse reaction')"
                            class="mt-1 w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-900 focus:border-[#00685f] focus:outline-none"
                        />
                        <p v-if="doseForm.errors.clinical_notes" class="text-xs text-rose-600 mt-1 font-semibold">
                            {{ doseForm.errors.clinical_notes }}
                        </p>
                    </div>

                    <!-- Catch-all error feedback for stock or business rule errors -->
                    <div v-if="Object.keys(doseForm.errors).some(k => !['product_id', 'dose_amount_base_units', 'route_administered', 'clinical_notes'].includes(k))" class="p-3 rounded-xl bg-rose-50 border border-rose-200">
                        <p
                            v-for="(msg, key) in doseForm.errors"
                            v-show="!['product_id', 'dose_amount_base_units', 'route_administered', 'clinical_notes'].includes(key)"
                            :key="key"
                            class="text-xs text-rose-700 font-semibold"
                        >
                            {{ msg }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="closeDose"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-stone-600 hover:bg-stone-100"
                        >
                            {{ $t('Cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="doseForm.processing"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#00685f] hover:bg-[#005049] transition disabled:opacity-50"
                        >
                            {{ doseForm.processing ? $t('Deducting Stock...') : $t('Administer & Deduct Stock') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: UPLOAD DIAGNOSTIC MEDIA (MinIO S3) -->
        <div
            v-if="isMediaModalOpen && !visit.is_signed"
            role="dialog"
            aria-modal="true"
            aria-labelledby="media-modal-title"
            class="fixed inset-0 z-50 bg-stone-900/50 backdrop-blur-xs flex items-center justify-center p-4 animate-fade-in"
            @click.self="closeMedia"
        >
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-stone-200 p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#00685f] text-2xl" aria-hidden="true">add_photo_alternate</span>
                        <h3 id="media-modal-title" class="text-base font-extrabold text-stone-900">{{ $t('Upload Radiograph / Diagnostic Media') }}</h3>
                    </div>
                    <button type="button" @click="closeMedia" :aria-label="$t('Close modal')" class="text-stone-400 hover:text-stone-700">
                        <span class="material-symbols-outlined text-xl" aria-hidden="true">close</span>
                    </button>
                </div>

                <form @submit.prevent="uploadMedia" class="space-y-4">
                    <div>
                        <label for="media_file_input" class="block text-xs font-bold text-stone-700">{{ $t('Select Image File *') }}</label>
                        <input
                            id="media_file_input"
                            type="file"
                            accept="image/*"
                            required
                            @change="handleFileSelect"
                            class="mt-1 w-full text-xs text-stone-500 file:me-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#00685f]/10 file:text-[#00685f] hover:file:bg-[#00685f]/20 cursor-pointer"
                        />
                        <p v-if="mediaForm.errors.image" class="text-xs text-rose-600 mt-1 font-semibold">
                            {{ mediaForm.errors.image }}
                        </p>
                    </div>

                    <div>
                        <label for="media_title_input" class="block text-xs font-bold text-stone-700">{{ $t('Study Title / Anatomical View') }}</label>
                        <input
                            id="media_title_input"
                            v-model="mediaForm.title"
                            type="text"
                            :placeholder="$t('e.g. Pre-Op Lateral Right Femur X-Ray')"
                            class="mt-1 w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-900 focus:border-[#00685f] focus:outline-none"
                        />
                        <p v-if="mediaForm.errors.title" class="text-xs text-rose-600 mt-1 font-semibold">
                            {{ mediaForm.errors.title }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="closeMedia"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-stone-600 hover:bg-stone-100"
                        >
                            {{ $t('Cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="mediaForm.processing || !mediaForm.image"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#00685f] hover:bg-[#005049] transition disabled:opacity-50"
                        >
                            {{ mediaForm.processing ? $t('Uploading to MinIO S3...') : $t('Upload Media') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: LIGHTBOX DICOM VIEWER -->
        <div
            v-if="activePreviewImage"
            role="dialog"
            aria-modal="true"
            aria-labelledby="preview-modal-title"
            class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex flex-col justify-between p-6 animate-fade-in"
            @click.self="activePreviewImage = null"
        >
            <div class="flex items-center justify-between text-white">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-2xl text-[#00685f]" aria-hidden="true">radiology</span>
                    <h3 id="preview-modal-title" class="text-base font-bold">{{ activePreviewImage.title || $t('Diagnostic View') }}</h3>
                </div>
                <button
                    type="button"
                    @click="activePreviewImage = null"
                    :aria-label="$t('Close preview')"
                    class="h-9 w-9 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white"
                >
                    <span class="material-symbols-outlined text-xl" aria-hidden="true">close</span>
                </button>
            </div>

            <div class="flex-1 flex items-center justify-center p-4 min-h-0">
                <img
                    :src="activePreviewImage.file_url || (activePreviewImage.file_path ? `/storage/${activePreviewImage.file_path}` : '')"
                    :alt="activePreviewImage.title || $t('Diagnostic Image')"
                    class="max-h-full max-w-full object-contain rounded-lg shadow-2xl"
                />
            </div>

            <div class="flex items-center justify-between text-stone-400 text-xs pt-2 border-t border-stone-800">
                <span>{{ formatDate(activePreviewImage.created_at) }}</span>
                <a
                    :href="activePreviewImage.file_url || (activePreviewImage.file_path ? `/storage/${activePreviewImage.file_path}` : '')"
                    download
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-[#00685f] hover:underline flex items-center gap-1 font-bold"
                >
                    <span class="material-symbols-outlined text-[16px]" aria-hidden="true">download</span>
                    {{ $t('Download Asset') }}
                </a>
            </div>
        </div>
    </ClinicLayout>
</template>
