<template>
  <div>
    <button
      :class="`${toggleClass}`"
      type="button"
      @click.prevent="openSlideOver"
    >
      <slot name="toggleIcon" />
    </button>

    <div v-show="slideOverRoot" class="relative z-[9999]">
      <Fade name="bg-fade">
        <div v-if="slideOver" class="fixed inset-0"></div>
      </Fade>
      <div class="fixed inset-0 overflow-hidden">
        <div class="fixed inset-0 overflow-hidden">
          <div
            class="fixed inset-y-0 right-0 flex max-w-full pl-10 pointer-events-none"
          >
            <Slide name="slide-over">
              <div
                v-if="slideOver"
                class="w-screen max-w-md pointer-events-auto"
              >
                <div
                  class="flex flex-col h-full bg-white divide-y divide-gray-200 shadow-xl"
                >
                  <div
                    class="flex flex-col h-full py-6 overflow-y-scroll bg-white shadow-xl"
                  >
                    <div class="p-4 shadow">
                      <div
                        class="flex mt-[26px] md:mt-3 lg:mt-3 items-start justify-between"
                      >
                        <h2
                          id="slide-over-title"
                          class="text-lg font-medium text-gray-900"
                        >
                          {{ title }}
                        </h2>
                        <div class="flex items-center ml-3 h-7">
                          <button
                            class="text-gray-400 bg-white rounded-md hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                            type="button"
                            @click.prevent="closeSlideOver"
                          >
                            <span class="sr-only">Close panel</span>
                            <component :is="XMarkIcon" aria-hidden="true" class="w-6 h-6" />
                          </button>
                        </div>
                      </div>
                    </div>
                    <div class="relative flex-1 px-4 mt-6 sm:px-6">
                      <div class="absolute inset-0 px-4 sm:px-6">
                        <slot name="content" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </Slide>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ref } from "vue";
import Slide from "@/components/ui/transition/Slide.vue";
import Fade from "@/components/ui/transition/Fade.vue";
import { XMarkIcon } from "@heroicons/vue/24/outline";

defineProps({
  toggleClass: {
    type: String,
    default:
      "p-2 z-[99999] text-white bg-teal-600 rounded-md bottom-4 left-1/2 right-1/2 hover:text-white focus:outline-none focus:ring-2 focus:ring-teal-500 cursor-pointer",
  },
  title: {
    type: String,
    default: "Slide Over Title",
  },
});

const slideOver = ref<Boolean>(false);
const slideOverRoot = ref<Boolean>(false);

const close = XMarkIcon;

const openSlideOver = () => {
  slideOverRoot.value = true;
  slideOver.value = true;
};

const closeSlideOver = () => {
  slideOver.value = false;
  setTimeout(() => {
    slideOverRoot.value = false;
  }, 800);
};
</script>
