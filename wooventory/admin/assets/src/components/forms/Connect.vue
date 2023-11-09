<template>
  <div>
    <div v-if="store.notConnected">
      <Alert
        v-for="(hint, index) in hints"
        :key="index"
        :message="hint"
        target="_blank"
      />
    </div>
    <InputGroup label="Account Domain">
      <div class="flex items-baseline gap-4">
        <SelectInput
          v-model="store.connection.account_domain"
          :options="accountDomains"
        />
        <a
          v-if="store.connection.account_domain"
          :href="`https://api-console.zoho.${store.connection.account_domain}/`"
          class="flex items-center gap-2 px-4 py-[9px] font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:ring-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed"
          rel="noopener noreferrer"
          target="_blank"
        >
          Access zoho console
        </a>
      </div>
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
    <InputGroup label="Authorization Redirect URI">
      <div class="flex rounded-md shadow-sm">
        <TextInput
          v-model="store.connection.redirect_uri"
          :disabled="true"
          :readonly="true"
        />
        <span class="px-4 py-2" @click="copy(store.connection.redirect_uri)">
          <ClipboardDocumentCheckIcon v-if="copied" />
          <ClipboardDocumentIcon v-else />
        </span>
      </div>
    </InputGroup>

    <div class="flex items-center justify-between py-2">
      <LoaderIcon v-if="loader.isLoading('load_connection')" :loading="true" />
      <div v-else class="flex gap-4">
        <BaseButton
          :disabled="store.connectionSettingsInvalid"
          :loading="loader.isLoading(backendAction.save_connect)"
          @click="store.handleSubmit(backendAction.save_connect)"
        >
          Save
        </BaseButton>
        <BaseButton
          :loading="loader.isLoading(backendAction.reset_connect)"
          type="lite"
          @click.prevent="store.handleReset(backendAction.reset_connect)"
          >Reset
        </BaseButton>
      </div>
    </div>
  </div>
</template>
<script lang="ts" setup>
import BaseButton from "@/components/ui/BaseButton.vue";
import {
  ClipboardDocumentCheckIcon,
  ClipboardDocumentIcon,
  ExclamationCircleIcon,
} from "@heroicons/vue/24/outline";
import { useZohoInventoryStore } from "@/stores/zohoInventory";
import { useLoadingStore } from "@/stores/loading";
import InputGroup from "../ui/InputGroup.vue";
import TextInput from "../ui/inputs/TextInput.vue";
import SelectInput from "../ui/inputs/SelectInput.vue";
import Alert from "@/components/ui/Alert.vue";
import LoaderIcon from "@/components/ui/LoaderIcon.vue";
import { useClipboard } from "@vueuse/core";
import { backendAction, redirect_uri, storeKey } from "@/composables";
import { onBeforeMount } from "vue";

const hints = {
  pluginDoc: {
    icon: ExclamationCircleIcon,
    message:
      "Please read the documentation first before you use this plugin and make sure to enable automatic updates for this plugin!",
    link: "https://support.wooventory.com/portal/en/kb/zoho-inventory-woocommerce",
    linkText: "Visit Here",
  },
  zohoDoc: {
    icon: ExclamationCircleIcon,
    message:
      "Please visit the Zoho OAuth Creation documentation page for usage instructions.",
    link: "https://accounts.zoho.com/developerconsole",
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
const { copy, copied } = useClipboard({ source });

onBeforeMount(async () => {
  const response = await store.loadData(
    storeKey.connection,
    backendAction.get_connect
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
