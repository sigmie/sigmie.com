<script setup>
// No props needed for this simple component
</script>

<template>
    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/60 via-gray-800/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="backdrop-filter: blur(8px);">
        <div class="absolute inset-0 flex flex-col items-center justify-center gap-2">
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-full p-3 transform scale-90 group-hover:scale-100 transition-transform duration-200 shadow-xl">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <span class="text-white font-medium text-xs px-3 py-1">
                Find Similar
            </span>
        </div>
    </div>
</template>

<style scoped>
/* Hover overlay styles */
</style>
