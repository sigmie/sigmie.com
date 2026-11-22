<script setup>
import { computed, ref, watch } from 'vue';
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
const totalHits = ref(initialPayload.total || 0);
const engineName = ref(initialPayload.engine || 'Elasticsearch');
const rawDsl = ref(initialPayload.dsl || {});
const activeIndex = ref(0);
const isLoading = ref(false);
const errorMessage = ref('');
const statusSource = ref('Recorded preset');
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

const hitCountLabel = computed(() => (
    activeMode.value === 'vector'
        ? `${totalHits.value.toLocaleString()} vector candidates`
        : `${totalHits.value.toLocaleString()} indexed hits`
));

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

const applyPayload = (payload, { comparePrevious, sourceLabel, elapsed }) => {
    const nextResults = payload.results || [];
    computeDeltas(nextResults, comparePrevious);
    results.value = nextResults;
    totalHits.value = payload.total ?? nextResults.length;
    engineName.value = payload.engine || 'Elasticsearch';
    rawDsl.value = payload.dsl || {};
    activeIndex.value = 0;
    statusSource.value = sourceLabel;
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
            sourceLabel: 'Live',
            elapsed: Math.max(1, Math.round(now - startedAt)),
        });
    } catch {
        if (seq !== requestSeq) return;

        const fallback = props.initial?.[activeMode.value];
        const isDefaultQuery = query === (fallback?.query || '') && activeFilterId.value === 'all';
        if (fallback && isDefaultQuery) {
            applyPayload(fallback, {
                comparePrevious,
                sourceLabel: 'Recorded preset',
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

const onResultKeyDown = (event, index) => {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeIndex.value = Math.min(index + 1, results.value.length - 1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex.value = Math.max(index - 1, 0);
    }
};

const deltaLabel = (id) => {
    const delta = rankDeltas.value[id];
    if (delta === undefined || delta === null) return null;
    if (delta === 'new') return { text: 'new', tone: 'up' };
    if (delta > 0) return { text: `↑ ${delta}`, tone: 'up' };
    if (delta < 0) return { text: `↓ ${Math.abs(delta)}`, tone: 'down' };
    return { text: '· same', tone: 'flat' };
};
</script>

<template>
    <section id="playground" class="scroll-mt-20 bg-canvas-white pt-5 pb-12 sm:pt-7 sm:pb-16 lg:pt-8 lg:pb-18 dark:bg-[#08080a]">
        <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
            <div class="grid gap-7 lg:grid-cols-12 lg:items-start lg:gap-8">
                <div class="flex min-w-0 flex-col lg:col-span-5">
                    <h1 class="text-[36px] leading-[1.02] font-semibold tracking-[-0.04em] text-graphite sm:text-[44px] lg:text-[46px] dark:text-[#f5f5f4]">
                        See what makes<br />a result rank
                    </h1>

                    <p class="mt-3 text-[15.5px] leading-[1.5] text-charcoal dark:text-[#b4b4b0]">
                        Fluent search for PHP on Elasticsearch and OpenSearch. Change one line of PHP and watch live rankings reorganize.
                    </p>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <Link
                            href="/docs/v2/quick-start"
                            class="group inline-flex shrink-0 items-center gap-1.5 rounded-full bg-graphite px-4 py-1.5 text-[13px] font-medium text-white transition-colors hover:bg-black dark:bg-[#f5f5f4] dark:text-graphite dark:hover:bg-white"
                        >
                            <span>Quick start</span>
                            <span class="transition-transform duration-150 group-hover:translate-x-0.5" aria-hidden="true">→</span>
                        </Link>
                        <CopyCommand command="composer require sigmie/sigmie" :compact="true" />
                    </div>

                    <div class="mt-5 rounded-2xl border border-light-steel bg-ghostly-gray/85 p-3.5 dark:border-[#242422] dark:bg-[#111113]">
                        <form class="flex items-center gap-2" @submit.prevent="submitQuery">
                            <label for="relevance-query" class="sr-only">Search movie and TV titles</label>
                            <div class="relative flex-1">
                                <svg
                                    class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-ink dark:text-[#9ca3af]"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m1.7-4.3a6 6 0 11-12 0 6 6 0 0112 0z" />
                                </svg>
                                <input
                                    id="relevance-query"
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Describe a plot or mood..."
                                    class="w-full rounded-xl border border-light-steel bg-canvas-white py-1.5 pr-3 pl-9 text-[13.5px] text-graphite placeholder:text-muted-ink focus:border-graphite focus:ring-0 focus:outline-none dark:border-[#2c2c2a] dark:bg-black dark:text-[#f5f5f4] dark:placeholder:text-[#9ca3af] dark:focus:border-white/40"
                                />
                            </div>
                            <button
                                type="submit"
                                :disabled="isLoading"
                                class="shrink-0 rounded-xl bg-graphite px-3.5 py-1.5 text-[13px] font-medium text-white transition-colors hover:bg-black disabled:opacity-50 dark:bg-[#f5f5f4] dark:text-graphite dark:hover:bg-white"
                            >
                                Run
                            </button>
                        </form>

                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <button
                                v-for="preset in RELEVANCE_PRESETS"
                                :key="preset.query"
                                type="button"
                                class="rounded-full border px-2.5 py-0.5 text-[11.5px] font-medium transition-colors"
                                :class="searchQuery.trim() === preset.query
                                    ? 'border-graphite bg-graphite text-white dark:border-[#f5f5f4] dark:bg-[#f5f5f4] dark:text-graphite'
                                    : 'border-light-steel bg-canvas-white text-charcoal hover:border-graphite/40 dark:border-[#2c2c2a] dark:bg-black/60 dark:text-[#d4d4d8] dark:hover:text-white'"
                                @click="selectPreset(preset.query)"
                            >
                                {{ preset.label }}
                            </button>
                        </div>

                        <div class="mt-3 border-t border-light-steel/80 pt-3 dark:border-[#242422]">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-[11px] uppercase tracking-[0.06em] text-muted-ink dark:text-[#9ca3af]">
                                    Scoring mode
                                </span>
                                <span class="font-mono text-[11px] text-charcoal dark:text-[#d4d4d8]">
                                    {{ activeModeMeta.short }}
                                </span>
                            </div>

                            <div
                                class="mt-1.5 grid grid-cols-3 gap-1 rounded-xl border border-light-steel bg-canvas-white p-1 dark:border-[#242422] dark:bg-black"
                                role="radiogroup"
                                aria-label="Search relevance mode"
                            >
                                <button
                                    v-for="mode in RELEVANCE_MODES"
                                    :key="mode.id"
                                    type="button"
                                    role="radio"
                                    :aria-checked="activeMode === mode.id"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[13px] font-medium transition-colors"
                                    :class="activeMode === mode.id
                                        ? 'bg-graphite text-white shadow-xs dark:bg-[#f5f5f4] dark:text-graphite'
                                        : 'text-charcoal hover:text-graphite dark:text-[#b4b4b0] dark:hover:text-white'"
                                    @click="selectMode(mode.id)"
                                >
                                    <span
                                        v-if="activeMode === mode.id"
                                        class="h-1.5 w-1.5 rounded-full bg-magic-orange"
                                        aria-hidden="true"
                                    ></span>
                                    <span>{{ mode.label }}</span>
                                </button>
                            </div>

                            <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2">
                                <div class="inline-flex items-center gap-1" role="radiogroup" aria-label="Filter by title type">
                                    <button
                                        v-for="opt in TYPE_FILTERS"
                                        :key="opt.id"
                                        type="button"
                                        role="radio"
                                        :aria-checked="activeFilterId === opt.id"
                                        class="rounded-full border px-2.5 py-0.5 text-[11.5px] font-medium transition-colors"
                                        :class="activeFilterId === opt.id
                                            ? 'border-graphite bg-canvas-white text-graphite dark:border-white/40 dark:bg-[#1c1c1e] dark:text-white'
                                            : 'border-transparent text-muted-ink hover:text-graphite dark:text-[#9ca3af] dark:hover:text-white'"
                                        @click="selectFilter(opt.id)"
                                    >
                                        {{ opt.label }}
                                    </button>
                                </div>

                                <span class="font-mono text-[11px] text-muted-ink dark:text-[#9ca3af]">
                                    {{ statusSource }}<template v-if="latencyMs !== null"> · {{ engineName }} · {{ latencyMs }}ms</template>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3.5 min-w-0">
                        <CodePreview
                            :code="phpCode"
                            filename="Search.php"
                            language="php"
                            :compact="true"
                            :highlight-lines="highlightedPhpLines"
                        >
                            <template #badge>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 font-mono text-[11px] transition-colors"
                                    :class="inspectOpen
                                        ? 'border-magic-orange/60 bg-magic-orange/15 text-[#ff8f61]'
                                        : 'border-white/15 bg-white/[0.04] text-[#e4e4e7] hover:border-white/30 hover:text-white'"
                                    :aria-expanded="inspectOpen"
                                    aria-controls="request-inspector-panel"
                                    @click="inspectOpen = !inspectOpen"
                                >
                                    <span>Inspect request</span>
                                    <span class="text-[#a1a1aa]">{{ dslLineCount }}L</span>
                                </button>
                            </template>
                        </CodePreview>
                    </div>
                </div>

                <div class="min-w-0 lg:col-span-7">
                    <div class="overflow-hidden rounded-2xl border border-light-steel bg-ghostly-gray/60 dark:border-[#242422] dark:bg-[#111113]">
                        <div class="flex items-center justify-between gap-3 border-b border-light-steel/80 px-4 py-2.5 sm:px-5 dark:border-[#242422]">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-magic-orange" aria-hidden="true"></span>
                                <span class="font-mono text-[11.5px] font-medium text-graphite dark:text-[#f5f5f4]">
                                    Rank orbit · {{ activeModeMeta.label }}
                                </span>
                            </div>
                            <span class="font-mono text-[11px] text-muted-ink dark:text-[#9ca3af]">
                                {{ hitCountLabel }} · inner ring = #1
                            </span>
                        </div>

                        <div class="border-b border-light-steel/80 bg-canvas-white px-2 py-1.5 sm:px-4 dark:border-[#242422] dark:bg-[#0c0c0e]">
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

                        <div class="sr-only" aria-live="polite" aria-atomic="true">
                            {{ liveAnnouncement }}
                        </div>

                        <div v-if="errorMessage" class="px-5 py-6 text-[14px] text-charcoal dark:text-[#b4b4b0]">
                            {{ errorMessage }}
                        </div>

                        <ol
                            v-else
                            class="divide-y divide-light-steel/75 dark:divide-[#20201f]"
                            aria-label="Ranked search results"
                        >
                            <li
                                v-for="(item, index) in results"
                                :key="item._id || index"
                            >
                                <button
                                    type="button"
                                    class="group flex w-full items-start gap-3.5 px-4 py-2.5 text-left transition-colors sm:px-5"
                                    :class="activeIndex === index
                                        ? 'bg-canvas-white dark:bg-[#18181b]'
                                        : 'hover:bg-canvas-white/70 dark:hover:bg-white/[0.02]'"
                                    :aria-current="activeIndex === index ? 'true' : undefined"
                                    @mouseenter="activeIndex = index"
                                    @focus="activeIndex = index"
                                    @click="activeIndex = index"
                                    @keydown="onResultKeyDown($event, index)"
                                >
                                    <span
                                        class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full font-mono text-[11px] font-semibold transition-colors"
                                        :class="activeIndex === index
                                            ? 'bg-graphite text-white ring-2 ring-magic-orange/70 dark:bg-[#f5f5f4] dark:text-graphite'
                                            : index === 0
                                                ? 'bg-graphite text-white dark:bg-[#f5f5f4] dark:text-graphite'
                                                : 'border border-light-steel bg-canvas-white text-charcoal dark:border-[#2e2e2c] dark:bg-black dark:text-[#d4d4d8]'"
                                    >
                                        {{ index + 1 }}
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                            <span class="text-[14.5px] font-semibold text-graphite dark:text-[#f5f5f4]">
                                                {{ item.title }}
                                            </span>
                                            <span class="font-mono text-[11px] text-muted-ink dark:text-[#9ca3af]">
                                                {{ item.type }} · {{ item.release_year }}
                                            </span>
                                            <span
                                                v-if="deltaLabel(item._id)"
                                                class="rounded px-1.5 py-0.2 font-mono text-[10.5px] font-medium"
                                                :class="deltaLabel(item._id).tone === 'up'
                                                    ? 'bg-magic-orange/12 text-orange-ink dark:text-[#ff7b47]'
                                                    : deltaLabel(item._id).tone === 'down'
                                                        ? 'bg-graphite/[0.06] text-charcoal dark:bg-white/10 dark:text-[#d4d4d8]'
                                                        : 'text-muted-ink dark:text-[#9ca3af]'"
                                            >
                                                {{ deltaLabel(item._id).text }}
                                            </span>
                                        </div>
                                        <p class="mt-0.5 line-clamp-1 text-[13px] leading-snug text-charcoal/85 dark:text-[#b4b4b0]">
                                            {{ item.description }}
                                        </p>
                                    </div>

                                    <span class="mt-0.5 shrink-0 font-mono text-[11.5px] tabular-nums text-muted-ink dark:text-[#9ca3af]">
                                        {{ Number(item._score ?? 0).toFixed(2) }}
                                    </span>
                                </button>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>

            <div
                v-if="inspectOpen"
                id="request-inspector-panel"
                class="mt-8 overflow-hidden rounded-2xl border border-black/15 bg-[#0b0d11] text-[#e4e4e7] shadow-[0_28px_60px_-28px_rgba(0,0,0,0.35)] dark:border-white/[0.1]"
            >
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/[0.08] bg-[#111318] px-5 py-3">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="font-mono text-[12px] font-medium text-white">
                            Request inspector
                        </span>
                        <span class="rounded border border-white/15 bg-white/[0.05] px-2 py-0.5 font-mono text-[10.5px] text-[#d4d4d8]">
                            Engine: {{ engineName }}
                        </span>
                        <span class="font-mono text-[11px] text-[#a1a1aa]">
                            Serialized directly by the Sigmie builder that ran this search.
                        </span>
                    </div>
                    <button
                        type="button"
                        class="rounded-full border border-white/15 px-3 py-1 font-mono text-[11px] text-[#d4d4d8] transition-colors hover:border-white/30 hover:text-white"
                        @click="inspectOpen = false"
                    >
                        Close
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
                            :filename="`elasticsearch-request.json (${dslLineCount} lines)`"
                            language="json"
                            :flat="true"
                            :compact="true"
                            :highlight-lines="dslHighlightLines"
                            max-height-class="max-h-[360px] overflow-y-auto"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
