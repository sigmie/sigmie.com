<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref, computed, watch, nextTick, onMounted } from "vue";
import axios from "axios";
import AppLayout from "../Layouts/AppLayout.vue";
import CodePreview from "../components/CodePreview.vue";
import QueryInput from "../components/QueryInput.vue";
import Menu from "../components/Menu.vue";
import Container from "../components/Container.vue";
import SearchIcon from "../components/icons/SearchIcon.vue";
import ImageIcon from "../components/icons/ImageIcon.vue";
import ImageHoverOverlay from "../components/ImageHoverOverlay.vue";
import Logo from "../components/Logo.vue";

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
const showAllResults = ref(false);

// Netflix field toggles
const retrieveType = ref(true);
const retrieveYear = ref(true);
const retrieveDirector = ref(true);

// Netflix type filter
const typeFilter = ref('all'); // 'all', 'movie', 'tv'

// Image suggestions
const imageSuggestions = [
    { label: "Safari", query: "Safari" },
    { label: "Owl", query: "Owl" },
    { label: "Space", query: "Space" },
    { label: "Snow", query: "Snow" },
    { label: "Love", query: "Love" },
    { label: "Baloons", query: "Balloons" },
    { label: "Nature", query: "Nature" },
    { label: "Forest", query: "Forest" },
    { label: "Fox", query: "Fox" },
    { label: "Clouds", query: "Balloons" }
];

const getRandomImageSuggestion = () => {
    return imageSuggestions[Math.floor(Math.random() * imageSuggestions.length)].query;
};

// Image search state
const imageSearchResults = ref([]);
const imageQuery = ref(getRandomImageSuggestion());
const initialImages = ref([]);
const isLoadingImages = ref(false);
const isSearchingImages = ref(false);
const imageSearchMode = ref('text'); // 'text' or 'image'
const selectedImageUrl = ref(null);
const imageGridStyle = ref(1); // 1, 2, 3, 4 for different grid styles

const presetQueries = [
    { label: "Romantic Comedy", query: "romantic comedy light-hearted love" },
    { label: "Action Thriller", query: "action thriller intense suspense" },
    { label: "Sci-Fi", query: "science fiction futuristic space" },
    { label: "Drama", query: "drama emotional powerful" },
    { label: "Documentary", query: "documentary real-life educational" },
    { label: "Horror", query: "horror scary terrifying" }
];

let previousCodeLines = [];
let isFirstLoad = true;

const codeString = computed(() => {
    const query = searchQuery.value || 'zombie apocalypse';

    // Build retrieve array based on toggles
    const allFields = [
        { name: 'title', enabled: true },
        { name: 'description', enabled: true },
        { name: 'type', enabled: retrieveType.value },
        { name: 'release_year', enabled: retrieveYear.value },
        { name: 'director', enabled: retrieveDirector.value },
    ];

    const fieldLines = allFields.map(field => {
        const prefix = field.enabled ? '' : '// ';
        return `${prefix}'${field.name}'`;
    }).join(",\n        ");

    const retrieveString = `[\n        ${fieldLines}\n    ]`;

    // Build filter line based on type selection
    let filterLine = '';
    if (typeFilter.value === 'movie') {
        filterLine = `->filters('type:"Movie"')`;
    } else if (typeFilter.value === 'tv') {
        filterLine = `->filters('type:"TV Show"')`;
    } else if (typeFilter.value === 'all') {
        filterLine = `->filters('type:"TV Show" OR type:"Movie"')`;
    }

    const codeLines = [
'$netflixIndex->newSearch()',
`->queryString('${query}')`
    ];

    if (filterLine) {
        codeLines.push(filterLine.trim());
    }

    codeLines.push(
        `->retrieve(${retrieveString})`,
        '->size(4)',
        '->hits();'
    );

    return codeLines.join('\n    ');
});

const highlightedLines = computed(() => {
    const currentCodeLines = codeString.value.split('\n');
    const changedLines = [];

    // Skip highlighting on first load
    if (!isFirstLoad) {
        // Compare each line with the previous version
        currentCodeLines.forEach((line, index) => {
            if (previousCodeLines[index] !== line) {
                changedLines.push(index + 1); // 1-indexed
            }
        });
    } else {
        isFirstLoad = false;
    }

    // Update previous code lines for next comparison
    previousCodeLines = [...currentCodeLines];

    return changedLines;
});

