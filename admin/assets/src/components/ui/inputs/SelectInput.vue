<script lang="ts" setup>

const value = defineModel()
defineProps({
  options: {
    type: Object,
    required: true
  }
})

</script>

<template>
  <select v-model="value"
          class="w-full py-2 pl-3 pr-10 mt-1 text-base border-gray-300 rounded-md shadow focus:border-teal-500 focus:outline-none focus:ring-teal-500 sm:text-sm"
          v-bind="$attrs">
    <option disabled value="">Please select one</option>
    <option v-for="(item, index) in options" :key="index" :value="index">{{ item }}</option>
  </select>
</template>
