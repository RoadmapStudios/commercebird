<template>
    <div>
        <BaseForm :keys="action" @reset="store.handleReset(action.reset)" @submit="store.handleSubmit(action.save)">
            <div v-if="store.isConnected">
                <Alert v-for="(hint, index) in hints" :key="index" :message="hint" target="_blank"/>
            </div>
            <InputGroup flexed label="Account Domain">
                <SelectInput v-model="store.connection.account_domain" :options="accountDomains" />
                <BaseLink v-if="store.connection.account_domain"
                    :href="`https://api-console.zoho.${store.connection.account_domain}/`" rel="noopener noreferrer"
                    target="_blank">
                    Access zoho console
                </BaseLink>
            </InputGroup>
            <InputGroup label="Client ID">
                <TextInput v-model="store.connection.client_id" />
            </InputGroup>
            <InputGroup label="Client Secret">
                <TextInput v-model="store.connection.client_secret" />
            </InputGroup>
            <CopyableInput :value="store.connection.redirect_uri" />
        </BaseForm>
    </div>
</template>
<script lang="ts" setup>
import { ExclamationCircleIcon } from "@heroicons/vue/24/outline";
import TextInput from "@/components/ui/inputs/TextInput.vue";
import SelectInput from "@/components/ui/inputs/SelectInput.vue";
import CopyableInput from "@/components/ui/inputs/CopyableInput.vue";
import InputGroup from "@/components/ui/inputs/InputGroup.vue";
import BaseLink from "@/components/ui/BaseLink.vue";
import BaseForm from "@/components/ui/BaseForm.vue";
import { backendAction, storeKey } from "@/keys";
import {redirect_uri} from "@/composable/helpers";
import { useZohoCrmStore } from "@/stores/zohoCrm";
import { useLoadingStore } from "@/stores/loading";
import { computed, onBeforeMount, ref } from "vue";

const accountDomains = {
    com: "com",
    eu: "eu",
    in: "in",
    jp: "jp",
    au: "com.au",
    cn: "com.cn",
};

const store = useZohoCrmStore();
const loader = useLoadingStore();
const action = backendAction.zohoCrm.connect;

const hints = ref({
    pluginDoc: {
        icon: ExclamationCircleIcon,
        message:
            "Please read the documentation first before you use this plugin and make sure to enable automatic updates for this plugin!",
        link: "https://support.commercebird.com/portal/en/kb/zoho-crm-woocommerce",
        linkText: "Visit Here",
    },
});


onBeforeMount(async () => {
  const response = await loader.loadData(
      storeKey.zohoCrm.connected,
      backendAction.zohoCrm.connection
  );
  if (response) {
    store.connection.client_id = response.client_id;
    store.connection.client_secret = response.client_secret;
    store.connection.redirect_uri = redirect_uri;
    store.connection.account_domain = response.account_domain;
  }
  await store.isConnectionValid();
});

</script>
