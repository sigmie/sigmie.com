<script setup>
defineProps({
    width: {
        type: [String, Number],
        default: null
    },
    height: {
        type: [String, Number],
        default: null
    },
    icon: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <span class="sigmie-logo inline-flex items-center">
        <img
            :src="icon ? '/logo/logo-icon-black.svg' : '/logo/logo-full-black.svg'"
            alt="Sigmie"
            class="block dark:hidden"
            :width="width || undefined"
            :height="height || undefined"
        />
        <img
            :src="icon ? '/logo/logo-icon-white.svg' : '/logo/logo-full-white.svg'"
            alt="Sigmie"
            class="hidden dark:block"
            :width="width || undefined"
            :height="height || undefined"
        />
    </span>
</template>
