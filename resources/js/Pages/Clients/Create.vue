<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const form = useForm({
    name: '',
    phone: '',
    email: '',
    address: '',
    tax_number: '',
    notes: '',
});

const submit = () => {
    form.post('/portal/clients');
};
</script>

<template>
    <ClinicLayout title="Register Client">
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">Register Client</h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">Add an animal owner, farm manager, or stable account.</p>
                </div>

                <Link href="/portal/clients" class="text-xs font-semibold text-stone-600 hover:text-stone-900">
                    &larr; Back to Clients
                </Link>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-stone-200 shadow-2xs">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="name" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            Full Client / Business Name <span class="text-rose-500">*</span>
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="John Doe, Al-Khor Stables, Greenfield Dairy"
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600 font-medium">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="phone" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Primary Phone Number
                            </label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                placeholder="+1 (555) 234-5678"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Email Address
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="client@domain.com"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="address" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Physical Address / Farm Location
                            </label>
                            <input
                                id="address"
                                v-model="form.address"
                                type="text"
                                placeholder="123 Farm Rd, Sector 4"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label for="tax_number" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                Tax / Commercial Reg. #
                            </label>
                            <input
                                id="tax_number"
                                v-model="form.tax_number"
                                type="text"
                                placeholder="VAT-109283-TR"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            Billing & Account Notes
                        </label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="2"
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>

                    <div class="pt-4 border-t border-stone-100 flex items-center justify-end space-x-3 rtl:space-x-reverse">
                        <Link href="/portal/clients" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-stone-600 hover:bg-stone-100 transition">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : 'Register Client' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClinicLayout>
</template>
