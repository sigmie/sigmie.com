<script setup>
import { ref } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    type: {
        type: String,
        default: 'Movie'
    },
    releaseYear: {
        type: String,
        default: null
    },
    cast: {
        type: String,
        default: null
    },
    country: {
        type: String,
        default: null
    },
    posterUrl: {
        type: String,
        default: null
    },
    defaultExpanded: {
        type: Boolean,
        default: false
    }
});

const isExpanded = ref(props.defaultExpanded);

const toggle = () => {
    isExpanded.value = !isExpanded.value;
};
</script>

<template>
    <div class="bg-black border border-gray-800 rounded-lg overflow-hidden">
        <!-- Header - Always Visible -->
        <div
            class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-900/50 transition-colors"
            @click="toggle"
        >
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 text-xs font-medium rounded-full bg-gray-800 text-gray-300">
                    {{ type }}
                </span>
                <h5 class="text-base font-medium text-gray-100">
                    {{ title }}
                </h5>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-400">
                    {{ isExpanded ? 'Show Less' : 'Show More' }}
                </span>
                <svg
                    class="w-4 h-4 text-gray-400 transition-transform duration-200"
                    :class="{ 'rotate-180': isExpanded }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>

        <!-- Details - Collapsible -->
        <div v-if="isExpanded" class="px-6 pb-6 border-t border-gray-800">
            <div class="flex gap-6 pt-4">
                <!-- Details Column -->
                <div class="flex-1 space-y-2 text-sm text-gray-400">
                    <p v-if="releaseYear">
                        <span class="font-medium">Release Date:</span> {{ releaseYear }}
                    </p>
                    <p v-if="cast">
                        <span class="font-medium">Actors:</span> {{ cast }}
                    </p>
                    <p v-if="country">
                        <span class="font-medium">Languages:</span> {{ country }}
                    </p>
                </div>
                <!-- Movie Poster -->
                <div v-if="posterUrl" class="flex-shrink-0">
                    <img
                        :src="posterUrl"
                        :alt="title"
                        class="w-32 h-48 object-cover rounded-lg border border-gray-800"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
