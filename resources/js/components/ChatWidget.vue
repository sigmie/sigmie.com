<script setup>
import { ref, computed, nextTick, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Chat } from '@ai-sdk/vue';
import { DefaultChatTransport } from 'ai';
import { Marked } from 'marked';
import { markedHighlight } from 'marked-highlight';
import hljs from 'highlight.js/lib/common';
import 'highlight.js/styles/github-dark.css';
import DOMPurify from 'dompurify';
import { useChatPanel } from '../composables/useChatPanel';

const md = new Marked(
    markedHighlight({
        emptyLangClass: 'hljs',
        langPrefix: 'hljs language-',
        highlight(code, lang) {
            const language = hljs.getLanguage(lang) ? lang : 'plaintext';
            return hljs.highlight(code, { language }).value;
        },
    })
);

// Opt-in panel state shared with the topbar "Ask AI" button + layouts.
const { enabled, open, focusSignal, hydrate, openChat, closeChat } = useChatPanel();
const input = ref('');
const inputRef = ref(null);
const scrollRef = ref(null);
const limitReached = ref(false);

const page = usePage();
hydrate(page.props.agentChat?.enabled);
onMounted(() => hydrate(page.props.agentChat?.enabled));

const chat = new Chat({
    transport: new DefaultChatTransport({
        api: '/api/agent/chat',
    }),
    onError: (err) => {
        if (typeof err?.message === 'string' && err.message.includes('429')) {
            limitReached.value = true;
        }
    },
});

const messages = computed(() => chat.messages);
const status = computed(() => chat.status);
const error = computed(() => chat.error);

const isStreaming = computed(() => status.value === 'streaming' || status.value === 'submitted');

const renderedMessages = computed(() =>
    messages.value.map((m) => {
        const text = (m.parts ?? [])
            .filter((p) => p.type === 'text')
            .map((p) => p.text)
            .join('');

        return {
            id: m.id,
            role: m.role,
            html: m.role === 'assistant' ? DOMPurify.sanitize(md.parse(text)) : null,
            text,
        };
    })
);

// True while the request is in flight and no assistant text has arrived yet.
const isThinking = computed(() => {
    if (! isStreaming.value) return false;
    const last = renderedMessages.value.at(-1);
    return ! last || last.role === 'user' || last.text.trim() === '';
});

function focusInput() {
    nextTick(() => inputRef.value?.focus());
}

function submit() {
    const text = input.value.trim();
    if (text === '' || isStreaming.value || limitReached.value) return;
    chat.sendMessage({ text });
    input.value = '';
    focusInput();
}

async function clearConversation() {
    if (isStreaming.value) return;
    chat.messages = [];
    limitReached.value = false;
    await fetch('/api/agent/clear', { method: 'POST', headers: { 'Content-Type': 'application/json' } }).catch(() => {});
    focusInput();
}

function scrollToBottom() {
    if (scrollRef.value) {
        scrollRef.value.scrollTop = scrollRef.value.scrollHeight;
    }
}

watch(messages, () => nextTick(scrollToBottom), { deep: true });

// Refocus the input once a response finishes so the user can keep typing.
watch(isStreaming, (streaming, was) => {
    if (was && ! streaming) focusInput();
});

// Topbar "Ask AI" button bumps focusSignal → open (handled by composable) + focus.
watch(focusSignal, () => focusInput());
</script>

