<script setup>
import { Link, router as Inertia } from "@inertiajs/vue3";
import Logo from "./components/Logo.vue";
import { onMounted, onUnmounted, ref, nextTick, watch } from "vue";
import { useTheme } from "./composables/useTheme";
import axios from "axios";

const { theme, toggleTheme } = useTheme();

let showMenu = ref(false);
const toggleNav = () => (showMenu.value = !showMenu.value);
const hideNav = () => (showMenu.value = true);

onMounted(() => hideNav());

let visit = (url) => {
    showMenu.value = true;
    Inertia.visit(url);
};

defineProps({
    navigation: Object,
});

const showSearchModal = ref(false);
const modalSearchQuery = ref('');
const modalSearchInput = ref(null);
const searchResults = ref([]);
const isSearching = ref(false);
const selectedResultIndex = ref(0);
let searchTimeout = null;

const openSearchModal = () => {
    showSearchModal.value = true;
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
    } catch (error) {
        console.error('Docs search error:', error);
        searchResults.value = [];
        selectedResultIndex.value = 0;
    } finally {
        isSearching.value = false;
    }
};

const handleModalSearch = (result = null) => {
    if (result) {
        Inertia.visit(result.url);
        closeSearchModal();
    } else if (searchResults.value.length > 0) {
        Inertia.visit(searchResults.value[selectedResultIndex.value].url);
        closeSearchModal();
    }
};

const handleKeyboardNavigation = (e) => {
    if (!showSearchModal.value || searchResults.value.length === 0) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedResultIndex.value = Math.min(selectedResultIndex.value + 1, searchResults.value.length - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedResultIndex.value = Math.max(selectedResultIndex.value - 1, 0);
    }
};

watch(modalSearchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout);

    if (modalSearchQuery.value.trim()) {
        isSearching.value = true;
    } else {
        searchResults.value = [];
        isSearching.value = false;
        return;
    }

    searchTimeout = setTimeout(() => searchDocs(), 300);
});

onMounted(() => {
    const handleKeyDown = (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            openSearchModal();
        }
        if (e.key === 'Escape' && showSearchModal.value) closeSearchModal();
        handleKeyboardNavigation(e);
    };
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    if (searchTimeout) clearTimeout(searchTimeout);
});
</script>

