<script setup lang="ts">
import TextInput from "@/components/ui/inputs/TextInput.vue";
import InputGroup from "@/components/ui/inputs/InputGroup.vue";
import { useExactOnlineStore } from "@/stores/exactOnline";
import BaseLink from "@/components/ui/BaseLink.vue";
import BaseForm from "@/components/ui/BaseForm.vue";
import { backendAction } from "@/keys";
import TokenImage from "@/components/TokenImage.vue";
import { QuestionMarkCircleIcon, XMarkIcon } from "@heroicons/vue/24/outline";
import { ref } from "vue";

const store = useExactOnlineStore();
const action = backendAction.exactOnline.connect;
const showHint = ref(false);
</script>

<template>
  <BaseForm
    @submit="store.handleSubmit(action.save)"
    @reset="store.handleReset(action.reset)"
    :keys="action"
  >
    <InputGroup label="CommerceBird Token" flexed>
      <div class="flex flex-1">
        <TextInput v-model="store.connection.token" />
        <button
          type="button"
          @click="showHint = !showHint"
          class="w-[2.875rem] h-[2.875rem] flex-shrink-0 inline-flex justify-center items-center gap-x-2 text-sm font-semibold border border-transparent disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-0 dark:focus:ring-gray-600"
        >
          <QuestionMarkCircleIcon v-if="!showHint" />
          <XMarkIcon v-else />
        </button>
      </div>
      <BaseLink
        href="/wp-admin/admin.php?page=wc-settings&tab=advanced&section=webhooks"
        rel="noopener noreferrer"
        target="_blank"
      >
        Copy Token from here
      </BaseLink>
    </InputGroup>
    <InputGroup label="Active Site URL" flexed>
      <TextInput v-model="store.connection.site" disabled />
      <BaseLink
        href="https://app.commercebird.com/integrations"
        rel="noopener noreferrer"
        target="_blank"
      >
        Access App Console
      </BaseLink>
    </InputGroup>
  </BaseForm>
  <div class="py-4 mt-6 space-y-4" v-if="showHint">
    <h1 class="text-xl font-bold tracking-tight text-gray-600">
      How to copy token from webhook url ?
    </h1>
    <TokenImage />
  </div>
</template>

<style scoped></style>
