<script setup lang="ts">

import TextInput from "@/components/ui/inputs/TextInput.vue";
import InputGroup from "@/components/ui/inputs/InputGroup.vue";
import {useExactOnlineStore} from "@/stores/exactOnline";
import BaseLink from "@/components/ui/BaseLink.vue";
import BaseForm from "@/components/ui/BaseForm.vue";
import {backendAction} from "@/keys";

const store = useExactOnlineStore();
const action = backendAction.exactOnline.connect;
</script>

<template>
  <BaseForm @submit="store.handleSubmit(action.save)"
            @reset="store.handleReset(action.reset)"
            :keys="action">
    <InputGroup label="CommerceBird Token" flexed>
      <TextInput v-model="store.connection.token"/>
      <BaseLink href="/wp-admin/admin.php?page=wc-settings&tab=advanced&section=webhooks"
                rel="noopener noreferrer"
                target="_blank"
      >
        Copy Token from here
      </BaseLink>
    </InputGroup>
    <InputGroup label="Active Site URL" flexed>
      <TextInput v-model="store.connection.site" disabled/>
      <BaseLink href="https://app.commercebird.com/integrations"
                rel="noopener noreferrer"
                target="_blank"
      >
        Access App Console
      </BaseLink>
    </InputGroup>
  </BaseForm>
</template>

<style scoped>

</style>