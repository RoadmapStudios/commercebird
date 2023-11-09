<script lang="ts" setup>
import { useZohoInventoryStore } from "@/stores/zohoInventory";
import InputGroup from "../ui/InputGroup.vue";
import Toggle from "../ui/inputs/Toggle.vue";
import BaseButton from "../ui/BaseButton.vue";
import { useLoadingStore } from "@/stores/loading";
import { backendAction } from "@/composables";
import SelectInput from "@/components/ui/inputs/SelectInput.vue";

const store = useZohoInventoryStore();
const loader = useLoadingStore();
const pushToStore = (index) => {
  store.selected_categories.push(index);
};
</script>
<template>
  <div>
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
      <InputGroup
        label="Disable Product Description Sync for Cron"
        type="toggle"
      >
        <Toggle v-model="store.cron_settings.disable_description_sync" />
      </InputGroup>
      <InputGroup label="Cron Interval">
        <SelectInput
          v-model="store.cron_settings.zi_cron_interval"
          :options="store.intervals"
        />
      </InputGroup>
    </div>
    <div
      class="flex flex-wrap items-center gap-4 pt-2 pb-4 bg-white border-b border-gray-200"
    >
      <InputGroup label="Select Categories" type="toggle">
        <div class="pl-4">
          <input
            v-if="Object.keys(store.zoho_categories).length"
            type="checkbox"
            id="toggle-all"
            @change="store.toggleSelectAll"
          />
        </div>
      </InputGroup>
    </div>
    <div class="grid grid-cols-2 gap-4 my-4">
      <div
        v-for="(category, index) in store.zoho_categories"
        :key="index"
        class="inline-flex justify-between mr-4"
      >
        <label class="mr-4">{{ category }}</label>
        <input
          v-model="store.selected_categories"
          :value="index"
          type="checkbox"
        />
      </div>
    </div>
    <div class="flex gap-4 mt-8">
      <BaseButton
        :loading="loader.isLoading(backendAction.save_cron)"
        @click="store.handleSubmit"
        @click.prevent="store.handleSubmit(backendAction.save_cron)"
      >
        Save
      </BaseButton>
      <BaseButton
        :loading="loader.isLoading(backendAction.reset_cron)"
        type="lite"
        @click.prevent="store.handleReset(backendAction.reset_cron)"
        >Reset
      </BaseButton>
    </div>
  </div>
</template>
