<script setup>
import { ref, shallowRef, watch, computed, onMounted, nextTick } from "vue";
import { Head } from "@inertiajs/vue3";
import { Chat } from "@ai-sdk/vue";
import { DefaultChatTransport } from "ai";
import { assistantMarkdownToHtml } from "../agentMarkdown";

const props = defineProps({
    csrf: {
        type: String,
        required: true,
    },
    routePrefix: {
        type: String,
        default: "sigmie",
    },
    agentTitle: {
        type: String,
        default: "Agent",
    },
    agentSubtitle: {
        type: String,
        default: "",
    },
});

function withRoutePrefix(path) {
    const p = String(props.routePrefix ?? "")
        .trim()
        .replace(/^\/+|\/+$/g, "");
    return (p ? `/${p}` : "") + path;
}

const MODEL_OPTIONS = [
    {
        id: "claude-haiku-4-5-20251001",
        label: "Claude Haiku 4.5",
        provider: "anthropic",
        model: "claude-haiku-4-5-20251001",
    },
    { id: "claude-sonnet-4-6", label: "Claude Sonnet 4.6", provider: "anthropic", model: "claude-sonnet-4-6" },
    { id: "gpt-4o-mini", label: "GPT-4o mini", provider: "openai", model: "gpt-4o-mini" },
    { id: "gpt-4o", label: "GPT-4o", provider: "openai", model: "gpt-4o" },
];

const input = ref("");
const renderTick = ref(0);
const selectedModelId = ref(MODEL_OPTIONS[0].id);
const selectedOption = computed(
    () => MODEL_OPTIONS.find((o) => o.id === selectedModelId.value) ?? MODEL_OPTIONS[0],
);
const isOpenAiModel = computed(() => selectedOption.value.provider === "openai");

const pendingFiles = ref([]);
const dragOver = ref(false);
const fileInputRef = ref(null);
const inputRef = ref(null);
const messagesEndRef = ref(null);
const conversations = ref([]);
const conversationChoice = ref("__new__");
const hydratingConversation = ref(true);

function redactSecrets(s) {
    if (typeof s !== "string" || !s) return s;
    return s
        .replace(/\bsk-[a-zA-Z0-9_-]{8,}\b/g, "[api key]")
        .replace(/\bsk-proj-[a-zA-Z0-9_-]{8,}\b/g, "[api key]")
        .replace(/\bxox[baprs]-[a-zA-Z0-9-]{8,}\b/g, "[token]");
}

function rawErrorString(err) {
    if (err == null) return "";
    if (typeof err === "string") return err;
    if (typeof err.message === "string") return err.message;
    try { return JSON.stringify(err); } catch { return String(err); }
}

function friendlyChatError(err) {
    const raw = redactSecrets(rawErrorString(err));
    let message = raw;
    try {
        const j = JSON.parse(raw);
        const inner = j?.error?.message || j?.message || (typeof j?.error === "string" ? j.error : null);
        if (typeof inner === "string" && inner.trim() !== "") {
            message = redactSecrets(inner);
        }
    } catch { /* not JSON */ }

    const lower = message.toLowerCase();
    if (
        lower.includes("status code 401") ||
        (/\b401\b/.test(message) && /http|request|unauthorized|forbidden/i.test(message))
    ) {
        const providerName = selectedOption.value?.provider ?? "unknown";
        const keyEnv = providerName === "anthropic" ? "ANTHROPIC_API_KEY" : "OPENAI_API_KEY";
        return {
            headline: `The chat request was rejected (401). Check ${keyEnv} in \`.env\` and run \`php artisan config:clear\`.`,
            hint: "Try another model in the Model dropdown if that provider is configured.",
            technical: message.length > 1800 ? `${message.slice(0, 1800)}…` : message,
        };
    }
    if (
        lower.includes("incorrect api key") ||
        lower.includes("invalid_api_key") ||
        lower.includes("invalid api key")
    ) {
        return {
            headline: "Invalid or missing API key. Check OPENAI_API_KEY / ANTHROPIC_API_KEY on the server.",
            hint: "Fix keys in `.env`, run `php artisan config:clear`, then retry.",
            technical: message.length > 1800 ? `${message.slice(0, 1800)}…` : message,
        };
    }
    if (lower.includes("rate limit") || lower.includes("429")) {
        return {
            headline: "Rate limit hit. Wait a moment and try again.",
            technical: message.length > 1800 ? `${message.slice(0, 1800)}…` : message,
        };
    }
    const short = message.length > 320 ? `${message.slice(0, 317)}…` : message;
    return {
        headline: short || "Something went wrong while contacting the AI provider.",
        technical: message.length > 1800 ? `${message.slice(0, 1800)}…` : message,
    };
}

