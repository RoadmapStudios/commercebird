<template>
  <div>
    <CopyableInput :value="product" label="Items real-time hook"/>
    <CopyableInput :value="shipping" label="Shipping status real-time hook"/>
  </div>
</template>
<script lang="ts" setup>
import {origin} from "@/composables";
import {useClipboard} from "@vueuse/core";
import CopyableInput from "@/components/ui/inputs/CopyableInput.vue";

const shipping = origin + "/wp-json/v2/zoho-shipping-status"
const product = origin + "/wp-json/v2/zoho-product"

const {copy, copied} = useClipboard({shipping})
</script>
