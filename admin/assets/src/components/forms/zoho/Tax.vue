<template>
  <div>
    <Alert :message="hints" target="_blank"/>
    <Alert v-if="!taxEnabled" :message="enableTax" target="_self"/>
    <Alert v-if="!Object.keys(store.zoho_taxes).length" :message="addZohoTax" target="_self"/>
    <Alert
        v-if="!Object.keys(store.wc_taxes).length"
        :message="addWCTax"
        target="_self"
    />
    <BaseForm
        v-if="
        taxEnabled &&
        Object.keys(store.zoho_taxes).length &&
        Object.keys(store.wc_taxes).length
      " :keys="action" @reset="store.handleReset(action.reset)"
        @submit="store.handleSubmit(action.save)"
    >
      <InputGroup
          v-for="(wc_tax_rate, index) in store.wc_taxes"
          :key="index"
          :label="wc_tax_rate.tax_rate_name"
      >
        <SelectInput
            v-model="store.tax_settings.selectedTaxRates[index]"
            :options="store.taxOptions(wc_tax_rate.id)"
        />
      </InputGroup>
    </BaseForm>
  </div>
</template>

<script lang="ts" setup>
import Alert from "@/components/ui/Alert.vue";
import {DocumentIcon} from "@heroicons/vue/24/outline";
import {taxEnabled} from "@/composable/helpers";
import {backendAction} from "@/keys";
import {useZohoInventoryStore} from "@/stores/zohoInventory";
import {useLoadingStore} from "@/stores/loading";
import InputGroup from "@/components/ui/inputs/InputGroup.vue";
import SelectInput from "@/components/ui/inputs/SelectInput.vue";
import BaseForm from "@/components/ui/BaseForm.vue";

const action = backendAction.zohoInventory.tax;
const hints = {
  icon: DocumentIcon,
  message: "Please read the Tax Mapping documentation",
  link: "https://support.commercebird.com/portal/en/kb/articles/tax-mapping",
  linkText: "Visit Here",
};

const enableTax = {
  icon: DocumentIcon,
  message:
      "Please enable Taxes in WC Settings with <strong>at least</strong> the Standard rate configured. Only do this if you have tax rates configured in Zoho.",
  link: "admin.php?page=wc-settings#:~:text=Shop%20country/region-,Enable%20taxes,-Enable%20taxes",
  linkText: "Visit Here",
};

const addZohoTax = {
  icon: DocumentIcon,
  message:
      "You don't have any taxes in Zoho Inventory, then you don't need to map anything!",
  link: "admin.php?page=wc-settings&tab=tax&section=standard",
  linkText: "Visit Here",
};

const addWCTax = {
  icon: DocumentIcon,
  message:
      "You don't have any taxes in WooCommerce, Create a standard tax rate!",
  link: "admin.php?page=wc-settings&tab=tax&section=standard",
  linkText: "Visit Here",
};

const store = useZohoInventoryStore();
const loader = useLoadingStore();
</script>
