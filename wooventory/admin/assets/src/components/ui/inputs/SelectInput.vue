<script lang="ts" setup>
import {computed} from 'vue'

const props = defineProps(['modelValue', 'options'])
const emit = defineEmits(['update:modelValue'])

const value = computed({
  get() {
    return props.modelValue
  },
  set(value) {
    emit('update:modelValue', value)
  }
})

</script>

<template>
  <select v-model="value"
          class="mt-1 w-full shadow rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-teal-500 focus:outline-none focus:ring-teal-500 sm:text-sm"
          v-bind="$attrs">
    <option disabled value="">Please select one</option>
    <option v-for="(item, index) in options" :key="index" :value="index">{{ item }}</option>
  </select>
</template>
