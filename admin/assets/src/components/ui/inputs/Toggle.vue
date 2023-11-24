<script lang="ts" setup>
defineProps(['modelValue'])

defineEmits(['update:modelValue'])
</script>

<template>
  <button :class="{
    'bg-teal-600': modelValue,
    'bg-gray-200': !modelValue,
  }" aria-checked="false"
          class="relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
          role="switch" type="button" @click.prevent="$emit('update:modelValue', !modelValue)">
    <span :class="{
      'translate-x-5': modelValue,
      'translate-x-0': !modelValue,
    }" aria-hidden="true"
          class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
  </button>
</template>

<style scoped></style>