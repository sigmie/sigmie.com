<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import CodePreview from './CodePreview.vue';
import QueryInput from './QueryInput.vue';
import Menu from './Menu.vue';
import SearchIcon from './icons/SearchIcon.vue';
import { NETFLIX_PRESETS, NETFLIX_TYPE_FILTERS } from '../data/landingExamples';

const searchQuery = ref('zombie apocalypse');
const typeFilter = ref('all');
const retrieveType = ref(true);
const retrieveYear = ref(true);
const retrieveDirector = ref(true);
const results = ref([]);
const isSearching = ref(false);
const hasSearched = ref(false);
const searchError = ref('');
const highlightedLines = ref([]);

const activeFilter = computed(() => (
    NETFLIX_TYPE_FILTERS.find((item) => item.id === typeFilter.value) || NETFLIX_TYPE_FILTERS[0]
));

const retrieveFields = computed(() => {
    const fields = ['title', 'description'];
    if (retrieveType.value) fields.push('type');
    if (retrieveYear.value) fields.push('release_year');
    if (retrieveDirector.value) fields.push('director');
    return fields;
});

const fieldToggles = computed(() => [
    { key: 'type', label: 'Type', active: retrieveType.value, toggle: () => { retrieveType.value = !retrieveType.value; } },
    { key: 'year', label: 'Year', active: retrieveYear.value, toggle: () => { retrieveYear.value = !retrieveYear.value; } },
    { key: 'director', label: 'Director', active: retrieveDirector.value, toggle: () => { retrieveDirector.value = !retrieveDirector.value; } },
]);

const phpQuote = (value) => String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'");

const codeString = computed(() => {
    const fields = retrieveFields.value;
    const fieldLines = fields.map((field, index) => (
        `        '${field}'${index < fields.length - 1 ? ',' : ''}`
    ));

    return [
        '$netflixIndex->newSearch()',
        '    ->semantic()',
        `    ->queryString('${phpQuote(searchQuery.value || 'zombie apocalypse')}')`,
        `    ->filters('${activeFilter.value.expr}')`,
        '    ->retrieve([',
        ...fieldLines,
        '    ])',
        '    ->size(4)',
        '    ->hits();',
    ].join('\n');
});

let previousCode = codeString.value;

watch(codeString, (next) => {
    const prevLines = previousCode.split('\n');
    const nextLines = next.split('\n');
    const changed = [];
    const total = Math.max(prevLines.length, nextLines.length);

    for (let index = 0; index < total; index += 1) {
        if (prevLines[index] !== nextLines[index]) changed.push(index + 1);
    }

    highlightedLines.value = changed;
    previousCode = next;
});

const performSearch = async (query = null) => {
    const searchTerm = (query ?? searchQuery.value).trim();
    if (!searchTerm) return;

    if (query) searchQuery.value = query;

    isSearching.value = true;
    hasSearched.value = true;
    searchError.value = '';

    try {
        const response = await axios.post('/api/search/netflix', {
            query: searchTerm,
            retrieve: retrieveFields.value,
            filters: activeFilter.value.expr,
        });
        results.value = response.data.results || [];
    } catch {
        results.value = [];
        searchError.value = 'The live search cluster is currently unreachable.';
    } finally {
        isSearching.value = false;
    }
};

watch([typeFilter, retrieveType, retrieveYear, retrieveDirector], () => {
    if (hasSearched.value) performSearch();
});

onMounted(() => {
    performSearch('zombie apocalypse');
});
</script>

