<script lang="ts" setup>
import {CurrencyDollarIcon, LinkIcon, ShoppingBagIcon, UsersIcon} from "@heroicons/vue/24/outline";
import {onBeforeMount} from "vue";
import {useExactOnlineStore} from "@/stores/exactOnline";
import TabComponent from "@/components/ui/tabs/TabComponent.vue";
import Connect from "@/components/forms/exact-online/Connect.vue";
import Product from "@/components/forms/exact-online/Product.vue";
import RequiredNotice from "@/components/ui/RequiredNotice.vue";

import {acf_enabled} from "@/composable/helpers";
import Cost from "@/components/forms/exact-online/Cost.vue";
import Customer from "@/components/forms/exact-online/Customer.vue";

const store = useExactOnlineStore()


const tabs = {
  connect: {title: "Connect", component: Connect, icon: LinkIcon},
  product: {title: "Products", component: Product, icon: ShoppingBagIcon},
  customers: {title: "Customers", component: Customer, icon: UsersIcon},
  cost_center_unit: {title: "Cost Centers/Units", component: Cost, icon: CurrencyDollarIcon},
};


onBeforeMount(() => {
  store.selectedTab = "connect";
});
</script>

<template>
  <div>
    <RequiredNotice
        v-if="!acf_enabled"
        message='Please install and activate <a href="/wp-admin/plugin-install.php?s=acf&tab=search&type=term" class="font-medium">ACF</a> plugin'
    />
    <TabComponent :tabs="tabs" v-model="store.selectedTab"/>
  </div>
</template>
