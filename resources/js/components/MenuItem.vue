<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    rank: {
        type: Number,
        default: null,
    },
    name: {
        type: String,
        required: true,
    },
    price: {
        type: Number,
        default: 0,
    },
    category: {
        type: String,
        default: null,
    },
    color: {
        type: String,
        default: null,
    },
    images: {
        type: Array,
        default: () => [],
    },
    type: {
        type: String,
        default: null,
    },
    director: {
        type: String,
        default: null,
    },
    description: {
        type: String,
        default: null,
    },
    releaseYear: {
        type: [Number, String],
        default: null,
    },
    defaultExpanded: {
        type: Boolean,
        default: false,
    },
});

const isExpanded = ref(props.defaultExpanded);

const toggle = () => {
    isExpanded.value = !isExpanded.value;
};

const mainImage = computed(() => (
    props.images && props.images.length > 0 ? props.images[0] : null
));
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border transition-colors"
        :class="isExpanded
            ? 'border-graphite/20 bg-canvas-white dark:border-white/15 dark:bg-[#111113]'
            : 'border-light-steel bg-canvas-white hover:border-graphite/20 dark:border-[#262624] dark:bg-[#0e0e10] dark:hover:border-white/15'"
    >
        <h3>
            <button
                type="button"
                class="flex w-full items-center gap-3.5 px-4 py-3.5 text-left transition-colors hover:bg-ghostly-gray/70 sm:px-5 dark:hover:bg-white/[0.02]"
                :aria-expanded="isExpanded"
                @click="toggle"
            >
                <span
                    v-if="rank"
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-ghostly-gray font-mono text-[11px] font-medium text-subtle-gray dark:bg-white/[0.06] dark:text-[#a1a1aa]"
                >
                    0{{ rank }}
                </span>
                <img
                    v-if="mainImage"
                    :src="mainImage"
                    :alt="name"
                    class="h-12 w-12 shrink-0 rounded-lg border border-light-steel object-cover dark:border-[#262624]"
                />
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[15px] font-semibold text-graphite dark:text-[#f5f5f4]">{{ name }}</span>
                    <span v-if="price" class="mt-0.5 block text-[14px] font-semibold text-graphite dark:text-[#f5f5f4]">£{{ price.toFixed(2) }}</span>
                    <span v-if="type || director || releaseYear" class="mt-1.5 flex flex-wrap items-center gap-1.5">
                        <span
                            v-if="type"
                            class="rounded-full bg-ghostly-gray px-2 py-0.5 text-[11px] font-medium text-charcoal dark:bg-white/[0.06] dark:text-[#d4d4d8]"
                        >
                            {{ type }}
                        </span>
                        <span
                            v-if="releaseYear"
                            class="rounded-full bg-ghostly-gray px-2 py-0.5 font-mono text-[11px] text-charcoal dark:bg-white/[0.06] dark:text-[#a1a1aa]"
                        >
                            {{ releaseYear }}
                        </span>
                        <span
                            v-if="director"
                            class="truncate text-[12px] text-subtle-gray dark:text-[#a1a1aa]"
                        >
                            {{ director }}
                        </span>
                    </span>
                </span>
                <svg
                    class="h-4 w-4 shrink-0 text-subtle-gray transition-transform duration-200 motion-reduce:transition-none"
                    :class="{ 'rotate-180': isExpanded }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </h3>

        <div
            class="grid transition-[grid-template-rows] duration-200 motion-reduce:transition-none"
            :class="isExpanded ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
        >
            <div class="overflow-hidden">
                <div class="border-t border-light-steel/80 bg-ghostly-gray/60 px-4 py-3.5 sm:px-5 dark:border-[#262624] dark:bg-white/[0.02]">
                    <p v-if="description" class="text-[14px] leading-relaxed text-charcoal dark:text-[#a3a3a0]">
                        {{ description }}
                    </p>
                    <div v-if="category" class="mt-3">
                        <p class="font-mono text-[11px] uppercase tracking-[0.06em] text-subtle-gray">Category</p>
                        <p class="mt-0.5 text-[14px] text-charcoal dark:text-[#a3a3a0]">{{ category }}</p>
                    </div>
                    <div v-if="color" class="mt-3">
                        <p class="font-mono text-[11px] uppercase tracking-[0.06em] text-subtle-gray">Color</p>
                        <p class="mt-0.5 text-[14px] text-charcoal dark:text-[#a3a3a0]">{{ color }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
