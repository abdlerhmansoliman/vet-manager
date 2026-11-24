<script setup>
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
    priority: {
        type: String,
        required: true,
    },
});

const priorityClass = computed(() => {
    switch (props.priority) {
        case 'emergency_critical':
            return 'bg-rose-100 text-rose-800 border-rose-200';
        case 'urgent':
            return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'routine':
        default:
            return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    }
});

const priorityLabel = computed(() => {
    switch (props.priority) {
        case 'emergency_critical':
            return trans('Critical / Emergency');
        case 'urgent':
            return trans('Urgent');
        case 'routine':
        default:
            return trans('Routine');
    }
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border',
            priorityClass,
        ]"
    >
        <span
            :class="[
                'w-1.5 h-1.5 rounded-full me-1.5',
                props.priority === 'emergency_critical'
                    ? 'bg-rose-500 animate-pulse'
                    : props.priority === 'urgent'
                    ? 'bg-amber-500'
                    : 'bg-emerald-500',
            ]"
        />
        {{ priorityLabel }}
    </span>
</template>
