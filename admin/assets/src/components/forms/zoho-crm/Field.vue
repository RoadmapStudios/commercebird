<template>
 
<div class="flex items-center justify-center flex-1 sm:items-stretch sm:justify-start h-16  border-b-2 sm:space-x-8">
  <div v-for="(tab,index) in tabs" :key="index" class="hidden sm:flex sm:space-x-8 gap-2 text-gray-500" >
    <div @click="selectedTab = tab" :class="{ 'border-b-2 border-indigo-500 text-gray-900': selectedTab.name === tab.name }"
      class="inline-flex items-center px-1 pt-1 text-sm font-medium focus:ring-0 cursor-pointer hover:text-gray-700"
    >
      {{ tab.name }}

    </div>
  </div>
</div>
    <BaseForm :keys="action" @reset="store.handleReset(action.reset)" @submit="store.handleSubmit(action.save)">
      <div class="pt-4 pb-2 flex justify-between items-center tracking-tight">
        <h1 class="text-xl font-bold">{{selectedTab.title}}</h1>
        <BaseButton @click="store.addField()">Add Item</BaseButton>
      </div>
      <div v-for="(field, index) in store.fields" :key="index" class="grid items-end gap-4 sm:grid-cols-5">
        <InputGroup label="WooCommerce Field" type="repeater">
          <SelectInput v-model="field.key" :options="store.customFields"/>
        </InputGroup>
        <InputGroup label="Zoho Field Label" type="repeater">
          <TextInput v-model="field.value"/>
        </InputGroup>
        <div class="pb-[11px]">
          <BaseButton @click="store.removeField(index)">Remove</BaseButton>
        </div>
      </div>
    </BaseForm>
  </template>
  
  <script lang="ts" setup>
  import { ref,onUpdated } from 'vue'
  import {backendAction} from "@/keys";
  import {useLoadingStore} from "@/stores/loading";
  import { useZohoCrmStore } from '@/stores/zohoCrm';
  import InputGroup from "../../ui/inputs/InputGroup.vue";
  import TextInput from "../../ui/inputs/TextInput.vue";
  import BaseButton from "../../ui/BaseButton.vue";
  import SelectInput from "../../ui/inputs/SelectInput.vue";
  import BaseForm from "@/components/ui/BaseForm.vue";
  
  const action = backendAction.zohoCrm.field;
  const store = useZohoCrmStore();
  const loader = useLoadingStore();
  const tabs = {
    orders: { name:"Orders",title: "Orders Custom Fields"},
    contacts: { name:"Contacts",title: "Contacts Custom Fields"},
    products: { name:"Products",title: "Products Custom Fields"},

  };
  let selectedTab =ref(tabs.orders) ;
  onUpdated(()=>{
    console.log("selected tab", selectedTab.value);

  })
  
  </script>
  
  







































