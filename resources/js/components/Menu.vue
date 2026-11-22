<script setup>
import { computed } from 'vue';
import MenuItem from './MenuItem.vue';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    emptyMessage: {
        type: String,
        default: 'No results found',
    },
    maxItems: {
        type: Number,
        default: null,
    },
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
        <div v-if="loading" class="space-y-3">
            <div
                v-for="i in 4"
                :key="`skeleton-${i}`"
                class="rounded-xl border border-light-steel bg-canvas-white p-4 dark:border-[#262624] dark:bg-[#111113]"
            >
                <div class="flex items-center gap-3">
                    <div class="h-6 w-6 rounded-md bg-fog animate-pulse dark:bg-[#262624]"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 w-2/5 rounded bg-fog animate-pulse dark:bg-[#262624]"></div>
                        <div class="flex gap-2">
                            <div class="h-3 w-14 rounded-full bg-ghostly-gray animate-pulse dark:bg-[#1c1c1e]"></div>
                            <div class="h-3 w-12 rounded-full bg-ghostly-gray animate-pulse dark:bg-[#1c1c1e]"></div>
                        </div>
                    </div>
                </div>
                <div v-if="i === 1" class="mt-4 space-y-2 border-t border-light-steel pt-3 dark:border-[#262624]">
                    <div class="h-3 w-full rounded bg-ghostly-gray animate-pulse dark:bg-[#1c1c1e]"></div>
                    <div class="h-3 w-4/5 rounded bg-ghostly-gray animate-pulse dark:bg-[#1c1c1e]"></div>
                </div>
            </div>
        </div>

        <div
            v-else-if="items.length === 0"
            class="rounded-xl border border-dashed border-light-steel bg-ghostly-gray/60 px-6 py-12 text-center dark:border-[#262624] dark:bg-[#141413]"
        >
            <p class="text-[14px] text-charcoal dark:text-[#a3a3a0]">{{ emptyMessage }}</p>
        </div>

        <div v-else class="space-y-2.5">
            <MenuItem
                v-for="(item, index) in displayItems"
                :key="item._id || item.id || index"
                :rank="index + 1"
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
