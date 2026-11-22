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

const CX = 280;
const CY = 152;

const RINGS = [
    { r: 50, label: '#1' },
    { r: 92, label: '#2–3' },
    { r: 130, label: '#4–5' },
];

const SLOTS = [
    { r: 50, deg: -26, side: 'right' },
    { r: 92, deg: -152, side: 'left' },
    { r: 92, deg: 28, side: 'right' },
    { r: 130, deg: 154, side: 'left' },
    { r: 130, deg: -34, side: 'right' },
];

const TICKS = Array.from({ length: 36 }, (_, idx) => {
    const rad = (idx * 10 * Math.PI) / 180;
    const inner = 126;
    const outer = idx % 9 === 0 ? 136 : 131;
    return {
        x1: CX + Math.cos(rad) * inner,
        y1: CY + Math.sin(rad) * inner,
        x2: CX + Math.cos(rad) * outer,
        y2: CY + Math.sin(rad) * outer,
        major: idx % 9 === 0,
    };
});

const shortTitle = (title) => {
    const clean = String(title || '').trim();
    return clean.length > 22 ? `${clean.slice(0, 21)}…` : clean;
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
    <div class="relative w-full select-none" :class="loading ? 'opacity-65 transition-opacity duration-200' : ''">
        <svg
            viewBox="0 0 560 304"
            class="block h-auto w-full"
            role="img"
            :aria-label="`Orbital rank bands for ${mode} search query ${query}. Rank 1 is on the inner ring.`"
        >
            <line
                x1="24"
                :y1="CY"
                x2="536"
                :y2="CY"
                class="stroke-black/[0.06] dark:stroke-white/[0.07]"
                stroke-width="1"
            />
            <line
                :x1="CX"
                y1="12"
                :x2="CX"
                y2="292"
                class="stroke-black/[0.06] dark:stroke-white/[0.07]"
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
                    :class="tick.major ? 'stroke-black/22 dark:stroke-white/25' : 'stroke-black/10 dark:stroke-white/10'"
                    stroke-width="1"
                />
            </g>

            <g v-for="(ring, idx) in RINGS" :key="`r-${ring.r}`">
                <circle
                    :cx="CX"
                    :cy="CY"
                    :r="ring.r"
                    :stroke-dasharray="idx === 2 ? '2 4' : undefined"
                    :class="idx === 0
                        ? 'fill-(--mode)/8 stroke-(--mode)/50'
                        : 'fill-none stroke-black/12 dark:stroke-white/12'"
                    :stroke-width="idx === 0 ? 1.25 : 1"
                />
                <text
                    :x="CX"
                    :y="CY + ring.r + 12"
                    text-anchor="middle"
                    class="fill-[#737373] text-[10px] tabular-nums dark:fill-[#8b8b93]"
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
                        ? 'stroke-[#171717]/60 dark:stroke-white/60'
                        : node.isTop
                            ? 'stroke-black/22 dark:stroke-white/24'
                            : 'stroke-black/10 dark:stroke-white/10'"
                    :stroke-width="node.isActive ? 1.4 : 1"
                    :stroke-dasharray="node.isActive ? undefined : '2 3'"
                />
            </g>

            <g :key="`pulse-${pulseKey}`" aria-hidden="true">
                <circle
                    v-if="pulseKey > 0"
                    :cx="CX"
                    :cy="CY"
                    r="26"
                    fill="none"
                    class="orbital-pulse stroke-(--mode)"
                    stroke-width="1.5"
                />
            </g>

            <g aria-hidden="true">
                <circle :cx="CX" :cy="CY" r="5" class="fill-[#171717] dark:fill-[#fafafa]" />
            </g>

            <g
                v-for="node in nodes"
                :key="node.id"
                class="orbital-node cursor-pointer"
                :style="{ transform: `translate(${node.x}px, ${node.y}px)` }"
                @mouseenter="emit('select', node.index)"
                @click="emit('select', node.index)"
            >
                <title>{{ node.rank }}. {{ node.title }}</title>
                <circle
                    v-if="node.isActive"
                    cx="0"
                    cy="0"
                    r="15.5"
                    fill="none"
                    class="stroke-[#171717]/35 dark:stroke-white/40"
                    stroke-width="1.25"
                />
                <circle
                    cx="0"
                    cy="0"
                    :r="node.isTop || node.isActive ? 11 : 9.5"
                    :class="node.isTop
                        ? 'fill-(--mode) stroke-white dark:stroke-black'
                        : 'fill-white stroke-[#171717]/40 dark:fill-[#141414] dark:stroke-white/40'"
                    stroke-width="1.75"
                />
                <text
                    x="0"
                    y="3.5"
                    text-anchor="middle"
                    class="text-[10.5px] font-semibold tabular-nums"
                    :class="node.isTop
                        ? 'fill-(--mode-on)'
                        : 'fill-[#171717] dark:fill-[#fafafa]'"
                >
                    {{ node.rank }}
                </text>

                <text
                    :x="node.side === 'right' ? 17 : -17"
                    y="-1"
                    :text-anchor="node.side === 'right' ? 'start' : 'end'"
                    class="text-[11.5px] font-medium"
                    :class="node.isActive
                        ? 'fill-[#171717] font-semibold dark:fill-white'
                        : node.isTop
                            ? 'fill-[#171717] dark:fill-[#fafafa]'
                            : 'fill-[#404040] dark:fill-[#a1a1aa]'"
                >
                    {{ node.short }}
                </text>
                <text
                    :x="node.side === 'right' ? 17 : -17"
                    y="12"
                    :text-anchor="node.side === 'right' ? 'start' : 'end'"
                    class="text-[10.5px] tabular-nums"
                    :class="(typeof node.delta === 'number' && node.delta > 0) || node.delta === 'new'
                        ? 'fill-(--mode)'
                        : 'fill-[#737373] dark:fill-[#8b8b93]'"
                >
                    {{ node.score !== undefined ? Number(node.score).toFixed(2) : '' }}{{ formatDelta(node.delta) ? `  ${formatDelta(node.delta)}` : '' }}
                </text>
            </g>
        </svg>
    </div>
</template>
