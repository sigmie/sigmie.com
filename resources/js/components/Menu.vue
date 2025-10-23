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
    <div class="space-y-3 h-[460px] overflow-y-auto scrollbar-hide">
        <!-- Loading Skeleton State -->
        <div v-if="loading" class="space-y-3">
            <!-- First skeleton item (expanded) -->
            <div key="skeleton-1" class="bg-gray-950 border border-gray-800 rounded-lg overflow-hidden">
                <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <!-- Skeleton Thumbnail -->
                        <div class="flex-shrink-0 w-14 h-14 bg-gray-800 rounded border border-gray-700 animate-pulse"></div>
                        <div class="min-w-0 flex-1">
                            <!-- Skeleton Title -->
                            <div class="h-4 bg-gray-800 rounded w-3/4 mb-2 animate-pulse"></div>
                            <!-- Skeleton Metadata -->
                            <div class="flex items-center gap-2">
                                <div class="h-3 bg-gray-800 rounded w-16 animate-pulse"></div>
                                <div class="h-3 bg-gray-800 rounded w-12 animate-pulse"></div>
                                <div class="h-3 bg-gray-800 rounded w-20 animate-pulse"></div>
                            </div>
                        </div>
                    </div>
                    <!-- Skeleton Chevron -->
                    <div class="w-4 h-4 bg-gray-800 rounded flex-shrink-0 ml-2 animate-pulse"></div>
                </div>
                <!-- Expanded details skeleton -->
                <div class="px-4 sm:px-6 pb-3 sm:pb-4 border-t border-gray-800 bg-gray-900/50">
                    <div class="flex flex-col pt-3">
                        <!-- Details Column -->
                        <div class="flex-1 space-y-2 text-sm min-w-0">
                            <div class="flex items-start gap-2">
                                <div class="w-3 h-3 bg-gray-800 rounded flex-shrink-0 mt-1 animate-pulse"></div>
                                <div class="min-w-0 flex-1">
                                    <div class="h-2 bg-gray-800 rounded w-20 mb-1 animate-pulse"></div>
                                    <div class="space-y-1">
                                        <div class="h-2 bg-gray-800 rounded w-full animate-pulse"></div>
                                        <div class="h-2 bg-gray-800 rounded w-5/6 animate-pulse"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remaining collapsed skeleton items -->
            <div v-for="i in 3" :key="`skeleton-${i + 1}`" class="bg-gray-950 border border-gray-800 rounded-lg overflow-hidden animate-pulse">
                <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <!-- Skeleton Thumbnail -->
                        <div class="flex-shrink-0 w-14 h-14 bg-gray-800 rounded border border-gray-700"></div>
                        <div class="min-w-0 flex-1">
                            <!-- Skeleton Title -->
                            <div class="h-4 bg-gray-800 rounded w-3/4 mb-2"></div>
                            <!-- Skeleton Metadata -->
                            <div class="flex items-center gap-2">
                                <div class="h-3 bg-gray-800 rounded w-16"></div>
                                <div class="h-3 bg-gray-800 rounded w-12"></div>
                                <div class="h-3 bg-gray-800 rounded w-20"></div>
                            </div>
                        </div>
                    </div>
                    <!-- Skeleton Chevron -->
                    <div class="w-4 h-4 bg-gray-800 rounded flex-shrink-0 ml-2"></div>
                </div>
            </div>
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
                :name="item.name || item.title"
                :price="item.price"
                :category="item.category"
                :color="item.color"
                :images="item.images"
                :type="item.type"
                :director="item.director"
                :description="item.description"
                :release-year="item.release_year"
                :default-expanded="index === 0"
            />
        </div>
    </div>
</template>

<style scoped>
.scrollbar-hide {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;     /* Firefox */
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;  /* Chrome, Safari and Opera */
}
</style>
