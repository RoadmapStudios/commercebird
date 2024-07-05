<template>
  <BaseForm :keys="action" @reset="store.handleReset(action.reset)" @submit="store.handleSubmit(action.save)">
    <div class="flex items-center justify-between pt-4 pb-2 tracking-tight border-b">
      <h1 class="text-xl font-bold">Orders Custom Fields</h1>
      <BaseButton @click="store.addField()">Add Item</BaseButton>
    </div>
    <div v-for="(field, index) in store.fields" :key="index" class="grid items-end gap-4 sm:grid-cols-5">
      <InputGroup label="WooCommerce Field" type="repeater">
        <SelectInput v-model="field.key" :options="store.customFields" />
        <TextInput v-model="field.key" placeholder="Or type custom field" />
      </InputGroup>
      <InputGroup label="Zoho Field Label" type="repeater">
        <TextInput v-model="field.value" />
      </InputGroup>
      <div class="pb-[11px]">
        <BaseButton @click="store.removeField(index)">Remove</BaseButton>
      </div>
    </div>
  </BaseForm>
</template>

<script lang="ts" setup>
import {backendAction} from "@/keys";
import {useLoadingStore} from "@/stores/loading";
import {useZohoInventoryStore} from "@/stores/zohoInventory";
import InputGroup from "../../ui/inputs/InputGroup.vue";
import TextInput from "../../ui/inputs/TextInput.vue";
import BaseButton from "../../ui/BaseButton.vue";
import SelectInput from "../../ui/inputs/SelectInput.vue";
import BaseForm from "@/components/ui/BaseForm.vue";

const action = backendAction.zohoInventory.field;
const store = useZohoInventoryStore();
const loader = useLoadingStore();
</script>

