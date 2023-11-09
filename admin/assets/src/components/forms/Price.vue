<template>
  <div>
    <div v-if="b2b_enabled">
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
      <div class="flex gap-4 mt-8">
        <BaseButton
          :loading="loader.isLoading(backendAction.save_price)"
          @click="store.handleSubmit(backendAction.save_price)"
        >
          Save
        </BaseButton>
        <BaseButton
          :loading="loader.isLoading(backendAction.reset_price)"
          type="lite"
          @click="store.handleReset(backendAction.reset_price)"
        >
          Reset
        </BaseButton>
      </div>
    </div>
    <div v-else>
      <Alert :message="message" target="_blank" />
    </div>
  </div>
</template>

<script lang="ts" setup>
import { b2b_enabled, backendAction, roles } from "@/composables";
import { ExclamationCircleIcon } from "@heroicons/vue/24/outline";
import InputGroup from "../ui/InputGroup.vue";
import SelectInput from "../ui/inputs/SelectInput.vue";
import BaseButton from "../ui/BaseButton.vue";
import Alert from "../ui/Alert.vue";
import { useZohoInventoryStore } from "@/stores/zohoInventory";
import { useLoadingStore } from "@/stores/loading";
import type { Message } from "../../composables";

const message: Message = {
  icon: ExclamationCircleIcon,
  message:
    "Please upgrade your plan for this feature and/or ensure the B2B plugin is active",
  link: "",
  linkText: "",
};
const store = useZohoInventoryStore();
const loader = useLoadingStore();
</script>
