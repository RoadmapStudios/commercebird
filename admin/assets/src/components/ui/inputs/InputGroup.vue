<template>
  <div v-bind="$attrs" :class="{
    'sm:grid-cols-4': type !== 'toggle' && type !== 'repeater',
    'sm:grid-cols-3': type === 'toggle',
    'items-baseline': hint,
    'items-center': !hint,
    'sm:col-span-2 sm:grid-cols-2': type === 'repeater',
  }" class="grid justify-start gap-2 py-2">
    <label :id="id" :class="{
      'col-span-2': type === 'toggle',
      'col-span-1': type === 'repeater',
    }">{{ label }}</label>
    <div :class="{
      'col-span-3': type !== 'toggle',
      'col-span-1': type === 'repeater',
    }" class="grid-cols-1">
      <div class="relative">
        <div :class="{
          'flex items-center gap-4': flexed
        }">
          <slot :id="id" />
        </div>
        <div v-if="invalid" class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
          <component :is="ExclamationCircleIcon" class="w-6 h-6 text-rose-600" />
        </div>
      </div>
      <p v-if="invalid || hint" v-html="hint ? hint : errorMessage"></p>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { ExclamationCircleIcon } from "@heroicons/vue/24/outline";

const id = Math.random().toString(36).substring(7);

defineProps({
  label: {
    type: String,
    default: "Input Label",
  },
  type: {
    type: String,
    default: "text",
  },
  flexed: {
    type: Boolean,
    default: false
  },
  errorClass: {
    type: String,
    default: "mt-2 text-sm text-rose-600",
  },
  errorMessage: {
    type: String,
    default: "",
  },
  invalid: {
    type: Boolean,
    default: false,
  },
  hint: {
    type: String,
    default: "",
  }
});
</script>
