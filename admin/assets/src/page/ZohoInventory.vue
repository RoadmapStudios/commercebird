<script lang="ts" setup>
import {
  ArchiveBoxIcon,
  ClockIcon,
  CurrencyDollarIcon,
  LinkIcon,
  SwatchIcon,
  TableCellsIcon,
  TruckIcon,
} from "@heroicons/vue/24/outline";
import Tax from "@/components/forms/zoho/Tax.vue";
import Product from "@/components/forms/zoho/Product.vue";
import Cron from "@/components/forms/zoho/Cron.vue";
import Orders from "@/components/forms/zoho/Order.vue";
import Price from "@/components/forms/zoho/Price.vue";
import Field from "@/components/forms/zoho/Field.vue";
import Connect from "@/components/forms/zoho/Connect.vue";
import Webhooks from "@/components/forms/zoho/Webhooks.vue";
import {fileinfo_enabled} from "@/composable/helpers";
import {useZohoInventoryStore} from "@/stores/zohoInventory";
import {onBeforeMount} from "vue";
import TabComponent from "@/components/ui/tabs/TabComponent.vue";
import RequiredNotice from "@/components/ui/RequiredNotice.vue";

const store = useZohoInventoryStore();

const tabs = {
  connect: {title: "Connect", component: Connect, icon: LinkIcon},
  tax: {title: "Tax", component: Tax, icon: CurrencyDollarIcon},
  product: {title: "Product", component: Product, icon: ArchiveBoxIcon},
  cron: {title: "Cron", component: Cron, icon: ClockIcon},
  order: {title: "Orders", component: Orders, icon: TruckIcon},
  price: {title: "Price List", component: Price, icon: SwatchIcon},
  field: {title: "Custom Fields", component: Field, icon: TableCellsIcon},
  webhooks: {title: "Webhooks", component: Webhooks, icon: LinkIcon},
};
onBeforeMount(() => {
  store.isConnectionValid();
  store.selectedTab = "connect";
});
</script>

<template>
  <div>
    <RequiredNotice
        v-if="!fileinfo_enabled"
        message='Please activate the PHP module
        <span class="font-medium">"fileinfo"</span> to import Product Images
    from Zoho Inventory. This can be activated via your hosting cPanel or
    please contact your hosting for this activation.'
    />

    <TabComponent v-model="store.selectedTab" :tabs="tabs"/>
  </div>
</template>
