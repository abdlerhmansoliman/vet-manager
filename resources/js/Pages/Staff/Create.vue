<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const form = useForm({
    name: '',
    email: '',
    role: 'doctor',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/portal/staff');
};
</script>

<template>
    <ClinicLayout title="Add Staff Member">
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">Add Clinic Staff Member</h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">Register a veterinarian, technician, or nurse to this clinic practice.</p>
                </div>

                <Link href="/portal/staff" class="text-xs font-semibold text-stone-600 hover:text-stone-900">
                    &larr; Back to Staff Roster
                </Link>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-stone-200 shadow-2xs">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="name" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            Full Practitioner Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Dr. Sarah Johnson, DVM"
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600 font-medium">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Email Address <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                placeholder="sarah.johnson@vetpractice.com"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600 font-medium">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label for="role" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Clinical Role <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="role"
                                v-model="form.role"
                                required
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            >
                                <option value="doctor">Doctor (Veterinarian)</option>
                                <option value="head_vet">Head Veterinarian / Medical Director</option>
                                <option value="field_doctor">Field / Ambulatory Doctor</option>
                                <option value="technician">Veterinary Technician / Nurse</option>
                                <option value="receptionist">Receptionist / Front Desk</option>
                                <option value="inventory_manager">Pharmacy / Inventory Manager</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Password <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                            <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600 font-medium">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Confirm Password <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="pt-4 border-t border-stone-100 flex items-center justify-end space-x-3 rtl:space-x-reverse">
                        <Link href="/portal/staff" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-stone-600 hover:bg-stone-100 transition">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Registering...' : 'Add Staff Member' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClinicLayout>
</template>
