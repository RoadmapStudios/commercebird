<script lang="ts" setup>


import type {Message} from "@/types";

interface Props {
  message: Message,
  target: '_blank' | '_self'
}

defineProps<Props>()
</script>

<template>
  <div class="p-4 my-4 rounded-md bg-teal-50">
    <div class="flex">
      <div class="flex-shrink-0">
        <component :is="message.icon" class="w-5 h-5 text-teal-400"/>
      </div>
      <div class="flex-1 ml-3 md:flex md:justify-between">
        <p class="text-sm text-teal-700" v-html="message.message"></p>
        <p v-if="message.link !== ''" class="mt-3 text-sm md:mt-0 md:ml-6">
          <a :href="message.link" :target="target"
             class="font-medium text-teal-700 whitespace-nowrap hover:text-teal-600">
            {{ message.linkText }}
            <span aria-hidden="true"> &rarr;</span>
          </a>
        </p>
      </div>
    </div>
  </div>
</template>
