<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref, computed, watch, nextTick } from "vue";
import axios from "axios";
import Sidebar from "../Sidebar.vue";
import Navbar from "../Navbar.vue";
import Banner from "../Banner.vue";

defineProps({
    title: String,
    posts: Object,
    navigation: Object,
});

const searchQuery = ref("");
const searchResults = ref([]);
const isSearching = ref(false);
const hasSearched = ref(false);
const selectedType = ref("all");

const presetQueries = [
    { label: "Woman protagonist", query: "woman" },
    { label: "Action thrillers", query: "action thriller" },
    { label: "Romantic comedies", query: "romantic comedy" },
    { label: "Sci-fi adventures", query: "science fiction space" },
    { label: "Crime dramas", query: "crime drama detective" },
    { label: "Family animations", query: "family animation kids" }
];

const highlightedCode = computed(() => {
    const query = searchQuery.value || 'your search query';

    const lines = [
        { tokens: parseTokens('$netflixIndex = app(NetflixTitles::class);') },
        { tokens: parseTokens('$blueprint = $netflixIndex->properties();') },
        { tokens: [] },
        { tokens: parseTokens('$search = $netflixIndex') },
        { tokens: parseTokens('    ->newSearch()') },
        { tokens: parseTokens('    ->properties($blueprint)') },
        { tokens: parseTokens('    ->semantic()') },
        { tokens: parseTokens('    ->noResultsOnEmptySearch()') },
        { tokens: parseTokens('    ->disableKeywordSearch()') },
    ];

    // Always show filter - either specific type or all types with OR
    let filterLine = '';
    let shouldHighlight = false;

    if (selectedType.value === 'all') {
        filterLine = `    ->filters('type:"TV Show" OR type:"Movie"')`;
    } else {
        filterLine = `    ->filters('type:"${selectedType.value}"')`;
        shouldHighlight = true; // Only highlight when a specific filter is selected
    }

    lines.push({
        tokens: parseTokens(filterLine),
        isNew: shouldHighlight
    });

    lines.push(
        { tokens: parseTokens(`    ->queryString('${query}')`) },
        { tokens: parseTokens("    ->retrieve(['type', 'title', 'director', 'cast'])") },
        { tokens: parseTokens('    ->size(20);') },
        { tokens: [] },
        { tokens: parseTokens('$response = $search->get();') }
    );

    return lines;
});

const parseTokens = (text) => {
    if (!text) return [];

    const tokens = [];
    let current = '';
    let inString = false;
    let stringChar = '';

    for (let i = 0; i < text.length; i++) {
        const char = text[i];
        const next = text[i + 1] || '';

        // Handle strings
        if ((char === "'" || char === '"') && !inString) {
            if (current) {
                tokens.push(...tokenizeNonString(current));
                current = '';
            }
            inString = true;
            stringChar = char;
            current = char;
        } else if (char === stringChar && inString) {
            current += char;
            tokens.push({ type: 'string', value: current });
            current = '';
            inString = false;
        } else if (inString) {
            current += char;
        }
        // Handle method calls ->
        else if (char === '-' && next === '>') {
            if (current) {
                tokens.push(...tokenizeNonString(current));
                current = '';
            }
            tokens.push({ type: 'operator', value: '->' });
            i++; // skip next char
        }
        // Handle double colon
        else if (char === ':' && next === ':') {
            if (current) {
                tokens.push(...tokenizeNonString(current));
                current = '';
            }
            tokens.push({ type: 'operator', value: '::' });
            i++;
        }
        // Handle special chars
        else if (['(', ')', '[', ']', ',', ';', '='].includes(char)) {
            if (current) {
                tokens.push(...tokenizeNonString(current));
                current = '';
            }
            tokens.push({ type: char === ',' ? 'comma' : char === ';' ? 'semicolon' : 'bracket', value: char });
        }
        // Handle whitespace
        else if (char === ' ') {
            if (current) {
                tokens.push(...tokenizeNonString(current));
                current = '';
            }
            tokens.push({ type: 'space', value: ' ' });
        } else {
            current += char;
        }
    }

    if (current) {
        if (inString) {
            tokens.push({ type: 'string', value: current });
        } else {
            tokens.push(...tokenizeNonString(current));
        }
    }

    return tokens;
};

