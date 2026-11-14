<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const statusStyle = computed(() => {
    switch (props.status?.toLowerCase()) {
        case 'open':
        case 'active':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'in_treatment':
        case 'in_progress':
            return 'bg-sky-50 text-sky-700 border-sky-200';
        case 'observation':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'resolved':
        case 'completed':
            return 'bg-stone-100 text-stone-700 border-stone-200';
        case 'closed':
        case 'deceased':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        default:
            return 'bg-stone-50 text-stone-600 border-stone-200';
    }
});

const statusLabel = computed(() => {
    return props.status?.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) || 'Unknown';
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border capitalize',
            statusStyle,
        ]"
    >
        {{ statusLabel }}
    </span>
</template>
