<script setup>
import { ref, defineProps } from "vue";

const props = defineProps({
    query: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div
        class="relative cursor-default select-none py-2 px-4 text-zinc-400 h-72 space-y-10 flex flex-col justify-center items-center text-lg"
    >
        <div class="mx-auto">
            <svg
                class="h-20"
                viewBox="0 0 20 20"
                fill="none"
                fill-rule="evenodd"
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path
                    d="M15.5 4.8c2 3 1.7 7-1 9.7h0l4.3 4.3-4.3-4.3a7.8 7.8 0 01-9.8 1m-2.2-2.2A7.8 7.8 0 0113.2 2.4M2 18L18 2"
                ></path>
            </svg>
        </div>

        <div class="mx-auto">
            <div class="w-56 text-center text-xl">
                No results for "<span class="text-zinc-400 font-medium">{{
                    query
                }}</span
                >"
            </div>
        </div>
    </div>
</template>
