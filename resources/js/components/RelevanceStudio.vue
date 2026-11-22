<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import CodePreview from './CodePreview.vue';
import CopyCommand from './CopyCommand.vue';
import OrbitalRelevancePlot from './OrbitalRelevancePlot.vue';
import { RELEVANCE_MODES, RELEVANCE_PRESETS, TYPE_FILTERS } from '../data/landingExamples';

const props = defineProps({
    initial: {
        type: Object,
        default: () => ({}),
    },
});

const defaultMode = 'hybrid';
const initialPayload = props.initial?.[defaultMode] || {
    query: RELEVANCE_PRESETS[0].query,
    mode: defaultMode,
    filters: TYPE_FILTERS[0].expr,
    engine: 'Elasticsearch',
    total: 0,
    results: [],
    dsl: {},
};

const searchQuery = ref(initialPayload.query || RELEVANCE_PRESETS[0].query);
const activeMode = ref(initialPayload.mode || defaultMode);
const activeFilterId = ref('all');
const results = ref(initialPayload.results || []);
const engineName = ref(initialPayload.engine || 'Elasticsearch');
const rawDsl = ref(initialPayload.dsl || {});
const activeIndex = ref(0);
const isLoading = ref(false);
const errorMessage = ref('');
const latencyMs = ref(null);
const inspectOpen = ref(false);
const pulseKey = ref(0);
const rankDeltas = ref({});
const previousRankMap = ref({});
const liveAnnouncement = ref('');
const highlightedPhpLines = ref([3, 4]);

let requestSeq = 0;

const activeModeMeta = computed(() => (
    RELEVANCE_MODES.find((item) => item.id === activeMode.value) || RELEVANCE_MODES[1]
));

const activeFilter = computed(() => (
    TYPE_FILTERS.find((item) => item.id === activeFilterId.value) || TYPE_FILTERS[0]
));

