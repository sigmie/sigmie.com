<script setup>
import { defineEmits, ref } from "vue";
import { ComboboxInput, ComboboxButton } from "@headlessui/vue";
import { MagnifyingGlassIcon } from "@heroicons/vue/20/solid";
import ShortcutButton from "./ShortcutButton.vue";
import CommandIcon from "./CommandIcon.vue";
const isWindows = ref(navigator.userAgent.indexOf("Win") !== -1);

const emit = defineEmits(["update:modelValue"]);

let props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    open: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div
        :class="{
            'rounded-lg border': !open,
            'rounded-t-lg': open,
        }"
        class="relative bg-white border-zinc-300 w-full cursor-default text-left outline-none focus:outline-none sm:text-sm"
    >
        <slot> </slot>

        <ComboboxInput
            autocomplete="off"
            ref="input"
            :class="{
                'rounded-lg': !open,
                'rounded-t-lg': open,
            }"
            class="focus:ring-0 bg-w-full outline-none border-none focus:outline-none py-4 px-12 text-base leading-5 text-zinc-600"
            @change="emit('update:modelValue', $event.target.value)"
        >
        </ComboboxInput>

        <ComboboxButton
            class="absolute inset-y-0 left-0 flex flex-row items-center pl-3 text-zinc-800 text-sm"
        >
            <div v-if="loading">
                <svg
                    aria-hidden="true"
                    class="md:w-5 md:h-5 h-3 w-3 text-zinc-200 animate-spin fill-zinc-800"
                    viewBox="0 0 100 101"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                        fill="currentColor"
                    />
                    <path
                        d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                        fill="currentFill"
                    />
                </svg>

                <span class="sr-only">Loading...</span>
            </div>
            <div v-else>
                <MagnifyingGlassIcon
                    class="h-6 w-6 text-zinc-300"
                ></MagnifyingGlassIcon>
            </div>
        </ComboboxButton>
        <ComboboxButton
            class="absolute inset-y-0 right-0 flex items-center pr-4 text-zinc-300 text-sm"
        >
            <div v-if="open">
                <ShortcutButton class="hidden md:block"> ESC </ShortcutButton>
                <ShortcutButton class="md:hidden"> CLOSE </ShortcutButton>
            </div>
            <div v-else class="flex flex-row items-center space-x-2">
                <ShortcutButton>
                    <CommandIcon v-if="!isWindows"></CommandIcon>
                    <span v-else> Ctrl </span>
                    <span> K </span>
                </ShortcutButton>
            </div>
        </ComboboxButton>
    </div>
</template>
