<template>
  <InputGroup label="Authorization Redirect URI" flexed>
    <TextInput
        :model-value="value"
        :disabled="true"
        :readonly="true"
    />
    <span class="px-4 py-2" @click="copy(value)">
          <ClipboardDocumentCheckIcon v-if="copied"/>
          <ClipboardDocumentIcon v-else/>
        </span>
  </InputGroup>
</template>
<script setup lang="ts">
import InputGroup from "@/components/ui/inputs/InputGroup.vue";
import TextInput from "@/components/ui/inputs/TextInput.vue";
import {useClipboard} from "@vueuse/core";
import {ClipboardDocumentCheckIcon, ClipboardDocumentIcon} from "@heroicons/vue/24/outline";

const {value} = defineProps({
  value: {
    type: String,
    required: true
  }
})
const {copy, copied} = useClipboard({value})
</script>