<template>
    <div class="min-h-screen bg-white dark:bg-gray-950">
        <Head title="Search Documentation" />
        
        <!-- Header -->
        <header class="border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center space-x-3">
                        <div class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                            Sigmie
                        </div>
                    </Link>
                    
                    <!-- Search Bar -->
                    <div class="flex-1 max-w-2xl mx-8">
                        <form @submit.prevent="performSearch" class="relative">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Ask a question or search documentation..."
                                class="w-full px-4 py-2 pl-10 pr-4 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg focus:outline-none focus:border-blue-500 dark:focus:border-blue-400"
                                @input="debouncedSearch"
                                autofocus
                            />
                            <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <div v-if="searchQuery" class="absolute right-2 top-2">
                                <button
                                    type="submit"
                                    :disabled="isSearching"
                                    class="px-3 py-1 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {{ isSearching ? 'Searching...' : 'Search' }}
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Navigation -->
                    <nav class="flex items-center space-x-6">
                        <Link href="/docs" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                            Docs
                        </Link>
                        <a href="https://github.com/sigmie/sigmie" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                            </svg>
                        </a>
                    </nav>
                </div>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Search Mode Toggle -->
            <div class="flex items-center justify-center mb-6">
                <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 p-1">
                    <button
                        @click="searchMode = 'semantic'"
                        :class="[
                            'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                            searchMode === 'semantic' 
                                ? 'bg-blue-600 text-white' 
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        ]"
                    >
                        AI-Powered Search
                    </button>
                    <button
                        @click="searchMode = 'standard'"
                        :class="[
                            'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                            searchMode === 'standard' 
                                ? 'bg-blue-600 text-white' 
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        ]"
                    >
                        Standard Search
                    </button>
                </div>
            </div>
            
            <!-- Loading State -->
            <div v-if="isSearching" class="flex justify-center py-12">
                <div class="text-center">
                    <div class="inline-flex items-center space-x-2">
                        <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ searchMode === 'semantic' ? 'Generating AI response...' : 'Searching documentation...' }}
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- AI Response (for semantic search) -->
            <div v-else-if="searchMode === 'semantic' && aiResponse" class="mb-8">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 rounded-xl p-6 border border-blue-200 dark:border-gray-700">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">AI Answer</h3>
                            <div 
                                v-html="renderMarkdown(aiResponse)" 
                                class="prose prose-blue dark:prose-invert max-w-none
                                       prose-headings:text-gray-900 dark:prose-headings:text-white
                                       prose-p:text-gray-700 dark:prose-p:text-gray-300
                                       prose-code:bg-gray-100 dark:prose-code:bg-gray-900 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded
                                       prose-pre:bg-gray-950 dark:prose-pre:bg-black prose-pre:border prose-pre:border-gray-800
                                       prose-a:text-blue-600 dark:prose-a:text-blue-400"
                            />
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Search Results -->
            <div v-if="searchResults.length > 0" class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ searchMode === 'semantic' ? 'Related Documentation' : 'Search Results' }}
                </h3>
                
                <div v-for="result in searchResults" :key="result._id" class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-6 hover:shadow-lg transition-shadow">
                    <Link :href="result.url" class="block group">
                        <div class="flex items-start justify-between mb-2">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                {{ result.title }}
                            </h4>
                            <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded">
                                {{ result.version }}
                            </span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">{{ result.description }}</p>
                        <div class="flex items-center space-x-4 text-sm">
                            <span class="text-gray-500 dark:text-gray-500">{{ result.section }}</span>
                            <span class="text-blue-600 dark:text-blue-400 group-hover:underline">
                                Read more →
                            </span>
                        </div>
                    </Link>
                </div>
            </div>
            
            <!-- Empty State -->
            <div v-else-if="!isSearching && searchQuery && hasSearched" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No results found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Try adjusting your search terms or switching search modes
                </p>
            </div>
            
            <!-- Initial State -->
            <div v-else-if="!searchQuery" class="text-center py-12">
                <div class="max-w-md mx-auto">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Search Sigmie Documentation</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Use AI-powered search to get instant answers or standard search to find specific documentation
                    </p>
                    
                    <!-- Suggested Topics -->
                    <div class="mt-6 space-y-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Popular topics</p>
                        <div class="flex flex-wrap gap-2 justify-center">
                            <button
                                v-for="topic in suggestedTopics"
                                :key="topic"
                                @click="searchQuery = topic; performSearch()"
                                class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full text-sm hover:bg-gray-200 dark:hover:bg-gray-700"
                            >
                                {{ topic }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { marked } from 'marked'
import DOMPurify from 'dompurify'
import { debounce } from 'lodash'

const searchQuery = ref('')
const searchResults = ref([])
const aiResponse = ref('')
const isSearching = ref(false)
const hasSearched = ref(false)
const searchMode = ref('semantic') // 'semantic' or 'standard'

const suggestedTopics = [
    'semantic search',
    'create index',
    'RAG',
    'mappings',
    'aggregations',
    'Laravel Scout',
    'filters',
    'analysis'
]

// Initialize marked options
marked.setOptions({
    breaks: true,
    gfm: true
})

const renderMarkdown = (content) => {
    if (!content) return ''
    const html = marked(content)
    return DOMPurify.sanitize(html)
}

const performSearch = async () => {
    if (!searchQuery.value.trim()) return
    
    isSearching.value = true
    hasSearched.value = true
    aiResponse.value = ''
    searchResults.value = []
    
    try {
        if (searchMode.value === 'semantic') {
            // Perform RAG search
            const response = await fetch('/api/search/rag', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ 
                    question: searchQuery.value 
                })
            })
            
            const data = await response.json()
            aiResponse.value = data.answer
            searchResults.value = data.results || []
        } else {
            // Perform standard search
            const response = await fetch('/api/search/standard', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ 
                    query: searchQuery.value 
                })
            })
            
            const data = await response.json()
            searchResults.value = data.results || []
        }
    } catch (error) {
        console.error('Search error:', error)
    } finally {
        isSearching.value = false
    }
}

// Debounced search for better UX
const debouncedSearch = debounce(() => {
    if (searchQuery.value.trim()) {
        performSearch()
    }
}, 500)

// Watch for search mode changes
watch(searchMode, () => {
    if (searchQuery.value.trim()) {
        performSearch()
    }
})
</script>