function readXsrfCookie() {
    const parts = document.cookie.split("; ");
    for (const p of parts) {
        const i = p.indexOf("=");
        if (i === -1) continue;
        if (p.slice(0, i) === "XSRF-TOKEN") {
            return decodeURIComponent(p.slice(i + 1));
        }
    }
    return null;
}

function csrfHeader() {
    const xsrf = readXsrfCookie();
    if (xsrf) return { "X-XSRF-TOKEN": xsrf };
    return { "X-CSRF-TOKEN": props.csrf };
}

function jsonHeaders() {
    return {
        Accept: "application/json",
        "Content-Type": "application/json",
        ...csrfHeader(),
    };
}

function fetchWithTimeout(url, opts = {}, timeoutMs = 10_000) {
    const controller = new AbortController();
    const id = setTimeout(() => controller.abort(), timeoutMs);
    return fetch(url, { ...opts, signal: controller.signal }).finally(() => clearTimeout(id));
}

function buildTransport() {
    return new DefaultChatTransport({
        api: withRoutePrefix("/api/agent-chat"),
        credentials: "same-origin",
        headers: () => ({ ...csrfHeader() }),
        body: () => {
            const opt = selectedOption.value;
            const cid = conversationChoice.value;
            const payload = { provider: opt.provider, model: opt.model };
            if (typeof cid === "string" && cid !== "" && cid !== "__new__") {
                payload.conversation_id = cid;
            }
            return payload;
        },
    });
}

async function ensureActiveConversationSynced(result) {
    const fromApi = result.active_conversation_id;
    if (typeof fromApi === "string" && fromApi !== "") return fromApi;
    const first = result.data?.[0];
    if (!first?.id) return null;
    await setActiveConversationOnServer(first.id);
    conversationChoice.value = first.id;
    return first.id;
}

function createChat(initialMessages = []) {
    return new Chat({
        transport: buildTransport(),
        messages: initialMessages,
        onFinish: async () => {
            try {
                let result = await loadConversations();
                let active = await ensureActiveConversationSynced(result);
                if (!active && result.data?.length) {
                    await new Promise((r) => setTimeout(r, 120));
                    result = await loadConversations();
                    await ensureActiveConversationSynced(result);
                }
            } catch {
                /* don't let post-stream bookkeeping break the UI */
            }
            scrollToBottom();
            focusInput();
        },
        onError: () => {
            scrollToBottom();
            focusInput();
        },
    });
}

const chat = shallowRef(createChat());

const errorBannerDismissed = ref(false);
watch(
    () => chat.value?.error,
    () => { errorBannerDismissed.value = false; },
);

function dismissErrorBanner() {
    errorBannerDismissed.value = true;
}

const chatErrorDisplay = computed(() => {
    const e = chat.value?.error;
    return e ? friendlyChatError(e) : null;
});

async function loadConversations() {
    const empty = { active_conversation_id: null, data: [] };
    try {
        const r = await fetchWithTimeout(withRoutePrefix("/api/agent-conversations"), {
            credentials: "same-origin",
            headers: { Accept: "application/json", ...csrfHeader() },
        });
        if (!r.ok) return empty;
        const json = await r.json();
        conversations.value = Array.isArray(json.data) ? json.data : [];
        const activeId =
            typeof json.active_conversation_id === "string" && json.active_conversation_id !== ""
                ? json.active_conversation_id
                : null;
        if (activeId) conversationChoice.value = activeId;
        return { active_conversation_id: activeId, data: conversations.value };
    } catch { return empty; }
}

