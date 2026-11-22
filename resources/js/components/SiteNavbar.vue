<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Logo from './Logo.vue';
import SearchDialog from './SearchDialog.vue';
import DocsNav from './DocsNav.vue';
import { useTheme } from '../composables/useTheme';
import { useChatPanel } from '../composables/useChatPanel';

// Docs pages pass their page list; phones get it in the menu, since the
// docs sidebar shows from lg up.
const props = defineProps({
    navigation: {
        type: Array,
        default: null,
    },
});

const page = usePage();
const menuBreakpoint = computed(() => (props.navigation ? 'lg:hidden' : 'md:hidden'));

const { toggleTheme } = useTheme();
const { enabled, showColumn, openChat } = useChatPanel();

const menuOpen = ref(false);
const searchOpen = ref(false);

const closeMenu = () => {
    menuOpen.value = false;
};

const openSearchModal = () => {
    menuOpen.value = false;
    searchOpen.value = true;
};

const onKeyDown = (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
        event.preventDefault();
        searchOpen.value ? (searchOpen.value = false) : openSearchModal();
        return;
    }

    if (event.key === 'Escape' && menuOpen.value) closeMenu();
};

onMounted(() => window.addEventListener('keydown', onKeyDown));
onUnmounted(() => window.removeEventListener('keydown', onKeyDown));

const headerClass = computed(() => ({
    'xl:right-[400px]': showColumn.value,
}));
</script>

<template>
    <header
        class="fixed top-0 left-0 right-0 z-40 h-16 border-b border-[#eaeaea] bg-white/85 backdrop-blur-xl dark:border-white/[0.1] dark:bg-black/85"
        :class="headerClass"
    >
        <div class="mx-auto flex h-full w-full max-w-site items-center justify-between gap-4 px-6 sm:px-10">
            <Link href="/" aria-label="Sigmie home" class="shrink-0" @click="closeMenu">
                <Logo :height="24" />
            </Link>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="press hidden items-center gap-2.5 rounded-full border border-[#e5e5e5] bg-white py-1.5 pr-2 pl-3.5 text-[13px] text-[#737373] hover:border-[#171717]/40 hover:text-[#171717] md:inline-flex dark:border-white/[0.14] dark:bg-[#0a0a0a] dark:text-[#8b8b93] dark:hover:text-white"
                    @click="openSearchModal"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span>Search docs</span>
                    <kbd class="rounded-md border border-[#eaeaea] bg-[#fafafa] px-1.5 py-0.5 font-mono text-[10.5px] text-[#525252] dark:border-white/[0.12] dark:bg-white/[0.04] dark:text-[#a1a1aa]">⌘K</kbd>
                </button>

                <button
                    v-if="enabled"
                    type="button"
                    class="press rounded-full border border-[#e5e5e5] bg-white px-3.5 py-1.5 text-[13px] font-medium text-[#404040] hover:border-[#171717]/40 hover:text-[#171717] dark:border-white/[0.14] dark:bg-[#0a0a0a] dark:text-[#d4d4d8] dark:hover:text-white"
                    @click="openChat"
                >
                    Ask AI
                </button>

                <button
                    type="button"
                    class="hidden h-9 w-9 items-center justify-center rounded-full transition-colors text-[#525252] hover:bg-[#f4f4f5] hover:text-[#171717] md:inline-flex dark:hidden"
                    title="Switch to dark mode"
                    aria-label="Switch to dark mode"
                    @click="toggleTheme"
                >
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>
                <button
                    type="button"
                    class="hidden h-9 w-9 items-center justify-center rounded-full text-[#a1a1aa] transition-colors hover:bg-white/[0.06] hover:text-white md:dark:inline-flex"
                    title="Switch to light mode"
                    aria-label="Switch to light mode"
                    @click="toggleTheme"
                >
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </button>

                <a
                    href="https://github.com/sigmie/sigmie"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="Sigmie on GitHub"
                    class="hidden h-9 w-9 items-center justify-center rounded-full transition-colors text-[#525252] hover:bg-[#f4f4f5] hover:text-[#171717] md:inline-flex dark:text-[#a1a1aa] dark:hover:bg-white/[0.06] dark:hover:text-white"
                >
                    <svg class="h-[18px] w-[18px]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                    </svg>
                </a>

                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-full transition-colors text-[#525252] hover:bg-[#f4f4f5] hover:text-[#171717] md:hidden dark:text-[#a1a1aa] dark:hover:bg-white/[0.06] dark:hover:text-white"
                    aria-label="Search docs"
                    @click="openSearchModal"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>

                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-full transition-colors text-[#525252] hover:bg-[#f4f4f5] hover:text-[#171717] dark:text-[#a1a1aa] dark:hover:bg-white/[0.06] dark:hover:text-white"
                    :class="menuBreakpoint"
                    :aria-expanded="menuOpen"
                    :aria-label="menuOpen ? 'Close menu' : 'Open menu'"
                    @click="menuOpen = !menuOpen"
                >
                    <svg v-if="!menuOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <div
        v-if="menuOpen"
        class="fixed inset-x-0 top-16 z-40 max-h-[calc(100dvh-4rem)] overflow-y-auto overscroll-contain border-b border-[#eaeaea] bg-white dark:border-white/[0.1] dark:bg-black"
        :class="[menuBreakpoint, { 'xl:right-[400px]': showColumn }]"
    >
        <nav class="mx-auto max-w-site px-6 pt-4 pb-8 sm:px-10" aria-label="Mobile">
            <DocsNav v-if="navigation" class="mb-6" :navigation="navigation" :current-path="page.url" @navigate="closeMenu" />

            <div class="divide-y divide-[#eaeaea] border-y border-[#eaeaea] dark:divide-white/[0.1] dark:border-white/[0.1]">
                <a
                    href="https://github.com/sigmie/sigmie"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center justify-between py-3 text-[15px] font-medium text-[#171717] dark:text-[#fafafa]"
                    @click="closeMenu"
                >
                    GitHub
                    <svg class="h-4 w-4 text-[#a1a1aa]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17 17 7M8 7h9v9" />
                    </svg>
                </a>
                <button
                    type="button"
                    class="flex w-full items-center justify-between py-3 text-left text-[15px] font-medium text-[#171717] dark:text-[#fafafa]"
                    @click="toggleTheme"
                >
                    <span class="dark:hidden">Switch to dark mode</span>
                    <span class="hidden dark:inline">Switch to light mode</span>
                    <svg class="h-4 w-4 text-[#a1a1aa] dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <svg class="hidden h-4 w-4 text-[#71717a] dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </button>
            </div>
        </nav>
    </div>

    <SearchDialog v-model:open="searchOpen" />
</template>
