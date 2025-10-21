<script setup>
import { ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Search...'
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue', 'submit']);

const localValue = ref(props.modelValue);

const handleInput = (event) => {
    localValue.value = event.target.value;
    emit('update:modelValue', event.target.value);
};

const handleSubmit = () => {
    emit('submit', localValue.value);
};
</script>

<template>
    <form @submit.prevent="handleSubmit" class="w-full">
        <div class="border-b border-gray-800 pb-4 focus-within:border-gray-700 transition-colors">
            <div class="relative flex gap-2 sm:gap-3 items-center pl-2">
                <slot name="icon">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </slot>
                <input
                    :value="modelValue"
                    type="text"
                    :placeholder="placeholder"
                    class="flex-1 py-3 text-base bg-transparent focus:outline-none focus:ring-0 text-gray-100 placeholder-gray-500 border-0"
                    :disabled="disabled"
                    @input="handleInput"
                    @keyup.enter="handleSubmit"
                />
            </div>
        </div>
    </form>
</template>
