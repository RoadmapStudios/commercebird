<template>
  <div>
    <BaseForm :keys="action" submit-label="Connect" @reset="store.handleReset(action.reset)"
      @submit="store.handleSubmit(action.save)">
      <div v-if="store.isConnected">
        <Alert v-for="(hint, index) in hints" :key="index" :message="hint" target="_blank" />
      </div>
      <!-- Account Information -->
      <InputGroup flexed label="Account Domain">
        <SelectInput v-model="store.connection.account_domain" :options="accountDomains" />
        <BaseLink v-if="store.connection.account_domain"
          :href="`https://api-console.zoho.${store.connection.account_domain}/`" rel="noopener noreferrer"
          target="_blank">
          Access Zoho Console
        </BaseLink>
      </InputGroup>

      <InputGroup label="Organization ID">
        <TextInput v-model="store.connection.organization_id" />
      </InputGroup>
      <InputGroup label="Client ID">
        <TextInput v-model="displayedClientId" @focus="handleFocus('client_id')" @blur="handleBlur('client_id')"
          @input="handleInput($event, 'client_id')" />
      </InputGroup>
      <InputGroup label="Client Secret">
        <TextInput v-model="displayedClientSecret" @focus="handleFocus('client_secret')"
          @blur="handleBlur('client_secret')" @input="handleInput($event, 'client_secret')" />
      </InputGroup>
      <CopyableInput :value="store.connection.redirect_uri" />
    </BaseForm>
    <!-- Reset Button -->
    <button type="button" @click="store.handleReset(action.reset, true)"
      class="px-4 py-2 font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
      title="Reset all when switching to different Organization">
      Reset All
    </button>
    <!-- ✅ API USAGE CARD -->
    <div v-if="store.isConnected" class="p-6 mt-6 bg-white rounded-lg shadow-md">
      <h3 class="mb-2 text-lg font-semibold">API Usage as of Today</h3>

      <!-- Progress Bar -->
      <div class="w-full bg-gray-200 rounded-full h-3.5 mb-4">
        <div class="h-3.5 rounded-full"
          :class="{ 'bg-red-500': apiUsageUsedPercentage > 80, 'bg-green-500': apiUsageUsedPercentage <= 80 }"
          :style="{ width: apiUsageRemainingPercentage + '%' }">
        </div>
      </div>

      <p class="text-sm text-gray-600">
        {{ store.isConnected.remaining_api_count }} APIs are available out of
        {{ store.isConnected.maximum_api_count }} APIs
      </p>

      <!-- API Usage Stats -->
      <div class="flex justify-between mt-4 text-center">
        <div>
          <p class="text-xl font-semibold">{{ store.isConnected.maximum_api_count }}</p>
          <p class="text-sm text-gray-500">Total API Calls</p>
        </div>
        <div>
          <p class="text-xl font-semibold">{{ store.isConnected.total_api_count }}</p>
          <p class="text-sm text-gray-500">Used API Calls</p>
        </div>
        <div>
          <p class="text-xl font-semibold">{{ store.isConnected.remaining_api_count }}</p>
          <p class="text-sm text-gray-500">Remaining API Calls</p>
        </div>
      </div>
    </div>
  </div>
</template>
<script lang="ts" setup>
import { ExclamationCircleIcon } from "@heroicons/vue/24/outline";
import { useZohoInventoryStore } from "@/stores/zohoInventory";
import { useLoadingStore } from "@/stores/loading";
import InputGroup from "../../ui/inputs/InputGroup.vue";
import TextInput from "../../ui/inputs/TextInput.vue";
import SelectInput from "../../ui/inputs/SelectInput.vue";
import Alert from "@/components/ui/Alert.vue";
import { redirect_uri } from "@/composable/helpers";
import { onBeforeMount, computed, ref, watch } from "vue";
import CopyableInput from "@/components/ui/inputs/CopyableInput.vue";
import { backendAction, storeKey } from "@/keys";
import BaseLink from "@/components/ui/BaseLink.vue";
import BaseForm from "@/components/ui/BaseForm.vue";

const store = useZohoInventoryStore();
const loader = useLoadingStore();

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
  au: "com.au",
  cn: "com.cn",
  jp: "jp",
  ca: "ca",
  sa: "sa",
};

const action = backendAction.zohoInventory.connect;

const apiUsageUsedPercentage = computed(() => {
  return store.isConnected?.maximum_api_count
    ? (store.isConnected.total_api_count / store.isConnected.maximum_api_count) * 100
    : 0;
});

const apiUsageRemainingPercentage = computed(() => {
  return store.isConnected?.maximum_api_count
    ? (store.isConnected.remaining_api_count / store.isConnected.maximum_api_count) * 100
    : 0;
});

const showFullClientId = ref(false);
const showFullClientSecret = ref(false);

const displayedClientId = ref('');
const displayedClientSecret = ref('');

const maskString = (keyValue: string): string => {
  if (!keyValue) return '';
  return `${'*'.repeat(keyValue.length - 5)}${keyValue.slice(-5)}`;
};

// Watch store values and initialize displayed values
watch(() => store.connection.client_id, (newValue) => {
  displayedClientId.value = newValue ? maskString(newValue) : '';
});

watch(() => store.connection.client_secret, (newValue) => {
  displayedClientSecret.value = newValue ? maskString(newValue) : '';
});

// Handle focus (show full value)
const handleFocus = (field: 'client_id' | 'client_secret') => {
  if (field === 'client_id') {
    showFullClientId.value = true;
    displayedClientId.value = store.connection.client_id;
  } else {
    showFullClientSecret.value = true;
    displayedClientSecret.value = store.connection.client_secret;
  }
};

// Handle blur (mask value again)
const handleBlur = (field: 'client_id' | 'client_secret') => {
  if (field === 'client_id') {
    showFullClientId.value = false;
    displayedClientId.value = maskString(store.connection.client_id);
  } else {
    showFullClientSecret.value = false;
    displayedClientSecret.value = maskString(store.connection.client_secret);
  }
};

// Handle input (update actual value)
const handleInput = (event: Event, field: 'client_id' | 'client_secret') => {
  const inputValue = (event.target as HTMLInputElement).value;
  if (field === 'client_id') {
    store.connection.client_id = inputValue;
  } else {
    store.connection.client_secret = inputValue;
  }
};

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

    // ✅ Store API usage details in store.isConnected
    store.isConnected.value = {
      ...store.isConnected.value,
      api_usage: response.total_api_count || 0,
      api_limit: response.maximum_api_count || 0,
      api_remaining: response.remaining_api_count || 0,
    };
  }
});
</script>
