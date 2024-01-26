<template>
  <div>
    <h1 class="pt-4 pb-2 text-xl font-bold tracking-tight border-b">Orders Custom Fields</h1>
    <div v-for="(field, index) in store.fields" :key="index" class="grid items-end gap-4 sm:grid-cols-5">
      <InputGroup label="WooCommerce Field" type="repeater">
        <SelectInput v-model="field.key" :options="store.customFields" />
      </InputGroup>
      <InputGroup label="Zoho Field Label" type="repeater">
        <TextInput v-model="field.value" />
      </InputGroup>
      <div class="pb-[11px]">
        <BaseButton @click="store.removeField(index)">Remove</BaseButton>
      </div>
    </div>
    <div class="flex justify-between gap-4 mt-8">
      <BaseButton @click="store.addField()">Add Item</BaseButton>
      <div class="flex gap-4">
        <BaseButton :loading="loader.isLoading(backendAction.zohoInventory.field.save)"
          @click="store.handleSubmit(backendAction.zohoInventory.field.save)">
          Save
        </BaseButton>
        <BaseButton :loading="loader.isLoading(backendAction.zohoInventory.field.reset)" type="lite"
          @click="store.handleReset(backendAction.zohoInventory.field.reset)">
          Reset
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import {backendAction} from "@/keys";
import {useLoadingStore} from "@/stores/loading";
import {useZohoInventoryStore} from "@/stores/zohoInventory";
import InputGroup from "../../ui/inputs/InputGroup.vue";
import TextInput from "../../ui/inputs/TextInput.vue";
import BaseButton from "../../ui/BaseButton.vue";
import SelectInput from "../../ui/inputs/SelectInput.vue";

const store = useZohoInventoryStore();
const loader = useLoadingStore();
</script>

