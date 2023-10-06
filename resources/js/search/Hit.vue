<script setup>
import { ref, defineProps } from "vue";

const props = defineProps({
    hit: {
        type: Object,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    selected: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <li
        class="relative cursor-default select-none py-3 px-4 flex flex-col items-center"
    >
        <div
            class="flex flex-col space-y-2 py-2 w-full"
            :class="{
                'font-medium': selected,
                'font-bold': !selected,
            }"
        >
            <div class="text-xl font-bold text-zinc-700">
                {{ hit.title }}
            </div>
            <div class="font-medium">
                <div v-for="(text, index) in hit._highlight.body" :key="index">
                    <span class="text-zinc-600" v-html="text"></span>...
                </div>
            </div>
        </div>
    </li>
</template>
