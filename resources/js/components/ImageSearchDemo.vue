<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import CodePreview from './CodePreview.vue';
import QueryInput from './QueryInput.vue';
import ImageIcon from './icons/ImageIcon.vue';
import ImageHoverOverlay from './ImageHoverOverlay.vue';
import { IMAGE_SUGGESTIONS } from '../data/landingExamples';

const imageQuery = ref('Owl');
const images = ref([]);
const isLoading = ref(false);
const mode = ref('text');
const selectedImageUrl = ref('');
const error = ref('');

const phpQuote = (value) => String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'");

const previewArg = (value) => {
    if (!value) return '';
    if (value.length <= 72) return value;
    return 'https://img.sigmie.com/selected.jpg';
};

const imageCode = computed(() => {
    const call = mode.value === 'image'
        ? `    ->queryImage('${phpQuote(previewArg(selectedImageUrl.value))}')`
        : `    ->queryString('${phpQuote(imageQuery.value || 'Owl')}')`;

    return [
        '$images->newSearch()',
        '    ->semantic()',
        call,
        '    ->size(4)',
        '    ->hits();',
    ].join('\n');
});

const highlightedLines = computed(() => [3]);

const tileClass = [
    'col-span-1 aspect-[4/3] md:col-span-1 md:row-span-2 md:aspect-auto',
    'col-span-1 aspect-[4/3] md:col-span-3 md:row-span-2 md:aspect-auto',
    'col-span-1 aspect-[4/3] md:col-span-2 md:row-span-1 md:aspect-auto',
    'col-span-1 aspect-[4/3] md:col-span-2 md:row-span-1 md:aspect-auto',
];

const applyResults = (payload) => {
    images.value = (payload || []).slice(0, 4).map((result, index) => ({
        id: result._id || index,
        url: result.image,
        title: mode.value === 'image' ? `Similar image ${index + 1}` : `${imageQuery.value} ${index + 1}`,
    }));
};

const updateImageQuery = async () => {
    const query = imageQuery.value.trim();
    if (!query) return;

    mode.value = 'text';
    selectedImageUrl.value = '';
    isLoading.value = true;
    error.value = '';

    try {
        const response = await axios.post('/api/search/images/text', { query });
        applyResults(response.data.results);
    } catch {
        images.value = [];
        error.value = 'The live CLIP image index is currently unreachable.';
    } finally {
        isLoading.value = false;
    }
};

const selectImage = async (imageUrl) => {
    selectedImageUrl.value = imageUrl;
    mode.value = 'image';
    isLoading.value = true;
    error.value = '';

    try {
        const response = await axios.post('/api/search/images/image', { image: imageUrl });
        applyResults(response.data.results);
    } catch {
        error.value = 'The live CLIP image index could not search by that photo.';
    } finally {
        isLoading.value = false;
    }
};

const runSuggestion = (suggestion) => {
    imageQuery.value = suggestion;
    updateImageQuery();
};

onMounted(() => {
    updateImageQuery();
});
</script>

