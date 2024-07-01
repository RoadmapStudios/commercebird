<template>
  <div class="grid grid-cols-2 gap-4 my-4">
    <BaseForm
      :keys="action"
      @reset="store.handleReset(action.reset)"
      @submit="store.handleSubmit(action.save)"
    >
      <div>
        <InputGroup label="StockPosition Webhook" type="toggle">
          <Toggle v-model="store.webhook_settings.enable_StockPosition" />
        </InputGroup>
        <InputGroup label="Item Webhook" type="toggle">
          <Toggle v-model="store.webhook_settings.enable_Item" />
        </InputGroup>
      </div>
    </BaseForm>

    <div>
      <!-- <InputGroup>
        <TextInput></TextInput>
        </InputGroup>
        <InputGroup>
        <TextInput></TextInput>
        </InputGroup>
       <InputGroup>
        <TextInput></TextInput>
        </InputGroup> -->
    </div>
  </div>
</template>
<script setup lang="ts">
import BaseForm from "@/components/ui/BaseForm.vue";
import TextInput from "@/components/ui/inputs/TextInput.vue";
import InputGroup from "@/components/ui/inputs/InputGroup.vue";
import Toggle from "@/components/ui/inputs/Toggle.vue";
import { useExactOnlineStore } from "@/stores/exactOnline";
import { backendAction } from "@/keys";
const store = useExactOnlineStore();
const action = backendAction.exactOnline.webhooks;
</script>