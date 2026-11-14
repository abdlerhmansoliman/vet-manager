<script setup>
import { Link } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    visit: {
        type: Object,
        required: true,
    },
});

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    try {
        return new Date(dateStr).toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch (e) {
        return dateStr;
    }
};
</script>

<template>
    <Link
        :href="`/portal/visits/${visit.id}`"
        class="block p-4 rounded-xl border border-stone-200 bg-white hover:border-emerald-300 hover:shadow-md transition group"
    >
        <div class="flex items-start justify-between">
            <div class="space-y-1">
                <div class="flex items-center space-x-2 rtl:space-x-reverse">
                    <span
                        v-if="visit.is_signed"
                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-stone-100 text-stone-700 border border-stone-300"
                    >
                        <svg class="w-3 h-3 me-1 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Signed & Locked
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                    >
                        In Progress
                    </span>

                    <span class="text-xs font-semibold text-stone-500 uppercase tracking-wide">
                        {{ visit.visit_type || 'Outpatient' }}
                    </span>
                </div>

                <h4 class="text-sm font-bold text-stone-900 group-hover:text-emerald-700 transition">
                    {{ visit.title }}
                </h4>

                <p v-if="visit.reason_for_visit" class="text-xs text-stone-500 line-clamp-1">
                    {{ visit.reason_for_visit }}
                </p>
            </div>

            <div class="text-end shrink-0">
                <span class="text-[11px] font-medium text-stone-400 block">
                    {{ formatDate(visit.visit_date || visit.created_at) }}
                </span>
                <StatusBadge :status="visit.status || 'open'" class="mt-1" />
            </div>
        </div>

        <div class="mt-3 pt-2.5 border-t border-stone-100 flex items-center justify-between text-[11px] text-stone-500">
            <span class="flex items-center space-x-1 rtl:space-x-reverse">
                <svg class="w-3.5 h-3.5 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>{{ visit.progress_notes_count || (visit.progress_notes ? visit.progress_notes.length : 0) }} SOAP note(s)</span>
            </span>

            <span class="text-emerald-600 font-semibold flex items-center space-x-1 rtl:space-x-reverse group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 transition">
                <span>Open Clinical Workspace</span>
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>
        </div>
    </Link>
</template>
