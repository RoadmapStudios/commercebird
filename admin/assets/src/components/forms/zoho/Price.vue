<template>
  <div>
    <Alert v-if="!((helpers.b2b_enabled || helpers.wcb2b_enabled) && homePafe.isPremiumSubscription)" :message="message"
      target="_blank" />
    <div class="flex items-center justify-between pt-4 pb-2 tracking-tight border-b">
      <h1 class="text-xl font-bold">Price List Mapping</h1>
      <BaseButton @click="store.addGroup()">Add Item</BaseButton>
    </div>
    <BaseForm v-if="helpers.b2b_enabled && homePafe.isPremiumSubscription" :keys="action"
      @reset="store.handleReset(action.reset)" @submit="store.handleSubmit(action.save)">
      <InputGroup label="Zoho Price List">
        <SelectInput v-model="store.price_settings.zoho_inventory_pricelist" :options="store.zoho_prices" />
      </InputGroup>
      <InputGroup v-if="Object.keys(roles).length && helpers.b2b_enabled" label="Users Role">
        <SelectInput v-model="store.price_settings.wp_user_role" :options="roles" />
      </InputGroup>
    </BaseForm>
    <BaseForm :keys="action" @reset="store.handleReset(action.reset)" @submit="store.handleSubmit(action.save)">
      <div v-for="(field, index) in store.wcb2b_groups" :key="index" class="grid items-end gap-4 sm:grid-cols-5">
        <InputGroup label="WooCommerce B2B Group" type="repeater">
          <SelectInput v-model="field.key" :options="wcb2b_groups" />
        </InputGroup>
        <InputGroup label="Zoho Price Book" type="repeater">
          <SelectInput v-model="field.value" :options="store.zoho_prices" />
        </InputGroup>
        <div class="pb-[11px]">
          <BaseButton @click="store.removeGroup(index)">Remove</BaseButton>
        </div>
      </div>
    </BaseForm>
  </div>
</template>
<script lang="ts" setup>
import { b2b_enabled, roles, wcb2b_enabled, wcb2b_groups } from "@/composable/helpers";
import { ExclamationCircleIcon } from "@heroicons/vue/24/outline";
import InputGroup from "../../ui/inputs/InputGroup.vue";
import SelectInput from "../../ui/inputs/SelectInput.vue";
import Alert from "../../ui/Alert.vue";
import { useZohoInventoryStore } from "@/stores/zohoInventory";
import { useHomepageStore } from "@/stores/homepage";
import type { Message } from "@/types";
import BaseForm from "@/components/ui/BaseForm.vue";
import { backendAction } from "@/keys";
import BaseButton from "@/components/ui/BaseButton.vue";
const action = backendAction.zohoInventory.price;
const message: Message = {
  icon: ExclamationCircleIcon,
  message:
    "Please upgrade your plan for this feature and/or ensure the B2B plugin is active",
  link: "",
  linkText: "",
};
const store = useZohoInventoryStore();
const homePafe = useHomepageStore();

// load helpers for the component like b2b_enabled, roles, wcb2b_enabled, wcb2b_groups
const helpers = {
  b2b_enabled,
  roles,
  wcb2b_enabled,
  wcb2b_groups,
};
</script>
