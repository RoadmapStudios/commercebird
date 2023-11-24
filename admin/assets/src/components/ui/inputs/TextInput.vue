<script lang="ts" setup>
import { computed } from "vue";

const props = defineProps(["modelValue", "invalid"]);
const emit = defineEmits(["update:modelValue"]);

const value = computed({
  get() {
    return props.modelValue;
  },
  set(value) {
    emit("update:modelValue", value);
  },
});
</script>

<template>
  <input
    v-model="value"
    :class="{
      'border-red-300 text-red-900 placeholder-red-300 focus:border-red-500':
        invalid,
    }"
    class="disabled:bg-gray-200 disabled:cursor-not-allowed"
    v-bind="$attrs"
  />
</template>

<style scoped></style>
