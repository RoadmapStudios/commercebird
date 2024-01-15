<template>
  <div>
    <Alert :message="hints" target="_blank" />
    <Alert v-if="!taxEnabled" :message="enableTax" target="_self" />
    <Alert
      v-if="!Object.keys(store.zoho_taxes).length"
      :message="addZohoTax"
      target="_self"
    />
    <Alert
      v-if="!Object.keys(store.wc_taxes).length"
      :message="addWCTax"
      target="_self"
    />
    <div
      v-if="
        taxEnabled &&
        Object.keys(store.zoho_taxes).length &&
        Object.keys(store.wc_taxes).length
      "
    >
      <InputGroup label="Enable Decimal Tax">
        <Toggle v-model="store.tax_settings.decimalTax" />
      </InputGroup>
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
      <InputGroup label="Vat Exempt">
        <SelectInput
          v-model="store.tax_settings.selectedVatExempt"
          :options="store.vatExemptOptions()"
        />
      </InputGroup>
      <div class="flex gap-4 mt-4">
        <BaseButton
          :loading="loader.isLoading(backendAction.save_tax)"
          @click.prevent="store.handleSubmit(backendAction.save_tax)"
          >Save
        </BaseButton>
        <BaseButton
          :loading="loader.isLoading(backendAction.reset_tax)"
          type="lite"
          @click.prevent="store.handleReset(backendAction.reset_tax)"
          >Reset
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import Alert from "@/components/ui/Alert.vue";
import { DocumentIcon } from "@heroicons/vue/24/outline";
import { backendAction, taxEnabled } from "@/composables";
import { useZohoInventoryStore } from "@/stores/zohoInventory";
import { useLoadingStore } from "@/stores/loading";
import InputGroup from "@/components/ui/InputGroup.vue";
import Toggle from "@/components/ui/inputs/Toggle.vue";
import SelectInput from "@/components/ui/inputs/SelectInput.vue";
import BaseButton from "@/components/ui/BaseButton.vue";

const hints = {
  icon: DocumentIcon,
  message: "Please read the Tax Mapping documentation",
  link: "https://support.commercebird.com/portal/en/kb/articles/tax-mapping",
  linkText: "Visit Here",
};

const enableTax = {
  icon: DocumentIcon,
  message:
    "Please enable Taxes in WC Settings with <strong>at least</strong> the Standard rate configured. Tax Mapping is <strong>required</strong> to sync Items.",
  link: "admin.php?page=wc-settings#:~:text=Shop%20country/region-,Enable%20taxes,-Enable%20taxes",
  linkText: "Visit Here",
};

const addZohoTax = {
  icon: DocumentIcon,
  message:
    "You don't have any taxes in Zoho Inventory, Create a standard tax rate! Even if that rate is 0% for your shop.",
  link: "admin.php?page=wc-settings&tab=tax&section=standard",
  linkText: "Visit Here",
};

const addWCTax = {
  icon: DocumentIcon,
  message:
    "You don't have any taxes in WooCommerce, Create a standard tax rate! Even if that rate is 0% for your shop.",
  link: "admin.php?page=wc-settings&tab=tax&section=standard",
  linkText: "Visit Here",
};

const store = useZohoInventoryStore();
const loader = useLoadingStore();
</script>
