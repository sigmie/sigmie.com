<script setup>
defineProps({
    title: {
        type: String,
        required: true
    },
    subtitle: {
        type: String,
        default: ""
    },
    description: {
        type: String,
        default: ""
    }
});
</script>

<template>
    <div class="border-b border-gray-800 bg-gradient-to-b from-black to-gray-900/50">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 py-12 sm:py-16 lg:py-20 lg:px-8">
            <div class="space-y-2">
                <div v-if="subtitle" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-950/30 to-purple-950/30 border border-blue-800 rounded-full mb-4">
                    <span class="text-sm font-medium bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">
                        {{ subtitle }}
                    </span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-100">
                    <slot name="title">
                        {{ title }}
                    </slot>
                </h1>

                <p v-if="description" class="text-lg text-gray-400 max-w-3xl mt-4">
                    {{ description }}
                </p>

                <slot name="extra" />
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Page header specific styles */
</style>
