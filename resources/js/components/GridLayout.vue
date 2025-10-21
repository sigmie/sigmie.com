<script setup>
defineProps({
    cols: {
        type: Number,
        default: 2,
        validator: (value) => [1, 2, 3, 4].includes(value)
    },
    gap: {
        type: String,
        default: "gap-6",
        validator: (value) => ["gap-2", "gap-4", "gap-6", "gap-8"].includes(value)
    },
    breakpoint: {
        type: String,
        default: "lg",
        validator: (value) => ["sm", "md", "lg", "xl"].includes(value)
    },
    responsive: {
        type: Boolean,
        default: true
    }
});

const gridClass = () => {
    if (!responsive) {
        return `grid grid-cols-${cols}`;
    }

    const colMap = {
        1: "grid-cols-1",
        2: "grid-cols-1 md:grid-cols-2",
        3: "grid-cols-1 md:grid-cols-2 lg:grid-cols-3",
        4: "grid-cols-1 md:grid-cols-2 lg:grid-cols-4"
    };

    return `grid ${colMap[cols]}`;
};
</script>

<template>
    <div :class="[gridClass(), gap]">
        <slot />
    </div>
</template>

<style scoped>
/* Grid layout specific styles */
</style>
