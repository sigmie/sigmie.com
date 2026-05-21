<script setup>
defineProps({
    variant: {
        type: String,
        default: "default",
        validator: (value) => ["default", "outlined", "elevated", "ghost"].includes(value)
    },
    hover: {
        type: Boolean,
        default: true
    },
    padding: {
        type: String,
        default: "p-6",
        validator: (value) => ["p-3", "p-4", "p-6", "p-8"].includes(value)
    },
    rounded: {
        type: String,
        default: "rounded-xl",
        validator: (value) => ["rounded-lg", "rounded-xl", "rounded-2xl"].includes(value)
    }
});

const variantClass = {
    default: "bg-ghostly-gray dark:bg-gray-900/50 border border-light-steel dark:border-gray-800",
    outlined: "bg-transparent border-2 border-light-steel dark:border-gray-700",
    elevated: "bg-canvas-white dark:bg-gray-900 border border-light-steel dark:border-gray-800 shadow-lg",
    ghost: "bg-transparent border border-transparent"
};

const hoverClass = hover ? "hover:border-graphite dark:hover:border-white hover:shadow-xl transition-all duration-300" : "";
</script>

<template>
    <div
        :class="[
            variantClass[variant],
            hoverClass,
            padding,
            rounded,
            "cursor-pointer"
        ]"
    >
        <slot />
    </div>
</template>

<style scoped>
/* Card specific styles */
</style>
