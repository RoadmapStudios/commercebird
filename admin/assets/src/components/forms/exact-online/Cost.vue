<script setup lang="ts">

import BaseButton from "@/components/ui/BaseButton.vue";
import {backendAction} from "@/keys";
import {useLoadingStore} from "@/stores/loading";
import {useExactOnlineStore} from "@/stores/exactOnline";

const store = useExactOnlineStore();
const loader = useLoadingStore();
const loaderKey = backendAction.exactOnline;
</script>

<template>
  <div class="flex gap-4 pt-4">
    <BaseButton :loading="loader.isLoading(loaderKey.gl_account.save)" @click="store.getGLAccounts()">
      Get GL Accounts
    </BaseButton>
    <BaseButton :loading="loader.isLoading(loaderKey.cost_center.save)" @click="store.getCenters()">
      Get Cost Centers
    </BaseButton>
    <BaseButton :loading="loader.isLoading(loaderKey.cost_unit.save)" @click="store.getUnits()">
      Get Cost Units
    </BaseButton>
    <BaseButton :loading="loader.isLoading(loaderKey.payment_status.save)" @click="store.getPaymentStatus()">
      Get Payment Status of Invoices
    </BaseButton>
  </div>
</template>

