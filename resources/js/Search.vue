<script setup>
import { router } from "@inertiajs/vue3";
import SearchButton from "./search/SearchButton.vue";
import NoResults from "./search/NoResults.vue";
import QueryInput from "./search/QueryInput.vue";
import Tail from "./search/Tail.vue";
import Hit from "./search/Hit.vue";
import { ref, nextTick } from "vue";
import {
    TransitionRoot,
    TransitionChild,
    Dialog,
    DialogPanel,
    Combobox,
    ComboboxOptions,
    ComboboxOption,
    ComboboxButton,
} from "@headlessui/vue";

import {
    ArrowUpIcon,
    ArrowDownIcon,
    MagnifyingGlassIcon,
} from "@heroicons/vue/20/solid";
import { SigmieSearch } from "@sigmie/vue";

let query = ref("");
let input = ref(null);
let hiddenButton = ref(null);
let isOpen = ref(false);

function visit(value) {
    router.get(value.href);
}

function closeModal() {
    isOpen.value = false;
}

function openModal() {
    isOpen.value = true;

    nextTick(() => {
        hiddenButton.value.focus();
        hiddenButton.value.click();
    });
}
</script>

<template>
    <div class="container flex flex-wrap items-center justify-between mx-auto">
        <SearchButton @open-search="openModal"></SearchButton>

        <TransitionRoot appear :show="isOpen" as="template">
            <Dialog as="div" @close="closeModal" class="relative z-50">
                <TransitionChild
                    as="template"
                    enter="duration-300 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div
                        class="fixed inset-0 bg-black/10 backdrop-filter backdrop-blur"
                    />
                </TransitionChild>

                <div class="fixed inset-x-0 top-5 overflow-y-auto">
                    <div
                        class="flex md:min-h-full items-center justify-center py-4 md:p-4"
                    >
                        <TransitionChild
                            as="template"
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel
                                class="w-full max-w-2xl transform overflow-hidden transition-all"
                            >
                                <div class="">
                                    <SigmieSearch
                                        apiKey="ptoku9AeftWaRE3WJqCWHnWrFu8R7b50sKJFLHaG"
                                        :query="query"
                                        :perPage="10"
                                        :filters="''"
                                        search="sigmie-com-docs"
                                        applicationId="svvhug7c38lsrznsn"
                                        v-slot="{
                                            hits,
                                            total,
                                            loading,
                                            processing_time_ms,
                                        }"
                                    >
                                        <Combobox
                                            @update:modelValue="visit"
                                            v-slot="{ open }"
                                        >
                                            <div class="relative">
                                                <QueryInput
                                                    :class="{
                                                        'rounded-lg border':
                                                            !open,
                                                        'rounded-t-lg': open,
                                                    }"
                                                    :open="open"
                                                    :loading="loading"
                                                    v-model="query"
                                                >
                                                    <ComboboxButton
                                                        class="hidden"
                                                    >
                                                        <button
                                                            ref="hiddenButton"
                                                        ></button>
                                                    </ComboboxButton>
                                                </QueryInput>
                                                <TransitionRoot
                                                    leave="transition ease-in duration-100"
                                                    leaveFrom="opacity-100"
                                                    leaveTo="opacity-0"
                                                    @after-leave="query = ''"
                                                >
                                                    <div
                                                        class="h-full pb-12 rounded-b-md bg-white border-t border-zinc-200"
                                                    >
                                                        <ComboboxOptions
                                                            class="relative max-h-[700px] w-full overflow-auto text-zinc-100 py-1 text-base focus:ring-0 focus:outline-none sm:text-sm"
                                                        >
                                                            <NoResults
                                                                :query="query"
                                                                v-if="
                                                                    query !=
                                                                        '' &&
                                                                    total === 0
                                                                "
                                                            ></NoResults>

                                                            <ComboboxOption
                                                                v-for="(
                                                                    hit, index
                                                                ) in Object.values(
                                                                    hits
                                                                )"
                                                                :key="hit._id"
                                                                as="div"
                                                                v-slot="{
                                                                    selected,
                                                                    active,
                                                                }"
                                                                :value="hit"
                                                            >
                                                                <Hit
                                                                    :hit="hit"
                                                                    :active="
                                                                        active
                                                                    "
                                                                    :selected="
                                                                        selected
                                                                    "
                                                                    :class="{
                                                                        'bg-zinc-200/30 text-zinc-100':
                                                                            active,
                                                                        'text-zinc-400':
                                                                            !active,
                                                                        'border-b border-zinc-200':
                                                                            index !==
                                                                            hits.length -
                                                                                1,
                                                                    }"
                                                                >
                                                                </Hit>
                                                            </ComboboxOption>
                                                        </ComboboxOptions>
                                                        <Tail></Tail>
                                                    </div>
                                                </TransitionRoot>
                                            </div>
                                        </Combobox>
                                    </SigmieSearch>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>