async function loadConversationMessages(conversationId) {
    const r = await fetchWithTimeout(
        withRoutePrefix(`/api/agent-conversations/${encodeURIComponent(conversationId)}/messages`),
        { credentials: "same-origin", headers: { Accept: "application/json", ...csrfHeader() } },
    );
    if (!r.ok) throw new Error(`Failed to load messages (${r.status})`);
    const json = await r.json();
    return Array.isArray(json.messages) ? json.messages : [];
}

async function setActiveConversationOnServer(conversationId) {
    await fetchWithTimeout(withRoutePrefix("/api/agent-conversations/active"), {
        method: "POST",
        credentials: "same-origin",
        headers: jsonHeaders(),
        body: JSON.stringify({ conversation_id: conversationId === "__new__" ? null : conversationId }),
    });
}

async function applyConversationChoice(id) {
    if (id === "__new__") {
        /* Fire-and-forget: don't block the UI if the server is slow. */
        fetchWithTimeout(withRoutePrefix("/api/agent-reset"), {
            method: "POST",
            headers: { Accept: "application/json", ...csrfHeader() },
            credentials: "same-origin",
        }).catch(() => {});
        setActiveConversationOnServer(null).catch(() => {});
        chat.value = createChat([]);
        conversationChoice.value = "__new__";
        renderTick.value++;
        return;
    }

    await setActiveConversationOnServer(id);
    const messages = await loadConversationMessages(id);
    chat.value = createChat(messages);
    conversationChoice.value = id;
    renderTick.value++;
    await nextTick();
    scrollToBottom();
}

async function onConversationSelect(event) {
    const id = event.target.value;
    if (hydratingConversation.value) return;
    try {
        await applyConversationChoice(id);
    } catch (e) {
        console.error(e);
        chat.value = createChat([]);
        conversationChoice.value = "__new__";
        renderTick.value++;
    }
}

onMounted(async () => {
    try {
        const { active_conversation_id: activeId } = await loadConversations();
        if (activeId) {
            const messages = await loadConversationMessages(activeId);
            chat.value = createChat(messages);
            renderTick.value++;
        }
    } catch { /* keep default empty chat */ }
    await nextTick();
    hydratingConversation.value = false;
    scrollToBottom();
    focusInput();
});

watch(
    () => chat.value?.messages,
    () => {
        renderTick.value++;
        nextTick(() => scrollToBottom());
    },
    { deep: true },
);

watch(
    () => chat.value?.status,
    () => { renderTick.value++; },
);

watch(isOpenAiModel, (openAi) => {
    if (!openAi) {
        pendingFiles.value = [];
        dragOver.value = false;
    }
});

watch(conversations, (list) => {
    const ids = new Set(list.map((x) => x.id));
    if (conversationChoice.value !== "__new__" && !ids.has(conversationChoice.value)) {
        conversationChoice.value = "__new__";
    }
});

function scrollToBottom() {
    nextTick(() => {
        messagesEndRef.value?.scrollIntoView({ behavior: "smooth" });
    });
}

function focusInput() {
    nextTick(() => { inputRef.value?.focus?.(); });
}

/* ── Stuck-state recovery ── */
let submittedAt = null;
const STUCK_TIMEOUT_MS = 30_000;

watch(
    () => chat.value?.status,
    (status) => {
        if (status === "submitted") {
            submittedAt = Date.now();
        } else {
            submittedAt = null;
        }
    },
);

setInterval(() => {
    if (submittedAt && Date.now() - submittedAt > STUCK_TIMEOUT_MS) {
        submittedAt = null;
        try { chat.value.stop(); } catch { /* ignore */ }
    }
}, 5_000);

/* ── File handling ── */
const MAX_FILES = 10;
const MAX_FILE_BYTES = 10 * 1024 * 1024;

function filesToFileList(files) {
    const dt = new DataTransfer();
    for (const f of files) dt.items.add(f);
    return dt.files;
}