const imageCodeString = computed(() => {
    const query = imageQuery.value || 'Safari';

    if (imageSearchMode.value === 'image') {
        const imageName = query.toLowerCase().replace(/\s+/g, '_');
        const fakeUrl = `https://img.sigmie.com/${imageName}1.png`;
        return `->queryImage('${fakeUrl}')`;
    } else {
        return `->queryString('${query}')`;
    }
});

const imageHighlightedLines = computed(() => {
    // Highlight the single line when active
    return imageSearchMode.value === 'image' || imageQuery.value ? [1] : [];
});

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
        // Build retrieve fields based on toggles
        const retrieveFields = ['title', 'description'];
        if (retrieveType.value) retrieveFields.push('type');
        if (retrieveYear.value) retrieveFields.push('release_year');
        if (retrieveDirector.value) retrieveFields.push('director');

        // Build filter based on type selection
        let filters = '';
        if (typeFilter.value === 'movie') {
            filters = 'type:"Movie"';
        } else if (typeFilter.value === 'tv') {
            filters = 'type:"TV Show"';
        } else if (typeFilter.value === 'all') {
            filters = 'type:"TV Show" OR type:"Movie"';
        }

        const response = await axios.post("/api/search/netflix", {
            query: searchTerm,
            retrieve: retrieveFields,
            filters: filters,
        });

        searchResults.value = response.data.results || [];
    } catch (error) {
        console.error("Search error:", error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

// Generate initial images based on query (fallback)
const generateInitialImages = () => {
    const seed = imageQuery.value.replace(/\s+/g, '-');
    initialImages.value = Array.from({ length: 9 }, (_, i) => ({
        id: i + 1,
        url: `https://picsum.photos/seed/${seed}-${i + 1}/400/300`,
        title: `${imageQuery.value} ${i + 1}`,
    }));
};

// Image search functions
const selectImageFromGallery = async (imageUrl) => {
    selectedImageUrl.value = imageUrl;
    imageSearchMode.value = 'image';
    isLoadingImages.value = true;

    try {
        const response = await axios.post("/api/search/images/image", {
            image: imageUrl,
        });

        initialImages.value = response.data.results.map((result, i) => ({
            id: i + 1,
            url: result.image,
            title: `Similar Image ${i + 1}`,
        }));
    } catch (error) {
        console.error("Image search error:", error);
    } finally {
        isLoadingImages.value = false;
    }
};

const updateImageQuery = async () => {
    if (!imageQuery.value.trim()) {
        return;
    }

    isLoadingImages.value = true;
    imageSearchMode.value = 'text';

    try {
        const response = await axios.post("/api/search/images/text", {
            query: imageQuery.value,
        });

        initialImages.value = response.data.results.map((result, i) => ({
            id: i + 1,
            url: result.image,
            title: `${imageQuery.value} ${i + 1}`,
        }));

        selectedImageUrl.value = null;
    } catch (error) {
        console.error("Image query error:", error);
        // Fallback to generating placeholder images
        generateInitialImages();
    } finally {
        isLoadingImages.value = false;
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

// Watch Netflix filter and toggles to auto-update search
watch(typeFilter, () => {
    if (hasSearched.value) {
        performSearch();
    }
});

watch([retrieveType, retrieveYear, retrieveDirector], () => {
    if (hasSearched.value) {
        performSearch();
    }
});

// Initialize searches on mount
onMounted(() => {
    updateImageQuery();
    // Load initial Netflix search results
    performSearch('zombie apocalypse');
});
</script>

<template>
    <Head :title="title" />

    <AppLayout :navigation="navigation">
        <template #default>

        <!-- Product Search Demo Section -->
        <div id="semantic-search" class="relative bg-canvas-white dark:bg-black overflow-hidden scroll-mt-20">
            <!-- Decorative background elements -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute top-1/4 -right-64 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-1/4 -left-64 w-96 h-96 bg-magic-orange/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 py-12 sm:py-16 lg:py-20 lg:px-8">
                <!-- Logo -->
                <div class="mb-12">
                    <Logo class="w-40 sm:w-48" />
                </div>

                <!-- Badges Section -->
                <div class="mb-12 flex justify-start items-center flex-wrap gap-4">
                    <a href="https://github.com/sigmie/sigmie/actions" target="_blank" rel="noopener noreferrer" class="inline-block">
                        <img src="https://github.com/sigmie/sigmie/actions/workflows/test.yml/badge.svg" alt="Build Status" class="h-6 hover:opacity-80 transition-opacity" />
                    </a>
                    <a href="https://packagist.org/packages/sigmie/sigmie" target="_blank" rel="noopener noreferrer" class="inline-block">
                        <img src="https://img.shields.io/packagist/v/sigmie/sigmie" alt="Latest Stable Version" class="h-6 hover:opacity-80 transition-opacity" />
                    </a>
                    <a href="https://packagist.org/packages/sigmie/sigmie/stats" target="_blank" rel="noopener noreferrer" class="inline-block">
                        <img src="https://img.shields.io/packagist/dt/sigmie/sigmie.svg" alt="Downloads" class="h-6 hover:opacity-80 transition-opacity" />
                    </a>
                    <a href="https://codecov.io/gh/sigmie/sigmie" target="_blank" rel="noopener noreferrer" class="inline-block">
                        <img src="https://codecov.io/gh/sigmie/sigmie/branch/master/graph/badge.svg?token=Dx6x8vPVN8" alt="Codecov" class="h-6 hover:opacity-80 transition-opacity" />
                    </a>
                    <a href="https://packagist.org/packages/sigmie/sigmie" target="_blank" rel="noopener noreferrer" class="inline-block">
                        <img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="License" class="h-6 hover:opacity-80 transition-opacity" />
                    </a>
                </div>

                <!-- Header Section -->
                <div class="mb-12">
                    <div class="relative border border-light-steel dark:border-gray-800 rounded-xl p-6 sm:p-8 bg-canvas-white dark:bg-black/50 backdrop-blur-sm overflow-hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                        <!-- Left Column: Title & Subtitle -->
                        <div class="text-left mb-12">
                            <h1 class="text-lg sm:text-xl font-medium text-graphite dark:text-gray-100 mb-4">
                                Sigmie — retrieval and search for PHP, Elasticsearch, and OpenSearch
                            </h1>
                            <p class="text-base sm:text-lg text-charcoal dark:text-gray-400 leading-relaxed mb-6">
                                Keyword, vector, hybrid retrieval, and reranking — the search-and-retrieval layer behind modern PHP apps and AI agents.
                            </p>
                            <Link
                                href="/docs"
                                class="group inline-flex items-center gap-2 pl-5 pr-4 py-2 text-sm font-medium rounded-full bg-graphite text-white hover:bg-charcoal dark:bg-white dark:text-graphite dark:hover:bg-gray-200 transition-colors duration-150"
                            >
                                Read the docs
                                <svg class="w-3.5 h-3.5 transition-transform duration-150 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                            </Link>
                        </div>

                        <!-- Right Column: Logo Images -->
                        <div class="flex justify-end gap-4 space-x-16 items-end px-10">
                            <img src="/elasticsearch.png" alt="Elasticsearch" class="w-16 h-auto object-contain" />
                            <img src="/opensearch.png" alt="OpenSearch" class="w-16 h-auto object-contain" />
                        </div>
                        </div>
                    </div>
                </div>

                <!-- SEO content section -->
                <section class="mb-12 prose prose-neutral dark:prose-invert max-w-none
                                prose-headings:font-semibold prose-headings:tracking-tight
                                prose-p:text-charcoal dark:prose-p:text-gray-300 prose-p:leading-[1.7]
                                prose-a:text-magic-orange prose-a:hover:text-graphite dark:prose-a:hover:text-white prose-a:no-underline">
                    <h2 class="text-[24px] sm:text-[28px] font-semibold text-graphite dark:text-white tracking-tight !mb-4">
                        What is Sigmie?
                    </h2>
                    <p class="!mt-0">
                        <strong>Sigmie</strong> is an open-source PHP library for Elasticsearch and OpenSearch — built for developers who care about search relevance, not about wiring up low-level mappings and analyzers.
                    </p>
                    <p>
                        It ships a single fluent API for keyword search, vector and hybrid retrieval, faceted filtering, aggregations, autocompletion, and reranking — the building blocks you assemble into search, recommendations, or the retrieval layer of a RAG pipeline.
                    </p>
                    <p>
                        If you're tired of stitching together raw Elasticsearch JSON, hand-rolling tokenizers, or paying a hosted search-as-a-service for queries you could run yourself — Sigmie gives you the ergonomics of a hosted service with the cost profile of an open-source library you control. Mappings come with sensible defaults, and semantic search is one method call away.
                    </p>

                    <h2 class="text-[24px] sm:text-[28px] font-semibold text-graphite dark:text-white tracking-tight !mt-12 !mb-4">
                        Who is Sigmie for?
                    </h2>
                    <p class="!mt-0">
                        PHP and Laravel teams building search for SaaS, e-commerce, content sites, internal tools, and AI agents. If you're already running Elasticsearch or OpenSearch (or thinking about it) and you want a library that gets out of your way, Sigmie is the shortest path from <em>"we need search"</em> to <em>"search is shipped"</em>.
                    </p>

                    <h2 class="text-[24px] sm:text-[28px] font-semibold text-graphite dark:text-white tracking-tight !mt-12 !mb-4">
                        What makes Sigmie different
                    </h2>
                    <ul class="!mt-0">
                        <li><strong>Hybrid keyword + semantic search</strong> out of the box.</li>
                        <li><strong>Retrieval and reranking building blocks</strong> for RAG pipelines and AI agents.</li>
                        <li><strong>Embeddings adapters</strong> for OpenAI, Cohere, Voyage, Jina, and Infinity — or bring your own. 384-dim vectors by default.</li>
                        <li><strong>Image search via CLIP adapters</strong> — one method call with <code>queryImage()</code>.</li>
                        <li><strong>Predefined property types</strong> tuned for relevance — name, email, address, price, tags, and friends.</li>
                        <li><strong>Fluent, typed PHP API</strong> — no raw JSON, no untyped arrays.</li>
                        <li><strong>Sigmie docs as a Model Context Protocol server</strong> at <a href="/mcp">/mcp</a> — so AI agents can search and read the library docs while writing your code.</li>
                        <li><strong>Laravel-friendly</strong> — Scout driver and AI agent tools available as companion packages.</li>
                        <li><strong>OpenSearch supported</strong> — drop-in connection swap.</li>
                    </ul>

                    <p class="!mt-8">
                        Get started with the <Link href="/docs/v2/quick-start" class="text-magic-orange">5-minute Quick Start</Link>, browse the <Link href="/docs/v2/introduction" class="text-magic-orange">Introduction</Link>, or jump into <Link href="/docs/v2/semantic-search" class="text-magic-orange">Semantic Search</Link> if you already know your way around Elasticsearch.
                    </p>
                </section>

                <!-- Main Content Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column: Search -->
                    <div class="rounded-lg p-6 border border-light-steel dark:border-white/10 bg-ghostly-gray dark:bg-[linear-gradient(59.66deg,#0C0D0F_0%,#07080A_100%)] shadow-sm">
                        <div class="space-y-8">
                            <!-- Search Form -->
                            <QueryInput
                                v-model="searchQuery"
                                placeholder="Search by mood, genre, or description..."
                                :disabled="isSearching"
                                @submit="performSearch()"
                            >
                                <template #icon>
                                    <SearchIcon />
                                </template>
                            </QueryInput>

                            <!-- Filters and Switches -->
                            <div class="flex flex-wrap items-center gap-4 px-1">
                                <!-- Type Filter Toggles -->
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="typeFilter = typeFilter === 'all' ? '' : 'all'"
                                        :class="typeFilter === 'all' ? 'bg-magic-orange text-white' : 'bg-ghostly-gray dark:bg-gray-800 text-charcoal dark:text-gray-400 hover:bg-fog dark:hover:bg-fog dark:bg-gray-700'"
                                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors"
                                    >
                                        All
                                    </button>
                                    <button
                                        @click="typeFilter = typeFilter === 'movie' ? '' : 'movie'"
                                        :class="typeFilter === 'movie' ? 'bg-magic-orange text-white' : 'bg-ghostly-gray dark:bg-gray-800 text-charcoal dark:text-gray-400 hover:bg-fog dark:hover:bg-fog dark:bg-gray-700'"
                                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors"
                                    >
                                        Movies
                                    </button>
                                    <button
                                        @click="typeFilter = typeFilter === 'tv' ? '' : 'tv'"
                                        :class="typeFilter === 'tv' ? 'bg-magic-orange text-white' : 'bg-ghostly-gray dark:bg-gray-800 text-charcoal dark:text-gray-400 hover:bg-fog dark:hover:bg-fog dark:bg-gray-700'"
                                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors"
                                    >
                                        TV Shows
                                    </button>
                                </div>

                                <!-- Field Switches -->
                                <div class="flex items-center gap-3">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <div class="relative">
                                            <input
                                                type="checkbox"
                                                v-model="retrieveType"
                                                class="sr-only peer"
                                            />
                                            <div class="w-9 h-5 bg-fog dark:bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-magic-orange"></div>
                                        </div>
                                        <span class="text-sm text-charcoal dark:text-gray-400">Type</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <div class="relative">
                                            <input
                                                type="checkbox"
                                                v-model="retrieveYear"
                                                class="sr-only peer"
                                            />
                                            <div class="w-9 h-5 bg-fog dark:bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-magic-orange"></div>
                                        </div>
                                        <span class="text-sm text-charcoal dark:text-gray-400">Year</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <div class="relative">
                                            <input
                                                type="checkbox"
                                                v-model="retrieveDirector"
                                                class="sr-only peer"
                                            />
                                            <div class="w-9 h-5 bg-fog dark:bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-magic-orange"></div>
                                        </div>
                                        <span class="text-sm text-charcoal dark:text-gray-400">Director</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Preset Queries -->
                            <div v-if="!hasSearched" class="space-y-2">
                                <p class="text-sm font-medium text-charcoal dark:text-gray-400 mb-3">Try these popular searches:</p>
                                <div class="flex flex-col gap-2">
                                    <button
                                        v-for="preset in presetQueries"
                                        :key="preset.query"
                                        @click="performSearch(preset.query)"
                                        class="group text-left px-4 py-2 text-sm font-medium text-charcoal dark:text-gray-300 bg-ghostly-gray dark:bg-gray-800/50 border border-light-steel dark:border-gray-700 rounded-lg hover:border-blue-500 hover:text-blue-400 hover:bg-ghostly-gray dark:hover:bg-ghostly-gray dark:bg-gray-800 transition-all duration-200"
                                        :disabled="isSearching"
                                    >
                                        <span class="flex items-center gap-2">
                                            {{ preset.label }}
                                            <svg class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Results -->
                            <Menu
                                v-if="hasSearched"
                                :items="searchResults"
                                :loading="isSearching"
                                :max-items="10"
                                empty-message="No results found"
                            />
                        </div>
                    </div>

                    <!-- Right Column: Code Preview -->
                    <div class="hidden lg:block">
                        <div class="sticky top-24">
                            <CodePreview
                                :code="codeString"
                                filename="NetflixSearchController.php"
                                :highlight-lines="highlightedLines"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Search Demo Section -->
        <div id="image-search" class="relative border-t border-light-steel dark:border-gray-800 bg-canvas-white dark:bg-black overflow-hidden scroll-mt-20">
            <!-- Decorative background elements -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute top-1/3 -left-64 w-96 h-96 bg-magic-orange/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-1/3 -right-64 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 py-16 sm:py-20 lg:py-28 lg:px-8">
                <div class="max-w-6xl mx-auto">
                    <!-- Hero Text Section -->
                    <div class="mb-12">
                        <h2 class="text-lg sm:text-xl font-medium text-graphite dark:text-gray-100 mb-4">
                            Search beyond just keywords
                        </h2>
                        <p class="text-base sm:text-lg text-charcoal dark:text-gray-400 leading-relaxed">
                            Create search experiences that understand images, not just text.
                        </p>
                    </div>

                    <!-- Code Preview -->
                    <div class="mb-16">
                        <CodePreview
                            :code="imageCodeString"
                            filename="ImageSearchController.php"
                            :highlight-lines="imageHighlightedLines"
                            :fade-right="true"
                            :fade-bottom="true"
                            :fade-left="false"
                            :fade-width-right="200"
                            :fade-height-bottom="48"
                        />
                    </div>

                    <!-- Query Input - Always Visible -->
                    <div class="mb-8">
                        <QueryInput
                            v-model="imageQuery"
                            placeholder="What kind of images are you looking for?"
                            :disabled="isLoadingImages || isSearchingImages"
                            @submit="updateImageQuery"
                        >
                            <template #icon>
                                <ImageIcon />
                            </template>
                        </QueryInput>

                        <!-- Suggestions -->
                        <div class="flex flex-wrap gap-2 mt-4">
                            <button
                                v-for="suggestion in imageSuggestions"
                                :key="suggestion.query"
                                @click="imageQuery = suggestion.query; updateImageQuery()"
                                class="group text-left px-3 py-1.5 text-xs font-medium text-charcoal dark:text-gray-300 bg-ghostly-gray dark:bg-gray-800/50 border border-light-steel dark:border-gray-700 rounded-lg hover:border-blue-500 hover:text-blue-400 hover:bg-ghostly-gray dark:hover:bg-ghostly-gray dark:bg-gray-800 transition-all duration-200"
                                :disabled="isLoadingImages"
                            >
                                {{ suggestion.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Images Gallery -->
                    <div class="mb-12">
                        <!-- Grid Layout -->
                        <div class="grid grid-cols-6 grid-rows-2 gap-2 h-[400px] transition-all duration-300" :class="{ 'blur-sm opacity-50': isLoadingImages }">
                            <!-- Image 1: 1 column × 2 rows -->
                            <button
                                v-if="initialImages[0]"
                                @click="selectImageFromGallery(initialImages[0].url)"
                                :disabled="isLoadingImages"
                                class="group relative col-span-1 row-span-2 overflow-hidden rounded-md border border-light-steel dark:border-gray-800 hover:border-fog dark:hover:border-fog dark:border-gray-600 transition-all duration-200 hover:shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                            >
                                <img :src="initialImages[0].url" :alt="initialImages[0].title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                <ImageHoverOverlay />
                            </button>

                            <!-- Image 2: 3 columns × 2 rows -->
                            <button
                                v-if="initialImages[1]"
                                @click="selectImageFromGallery(initialImages[1].url)"
                                :disabled="isLoadingImages"
                                class="group relative col-span-3 row-span-2 overflow-hidden rounded-md border border-light-steel dark:border-gray-800 hover:border-fog dark:hover:border-fog dark:border-gray-600 transition-all duration-200 hover:shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                            >
                                <img :src="initialImages[1].url" :alt="initialImages[1].title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                <ImageHoverOverlay />
                            </button>

                            <!-- Image 3: 2 columns × 1 row -->
                            <button
                                v-if="initialImages[2]"
                                @click="selectImageFromGallery(initialImages[2].url)"
                                :disabled="isLoadingImages"
                                class="group relative col-span-2 row-span-1 overflow-hidden rounded-md border border-light-steel dark:border-gray-800 hover:border-fog dark:hover:border-fog dark:border-gray-600 transition-all duration-200 hover:shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                            >
                                <img :src="initialImages[2].url" :alt="initialImages[2].title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                <ImageHoverOverlay />
                            </button>

                            <!-- Image 4: 2 columns × 1 row -->
                            <button
                                v-if="initialImages[3]"
                                @click="selectImageFromGallery(initialImages[3].url)"
                                :disabled="isLoadingImages"
                                class="group relative col-span-2 row-span-1 overflow-hidden rounded-md border border-light-steel dark:border-gray-800 hover:border-fog dark:hover:border-fog dark:border-gray-600 transition-all duration-200 hover:shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                            >
                                <img :src="initialImages[3].url" :alt="initialImages[3].title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                <ImageHoverOverlay />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- About Me Section -->
        <div id="about" class="border-t border-light-steel dark:border-gray-800 bg-canvas-white dark:bg-black scroll-mt-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 py-16 sm:py-20 lg:py-28 lg:px-8">
                <div class="text-center mb-12 sm:mb-16">
                    <h2 class="text-lg sm:text-xl font-medium text-graphite dark:text-gray-100 mb-4">
                        About Me
                    </h2>
                    <p class="text-lg text-charcoal dark:text-gray-400 max-w-2xl mx-auto">
                        Hi, I'm Nico Orfanos – passionate about building search experiences from Dortmund, Germany
                    </p>
                </div>

                <div class="max-w-6xl mx-auto">
                    <!-- Profile Card -->
                    <div class="max-w-lg mx-auto mb-16 p-8 bg-ghostly-gray dark:bg-gradient-to-br dark:from-gray-900 dark:to-gray-950 rounded-3xl border border-light-steel dark:border-gray-800 shadow-xl">
                        <div class="flex items-start gap-6 mb-6">
                            <img
                                src="https://github.com/nicoorfi.png"
                                alt="Nico Orfanos"
                                class="w-20 h-20 rounded-2xl border-2 border-light-steel dark:border-gray-700 shadow-lg flex-shrink-0"
                            />
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xl font-semibold text-graphite dark:text-white mb-1">
                                    Nico
                                </h3>
                                <p class="text-charcoal dark:text-gray-300 text-sm mb-3">
                                    Search Relevance Engineer
                                </p>
                                <div class="flex items-center gap-2 text-charcoal dark:text-gray-400 text-sm mb-4">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span>Dortmund, Germany</span>
                                </div>
                                <div class="flex flex-wrap gap-2 mb-6">
                                    <span class="px-3 py-1 bg-ghostly-gray dark:bg-gray-800/70 border border-light-steel dark:border-gray-700 text-charcoal dark:text-gray-300 rounded-lg text-xs font-medium">
                                        Opensearch 
                                    </span>
                                    <span class="px-3 py-1 bg-ghostly-gray dark:bg-gray-800/70 border border-light-steel dark:border-gray-700 text-charcoal dark:text-gray-300 rounded-lg text-xs font-medium">
                                        Elasticsearch 
                                    </span>
                                    <span class="px-3 py-1 bg-ghostly-gray dark:bg-gray-800/70 border border-light-steel dark:border-gray-700 text-charcoal dark:text-gray-300 rounded-lg text-xs font-medium">
                                        Php
                                    </span>
                                    <span class="px-3 py-1 bg-ghostly-gray dark:bg-gray-800/70 border border-light-steel dark:border-gray-700 text-charcoal dark:text-gray-300 rounded-lg text-xs font-medium">
                                        Python
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Social Links -->
                        <div class="flex items-center justify-center gap-8 pt-6 border-t border-light-steel dark:border-gray-800">
                            <a
                                href="https://github.com/nicoorfi"
                                target="_blank"
                                class="text-charcoal dark:text-gray-400 hover:text-graphite dark:hover:text-white transition-colors"
                                title="GitHub"
                            >
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"></path>
                                </svg>
                            </a>
                            <a
                                href="https://www.linkedin.com/in/nicoorfi/"
                                target="_blank"
                                class="text-charcoal dark:text-gray-400 hover:text-graphite dark:hover:text-white transition-colors"
                                title="LinkedIn"
                            >
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                            </a>
                            <a
                                href="https://www.upwork.com/freelancers/~0168612ffb19d75fc6"
                                target="_blank"
                                class="text-charcoal dark:text-gray-400 hover:text-graphite dark:hover:text-white transition-colors"
                                title="Upwork"
                            >
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.561 13.158c-1.102 0-2.135-.467-3.074-1.227l.228-1.076.008-.042c.207-1.143.849-3.06 2.839-3.06 1.492 0 2.703 1.212 2.703 2.703 0 1.489-1.211 2.702-2.704 2.702zm0-8.14c-2.539 0-4.51 1.649-5.31 4.366-1.22-1.834-2.148-4.036-2.687-5.892H7.828v7.112c-.002 1.406-1.141 2.546-2.547 2.548-1.405-.002-2.543-1.143-2.545-2.548V3.492H0v7.112c0 2.914 2.37 5.303 5.281 5.303 2.913 0 5.283-2.389 5.283-5.303v-1.19c.529 1.107 1.182 2.229 1.974 3.221l-1.673 7.873h2.797l1.213-5.71c1.063.679 2.285 1.109 3.686 1.109 3 0 5.439-2.452 5.439-5.45 0-3-2.439-5.439-5.439-5.439z"/>
                                </svg>
                            </a>
                            <a
                                href="https://x.com/nicoorfi"
                                target="_blank"
                                class="text-charcoal dark:text-gray-400 hover:text-graphite dark:hover:text-white transition-colors"
                                title="Twitter"
                            >
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path>
                                </svg>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        </template>
    </AppLayout>
</template>

<style scoped>
/* No custom styles needed - component handles everything */
</style>
