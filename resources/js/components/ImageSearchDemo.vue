<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';
import CodePreview from './CodePreview.vue';
import { IMAGE_SUGGESTIONS } from '../data/landingExamples';

const props = defineProps({
    initial: {
        type: Object,
        default: () => ({ query: 'Owl', results: [] }),
    },
});

const imageQuery = ref(props.initial?.query || 'Owl');
const images = ref(
    (props.initial?.results || []).slice(0, 4).map((item, idx) => ({
        id: item._id || `clip-${idx}`,
        url: item.image,
        label: `${props.initial?.query || 'Owl'} match ${idx + 1}`,
    })),
);
const isLoading = ref(false);
const mode = ref('text');
const selectedImage = ref(null);
const error = ref('');
const statusText = ref('Recorded preset');

let requestSeq = 0;

const phpQuote = (value) => String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'");

const imageCode = computed(() => {
    if (mode.value === 'image' && selectedImage.value) {
        const refId = phpQuote(selectedImage.value.id || 'selected.jpg');
        return [
            `$selectedImage = base64_encode(file_get_contents('${refId}'));`,
            '',
            '$images->newSearch()',
            '    ->semantic()',
            '    ->queryImage($selectedImage)',
            '    ->size(4)',
            '    ->hits();',
        ].join('\n');
    }

    return [
        '$images->newSearch()',
        '    ->semantic()',
        `    ->queryString('${phpQuote(imageQuery.value.trim() || 'Owl')}')`,
        '    ->size(4)',
        '    ->hits();',
    ].join('\n');
});

const highlightedLines = computed(() => (mode.value === 'image' ? [1, 5] : [3]));

const tileClasses = [
    'col-span-2 aspect-[16/10] md:col-span-6 md:row-span-2 md:aspect-auto',
    'col-span-2 aspect-[16/9] md:col-span-6 md:row-span-1 md:aspect-auto',
    'col-span-1 aspect-[4/3] md:col-span-3 md:row-span-1 md:aspect-auto',
    'col-span-1 aspect-[4/3] md:col-span-3 md:row-span-1 md:aspect-auto',
];

const applyResults = (payload, labelPrefix) => {
    images.value = (payload || []).slice(0, 4).map((result, index) => ({
        id: result._id || `clip-${index}`,
        url: result.image,
        label: `${labelPrefix} rank ${index + 1}`,
    }));
};

const runTextSearch = async (term = null) => {
    const query = (term ?? imageQuery.value).trim();
    if (!query) return;

    if (term !== null) imageQuery.value = term;
    mode.value = 'text';
    selectedImage.value = null;
    error.value = '';
    const seq = ++requestSeq;
    isLoading.value = true;

    try {
        const response = await axios.post('/api/search/images/text', { query });
        if (seq !== requestSeq) return;
        applyResults(response.data.results, query);
        statusText.value = 'Live · CLIP 512-dim';
    } catch {
        if (seq !== requestSeq) return;
        error.value = 'The live CLIP image index is currently unreachable.';
    } finally {
        if (seq === requestSeq) isLoading.value = false;
    }
};

const selectPhoto = async (photo) => {
    selectedImage.value = photo;
    mode.value = 'image';
    error.value = '';
    const seq = ++requestSeq;
    isLoading.value = true;

    try {
        const response = await axios.post('/api/search/images/image', { image: photo.url });
        if (seq !== requestSeq) return;
        applyResults(response.data.results, 'Visually similar');
        statusText.value = `Live · queryImage(${photo.id})`;
    } catch {
        if (seq !== requestSeq) return;
        error.value = 'Could not run image-to-image similarity against the live CLIP index.';
    } finally {
        if (seq === requestSeq) isLoading.value = false;
    }
};

const resetToText = () => {
    if (imageQuery.value.trim() === (props.initial?.query || 'Owl') && props.initial?.results?.length) {
        mode.value = 'text';
        selectedImage.value = null;
        error.value = '';
        images.value = props.initial.results.slice(0, 4).map((item, idx) => ({
            id: item._id || `clip-${idx}`,
            url: item.image,
            label: `${imageQuery.value} rank ${idx + 1}`,
        }));
        statusText.value = 'Recorded preset';
        return;
    }
    runTextSearch();
};
</script>