const phpQuote = (value) => String(value).replace(/\\/g, '\\\\').replace(/'/g, "\\'");

const phpCode = computed(() => {
    const lines = [
        '$netflix->newSearch()',
        '    ->properties($props)',
    ];

    if (activeMode.value === 'hybrid') {
        lines.push('    ->semantic()');
        lines.push('    ->textScoreMultiplier(0.05)');
    } else if (activeMode.value === 'vector') {
        lines.push('    ->semantic()');
        lines.push('    ->disableKeywordSearch()');
    }

    lines.push(`    ->queryString('${phpQuote(searchQuery.value.trim() || RELEVANCE_PRESETS[0].query)}')`);

    if (activeFilterId.value !== 'all') {
        lines.push(`    ->filters('${activeFilter.value.expr}')`);
    }

    lines.push('    ->size(5)');
    lines.push('    ->get();');

    return lines.join('\n');
});

const formattedDsl = computed(() => {
    if (!rawDsl.value || Object.keys(rawDsl.value).length === 0) {
        return '{}';
    }

    return JSON.stringify(rawDsl.value, null, 2);
});

const dslLineCount = computed(() => formattedDsl.value.split('\n').length);

const dslHighlightLines = computed(() => {
    const lines = formattedDsl.value.split('\n');
    const hits = [];

    if (activeFilterId.value !== 'all') {
        const needle = activeFilterId.value === 'movie' ? '"value": "Movie"' : '"value": "TV Show"';
        lines.forEach((line, idx) => {
            if (line.includes(needle)) hits.push(idx + 1);
        });
    } else if (activeMode.value !== 'keyword') {
        lines.forEach((line, idx) => {
            if (line.includes('"knn":') || line.includes('384-dim float vector')) hits.push(idx + 1);
        });
    }

    return hits.slice(0, 6);
});

let previousPhp = phpCode.value;

watch(phpCode, (next) => {
    const prevSet = new Set(previousPhp.split('\n').map((l) => l.trim()));
    const nextLines = next.split('\n');
    const changed = [];

    nextLines.forEach((line, idx) => {
        if (!prevSet.has(line.trim())) changed.push(idx + 1);
    });

    if (changed.length === 0 && activeMode.value === 'keyword') {
        const qIdx = nextLines.findIndex((l) => l.includes('->queryString('));
        if (qIdx !== -1) changed.push(qIdx + 1);
    }

    highlightedPhpLines.value = changed;
    previousPhp = next;
});

const snapshotCurrentRanks = () => {
    const map = {};
    results.value.forEach((item, idx) => {
        if (item._id) map[item._id] = idx;
    });
    previousRankMap.value = map;
};

const computeDeltas = (nextResults, comparePrevious) => {
    if (!comparePrevious || Object.keys(previousRankMap.value).length === 0) {
        rankDeltas.value = {};
        return;
    }

    const deltas = {};
    nextResults.forEach((item, idx) => {
        const prev = previousRankMap.value[item._id];
        if (prev === undefined) {
            deltas[item._id] = 'new';
        } else {
            deltas[item._id] = prev - idx;
        }
    });
    rankDeltas.value = deltas;
};

const applyPayload = (payload, { comparePrevious, elapsed }) => {
    const nextResults = payload.results || [];
    computeDeltas(nextResults, comparePrevious);
    results.value = nextResults;
    engineName.value = payload.engine || 'Elasticsearch';
    rawDsl.value = payload.dsl || {};
    activeIndex.value = 0;
    latencyMs.value = elapsed;
    pulseKey.value += 1;

    const topTitle = nextResults[0]?.title;
    liveAnnouncement.value = topTitle
        ? `${activeModeMeta.value.label} mode updated. Rank 1 is ${topTitle}.`
        : `${activeModeMeta.value.label} mode returned no matching titles.`;
};

const runSearch = async ({ comparePrevious = true } = {}) => {
    const query = searchQuery.value.trim();
    if (!query) return;

    snapshotCurrentRanks();
    const seq = ++requestSeq;
    isLoading.value = true;
    errorMessage.value = '';
    const startedAt = typeof performance !== 'undefined' ? performance.now() : Date.now();

    try {
        const response = await axios.post('/api/search/netflix', {
            query,
            mode: activeMode.value,
            filters: activeFilter.value.expr,
            retrieve: ['title', 'description', 'type', 'release_year', 'director'],
            size: 5,
        });

        if (seq !== requestSeq) return;

        const now = typeof performance !== 'undefined' ? performance.now() : Date.now();
        applyPayload(response.data, {
            comparePrevious,
            elapsed: Math.max(1, Math.round(now - startedAt)),
        });
    } catch {
        if (seq !== requestSeq) return;

        const fallback = props.initial?.[activeMode.value];
        const isDefaultQuery = query === (fallback?.query || '') && activeFilterId.value === 'all';
        if (fallback && isDefaultQuery) {
            applyPayload(fallback, {
                comparePrevious,
                elapsed: null,
            });
        } else {
            errorMessage.value = 'Could not reach the live search cluster for that query.';
        }
    } finally {
        if (seq === requestSeq) {
            isLoading.value = false;
        }
    }
};

const selectMode = (modeId) => {
    if (modeId === activeMode.value) return;
    activeMode.value = modeId;
    runSearch({ comparePrevious: true });
};

const selectFilter = (filterId) => {
    if (filterId === activeFilterId.value) return;
    activeFilterId.value = filterId;
    runSearch({ comparePrevious: true });
};

const selectPreset = (query) => {
    searchQuery.value = query;
    runSearch({ comparePrevious: false });
};

const submitQuery = () => {
    runSearch({ comparePrevious: false });
};

const onResultKeyDown = (event) => {
    const row = event.currentTarget.closest('li');
    const target = event.key === 'ArrowDown' ? row?.nextElementSibling : event.key === 'ArrowUp' ? row?.previousElementSibling : null;
    if (!target) return;
    event.preventDefault();
    target.querySelector('button')?.focus();
};

const showRequestButton = ref(null);
const hideRequestButton = ref(null);

const setInspectOpen = async (open) => {
    inspectOpen.value = open;
    await nextTick();
    (open ? hideRequestButton : showRequestButton).value?.focus();
};

const deltaLabel = (id) => {
    const delta = rankDeltas.value[id];
    if (delta === undefined || delta === null || delta === 0) return null;
    if (delta === 'new') return { text: 'New', tone: 'up', label: 'new in the top 5' };
    if (delta > 0) return { text: `↑ ${delta}`, tone: 'up', label: `up ${delta} places` };
    return { text: `↓ ${Math.abs(delta)}`, tone: 'down', label: `down ${Math.abs(delta)} places` };
};

const statusLine = computed(() => (
    latencyMs.value === null
        ? `${activeModeMeta.value.short}, recorded preset`
        : `${activeModeMeta.value.short}, live from ${engineName.value} in ${latencyMs.value} ms`
));
</script>

<template>
    <section id="playground" class="relative scroll-mt-16 bg-white dark:bg-black">
        <div class="grid lg:grid-cols-12">
            <div class="px-6 pt-10 pb-8 sm:px-10 sm:pt-16 sm:pb-12 lg:col-span-7">
                <h1 class="max-w-[15ch] text-balance text-[38px] leading-[1] sm:leading-[0.98] font-semibold tracking-[-0.04em] text-[#171717] sm:text-[60px] lg:text-[68px] dark:text-[#fafafa]">
                    See what makes a result rank.
                </h1>

                <p class="mt-5 max-w-[34rem] text-[16.5px] leading-[1.6] text-[#525252] sm:text-[18px] dark:text-[#a1a1aa]">
                    Sigmie is a PHP library for keyword, semantic and hybrid search on Elasticsearch and OpenSearch. Change one line of the query and watch the ranking reorder live.
                </p>

                <div class="mt-7 flex flex-wrap items-center gap-3">
                    <Link
                        href="/docs/v2/quick-start"
                        class="press inline-flex items-center rounded-full bg-[#171717] px-5 whitespace-nowrap py-2.5 text-[14px] font-medium text-white hover:bg-black dark:bg-[#fafafa] dark:text-[#171717] dark:hover:bg-white"
                    >
                        Read the quick start
                    </Link>
                    <CopyCommand command="composer require sigmie/sigmie" />
                </div>
            </div>

            <form
                class="flex flex-col justify-center gap-5 border-t border-[#eaeaea] bg-[#fafafa] px-6 py-7 sm:px-10 lg:col-span-5 lg:border-t-0 lg:border-l lg:px-8 dark:border-white/[0.1] dark:bg-[#0a0a0a]"
                @submit.prevent="submitQuery"
            >
                <div>
                    <label for="relevance-query" class="text-[13px] font-medium text-[#171717] dark:text-[#fafafa]">
                        Search movie and TV show plots
                    </label>
                    <div class="mt-2 flex items-center gap-2">
                        <input
                            id="relevance-query"
                            v-model="searchQuery"
                            type="text"
                            autocomplete="off"
                            placeholder="Describe a plot or mood…"
                            class="min-w-0 flex-1 rounded-full border border-[#e5e5e5] bg-white px-4 py-2 text-[14px] text-[#171717] placeholder:text-[#737373] focus:border-[#171717] focus:ring-0 dark:border-white/[0.14] dark:bg-black dark:text-[#fafafa] dark:placeholder:text-[#8b8b93] dark:focus:border-white/50"
                        />
                        <button
                            type="submit"
                            :disabled="isLoading"
                            class="press shrink-0 rounded-full bg-[#171717] px-4 py-2 text-[13.5px] font-medium text-white hover:bg-black disabled:opacity-50 dark:bg-[#fafafa] dark:text-[#171717] dark:hover:bg-white"
                        >
                            Search
                        </button>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-1.5" role="group" aria-label="Example queries">
                        <button
                            v-for="preset in RELEVANCE_PRESETS"
                            :key="preset.query"
                            type="button"
                            class="press rounded-full border px-2.5 py-1 text-[12.5px]"
                            :class="searchQuery.trim() === preset.query
                                ? 'border-[#171717] text-[#171717] dark:border-[#fafafa] dark:text-[#fafafa]'
                                : 'border-[#e5e5e5] text-[#525252] hover:border-[#a3a3a3] hover:text-[#171717] dark:border-white/[0.14] dark:text-[#a1a1aa] dark:hover:text-white'"
                            @click="selectPreset(preset.query)"
                        >
                            {{ preset.label }}
                        </button>
                    </div>
                </div>

                <div>
                    <div
                        class="grid grid-cols-3 rounded-full border border-[#e5e5e5] bg-white p-1 dark:border-white/[0.14] dark:bg-black"
                        role="group"
                        aria-label="Ranking mode"
                    >
                        <button
                            v-for="mode in RELEVANCE_MODES"
                            :key="mode.id"
                            type="button"
                            :aria-pressed="activeMode === mode.id"
                            class="press rounded-full py-1.5 text-[13.5px] font-medium"
                            :class="activeMode === mode.id
                                ? 'bg-[#171717] text-white dark:bg-[#fafafa] dark:text-[#171717]'
                                : 'text-[#525252] hover:text-[#171717] dark:text-[#a1a1aa] dark:hover:text-white'"
                            @click="selectMode(mode.id)"
                        >
                            {{ mode.label }}
                        </button>
                    </div>
                    <p class="mt-2.5 min-h-[2.6em] text-[13px] leading-[1.45] text-[#525252] dark:text-[#a1a1aa]">
                        {{ activeModeMeta.detail }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span id="type-filter-label" class="text-[13px] text-[#525252] dark:text-[#a1a1aa]">Show</span>
                    <div class="inline-flex gap-1" role="group" aria-labelledby="type-filter-label">
                        <button
                            v-for="opt in TYPE_FILTERS"
                            :key="opt.id"
                            type="button"
                            :aria-pressed="activeFilterId === opt.id"
                            class="press rounded-full px-3 py-1 text-[13px] font-medium"
                            :class="activeFilterId === opt.id
                                ? 'bg-[#e9e9eb] text-[#171717] dark:bg-white/[0.14] dark:text-white'
                                : 'text-[#525252] hover:text-[#171717] dark:text-[#a1a1aa] dark:hover:text-white'"
                            @click="selectFilter(opt.id)"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="grid border-t border-[#eaeaea] lg:grid-cols-12 dark:border-white/[0.1]">
            <div class="flex min-w-0 flex-col border-b border-[#eaeaea] lg:col-span-6 lg:border-r lg:border-b-0 dark:border-white/[0.1]">
                <div class="flex items-baseline justify-between px-6 pt-5 sm:px-10 lg:px-8">
                    <h2 class="text-[14px] font-medium text-[#171717] dark:text-[#fafafa]">Rank orbit</h2>
                    <span class="text-[13px] text-[#737373] dark:text-[#8b8b93]">Inner ring is rank 1</span>
                </div>
                <div class="flex flex-1 items-center justify-center px-3 py-4 sm:px-6">
                    <OrbitalRelevancePlot
                        :query="searchQuery"
                        :mode="activeMode"
                        :items="results"
                        :active-index="activeIndex"
                        :pulse-key="pulseKey"
                        :deltas="rankDeltas"
                        :loading="isLoading"
                        @select="(idx) => { activeIndex = idx; }"
                    />
                </div>
            </div>

            <div class="flex min-w-0 flex-col lg:col-span-6">
                <div class="flex items-baseline justify-between gap-4 px-6 pt-5 pb-3 sm:px-10 lg:px-8">
                    <h2 class="text-[14px] font-medium text-[#171717] dark:text-[#fafafa]">Top 5</h2>
                    <span class="truncate text-[13px] tabular-nums text-[#737373] dark:text-[#8b8b93]">{{ statusLine }}</span>
                </div>

                <div class="sr-only" aria-live="polite" aria-atomic="true">
                    {{ liveAnnouncement }}
                </div>

                <p v-if="errorMessage" class="px-6 pb-8 text-[14px] text-[#525252] sm:px-10 lg:px-8 dark:text-[#a1a1aa]">
                    {{ errorMessage }} Try one of the example queries above.
                </p>

                <TransitionGroup
                    v-else
                    tag="ol"
                    move-class="rank-move"
                    class="flex-1 transition-opacity duration-200"
                    :class="isLoading ? 'opacity-60' : ''"
                    aria-label="Ranked search results"
                >
                    <li
                        v-for="(item, index) in results"
                        :key="item._id || index"
                        class="border-t border-[#f0f0f0] bg-white dark:border-white/[0.07] dark:bg-black"
                    >
                        <button
                            type="button"
                            class="flex w-full items-start gap-4 px-6 py-3.5 text-left sm:px-10 lg:px-8"
                            :class="activeIndex === index ? 'bg-[#fafafa] dark:bg-white/[0.04]' : ''"
                            :aria-current="activeIndex === index ? 'true' : undefined"
                            @mouseenter="activeIndex = index"
                            @focus="activeIndex = index"
                            @click="activeIndex = index"
                            @keydown="onResultKeyDown"
                        >
                            <span
                                class="mt-px flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[12px] font-semibold tabular-nums"
                                :class="index === 0
                                    ? 'bg-orange-ink text-white dark:bg-magic-orange dark:text-black'
                                    : 'border border-[#e5e5e5] text-[#404040] dark:border-white/[0.16] dark:text-[#d4d4d8]'"
                            >
                                {{ index + 1 }}
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-baseline gap-x-2">
                                    <span class="text-[15px] font-semibold text-[#171717] dark:text-[#fafafa]">{{ item.title }}</span>
                                    <span class="text-[13px] text-[#737373] dark:text-[#8b8b93]">{{ item.type }}, {{ item.release_year }}</span>
                                </span>
                                <span class="mt-0.5 line-clamp-1 text-[13.5px] leading-snug text-[#525252] dark:text-[#a1a1aa]">
                                    {{ item.description }}
                                </span>
                            </span>

                            <span class="flex shrink-0 flex-col items-end gap-0.5 tabular-nums">
                                <span class="text-[13px] text-[#404040] dark:text-[#d4d4d8]">{{ Number(item._score ?? 0).toFixed(2) }}</span>
                                <span
                                    v-if="deltaLabel(item._id)"
                                    class="text-[12px] font-medium"
                                    :class="deltaLabel(item._id).tone === 'up' ? 'text-orange-ink dark:text-[#ff7b47]' : 'text-[#737373] dark:text-[#8b8b93]'"
                                >
                                    <span aria-hidden="true">{{ deltaLabel(item._id).text }}</span>
                                    <span class="sr-only">{{ deltaLabel(item._id).label }}</span>
                                </span>
                            </span>
                        </button>
                    </li>
                </TransitionGroup>
            </div>
        </div>

        <div class="border-t border-[#eaeaea] bg-[#0F111A] dark:border-white/[0.1]">
            <div v-if="!inspectOpen">
                <CodePreview
                    :code="phpCode"
                    filename="Search.php"
                    language="php"
                    :flat="true"
                    :compact="true"
                    :highlight-lines="highlightedPhpLines"
                >
                    <template #badge>
                        <button
                            ref="showRequestButton"
                            type="button"
                            class="press inline-flex items-center gap-1.5 rounded-full border border-white/15 px-3 py-1 text-[12px] text-[#e4e4e7] hover:border-white/35 hover:text-white"
                            aria-expanded="false"
                            @click="setInspectOpen(true)"
                        >
                            Show the {{ engineName }} request
                        </button>
                    </template>
                </CodePreview>
            </div>

            <div v-else id="request-inspector-panel">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/[0.08] px-6 py-3 sm:px-10 lg:px-8">
                    <p class="text-[13px] text-[#a1a1aa]">
                        The JSON the builder sent to {{ engineName }} for this search, {{ dslLineCount }} lines.
                    </p>
                    <button
                        ref="hideRequestButton"
                        type="button"
                        class="press rounded-full border border-white/15 px-3 py-1 text-[12px] text-[#d4d4d8] hover:border-white/35 hover:text-white"
                        aria-expanded="true"
                        @click="setInspectOpen(false)"
                    >
                        Hide request
                    </button>
                </div>

                <div class="grid divide-y divide-white/[0.08] lg:grid-cols-12 lg:divide-x lg:divide-y-0">
                    <div class="min-w-0 lg:col-span-5">
                        <CodePreview
                            :code="phpCode"
                            filename="Search.php"
                            language="php"
                            :flat="true"
                            :compact="true"
                            :highlight-lines="highlightedPhpLines"
                        />
                    </div>
                    <div class="min-w-0 lg:col-span-7">
                        <CodePreview
                            :code="formattedDsl"
                            :filename="`${engineName.toLowerCase()}-request.json`"
                            language="json"
                            :flat="true"
                            :compact="true"
                            :highlight-lines="dslHighlightLines"
                            max-height-class="max-h-[340px] overflow-y-auto"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
