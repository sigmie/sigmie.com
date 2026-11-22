<script setup>
const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Search...',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    inputLabel: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'submit']);

const handleInput = (event) => {
    emit('update:modelValue', event.target.value);
};

const handleSubmit = () => {
    emit('submit', props.modelValue);
};
</script>

<template>
    <form class="w-full" @submit.prevent="handleSubmit">
        <div class="flex items-center gap-2.5 rounded-full border border-light-steel bg-ghostly-gray py-1.5 pr-1.5 pl-4 transition-colors focus-within:border-graphite focus-within:bg-canvas-white dark:border-[#262624] dark:bg-[#161618] dark:focus-within:border-white/35 dark:focus-within:bg-black">
            <span class="shrink-0 text-subtle-gray" aria-hidden="true">
                <slot name="icon">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </slot>
            </span>
            <input
                :value="modelValue"
                type="text"
                :placeholder="placeholder"
                :aria-label="inputLabel || placeholder"
                class="min-w-0 flex-1 border-0 bg-transparent py-2 text-[15px] text-graphite placeholder:text-subtle-gray focus:ring-0 focus:outline-none dark:text-[#f5f5f4]"
                :disabled="disabled"
                @input="handleInput"
            />
            <button
                type="submit"
                :disabled="disabled"
                class="shrink-0 rounded-full bg-graphite px-4 py-2 text-[13px] font-medium text-white transition-colors hover:bg-black disabled:opacity-50 dark:bg-[#f5f5f4] dark:text-graphite dark:hover:bg-white"
            >
                Search
            </button>
        </div>
    </form>
</template>
