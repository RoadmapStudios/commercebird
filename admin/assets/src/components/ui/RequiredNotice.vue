<template>
  <div class="p-2 mb-4 rounded-md shadow bg-rose-100">
    <h3 class="text-sm font-semibold text-rose-700">Warning</h3>
    <p class="mt-1 text-sm text-rose-900" v-if="type === 'plugin'">
      Please install and activate
      <a :href="`/wp-admin/plugin-install.php?s=${slug}&tab=search&type=term`" class="font-medium" target="_blank">{{ name
      }}</a>
      plugin
    </p>
    <p class="mt-1 text-sm text-rose-900" v-else v-html="message"></p>
  </div>
</template>
<script setup lang="ts">
defineProps({
  message: String,
  slug: String,
  name: String,
  type: String
})
</script>