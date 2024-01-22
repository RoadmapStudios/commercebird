<script setup lang="ts">

import BaseButton from "@/components/ui/BaseButton.vue";
import {useExactOnlineStore} from "@/stores/exactOnline";
import {useLoadingStore} from "@/stores/loading";
import {backendAction} from "@/keys";

const store = useExactOnlineStore();
const loader = useLoadingStore();
const actionKey = backendAction.exactOnline.product;
</script>

<template>
  <div class="inline-flex gap-6 pt-4">
    <BaseButton :loading="loader.isLoading(actionKey.map)" @click="store.mapProducts()">
      Map all Products with Exact
    </BaseButton>
    <BaseButton type="lite" :loading="loader.isLoading(actionKey.import)" @click="store.importProducts()">
      Import Products from Exact
    </BaseButton>
  </div>
</template>

<style scoped>

</style>