<script lang="ts" setup>
import {
  ArchiveBoxIcon,
  ClockIcon,
  CurrencyDollarIcon,
  LinkIcon,
  SwatchIcon,
  TableCellsIcon,
  TruckIcon,
  UserGroupIcon
} from "@heroicons/vue/24/outline";
import Tax from "@/components/forms/zoho/Tax.vue";
import Product from "@/components/forms/zoho/Product.vue";
import Categories from "@/components/forms/zoho/Categories.vue";
import Orders from "@/components/forms/zoho/Order.vue";
import Price from "@/components/forms/zoho/Price.vue";
import Field from "@/components/forms/zoho/Field.vue";
import Connect from "@/components/forms/zoho/Connect.vue";
import Webhooks from "@/components/forms/zoho/Webhooks.vue";
import { cosw_enabled, fileinfo_enabled, notify, redirect_uri } from "@/composable/helpers";
import { useZohoInventoryStore } from "@/stores/zohoInventory";
import { onBeforeMount, watchEffect, type Component } from "vue";
import TabComponent from "@/components/ui/tabs/TabComponent.vue";
import RequiredNotice from "@/components/ui/RequiredNotice.vue";
import { ajaxUrl } from "@/composable/http";
import Contact from "@/components/forms/zoho/Contact.vue";

const store = useZohoInventoryStore();

export interface Tab {
  title: string;
  component: Component;
  icon: Component;
}

/**
 * Represents a collection of tabs with specified titles, components, and icons
 * @typedef {Object.<string, Tab>}
 */
const tabs: Record<string, Tab> = {
  connect: { title: "Connect", component: Connect, icon: LinkIcon },
  tax: { title: "Tax", component: Tax, icon: CurrencyDollarIcon },
  cron: { title: "Categories", component: Categories, icon: ClockIcon },
  product: { title: "Product", component: Product, icon: ArchiveBoxIcon },
  order: { title: "Orders", component: Orders, icon: TruckIcon },
  contact: { title: "Contacts", component: Contact, icon: UserGroupIcon },
  price: { title: "Price List", component: Price, icon: SwatchIcon },
  field: { title: "Custom Fields", component: Field, icon: TableCellsIcon },
  webhooks: { title: "Webhooks", component: Webhooks, icon: LinkIcon },
};
onBeforeMount(() => {
  store.isConnectionValid();
  store.selectedTab = "connect";
});


let currentURL = new URL(location.href, redirect_uri);
let hasCode = currentURL.searchParams.has("code");

watchEffect(() => {
  if (hasCode) {
    notify.success("Verifying your connection, please wait.");
    let code = currentURL.searchParams.get("code");
    fetch(`${ajaxUrl("handle_code")}&code=${code}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          location.href = redirect_uri;
          return;
        }
      });
  }
});
</script>

<template>
  <div>
    <RequiredNotice v-if="!fileinfo_enabled" message='Please activate the PHP module
        <span class="font-medium">"fileinfo"</span> to import Product Images
    from Zoho Inventory. This can be activated via your hosting cPanel or
    please contact your hosting for this activation.' />
    <RequiredNotice v-if="store.selectedTab === 'webhooks' && !cosw_enabled" name="Custom Order Status for WooCommerce"
      slug="custom-order-statuses-woocommerce" type="plugin" />
    <RequiredNotice v-if="!store.isConnected" message="Please connect to Zoho Inventory." />
    <TabComponent v-model="store.selectedTab" :tabs="tabs" />
  </div>
</template>