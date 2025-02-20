<script setup lang="ts">

import BaseButton from "@/components/ui/BaseButton.vue";
import { useExactOnlineStore } from "@/stores/exactOnline";
import { useLoadingStore } from "@/stores/loading";
import { backendAction } from "@/keys";
import InputGroup from "@/components/ui/inputs/InputGroup.vue";
import Toggle from "@/components/ui/inputs/Toggle.vue";
import { ConfirmModal } from "@/composable/helpers";

const store = useExactOnlineStore();
const loader = useLoadingStore();
const actionKey = backendAction.exactOnline.customer;
const handleClick = () => {

  if (store.importCustomers) {
    ConfirmModal.fire({
      text: "Are you sure, you want to import customers?",
    }).then((result) => {
      if (result.isConfirmed) {
        // set importCustomers to true
        store.importCustomers = true;
        store.mapCustomers();
      }
    })
  } else {
    store.mapCustomers();
  }
};
</script>

<template>
  <div class="pt-4 space-y-4">
    <InputGroup label="Do you want to import customers">
      <Toggle v-model="store.importCustomers" />
    </InputGroup>
    <BaseButton :loading="loader.isLoading(actionKey.map)" @click="handleClick">
      Map all Customers with Exact
    </BaseButton>
  </div>
</template>

<style scoped></style>