<template>
    <div class="font-sans">
        <div
            v-if="!showMenu"
            class="lg:hidden fixed inset-0 top-16 z-40 bg-canvas-white dark:bg-black"
        >
            <div class="h-full overflow-y-auto px-6 py-6">
                <nav class="space-y-8">
                    <div v-for="(section, index) in navigation" :key="index">
                        <h5 class="mb-3 font-semibold text-[13px] uppercase tracking-wider text-subtle-gray">
                            {{ section.title }}
                        </h5>
                        <ul class="space-y-1">
                            <li v-for="(link, linkIndex) in section.links" :key="linkIndex">
                                <button
                                    @click.prevent="() => visit(link.href)"
                                    :class="[
                                        'flex items-center w-full px-3 py-2 text-[15px] rounded-lg transition-colors text-left',
                                        $page.url === link.href
                                            ? 'bg-ghostly-gray text-graphite font-medium dark:bg-gray-900 dark:text-white'
                                            : 'text-charcoal hover:text-graphite hover:bg-ghostly-gray dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-900/50'
                                    ]"
                                >
                                    {{ link.title }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>

        <header
            class="bg-canvas-white/90 dark:bg-black/90 backdrop-blur h-16 fixed top-0 left-0 right-0 z-40 border-b border-light-steel dark:border-gray-800"
        >
            <nav class="h-full flex items-center justify-between px-6 max-w-[92rem] mx-auto">
                <div class="flex-shrink-0">
                    <Link href="/" aria-label="Sigmie home" class="flex items-center">
                        <Logo :height="32" />
                    </Link>
                </div>

                <div class="hidden md:block max-w-md w-full">
                    <button
                        @click="openSearchModal"
                        class="w-full pl-10 pr-20 py-2.5 text-[14px] text-subtle-gray bg-ghostly-gray dark:bg-gray-900/50 border border-light-steel dark:border-gray-800 rounded-full hover:bg-fog dark:hover:bg-gray-900 transition-colors text-left relative group"
                    >
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <svg class="w-4 h-4 text-subtle-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <span>Search documentation...</span>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4">
                            <kbd class="hidden sm:inline-block px-2 py-0.5 text-[11px] text-subtle-gray bg-canvas-white dark:bg-gray-950 rounded-md border border-light-steel dark:border-gray-700 font-mono">
                                ⌘K
                            </kbd>
                        </span>
                    </button>
                </div>

                <div class="flex items-center gap-1 flex-shrink-0">
                    <button
                        @click="toggleTheme"
                        class="hidden sm:block p-2 rounded-full text-charcoal hover:text-graphite hover:bg-ghostly-gray dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-900 transition-colors"
                        :title="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                    >
                        <svg v-if="theme === 'dark'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    <a
                        href="https://github.com/sigmie/sigmie"
                        target="_blank"
                        aria-label="Sigmie on GitHub"
                        class="hidden sm:block p-2 rounded-full text-charcoal hover:text-graphite hover:bg-ghostly-gray dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-900 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                        </svg>
                    </a>

                    <button
                        @click="toggleNav"
                        aria-label="Toggle navigation menu"
                        class="lg:hidden p-2 rounded-full text-charcoal hover:text-graphite hover:bg-ghostly-gray dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-900 transition-colors"
                    >
                        <svg v-if="showMenu" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </nav>
        </header>

        <Transition name="fade">
            <div
                v-if="showSearchModal"
                class="fixed inset-0 z-50 bg-graphite/50 backdrop-blur-md flex items-start justify-center pt-20 px-4"
                @click.self="closeSearchModal"
            >
                <div class="bg-canvas-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-3xl border border-light-steel dark:border-gray-800 max-h-[calc(100vh-10rem)] flex flex-col overflow-hidden">
                    <div class="px-6 pt-5 pb-4 flex-shrink-0 border-b border-light-steel dark:border-gray-800">
                        <div class="relative flex gap-3 items-center">
                            <svg class="w-5 h-5 text-subtle-gray flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                ref="modalSearchInput"
                                v-model="modalSearchQuery"
                                @keyup.enter="handleModalSearch()"
                                type="text"
                                placeholder="Search documentation..."
                                class="flex-1 py-2 text-[17px] bg-transparent text-graphite dark:text-gray-100 placeholder-subtle-gray border-0 focus:outline-none focus:ring-0"
                            />
                            <button
                                @click="closeSearchModal"
                                aria-label="Close search"
                                class="text-subtle-gray hover:text-graphite dark:hover:text-gray-300 transition-colors p-1.5 hover:bg-ghostly-gray dark:hover:bg-gray-800 rounded-full"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div v-if="modalSearchQuery.trim()" class="flex-1 overflow-y-auto scrollbar-thin">
                        <div v-if="isSearching" class="py-16 text-center">
                            <div class="w-10 h-10 border-2 border-light-steel border-t-magic-orange rounded-full animate-spin mx-auto"></div>
                            <p class="mt-4 text-[14px] text-subtle-gray">Searching...</p>
                        </div>

                        <div v-else-if="searchResults.length > 0" class="py-3">
                            <button
                                v-for="(result, index) in searchResults"
                                :key="result._id"
                                @click="handleModalSearch(result)"
                                @mouseenter="selectedResultIndex = index"
                                :class="[
                                    'w-full text-left px-4 py-3.5 mx-2 my-1 rounded-xl transition-colors group relative',
                                    selectedResultIndex === index
                                        ? 'bg-ghostly-gray dark:bg-gray-800/50'
                                        : 'hover:bg-ghostly-gray dark:hover:bg-gray-800/50'
                                ]"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 mt-1">
                                        <svg class="w-4 h-4 text-magic-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-graphite dark:text-gray-100 mb-1 text-[14px]">
                                            {{ result.title }}
                                        </div>
                                        <div class="text-[12px] text-subtle-gray mb-2 truncate">
                                            {{ result.url }}
                                        </div>
                                        <div v-if="result.content" class="text-[13px] text-charcoal dark:text-gray-400 line-clamp-2 leading-relaxed">
                                            {{ result.content.substring(0, 150) }}{{ result.content.length > 150 ? '...' : '' }}
                                        </div>
                                    </div>
                                    <div v-if="selectedResultIndex === index" class="flex-shrink-0">
                                        <kbd class="px-1.5 py-0.5 text-[11px] font-mono bg-canvas-white dark:bg-gray-800 text-charcoal dark:text-gray-300 rounded border border-light-steel dark:border-gray-700">↵</kbd>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <div v-else class="py-16 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-ghostly-gray dark:bg-gray-800 flex items-center justify-center">
                                <svg class="w-8 h-8 text-subtle-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <p class="text-graphite dark:text-gray-400 text-[14px] font-medium mb-1">No results found</p>
                            <p class="text-subtle-gray text-[13px]">Try searching with different keywords</p>
                        </div>
                    </div>

                    <div v-else class="flex-1 flex items-center justify-center py-16">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-ghostly-gray dark:bg-gray-800 flex items-center justify-center">
                                <svg class="w-8 h-8 text-subtle-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <p class="text-subtle-gray text-[14px]">Start typing to search documentation</p>
                        </div>
                    </div>

                    <div v-if="searchResults.length > 0" class="px-6 py-3.5 flex items-center justify-between border-t border-light-steel dark:border-gray-800 flex-shrink-0 bg-ghostly-gray dark:bg-gray-900/50">
                        <div class="flex items-center gap-3 text-[12px] text-subtle-gray">
                            <span class="flex items-center gap-1">
                                <kbd class="px-1.5 py-0.5 bg-canvas-white dark:bg-gray-800 rounded border border-light-steel dark:border-gray-700 text-charcoal dark:text-gray-300 font-mono text-[11px]">↑↓</kbd>
                                <span>to navigate</span>
                            </span>
                            <span class="flex items-center gap-1">
                                <kbd class="px-1.5 py-0.5 bg-canvas-white dark:bg-gray-800 rounded border border-light-steel dark:border-gray-700 text-charcoal dark:text-gray-300 font-mono text-[11px]">↵</kbd>
                                <span>to select</span>
                            </span>
                            <span class="flex items-center gap-1">
                                <kbd class="px-1.5 py-0.5 bg-canvas-white dark:bg-gray-800 rounded border border-light-steel dark:border-gray-700 text-charcoal dark:text-gray-300 font-mono text-[11px]">esc</kbd>
                                <span>to close</span>
                            </span>
                        </div>
                        <div class="text-[12px] text-subtle-gray font-medium">
                            {{ searchResults.length }} result{{ searchResults.length !== 1 ? 's' : '' }}
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style type="text/css">
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
