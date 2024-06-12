<script lang="ts" setup>
import {
  CurrencyDollarIcon,
  LinkIcon,
  ShoppingBagIcon,
  UsersIcon,
  TruckIcon,
} from "@heroicons/vue/24/outline";
import { onBeforeMount } from "vue";
import { useExactOnlineStore } from "@/stores/exactOnline";
import TabComponent from "@/components/ui/tabs/TabComponent.vue";
import Connect from "@/components/forms/exact-online/Connect.vue";
import Product from "@/components/forms/exact-online/Product.vue";
import RequiredNotice from "@/components/ui/RequiredNotice.vue";

import { acf_enabled } from "@/composable/helpers";
import Cost from "@/components/forms/exact-online/Cost.vue";
import Customer from "@/components/forms/exact-online/Customer.vue";
import Order from "@/components/forms/exact-online/Order.vue";
import Webhooks from "@/components/forms/exact-online/Webhooks.vue";
const store = useExactOnlineStore();

const tabs = {
  connect: { title: "Connect", component: Connect, icon: LinkIcon },
  product: { title: "Products", component: Product, icon: ShoppingBagIcon },
  order: { title: "Orders", component: Order, icon: TruckIcon },
  customers: { title: "Customers", component: Customer, icon: UsersIcon },
  cost_center_unit: {
    title: "Cost Centers/Units",
    component: Cost,
    icon: CurrencyDollarIcon,
  },
  webhooks: { title: "Webhooks", component: Webhooks, icon: LinkIcon },
};

onBeforeMount(() => {
  store.selectedTab = "connect";
});
</script>

<template>
  <div>
    <RequiredNotice
      v-if="!acf_enabled"
      name="ACF"
      slug="advanced-custom-fields"
      type="plugin"
    />
    <TabComponent :tabs="tabs" v-model="store.selectedTab" />
  </div>
</template>
