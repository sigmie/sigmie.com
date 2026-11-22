<script setup>
import { computed } from 'vue';

const props = defineProps({
    width: {
        type: [String, Number],
        default: null,
    },
    height: {
        type: [String, Number],
        default: null,
    },
    icon: {
        type: Boolean,
        default: false,
    },
});

const imgStyle = computed(() => {
    const style = {};
    if (props.height) style.height = typeof props.height === 'number' ? `${props.height}px` : props.height;
    if (props.width) style.width = typeof props.width === 'number' ? `${props.width}px` : props.width;
    return style;
});
</script>

<template>
    <span class="sigmie-logo inline-flex items-center">
        <img
            :src="icon ? '/logo/logo-icon-black.svg' : '/logo/logo-full-black.svg'"
            alt="Sigmie"
            class="block w-auto dark:hidden"
            :style="imgStyle"
        />
        <img
            :src="icon ? '/logo/logo-icon-white.svg' : '/logo/logo-full-white.svg'"
            alt="Sigmie"
            class="hidden w-auto dark:block"
            :style="imgStyle"
        />
    </span>
</template>
