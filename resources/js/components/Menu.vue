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
    <div class="space-y-4">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
            <div class="relative inline-flex">
                <div class="w-8 h-8 border-3 border-gray-700 border-t-blue-600 rounded-full animate-spin"></div>
            </div>
            <p class="mt-3 text-sm text-gray-400">Searching...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="items.length === 0" class="text-center py-12">
            <p class="text-sm text-gray-400">{{ emptyMessage }}</p>
        </div>

        <!-- Menu Items -->
        <div v-else class="space-y-4">
            <MenuItem
                v-for="(item, index) in displayItems"
                :key="item._id || item.id || index"
                :title="item.title"
                :type="item.type"
                :release-year="item.release_year"
                :cast="item.cast"
                :country="item.country"
                :poster-url="item.poster_url"
            />
        </div>
    </div>
</template>
