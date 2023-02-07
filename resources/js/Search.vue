<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import ShortcutButton from "./ShortcutButton.vue";
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
  DialogTitle,
  Combobox,
  ComboboxInput,
  ComboboxButton,
  ComboboxOptions,
  ComboboxOption,
} from "@headlessui/vue";

import {
  ArrowUpIcon,
  ArrowDownIcon,
  MagnifyingGlassIcon,
} from "@heroicons/vue/20/solid";
import { SigmieSearch } from "@sigmie/vue";

let query = ref("");
let input = ref(null);
let isOpen = ref(false);

function visit(value) {
  console.log(value);
}

function handleKeyDown(e) {
  // On Esc
  if (e.keyCode === 27) {
    input?.value?.blur();
  }

  // On CMD + K
  if (e.keyCode === 75 && e.metaKey) {
    openModal(true);
    input?.value?.focus();
    input?.value?.click();
  }
}

function closeModal() {
  isOpen.value = false;
}

function openModal() {
  isOpen.value = true;
}

function addKeydownListener() {
  document.addEventListener("keydown", handleKeyDown);
}

function removeKeydownListener() {
  document.removeEventListener("keydown", handleKeyDown);
}

onMounted(() => {
  addKeydownListener();
});

onUnmounted(() => {
  removeKeydownListener();
});
</script>

