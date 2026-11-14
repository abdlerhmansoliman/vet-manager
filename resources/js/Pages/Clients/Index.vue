<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const props = defineProps({
    clients: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const search = ref(props.filters.search || '');

const handleSearch = () => {
    router.get('/portal/clients', { search: search.value }, { preserveState: true, replace: true });
};
</script>

<template>
    <ClinicLayout title="Clients Directory">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">Clients & Pet Owners</h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">Contact information, farm/stable accounts, and associated patients.</p>
                </div>

                <Link
                    href="/portal/clients/create"
                    class="inline-flex items-center space-x-1.5 rtl:space-x-reverse px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 self-start sm:self-auto"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Register New Client</span>
                </Link>
            </div>

            <!-- SEARCH -->
            <div class="p-4 rounded-2xl bg-white border border-stone-200 shadow-2xs">
                <input
                    v-model="search"
                    @input="handleSearch"
                    type="text"
                    placeholder="Search by client name, phone number, email, or tax number..."
                    class="w-full rounded-xl border border-stone-300 px-3.5 py-2 text-xs sm:text-sm text-stone-900 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                />
            </div>

            <!-- TABLE -->
            <div class="rounded-2xl bg-white border border-stone-200 shadow-2xs overflow-hidden">
                <div v-if="clients.length === 0" class="py-16 text-center text-stone-500 text-xs sm:text-sm">
                    No client records found.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-start text-xs sm:text-sm">
                        <thead class="bg-stone-50 text-stone-500 font-bold uppercase tracking-wider text-[11px] border-b border-stone-200">
                            <tr>
                                <th class="py-3 px-4 text-start">Client Name</th>
                                <th class="py-3 px-4 text-start">Phone</th>
                                <th class="py-3 px-4 text-start">Email</th>
                                <th class="py-3 px-4 text-start">Registered Patients</th>
                                <th class="py-3 px-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="c in clients" :key="c.id" class="hover:bg-stone-50/60 transition">
                                <td class="py-3.5 px-4 font-bold text-stone-900">
                                    <Link :href="`/portal/clients/${c.id}`" class="hover:text-emerald-700 transition">
                                        {{ c.name }}
                                    </Link>
                                </td>
                                <td class="py-3.5 px-4 text-stone-600 font-mono text-xs">
                                    {{ c.phone || '—' }}
                                </td>
                                <td class="py-3.5 px-4 text-stone-500">
                                    {{ c.email || '—' }}
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-stone-700">
                                    {{ c.patients_count || (c.patients ? c.patients.length : 0) }} patient(s)
                                </td>
                                <td class="py-3.5 px-4 text-end">
                                    <Link
                                        :href="`/portal/clients/${c.id}`"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition inline-block"
                                    >
                                        View &rarr;
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </ClinicLayout>
</template>
