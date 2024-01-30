<template>
  <div class="relative pb-6 lg:pb-16">
    <div
        v-if="useStorage().get(storeKey.homepage.subscription) && useStorage().get(storeKey.homepage.subscription).length"
        class="absolute inset-0 z-10"
        @click.prevent="handleClick"
    ></div>
    <div class="overflow-hidden bg-white rounded-lg shadow">
      <div
          class="divide-y divide-gray-200 xl:grid xl:grid-cols-12 xl:divide-y-0 xl:divide-x"
      >
        <TabMenu v-model="selectedTab" :tabs="tabs"/>

        <TabContent v-model="selectedTab" :tabs="tabs"/>
      </div>
    </div>
  </div>
</template>
<script lang="ts" setup>
import TabMenu from "@/components/ui/tabs/TabMenu.vue";
import TabContent from "@/components/ui/tabs/TabContent.vue";
import {notify} from "@/composable/helpers";
import {useHomepageStore} from "@/stores/homepage";
import {storeKey} from "@/keys";
import {useStorage} from "@/composable/storage";

const homepage = useHomepageStore()
const selectedTab = defineModel()
defineProps({
  tabs: Object
})

const handleClick = () => {
  notify.error("Please subscribe to commercebird");
};
</script>