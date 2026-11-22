<script setup>
import { onUnmounted, ref } from 'vue';

defineProps({
    command: {
        type: String,
        required: true,
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const copied = ref(false);
let timer;

const copy = async (command) => {
    try {
        await navigator.clipboard.writeText(command);
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
        class="group inline-flex max-w-full items-center rounded-full border border-light-steel bg-canvas-white text-left shadow-xs transition-colors hover:border-graphite/40 dark:border-[#262624] dark:bg-[#111113] dark:hover:border-white/25"
        :class="compact ? 'gap-2 py-1 pr-1 pl-3' : 'gap-2 py-1.5 pr-1.5 pl-3.5 sm:gap-3 sm:pl-4'"
        @click="copy(command)"
    >
        <span
            class="truncate font-mono text-graphite dark:text-[#f5f5f4]"
            :class="compact ? 'text-[11.5px]' : 'text-[12px] sm:text-[13px]'"
        >
            <span class="mr-1.5 select-none text-orange-ink dark:text-[#ff7b47]" aria-hidden="true">$</span>{{ command }}
        </span>
        <span
            class="inline-flex shrink-0 items-center gap-1 rounded-full border border-light-steel/80 bg-ghostly-gray font-sans font-medium transition-colors group-hover:bg-fog dark:border-[#262624] dark:bg-black dark:group-hover:bg-[#1c1c1e]"
            :class="[
                compact ? 'px-2.5 py-1 text-[11.5px]' : 'px-2.5 py-1.5 text-[12px] sm:px-3',
                copied ? 'border-magic-green/40 text-graphite dark:text-[#63df9a]' : 'text-charcoal dark:text-[#d4d4d8]',
            ]"
        >
            <svg v-if="copied" class="h-3 w-3 text-magic-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <svg v-else class="h-3 w-3 opacity-75" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            {{ copied ? 'Copied' : 'Copy' }}
        </span>
    </button>
</template>