<template>
    <section id="image-search" class="scroll-mt-20 border-y border-white/[0.08] bg-[#0a0a0c] py-16 text-[#f5f5f4] sm:py-20 lg:py-24">
        <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-xl">
                    <p class="font-mono text-[11.5px] uppercase tracking-[0.09em] text-[#ff7b47]">
                        Multimodal · CLIP 512-dim
                    </p>
                    <h2 class="mt-2.5 text-[28px] leading-[1.1] font-semibold tracking-[-0.03em] text-white sm:text-[38px]">
                        Search photographs with words, or click any frame to pivot.
                    </h2>
                </div>

                <form class="flex w-full max-w-md items-center gap-2" @submit.prevent="runTextSearch()">
                    <label for="clip-query" class="sr-only">Search images with CLIP</label>
                    <input
                        id="clip-query"
                        v-model="imageQuery"
                        type="text"
                        placeholder="Describe a scene or subject..."
                        class="w-full rounded-xl border border-white/15 bg-white/[0.05] px-3.5 py-2 text-[14px] text-white placeholder:text-[#71717a] focus:border-magic-orange focus:ring-0 focus:outline-none"
                    />
                    <button
                        type="submit"
                        :disabled="isLoading"
                        class="shrink-0 rounded-xl bg-white px-4 py-2 text-[13px] font-medium text-graphite transition-colors hover:bg-[#e4e4e7] disabled:opacity-50"
                    >
                        Search
                    </button>
                </form>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="suggestion in IMAGE_SUGGESTIONS"
                        :key="suggestion"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[12px] font-medium transition-colors"
                        :class="mode === 'text' && imageQuery === suggestion
                            ? 'border-white bg-white text-graphite'
                            : 'border-white/15 bg-white/[0.04] text-[#d4d4d8] hover:border-white/35 hover:text-white'"
                        :disabled="isLoading"
                        @click="runTextSearch(suggestion)"
                    >
                        <span
                            v-if="mode === 'text' && imageQuery === suggestion"
                            class="h-1.5 w-1.5 rounded-full bg-magic-orange"
                            aria-hidden="true"
                        ></span>
                        {{ suggestion }}
                    </button>
                </div>
                <span class="font-mono text-[11px] text-[#a1a1aa]">
                    {{ statusText }}
                </span>
            </div>

            <div
                v-if="error"
                class="mt-6 rounded-2xl border border-white/12 bg-white/[0.03] px-5 py-6 text-[14px] text-[#d4d4d8]"
            >
                {{ error }}
            </div>

            <div
                v-else
                class="mt-6 grid grid-cols-2 gap-3 transition-opacity duration-200 md:h-[460px] md:grid-cols-12 md:grid-rows-2"
                :class="isLoading ? 'opacity-55' : ''"
            >
                <button
                    v-for="(image, index) in images"
                    :key="`${image.id}-${index}`"
                    type="button"
                    class="group relative overflow-hidden rounded-2xl border bg-[#141417] text-left transition-colors"
                    :class="[
                        tileClasses[index],
                        selectedImage?.id === image.id
                            ? 'border-magic-orange ring-1 ring-magic-orange/60'
                            : 'border-white/10 hover:border-white/30',
                    ]"
                    :disabled="isLoading"
                    @click="selectPhoto(image)"
                >
                    <img
                        :src="image.url"
                        :alt="image.label"
                        loading="lazy"
                        decoding="async"
                        class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.03] motion-reduce:transform-none"
                    />
                    <div class="pointer-events-none absolute inset-x-0 top-0 flex items-center justify-between p-3">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 font-mono text-[11px] font-semibold backdrop-blur-md"
                            :class="index === 0
                                ? 'bg-white text-graphite'
                                : 'bg-black/75 text-white'"
                        >
                            <span v-if="index === 0" class="h-1.5 w-1.5 rounded-full bg-magic-orange" aria-hidden="true"></span>
                            #{{ index + 1 }}
                        </span>
                        <span class="rounded-full bg-black/65 px-2.5 py-0.5 font-mono text-[10.5px] text-white/90 opacity-0 backdrop-blur-md transition-opacity duration-150 group-hover:opacity-100 group-focus-visible:opacity-100">
                            Find similar →
                        </span>
                    </div>
                </button>
            </div>

            <div class="mt-6 grid gap-5 lg:grid-cols-12 lg:items-center">
                <div class="lg:col-span-5">
                    <div
                        v-if="mode === 'image' && selectedImage"
                        class="flex items-center gap-4 rounded-2xl border border-magic-orange/45 bg-white/[0.04] p-4"
                    >
                        <img
                            :src="selectedImage.url"
                            :alt="`Pinned query image ${selectedImage.id}`"
                            class="h-16 w-16 shrink-0 rounded-xl border border-magic-orange/50 object-cover"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-[11px] uppercase tracking-[0.07em] text-magic-orange">
                                Pinned query image
                            </p>
                            <p class="mt-0.5 truncate text-[14px] font-medium text-white">
                                {{ selectedImage.id }}
                            </p>
                            <p class="mt-0.5 text-[12.5px] text-[#a1a1aa]">
                                Showing the 4 nearest visual neighbors in rank order.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 rounded-full border border-white/15 bg-white/[0.06] px-3 py-1.5 text-[12px] font-medium text-white transition-colors hover:bg-white/15"
                            @click="resetToText"
                        >
                            Back to text
                        </button>
                    </div>

                    <div
                        v-else
                        class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                    >
                        <p class="font-mono text-[11px] uppercase tracking-[0.07em] text-[#8e8e93]">
                            Active vector query
                        </p>
                        <p class="mt-1 text-[15px] font-medium text-white">
                            “{{ imageQuery }}”
                        </p>
                        <p class="mt-1 text-[13px] leading-relaxed text-[#a1a1aa]">
                            Click any photograph above to pin it as the query image and switch the live PHP call from <code class="font-mono text-[#e4e4e7]">queryString()</code> to <code class="font-mono text-[#ff8f61]">queryImage()</code>.
                        </p>
                    </div>
                </div>

                <div class="min-w-0 lg:col-span-7">
                    <CodePreview
                        :code="imageCode"
                        filename="ClipImageSearch.php"
                        language="php"
                        :highlight-lines="highlightedLines"
                    />
                </div>
            </div>
        </div>
    </section>
</template>
