<script setup>
defineProps({
    variant: {
        type: String,
        default: "default",
        validator: (value) => ["default", "blue", "purple", "green", "red", "yellow"].includes(value)
    },
    size: {
        type: String,
        default: "md",
        validator: (value) => ["sm", "md", "lg"].includes(value)
    },
    rounded: {
        type: Boolean,
        default: true
    }
});

const variantClass = {
    default: "bg-gray-800/50 text-gray-200 border border-gray-700",
    blue: "bg-blue-900/30 text-blue-300 border border-blue-800",
    purple: "bg-purple-900/30 text-purple-300 border border-purple-800",
    green: "bg-green-900/30 text-green-300 border border-green-800",
    red: "bg-red-900/30 text-red-300 border border-red-800",
    yellow: "bg-yellow-900/30 text-yellow-300 border border-yellow-800"
};

const sizeClass = {
    sm: "px-2 py-1 text-xs",
    md: "px-3 py-1.5 text-sm",
    lg: "px-4 py-2 text-base"
};

const roundedClass = rounded ? "rounded-full" : "rounded-md";
</script>

<template>
    <span
        :class="[
            'inline-flex items-center font-medium',
            variantClass[variant],
            sizeClass[size],
            roundedClass
        ]"
    >
        <slot />
    </span>
</template>

<style scoped>
/* Badge specific styles */
</style>
