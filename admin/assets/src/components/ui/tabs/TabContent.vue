<template>
  <div v-if="selectedTab" class="relative col-span-10 px-4 pb-4 ">
    <div v-if="useLoadingStore().isRunning()" class="absolute inset-0 z-10 bg-gray-900 opacity-20"></div>
    <KeepAlive>
      <component :is="tabs[selectedTab].component" />
    </KeepAlive>
  </div>
</template>
<script setup lang="ts">
import { useLoadingStore } from "@/stores/loading";
const selectedTab = defineModel()
defineProps({
  tabs: Object
})
</script>