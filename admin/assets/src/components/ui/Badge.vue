<script lang="ts" setup>
defineProps({
  text: {
    type: String,
    default: 'Not active'
  }
})
</script>

<template>
  <span :class="{
    'bg-teal-100 text-teal-800': text === 'active'
  }"
    class="capitalize inline-flex items-center gap-x-1.5 py-1 px-3 rounded-full text-xs font-medium bg-gray-50 text-gray-500">{{
      text }}</span>
</template>

