<script setup lang="ts">

import BaseButton from "@/components/ui/BaseButton.vue";
import { useExactOnlineStore } from "@/stores/exactOnline";
import { useLoadingStore } from "@/stores/loading";
import { backendAction } from "@/keys";
import InputGroup from "@/components/ui/inputs/InputGroup.vue";
import Toggle from "@/components/ui/inputs/Toggle.vue";

const store = useExactOnlineStore();
const loader = useLoadingStore();
const actionKey = backendAction.exactOnline.product;
const handleClick = () => {
  if (store.importProducts) {
    if (confirm("Are you sure you want to import all products as simple products?")) {
      store.mapProducts();
    }
  } else {
    store.mapProducts();
  }
};
</script>

<template>
  <div class="pt-4 space-y-4">
    <InputGroup label="Do you want to import products">
      <Toggle v-model="store.importProducts" />
    </InputGroup>
    <BaseButton :loading="loader.isLoading(actionKey.map)" @click="handleClick">
      Map all Products with Exact
    </BaseButton>
  </div>
</template>
