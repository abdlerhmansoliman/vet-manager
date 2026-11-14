<script setup>
defineProps({
    allergies: {
        type: [String, Array],
        default: null,
    },
});
</script>

<template>
    <div
        v-if="allergies && (Array.isArray(allergies) ? allergies.length > 0 : allergies.trim().length > 0)"
        class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-rose-900 shadow-xs flex items-start space-x-3 rtl:space-x-reverse"
    >
        <div class="p-1.5 bg-rose-100 rounded-lg text-rose-700 shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-rose-900 flex items-center space-x-1.5 rtl:space-x-reverse">
                <span>Critical Medical Alert — Documented Allergies</span>
            </h4>
            <p class="text-xs sm:text-sm font-semibold text-rose-800 mt-0.5">
                {{ Array.isArray(allergies) ? allergies.join(', ') : allergies }}
            </p>
        </div>
    </div>
</template>