function addFilesFromList(fileList) {
    if (!isOpenAiModel.value || !fileList?.length) return;
    const incoming = Array.from(fileList).filter((f) => f.size <= MAX_FILE_BYTES);
    const room = MAX_FILES - pendingFiles.value.length;
    pendingFiles.value = [...pendingFiles.value, ...incoming.slice(0, room)];
}

function removePendingFile(index) {
    pendingFiles.value = pendingFiles.value.filter((_, i) => i !== index);
}

function onDragOverChat(e) {
    if (!isOpenAiModel.value) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = "copy";
    dragOver.value = true;
}

function onDragLeaveChat(e) {
    if (!e.currentTarget.contains(e.relatedTarget)) dragOver.value = false;
}

function onDropChat(e) {
    e.preventDefault();
    dragOver.value = false;
    if (!isOpenAiModel.value) return;
    addFilesFromList(e.dataTransfer?.files);
}

function openFilePicker() {
    fileInputRef.value?.click?.();
}

function onFileInputChange(e) {
    addFilesFromList(e.target.files);
    e.target.value = "";
}

function userHasFileParts(message) {
    return Array.isArray(message.parts) && message.parts.some((p) => p?.type === "file");
}

async function resetConversation() {
    await applyConversationChoice("__new__");
    focusInput();
}

const busy = () => chat.value.status === "streaming" || chat.value.status === "submitted";

async function onSubmit(e) {
    e?.preventDefault?.();
    const text = input.value.trim();
    const hasFiles = pendingFiles.value.length > 0;
    if ((!text && !hasFiles) || busy()) return;

    input.value = "";
    if (inputRef.value) inputRef.value.style.height = "auto";

    if (isOpenAiModel.value && hasFiles) {
        const list = filesToFileList(pendingFiles.value);
        pendingFiles.value = [];
        await chat.value.sendMessage(text ? { text, files: list } : { files: list });
        return;
    }
    await chat.value.sendMessage({ text });
}

function onTextareaKeydown(e) {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        onSubmit();
    }
}

function autoResize(e) {
    const el = e.target;
    el.style.height = "auto";
    el.style.height = Math.min(el.scrollHeight, 160) + "px";
}

function messageText(message) {
    if (!Array.isArray(message.parts)) return "";
    return message.parts
        .filter((p) => p?.type === "text" && typeof p.text === "string")
        .map((p) => p.text)
        .join("");
}

function assistantHtml(message) {
    return assistantMarkdownToHtml(messageText(message));
}

function formatShortUpdated(iso) {
    if (typeof iso !== "string" || iso === "") return "";
    try {
        const d = new Date(iso);
        if (Number.isNaN(d.getTime())) return "";
        return new Intl.DateTimeFormat(undefined, { month: "short", day: "numeric" }).format(d);
    } catch { return ""; }
}

