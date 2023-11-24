<template>
  <div class="flex flex-col overflow-hidden bg-white divide-y divide-gray-200 rounded-lg shadow h-fit">
    <div v-if="title" class="px-4 py-2 sm:px-6">
      <div class="flex flex-wrap items-center justify-between -mt-2 -ml-4 sm:flex-nowrap">
        <div class="mt-2 ml-4">
          <h3 class="text-lg font-medium leading-6 text-gray-900">
            {{ title }}
          </h3>
        </div>
        <div class="flex-shrink-0 mt-2 ml-4">
          <slot name="action"/>
        </div>
      </div>
      <slot name="header"/>
    </div>
    <div>
      <slot/>

    </div>
    <div v-if="foot" class="inline-flex flex-1 gap-4 px-4 py-2 bg-slate-50">
      <slot name="footer"/>
    </div>
  </div>
</template>
<script lang="ts" setup>
defineProps({
  title: {
    type: String,
    default: "",
  },
  foot: {
    type: Boolean,
    default: false,
  },
});
</script>

