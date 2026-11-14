<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    step: {
        type: String,
        default: 'discover_email',
    },
    selectedTenant: {
        type: Object,
        default: null,
    },
    discoveredClinics: {
        type: Array,
        default: () => [],
    },
    discoveredEmail: {
        type: String,
        default: '',
    },
    status: {
        type: String,
        default: null,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const step = ref(props.step === 'enter_password' || props.step === 'select_clinic' || props.discoveredClinics.length > 0 ? 2 : 1);
const selectedClinic = ref(props.selectedTenant || (props.discoveredClinics.length === 1 ? props.discoveredClinics[0] : null));

// Step 1 Discovery Form
const discoverForm = useForm({
    email: props.discoveredEmail || '',
});

const handleDiscover = () => {
    discoverForm.post('/portal/login/discover', {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.discoveredClinics?.length > 0) {
                step.value = 2;
                if (page.props.discoveredClinics.length === 1) {
                    selectedClinic.value = page.props.discoveredClinics[0];
                }
            }
        },
    });
};

// Step 2 Authentication Form
const loginForm = useForm({
    tenant_id: '',
    email: '',
    password: '',
    remember: false,
});

const selectClinic = (clinic) => {
    selectedClinic.value = clinic;
};

const handleLogin = () => {
    if (!selectedClinic.value) return;
    loginForm.tenant_id = selectedClinic.value.id;
    loginForm.email = discoverForm.email;
    loginForm.post('/portal/login', {
        preserveScroll: true,
    });
};

const resetDiscovery = () => {
    step.value = 1;
    selectedClinic.value = null;
    discoverForm.reset();
};
</script>

<template>
    <div class="min-h-screen bg-[#FAF8F5] flex flex-col justify-center py-12 sm:px-6 lg:px-8 selection:bg-emerald-500 selection:text-white">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 items-center justify-center text-white shadow-md mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-stone-900 tracking-tight">VetManager Clinic Portal</h2>
            <p class="mt-1.5 text-xs sm:text-sm text-stone-500">Dedicated Clinical Practice & Inpatient System</p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
            <div class="bg-white py-8 px-6 shadow-xl rounded-2xl border border-stone-200/80 sm:px-10">
                <!-- Status banner -->
                <div v-if="status" class="mb-4 p-3 rounded-xl bg-emerald-50 text-emerald-800 text-xs font-semibold border border-emerald-200">
                    {{ status }}
                </div>

                <!-- STEP 1: EMAIL DISCOVERY -->
                <div v-if="step === 1">
                    <div class="mb-5">
                        <h3 class="text-sm font-bold text-stone-900">Sign in to your practice</h3>
                        <p class="text-xs text-stone-500 mt-0.5">Enter your doctor or staff email to discover associated clinic tenants.</p>
                    </div>

                    <form @submit.prevent="handleDiscover" class="space-y-4">
                        <div>
                            <label for="email" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">Email Address</label>
                            <input
                                id="email"
                                v-model="discoverForm.email"
                                type="email"
                                required
                                autofocus
                                placeholder="dr.smith@veterinary.com"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm text-stone-900 shadow-2xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="discoverForm.errors.email" class="mt-1.5 text-xs text-rose-600 font-medium">
                                {{ discoverForm.errors.email }}
                            </p>
                        </div>

                        <button
                            type="submit"
                            :disabled="discoverForm.processing"
                            class="w-full flex justify-center py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-98 disabled:opacity-50"
                        >
                            {{ discoverForm.processing ? 'Discovering Clinics...' : 'Continue with Email' }}
                        </button>
                    </form>
                </div>

                <!-- STEP 2: CLINIC SELECTION & PASSWORD -->
                <div v-else>
                    <div class="mb-4 flex items-center justify-between pb-3 border-b border-stone-100">
                        <div>
                            <span class="text-[11px] text-stone-400 font-bold uppercase tracking-wider block">Identified Practitioner</span>
                            <span class="text-xs font-bold text-stone-900">{{ discoverForm.email }}</span>
                        </div>
                        <button @click="resetDiscovery" class="text-xs font-semibold text-emerald-700 hover:underline">
                            Change
                        </button>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-2">
                            Select Clinic Practice
                        </label>

                        <div class="space-y-2">
                            <div
                                v-for="clinic in discoveredClinics"
                                :key="clinic.id"
                                @click="selectClinic(clinic)"
                                class="p-3 rounded-xl border cursor-pointer transition flex items-center justify-between"
                                :class="[
                                    selectedClinic?.id === clinic.id
                                        ? 'border-emerald-500 bg-emerald-50/60 shadow-xs ring-1 ring-emerald-500'
                                        : 'border-stone-200 bg-stone-50/50 hover:bg-stone-50',
                                ]"
                            >
                                <div>
                                    <h4 class="text-xs font-bold text-stone-900">{{ clinic.name }}</h4>
                                    <span class="text-[10px] text-stone-500 block">{{ clinic.subdomain }}.vetmanager.app</span>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-white border border-stone-200 text-stone-600 uppercase">
                                    {{ clinic.role || 'Doctor' }}
                                </span>
                            </div>
                        </div>

                        <p v-if="loginForm.errors.tenant_id" class="mt-1.5 text-xs text-rose-600 font-medium">
                            {{ loginForm.errors.tenant_id }}
                        </p>
                    </div>

                    <!-- Password form for selected clinic -->
                    <form @submit.prevent="handleLogin" class="space-y-4">
                        <div>
                            <label for="password" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">Account Password</label>
                            <input
                                id="password"
                                v-model="loginForm.password"
                                type="password"
                                required
                                autofocus
                                placeholder="••••••••"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-sm text-stone-900 shadow-2xs focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="loginForm.errors.password" class="mt-1.5 text-xs text-rose-600 font-medium">
                                {{ loginForm.errors.password }}
                            </p>
                            <p v-if="loginForm.errors.email" class="mt-1.5 text-xs text-rose-600 font-medium">
                                {{ loginForm.errors.email }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center text-xs text-stone-600">
                                <input v-model="loginForm.remember" type="checkbox" class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500 me-2" />
                                Remember this session
                            </label>
                        </div>

                        <button
                            type="submit"
                            :disabled="loginForm.processing || !selectedClinic"
                            class="w-full flex justify-center py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-98 disabled:opacity-50"
                        >
                            {{ loginForm.processing ? 'Signing in...' : 'Sign In to ' + (selectedClinic?.name || 'Clinic') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
