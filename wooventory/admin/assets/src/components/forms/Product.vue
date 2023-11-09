<template>
  <div>
    <Alert :message="message" target="_blank" />
    <div class="grid grid-cols-2 gap-4 my-4">
      <InputGroup label="Import from Zoho Inventory" type="toggle">
        <Toggle v-model="store.product_settings.item_from_zoho" />
      </InputGroup>
      <InputGroup label="Disable Stock Sync" type="toggle">
        <Toggle v-model="store.product_settings.disable_stock_sync" />
      </InputGroup>
      <InputGroup label="Disable Product Sync to Zoho" type="toggle">
        <Toggle v-model="store.product_settings.disable_product_sync" />
      </InputGroup>
      <InputGroup label="Switch to Accounting Stock Mode" type="toggle">
        <Toggle v-model="store.product_settings.enable_accounting_stock" />
      </InputGroup>
    </div>
    <div class="flex gap-4 mb-4">
      <BaseButton
        :loading="loader.isLoading(backendAction.save_product)"
        @click.prevent="store.handleSubmit(backendAction.save_product)"
      >
        Save
      </BaseButton>
      <BaseButton
        :loading="loader.isLoading(backendAction.reset_product)"
        type="lite"
        @click.prevent="store.handleReset(backendAction.reset_product)"
      >
        Reset
      </BaseButton>
    </div>

    <div class="relative">
      <div aria-hidden="true" class="absolute inset-0 flex items-center">
        <div class="w-full border-t border-gray-300"></div>
      </div>
      <div class="relative flex justify-center">
        <span class="px-3 text-lg font-medium text-gray-900 bg-white"
          >Sync Actions</span
        >
      </div>
    </div>

    <div class="flex flex-wrap gap-4 py-4">
      <BaseButton
        :disabled="loader.isLoading('category')"
        :loading="loader.isLoading('category')"
        @click.prevent="store.sync('category')"
        >Categories Sync
      </BaseButton>
      <BaseButton
        :disabled="loader.isLoading('subcategory')"
        :loading="loader.isLoading('subcategory')"
        @click.prevent="store.sync('subcategory')"
        >Sub Categories Sync
      </BaseButton>
      <BaseButton
        :disabled="loader.isLoading('item')"
        :loading="loader.isLoading('item')"
        @click.prevent="store.sync('item')"
        >Items Sync
      </BaseButton>
      <BaseButton
        :disabled="loader.isLoading('variable_item')"
        :loading="loader.isLoading('variable_item')"
        @click.prevent="store.sync('variable_item')"
        >Variable Items Sync
      </BaseButton>
      <BaseButton
        :disabled="loader.isLoading('composite_item')"
        :loading="loader.isLoading('composite_item')"
        @click.prevent="store.sync('composite_item')"
        >Composite Items Sync
      </BaseButton>
    </div>
    <Table :rows="store.syncResponse" />
  </div>
</template>
<script lang="ts" setup>
import { backendAction } from "@/composables";
import { ExclamationCircleIcon } from "@heroicons/vue/24/outline";
import type { Message } from "../../composables";
import Alert from "../ui/Alert.vue";
import { useZohoInventoryStore } from "@/stores/zohoInventory";
import InputGroup from "../ui/InputGroup.vue";
import Toggle from "../ui/inputs/Toggle.vue";
import BaseButton from "../ui/BaseButton.vue";
import Table from "../ui/Table.vue";
import { useLoadingStore } from "@/stores/loading";

const store = useZohoInventoryStore();
const loader = useLoadingStore();
const message = <Message>{
  icon: ExclamationCircleIcon,
  message:
    "To sync categories from WooCommerce to Zoho and vice versa, you need to enable it in zoho. <strong>PLEASE DO THIS FIRST!<strong/>",
  link: `https://inventory.zoho.${store.connection.account_domain}`,
  linkText: "Visit Here",
};
</script>
