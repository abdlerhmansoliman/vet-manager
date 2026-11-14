<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';
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
    latestNote: {
        type: Object,
        default: () => null,
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

const cCase = computed(() => props.clinicalCase?.id ? props.clinicalCase : (props.case || {}));

// 1. SOAP Examination Form
const soapForm = useForm({
    subjective: props.latestNote?.subjective || props.latestNote?.subjective_notes || '',
    objective: props.latestNote?.objective || props.latestNote?.objective_notes || '',
    assessment: props.latestNote?.assessment || props.latestNote?.assessment_notes || '',
    plan: props.latestNote?.plan || props.latestNote?.plan_notes || '',
    temperature_c: props.latestNote?.temperature_c || '',
    heart_rate_bpm: props.latestNote?.heart_rate_bpm || '',
    respiratory_rate: props.latestNote?.respiratory_rate || '',
    weight_kg: props.latestNote?.weight_kg || props.patient?.current_weight_kg || '',
    systolic_bp: props.latestNote?.systolic_bp || '',
    diastolic_bp: props.latestNote?.diastolic_bp || '',
});

const saveSoap = () => {
    soapForm.post(`/portal/visits/${props.visit.id}/soap`, {
        preserveScroll: true,
    });
};

// 2. Medication Administration (eMAR) Form
const doseForm = useForm({
    product_id: '',
    dose_amount_base_units: '',
    route_administered: 'SC',
    clinical_notes: '',
});

const administerDose = () => {
    doseForm.post(`/portal/visits/${props.visit.id}/dose`, {
        preserveScroll: true,
        onSuccess: () => doseForm.reset('dose_amount_base_units', 'clinical_notes'),
    });
};

// 3. MinIO S3 Media Upload Form
const mediaForm = useForm({
    image: null,
    title: '',
});

const handleFileSelect = (e) => {
    if (e.target.files.length > 0) {
        mediaForm.image = e.target.files[0];
    }
};

const uploadMedia = () => {
    if (!mediaForm.image) return;
    mediaForm.post(`/portal/visits/${props.visit.id}/media`, {
        preserveScroll: true,
        onSuccess: () => mediaForm.reset(),
    });
};

// 4. Digital Signing & Locking
const isSigning = ref(false);
const signAndLock = () => {
    if (confirm('Are you sure you want to digitally sign and lock this clinical visit? All examination notes and dose records will be permanently locked against future edits.')) {
        isSigning.value = true;
        router.post(`/portal/visits/${props.visit.id}/sign`, {}, {
            preserveScroll: true,
            onFinish: () => (isSigning.value = false),
        });
    }
};
</script>

<template>
    <ClinicLayout :title="`Visit: ${visit.title}`">
        <div class="space-y-6">
            <!-- LEVEL 3 HEADER & LOCK STATUS -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 rtl:space-x-reverse">
                        <Link :href="`/portal/cases/${cCase.id}`" class="text-xs font-semibold text-emerald-700 hover:underline">
                            &larr; Case {{ cCase.case_number }}
                        </Link>
                        <span class="text-stone-300">/</span>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">
                            {{ visit.title }}
                        </h1>
                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                            Level 3 Workspace
                        </span>
                    </div>

                    <p class="text-xs sm:text-sm text-stone-500 mt-1">
                        Patient: <strong>{{ patient.name_or_tag }}</strong> ({{ patient.species }}) &bull;
                        Type: <span class="capitalize font-semibold text-stone-700">{{ visit.visit_type?.replace('_', ' ') }}</span>
                    </p>
                </div>

                <div class="flex items-center space-x-2.5 rtl:space-x-reverse self-start sm:self-auto">
                    <Link
                        v-if="!visit.is_signed"
                        :href="`/portal/visits/${visit.id}/edit`"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold text-stone-700 bg-white hover:bg-stone-50 border border-stone-200 shadow-2xs transition active:scale-95"
                    >
                        Edit Metadata
                    </Link>

                    <!-- SIGN & LOCK BUTTON -->
                    <button
                        v-if="!visit.is_signed"
                        type="button"
                        @click="signAndLock"
                        :disabled="isSigning"
                        class="inline-flex items-center space-x-1.5 rtl:space-x-reverse px-4 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-stone-900 hover:bg-black shadow-sm transition active:scale-95 disabled:opacity-50"
                    >
                        <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span>{{ isSigning ? 'Signing...' : 'Sign & Lock Record' }}</span>
                    </button>

                    <div
                        v-else
                        class="px-3.5 py-2 rounded-xl text-xs font-bold bg-stone-100 text-stone-800 border border-stone-300 flex items-center space-x-2 rtl:space-x-reverse"
                    >
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Signed by {{ visit.signed_by || 'Attending Doctor' }}</span>
                    </div>
                </div>
            </div>

            <!-- PATIENT ALLERGY WARNING (Inherited from Level 1) -->
            <MedicalAlertBanner :allergies="patient.high_risk_allergies" />

            <!-- SECTION 1: SOAP CLINICAL EXAMINATION & VITALS BAR -->
            <div class="p-6 rounded-2xl bg-white border border-stone-200 shadow-2xs space-y-6">
                <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                    <div>
                        <h2 class="text-base font-bold text-stone-900">SOAP Clinical Examination & Vital Signs</h2>
                        <p class="text-xs text-stone-500">Subjective observation, objective physical parameters, clinical assessment, and care plan.</p>
                    </div>

                    <span v-if="visit.is_signed" class="text-xs font-bold text-stone-400 uppercase">
                        Record Finalized & Locked
                    </span>
                </div>

                <form @submit.prevent="saveSoap" class="space-y-5">
                    <!-- OBJECTIVE VITALS BAR -->
                    <div class="p-4 rounded-xl bg-stone-50 border border-stone-200 space-y-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-stone-500 block">
                            Objective Vital Signs Bar
                        </span>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                            <div>
                                <label for="temperature_c" class="block text-[11px] font-medium text-stone-600">Temp (°C)</label>
                                <input
                                    id="temperature_c"
                                    v-model="soapForm.temperature_c"
                                    :disabled="visit.is_signed"
                                    type="number"
                                    step="0.1"
                                    placeholder="38.5"
                                    class="mt-1 w-full rounded-lg border border-stone-300 px-2.5 py-1.5 text-xs text-stone-900 focus:border-emerald-500 focus:outline-none disabled:bg-stone-100"
                                />
                            </div>

                            <div>
                                <label for="heart_rate_bpm" class="block text-[11px] font-medium text-stone-600">Heart Rate (bpm)</label>
                                <input
                                    id="heart_rate_bpm"
                                    v-model="soapForm.heart_rate_bpm"
                                    :disabled="visit.is_signed"
                                    type="number"
                                    placeholder="110"
                                    class="mt-1 w-full rounded-lg border border-stone-300 px-2.5 py-1.5 text-xs text-stone-900 focus:border-emerald-500 focus:outline-none disabled:bg-stone-100"
                                />
                            </div>

                            <div>
                                <label for="respiratory_rate" class="block text-[11px] font-medium text-stone-600">Resp Rate (bpm)</label>
                                <input
                                    id="respiratory_rate"
                                    v-model="soapForm.respiratory_rate"
                                    :disabled="visit.is_signed"
                                    type="number"
                                    placeholder="24"
                                    class="mt-1 w-full rounded-lg border border-stone-300 px-2.5 py-1.5 text-xs text-stone-900 focus:border-emerald-500 focus:outline-none disabled:bg-stone-100"
                                />
                            </div>

                            <div>
                                <label for="weight_kg" class="block text-[11px] font-medium text-stone-600">Weight (kg)</label>
                                <input
                                    id="weight_kg"
                                    v-model="soapForm.weight_kg"
                                    :disabled="visit.is_signed"
                                    type="number"
                                    step="0.01"
                                    placeholder="12.4"
                                    class="mt-1 w-full rounded-lg border border-stone-300 px-2.5 py-1.5 text-xs text-stone-900 focus:border-emerald-500 focus:outline-none disabled:bg-stone-100"
                                />
                            </div>

                            <div>
                                <label for="systolic_bp" class="block text-[11px] font-medium text-stone-600">Systolic BP</label>
                                <input
                                    id="systolic_bp"
                                    v-model="soapForm.systolic_bp"
                                    :disabled="visit.is_signed"
                                    type="number"
                                    placeholder="125"
                                    class="mt-1 w-full rounded-lg border border-stone-300 px-2.5 py-1.5 text-xs text-stone-900 focus:border-emerald-500 focus:outline-none disabled:bg-stone-100"
                                />
                            </div>

                            <div>
                                <label for="diastolic_bp" class="block text-[11px] font-medium text-stone-600">Diastolic BP</label>
                                <input
                                    id="diastolic_bp"
                                    v-model="soapForm.diastolic_bp"
                                    :disabled="visit.is_signed"
                                    type="number"
                                    placeholder="75"
                                    class="mt-1 w-full rounded-lg border border-stone-300 px-2.5 py-1.5 text-xs text-stone-900 focus:border-emerald-500 focus:outline-none disabled:bg-stone-100"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Subjective (S) -->
                    <div>
                        <label for="subjective" class="block text-xs font-bold text-stone-700 uppercase tracking-wider flex items-center space-x-1.5 rtl:space-x-reverse">
                            <span class="w-5 h-5 rounded-md bg-emerald-100 text-emerald-800 text-[11px] font-bold flex items-center justify-center">S</span>
                            <span>Subjective History & Owner Observations</span>
                        </label>
                        <textarea
                            id="subjective"
                            v-model="soapForm.subjective"
                            :disabled="visit.is_signed"
                            rows="2"
                            placeholder="Patient demeanour, appetite, water intake, vomiting frequency, attitude..."
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3 py-2 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none disabled:bg-stone-50"
                        />
                    </div>

                    <!-- Objective (O) -->
                    <div>
                        <label for="objective" class="block text-xs font-bold text-stone-700 uppercase tracking-wider flex items-center space-x-1.5 rtl:space-x-reverse">
                            <span class="w-5 h-5 rounded-md bg-teal-100 text-teal-800 text-[11px] font-bold flex items-center justify-center">O</span>
                            <span>Objective Physical Examination Findings</span>
                        </label>
                        <textarea
                            id="objective"
                            v-model="soapForm.objective"
                            :disabled="visit.is_signed"
                            rows="2"
                            placeholder="Mucous membranes, CRT, thoracic auscultation, abdominal palpation, hydration status..."
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3 py-2 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none disabled:bg-stone-50"
                        />
                    </div>

                    <!-- Assessment (A) -->
                    <div>
                        <label for="assessment" class="block text-xs font-bold text-stone-700 uppercase tracking-wider flex items-center space-x-1.5 rtl:space-x-reverse">
                            <span class="w-5 h-5 rounded-md bg-amber-100 text-amber-800 text-[11px] font-bold flex items-center justify-center">A</span>
                            <span>Assessment & Clinical Differential Evaluation</span>
                        </label>
                        <textarea
                            id="assessment"
                            v-model="soapForm.assessment"
                            :disabled="visit.is_signed"
                            rows="2"
                            placeholder="Clinical problem list, rule-outs, diagnostic evaluation, patient stability..."
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3 py-2 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none disabled:bg-stone-50"
                        />
                    </div>

                    <!-- Plan (P) -->
                    <div>
                        <label for="plan" class="block text-xs font-bold text-stone-700 uppercase tracking-wider flex items-center space-x-1.5 rtl:space-x-reverse">
                            <span class="w-5 h-5 rounded-md bg-sky-100 text-sky-800 text-[11px] font-bold flex items-center justify-center">P</span>
                            <span>Plan & Medical Treatment Protocols</span>
                        </label>
                        <textarea
                            id="plan"
                            v-model="soapForm.plan"
                            :disabled="visit.is_signed"
                            rows="2"
                            placeholder="Therapeutic protocol, fluids, scheduled medications, client communication, follow-up..."
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3 py-2 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none disabled:bg-stone-50"
                        />
                    </div>

                    <div v-if="!visit.is_signed" class="pt-2 flex justify-end">
                        <button
                            type="submit"
                            :disabled="soapForm.processing"
                            class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 disabled:opacity-50"
                        >
                            {{ soapForm.processing ? 'Saving...' : 'Save SOAP Examination' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- SECTION 2: ADMINISTERED MEDICATIONS (eMAR) WITH AUTOMATED BATCH FEFO DEDUCTION -->
            <div class="p-6 rounded-2xl bg-white border border-stone-200 shadow-2xs space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                    <div>
                        <h2 class="text-base font-bold text-stone-900">Administered Medications (eMAR)</h2>
                        <p class="text-xs text-stone-500">Doses administered to patient with automated batch FEFO stock deduction.</p>
                    </div>

                    <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg">
                        FEFO Inventory Sync
                    </span>
                </div>

                <!-- Administer Dose Form -->
                <form v-if="!visit.is_signed" @submit.prevent="administerDose" class="p-4 rounded-xl bg-stone-50 border border-stone-200 space-y-4">
                    <h4 class="text-xs font-bold text-stone-800 uppercase tracking-wider">Log & Administer Medication Dose</h4>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label for="product_id" class="block text-[11px] font-bold text-stone-700">Select Pharmaceutical Product *</label>
                            <select
                                id="product_id"
                                v-model="doseForm.product_id"
                                required
                                class="mt-1 w-full rounded-lg border border-stone-300 px-2.5 py-1.5 text-xs text-stone-900 focus:border-emerald-500 focus:outline-none"
                            >
                                <option value="" disabled>Select drug...</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">
                                    {{ p.name }} ({{ p.total_stock || p.current_stock || '0' }} {{ p.base_unit }} available)
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="dose_amount_base_units" class="block text-[11px] font-bold text-stone-700">Dose Quantity *</label>
                            <input
                                id="dose_amount_base_units"
                                v-model="doseForm.dose_amount_base_units"
                                type="number"
                                step="0.0001"
                                required
                                placeholder="e.g. 2.5000"
                                class="mt-1 w-full rounded-lg border border-stone-300 px-2.5 py-1.5 text-xs text-stone-900 focus:border-emerald-500 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label for="route_administered" class="block text-[11px] font-bold text-stone-700">Route Administered</label>
                            <select
                                id="route_administered"
                                v-model="doseForm.route_administered"
                                class="mt-1 w-full rounded-lg border border-stone-300 px-2.5 py-1.5 text-xs text-stone-900 focus:border-emerald-500 focus:outline-none"
                            >
                                <option value="SC">Subcutaneous (SC)</option>
                                <option value="IV">Intravenous (IV)</option>
                                <option value="IM">Intramuscular (IM)</option>
                                <option value="PO">Oral (PO)</option>
                                <option value="Topical">Topical</option>
                                <option value="Inhalation">Inhalation</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="clinical_notes" class="block text-[11px] font-bold text-stone-700">Clinical Administration Notes</label>
                        <input
                            id="clinical_notes"
                            v-model="doseForm.clinical_notes"
                            type="text"
                            placeholder="e.g. Administered interscapular, patient tolerated without adverse signs"
                            class="mt-1 w-full rounded-lg border border-stone-300 px-2.5 py-1.5 text-xs text-stone-900 focus:border-emerald-500 focus:outline-none"
                        />
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="doseForm.processing"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 disabled:opacity-50"
                        >
                            {{ doseForm.processing ? 'Deducting Stock...' : 'Administer Dose (Deduct Stock)' }}
                        </button>
                    </div>
                </form>

                <!-- Administered Doses Log Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-xs">
                        <thead class="bg-stone-50 text-stone-500 font-bold uppercase tracking-wider text-[11px] border-b border-stone-200">
                            <tr>
                                <th class="py-2.5 px-3 text-start">Medication</th>
                                <th class="py-2.5 px-3 text-start">Dose Amount</th>
                                <th class="py-2.5 px-3 text-start">Route</th>
                                <th class="py-2.5 px-3 text-start">Administered By</th>
                                <th class="py-2.5 px-3 text-start">Time</th>
                                <th class="py-2.5 px-3 text-start">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-if="administeredDoses.length === 0">
                                <td colspan="6" class="py-6 text-center text-stone-400">
                                    No medications administered during this visit yet.
                                </td>
                            </tr>
                            <tr v-for="d in administeredDoses" :key="d.id" class="hover:bg-stone-50/60">
                                <td class="py-2.5 px-3 font-bold text-stone-900">{{ d.product?.name || 'Medication' }}</td>
                                <td class="py-2.5 px-3 font-mono font-bold text-emerald-700">
                                    {{ d.dose_amount_base_units }} {{ d.product?.base_unit || '' }}
                                </td>
                                <td class="py-2.5 px-3 font-semibold text-stone-600">{{ d.route_administered || '—' }}</td>
                                <td class="py-2.5 px-3 text-stone-700">{{ d.administered_by?.name || 'Staff' }}</td>
                                <td class="py-2.5 px-3 text-stone-400">
                                    {{ d.created_at ? new Date(d.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—' }}
                                </td>
                                <td class="py-2.5 px-3 text-stone-500">{{ d.clinical_notes || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECTION 3: DIAGNOSTIC MEDIA GALLERY (MinIO S3) -->
            <!-- (Exclusive to Level 3 Visit Workspace!) -->
            <div class="p-6 rounded-2xl bg-white border border-stone-200 shadow-2xs space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                    <div>
                        <h2 class="text-base font-bold text-stone-900">Diagnostic Media & Radiographs (MinIO S3)</h2>
                        <p class="text-xs text-stone-500">Ultrasounds, radiographs, endoscopy, and surgical photographs attached to this visit.</p>
                    </div>

                    <span class="text-xs font-semibold text-stone-500">
                        {{ mediaImages.length }} diagnostic asset(s)
                    </span>
                </div>

                <!-- Media Upload Form -->
                <form v-if="!visit.is_signed" @submit.prevent="uploadMedia" class="p-4 rounded-xl bg-stone-50 border border-stone-200 space-y-3">
                    <h4 class="text-xs font-bold text-stone-800 uppercase tracking-wider">Attach Radiograph / Diagnostic Media</h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="image" class="block text-[11px] font-bold text-stone-700">Select Image File *</label>
                            <input
                                id="image"
                                type="file"
                                accept="image/*"
                                required
                                @change="handleFileSelect"
                                class="mt-1 w-full text-xs text-stone-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                            />
                        </div>

                        <div>
                            <label for="media_title" class="block text-[11px] font-bold text-stone-700">Media Title / View</label>
                            <input
                                id="media_title"
                                v-model="mediaForm.title"
                                type="text"
                                placeholder="e.g. Lateral Abdominal Radiograph, Right Stifle US"
                                class="mt-1 w-full rounded-lg border border-stone-300 px-2.5 py-1.5 text-xs text-stone-900 focus:border-emerald-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="mediaForm.processing || !mediaForm.image"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 disabled:opacity-50"
                        >
                            {{ mediaForm.processing ? 'Uploading to MinIO S3...' : 'Upload Diagnostic Media' }}
                        </button>
                    </div>
                </form>

                <!-- Media Thumbnails Grid -->
                <div v-if="mediaImages.length === 0" class="py-8 text-center text-stone-400 text-xs">
                    No diagnostic media attached to this visit encounter yet.
                </div>

                <div v-else class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div
                        v-for="img in mediaImages"
                        :key="img.id"
                        class="group relative rounded-xl overflow-hidden border border-stone-200 bg-stone-900 aspect-square flex flex-col justify-end"
                    >
                        <img
                            :src="img.file_url || `/storage/${img.file_path}`"
                            :alt="img.title || 'Diagnostic Media'"
                            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition"
                            loading="lazy"
                        />
                        <div class="relative z-10 p-2 bg-gradient-to-t from-black/80 via-black/40 to-transparent text-white">
                            <span class="block text-xs font-bold truncate">{{ img.title || 'Diagnostic Image' }}</span>
                            <span class="text-[10px] text-stone-300 block">
                                {{ img.created_at ? new Date(img.created_at).toLocaleDateString() : '' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClinicLayout>
</template>
