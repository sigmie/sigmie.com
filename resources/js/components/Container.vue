<script setup>
defineProps({
    maxWidth: {
        type: String,
        default: "max-w-7xl",
        validator: (value) => [
            "max-w-sm", "max-w-md", "max-w-lg", "max-w-xl", "max-w-2xl",
            "max-w-3xl", "max-w-4xl", "max-w-5xl", "max-w-6xl", "max-w-7xl"
        ].includes(value)
    },
    padding: {
        type: String,
        default: "px-4 sm:px-6 lg:px-8"
    }
});
</script>

<template>
    <div :class="['mx-auto', maxWidth, padding]">
        <slot />
    </div>
</template>

<style scoped>
/* Container specific styles */
</style>
