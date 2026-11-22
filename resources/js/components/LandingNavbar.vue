<script setup>
import { Link, router as Inertia } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import axios from 'axios';
import Logo from './Logo.vue';
import { useTheme } from '../composables/useTheme';
import { useChatPanel } from '../composables/useChatPanel';

const { toggleTheme } = useTheme();
const { enabled, showColumn, openChat } = useChatPanel();

const menuOpen = ref(false);
const showSearchModal = ref(false);
const modalSearchQuery = ref('');
const modalSearchInput = ref(null);
const searchResults = ref([]);
const isSearching = ref(false);
const selectedResultIndex = ref(0);
let searchTimeout = null;

const popularTopics = ['semantic search', 'mappings', 'aggregations', 'filters', 'RAG', 'Laravel Scout', 'analysis'];

const closeMenu = () => {
    menuOpen.value = false;
};

const openSearchModal = () => {
    showSearchModal.value = true;
    menuOpen.value = false;
    modalSearchQuery.value = '';
    searchResults.value = [];
    selectedResultIndex.value = 0;
    nextTick(() => modalSearchInput.value?.focus());
};

const closeSearchModal = () => {
    showSearchModal.value = false;
    searchResults.value = [];
    if (searchTimeout) {
        clearTimeout(searchTimeout);
        searchTimeout = null;
    }
};

const searchDocs = async () => {
    if (!modalSearchQuery.value.trim()) {
        searchResults.value = [];
        selectedResultIndex.value = 0;
        return;
    }

    isSearching.value = true;

    try {
        const response = await axios.post('/api/search/docs', { query: modalSearchQuery.value });
        searchResults.value = response.data.results || [];
        selectedResultIndex.value = 0;
    } catch {
        searchResults.value = [];
        selectedResultIndex.value = 0;
    } finally {
        isSearching.value = false;
    }
};

const handleModalSearch = (result = null) => {
    const target = result || searchResults.value[selectedResultIndex.value];
    if (!target?.url) return;
    Inertia.visit(target.url);
    closeSearchModal();
};

const handleKeyboardNavigation = (event) => {
    if (!showSearchModal.value || searchResults.value.length === 0) return;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        selectedResultIndex.value = Math.min(selectedResultIndex.value + 1, searchResults.value.length - 1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        selectedResultIndex.value = Math.max(selectedResultIndex.value - 1, 0);
    }
};

watch(modalSearchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout);

    if (!modalSearchQuery.value.trim()) {
        searchResults.value = [];
        isSearching.value = false;
        return;
    }

    isSearching.value = true;
    searchTimeout = setTimeout(searchDocs, 300);
});

const onKeyDown = (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
        event.preventDefault();
        showSearchModal.value ? closeSearchModal() : openSearchModal();
        return;
    }

    if (event.key === 'Escape' && showSearchModal.value) closeSearchModal();
    if (event.key === 'Escape' && menuOpen.value) closeMenu();
    handleKeyboardNavigation(event);
};

onMounted(() => window.addEventListener('keydown', onKeyDown));
onUnmounted(() => {
    window.removeEventListener('keydown', onKeyDown);
    if (searchTimeout) clearTimeout(searchTimeout);
});

const headerClass = computed(() => ({
    'xl:right-[400px]': showColumn.value,
}));
</script>