function conversationLabel(c) {
    const raw = (c.title || "").trim();
    const title = raw.length > 40 ? `${raw.slice(0, 37)}…` : raw;
    const dateStr = formatShortUpdated(c.updated_at);
    const idHint = c.id ? String(c.id).replace(/-/g, "").slice(0, 6) : "";
    if (title && dateStr) return `${title} · ${dateStr}`;
    if (title) return title;
    return dateStr ? `Chat · ${dateStr}` : `Chat · ${idHint}`;
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-zinc-950 text-zinc-100">
        <Head :title="agentTitle" />

        <div
            class="mx-auto flex w-full max-w-3xl flex-1 flex-col px-4 py-6"
            @dragover="onDragOverChat"
            @dragleave="onDragLeaveChat"
            @drop="onDropChat"
        >
            <!-- Header -->
            <header class="mb-4 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-base font-semibold text-white">{{ agentTitle }}</h1>
                    <p v-if="agentSubtitle" class="text-xs text-zinc-500">{{ agentSubtitle }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <select
                        :key="`conv-${conversations.length}-${conversationChoice}`"
                        :value="conversationChoice"
                        class="select-agent h-8 rounded-lg border border-zinc-700 bg-zinc-900 pl-2 pr-7 text-xs text-zinc-300 focus:border-zinc-500 focus:outline-none"
                        @change="onConversationSelect"
                    >
                        <option value="__new__">New chat</option>
                        <option
                            v-for="c in conversations"
                            :key="c.id"
                            :value="c.id"
                            :title="(c.title || c.id || '').trim() || undefined"
                        >
                            {{ conversationLabel(c) }}
                        </option>
                    </select>
                    <select
                        v-model="selectedModelId"
                        class="select-agent h-8 rounded-lg border border-zinc-700 bg-zinc-900 pl-2 pr-7 text-xs text-zinc-300 focus:border-zinc-500 focus:outline-none"
                    >
                        <option v-for="opt in MODEL_OPTIONS" :key="opt.id" :value="opt.id">
                            {{ opt.label }}
                        </option>
                    </select>
                    <button
                        type="button"
                        class="flex h-8 items-center rounded-lg border border-zinc-700 bg-zinc-900 px-3 text-xs text-zinc-300 hover:bg-zinc-800"
                        @click="resetConversation"
                    >
                        + New
                    </button>
                </div>
            </header>

            <!-- Drop overlay -->
            <div
                v-if="isOpenAiModel && dragOver"
                class="pointer-events-none fixed inset-0 z-20 flex items-center justify-center bg-zinc-950/80 backdrop-blur-sm"
            >
                <p class="text-sm font-medium text-sky-200">Drop files here</p>
            </div>

            <!-- Error banner -->
            <div
                v-if="chat.error && !errorBannerDismissed && chatErrorDisplay"
                class="mb-3 rounded-lg border border-red-900/50 bg-red-950/40 px-4 py-3 text-sm"
                role="alert"
            >
                <div class="flex gap-3">
                    <span class="mt-0.5 shrink-0 text-red-400" aria-hidden="true">!</span>
                    <div class="min-w-0 flex-1 space-y-1.5">
                        <p class="text-sm leading-snug text-red-100">{{ chatErrorDisplay.headline }}</p>
                        <p v-if="chatErrorDisplay.hint" class="text-xs text-red-200/80">{{ chatErrorDisplay.hint }}</p>
                        <details v-if="chatErrorDisplay.technical" class="rounded border border-red-900/40 bg-black/25">
                            <summary class="cursor-pointer px-2 py-1 text-xs text-red-300/80 hover:bg-red-950/50">
                                Technical detail
                            </summary>
                            <pre class="max-h-32 overflow-auto whitespace-pre-wrap break-words px-2 pb-2 font-mono text-[11px] text-red-200/60">{{ redactSecrets(chatErrorDisplay.technical) }}</pre>
                        </details>
                        <button
                            type="button"
                            class="text-xs text-red-300 underline underline-offset-2 hover:text-white"
                            @click="dismissErrorBanner"
                        >
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div
                :key="renderTick"
                class="flex flex-1 flex-col gap-4 overflow-y-auto pb-4"
            >
                <div v-if="!chat.messages.length" class="flex flex-1 items-center justify-center">
                    <p class="text-sm text-zinc-600">Send a message to start a conversation.</p>
                </div>

                <template v-for="message in chat.messages" :key="message.id">
                    <!-- User message -->
                    <div v-if="message.role === 'user'" class="flex justify-end">
                        <div class="max-w-[80%] rounded-2xl rounded-br-md bg-zinc-700 px-4 py-2.5 text-sm leading-relaxed text-white">
                            <div v-if="userHasFileParts(message)" class="space-y-1.5">
                                <template v-for="(part, pi) in message.parts" :key="pi">
                                    <div v-if="part.type === 'text'" class="whitespace-pre-wrap">{{ part.text }}</div>
                                    <div
                                        v-else-if="part.type === 'file'"
                                        class="rounded border border-zinc-500/40 bg-zinc-800/60 px-2 py-1 text-xs text-zinc-300"
                                    >
                                        {{ part.filename || "Attachment" }}
                                    </div>
                                </template>
                            </div>
                            <div v-else class="whitespace-pre-wrap">{{ messageText(message) }}</div>
                        </div>
                    </div>

                    <!-- Assistant message -->
                    <div v-else class="flex justify-start">
                        <div
                            class="max-w-[85%] rounded-2xl rounded-bl-md border border-zinc-800 bg-zinc-900 px-4 py-2.5 text-sm leading-relaxed text-zinc-200"
                        >
                            <div
                                class="prose prose-invert prose-sm max-w-none prose-p:my-1.5 prose-ol:my-1.5 prose-ul:my-1.5 prose-headings:mt-3 prose-headings:mb-1.5 prose-pre:bg-zinc-950 prose-pre:border prose-pre:border-zinc-700"
                                v-html="assistantHtml(message)"
                            />
                        </div>
                    </div>
                </template>

                <!-- Thinking indicator -->
                <div v-if="chat.status === 'submitted'" class="flex justify-start">
                    <div class="flex items-center gap-2 rounded-2xl rounded-bl-md border border-zinc-800 bg-zinc-900 px-4 py-3">
                        <span class="flex gap-1">
                            <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-zinc-400" style="animation-delay: 0ms" />
                            <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-zinc-400" style="animation-delay: 150ms" />
                            <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-zinc-400" style="animation-delay: 300ms" />
                        </span>
                    </div>
                </div>

                <div ref="messagesEndRef" />
            </div>

            <!-- Input area -->
            <div class="sticky bottom-0 border-t border-zinc-800/50 bg-zinc-950 pt-3 pb-2">
                <p v-if="isOpenAiModel" class="mb-2 text-[11px] text-zinc-600">
                    Drag and drop files or use Attach (OpenAI models only).
                </p>
                <div v-if="isOpenAiModel && pendingFiles.length" class="mb-2 flex flex-wrap gap-1.5">
                    <span
                        v-for="(f, fi) in pendingFiles"
                        :key="fi + f.name"
                        class="inline-flex items-center gap-1 rounded-md border border-zinc-700 bg-zinc-800/80 px-2 py-0.5 text-xs text-zinc-300"
                    >
                        {{ f.name }}
                        <button
                            type="button"
                            class="rounded px-0.5 text-zinc-500 hover:text-white"
                            :disabled="busy()"
                            @click="removePendingFile(fi)"
                        >
                            ×
                        </button>
                    </span>
                </div>
                <form class="flex items-end gap-2" @submit="onSubmit">
                    <input
                        ref="fileInputRef"
                        type="file"
                        class="hidden"
                        multiple
                        accept="image/jpeg,image/png,image/gif,image/webp,application/pdf,text/plain,audio/mpeg,audio/wav,.pdf,.txt,.md,.json"
                        :disabled="busy() || !isOpenAiModel"
                        @change="onFileInputChange"
                    >
                    <button
                        v-if="isOpenAiModel"
                        type="button"
                        class="shrink-0 rounded-xl border border-zinc-700 bg-zinc-900 px-3 py-2.5 text-xs text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200 disabled:opacity-40"
                        title="Attach files"
                        :disabled="busy()"
                        @click="openFilePicker"
                    >
                        Attach
                    </button>
                    <textarea
                        ref="inputRef"
                        v-model="input"
                        rows="1"
                        autocomplete="off"
                        :placeholder="isOpenAiModel ? 'Message… (files optional)' : 'Message…'"
                        class="min-w-0 flex-1 resize-none rounded-xl border border-zinc-700 bg-zinc-900 px-4 py-2.5 text-sm text-white placeholder-zinc-500 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500/40"
                        :disabled="chat.status === 'submitted'"
                        @keydown="onTextareaKeydown"
                        @input="autoResize"
                    />
                    <button
                        v-if="busy()"
                        type="button"
                        class="shrink-0 rounded-xl border border-zinc-600 bg-zinc-800 px-4 py-2.5 text-sm text-zinc-300 hover:bg-zinc-700"
                        @click="chat.stop()"
                    >
                        Stop
                    </button>
                    <button
                        v-else
                        type="submit"
                        class="shrink-0 rounded-xl bg-white px-4 py-2.5 text-sm font-medium text-zinc-900 hover:bg-zinc-200 disabled:opacity-40"
                        :disabled="!input.trim() && !pendingFiles.length"
                    >
                        Send
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.select-agent {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%23a1a1aa' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.25rem center;
    background-size: 1.1rem 1.1rem;
}
</style>
