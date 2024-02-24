<template>
  <div>
    <Alert v-if="!isPremiumSubscription" :message="subscriptionMessage" target="_blank" type="warning" />
    <div v-else>
      <CopyableInput v-for="(webhook, index) in webhooks" :key="index" @copy="copy(webhook)" :value="webhook"
        :label="`${index} Webhook URL`" />
      <CopyableInput :value="api_token" label="Auth Header Token"
        :hint="`Use this as <strong>Authorization</strong> header in your webhook`" />
    </div>
  </div>
</template>
<script lang="ts" setup>
import { api_token, webhooks } from "@/composable/helpers";
import { useClipboard } from "@vueuse/core";
import CopyableInput from "@/components/ui/inputs/CopyableInput.vue";
import Alert from "@/components/ui/Alert.vue";
import { ExclamationCircleIcon } from "@heroicons/vue/24/outline";
import { useHomepageStore } from "@/stores/homepage";
import { onBeforeMount } from "vue";


const subscriptionMessage = {
  icon: ExclamationCircleIcon,
  message: 'Please upgrade to Premium plan to access this feature',
  link: 'https://commercebird.com/my-account',
  linkText: 'Upgrade Now',
}

const { isPremiumSubscription } = useHomepageStore();


const { copy, copied } = useClipboard()
</script>