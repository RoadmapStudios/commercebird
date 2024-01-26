<template>
  <div>
    <BaseForm v-if="b2b_enabled && useHomepageStore().subscription.variation_id.includes(18)"
              :keys="action" @reset="store.handleReset(action.reset)" @submit="store.handleSubmit(action.save)">
      <InputGroup v-if="b2b_enabled" label="Zoho Price List">
        <SelectInput
            v-model="store.price_settings.zoho_inventory_pricelist"
            :options="store.zoho_prices"
        />
      </InputGroup>
      <InputGroup v-if="Object.keys(roles).length" label="Users Role">
        <SelectInput
            v-model="store.price_settings.wp_user_role"
            :options="roles"
        />
      </InputGroup>
    </BaseForm>
    <div v-else>
      <Alert :message="message" target="_blank"/>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {b2b_enabled, roles} from "@/composable/helpers";
import {ExclamationCircleIcon} from "@heroicons/vue/24/outline";
import InputGroup from "../../ui/inputs/InputGroup.vue";
import SelectInput from "../../ui/inputs/SelectInput.vue";
import Alert from "../../ui/Alert.vue";
import {useZohoInventoryStore} from "@/stores/zohoInventory";
import {useHomepageStore} from "@/stores/homepage";
import type {Message} from "@/types";
import BaseForm from "@/components/ui/BaseForm.vue";
import {backendAction} from "@/keys";
const action = backendAction.zohoInventory.price;
const message: Message = {
  icon: ExclamationCircleIcon,
  message:
      "Please upgrade your plan for this feature and/or ensure the B2B plugin is active",
  link: "",
  linkText: "",
};
const store = useZohoInventoryStore();
</script>