<template>
    <section id="image-search" class="scroll-mt-20 border-t border-light-steel bg-canvas-white py-20 sm:py-24 lg:py-28 dark:border-white/[0.08] dark:bg-black">
        <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="inline-flex items-center gap-2 font-mono text-[12px] font-medium uppercase tracking-[0.08em] text-magic-orange">
                    <span class="h-1.5 w-1.5 rounded-full bg-magic-orange" aria-hidden="true"></span>
                    02 · Multimodal CLIP search
                </p>
                <h2 class="mt-3 text-[28px] leading-[1.12] font-semibold tracking-[-0.03em] text-graphite sm:text-[40px] dark:text-[#f5f5f4]">
                    Text-to-image and image-to-image in one call.
                </h2>
                <p class="mt-4 text-[16px] leading-relaxed text-charcoal dark:text-[#a3a3a0]">
                    CLIP embeds words and photographs into one vector space. Search with plain text, or click any photograph below to find visually similar images with queryImage().
                </p>
            </div>

            <div class="mt-10 grid gap-6 lg:grid-cols-12 lg:items-center">
                <div class="rounded-2xl border border-light-steel bg-ghostly-gray/70 p-5 sm:p-6 lg:col-span-7 dark:border-[#262624] dark:bg-[#111113]">
                    <QueryInput
                        v-model="imageQuery"
                        placeholder="Describe a scene or subject..."
                        input-label="Search images"
                        :disabled="isLoading"
                        @submit="updateImageQuery"
                    >
                        <template #icon>
                            <ImageIcon />
                        </template>
                    </QueryInput>

                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <button
                            v-for="suggestion in IMAGE_SUGGESTIONS"
                            :key="suggestion"
                            type="button"
                            class="rounded-full border px-3 py-1 text-[12px] font-medium transition-colors"
                            :class="mode === 'text' && imageQuery === suggestion
                                ? 'border-graphite bg-graphite text-white dark:border-[#f5f5f4] dark:bg-[#f5f5f4] dark:text-graphite'
                                : 'border-light-steel bg-canvas-white text-charcoal hover:border-graphite/40 dark:border-[#262624] dark:bg-black/40 dark:text-[#a3a3a0] dark:hover:border-white/25 dark:hover:text-white'"
                            :disabled="isLoading"
                            @click="runSuggestion(suggestion)"
                        >
                            {{ suggestion }}
                        </button>
                    </div>

                    <div
                        v-if="mode === 'image'"
                        class="mt-4 flex items-center gap-3 rounded-xl border border-light-steel bg-canvas-white px-3.5 py-2.5 dark:border-[#262624] dark:bg-black"
                    >
                        <img
                            :src="selectedImageUrl"
                            alt="Selected seed photo"
                            class="h-10 w-10 rounded-lg border border-light-steel object-cover dark:border-[#262624]"
                        />
                        <p class="min-w-0 flex-1 text-[13px] font-medium text-graphite dark:text-[#f5f5f4]">
                            Finding images visually similar to your selection
                        </p>
                        <button
                            type="button"
                            class="shrink-0 rounded-full border border-light-steel bg-ghostly-gray px-3 py-1 text-[12px] font-medium text-graphite transition-colors hover:bg-fog dark:border-[#262624] dark:bg-[#161618] dark:text-[#f5f5f4]"
                            @click="updateImageQuery"
                        >
                            Reset to text
                        </button>
                    </div>
                </div>

                <div class="min-w-0 lg:col-span-5">
                    <CodePreview
                        :code="imageCode"
                        filename="ImageSearch.php"
                        language="php"
                        :live="true"
                        :highlight-lines="highlightedLines"
                    />
                </div>
            </div>

            <div
                v-if="error"
                class="mt-6 rounded-2xl border border-light-steel bg-ghostly-gray px-5 py-6 text-[14px] text-charcoal dark:border-[#262624] dark:bg-[#111113] dark:text-[#a3a3a0]"
            >
                {{ error }}
            </div>

            <div v-else class="mt-8">
                <div
                    class="grid grid-cols-2 gap-3.5 transition-opacity duration-200 md:h-[440px] md:grid-cols-6 md:grid-rows-2"
                    :class="isLoading ? 'opacity-55' : ''"
                >
                    <template v-if="images.length">
                        <button
                            v-for="(image, index) in images"
                            :key="image.id"
                            type="button"
                            class="group relative overflow-hidden rounded-2xl border border-light-steel bg-ghostly-gray text-left shadow-xs dark:border-[#262624] dark:bg-[#111113]"
                            :class="tileClass[index]"
                            :disabled="isLoading"
                            @click="selectImage(image.url)"
                        >
                            <img
                                :src="image.url"
                                :alt="image.title"
                                class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.03] motion-reduce:transform-none"
                            />
                            <ImageHoverOverlay />
                        </button>
                    </template>
                    <template v-else-if="isLoading">
                        <div
                            v-for="i in 4"
                            :key="`img-skel-${i}`"
                            class="rounded-2xl border border-light-steel bg-ghostly-gray animate-pulse dark:border-[#262624] dark:bg-[#141413]"
                            :class="tileClass[i - 1]"
                        ></div>
                    </template>
                </div>

                <p class="mt-3 text-right font-mono text-[12px] text-subtle-gray">
                    Click any photo to run an image-to-image similarity search
                </p>
            </div>
        </div>
    </section>
</template>