<template>
    <section id="semantic-search" class="scroll-mt-20 border-t border-light-steel bg-ghostly-gray py-20 sm:py-24 lg:py-28 dark:border-white/[0.08] dark:bg-[#09090b]">
        <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="inline-flex items-center gap-2 font-mono text-[12px] font-medium uppercase tracking-[0.08em] text-magic-orange">
                    <span class="h-1.5 w-1.5 rounded-full bg-magic-orange" aria-hidden="true"></span>
                    01 · Live semantic search
                </p>
                <h2 class="mt-3 text-[28px] leading-[1.12] font-semibold tracking-[-0.03em] text-graphite sm:text-[40px] dark:text-[#f5f5f4]">
                    Search by meaning, not exact words.
                </h2>
                <p class="mt-4 text-[16px] leading-relaxed text-charcoal dark:text-[#a3a3a0]">
                    Every query runs live against Elasticsearch using 384-dim embeddings. Toggle filters or retrieved fields to watch both the results and PHP code update together.
                </p>
            </div>

            <div class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-start">
                <div class="rounded-2xl border border-light-steel bg-canvas-white p-5 shadow-[var(--shadow-xl)] sm:p-6 lg:col-span-7 dark:border-[#262624] dark:bg-[#111113] dark:shadow-none">
                    <QueryInput
                        v-model="searchQuery"
                        placeholder="Try a mood, genre, or plot premise..."
                        input-label="Search titles"
                        :disabled="isSearching"
                        @submit="performSearch()"
                    >
                        <template #icon>
                            <SearchIcon />
                        </template>
                    </QueryInput>

                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <button
                            v-for="preset in NETFLIX_PRESETS"
                            :key="preset.query"
                            type="button"
                            class="rounded-full border px-3 py-1 text-[12px] font-medium transition-colors"
                            :class="searchQuery === preset.query
                                ? 'border-graphite bg-graphite text-white dark:border-[#f5f5f4] dark:bg-[#f5f5f4] dark:text-graphite'
                                : 'border-light-steel bg-canvas-white text-charcoal hover:border-graphite/40 dark:border-[#262624] dark:bg-black/40 dark:text-[#a3a3a0] dark:hover:border-white/25 dark:hover:text-white'"
                            :disabled="isSearching"
                            @click="performSearch(preset.query)"
                        >
                            {{ preset.label }}
                        </button>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-y border-light-steel/80 py-3.5 dark:border-[#262624]">
                        <div class="inline-flex rounded-full border border-light-steel bg-ghostly-gray p-0.5 dark:border-[#262624] dark:bg-black/60" role="radiogroup" aria-label="Title type">
                            <button
                                v-for="option in NETFLIX_TYPE_FILTERS"
                                :key="option.id"
                                type="button"
                                role="radio"
                                :aria-checked="typeFilter === option.id"
                                class="rounded-full px-3 py-1 text-[12px] font-medium transition-colors"
                                :class="typeFilter === option.id
                                    ? 'bg-canvas-white text-graphite shadow-xs dark:bg-[#262624] dark:text-white'
                                    : 'text-subtle-gray hover:text-graphite dark:hover:text-white'"
                                @click="typeFilter = option.id"
                            >
                                {{ option.label }}
                            </button>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="Retrieved fields">
                            <span class="mr-1 font-mono text-[11px] text-subtle-gray">retrieve:</span>
                            <button
                                v-for="field in fieldToggles"
                                :key="field.key"
                                type="button"
                                :aria-pressed="field.active"
                                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[12px] font-medium transition-colors"
                                :class="field.active
                                    ? 'border-magic-orange/35 bg-magic-orange/10 text-graphite dark:text-[#f5f5f4]'
                                    : 'border-light-steel text-subtle-gray hover:text-graphite dark:border-[#262624] dark:hover:text-white'"
                                @click="field.toggle"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full transition-colors"
                                    :class="field.active ? 'bg-magic-orange' : 'bg-light-steel dark:bg-[#3f3f46]'"
                                    aria-hidden="true"
                                ></span>
                                {{ field.label }}
                            </button>
                        </div>
                    </div>

                    <div v-if="searchError" class="mt-5 rounded-xl border border-light-steel bg-ghostly-gray px-4 py-4 text-[14px] text-charcoal dark:border-[#262624] dark:bg-[#161618] dark:text-[#a3a3a0]">
                        {{ searchError }}
                        <Link href="/docs/v2/semantic-search" class="ml-1 font-medium text-magic-orange hover:underline">
                            Read the semantic search guide →
                        </Link>
                    </div>
                    <div v-else class="mt-5">
                        <Menu
                            v-if="hasSearched"
                            :items="results"
                            :loading="isSearching"
                            empty-message="No titles matched that description."
                        />
                    </div>
                </div>

                <div class="min-w-0 lg:col-span-5 lg:sticky lg:top-24">
                    <CodePreview
                        :code="codeString"
                        filename="NetflixSearch.php"
                        language="php"
                        :live="true"
                        :highlight-lines="highlightedLines"
                    />
                </div>
            </div>
        </div>
    </section>
</template>
