<template>
  <div
      :class="{
      'sm:grid-cols-4': type !== 'toggle' && type !== 'repeater',
      'sm:grid-cols-3': type === 'toggle',
      'sm:col-span-2 sm:grid-cols-2': type === 'repeater',
    }"
      class="grid items-center justify-start gap-2 py-2"
  >
    <label
        :id="id"
        :class="{
        'col-span-2': type === 'toggle',
        'col-span-1': type === 'repeater',
      }"
    >{{ label }}</label
    >
    <div
        :class="{
        'col-span-3': type !== 'toggle',
        'col-span-1': type === 'repeater',
      }"
        class="grid-cols-1"
    >
      <div class="relative">
        <slot :id="id"/>
        <div
            v-if="invalid"
            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none"
        >
          <ExclamationCircleIcon class="w-6 h-6 text-rose-600"/>
        </div>
      </div>
      <p v-if="invalid" :class="errorClass">{{ errorMessage }}</p>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {computed} from "vue";
import {ExclamationCircleIcon} from "@heroicons/vue/24/outline";

const id = Math.random().toString(36).substring(7);
const emit = defineEmits(["update:modelValue"]);

const props = defineProps({
  label: {
    type: String,
    default: "Input Label",
  },
  type: {
    type: String,
    default: "text",
  },
  placeholder: {
    type: String,
    default: "",
  },
  value: {
    type: String,
    default: "",
  },
  errorClass: {
    type: String,
    default: "mt-2 text-sm text-rose-600",
  },
  errorMessage: {
    type: String,
    default: "",
  },
  modelValue: {
    type: String,
  },
  invalid: {
    type: Boolean,
    default: false,
  },
});

const value = computed({
  get() {
    return props.modelValue;
  },
  set(value) {
    emit("update:modelValue", value);
  },
});
</script>
