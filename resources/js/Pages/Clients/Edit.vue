<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const props = defineProps({
    client: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.client.name || '',
    phone: props.client.phone || '',
    email: props.client.email || '',
    address: props.client.address || '',
    tax_number: props.client.tax_number || '',
    notes: props.client.notes || '',
});

const submit = () => {
    form.put(`/portal/clients/${props.client.id}`);
};
</script>

<template>
    <ClinicLayout :title="`Edit Client: ${client.name}`">
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">Edit Client</h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">Update client details and contact information.</p>
                </div>

                <Link :href="`/portal/clients/${client.id}`" class="text-xs font-semibold text-stone-600 hover:text-stone-900">
                    &larr; Back to Client
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
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="phone" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">Phone Number</label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">Email Address</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="address" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">Address / Location</label>
                            <input
                                id="address"
                                v-model="form.address"
                                type="text"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label for="tax_number" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">Tax Number</label>
                            <input
                                id="tax_number"
                                v-model="form.tax_number"
                                type="text"
                                class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="block text-xs font-bold text-stone-700 uppercase tracking-wider">Notes</label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="2"
                            class="mt-1.5 w-full rounded-xl border border-stone-300 px-3.5 py-2.5 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        />
                    </div>

                    <div class="pt-4 border-t border-stone-100 flex items-center justify-end space-x-3 rtl:space-x-reverse">
                        <Link :href="`/portal/clients/${client.id}`" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-stone-600 hover:bg-stone-100 transition">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Client' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </ClinicLayout>
</template>
