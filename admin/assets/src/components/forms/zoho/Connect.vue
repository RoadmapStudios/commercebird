<template>
  <BaseForm :keys="action" @reset="store.handleReset(action.reset)" @submit="store.handleSubmit(action.save)">
    <div v-if="store.isConnected">
      <Alert v-for="(hint, index) in hints" :key="index" :message="hint" target="_blank" />
    </div>
    <InputGroup flexed label="Account Domain">
      <SelectInput v-model="store.connection.account_domain" :options="accountDomains" />
      <BaseLink v-if="store.connection.account_domain"
        :href="`https://api-console.zoho.${store.connection.account_domain}/`" rel="noopener noreferrer" target="_blank">
        Access zoho console
      </BaseLink>
    </InputGroup>
    <InputGroup label="Organization ID">
      <TextInput v-model="store.connection.organization_id" />
    </InputGroup>
    <InputGroup label="Client ID">
      <TextInput v-model="store.connection.client_id" />
    </InputGroup>
    <InputGroup label="Client Secret">
      <TextInput v-model="store.connection.client_secret" />
    </InputGroup>
    <CopyableInput :value="store.connection.redirect_uri" />
  </BaseForm>
</template>
<script lang="ts" setup>
import { ExclamationCircleIcon } from "@heroicons/vue/24/outline";
import { useZohoInventoryStore } from "@/stores/zohoInventory";
import { useLoadingStore } from "@/stores/loading";
import InputGroup from "../../ui/inputs/InputGroup.vue";
import TextInput from "../../ui/inputs/TextInput.vue";
import SelectInput from "../../ui/inputs/SelectInput.vue";
import Alert from "@/components/ui/Alert.vue";
import { useClipboard } from "@vueuse/core";
import { redirect_uri } from "@/composable/helpers";
import { onBeforeMount } from "vue";
import CopyableInput from "@/components/ui/inputs/CopyableInput.vue";
import { backendAction, storeKey } from "@/keys";
import BaseLink from "@/components/ui/BaseLink.vue";
import BaseForm from "@/components/ui/BaseForm.vue";

const hints = {
  pluginDoc: {
    icon: ExclamationCircleIcon,
    message:
      "Please read the documentation first before you use this plugin and make sure to enable automatic updates for this plugin!",
    link: "https://support.commercebird.com/portal/en/kb/zoho-inventory-woocommerce",
    linkText: "Visit Here",
  },
};

const accountDomains = {
  com: "com",
  eu: "eu",
  in: "in",
  "com.au": "com.au",
};

const store = useZohoInventoryStore();
const loader = useLoadingStore();
const source = store.connection.redirect_uri;
const action = backendAction.zohoInventory.connect;
onBeforeMount(async () => {
  const response = await loader.loadData(
    storeKey.zohoInventory.connected,
    backendAction.zohoInventory.connection
  );
  if (response) {
    store.connection.organization_id = response.organization_id;
    store.connection.client_id = response.client_id;
    store.connection.client_secret = response.client_secret;
    store.connection.redirect_uri = redirect_uri;
    store.connection.account_domain = response.account_domain;
  }
});
</script>
