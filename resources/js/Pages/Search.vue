<template>
    <div class="min-h-screen bg-white dark:bg-gray-950">
        <Head>
            <title>Search Documentation - Sigmie</title>
            <meta charset="utf-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />
            <meta name="description" content="Search through Sigmie documentation. Find guides, examples, and API references for the Elasticsearch library." />
            <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
            <meta name="language" content="en-us" />
            <link rel="canonical" href="https://sigmie.com/search" />

            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="website" />
            <meta property="og:url" content="https://sigmie.com/search" />
            <meta property="og:title" content="Search Documentation - Sigmie" />
            <meta property="og:description" content="Search through Sigmie documentation. Find guides, examples, and API references." />
            <meta property="og:site_name" content="Sigmie" />

            <!-- Twitter -->
            <meta name="twitter:card" content="summary" />
            <meta name="twitter:url" content="https://sigmie.com/search" />
            <meta name="twitter:title" content="Search Documentation - Sigmie" />
            <meta name="twitter:description" content="Search through Sigmie documentation. Find guides, examples, and API references." />
        </Head>
        
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
            <!-- Conversation ID Display -->
            <div v-if="conversationId && searchMode === 'semantic'" class="flex items-center justify-center mb-4">
                <div class="inline-flex items-center space-x-2 px-4 py-2 bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-lg text-sm">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span class="text-gray-700 dark:text-gray-300">Conversation:</span>
                    <code class="font-mono text-xs text-blue-700 dark:text-blue-300">{{ conversationId.slice(0, 8) }}...</code>
                    <button
                        @click="clearConversation"
                        class="ml-2 text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
                        title="Clear conversation context"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            
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
            
            <!-- Processing Steps Display -->
            <div v-if="processingSteps.length > 0 && searchMode === 'semantic'" class="mb-6">
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-800">
                    <div class="space-y-2">
                        <div v-for="step in processingSteps" :key="step.type" class="flex items-center space-x-3">
                            <!-- Step indicator -->
                            <div class="flex-shrink-0">
                                <div v-if="step.status === 'completed'" class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div v-else-if="step.status === 'running' || step.status === 'streaming'" class="w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center">
                                    <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                                </div>
                                <div v-else class="w-5 h-5 bg-gray-300 dark:bg-gray-700 rounded-full"></div>
                            </div>
                            <!-- Step message -->
                            <div class="flex-1">
                                <span class="text-sm" :class="{
                                    'text-gray-900 dark:text-white font-medium': step.status === 'running' || step.status === 'streaming',
                                    'text-gray-600 dark:text-gray-400': step.status === 'completed'
                                }">
                                    {{ step.message }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Current status -->
                    <div v-if="currentStatus && isSearching" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-800">
                        <div class="flex items-center space-x-2">
                            <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ currentStatus }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- AI Response (for semantic search) -->
            <div v-if="searchMode === 'semantic' && aiResponse" class="mb-8">
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
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-3">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">AI Answer</h3>
                                    <div v-if="conversationId" class="flex items-center space-x-2">
                                        <div class="flex items-center space-x-1">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            <span class="text-xs text-gray-500 dark:text-gray-400" :title="'Conversation: ' + conversationId">
                                                Context preserved
                                            </span>
                                        </div>
                                        <button
                                            @click="clearConversation"
                                            class="text-xs text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 underline"
                                        >
                                            Clear context
                                        </button>
                                    </div>
                                </div>
                                <div v-if="isSearching" class="flex items-center space-x-2">
                                    <div class="flex space-x-1">
                                        <div class="w-1.5 h-1.5 bg-blue-600 rounded-full animate-pulse"></div>
                                        <div class="w-1.5 h-1.5 bg-blue-600 rounded-full animate-pulse" style="animation-delay: 150ms"></div>
                                        <div class="w-1.5 h-1.5 bg-blue-600 rounded-full animate-pulse" style="animation-delay: 300ms"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Streaming response...</span>
                                </div>
                            </div>
                            <div 
                                v-html="renderMarkdown(aiResponse)" 
                                class="prose prose-blue dark:prose-invert max-w-none
                                       prose-headings:text-gray-900 dark:prose-headings:text-white
                                       prose-p:text-gray-700 dark:prose-p:text-gray-300
                                       prose-code:bg-gray-100 dark:prose-code:bg-gray-900 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded
                                       prose-pre:bg-gray-950 dark:prose-pre:bg-black prose-pre:border prose-pre:border-gray-800
                                       prose-a:text-blue-600 dark:prose-a:text-blue-400"
                            />
                            
                            <!-- Source Citations -->
                            <div v-if="aiSources.length > 0" class="mt-6 pt-4 border-t border-blue-200 dark:border-gray-700">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Sources</h4>
                                <div class="space-y-2">
                                    <div v-for="source in aiSources" :key="source.index" class="flex items-start space-x-2">
                                        <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 bg-gray-100 dark:bg-gray-800 text-xs font-medium text-gray-600 dark:text-gray-400 rounded">
                                            {{ source.index }}
                                        </span>
                                        <Link 
                                            :href="source.url"
                                            class="flex-1 text-sm text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                        >
                                            <span class="font-medium">{{ source.title }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-500 ml-2">({{ source.version }})</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Retrieved Documents (for semantic search) -->
            <div v-if="searchMode === 'semantic' && searchDocuments.length > 0" class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Documents Used for Answer</h3>
                <div class="grid gap-3">
                    <div v-for="(doc, index) in searchDocuments" :key="index" 
                         class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-100 dark:bg-blue-900 text-xs font-bold text-blue-600 dark:text-blue-400 rounded">
                                        {{ index + 1 }}
                                    </span>
                                    <Link :href="doc.url" class="text-sm font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ doc.title }}
                                    </Link>
                                    <span class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded">
                                        {{ doc.version }}
                                    </span>
                                    <span v-if="doc.section" class="text-xs text-gray-500 dark:text-gray-500">
                                        • {{ doc.section }}
                                    </span>
                                </div>
                                <p v-if="doc.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ doc.description }}
                                </p>
                            </div>
                            <div v-if="doc.score" class="ml-4 flex-shrink-0">
                                <span class="text-xs text-gray-500 dark:text-gray-500">Score: {{ doc.score?.toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Search Results -->
            <div v-else-if="searchResults.length > 0 && searchMode === 'standard'" class="space-y-4">
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
const aiSources = ref([])  // Store source citations
const searchDocuments = ref([])  // Store retrieved documents
const isSearching = ref(false)
const hasSearched = ref(false)
const searchMode = ref('semantic') // 'semantic' or 'standard'
const currentStatus = ref('')  // Current processing status
const processingSteps = ref([])  // Track all processing steps
const conversationId = ref(null)  // Store conversation ID for context

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
    aiSources.value = []
    searchDocuments.value = []
    searchResults.value = []
    currentStatus.value = ''
    processingSteps.value = []
    // Keep conversation ID to maintain context
    
    try {
        if (searchMode.value === 'semantic') {
            // Use streaming for RAG search
            const response = await fetch('/api/search/rag-stream', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ 
                    question: searchQuery.value,
                    conversation_id: conversationId.value 
                })
            })
            
            if (!response.ok) throw new Error('Stream request failed')
            
            const reader = response.body.getReader()
            const decoder = new TextDecoder()
            let buffer = ''
            
            // Process stream with minimal delay
            const processStream = async () => {
                while (true) {
                    const { done, value } = await reader.read()
                    if (done) break
                    
                    // Decode chunk immediately
                    const chunk = decoder.decode(value, { stream: true })
                    buffer += chunk
                    
                    // Process complete lines immediately
                    let newlineIndex
                    while ((newlineIndex = buffer.indexOf('\n')) !== -1) {
                        const line = buffer.slice(0, newlineIndex)
                        buffer = buffer.slice(newlineIndex + 1)
                        
                        if (line.startsWith('data: ')) {
                            try {
                                const data = JSON.parse(line.slice(6))
                                
                                // Handle all event types
                                switch(data.type) {
                                    case 'search.started':
                                        currentStatus.value = data.message
                                        processingSteps.value.push({ type: 'search', status: 'running', message: data.message })
                                        break
                                    case 'search.completed':
                                        const searchStep = processingSteps.value.find(s => s.type === 'search')
                                        if (searchStep) {
                                            searchStep.status = 'completed'
                                            searchStep.message = data.message
                                        }
                                        currentStatus.value = data.message
                                        break
                                    case 'rerank.started':
                                        currentStatus.value = data.message
                                        processingSteps.value.push({ type: 'rerank', status: 'running', message: data.message })
                                        break
                                    case 'rerank.completed':
                                        const rerankStep = processingSteps.value.find(s => s.type === 'rerank')
                                        if (rerankStep) {
                                            rerankStep.status = 'completed'
                                            rerankStep.message = data.message
                                        }
                                        currentStatus.value = data.message
                                        break
                                    case 'prompt.generated':
                                        processingSteps.value.push({ type: 'prompt', status: 'completed', message: data.message })
                                        currentStatus.value = data.message
                                        break
                                    case 'llm.request.started':
                                        currentStatus.value = data.message
                                        processingSteps.value.push({ type: 'llm', status: 'running', message: data.message })
                                        break
                                    case 'llm.first_token':
                                        const llmStep = processingSteps.value.find(s => s.type === 'llm')
                                        if (llmStep) {
                                            llmStep.status = 'streaming'
                                            llmStep.message = 'Streaming response...'
                                        }
                                        currentStatus.value = 'Streaming response...'
                                        break
                                    case 'conversation.created':
                                        conversationId.value = data.conversation_id
                                        processingSteps.value.push({ 
                                            type: 'conversation', 
                                            status: 'completed', 
                                            message: 'New conversation started' 
                                        })
                                        break
                                    case 'conversation.reused':
                                        conversationId.value = data.conversation_id
                                        processingSteps.value.push({ 
                                            type: 'conversation', 
                                            status: 'completed', 
                                            message: 'Using existing conversation context' 
                                        })
                                        break
                                    case 'stream.start':
                                        aiSources.value = data.sources || []
                                        searchDocuments.value = data.documents || []
                                        searchResults.value = data.documents || []  // Also update search results
                                        if (data.conversation_id) {
                                            conversationId.value = data.conversation_id
                                        }
                                        break
                                    case 'content.delta':
                                        // Append content immediately for real-time display
                                        aiResponse.value += data.content
                                        break
                                    case 'stream.complete':
                                        const finalLlmStep = processingSteps.value.find(s => s.type === 'llm')
                                        if (finalLlmStep) {
                                            finalLlmStep.status = 'completed'
                                            finalLlmStep.message = 'Response complete'
                                        }
                                        currentStatus.value = ''
                                        isSearching.value = false
                                        break
                                    case 'error':
                                        console.error('Stream error:', data.error)
                                        aiResponse.value = data.error || 'An error occurred'
                                        currentStatus.value = 'Error occurred'
                                        isSearching.value = false
                                        break
                                    // Legacy event support
                                    case 'results':
                                        searchResults.value = data.results || []
                                        break
                                    case 'sources':
                                        aiSources.value = data.sources || []
                                        break
                                    case 'content':
                                        aiResponse.value += data.content
                                        break
                                    case 'done':
                                        isSearching.value = false
                                        break
                                }
                            } catch (e) {
                                // Ignore JSON parse errors
                            }
                        }
                    }
                }
            }
            
            await processStream()
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
        aiResponse.value = 'An error occurred while searching. Please try again.'
    } finally {
        isSearching.value = false
    }
}

// Clear conversation context
const clearConversation = async () => {
    try {
        const response = await fetch('/api/search/clear-conversation', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        })
        
        if (response.ok) {
            conversationId.value = null
            // Clear the AI response and sources to indicate context was cleared
            aiResponse.value = ''
            aiSources.value = []
            searchDocuments.value = []
            processingSteps.value = []
        }
    } catch (error) {
        console.error('Failed to clear conversation:', error)
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