const tokenizeNonString = (text) => {
    if (!text) return [];

    // Check if it's a variable
    if (text.startsWith('$')) {
        return [{ type: 'variable', value: text }];
    }

    // Check if it's a function
    if (['app', 'get', 'class'].includes(text)) {
        return [{ type: 'function', value: text }];
    }

    // Check if it's a number
    if (/^\d+$/.test(text)) {
        return [{ type: 'number', value: text }];
    }

    // Otherwise it's a method or text
    return [{ type: 'method', value: text }];
};

const getTokenClass = (token) => {
    switch (token.type) {
        case 'variable':
            return 'text-purple-400 font-semibold';
        case 'string':
            return 'text-green-400';
        case 'function':
            return 'text-cyan-400';
        case 'method':
            return 'text-blue-300';
        case 'operator':
            return 'text-gray-400';
        case 'bracket':
            return 'text-yellow-300';
        case 'comma':
        case 'semicolon':
            return 'text-gray-500';
        case 'number':
            return 'text-orange-400';
        default:
            return 'text-gray-300';
    }
};

const filteredResults = computed(() => {
    if (selectedType.value === "all") {
        return searchResults.value;
    }
    return searchResults.value.filter(result => result.type === selectedType.value);
});

const performSearch = async (query = null) => {
    const searchTerm = query || searchQuery.value;

    if (!searchTerm.trim()) {
        return;
    }

    if (query) {
        searchQuery.value = query;
    }

    isSearching.value = true;
    hasSearched.value = true;
    selectedType.value = "all";

    try {
        const response = await axios.post("/api/search/netflix", {
            query: searchTerm,
        });

        searchResults.value = response.data.results || [];
    } catch (error) {
        console.error("Search error:", error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

const copyCode = async () => {
    try {
        const plainCode = highlightedCode.value
            .map(line => line.tokens.map(t => t.value).join(''))
            .join('\n');
        await navigator.clipboard.writeText(plainCode);
    } catch (err) {
        console.error('Failed to copy code:', err);
    }
};

// Preserve scroll position when filtering
let savedScrollY = 0;

watch(selectedType, async (newVal, oldVal) => {
    if (oldVal !== null && hasSearched.value) {
        // Save current scroll position
        savedScrollY = window.scrollY;

        // Wait for DOM to update
        await nextTick();

        // Restore scroll position smoothly
        window.scrollTo({
            top: savedScrollY,
            behavior: 'instant' // Use instant to prevent any visible scroll jump
        });
    }
});
</script>

<template>
    <Head>
        <title>Sigmie - A different Elasticsearch library</title>
        <meta name="description" content="Sigmie is a modern, developer-friendly Elasticsearch library that handles all complexities, letting you focus solely on search relevance. Simple API, production-ready." />
        <meta name="keywords" content="elasticsearch, search library, full-text search, sigmie, php, laravel, search api, elasticsearch php" />
        <meta property="og:title" content="Sigmie - A different Elasticsearch library" />
        <meta property="og:description" content="Sigmie is a modern, developer-friendly Elasticsearch library that handles all complexities, letting you focus solely on search relevance." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://sigmie.com" />
        <meta property="og:image" content="https://sigmie.com/og-image.png" />
        <meta property="og:site_name" content="Sigmie" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Sigmie - A different Elasticsearch library" />
        <meta name="twitter:description" content="Sigmie is a modern, developer-friendly Elasticsearch library that handles all complexities, letting you focus solely on search relevance." />
        <meta name="twitter:image" content="https://sigmie.com/og-image.png" />
        <link rel="canonical" href="https://sigmie.com" />
    </Head>
    <div class="min-h-screen bg-white dark:bg-black">
        <div class="relative isolate px-4 sm:px-6 pt-14 lg:px-8">
            <div
                class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80"
                aria-hidden="true"
            >
                <div
                    class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-blue-600 to-purple-600 opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"
                    style="
                        clip-path: polygon(
                            74.1% 44.1%,
                            100% 61.6%,
                            97.5% 26.9%,
                            85.5% 0.1%,
                            80.7% 2%,
                            72.5% 32.5%,
                            60.2% 62.4%,
                            52.4% 68.1%,
                            47.5% 58.3%,
                            45.2% 34.5%,
                            27.5% 76.7%,
                            0.1% 64.9%,
                            17.9% 100%,
                            27.6% 76.8%,
                            76.1% 97.7%,
                            74.1% 44.1%
                        );
                    "
                ></div>
            </div>
            <div class="mx-auto max-w-6xl py-16 sm:py-24 lg:py-32 xl:py-56">
                <div class="flex flex-col items-center">
                    <img
                        class="h-16 sm:h-20 lg:h-24 mb-8 sm:mb-12"
                        src="https://github.com/sigmie/art/blob/main/logo/svg/logo-icon-black.svg?raw=true"
                    />
                    <div class="text-center max-w-4xl">
                        <h1
                            class="text-3xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold tracking-tight text-gray-900 dark:text-gray-100 leading-tight"
                        >
                            A different<br class="hidden sm:block" />
                            <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                Elasticsearch
                            </span>
                            library
                        </h1>
                        <p class="mt-6 sm:mt-8 text-base sm:text-geist-lg leading-relaxed text-gray-600 dark:text-gray-400 max-w-2xl mx-auto px-4 sm:px-0">
                            Sigmie library allows you to effortlessly create
                            powerful searches without mastering Elasticsearch. It
                            handles all the complexities, letting you focus solely
                            on relevance.
                        </p>
                        <div class="mt-8 sm:mt-12 flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 px-4 sm:px-0">
                            <Link
                                href="/docs/v1/introduction"
                                class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 text-sm sm:text-geist-base font-medium text-white bg-gray-900 dark:bg-white dark:text-black rounded-geist hover:bg-gray-800 dark:hover:bg-gray-100 transition-all duration-200 shadow-geist hover:shadow-geist-hover"
                            >
                                Get started
                            </Link>
                            <a
                                href="https://github.com/sigmie"
                                target="_blank"
                                class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 text-sm sm:text-geist-base font-medium text-gray-900 dark:text-gray-100 bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-geist hover:bg-gray-50 dark:hover:bg-gray-900 transition-all duration-200"
                            >
                                View on GitHub
                            </a>
                        </div>
                    </div>

                    <div class="mt-12 sm:mt-16 flex items-center justify-center px-4 sm:px-0">
                        <div
                            class="relative px-3 sm:px-4 py-2 text-xs sm:text-geist-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-geist text-center"
                        >
                            Looking for Search as a Service?
                            <a
                                href="https://sigmie.app"
                                class="ml-1 font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300"
                            >
                                Try our app →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-800">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 py-16 sm:py-20 lg:py-24 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
                    <div class="p-4 sm:p-6 bg-gray-50 dark:bg-gray-900 rounded-geist border border-gray-200 dark:border-gray-800">
                        <h3 class="text-base sm:text-geist-lg font-semibold text-gray-900 dark:text-gray-100 mb-2 sm:mb-3">
                            Simple API
                        </h3>
                        <p class="text-sm sm:text-geist-base text-gray-600 dark:text-gray-400">
                            Intuitive methods that abstract away Elasticsearch complexity while maintaining full power.
                        </p>
                    </div>
                    <div class="p-4 sm:p-6 bg-gray-50 dark:bg-gray-900 rounded-geist border border-gray-200 dark:border-gray-800">
                        <h3 class="text-base sm:text-geist-lg font-semibold text-gray-900 dark:text-gray-100 mb-2 sm:mb-3">
                            Focus on Relevance
                        </h3>
                        <p class="text-sm sm:text-geist-base text-gray-600 dark:text-gray-400">
                            Built-in best practices for search relevance, so you can focus on your product.
                        </p>
                    </div>
                    <div class="p-4 sm:p-6 bg-gray-50 dark:bg-gray-900 rounded-geist border border-gray-200 dark:border-gray-800">
                        <h3 class="text-base sm:text-geist-lg font-semibold text-gray-900 dark:text-gray-100 mb-2 sm:mb-3">
                            Production Ready
                        </h3>
                        <p class="text-sm sm:text-geist-base text-gray-600 dark:text-gray-400">
                            Battle-tested in production environments with comprehensive documentation.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Netflix Search Demo Section -->
        <div class="relative border-t border-gray-200 dark:border-gray-800 bg-gradient-to-b from-white to-gray-50 dark:from-black dark:to-gray-900/50 overflow-hidden">
            <!-- Decorative background elements -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute top-1/4 -right-64 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-1/4 -left-64 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 py-16 sm:py-20 lg:py-28 lg:px-8">
                <div class="text-center mb-10 sm:mb-14">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-950/30 dark:to-purple-950/30 border border-blue-200 dark:border-blue-800 rounded-full mb-6">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span class="text-sm font-medium bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            Live Demo
                        </span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-bold text-gray-900 dark:text-gray-100 mb-4 sm:mb-6">
                        Experience
                        <span class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                            Semantic Search
                        </span>
                    </h2>
                    <p class="text-base sm:text-lg lg:text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto leading-relaxed">
                        Search 8,000+ Netflix titles using natural language. See how Sigmie's semantic search understands intent, not just keywords.
                    </p>
                </div>

                <div class="max-w-4xl mx-auto">
                    <!-- Search Form -->
                    <form @submit.prevent="performSearch()" class="mb-8 sm:mb-10">
                        <div class="relative">
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-purple-500 rounded-2xl blur-xl opacity-20"></div>
                            <div class="relative flex gap-2 sm:gap-3 p-2 bg-white dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl transition-all duration-300 focus-within:border-blue-500 dark:focus-within:border-blue-400">
                                <div class="flex-1 flex items-center gap-3 px-3">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search by mood, genre, or description..."
                                        class="flex-1 py-3 text-sm sm:text-base bg-transparent focus:outline-none dark:text-gray-100 placeholder-gray-400"
                                        :disabled="isSearching"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    class="group relative px-6 sm:px-8 py-3 sm:py-4 font-semibold text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl hover:from-blue-700 hover:to-purple-700 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl disabled:hover:shadow-lg overflow-hidden"
                                    :disabled="isSearching || !searchQuery.trim()"
                                >
                                    <span class="relative z-10 flex items-center gap-2">
                                        <span class="hidden sm:inline">{{ isSearching ? "Searching..." : "Search" }}</span>
                                        <svg v-if="!isSearching" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                        </svg>
                                        <div v-else class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Preset Queries -->
                    <div v-if="!hasSearched" class="mb-12">
                        <p class="text-center text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">
                            Try these popular searches:
                        </p>
                        <div class="flex flex-wrap justify-center gap-2 sm:gap-3">
                            <button
                                v-for="preset in presetQueries"
                                :key="preset.query"
                                @click="performSearch(preset.query)"
                                class="group px-4 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full hover:border-blue-500 dark:hover:border-blue-400 hover:text-blue-600 dark:hover:text-blue-400 hover:shadow-md transition-all duration-200"
                                :disabled="isSearching"
                            >
                                <span class="flex items-center gap-2">
                                    {{ preset.label }}
                                    <svg class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Filter & Code Preview -->
                    <div v-if="hasSearched && !isSearching" class="mb-8 space-y-4">
                        <!-- Type Filters -->
                        <div class="flex items-center justify-center gap-2">
                            <button
                                @click="selectedType = 'all'"
                                class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                                :class="selectedType === 'all'
                                    ? 'bg-gray-900 dark:bg-white text-white dark:text-black shadow-md'
                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                            >
                                All ({{ searchResults.length }})
                            </button>
                            <button
                                @click="selectedType = 'Movie'"
                                class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                                :class="selectedType === 'Movie'
                                    ? 'bg-blue-600 text-white shadow-md'
                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600'"
                            >
                                Movies ({{ searchResults.filter(r => r.type === 'Movie').length }})
                            </button>
                            <button
                                @click="selectedType = 'TV Show'"
                                class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                                :class="selectedType === 'TV Show'
                                    ? 'bg-purple-600 text-white shadow-md'
                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600'"
                            >
                                TV Shows ({{ searchResults.filter(r => r.type === 'TV Show').length }})
                            </button>
                        </div>

                        <!-- Code Preview -->
                        <div class="relative group">
                            <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl blur opacity-20 group-hover:opacity-30 transition duration-300"></div>
                            <div class="relative bg-gray-900 dark:bg-gray-950 rounded-xl overflow-hidden border border-gray-800">
                                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-800">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                        </svg>
                                        <span class="text-xs font-medium text-gray-400">SearchController.php</span>
                                    </div>
                                    <button
                                        @click="copyCode"
                                        class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 rounded transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                        Copy
                                    </button>
                                </div>
                                <div class="p-4 overflow-x-auto">
                                    <div class="flex font-mono text-xs sm:text-sm leading-relaxed">
                                        <!-- Line Numbers -->
                                        <div class="select-none pr-4 text-right text-gray-600 border-r border-gray-800 mr-6 min-w-[2rem]">
                                            <div
                                                v-for="(line, index) in highlightedCode"
                                                :key="`num-${index}`"
                                                class="code-line-num"
                                            >
                                                {{ index + 1 }}
                                            </div>
                                        </div>
                                        <!-- Code Content -->
                                        <div class="flex-1 relative">
                                            <div
                                                v-for="(line, index) in highlightedCode"
                                                :key="`line-${index}`"
                                                class="code-line"
                                                :class="{
                                                    'highlight-change': line.isNew,
                                                    'opacity-50': line.tokens.length === 0
                                                }"
                                            >
                                                <template v-if="line.tokens.length > 0">
                                                    <span
                                                        v-for="(token, tIndex) in line.tokens"
                                                        :key="`${index}-${tIndex}`"
                                                        :class="getTokenClass(token)"
                                                    >{{ token.value }}</span>
                                                </template>
                                                <span v-else>&nbsp;</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Results -->
                    <div v-if="hasSearched" class="space-y-6">
                        <div v-if="isSearching" class="text-center py-16 sm:py-20">
                            <div class="relative inline-flex">
                                <div class="w-12 h-12 border-4 border-gray-200 dark:border-gray-700 border-t-blue-600 rounded-full animate-spin"></div>
                                <div class="absolute inset-0 w-12 h-12 border-4 border-transparent border-t-purple-600 rounded-full animate-spin" style="animation-duration: 1.5s; animation-direction: reverse;"></div>
                            </div>
                            <p class="mt-6 text-base font-medium text-gray-600 dark:text-gray-400">Searching through 8,000+ titles...</p>
                        </div>

                        <div v-else-if="searchResults.length === 0" class="text-center py-16 sm:py-20">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M12 12h.01M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No results found</h3>
                            <p class="text-base text-gray-600 dark:text-gray-400 mb-6">Try a different search term or one of our suggestions above</p>
                            <button
                                @click="hasSearched = false; searchQuery = ''; searchResults = []"
                                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Try again
                            </button>
                        </div>

                        <div v-else>
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-gray-100">
                                        <span v-if="selectedType === 'all'">Found {{ searchResults.length }} results</span>
                                        <span v-else>Showing {{ filteredResults.length }} {{ selectedType === 'Movie' ? 'movies' : 'TV shows' }}</span>
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        Powered by semantic search
                                    </p>
                                </div>
                                <button
                                    @click="hasSearched = false; searchQuery = ''; searchResults = []; selectedType = 'all'"
                                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    New search
                                </button>
                            </div>

                            <div v-if="filteredResults.length === 0" class="text-center py-12">
                                <p class="text-gray-600 dark:text-gray-400">No {{ selectedType === 'Movie' ? 'movies' : 'TV shows' }} found in these results.</p>
                            </div>

                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5" id="results-grid">
                                <div
                                    v-for="(result, index) in filteredResults"
                                    :key="`${result._id || result.title}-${index}`"
                                    class="group relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-xl transition-all duration-300"
                                >
                                    <div class="absolute top-3 right-3">
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full"
                                            :class="result.type === 'Movie'
                                                ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                                                : 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300'">
                                            {{ result.type }}
                                        </span>
                                    </div>

                                    <div class="pr-16 mb-4">
                                        <h3 class="font-bold text-base text-gray-900 dark:text-gray-100 line-clamp-2 mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                            {{ result.title }}
                                        </h3>
                                        <p v-if="result.release_year" class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            {{ result.release_year }}
                                        </p>
                                    </div>

                                    <div class="space-y-2.5 text-xs text-gray-600 dark:text-gray-400">
                                        <div v-if="result.director" class="flex items-start gap-2">
                                            <svg class="w-4 h-4 shrink-0 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                            <span class="line-clamp-1 flex-1">{{ result.director }}</span>
                                        </div>
                                        <div v-if="result.cast" class="flex items-start gap-2">
                                            <svg class="w-4 h-4 shrink-0 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            <span class="line-clamp-2 flex-1">{{ result.cast }}</span>
                                        </div>
                                        <div v-if="result.country" class="flex items-start gap-2">
                                            <svg class="w-4 h-4 shrink-0 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path>
                                            </svg>
                                            <span class="line-clamp-1 flex-1">{{ result.country }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeInUp 0.5s ease-out forwards;
}

@keyframes highlightPulse {
    0% {
        background: linear-gradient(90deg, rgba(59, 130, 246, 0.4) 0%, rgba(147, 51, 234, 0.3) 100%);
        transform: translateX(-4px);
        box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
    }
    50% {
        background: linear-gradient(90deg, rgba(59, 130, 246, 0.6) 0%, rgba(147, 51, 234, 0.5) 100%);
        transform: translateX(0);
        box-shadow: 0 0 30px rgba(59, 130, 246, 0.5);
    }
    100% {
        background: linear-gradient(90deg, rgba(59, 130, 246, 0.2) 0%, rgba(147, 51, 234, 0.15) 100%);
        transform: translateX(0);
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.2);
    }
}

@keyframes slideIn {
    0% {
        opacity: 0;
        transform: translateX(-20px);
    }
    100% {
        opacity: 1;
        transform: translateX(0);
    }
}

.code-line-num {
    padding: 0.25rem 0;
    line-height: 1.5rem;
}

.code-line {
    padding: 0.25rem 0.75rem;
    margin: 0 -0.75rem;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
    position: relative;
    line-height: 1.5rem;
    min-height: 1.5rem;
}

.highlight-change {
    animation: highlightPulse 1.2s ease-out, slideIn 0.3s ease-out;
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.2) 0%, rgba(147, 51, 234, 0.15) 100%);
    border-left: 3px solid rgb(59, 130, 246);
    padding-left: calc(0.75rem - 3px);
    position: relative;
}

.highlight-change::before {
    content: '+';
    position: absolute;
    left: -1.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: rgb(34, 197, 94);
    font-weight: bold;
    font-size: 0.875rem;
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-50%) scale(0.8);
    }
    to {
        opacity: 1;
        transform: translateY(-50%) scale(1);
    }
}
</style>
