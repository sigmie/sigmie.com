import { ref, computed } from 'vue';

// Module-level refs → shared singleton across every importer (Navbar button,
// layouts, ChatWidget). Drives the opt-in chat panel.
//
// `enabled` = server master switch (off by default). `open` = user opt-in,
// remembered in localStorage. The chat only shows when enabled && open.
const enabled = ref(false);
const open = ref(false);
const focusSignal = ref(0);

const STORAGE_KEY = 'sigmie-chat-open';
let hydrated = false;

function hydrate(serverEnabled) {
    enabled.value = !! serverEnabled;

    if (! hydrated && typeof window !== 'undefined') {
        open.value = enabled.value && window.localStorage.getItem(STORAGE_KEY) === '1';
        hydrated = true;
    }
}

function persist() {
    if (typeof window !== 'undefined') {
        window.localStorage.setItem(STORAGE_KEY, open.value ? '1' : '0');
    }
}

function openChat() {
    open.value = true;
    focusSignal.value++;
    persist();
}

function closeChat() {
    open.value = false;
    persist();
}

// Whether the panel occupies its own column (and content reserves space).
const showColumn = computed(() => enabled.value && open.value);

export function useChatPanel() {
    return { enabled, open, focusSignal, showColumn, hydrate, openChat, closeChat };
}
