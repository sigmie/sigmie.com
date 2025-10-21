<script setup>
defineProps({
    id: {
        type: String,
        default: ""
    },
    variant: {
        type: String,
        default: "default",
        validator: (value) => ["default", "dark", "light"].includes(value)
    },
    hasPadding: {
        type: Boolean,
        default: true
    },
    maxWidth: {
        type: String,
        default: "max-w-7xl"
    }
});

const bgClass = {
    default: "bg-gradient-to-b from-black to-gray-900/50",
    dark: "bg-black",
    light: "bg-gradient-to-b from-gray-900/50 to-black"
};

const borderClass = "border-t border-gray-800";
</script>

<template>
    <div
        :id="id"
        :class="[
            'relative overflow-hidden scroll-mt-20',
            borderClass,
            bgClass[variant]
        ]"
    >
        <!-- Decorative background elements -->
        <slot name="background" />

        <!-- Content -->
        <div
            :class="[
                'relative mx-auto',
                hasPadding ? 'px-4 sm:px-6 py-12 sm:py-16 lg:py-20 lg:px-8' : '',
                maxWidth
            ]"
        >
            <slot />
        </div>
    </div>
</template>

<style scoped>
/* Content section specific styles */
</style>
