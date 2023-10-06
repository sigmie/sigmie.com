<script setup>
import TailText from "./TailText.vue";
import TailItem from "./TailItem.vue";
import { ArrowUpIcon, ArrowDownIcon } from "@heroicons/vue/20/solid";
import ShortcutButton from "./ShortcutButton.vue";
</script>

<template>
    <div
        class="absolute rounded-b-lg bottom-0 h-24 w-full px-4 py-2 border-t border-zinc-300"
    >
        <div class="flex flex-col space-y-4 justify-center h-full">
            <div class="flex flex-row justify-between items-center px-4">
                <TailItem>
                    <div class="flex flex-row space-x-2">
                        <ShortcutButton>
                            <ArrowUpIcon class="h-4"></ArrowUpIcon>
                        </ShortcutButton>
                        <ShortcutButton>
                            <ArrowDownIcon class="h-4"></ArrowDownIcon>
                        </ShortcutButton>
                    </div>
                    <TailText> to navigate </TailText>
                </TailItem>

                <TailItem>
                    <ShortcutButton> ESC </ShortcutButton>
                    <TailText> to close</TailText>
                </TailItem>

                <TailItem>
                    <ShortcutButton> ENTER </ShortcutButton>
                    <TailText> to enter</TailText>
                </TailItem>
            </div>
            <div class="mb-8 flex justify-center">
                <a
                    href="https://sigmie.app"
                    class="relative rounded-full px-3 py-1 text-sm leading-6 text-gray-600 ring-1 ring-gray-900/10 hover:ring-gray-900/20"
                >
                    Search by
                    <span class="font-semibold text-black">Sigmie <span aria-hidden="true">&rarr;</span></span>
                </a>
            </div>
        </div>
    </div>
</template>
