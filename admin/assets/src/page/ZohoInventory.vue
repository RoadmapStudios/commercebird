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
        please contact your hosting for this activation.
      </p>
    </div>
    <div class="relative pb-6 lg:pb-16">
      <div
        v-if="store.notSubscribed && store.selectedTab !== 'connect'"
        class="absolute inset-0 z-10"
        @click.prevent="handleClick"
      ></div>
      <div class="overflow-hidden bg-white rounded-lg shadow">
        <div
          class="divide-y divide-gray-200 xl:grid xl:grid-cols-12 xl:divide-y-0 xl:divide-x"
        >
          <aside class="col-span-2">
            <nav class="space-y-1">
              <button
                v-for="(item, menu) in tabs"
                :key="menu"
                :class="{
                  'border-transparent text-gray-900 hover:bg-gray-50 hover:text-gray-900':
                    store.selectedTab !== menu,
                  'bg-teal-50 border-teal-500 text-teal-700 hover:bg-teal-50 hover:text-teal-700':
                    store.selectedTab === menu,
                }"
                class="flex items-center w-full px-4 py-2 text-sm font-medium border-l-4 group"
                type="button"
                @click.prevent="select(menu)"
              >
                <component
                  :is="item.icon"
                  v-if="item.icon"
                  :class="{
                    'text-teal-500 group-hover:text-teal-500':
                      store.selectedTab === menu,
                    'text-gray-400 group-hover:text-gray-500':
                      store.selectedTab !== menu,
                  }"
                  class="flex-shrink-0 w-6 h-6 mr-3 -ml-1"
                />

                <span class="truncate">{{ item.title }}</span>
              </button>
            </nav>
          </aside>

          <div v-if="store.selectedTab" class="relative col-span-10 px-4 pb-4">
            <div
              v-if="loader.isRunning()"
              class="absolute inset-0 z-10 bg-gray-900 opacity-20"
            ></div>
            <KeepAlive>
              <component :is="tabs[store.selectedTab].component" />
            </KeepAlive>
          </div>
        </div>
      </div>
    </div>
    from Zoho Inventory. This can be activated via your hosting cPanel or
    please contact your hosting for this activation.'
    />

    <TabComponent v-model="store.selectedTab" :tabs="tabs"/>
  </div>
</template>
