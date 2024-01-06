<template>
  <div>
    <div class="grid grid-cols-2 gap-4 my-4">
      <InputGroup label="Create Package" type="toggle">
        <Toggle v-model="store.order_settings.package_sync" />
      </InputGroup>
      <InputGroup label="Disable order sync" type="toggle">
        <Toggle v-model="store.order_settings.disable_sync" />
      </InputGroup>
      <InputGroup label="Enable auto sales order number" type="toggle">
        <Toggle v-model="store.order_settings.enable_auto_number" />
      </InputGroup>
      <InputGroup label="Send all Orders as Draft" type="toggle">
        <Toggle v-model="store.order_settings.enable_order_status" />
      </InputGroup>
    </div>
    <InputGroup v-if="store.order_settings.enable_auto_number" label="Order Prefix">
      <TextInput v-model="store.order_settings.order_prefix" placeholder="Set Order Prefix like WC" />
    </InputGroup>
    <InputGroup label="Select a Warehouse (optional)">
      <SelectInput v-model="store.order_settings.warehouse_id" :options="store.zoho_warehouses" />
    </InputGroup>
    <div v-if="store.order_settings.warehouse_id" class="flex items-center py-4 gap-x-6">
      <label>Use this Warehouse also for Stock Sync</label>
      <Toggle v-model="store.order_settings.enable_warehousestock" />
    </div>
    <div class="flex gap-4 mt-8">
      <BaseButton :loading="loader.isLoading(backendAction.save_order)" @click="store.handleSubmit"
        @click.prevent="store.handleSubmit(backendAction.save_order)">
        Save
      </BaseButton>
      <BaseButton :loading="loader.isLoading(backendAction.reset_order)" type="lite"
        @click.prevent="store.handleReset(backendAction.reset_order)">Reset
      </BaseButton>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { backendAction } from '@/composables';
import { useLoadingStore } from '@/stores/loading';
import { useZohoInventoryStore } from '@/stores/zohoInventory';
import InputGroup from '../ui/InputGroup.vue';
import Toggle from '../ui/inputs/Toggle.vue';
import TextInput from '../ui/inputs/TextInput.vue';
import SelectInput from '../ui/inputs/SelectInput.vue';
import BaseButton from '../ui/BaseButton.vue';

const store = useZohoInventoryStore();
const loader = useLoadingStore();
</script>
  