<template>
    <header
        class="fixed top-0 left-0 right-0 z-40 h-16 border-b border-light-steel/80 bg-canvas-white/85 backdrop-blur-xl dark:border-white/[0.08] dark:bg-[#08080a]/85"
        :class="headerClass"
    >
        <div class="mx-auto flex h-full w-full max-w-6xl items-center justify-between gap-4 px-5 sm:px-6 lg:px-8">
            <div class="flex items-center gap-8">
                <Link href="/" aria-label="Sigmie home" class="shrink-0" @click="closeMenu">
                    <Logo :height="24" />
                </Link>

                <nav class="hidden items-center gap-6 md:flex" aria-label="Primary">
                    <a
                        href="#playground"
                        class="text-[14px] font-medium text-charcoal/80 transition-colors hover:text-graphite dark:text-[#a3a3a0] dark:hover:text-white"
                    >
                        Playground
                    </a>
                    <Link
                        href="/docs"
                        class="text-[14px] font-medium text-charcoal/80 transition-colors hover:text-graphite dark:text-[#a3a3a0] dark:hover:text-white"
                    >
                        Docs
                    </Link>
                    <Link
                        href="/docs/v2/quick-start"
                        class="text-[14px] font-medium text-charcoal/80 transition-colors hover:text-graphite dark:text-[#a3a3a0] dark:hover:text-white"
                    >
                        Quick start
                    </Link>
                </nav>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="hidden items-center gap-2.5 rounded-full border border-light-steel bg-ghostly-gray py-1.5 pr-2 pl-3.5 text-[13px] text-muted-ink transition-colors hover:border-graphite/30 hover:text-graphite md:inline-flex dark:border-[#242422] dark:bg-[#111113] dark:text-[#9ca3af] dark:hover:border-white/20 dark:hover:text-[#f5f5f4]"
                    @click="openSearchModal"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span>Search docs</span>
                    <kbd class="rounded-full border border-light-steel bg-canvas-white px-1.5 py-0.5 font-mono text-[10px] text-charcoal dark:border-[#242422] dark:bg-black dark:text-[#a3a3a0]">⌘K</kbd>
                </button>

                <button
                    v-if="enabled"
                    type="button"
                    class="rounded-full border border-light-steel bg-canvas-white px-3.5 py-1.5 text-[13px] font-medium text-graphite transition-colors hover:bg-ghostly-gray dark:border-[#242422] dark:bg-[#111113] dark:text-[#f5f5f4] dark:hover:bg-[#1c1c1e]"
                    @click="openChat"
                >
                    Ask AI
                </button>

                <button
                    type="button"
                    class="hidden h-9 w-9 items-center justify-center rounded-full text-charcoal transition-colors hover:bg-ghostly-gray hover:text-graphite md:inline-flex dark:hidden"
                    title="Switch to dark mode"
                    aria-label="Switch to dark mode"
                    @click="toggleTheme"
                >
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>
                <button
                    type="button"
                    class="hidden h-9 w-9 items-center justify-center rounded-full text-[#a3a3a0] transition-colors hover:bg-[#141413] hover:text-white md:dark:inline-flex"
                    title="Switch to light mode"
                    aria-label="Switch to light mode"
                    @click="toggleTheme"
                >
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </button>

                <a
                    href="https://github.com/sigmie/sigmie"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="Sigmie on GitHub"
                    class="hidden h-9 w-9 items-center justify-center rounded-full text-charcoal transition-colors hover:bg-ghostly-gray hover:text-graphite md:inline-flex dark:text-[#a3a3a0] dark:hover:bg-[#141413] dark:hover:text-white"
                >
                    <svg class="h-[18px] w-[18px]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                    </svg>
                </a>

                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-full text-charcoal hover:bg-ghostly-gray md:hidden dark:text-[#a3a3a0] dark:hover:bg-[#141413]"
                    :aria-expanded="menuOpen"
                    :aria-label="menuOpen ? 'Close menu' : 'Open menu'"
                    @click="menuOpen = !menuOpen"
                >
                    <svg v-if="!menuOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <div
        v-if="menuOpen"
        class="fixed inset-x-0 top-16 z-40 border-b border-light-steel bg-canvas-white/95 backdrop-blur-xl md:hidden dark:border-white/10 dark:bg-black/95"
        :class="{ 'xl:right-[400px]': showColumn }"
    >
        <nav class="mx-auto flex max-w-6xl flex-col gap-1 px-5 py-4 sm:px-6" aria-label="Mobile">
            <a
                href="#playground"
                class="rounded-xl px-3 py-2.5 text-[15px] font-medium text-graphite hover:bg-ghostly-gray dark:text-[#f5f5f4] dark:hover:bg-[#141413]"
                @click="closeMenu"
            >
                Playground
            </a>
            <Link href="/docs" class="rounded-xl px-3 py-2.5 text-[15px] font-medium text-graphite hover:bg-ghostly-gray dark:text-[#f5f5f4] dark:hover:bg-[#141413]" @click="closeMenu">
                Docs
            </Link>
            <Link href="/docs/v2/quick-start" class="rounded-xl px-3 py-2.5 text-[15px] font-medium text-graphite hover:bg-ghostly-gray dark:text-[#f5f5f4] dark:hover:bg-[#141413]" @click="closeMenu">
                Quick start
            </Link>
            <a
                href="https://github.com/sigmie/sigmie"
                target="_blank"
                rel="noopener noreferrer"
                class="rounded-xl px-3 py-2.5 text-[15px] font-medium text-graphite hover:bg-ghostly-gray dark:text-[#f5f5f4] dark:hover:bg-[#141413]"
                @click="closeMenu"
            >
                GitHub
            </a>
            <div class="my-2 border-t border-light-steel dark:border-[#242422]"></div>
            <button
                type="button"
                class="block rounded-xl px-3 py-2.5 text-left text-[15px] text-charcoal hover:bg-ghostly-gray dark:hidden"
                @click="toggleTheme"
            >
                Switch to dark mode
            </button>
            <button
                type="button"
                class="hidden rounded-xl px-3 py-2.5 text-left text-[15px] text-[#a3a3a0] hover:bg-[#141413] dark:block"
                @click="toggleTheme"
            >
                Switch to light mode
            </button>
            <button
                type="button"
                class="rounded-xl px-3 py-2.5 text-left text-[15px] text-charcoal hover:bg-ghostly-gray dark:text-[#a3a3a0] dark:hover:bg-[#141413]"
                @click="openSearchModal"
            >
                Search docs
            </button>
        </nav>
    </div>

    <div
        v-if="showSearchModal"
        class="fixed inset-0 z-50 flex items-start justify-center bg-black/50 px-4 pt-20 backdrop-blur-sm"
        @click.self="closeSearchModal"
    >
        <div class="flex max-h-[calc(100vh-8rem)] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-light-steel bg-canvas-white shadow-2xl dark:border-[#242422] dark:bg-[#0c0c0b]">
            <div class="flex items-center gap-3 border-b border-light-steel px-5 py-4 dark:border-[#242422]">
                <svg class="h-4 w-4 shrink-0 text-subtle-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    ref="modalSearchInput"
                    v-model="modalSearchQuery"
                    type="text"
                    placeholder="Search documentation..."
                    aria-label="Search documentation"
                    class="flex-1 border-0 bg-transparent py-1 text-[16px] text-graphite placeholder:text-subtle-gray focus:ring-0 focus:outline-none dark:text-[#f5f5f4]"
                    @keyup.enter="handleModalSearch()"
                />
                <button
                    type="button"
                    class="rounded-md border border-light-steel px-2 py-0.5 font-mono text-[11px] text-subtle-gray hover:text-graphite dark:border-[#242422] dark:hover:text-white"
                    aria-label="Close search"
                    @click="closeSearchModal"
                >
                    Esc
                </button>
            </div>

            <div v-if="modalSearchQuery.trim()" class="min-h-0 flex-1 overflow-y-auto">
                <p v-if="isSearching" class="px-5 py-8 text-[14px] text-subtle-gray">Searching...</p>
                <div v-else-if="searchResults.length" class="py-2">
                    <button
                        v-for="(result, index) in searchResults"
                        :key="result._id || index"
                        type="button"
                        class="block w-full px-5 py-3 text-left transition-colors"
                        :class="selectedResultIndex === index ? 'bg-ghostly-gray dark:bg-[#141413]' : ''"
                        @click="handleModalSearch(result)"
                        @mouseenter="selectedResultIndex = index"
                    >
                        <span class="block text-[14px] font-medium text-graphite dark:text-[#f5f5f4]">{{ result.title }}</span>
                        <span class="mt-0.5 block truncate text-[12px] text-subtle-gray">{{ result.url }}</span>
                    </button>
                </div>
                <p v-else class="px-5 py-8 text-[14px] text-subtle-gray">No documentation pages matched.</p>
            </div>

            <div v-else class="px-5 py-5">
                <p class="font-mono text-[11px] uppercase tracking-[0.08em] text-subtle-gray">Popular topics</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button
                        v-for="topic in popularTopics"
                        :key="topic"
                        type="button"
                        class="rounded-full border border-light-steel px-3 py-1.5 text-[13px] text-charcoal transition-colors hover:border-graphite hover:text-graphite dark:border-[#242422] dark:text-[#a3a3a0] dark:hover:border-white/30 dark:hover:text-white"
                        @click="modalSearchQuery = topic"
                    >
                        {{ topic }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
