<script setup>
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';

const open = defineModel('open', { type: Boolean, default: false });

// Listed before the first keystroke, so the dialog never opens empty.
const SUGGESTED = [
    { title: 'Quick start', section: 'Getting Started', url: '/docs/v2/quick-start' },
    { title: 'Search', section: 'Core Concepts', url: '/docs/v2/search' },
    { title: 'Semantic Search', section: 'Features', url: '/docs/v2/semantic-search' },
    { title: 'Mappings & Properties', section: 'Core Concepts', url: '/docs/v2/mappings' },
    { title: 'Filter Parser', section: 'Utilities', url: '/docs/v2/filter-parser' },
    { title: 'Facets', section: 'Features', url: '/docs/v2/facets' },
    { title: 'Laravel Scout', section: 'Integrations', url: '/docs/v2/laravel-scout' },
    { title: 'MCP Server', section: 'Integrations', url: '/docs/v2/mcp' },
];

const query = ref('');
const results = ref([]);
const loading = ref(false);
const selected = ref(0);
const input = ref(null);
const list = ref(null);
let timer;
let seq = 0;

const trimmed = computed(() => query.value.trim());
const items = computed(() => (trimmed.value ? results.value : SUGGESTED));
const terms = computed(() => trimmed.value.toLowerCase().split(/\s+/).filter((t) => t.length > 1));

const status = computed(() => {
    if (!trimmed.value) return 'Suggested';
    if (loading.value) return 'Searching…';
    return `${results.value.length} result${results.value.length === 1 ? '' : 's'}`;
});

const escapeRegExp = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

// One line of the passage, opened at the first query term, split into
// parts so the terms render bold without v-html.
const snippet = (content) => {
    const text = String(content || '')
        .replace(/\/\/\s*\[tl![^\]]*\]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
    const lower = text.toLowerCase();
    const first = Math.min(...terms.value.map((t) => lower.indexOf(t)).filter((i) => i >= 0), Infinity);
    const start = first === Infinity || first < 48 ? 0 : first - 32;
    const slice = (start ? '…' : '') + text.slice(start, start + 180);
    if (!terms.value.length) return [{ text: slice, hit: false }];

    const pattern = new RegExp(`(${terms.value.map(escapeRegExp).join('|')})`, 'gi');
    return slice
        .split(pattern)
        .filter(Boolean)
        .map((part) => ({ text: part, hit: terms.value.includes(part.toLowerCase()) }));
};

watch(trimmed, (q) => {
    clearTimeout(timer);
    selected.value = 0;
    seq++;

    if (!q) {
        results.value = [];
        loading.value = false;
        return;
    }

    loading.value = true;
    const mine = seq;
    timer = setTimeout(async () => {
        try {
            const { data } = await axios.post('/api/search/docs', { query: q });
            if (mine === seq) results.value = data.results || [];
        } catch {
            if (mine === seq) results.value = [];
        } finally {
            if (mine === seq) loading.value = false;
        }
    }, 200);
});

watch(open, (isOpen) => {
    document.documentElement.style.overflow = isOpen ? 'hidden' : '';
    if (!isOpen) return;
    query.value = '';
    selected.value = 0;
    nextTick(() => input.value?.focus());
});

onUnmounted(() => {
    clearTimeout(timer);
    document.documentElement.style.overflow = '';
});

const close = () => {
    open.value = false;
};

const go = (item) => {
    if (!item?.url) return;
    close();
    router.visit(item.url);
};

const move = (step) => {
    const count = items.value.length;
    if (!count) return;
    selected.value = (selected.value + step + count) % count;
    nextTick(() => list.value?.querySelector('[aria-selected="true"]')?.scrollIntoView({ block: 'nearest' }));
};

const onKeydown = (event) => {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        move(1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        move(-1);
    } else if (event.key === 'Enter') {
        event.preventDefault();
        go(items.value[selected.value]);
    }
};
</script>

