<template>
  <div>
    <BaseForm :keys="action" @reset="store.handleReset(action.reset)" @submit="store.handleSubmit(action.save)">
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
    </BaseForm>


    <div class="relative">
      <div aria-hidden="true" class="absolute inset-0 flex items-center">
        <div class="w-full border-t border-gray-300"></div>
      </div>
      <div class="relative flex justify-center">
        <span class="px-3 text-lg font-medium text-gray-900 bg-white">Sync Actions</span>
      </div>
    </div>

    <div class="flex flex-wrap gap-4 py-4">
      <BaseButton :disabled="loader.isLoading('item')" :loading="loader.isLoading('item')"
        @click.prevent="store.sync('item', selectedCategory)">
        Items Sync
      </BaseButton>
      <BaseButton :disabled="loader.isLoading('variable_item')" :loading="loader.isLoading('variable_item')"
        @click.prevent="store.sync('variable_item', selectedCategory)">
        Item Groups Sync
      </BaseButton>

      <BaseButton :disabled="loader.isLoading('composite_item')" :loading="loader.isLoading('composite_item')"
        @click.prevent="store.sync('composite_item', selectedCategory)">
        Composite Items Sync
      </BaseButton>
      <!-- Category selection dropdown -->
      <div class="w-full mt-4">
        <v-select v-model="selectedCategory" :options="categoryOptions" label="label" :clearable="true"
          placeholder="Select a category for Testing (optional)" :filterable="true" />
      </div>
    </div>
    <Table :rows="store.syncResponse"></Table>
  </div>
</template>
<script lang="ts" setup>
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseForm from '@/components/ui/BaseForm.vue';
import Table from '@/components/ui/Table.vue';
import InputGroup from '@/components/ui/inputs/InputGroup.vue';
import Toggle from '@/components/ui/inputs/Toggle.vue';
import { backendAction } from '@/keys';
import { useLoadingStore } from '@/stores/loading';
import { useZohoInventoryStore } from '@/stores/zohoInventory';
import type { ZohoCategory } from '@/types';
import { ref, computed } from 'vue';
import vSelect from 'vue-select';

const action = backendAction.zohoInventory.product;
const store = useZohoInventoryStore();
const loader = useLoadingStore();

const selectedCategory = ref<{ label: string; value: string } | null>(null);

const stripHtml = (html: string): string => {
  const doc = new DOMParser().parseFromString(html, "text/html");
  return doc.body.textContent || "";
};

// Prepare categories for the dropdown
const categoryOptions = computed(() => {
  return store.zoho_categories.map((category: ZohoCategory) => ({
    label: stripHtml(category.label),
    value: category.id,
  }));
});
</script>
<style>
@import "vue-select/dist/vue-select.css";
</style>
