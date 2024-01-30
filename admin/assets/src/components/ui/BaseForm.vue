<script lang="ts" setup>
import BaseButton from "@/components/ui/BaseButton.vue";
import {useLoadingStore} from "@/stores/loading";

const loader = useLoadingStore();
defineProps({
  keys: {
    type: Object
  },
  submitLabel: {
    type: String,
    default: "Save"
  }
})
</script>

<template>
  <div>
    <slot/>

    <div class="flex items-center gap-4 py-2">
      <BaseButton
          :loading="loader.isLoading(keys?.save)"
          @click="$emit('submit')"
      >
        {{ submitLabel }}
      </BaseButton>
      <BaseButton
          :loading="loader.isLoading(keys?.reset)"
          type="lite"
          @click.prevent="$emit('reset')"
      >
        Reset
      </BaseButton>
    </div>
  </div>
</template>
