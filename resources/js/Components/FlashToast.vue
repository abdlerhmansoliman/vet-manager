<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const visible = ref(false);
const message = ref('');
const type = ref('success');

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            message.value = flash.success;
            type.value = 'success';
            visible.value = true;
            setTimeout(() => (visible.value = false), 5000);
        } else if (flash?.error) {
            message.value = flash.error;
            type.value = 'error';
            visible.value = true;
            setTimeout(() => (visible.value = false), 6000);
        } else if (flash?.warning) {
            message.value = flash.warning;
            type.value = 'warning';
            visible.value = true;
            setTimeout(() => (visible.value = false), 5000);
        }
    },
    { deep: true, immediate: true }
);
</script>

<template>
    <div
        v-if="visible"
        class="fixed bottom-6 end-6 z-50 max-w-md shadow-xl rounded-2xl p-4 border transition-all animate-fade-in flex items-start space-x-3 rtl:space-x-reverse"
        :class="[
            type === 'success'
                ? 'bg-emerald-50 border-emerald-200 text-emerald-900'
                : type === 'error'
                ? 'bg-rose-50 border-rose-200 text-rose-900'
                : 'bg-amber-50 border-amber-200 text-amber-900',
        ]"
    >
        <div
            class="p-1 rounded-full shrink-0"
            :class="[
                type === 'success'
                    ? 'bg-emerald-200 text-emerald-800'
                    : type === 'error'
                    ? 'bg-rose-200 text-rose-800'
                    : 'bg-amber-200 text-amber-800',
            ]"
        >
            <svg v-if="type === 'success'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <div class="flex-1 text-xs sm:text-sm font-semibold">
            {{ message }}
        </div>

        <button @click="visible = false" class="text-stone-400 hover:text-stone-600 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</template>