<template>
    <template v-if="enabled">
    <!-- Launcher (shown whenever the chat is closed) -->
    <button
        v-if="!open"
        @click="openChat"
        class="fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-full bg-graphite px-5 py-3 text-sm font-medium text-white shadow-lg transition hover:bg-black dark:bg-white dark:text-graphite dark:hover:bg-gray-100"
        aria-label="Open documentation chat"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72A8.97 8.97 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        Ask AI
    </button>

    <!-- Backdrop (tablet / mobile only, when open) -->
    <div
        v-if="open"
        @click="closeChat"
        class="xl:hidden fixed inset-0 z-40 bg-graphite/50"
    />

    <!-- Chat column: reserves space on xl+ when open, slide-over below -->
    <aside
        class="fixed top-0 right-0 z-50 flex h-screen w-full max-w-[420px] flex-col border-l border-light-steel bg-canvas-white transition-transform duration-300 dark:border-gray-800 dark:bg-black xl:z-30 xl:w-[400px]"
        :class="open ? 'translate-x-0' : 'translate-x-full'"
    >
        <header class="flex items-center justify-between border-b border-light-steel px-5 py-4 dark:border-gray-800">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-graphite dark:text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72A8.97 8.97 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <div>
                    <h2 class="text-sm font-semibold text-graphite dark:text-white">Ask Sigmie</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Answers from the docs</p>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <button
                    @click="clearConversation"
                    :disabled="isStreaming || renderedMessages.length === 0"
                    class="flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-gray-500 hover:bg-gray-100 disabled:opacity-40 dark:text-gray-400 dark:hover:bg-gray-800"
                    aria-label="Clear conversation"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Clear
                </button>
                <button
                    @click="closeChat"
                    class="rounded-md p-1 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                    aria-label="Close chat"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </header>

        <div ref="scrollRef" class="flex-1 space-y-4 overflow-y-auto px-5 py-5">
            <div
                v-if="renderedMessages.length === 0"
                class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-300"
            >
                Hi! Ask me anything about Sigmie — installation, search builders, semantic search, indexing patterns, anything from the docs.
            </div>

            <div
                v-for="message in renderedMessages"
                :key="message.id"
                class="flex"
                :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
            >
                <div
                    v-if="message.role === 'user'"
                    class="max-w-[85%] rounded-lg bg-graphite px-3 py-2 text-sm text-white dark:bg-white dark:text-graphite"
                >
                    {{ message.text }}
                </div>

                <div
                    v-else
                    class="chat-markdown prose prose-sm dark:prose-invert max-w-full rounded-lg bg-gray-50 px-3 py-2 text-sm dark:bg-gray-900"
                    v-html="message.html"
                />
            </div>

            <div v-if="isThinking" class="flex justify-start">
                <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                    <span class="flex gap-1">
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.3s]"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.15s]"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400"></span>
                    </span>
                    Thinking…
                </div>
            </div>

            <div v-if="limitReached" class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                You've reached the message limit for now. Please try again later, or read the docs directly.
            </div>

            <div v-else-if="error" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-950 dark:text-red-300">
                Something went wrong. Please try again.
            </div>
        </div>

        <form @submit.prevent="submit" class="border-t border-light-steel p-4 dark:border-gray-800">
            <div class="flex gap-2">
                <input
                    ref="inputRef"
                    v-model="input"
                    type="text"
                    placeholder="Ask a question…"
                    class="flex-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-graphite focus:border-graphite focus:outline-none dark:border-gray-700 dark:bg-graphite dark:text-white"
                    :disabled="limitReached"
                />
                <button
                    type="submit"
                    :disabled="isStreaming || limitReached || input.trim() === ''"
                    class="rounded-md bg-graphite px-4 py-2 text-sm font-medium text-white disabled:opacity-50 dark:bg-white dark:text-graphite"
                >
                    Send
                </button>
            </div>
        </form>
    </aside>
    </template>
</template>

<style>
.chat-markdown pre {
    margin: 0.5rem 0;
    border-radius: 0.5rem;
    overflow-x: auto;
}

.chat-markdown pre code.hljs {
    display: block;
    padding: 0.75rem 1rem;
    font-size: 0.8125rem;
    line-height: 1.5;
    border-radius: 0.5rem;
}

.chat-markdown :not(pre) > code {
    background: rgba(125, 125, 125, 0.15);
    padding: 0.1rem 0.3rem;
    border-radius: 0.25rem;
    font-size: 0.85em;
}
</style>
