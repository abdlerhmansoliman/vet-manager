<script setup>
import { Link, router } from '@inertiajs/vue3';
import ClinicLayout from '@/Layouts/ClinicLayout.vue';

const props = defineProps({
    staff: {
        type: Array,
        default: () => [],
    },
});

const toggleDuty = (user) => {
    router.post(`/portal/staff/${user.id}/toggle-duty`, {}, { preserveScroll: true });
};
</script>

<template>
    <ClinicLayout title="Clinic Staff Roster">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-stone-900 tracking-tight">Clinic Staff & Practitioners</h1>
                    <p class="text-xs sm:text-sm text-stone-500 mt-0.5">Clinical team directory, professional credentials, and active on-duty presence.</p>
                </div>

                <Link
                    href="/portal/staff/create"
                    class="inline-flex items-center space-x-1.5 rtl:space-x-reverse px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition active:scale-95 self-start sm:self-auto"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Add Staff Member</span>
                </Link>
            </div>

            <!-- STAFF ROSTER TABLE -->
            <div class="rounded-2xl bg-white border border-stone-200 shadow-2xs overflow-hidden">
                <div v-if="staff.length === 0" class="py-16 text-center text-stone-500 text-xs sm:text-sm">
                    No staff members registered in this clinic.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-start text-xs sm:text-sm">
                        <thead class="bg-stone-50 text-stone-500 font-bold uppercase tracking-wider text-[11px] border-b border-stone-200">
                            <tr>
                                <th class="py-3 px-4 text-start">Practitioner</th>
                                <th class="py-3 px-4 text-start">Clinical Role</th>
                                <th class="py-3 px-4 text-start">Email Address</th>
                                <th class="py-3 px-4 text-start">Duty Presence</th>
                                <th class="py-3 px-4 text-end">Toggle Duty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="u in staff" :key="u.id" class="hover:bg-stone-50/60 transition">
                                <td class="py-3.5 px-4 font-bold text-stone-900 flex items-center space-x-2.5 rtl:space-x-reverse">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-xs">
                                        {{ u.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <span>{{ u.name }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase bg-stone-100 text-stone-700 border border-stone-200">
                                        {{ u.role }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-stone-600 font-mono text-xs">
                                    {{ u.email }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span
                                        v-if="u.is_active"
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 me-1.5 animate-pulse" />
                                        On Duty
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-stone-100 text-stone-500 border border-stone-200"
                                    >
                                        Off Duty
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-end">
                                    <button
                                        type="button"
                                        @click="toggleDuty(u)"
                                        class="px-2.5 py-1 rounded text-xs font-semibold text-stone-600 hover:bg-stone-100 border border-stone-200 transition"
                                    >
                                        {{ u.is_active ? 'Set Off Duty' : 'Set On Duty' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </ClinicLayout>
</template>
