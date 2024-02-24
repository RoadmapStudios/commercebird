<script lang="ts" setup>
import type { Message } from "@/types";

defineProps({
  message: {
    type: Object,
    required: true
  },
  type: {
    type: String,
    default: 'success'
  },
  target: {
    type: String,
    default: '_blank'
  }
})
</script>

<template>
  <div class="p-4 my-4 rounded-md" :class="{
    'bg-teal-50 text-teal-700': type !== 'warning',
    'bg-rose-50 text-rose-700': type === 'warning'
  }">
    <div class="flex">
      <div class="flex-shrink-0">
        <component :is="message.icon" class="w-5 h-5" :class="{
          'text-teal-700': type !== 'warning',
          'text-rose-700': type === 'warning'
        }" />
      </div>
      <div class="flex-1 ml-3 md:flex md:justify-between">
        <p class="text-sm" :class="{
          'text-teal-700': type !== 'warning',
          'text-rose-700': type === 'warning'
        }" v-html="message.message"></p>
        <p v-if="message.link !== ''" class="mt-3 text-sm md:mt-0 md:ml-6">
          <a :href="message.link" :target="target" class="font-medium whitespace-nowrap" :class="{
            'text-teal-700': type !== 'warning',
            'text-rose-700': type === 'warning'
          }">
            {{ message.linkText }}
            <span aria-hidden="true"> &rarr;</span>
          </a>
        </p>
      </div>
    </div>
  </div>
</template>