<template>
  <div class="relative flex flex-row justify-between w-full max-w-sm">
    <button
      @click="openModal"
      class="
        flex flex-row
        justify-between
        text-slate-400
        w-full
        border border-slate-100
        bg-slate-50
        rounded-lg
        items-center
        px-4
        py-2
      "
    >
      <div>Search...</div>
      <div class="flex flex-row items-center space-x-2">
        <ShortcutButton> CMD/CTR </ShortcutButton>
        <div class="font-semibold text-xs text-slate-500">+</div>
        <ShortcutButton>K</ShortcutButton>
      </div>
    </button>
  </div>

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
        <div class="fixed inset-0 bg-black bg-opacity-25" />
      </TransitionChild>

      <div class="fixed inset-x-0 top-20 overflow-y-auto">
        <div
          class="flex min-h-full items-center justify-center p-4 text-center"
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
              class="
                w-full
                max-w-xl
                transform
                overflow-hidden
                shadow-xl
                transition-all
              "
            >
              <div class="">
                <SigmieSearch
                  apiKey="Dw4cThA9iGZwPw0r8VFMEiZwWGzroKFg6C0nb39D"
                  :query="query"
                  :perPage="10"
                  :filter="''"
                  search="testing"
                  applicationId="svvhug7c38lsrznsn"
                  v-slot="{ hits, total, loading }"
                >
                  <Combobox @update:modelValue="visit" v-slot="{ open }">
                    <div class="relative">
                      <div
                        :class="{
                          'rounded-lg': !open,
                          'rounded-t-lg': open,
                        }"
                        class="
                          relative
                          border-b
                          w-full
                          cursor-default
                          text-left
                          outline-none
                          focus:outline-none
                          sm:text-sm
                        "
                      >
                        <ComboboxInput
                          :ref="input"
                          :class="{
                            'rounded-lg': !open,
                            'rounded-t-lg': open,
                          }"
                          class="
                            focus:ring-0
                            w-full
                            outline-none
                            border-none
                            focus:outline-none
                            py-3
                            pl-10
                            pr-10
                            text-sm
                            leading-5
                            text-slate-700
                          "
                          @change="query = $event.target.value"
                        />

                        <ComboboxButton
                          class="
                            absolute
                            inset-y-0
                            left-0
                            flex flex-row
                            items-center
                            pl-3
                            text-slate-300 text-sm
                          "
                        >
                          <div v-if="loading">
                            <svg
                              aria-hidden="true"
                              class="
                                w-5
                                h-5
                                text-gray-100
                                animate-spin
                                fill-gray-700
                              "
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
                              class="h-5 w-5 text-slate-300"
                            ></MagnifyingGlassIcon>
                          </div>
                        </ComboboxButton>
                        <ComboboxButton
                          class="
                            absolute
                            inset-y-0
                            right-0
                            flex
                            items-center
                            pr-4
                            text-slate-300 text-sm
                          "
                        >
                          <div v-if="open">
                            <ShortcutButton>ESC</ShortcutButton>
                          </div>
                          <div
                            v-else
                            class="flex flex-row items-center space-x-2"
                          >
                            <ShortcutButton>CMD/CTR</ShortcutButton>
                            <div class="font-semibold text-xs text-slate-500">
                              +
                            </div>
                            <ShortcutButton>K</ShortcutButton>
                          </div>
                        </ComboboxButton>
                      </div>
                      <TransitionRoot
                        leave="transition ease-in duration-100"
                        leaveFrom="opacity-100"
                        leaveTo="opacity-0"
                        @after-leave="query = ''"
                      >
                        <div class="h-full pb-12">
                          <ComboboxOptions
                            class="
                              relative
                              bg-white
                              max-h-[800px]
                              w-full
                              overflow-auto
                              text-white
                              py-1
                              text-base
                              shadow-lg
                              focus:ring-0 focus:outline-none
                              sm:text-sm
                            "
                          >
                            <div
                              v-if="query !== '' && total === 0"
                              class="
                                border-t border-slate-100
                                relative
                                cursor-default
                                select-none
                                py-2
                                px-4
                                text-slate-400
                                h-40
                                flex flex-row
                                items-center
                                text-lg
                              "
                            >
                              <div class="mx-auto">
                                <svg
                                  width="40"
                                  height="40"
                                  viewBox="0 0 20 20"
                                  fill="none"
                                  fill-rule="evenodd"
                                  stroke="currentColor"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"
                                >
                                  <path
                                    d="M15.5 4.8c2 3 1.7 7-1 9.7h0l4.3 4.3-4.3-4.3a7.8 7.8 0 01-9.8 1m-2.2-2.2A7.8 7.8 0 0113.2 2.4M2 18L18 2"
                                  ></path>
                                </svg>
                              </div>

                              <div class="mx-auto">
                                <div class="w-56 text-center">
                                  No results for "<span
                                    class="text-slate-400 font-medium"
                                    >{{ query }}</span
                                  >"
                                </div>
                              </div>
                            </div>

                            <ComboboxOption
                              v-for="(hit, index) in Object.values(hits)"
                              :key="hit._id"
                              as="template"
                              v-slot="{ selected, active }"
                              :value="hit"
                            >
                              <li
                                class="
                                  relative
                                  cursor-default
                                  select-none
                                  py-3
                                  px-4
                                  flex flex-col
                                  items-center
                                "
                                :class="{
                                  'bg-slate-100 text-black': active,
                                  'text-slate-800': !active,
                                  'border-b border-slate-100':
                                    index !== hits.length - 1,
                                }"
                              >
                                <div
                                  class="
                                    flex flex-row
                                    space-x-4
                                    py-2
                                    items-center
                                    w-full
                                  "
                                  :class="{
                                    'font-medium': selected,
                                    'font-normal': !selected,
                                  }"
                                >
                                  <div
                                    class="
                                      border
                                      rounded-lg
                                      border-slate-200
                                      text-xs text-
                                      p-1
                                    "
                                  >
                                    {{ hit }}
                                  </div>
                                </div>
                              </li>
                            </ComboboxOption>
                          </ComboboxOptions>

                          <div
                            class="
                              absolute
                              rounded-b-lg
                              bottom-0
                              h-12
                              bg-slate-50
                              w-full
                              px-4
                              py-2
                              flex flex-row
                              justify-between
                              items-center
                              border-t border-slate-200
                            "
                          >
                            <div class="flex flex-row space-x-1 items-center">
                              <ShortcutButton>
                                <ArrowUpIcon
                                  class="text-black h-3 w-3"
                                ></ArrowUpIcon>
                              </ShortcutButton>
                              <ShortcutButton>
                                <ArrowDownIcon
                                  class="text-black h-3 w-3"
                                ></ArrowDownIcon>
                              </ShortcutButton>
                              <div className="text-slate-500 pl-1 text-sm">
                                to navigate
                              </div>
                            </div>

                            <div class="flex flex-row items-center space-x-2">
                              <ShortcutButton>ESC</ShortcutButton>
                              <div className="text-slate-500 pl-1 text-sm">
                                to close
                              </div>
                            </div>

                            <div class="flex flex-row items-center space-x-2">
                              <ShortcutButton>ENTER</ShortcutButton>
                              <div className="text-slate-500 pl-1 text-sm">
                                to visit
                              </div>
                            </div>
                          </div>
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
</template>
