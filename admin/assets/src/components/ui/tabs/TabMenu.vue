<script setup lang="ts">
const selectedTab = defineModel();
defineProps({
  tabs: {
    type: Object,
    required: true
  }
})
</script>
<template>
  <aside class="col-span-2">
    <nav class="space-y-1">
      <button
          v-for="(item, menu) in tabs"
          :key="menu"
          :class="{
                  'border-transparent text-gray-900 hover:bg-gray-50 hover:text-gray-900':
                    selectedTab !== menu,
                  'bg-teal-50 border-teal-500 text-teal-700 hover:bg-teal-50 hover:text-teal-700':
                    selectedTab === menu,
                }"
          class="flex items-center w-full px-4 py-2 text-sm font-medium border-l-4 group"
          type="button"
          @click.prevent="selectedTab = menu"
      >
        <component
            :is="item.icon"
            v-if="item.icon"
            :class="{
                    'text-teal-500 group-hover:text-teal-500':
                      selectedTab === menu,
                    'text-gray-400 group-hover:text-gray-500':
                      selectedTab !== menu,
                  }"
            class="flex-shrink-0 w-6 h-6 mr-3 -ml-1"
        />

        <span class="truncate">{{ item.title }}</span>
      </button>
    </nav>
  </aside>
</template>
