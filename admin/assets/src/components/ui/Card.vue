<template>
  <div class="flex flex-col overflow-hidden bg-white divide-y divide-gray-200 rounded-lg shadow h-fit">
    <div v-if="title" class="px-4 py-2 sm:px-6">
      <div class="flex items-center justify-between">
        <!-- Title and Slot Section -->
        <div class="flex items-center gap-2">
          <h3 class="text-lg font-medium leading-6 text-gray-900">
            {{ title }}
          </h3>
          <slot name="title"></slot>
        </div>
        <!-- Version Section (aligned to the end) -->
        <div v-if="title === 'Settings'" class="text-sm text-gray-500">
          Version {{ version }}
        </div>
      </div>
      <!-- Header Slot Section -->
      <slot name="header" />
    </div>
    <!-- Main Content Slot Section -->
    <div>
      <slot />
    </div>
    <!-- Footer Section -->
    <div v-if="foot" class="inline-flex flex-1 gap-4 px-4 py-2 bg-slate-50">
      <slot name="footer" />
    </div>
  </div>
</template>
<script lang="ts" setup>
defineProps({
  title: {
    type: String,
    default: "",
  },
  version: {
    type: String,
    default: cmbirdData.version,
  },
  foot: {
    type: Boolean,
    default: false,
  },
});
</script>
