<template>
    <div class="min-h-screen bg-canvas-white dark:bg-black font-sans text-graphite dark:text-white">
        <Head :title="title" />

        <header class="border-b border-light-steel dark:border-gray-800 bg-canvas-white/90 dark:bg-black/90 backdrop-blur sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="flex items-center justify-between h-16 gap-8">
                    <Link href="/" class="flex items-center">
                        <span class="text-[20px] font-semibold tracking-tight text-graphite dark:text-white">Sigmie</span>
                    </Link>

                    <div class="hidden md:block flex-1 max-w-2xl">
                        <form @submit.prevent="performSearch" class="relative">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Ask a question or search documentation..."
                                class="w-full pl-11 pr-28 py-2.5 text-[14px] text-graphite dark:text-white bg-ghostly-gray dark:bg-gray-900 border border-light-steel dark:border-gray-800 rounded-full focus:outline-none focus:border-carbon-black dark:focus:border-white placeholder-subtle-gray"
                                @input="debouncedSearch"
                                autofocus
                            />
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-subtle-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <div v-if="searchQuery" class="absolute right-2 top-1/2 -translate-y-1/2">
                                <button
                                    type="submit"
                                    :disabled="isSearching"
                                    class="px-4 py-1.5 text-[13px] font-medium bg-carbon-black text-canvas-white rounded-full hover:bg-graphite disabled:opacity-50 transition-colors"
                                >
                                    {{ isSearching ? 'Searching…' : 'Search' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <nav class="flex items-center gap-6">
                        <Link href="/docs" class="text-[14px] text-charcoal hover:text-graphite dark:text-gray-400 dark:hover:text-white transition-colors">
                            Docs
                        </Link>
                        <a href="https://github.com/sigmie/sigmie" aria-label="Sigmie on GitHub" class="text-charcoal hover:text-graphite dark:text-gray-400 dark:hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                            </svg>
                        </a>
                    </nav>
                </div>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-6 lg:px-8 py-16">
            <section class="mb-12 text-center">
                <h1 class="text-[40px] sm:text-[56px] leading-[0.95] font-semibold tracking-tight text-graphite dark:text-white text-balance mb-5">
                    Sigmie Search Playground
                </h1>
                <p class="text-[17px] leading-[1.5] text-charcoal dark:text-gray-400 max-w-2xl mx-auto">
                    Try Sigmie's hybrid keyword and semantic search live against the project's own documentation. AI-Powered Search runs vector retrieval with citations; Standard Search runs a traditional Elasticsearch query.
                </p>
            </section>

            <div v-if="conversationId && searchMode === 'semantic'" class="flex items-center justify-center mb-6">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-ghostly-gray dark:bg-gray-900 border border-light-steel dark:border-gray-800 rounded-full text-[13px]">
                    <svg class="w-4 h-4 text-magic-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span class="text-charcoal dark:text-gray-300">Conversation</span>
                    <code class="font-mono text-[12px] text-graphite dark:text-white">{{ conversationId.slice(0, 8) }}…</code>
                    <button
                        @click="clearConversation"
                        class="ml-2 text-subtle-gray hover:text-graphite transition-colors"
                        title="Clear conversation context"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-center mb-10">
                <div class="inline-flex rounded-full border border-light-steel dark:border-gray-800 p-1 bg-ghostly-gray dark:bg-gray-900">
                    <button
                        @click="searchMode = 'semantic'"
                        :class="[
                            'px-5 py-2 rounded-full text-[13px] font-medium transition-colors',
                            searchMode === 'semantic'
                                ? 'bg-carbon-black text-canvas-white'
                                : 'text-charcoal hover:text-graphite dark:text-gray-400 dark:hover:text-white'
                        ]"
                    >
                        AI-Powered Search
                    </button>
                    <button
                        @click="searchMode = 'standard'"
                        :class="[
                            'px-5 py-2 rounded-full text-[13px] font-medium transition-colors',
                            searchMode === 'standard'
                                ? 'bg-carbon-black text-canvas-white'
                                : 'text-charcoal hover:text-graphite dark:text-gray-400 dark:hover:text-white'
                        ]"
                    >
                        Standard Search
                    </button>
                </div>
            </div>

            <div v-if="processingSteps.length > 0 && searchMode === 'semantic'" class="mb-8">
                <div class="bg-ghostly-gray dark:bg-gray-900 rounded-xl p-6 border border-light-steel dark:border-gray-800">
                    <div class="space-y-2">
                        <div v-for="step in processingSteps" :key="step.type" class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                <div v-if="step.status === 'completed'" class="w-5 h-5 bg-magic-green rounded-full flex items-center justify-center">
                                    <svg class="w-3 h-3 text-canvas-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div v-else-if="step.status === 'running' || step.status === 'streaming'" class="w-5 h-5 bg-magic-orange rounded-full flex items-center justify-center">
                                    <div class="w-2 h-2 bg-canvas-white rounded-full animate-pulse"></div>
                                </div>
                                <div v-else class="w-5 h-5 bg-light-steel dark:bg-gray-700 rounded-full"></div>
                            </div>
                            <div class="flex-1">
                                <span class="text-[13px]" :class="{
                                    'text-graphite dark:text-white font-medium': step.status === 'running' || step.status === 'streaming',
                                    'text-charcoal dark:text-gray-400': step.status === 'completed'
                                }">
                                    {{ step.message }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="currentStatus && isSearching" class="mt-3 pt-3 border-t border-light-steel dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-magic-orange" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-[13px] text-charcoal dark:text-gray-400">{{ currentStatus }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="searchMode === 'semantic' && aiResponse" class="mb-10">
                <div class="bg-canvas-white dark:bg-gray-900 rounded-xl p-8 border border-light-steel dark:border-gray-800 shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-9 h-9 bg-magic-orange rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-canvas-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-[17px] font-semibold text-graphite dark:text-white">AI Answer</h3>
                                    <div v-if="conversationId" class="flex items-center gap-2">
                                        <span class="text-[12px] text-subtle-gray" :title="'Conversation: ' + conversationId">
                                            Context preserved
                                        </span>
                                        <button
                                            @click="clearConversation"
                                            class="text-[12px] text-subtle-gray hover:text-graphite underline transition-colors"
                                        >
                                            Clear context
                                        </button>
                                    </div>
                                </div>
                                <div v-if="isSearching" class="flex items-center gap-2">
                                    <div class="flex gap-1">
                                        <div class="w-1.5 h-1.5 bg-magic-orange rounded-full animate-pulse"></div>
                                        <div class="w-1.5 h-1.5 bg-magic-orange rounded-full animate-pulse" style="animation-delay: 150ms"></div>
                                        <div class="w-1.5 h-1.5 bg-magic-orange rounded-full animate-pulse" style="animation-delay: 300ms"></div>
                                    </div>
                                    <span class="text-[12px] text-subtle-gray">Streaming…</span>
                                </div>
                            </div>
                            <div
                                v-html="renderMarkdown(aiResponse)"
                                class="prose prose-neutral dark:prose-invert max-w-none
                                       prose-headings:text-graphite dark:prose-headings:text-white prose-headings:font-semibold
                                       prose-p:text-charcoal dark:prose-p:text-gray-300
                                       prose-code:bg-ghostly-gray dark:prose-code:bg-gray-800 prose-code:text-graphite dark:prose-code:text-gray-200 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:before:hidden prose-code:after:hidden
                                       prose-pre:bg-graphite dark:prose-pre:bg-black prose-pre:border prose-pre:border-light-steel dark:prose-pre:border-gray-800
                                       prose-a:text-magic-orange hover:prose-a:text-graphite"
                            />

                            <div v-if="aiSources.length > 0" class="mt-6 pt-6 border-t border-light-steel dark:border-gray-800">
                                <h4 class="text-[13px] uppercase tracking-wider font-semibold text-subtle-gray mb-3">Sources</h4>
                                <div class="space-y-2">
                                    <div v-for="source in aiSources" :key="source.index" class="flex items-start gap-3">
                                        <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 bg-ghostly-gray dark:bg-gray-800 text-[12px] font-medium text-charcoal dark:text-gray-400 rounded-full">
                                            {{ source.index }}
                                        </span>
                                        <Link
                                            :href="source.url"
                                            class="flex-1 text-[14px] text-charcoal hover:text-magic-orange dark:text-gray-400 dark:hover:text-magic-orange transition-colors"
                                        >
                                            <span class="font-medium">{{ source.title }}</span>
                                            <span class="text-[12px] text-subtle-gray ml-2">({{ source.version }})</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="searchMode === 'semantic' && searchDocuments.length > 0" class="mb-10">
                <h3 class="text-[17px] font-semibold text-graphite dark:text-white mb-4">Documents used for answer</h3>
                <div class="grid gap-3">
                    <div
                        v-for="(doc, index) in searchDocuments"
                        :key="index"
                        class="bg-canvas-white dark:bg-gray-900 rounded-xl border border-light-steel dark:border-gray-800 p-6 transition-shadow hover:shadow-xl"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-ghostly-gray dark:bg-gray-800 text-[12px] font-semibold text-graphite dark:text-white rounded-full">
                                        {{ index + 1 }}
                                    </span>
                                    <Link :href="doc.url" class="text-[14px] font-semibold text-graphite dark:text-white hover:text-magic-orange transition-colors">
                                        {{ doc.title }}
                                    </Link>
                                    <span class="text-[11px] px-2 py-0.5 bg-ghostly-gray dark:bg-gray-800 text-charcoal dark:text-gray-400 rounded-full">
                                        {{ doc.version }}
                                    </span>
                                    <span v-if="doc.section" class="text-[12px] text-subtle-gray">
                                        • {{ doc.section }}
                                    </span>
                                </div>
                                <p v-if="doc.description" class="text-[14px] text-charcoal dark:text-gray-400 mt-1">
                                    {{ doc.description }}
                                </p>
                            </div>
                            <div v-if="doc.score" class="flex-shrink-0">
                                <span class="text-[12px] text-subtle-gray">Score {{ doc.score?.toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="searchResults.length > 0 && searchMode === 'standard'" class="space-y-4">
                <h3 class="text-[17px] font-semibold text-graphite dark:text-white mb-4">Search results</h3>

                <div
                    v-for="result in searchResults"
                    :key="result._id"
                    class="bg-canvas-white dark:bg-gray-900 rounded-xl border border-light-steel dark:border-gray-800 p-8 transition-shadow hover:shadow-xl"
                >
                    <Link :href="result.url" class="block group">
                        <div class="flex items-start justify-between gap-4 mb-2">
                            <h4 class="text-[17px] font-semibold text-graphite dark:text-white group-hover:text-magic-orange transition-colors">
                                {{ result.title }}
                            </h4>
                            <span class="text-[11px] px-2 py-0.5 bg-ghostly-gray dark:bg-gray-800 text-charcoal dark:text-gray-400 rounded-full flex-shrink-0">
                                {{ result.version }}
                            </span>
                        </div>
                        <p class="text-[14px] text-charcoal dark:text-gray-400 mb-3 leading-relaxed">{{ result.description }}</p>
                        <div class="flex items-center gap-4 text-[13px]">
                            <span class="text-subtle-gray">{{ result.section }}</span>
                            <span class="text-magic-orange group-hover:underline">Read more →</span>
                        </div>
                    </Link>
                </div>
            </div>

            <div v-else-if="!isSearching && searchQuery && hasSearched" class="text-center py-20">
                <svg class="mx-auto h-12 w-12 text-subtle-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-4 text-[17px] font-semibold text-graphite dark:text-white">No results found</h3>
                <p class="mt-1 text-[14px] text-subtle-gray">Try adjusting your search terms or switching search modes</p>
            </div>

            <div v-else-if="!searchQuery" class="text-center py-20">
                <div class="max-w-md mx-auto">
                    <h2 class="text-[40px] leading-[1.2] font-semibold tracking-tight text-graphite dark:text-white">
                        Search the docs
                    </h2>
                    <p class="mt-4 text-[15px] text-charcoal dark:text-gray-400 leading-relaxed">
                        Get instant answers from AI or browse the full documentation with standard search.
                    </p>

                    <div class="mt-10 space-y-3">
                        <p class="text-[12px] text-subtle-gray uppercase tracking-wider font-semibold">Popular topics</p>
                        <div class="flex flex-wrap gap-2 justify-center">
                            <button
                                v-for="topic in suggestedTopics"
                                :key="topic"
                                @click="searchQuery = topic; performSearch()"
                                class="px-4 py-1.5 bg-ghostly-gray dark:bg-gray-900 border border-light-steel dark:border-gray-800 text-charcoal dark:text-gray-300 rounded-full text-[13px] hover:bg-fog dark:hover:bg-gray-800 transition-colors"
                            >
                                {{ topic }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { marked } from 'marked'
import DOMPurify from 'dompurify'
import debounce from 'lodash/debounce.js'
import Footer from '../components/Footer.vue'

defineProps({
    title: String,
    description: String,
    href: String,
    card: String,
});

const searchQuery = ref('')
const searchResults = ref([])
const aiResponse = ref('')
const aiSources = ref([])
const searchDocuments = ref([])
const isSearching = ref(false)
const hasSearched = ref(false)
const searchMode = ref('semantic')
const currentStatus = ref('')
const processingSteps = ref([])
const conversationId = ref(null)

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

marked.setOptions({ breaks: true, gfm: true })

const renderMarkdown = (content) => {
    if (!content) return ''
    return DOMPurify.sanitize(marked(content))
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

    try {
        if (searchMode.value === 'semantic') {
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

            const processStream = async () => {
                while (true) {
                    const { done, value } = await reader.read()
                    if (done) break

                    buffer += decoder.decode(value, { stream: true })

                    let newlineIndex
                    while ((newlineIndex = buffer.indexOf('\n')) !== -1) {
                        const line = buffer.slice(0, newlineIndex)
                        buffer = buffer.slice(newlineIndex + 1)

                        if (line.startsWith('data: ')) {
                            try {
                                const data = JSON.parse(line.slice(6))

                                switch (data.type) {
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
                                        processingSteps.value.push({ type: 'conversation', status: 'completed', message: 'New conversation started' })
                                        break
                                    case 'conversation.reused':
                                        conversationId.value = data.conversation_id
                                        processingSteps.value.push({ type: 'conversation', status: 'completed', message: 'Using existing conversation context' })
                                        break
                                    case 'stream.start':
                                        aiSources.value = data.sources || []
                                        searchDocuments.value = data.documents || []
                                        searchResults.value = data.documents || []
                                        if (data.conversation_id) conversationId.value = data.conversation_id
                                        break
                                    case 'content.delta':
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
                                // ignore
                            }
                        }
                    }
                }
            }

            await processStream()
        } else {
            const response = await fetch('/api/search/standard', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ query: searchQuery.value })
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
            aiResponse.value = ''
            aiSources.value = []
            searchDocuments.value = []
            processingSteps.value = []
        }
    } catch (error) {
        console.error('Failed to clear conversation:', error)
    }
}

const debouncedSearch = debounce(() => {
    if (searchQuery.value.trim()) performSearch()
}, 500)

watch(searchMode, () => {
    if (searchQuery.value.trim()) performSearch()
})
</script>
