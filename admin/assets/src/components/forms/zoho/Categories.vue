<script lang="ts" setup>
import { useZohoInventoryStore } from "@/stores/zohoInventory";
import InputGroup from "../../ui/inputs/InputGroup.vue";
import Toggle from "../../ui/inputs/Toggle.vue";
import { backendAction } from "@/keys";
import SelectInput from "@/components/ui/inputs/SelectInput.vue";
import BaseForm from "@/components/ui/BaseForm.vue";
import BaseButton from '@/components/ui/BaseButton.vue';
import { useLoadingStore } from '@/stores/loading';

const action = backendAction.zohoInventory.cron;
const store = useZohoInventoryStore();
const loader = useLoadingStore();
</script>
<template>
  <div>

    <!-- <div class="relative"> -->
    <!-- <div aria-hidden="true" class="absolute inset-0 flex items-center">
        <div class="w-full border-t border-gray-300"></div>
      </div> -->
    <!-- <div class="relative flex justify-center">
        <span class="px-3 text-lg font-medium text-gray-900 bg-white">Sync Actions</span>
      </div> -->
    <!-- </div> -->

    <div class="flex flex-wrap gap-4 py-4 border-b border-gray-200">
      <BaseButton :disabled="loader.isLoading('category')" :loading="loader.isLoading('category')"
        @click.prevent="store.sync('category')">Categories Sync
      </BaseButton>
      <BaseButton :disabled="loader.isLoading('subcategory')" :loading="loader.isLoading('subcategory')"
        @click.prevent="store.sync('subcategory')">Sub Categories Sync
      </BaseButton>
    </div>

    <BaseForm :keys="action" @reset="store.handleReset(action.reset)" @submit="store.handleSubmit(action.save)">
      <div class="grid grid-cols-2 gap-4 my-4">
        <InputGroup label="Disable Item Name Sync for Cron" type="toggle">
          <Toggle v-model="store.cron_settings.disable_name_sync" />
        </InputGroup>
        <InputGroup label="Disable Item Price Sync for Cron" type="toggle">
          <Toggle v-model="store.cron_settings.disable_price_sync" />
        </InputGroup>
        <InputGroup label="Disable Item Image Sync for Cron" type="toggle">
          <Toggle v-model="store.cron_settings.disable_image_sync" />
        </InputGroup>
        <InputGroup label="Disable Product Description Sync for Cron" type="toggle">
          <Toggle v-model="store.cron_settings.disable_description_sync" />
        </InputGroup>
        <InputGroup label="Cron Interval">
          <SelectInput v-model="store.cron_settings.zi_cron_interval" :options="store.intervals" />
        </InputGroup>
      </div>
      <div class="flex flex-wrap items-center gap-4 pt-2 pb-4 bg-white border-b border-gray-200">
        <InputGroup label="Select Categories" type="toggle">
          <div class="pl-4">
            <input v-if="Object.keys(store.zoho_categories).length" id="toggle-all" type="checkbox"
              @change="store.toggleSelectAll" />
          </div>
        </InputGroup>
      </div>

      <div class="grid h-64 grid-cols-1 gap-4 overflow-hidden">
        <div class="overflow-y-scroll scrollbar-hide">
          <div class="grid grid-cols-1 gap-4 my-4">
            <div v-for="(category, index) in store.zoho_categories" :key="index" class="inline-flex mr-4">
              <input v-if="category" v-model="category.selected" :value="index" type="checkbox" class="ml-1" />
              <label class="ml-4 mr-4" v-html="category.label"></label>
            </div>
          </div>
        </div>
      </div>

    </BaseForm>
  </div>
</template>
