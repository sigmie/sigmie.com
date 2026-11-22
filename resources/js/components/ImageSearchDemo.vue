<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';
import CodePreview from './CodePreview.vue';
import GridCrosshairs from './GridCrosshairs.vue';
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
        statusText.value = 'Live, CLIP 512-dimension vectors';
    } catch {
        if (seq !== requestSeq) return;
        error.value = 'The live image index did not answer. Try one of the suggestions above.';
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
        statusText.value = 'Live, searched with the selected photo';
    } catch {
        if (seq !== requestSeq) return;
        error.value = 'The search with this photo did not run. Try one of the suggestions above.';
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
    <section id="image-search" class="dark relative scroll-mt-16 border-t border-[#eaeaea] bg-[#0a0a0a] text-[#fafafa] dark:border-white/[0.1]">
        <GridCrosshairs />

        <div class="flex flex-col gap-6 border-b border-white/[0.08] px-6 pt-16 pb-8 sm:px-10 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-xl">
                <h2 class="text-balance text-[32px] leading-[1.05] font-semibold tracking-[-0.035em] text-white sm:text-[44px]">
                    Search photos with words, then with a photo.
                </h2>
                <p class="mt-4 max-w-[30rem] text-[16px] leading-[1.6] text-[#a1a1aa]">
                    The same builder runs CLIP image search. Type a subject, then click any result to find photos that look like it.
                </p>
            </div>

            <form class="flex w-full max-w-md items-center gap-2" @submit.prevent="runTextSearch()">
                <label for="clip-query" class="sr-only">Search images with CLIP</label>
                <input
                    id="clip-query"
                    v-model="imageQuery"
                    type="text"
                    placeholder="Describe a scene or subject…"
                    class="w-full rounded-full border border-white/15 bg-black px-4 py-2 text-[14px] text-white placeholder:text-[#a1a1aa] focus:border-white/40 focus:ring-0"
                />
                <button
                    type="submit"
                    :disabled="isLoading"
                    class="press shrink-0 rounded-full bg-white px-4 py-2 text-[13.5px] font-medium text-[#171717] hover:bg-[#e5e5e5] disabled:opacity-50"
                >
                    Search
                </button>
            </form>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/[0.08] px-6 py-3 sm:px-10">
            <div class="flex flex-wrap gap-1.5">
                <button
                    v-for="suggestion in IMAGE_SUGGESTIONS"
                    :key="suggestion"
                    type="button"
                    class="press rounded-full border px-3 py-1 text-[12.5px] font-medium"
                    :class="mode === 'text' && imageQuery === suggestion
                        ? 'border-white bg-white text-[#171717]'
                        : 'border-white/15 bg-transparent text-[#d4d4d8] hover:border-white/35 hover:text-white'"
                    @click="runTextSearch(suggestion)"
                >
                    {{ suggestion }}
                </button>
            </div>
            <span class="text-[13px] text-[#8b8b93]" role="status">
                {{ statusText }}
            </span>
        </div>

        <div class="px-6 py-8 sm:px-10">
            <div
                v-if="error"
                role="status"
                class="border border-white/12 bg-white/[0.03] px-5 py-6 text-[14px] text-[#d4d4d8]"
            >
                {{ error }}
            </div>

            <div
                v-else
                class="grid grid-cols-2 gap-3 transition-opacity duration-200 md:h-[460px] md:grid-cols-12 md:grid-rows-2"
                :class="isLoading ? 'opacity-55' : ''"
            >
                <button
                    v-for="(image, index) in images"
                    :key="`${image.id}-${index}`"
                    type="button"
                    class="group relative overflow-hidden rounded-xl border bg-[#141414] text-left transition-colors duration-150"
                    :class="[
                        tileClasses[index],
                        selectedImage?.id === image.id
                            ? 'border-magic-orange'
                            : 'border-white/10 hover:border-white/30',
                    ]"
                    @click="selectPhoto(image)"
                >
                    <img
                        :src="image.url"
                        :alt="image.label"
                        loading="lazy"
                        decoding="async"
                        class="h-full w-full object-cover"
                    />
                    <div class="pointer-events-none absolute inset-x-0 top-0 flex items-center justify-between p-3">
                        <span
                            class="flex h-6 min-w-6 items-center justify-center rounded-full px-1.5 text-[12px] font-semibold tabular-nums"
                            :class="index === 0 ? 'bg-magic-orange text-black' : 'bg-black/80 text-white'"
                        >
                            {{ index + 1 }}
                        </span>
                        <span class="rounded-full bg-black/80 px-2.5 py-1 text-[12px] text-white opacity-0 transition-opacity duration-150 group-hover:opacity-100 group-focus-visible:opacity-100">
                            Find similar
                        </span>
                    </div>
                </button>
            </div>
        </div>

        <div class="grid divide-y divide-white/[0.08] border-t border-white/[0.08] lg:grid-cols-12 lg:divide-x lg:divide-y-0">
            <div class="flex items-center px-6 py-6 sm:px-10 lg:col-span-5">
                <div
                    v-if="mode === 'image' && selectedImage"
                    class="flex w-full items-center gap-4"
                >
                    <img
                        :src="selectedImage.url"
                        :alt="`Pinned query image ${selectedImage.id}`"
                        class="h-16 w-16 shrink-0 rounded-lg border border-magic-orange/60 object-cover"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="text-[14px] font-medium text-white">
                            Photos that look like this one
                        </p>
                        <p class="mt-0.5 text-[13px] text-[#a1a1aa]">
                            The four closest matches, in rank order.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="press shrink-0 rounded-full border border-white/15 px-3 py-1.5 text-[12.5px] font-medium text-white hover:border-white/35"
                        @click="resetToText"
                    >
                        Back to text
                    </button>
                </div>

                <div v-else class="w-full">
                    <p class="text-[16px] font-medium text-white">
                        Photos of “{{ imageQuery }}”
                    </p>
                    <p class="mt-1.5 text-[13.5px] leading-relaxed text-[#a1a1aa]">
                        Click a photo to search with it. The call changes from <code class="font-mono text-[12.5px] text-white">queryString()</code> to <code class="font-mono text-[12.5px] text-[#ff7b47]">queryImage()</code>.
                    </p>
                </div>
            </div>

            <div class="min-w-0 lg:col-span-7">
                <CodePreview
                    :code="imageCode"
                    filename="ClipImageSearch.php"
                    language="php"
                    :flat="true"
                    :compact="true"
                    :highlight-lines="highlightedLines"
                />
            </div>
        </div>
    </section>
</template>
