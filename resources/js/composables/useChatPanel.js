import { ref, computed, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Shared by the topbar "Ask AI" button, the launcher, AppLayout and
// ChatWidget. Module-level refs survive Inertia page visits.
//
//   enabled   server switch (agentChat.enabled), off by default
//   open      the panel is showing; remembered in localStorage
//   loaded    the panel was opened once, so its chunk is loaded and it
//             stays mounted (closing hides it, the conversation stays)
//
// Only the client changes these, and only after mount, so the server and
// the first client render agree: closed, nothing loaded.
const open = ref(false);
const loaded = ref(false);
const focusRequested = ref(false);

const STORAGE_KEY = 'sigmie-chat-open';
let opener = null;

function persist() {
    try {
        window.localStorage.setItem(STORAGE_KEY, open.value ? '1' : '0');
    } catch {}
}

function show() {
    open.value = true;
    loaded.value = true;
}

// Called from AppLayout's onMounted: reopens a panel the visitor left open.
function restore(enabled) {
    let remembered = false;
    try {
        remembered = window.localStorage.getItem(STORAGE_KEY) === '1';
    } catch {}

    if (enabled && remembered) show();
}

function openChat() {
    opener = document.activeElement;
    show();
    focusRequested.value = true;
    persist();
}

function closeChat() {
    open.value = false;
    persist();

    // The launcher re-renders on close, so focus lands after the next tick.
    nextTick(() => {
        const target = opener?.isConnected ? opener : document.querySelector('[data-chat-launcher]');
        target?.focus();
        opener = null;
    });
}

export function useChatPanel() {
    const page = usePage();
    const enabled = computed(() => !! page.props.agentChat?.enabled);
    // Whether the panel occupies its own column (and content reserves space).
    const showColumn = computed(() => enabled.value && open.value);

    return { enabled, open, loaded, focusRequested, showColumn, restore, openChat, closeChat };
}
