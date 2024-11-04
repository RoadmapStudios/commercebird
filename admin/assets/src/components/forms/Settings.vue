<script lang="ts" setup>

import {useHomepageStore} from "@/stores/homepage";
import {useLoadingStore} from "@/stores/loading";
import {backendAction} from "@/keys";
import Card from "@/components/ui/Card.vue";
import LoaderIcon from "@/components/ui/LoaderIcon.vue";
import InputGroup from "@/components/ui/inputs/InputGroup.vue";
import Toggle from "@/components/ui/inputs/Toggle.vue";
import TextInput from "@/components/ui/inputs/TextInput.vue";
import BaseButton from "@/components/ui/BaseButton.vue";

const action = backendAction.homepage.settings
const store = useHomepageStore()
const loader = useLoadingStore()
</script>

<template>
  <Card :foot="true" title="Settings">
    <template #action>
      <LoaderIcon :loading="loader.isLoading(backendAction.homepage.settings.get)"/>
    </template>
    <div class=" px-4 space-y-4 mb-[50px]">
      <InputGroup label="Enable CORS">
        <Toggle v-model="store.settings.cors"/>
      </InputGroup>
      <InputGroup :invalid="store.invalidId" error-message="Please enter a valid subscription ID"
                  label="Subscription ID">
        <TextInput v-model="store.settings.id" :invalid="store.invalidId"/>
      </InputGroup>
    </div>

    <template #footer>
      <BaseButton :loading="loader.isLoading(action.save)" @click.prevent="store.save_settings()">Save
      </BaseButton>
      <BaseButton :loading="loader.isLoading(action.reset)" type="lite"
                  @click.prevent="store.reset_settings()">
        Reset
      </BaseButton>
    </template>
  </Card>
</template>
