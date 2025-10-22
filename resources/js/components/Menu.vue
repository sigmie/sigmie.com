<script setup>
import { computed } from 'vue';
import MenuItem from './MenuItem.vue';

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    },
    emptyMessage: {
        type: String,
        default: 'No results found'
    },
    maxItems: {
        type: Number,
        default: null
    }
});

const displayItems = computed(() => {
    if (props.maxItems) {
        return props.items.slice(0, props.maxItems);
    }
    return props.items;
});
</script>

<template>
    <div class="space-y-3">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-16 bg-gray-950 border border-gray-800 rounded-lg">
            <div class="relative inline-flex">
                <div class="w-10 h-10 border-4 border-gray-800 border-t-blue-500 rounded-full animate-spin"></div>
            </div>
            <p class="mt-4 text-sm text-gray-400 font-medium">Searching products...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="items.length === 0" class="text-center py-16 bg-gray-950 border border-gray-800 rounded-lg">
            <svg class="w-12 h-12 text-gray-700 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
            <p class="text-sm text-gray-400">{{ emptyMessage }}</p>
        </div>

        <!-- Menu Items -->
        <div v-else class="space-y-3">
            <MenuItem
                v-for="(item, index) in displayItems"
                :key="item._id || item.id || index"
                :name="item.name"
                :price="item.price"
                :category="item.category"
                :color="item.color"
                :images="item.images"
            />
        </div>
    </div>
</template>
