<script setup>
defineProps({
    variant: {
        type: String,
        default: "primary",
        validator: (value) => ["primary", "secondary", "outline", "ghost", "danger"].includes(value)
    },
    size: {
        type: String,
        default: "md",
        validator: (value) => ["sm", "md", "lg", "xl"].includes(value)
    },
    disabled: {
        type: Boolean,
        default: false
    },
    loading: {
        type: Boolean,
        default: false
    },
    fullWidth: {
        type: Boolean,
        default: false
    },
    type: {
        type: String,
        default: "button",
        validator: (value) => ["button", "submit", "reset"].includes(value)
    }
});

const variantClass = {
    primary: "bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:from-blue-700 hover:to-purple-700",
    secondary: "bg-gray-800 text-gray-100 hover:bg-gray-700 border border-gray-700",
    outline: "border-2 border-gray-600 text-gray-300 hover:border-blue-500 hover:text-blue-400",
    ghost: "text-gray-300 hover:text-white hover:bg-gray-800/50",
    danger: "bg-red-600 text-white hover:bg-red-700"
};

const sizeClass = {
    sm: "px-3 py-1.5 text-sm",
    md: "px-4 py-2 text-base",
    lg: "px-6 py-3 text-lg",
    xl: "px-8 py-4 text-xl"
};

const disabledClass = disabled ? "opacity-50 cursor-not-allowed" : "";
const widthClass = fullWidth ? "w-full" : "";
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="[
            'font-semibold rounded-lg transition-all duration-200 inline-flex items-center gap-2',
            variantClass[variant],
            sizeClass[size],
            disabledClass,
            widthClass
        ]"
    >
        <div v-if="loading" class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></div>
        <slot />
    </button>
</template>

<style scoped>
/* Button specific styles */
</style>
