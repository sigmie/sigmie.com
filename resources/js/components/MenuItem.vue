<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    name: {
        type: String,
        required: true
    },
    price: {
        type: Number,
        default: 0
    },
    category: {
        type: String,
        default: null
    },
    color: {
        type: String,
        default: null
    },
    images: {
        type: Array,
        default: () => []
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

const mainImage = computed(() => {
    return props.images && props.images.length > 0 ? props.images[0] : null;
});
</script>

<template>
    <div class="bg-gray-950 border border-gray-800 rounded-lg overflow-hidden hover:border-gray-700 transition-colors">
        <!-- Header - Always Visible -->
        <div
            class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 cursor-pointer hover:bg-gray-900 transition-colors"
            @click="toggle"
        >
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <!-- Product Image Thumbnail -->
                <div v-if="mainImage" class="flex-shrink-0">
                    <img
                        :src="mainImage"
                        :alt="name"
                        class="w-14 h-14 object-cover rounded border border-gray-800"
                    />
                </div>
                <div class="min-w-0 flex-1">
                    <h5 class="text-sm sm:text-base font-medium text-white truncate mb-1">
                        {{ name }}
                    </h5>
                    <p class="text-base font-bold text-blue-400">
                        £{{ price?.toFixed(2) }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                <span class="hidden sm:inline text-xs font-medium text-gray-500">
                    {{ isExpanded ? 'Less' : 'More' }}
                </span>
                <svg
                    class="w-4 h-4 text-gray-500 transition-transform duration-200"
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
        <div v-if="isExpanded" class="px-4 sm:px-6 pb-4 sm:pb-6 border-t border-gray-800 bg-gray-900/50">
            <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 pt-4">
                <!-- Details Column -->
                <div class="flex-1 space-y-3 text-sm min-w-0">
                    <div v-if="category" class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-gray-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        <div class="min-w-0">
                            <span class="text-gray-500 text-xs">Category</span>
                            <p class="text-gray-300 break-words">{{ category }}</p>
                        </div>
                    </div>
                    <div v-if="color" class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-gray-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                        </svg>
                        <div class="min-w-0">
                            <span class="text-gray-500 text-xs">Color</span>
                            <p class="text-gray-300 break-words">{{ color }}</p>
                        </div>
                    </div>
                </div>
                <!-- Product Image -->
                <div v-if="mainImage" class="flex-shrink-0 self-center sm:self-start">
                    <img
                        :src="mainImage"
                        :alt="name"
                        class="w-32 h-32 sm:w-40 sm:h-40 object-cover rounded-lg border border-gray-800 shadow-lg"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