<template>
    <Transition
        enter-active-class="transition-opacity duration-150 ease-out"
        enter-from-class="opacity-0"
        leave-active-class="transition-opacity duration-100 ease-in"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-start justify-center bg-black/20 px-4 pt-[10vh] backdrop-blur-[2px] sm:pt-[14vh] dark:bg-black/60"
            @mousedown.self="close"
        >
            <div
                role="dialog"
                aria-modal="true"
                aria-label="Search documentation"
                class="flex max-h-[min(34rem,78vh)] w-full max-w-[640px] flex-col overflow-hidden rounded-2xl border border-[#eaeaea] bg-white shadow-[0_24px_80px_-16px_rgb(0_0_0/0.28)] dark:border-white/[0.12] dark:bg-[#0a0a0a] dark:shadow-[0_24px_80px_-16px_rgb(0_0_0/0.9)]"
                @keydown.esc.prevent="close"
            >
                <div class="flex items-center gap-3 border-b border-[#eaeaea] px-4 dark:border-white/[0.1]">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center text-[#737373] dark:text-[#8b8b93]" aria-hidden="true">
                        <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                        <svg v-else class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        ref="input"
                        v-model="query"
                        type="text"
                        role="combobox"
                        aria-label="Search documentation"
                        aria-autocomplete="list"
                        aria-controls="search-results"
                        aria-expanded="true"
                        :aria-activedescendant="items.length ? `search-option-${selected}` : undefined"
                        autocomplete="off"
                        spellcheck="false"
                        placeholder="Search the docs"
                        class="h-14 min-w-0 flex-1 border-0 bg-transparent p-0 text-[16px] text-[#171717] placeholder:text-[#a1a1aa] focus:ring-0 focus:outline-none dark:text-[#fafafa] dark:placeholder:text-[#71717a]"
                        @keydown="onKeydown"
                    />
                    <button
                        type="button"
                        class="press rounded-md border border-[#eaeaea] bg-[#fafafa] px-1.5 py-0.5 font-mono text-[11px] text-[#737373] hover:text-[#171717] dark:border-white/[0.12] dark:bg-white/[0.04] dark:text-[#8b8b93] dark:hover:text-white"
                        aria-label="Close search"
                        @click="close"
                    >
                        Esc
                    </button>
                </div>

                <div ref="list" class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-2" tabindex="0" aria-label="Search results">
                    <p class="px-3 pt-1.5 pb-2 text-[12px] font-medium text-[#737373] dark:text-[#8b8b93]" aria-live="polite">
                        {{ status }}
                    </p>

                    <ul id="search-results" role="listbox" aria-label="Documentation pages">
                        <li
                            v-for="(item, index) in items"
                            :id="`search-option-${index}`"
                            :key="item.url"
                            role="option"
                            :aria-selected="index === selected"
                            class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 transition-colors duration-100"
                            :class="index === selected ? 'bg-[#f4f4f5] dark:bg-white/[0.07]' : ''"
                            @mousemove="selected = index"
                            @click="go(item)"
                        >
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border text-[#737373] dark:text-[#8b8b93]"
                                :class="index === selected
                                    ? 'border-[#e5e5e5] bg-white dark:border-white/[0.14] dark:bg-black'
                                    : 'border-[#eaeaea] bg-[#fafafa] dark:border-white/[0.1] dark:bg-white/[0.03]'"
                                aria-hidden="true"
                            >
                                <svg v-if="item.url.includes('#')" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5-3.9 19.5m-2.1-19.5-3.9 19.5" />
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[14px] font-medium text-[#171717] dark:text-[#fafafa]">
                                    {{ item.title }}
                                </span>
                                <span class="mt-0.5 block truncate text-[12.5px] text-[#6b6b6b] [font-variant-ligatures:none] dark:text-[#8b8b93]">
                                    <template v-if="trimmed">
                                        <span class="text-[#525252] dark:text-[#a1a1aa]">{{ item.page_title }}</span>
                                        <span class="px-1.5" aria-hidden="true">·</span>
                                        <template v-for="(part, i) in snippet(item.content)" :key="i">
                                            <strong v-if="part.hit" class="font-medium text-[#171717] dark:text-[#fafafa]">{{ part.text }}</strong>
                                            <template v-else>{{ part.text }}</template>
                                        </template>
                                    </template>
                                    <template v-else>{{ item.section }}</template>
                                </span>
                            </span>

                            <svg
                                v-if="index === selected"
                                class="h-4 w-4 shrink-0 text-[#a1a1aa] dark:text-[#71717a]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10 4 15l5 5m-5-5h11a5 5 0 0 0 5-5V4" />
                            </svg>
                        </li>
                    </ul>

                    <p v-if="trimmed && !loading && !results.length" class="px-3 py-10 text-center text-[14px] text-[#737373] dark:text-[#8b8b93]">
                        No pages match “{{ trimmed }}”.
                    </p>
                </div>

                <div class="hidden items-center justify-between gap-4 border-t border-[#eaeaea] bg-[#fafafa] px-4 py-2.5 text-[12px] text-[#737373] sm:flex dark:border-white/[0.1] dark:bg-black dark:text-[#8b8b93]">
                    <span class="flex items-center gap-4">
                        <span class="flex items-center gap-1.5"><kbd class="search-key">↑</kbd><kbd class="search-key">↓</kbd> Move</span>
                        <span class="flex items-center gap-1.5"><kbd class="search-key">↵</kbd> Open</span>
                        <span class="flex items-center gap-1.5"><kbd class="search-key">Esc</kbd> Close</span>
                    </span>
                    <span class="mode-hybrid flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-(--mode)" aria-hidden="true"></span>
                        Hybrid search by Sigmie
                    </span>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.search-key {
    display: inline-flex;
    min-width: 1.25rem;
    height: 1.25rem;
    align-items: center;
    justify-content: center;
    padding: 0 0.3rem;
    border: 1px solid #e5e5e5;
    border-radius: 5px;
    background: #fff;
    font-family: var(--font-mono);
    font-size: 11px;
    color: #525252;
}
:global(.dark) .search-key {
    border-color: rgb(255 255 255 / 0.12);
    background: #0a0a0a;
    color: #a1a1aa;
}
</style>
