<script lang="ts" setup>
import { LinkIcon, TruckIcon,TableCellsIcon } from "@heroicons/vue/24/outline";
import { onBeforeMount } from "vue";
import TabComponent from "@/components/ui/tabs/TabComponent.vue";
import RequiredNotice from "@/components/ui/RequiredNotice.vue";

import { acf_enabled } from "@/composable/helpers";
import Connect from "@/components/forms/zoho-crm/Connect.vue";
import Order from "@/components/forms/zoho-crm/Order.vue";
import Field from "@/components/forms/zoho-crm/Field.vue";
import { useZohoCrmStore } from "@/stores/zohoCrm";

const store = useZohoCrmStore();


const tabs = {
    connect: { title: "Connect", component: Connect, icon: LinkIcon },
    order: { title: "Orders", component: Order, icon: TruckIcon },
    field: { title: "Custom Fields", component: Field, icon: TableCellsIcon },
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
