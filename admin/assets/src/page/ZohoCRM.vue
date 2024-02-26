<script lang="ts" setup>
import { CurrencyDollarIcon, LinkIcon, ShoppingBagIcon, UsersIcon, TruckIcon } from "@heroicons/vue/24/outline";
import { onBeforeMount } from "vue";
import { useExactOnlineStore } from "@/stores/exactOnline";
import TabComponent from "@/components/ui/tabs/TabComponent.vue";
import RequiredNotice from "@/components/ui/RequiredNotice.vue";

import { acf_enabled } from "@/composable/helpers";
import Connect from "@/components/forms/zoho-crm/Connect.vue";
import Order from "@/components/forms/zoho-crm/Order.vue";

const store = useExactOnlineStore()


const tabs = {
    connect: { title: "Connect", component: Connect, icon: LinkIcon },
    order: { title: "Orders", component: Order, icon: TruckIcon },
};


onBeforeMount(() => {
    store.selectedTab = "connect";
});
</script>

<template>
    <div>
        <RequiredNotice v-if="!acf_enabled" name="ACF" slug="advanced-custom-fields" type="plugin" />
        <TabComponent :tabs="tabs" v-model="store.selectedTab" />
    </div>
</template>
