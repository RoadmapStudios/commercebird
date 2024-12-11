<script lang="ts" setup>
import TextInput from "@/components/ui/inputs/TextInput.vue";
import InputGroup from "@/components/ui/inputs/InputGroup.vue";
import {useExactOnlineStore} from "@/stores/exactOnline";
import BaseLink from "@/components/ui/BaseLink.vue";
import BaseForm from "@/components/ui/BaseForm.vue";
import {backendAction} from "@/keys";

import {QuestionMarkCircleIcon} from "@heroicons/vue/24/outline";
import {ref} from "vue";
import Swal from "sweetalert2";
import {tokenImage} from "@/composable/helpers";

const store = useExactOnlineStore();
const action = backendAction.exactOnline.connect;
const showHint = ref(false);
const handleClick = () => {
  Swal.fire({
    target: "#commercebird-app",
    icon: "info",
    title: 'How to copy token from webhook url ?',
    html: `<img src="${tokenImage}" class="h-auto w-fit"/>`,
    customClass: {
      icon: 'p-4 border-4 border-teal-600 rounded-full bg-white text-teal-700',
      title: 'w-full text-xl font-bold tracking-tight text-gray-600 capitalize',
      confirmButton:
          "py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-teal-600 text-white hover:bg-teal-700 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2",
    }
  });
}
</script>

<template>
  <div>
    <BaseForm :keys="action" @reset="store.handleReset(action.reset)" @submit="store.handleSubmit(action.save)">
      <InputGroup flexed label="CommerceBird Token">
        <div class="flex flex-1">
          <TextInput v-model="store.connection.token"/>
          <button class="w-[2.875rem] h-[2.875rem] flex-shrink-0 inline-flex justify-center items-center gap-x-2 text-sm font-semibold border border-transparent disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-0 dark:focus:ring-gray-600" type="button"
                  @click="showHint = !showHint">
            <component :is="QuestionMarkCircleIcon" @click="handleClick"/>
          </button>
        </div>
        <BaseLink href="/wp-admin/admin.php?page=wc-settings&tab=advanced&section=webhooks" rel="noopener noreferrer"
                  target="_blank">
          Copy Token from here
        </BaseLink>
      </InputGroup>
      <InputGroup flexed label="Active Site URL">
        <TextInput v-model="store.connection.site" disabled/>
        <BaseLink href="https://app.commercebird.com/integrations" rel="noopener noreferrer" target="_blank">
          Access App Console
        </BaseLink>
      </InputGroup>
    </BaseForm>
  </div>
</template>

<style scoped></style>
