<script setup>
import { computed, onUnmounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import CodePreview from './CodePreview.vue';
import { HERO_EXAMPLES } from '../data/landingExamples';

const activeId = ref(HERO_EXAMPLES[0].id);
const compareJson = ref(false);
const copied = ref(false);
let copyTimer;

const active = computed(() => HERO_EXAMPLES.find((example) => example.id === activeId.value) || HERO_EXAMPLES[0]);
const phpLines = computed(() => active.value.phpCode.split('\n').length);
const jsonLines = computed(() => (active.value.rawJsonCode || '').split('\n').length);
const showCompare = computed(() => active.value.id === 'search' && compareJson.value && !!active.value.rawJsonCode);

const selectTab = (id) => {
    activeId.value = id;
    if (id !== 'search') compareJson.value = false;
    copied.value = false;
};

const copyActiveCode = async () => {
    try {
        await navigator.clipboard.writeText(active.value.phpCode);
        copied.value = true;
        clearTimeout(copyTimer);
        copyTimer = setTimeout(() => {
            copied.value = false;
        }, 1600);
    } catch {
        copied.value = false;
    }
};

onUnmounted(() => clearTimeout(copyTimer));
</script>

<template>
    <div
        id="examples"
        class="scroll-mt-24 overflow-hidden rounded-2xl border border-black/10 bg-[#0b0d11] text-[#e4e4e7] shadow-[0_32px_64px_-24px_rgba(0,0,0,0.28)] dark:border-white/[0.09]"
    >
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/[0.08] bg-[#111318] px-4 py-2.5 sm:px-5">
            <div class="flex min-w-0 items-center gap-4">
                <div class="hidden items-center gap-1.5 sm:flex" aria-hidden="true">
                    <span class="h-2.5 w-2.5 rounded-full bg-white/15"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-white/15"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-white/15"></span>
                </div>
                <div class="flex min-w-0 items-center gap-1 overflow-x-auto" role="tablist" aria-label="Code examples">
                    <button
                        v-for="example in HERO_EXAMPLES"
                        :key="example.id"
                        type="button"
                        role="tab"
                        :aria-selected="example.id === activeId"
                        class="shrink-0 rounded-full px-3 py-1.5 text-[13px] font-medium transition-colors"
                        :class="example.id === activeId
                            ? 'bg-white/[0.11] text-white'
                            : 'text-[#8e8e93] hover:text-[#e4e4e7]'"
                        @click="selectTab(example.id)"
                    >
                        {{ example.label }}
                    </button>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <div
                    v-if="active.id === 'search'"
                    class="inline-flex rounded-full border border-white/10 bg-black/40 p-0.5"
                    role="group"
                    aria-label="Example format"
                >
                    <button
                        type="button"
                        class="rounded-full px-2.5 py-1 text-[12px] font-medium transition-colors"
                        :class="!compareJson ? 'bg-white text-graphite' : 'text-[#a1a1aa] hover:text-white'"
                        :aria-pressed="!compareJson"
                        @click="compareJson = false"
                    >
                        Sigmie PHP
                    </button>
                    <button
                        type="button"
                        class="rounded-full px-2.5 py-1 text-[12px] font-medium transition-colors"
                        :class="compareJson ? 'bg-magic-orange text-white' : 'text-[#a1a1aa] hover:text-white'"
                        :aria-pressed="compareJson"
                        @click="compareJson = true"
                    >
                        vs. Raw ES JSON
                    </button>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.04] px-2.5 py-1 font-mono text-[11px] text-[#a1a1aa] transition-colors hover:border-white/20 hover:text-white"
                    :class="copied ? 'border-magic-green/40 text-[#63df9a]' : ''"
                    @click="copyActiveCode"
                >
                    <svg v-if="copied" class="h-3 w-3 text-[#63df9a]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg v-else class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    {{ copied ? 'Copied' : 'Copy' }}
                </button>
            </div>
        </div>

        <div v-if="showCompare" class="grid divide-y divide-white/[0.08] lg:grid-cols-12 lg:divide-x lg:divide-y-0">
            <div class="min-w-0 lg:col-span-6">
                <div class="flex items-center justify-between gap-2 border-b border-white/[0.06] bg-white/[0.02] px-4 py-2 sm:px-5">
                    <span class="truncate font-mono text-[12px] text-[#e4e4e7]">{{ active.filename }}</span>
                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-magic-green/15 px-2.5 py-0.5 font-mono text-[11px] text-[#63df9a]">
                        {{ phpLines }} lines · fluent PHP
                    </span>
                </div>
                <CodePreview
                    :code="active.phpCode"
                    :filename="active.filename"
                    language="php"
                    :frameless="true"
                />
            </div>

            <div class="min-w-0 lg:col-span-6">
                <div class="flex items-center justify-between gap-2 border-b border-white/[0.06] bg-white/[0.02] px-4 py-2 sm:px-5">
                    <span class="truncate font-mono text-[12px] text-[#a1a1aa]">{{ active.jsonFilename }}</span>
                    <span class="shrink-0 rounded-full bg-white/[0.06] px-2.5 py-0.5 font-mono text-[11px] text-[#a1a1aa]">
                        {{ jsonLines }} lines · raw ES DSL
                    </span>
                </div>
                <CodePreview
                    :code="active.rawJsonCode"
                    :filename="active.jsonFilename"
                    language="json"
                    :frameless="true"
                    max-height-class="max-h-[340px] overflow-y-auto"
                />
            </div>
        </div>

        <div v-else>
            <CodePreview
                :code="active.phpCode"
                :filename="active.filename"
                language="php"
                :frameless="true"
            />
        </div>

        <div class="flex flex-col gap-2 border-t border-white/[0.08] bg-[#111318] px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[13px] text-[#a1a1aa]">
                {{ active.summary }}
            </p>
            <Link
                :href="active.docHref"
                class="group inline-flex shrink-0 items-center gap-1.5 text-[13px] font-medium text-[#ff7b47] transition-colors hover:text-white"
            >
                <span>Read the {{ active.docLabel }} guide</span>
                <span class="transition-transform duration-150 group-hover:translate-x-0.5" aria-hidden="true">→</span>
            </Link>
        </div>
    </div>
</template>
