<script setup>
import { computed } from 'vue';

const props = defineProps({
    query: {
        type: String,
        required: true,
    },
    mode: {
        type: String,
        required: true,
    },
    items: {
        type: Array,
        default: () => [],
    },
    activeIndex: {
        type: Number,
        default: 0,
    },
    pulseKey: {
        type: Number,
        default: 0,
    },
    deltas: {
        type: Object,
        default: () => ({}),
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select']);

const CX = 270;
const CY = 136;

const RINGS = [
    { r: 46, label: '#1' },
    { r: 84, label: '#2–3' },
    { r: 118, label: '#4–5' },
];

const SLOTS = [
    { r: 46, deg: -26, side: 'right' },
    { r: 84, deg: -152, side: 'left' },
    { r: 84, deg: 28, side: 'right' },
    { r: 118, deg: 154, side: 'left' },
    { r: 118, deg: -34, side: 'right' },
];

const TICKS = Array.from({ length: 24 }, (_, idx) => {
    const rad = (idx * 15 * Math.PI) / 180;
    const inner = 114;
    const outer = idx % 6 === 0 ? 124 : 120;
    return {
        x1: CX + Math.cos(rad) * inner,
        y1: CY + Math.sin(rad) * inner,
        x2: CX + Math.cos(rad) * outer,
        y2: CY + Math.sin(rad) * outer,
        major: idx % 6 === 0,
    };
});

const shortTitle = (title) => {
    const clean = String(title || '').trim();
    return clean.length > 20 ? `${clean.slice(0, 19)}…` : clean;
};

const nodes = computed(() => (
    props.items.slice(0, 5).map((item, index) => {
        const slot = SLOTS[index] || SLOTS[SLOTS.length - 1];
        const rad = (slot.deg * Math.PI) / 180;
        const x = Math.round((CX + Math.cos(rad) * slot.r) * 10) / 10;
        const y = Math.round((CY + Math.sin(rad) * slot.r) * 10) / 10;
        const delta = props.deltas[item._id] ?? null;

        return {
            id: item._id || `rank-${index}`,
            rank: index + 1,
            index,
            title: item.title || '',
            short: shortTitle(item.title),
            score: item._score,
            x,
            y,
            side: slot.side,
            delta,
            isActive: props.activeIndex === index,
            isTop: index === 0,
        };
    })
));

const formatDelta = (delta) => {
    if (!delta) return '';
    if (delta === 'new') return 'new';
    if (typeof delta === 'number' && delta > 0) return `↑${delta}`;
    if (typeof delta === 'number' && delta < 0) return `↓${Math.abs(delta)}`;
    return '';
};
</script>

<template>
    <div class="relative select-none" :class="loading ? 'opacity-65 transition-opacity duration-200' : ''">
        <svg
            viewBox="0 0 540 272"
            class="block h-auto w-full"
            role="img"
            :aria-label="`Orbital rank bands for ${mode} search query ${query}. Rank 1 is on the inner ring.`"
        >
            <line
                x1="28"
                :y1="CY"
                x2="512"
                :y2="CY"
                class="stroke-graphite/[0.06] dark:stroke-white/[0.06]"
                stroke-width="1"
            />
            <line
                :x1="CX"
                y1="10"
                :x2="CX"
                y2="262"
                class="stroke-graphite/[0.06] dark:stroke-white/[0.06]"
                stroke-width="1"
            />

            <g aria-hidden="true">
                <line
                    v-for="(tick, idx) in TICKS"
                    :key="`t-${idx}`"
                    :x1="tick.x1"
                    :y1="tick.y1"
                    :x2="tick.x2"
                    :y2="tick.y2"
                    :class="tick.major ? 'stroke-graphite/20 dark:stroke-white/20' : 'stroke-graphite/10 dark:stroke-white/10'"
                    stroke-width="1"
                />
            </g>

            <g v-for="(ring, idx) in RINGS" :key="`r-${ring.r}`">
                <circle
                    :cx="CX"
                    :cy="CY"
                    :r="ring.r"
                    fill="none"
                    :stroke-dasharray="idx === 2 ? '3 4' : undefined"
                    :class="idx === 0
                        ? 'stroke-graphite/22 dark:stroke-white/24'
                        : 'stroke-graphite/12 dark:stroke-white/12'"
                    :stroke-width="idx === 0 ? 1.25 : 1"
                />
                <text
                    :x="CX"
                    :y="CY + ring.r + 12"
                    text-anchor="middle"
                    class="fill-muted-ink font-mono text-[9.5px] dark:fill-[#9ca3af]"
                >
                    {{ ring.label }}
                </text>
            </g>

            <g aria-hidden="true">
                <line
                    v-for="node in nodes"
                    :key="`tether-${node.id}`"
                    :x1="CX"
                    :y1="CY"
                    :x2="node.x"
                    :y2="node.y"
                    :class="node.isActive
                        ? 'stroke-magic-orange/75'
                        : node.isTop
                            ? 'stroke-graphite/25 dark:stroke-white/25'
                            : 'stroke-graphite/10 dark:stroke-white/10'"
                    :stroke-width="node.isActive ? 1.4 : 1"
                    :stroke-dasharray="node.isActive ? undefined : '2 3'"
                />
            </g>

            <g :key="`pulse-${pulseKey}`" aria-hidden="true">
                <circle
                    v-if="pulseKey > 0"
                    :cx="CX"
                    :cy="CY"
                    r="24"
                    fill="none"
                    class="orbital-pulse stroke-magic-orange"
                    stroke-width="1.5"
                />
            </g>

            <g aria-hidden="true">
                <circle
                    :cx="CX"
                    :cy="CY"
                    r="16"
                    class="fill-magic-orange/12"
                />
                <circle
                    :cx="CX"
                    :cy="CY"
                    r="9.5"
                    class="fill-magic-orange"
                />
                <circle
                    :cx="CX"
                    :cy="CY"
                    r="3"
                    class="fill-white"
                />
            </g>

            <g
                v-for="node in nodes"
                :key="node.id"
                class="orbital-node cursor-pointer"
                :style="{ transform: `translate(${node.x}px, ${node.y}px)` }"
                @mouseenter="emit('select', node.index)"
                @click="emit('select', node.index)"
            >
                <circle
                    v-if="node.isActive || (typeof node.delta === 'number' && node.delta > 0)"
                    cx="0"
                    cy="0"
                    r="15"
                    fill="none"
                    :class="node.isActive ? 'stroke-magic-orange/55' : 'stroke-magic-orange/35'"
                    stroke-width="1.25"
                />
                <circle
                    cx="0"
                    cy="0"
                    :r="node.isTop || node.isActive ? 10.5 : 9"
                    :class="node.isActive
                        ? 'fill-magic-orange stroke-canvas-white dark:stroke-[#111113]'
                        : node.isTop
                            ? 'fill-graphite stroke-canvas-white dark:fill-[#f5f5f4] dark:stroke-[#111113]'
                            : 'fill-canvas-white stroke-graphite/45 dark:fill-[#161618] dark:stroke-white/40'"
                    stroke-width="1.75"
                />
                <text
                    x="0"
                    y="3.5"
                    text-anchor="middle"
                    class="font-mono text-[10px] font-semibold"
                    :class="node.isActive
                        ? 'fill-white'
                        : node.isTop
                            ? 'fill-white dark:fill-graphite'
                            : 'fill-graphite dark:fill-[#e4e4e7]'"
                >
                    {{ node.rank }}
                </text>

                <text
                    :x="node.side === 'right' ? 16 : -16"
                    y="-1"
                    :text-anchor="node.side === 'right' ? 'start' : 'end'"
                    class="text-[11px] font-medium transition-colors"
                    :class="node.isActive
                        ? 'fill-graphite font-semibold dark:fill-white'
                        : node.isTop
                            ? 'fill-graphite dark:fill-[#f5f5f4]'
                            : 'fill-charcoal/85 dark:fill-[#a3a3a0]'"
                >
                    {{ node.short }}
                </text>
                <text
                    :x="node.side === 'right' ? 16 : -16"
                    y="11"
                    :text-anchor="node.side === 'right' ? 'start' : 'end'"
                    class="font-mono text-[9.5px]"
                    :class="typeof node.delta === 'number' && node.delta > 0
                        ? 'fill-orange-ink dark:fill-[#ff7b47]'
                        : node.delta === 'new'
                            ? 'fill-orange-ink dark:fill-[#ff7b47]'
                            : 'fill-muted-ink dark:fill-[#9ca3af]'"
                >
                    {{ node.score !== undefined ? Number(node.score).toFixed(2) : '' }}{{ formatDelta(node.delta) ? ` · ${formatDelta(node.delta)}` : '' }}
                </text>
            </g>
        </svg>
    </div>
</template>
