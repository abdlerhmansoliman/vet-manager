<script setup>
import { Link } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    client: {
        type: Object,
        required: true,
    },
    patients: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <ClinicLayout :title="`Client: ${client.name}`">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2.5 rtl:space-x-reverse">
                        <Link href="/portal/clients" class="text-xs font-semibold text-emerald-700 hover:underline">
                            &larr; Clients
                        </Link>
                        <span class="text-stone-300">/</span>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">
                            {{ client.name }}
                        </h1>
                    </div>
                    <p class="text-xs sm:text-sm text-stone-500 mt-1">
                        {{ client.phone || 'No phone' }} &bull; {{ client.email || 'No email' }}
                    </p>
                </div>

                <div class="flex items-center space-x-2.5 rtl:space-x-reverse self-start sm:self-auto">
                    <Link
                        :href="`/portal/clients/${client.id}/edit`"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold text-stone-700 bg-white hover:bg-stone-50 border border-stone-200 shadow-2xs transition active:scale-95"
                    >
                        Edit Client
                    </Link>

                    <Link
                        :href="`/portal/patients/create?client_id=${client.id}`"
                        class="inline-flex items-center space-x-1.5 rtl:space-x-reverse px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Register Animal</span>
                    </Link>
                </div>
            </div>

            <!-- CLIENT DETAILS CARD -->
            <div class="p-6 rounded-2xl bg-white border border-stone-200 shadow-2xs">
                <h3 class="text-xs font-bold uppercase tracking-wider text-stone-400 mb-4 pb-2 border-b border-stone-100">
                    Account & Contact Details
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-stone-400 font-medium block">Phone Number</span>
                        <span class="font-bold text-stone-900 text-sm mt-0.5 block font-mono">{{ client.phone || '—' }}</span>
                    </div>

                    <div>
                        <span class="text-stone-400 font-medium block">Email</span>
                        <span class="font-bold text-stone-900 text-sm mt-0.5 block">{{ client.email || '—' }}</span>
                    </div>

                    <div>
                        <span class="text-stone-400 font-medium block">Tax / Registration</span>
                        <span class="font-bold text-stone-900 text-sm mt-0.5 block font-mono">{{ client.tax_number || '—' }}</span>
                    </div>
                </div>

                <div v-if="client.address" class="mt-4 pt-4 border-t border-stone-100 text-xs">
                    <span class="text-stone-400 font-medium block">Address / Location</span>
                    <span class="font-semibold text-stone-800 block mt-0.5">{{ client.address }}</span>
                </div>
            </div>

            <!-- ASSOCIATED PATIENTS LIST -->
            <div class="p-6 rounded-2xl bg-white border border-stone-200 shadow-2xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                    <div>
                        <h2 class="text-base font-bold text-stone-900">Registered Patients ({{ patients.length }})</h2>
                        <p class="text-xs text-stone-500">Animals belonging to this client or stable.</p>
                    </div>

                    <Link
                        :href="`/portal/patients/create?client_id=${client.id}`"
                        class="text-xs font-bold text-emerald-700 hover:underline"
                    >
                        + Add Animal
                    </Link>
                </div>

                <div v-if="patients.length === 0" class="py-12 text-center text-stone-500 text-xs">
                    No animals registered under this client yet.
                </div>

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link
                        v-for="p in patients"
                        :key="p.id"
                        :href="`/portal/patients/${p.id}`"
                        class="p-4 rounded-xl border border-stone-200 hover:border-emerald-300 hover:shadow-xs transition bg-stone-50/40 hover:bg-white"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-stone-900 hover:text-emerald-700 transition">
                                    {{ p.name_or_tag }}
                                </h4>
                                <span class="text-xs text-stone-500 block">{{ p.species }} &bull; {{ p.breed || 'Mixed' }}</span>
                            </div>
                            <StatusBadge :status="p.status || 'active'" />
                        </div>

                        <div class="mt-3 pt-2 border-t border-stone-100 flex items-center justify-between text-[11px] text-stone-400">
                            <span>Weight: <strong class="text-stone-600">{{ p.current_weight_kg ? `${p.current_weight_kg} kg` : '—' }}</strong></span>
                            <span class="text-emerald-600 font-semibold">Open Profile &rarr;</span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </ClinicLayout>
</template>
