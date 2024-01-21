<template>
  <div class="p-2 mb-4 rounded-md shadow bg-rose-100">
    <h3 class="text-sm font-semibold text-rose-700">Warning</h3>
    <p class="mt-1 text-sm text-rose-900" v-html="message"></p>
  </div>
</template>
<script setup lang="ts">
defineProps({
  message: String
})
</script>