<script setup>
import { onUnmounted, ref } from 'vue';

const props = defineProps({
    command: {
        type: String,
        required: true,
    },
});

const copied = ref(false);
let timer;

const copy = async () => {
    try {
        await navigator.clipboard.writeText(props.command);
        copied.value = true;
        clearTimeout(timer);
        timer = setTimeout(() => {
            copied.value = false;
        }, 1600);
    } catch {
        copied.value = false;
    }
};

onUnmounted(() => clearTimeout(timer));
</script>

<template>
    <button
        type="button"
        class="group inline-flex max-w-full items-center gap-2 rounded-full border border-light-steel bg-canvas-white py-1.5 pr-1.5 pl-3.5 text-left shadow-xs transition-colors hover:border-graphite/40 sm:gap-3 sm:pl-4 dark:border-[#262624] dark:bg-[#111113] dark:hover:border-white/25"
        @click="copy"
    >
        <span class="truncate font-mono text-[12px] text-graphite sm:text-[13px] dark:text-[#f5f5f4]">
            <span class="mr-1.5 select-none text-magic-orange sm:mr-2">$</span>{{ command }}
        </span>
        <span
            class="inline-flex shrink-0 items-center gap-1 rounded-full border border-light-steel/80 bg-ghostly-gray px-2.5 py-1.5 font-sans text-[12px] font-medium transition-colors group-hover:bg-fog sm:px-3 dark:border-[#262624] dark:bg-black dark:group-hover:bg-[#1c1c1e]"
            :class="copied ? 'border-magic-green/40 text-magic-green' : 'text-charcoal dark:text-[#a3a3a0]'"
        >
            <svg v-if="copied" class="h-3.5 w-3.5 text-magic-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <svg v-else class="h-3.5 w-3.5 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            {{ copied ? 'Copied' : 'Copy' }}
        </span>
    </button>
</template>
