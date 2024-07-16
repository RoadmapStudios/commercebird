<script lang="ts" setup>
import { LinkIcon, TruckIcon,TableCellsIcon } from "@heroicons/vue/24/outline";
import { onBeforeMount, watchEffect } from "vue";
import TabComponent from "@/components/ui/tabs/TabComponent.vue";
import RequiredNotice from "@/components/ui/RequiredNotice.vue";

import { acf_enabled, notify, redirect_uri } from "@/composable/helpers";
import Connect from "@/components/forms/zoho-crm/Connect.vue";
import Order from "@/components/forms/zoho-crm/Order.vue";
import Field from "@/components/forms/zoho-crm/Field.vue";
import { useZohoCrmStore } from "@/stores/zohoCrm";
import { ajaxUrl } from "@/composable/http";

const store = useZohoCrmStore();

let currentURL = new URL(location.href, redirect_uri);
let hasCode = currentURL.searchParams.has("code");

watchEffect(() => {
  if (hasCode) {
    notify.success("Verifying your connection, please wait.");
    let code = currentURL.searchParams.get("code");
    fetch(`${ajaxUrl("zcrm_handle_code")}&code=${code}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          location.href = redirect_uri;
          return;
        }
      });
  }
});

const tabs = {
    connect: { title: "Connect", component: Connect, icon: LinkIcon },
    order: { title: "Orders", component: Order, icon: TruckIcon },
    field: { title: "Custom Fields", component: Field, icon: TableCellsIcon },
};

onBeforeMount(() => {
    store.isConnectionValid();
    store.selectedTab = "connect";
});
</script>

<template>
    <div>
        <RequiredNotice v-if="!acf_enabled" name="ACF" slug="advanced-custom-fields" type="plugin" />
        <TabComponent :tabs="tabs" v-model="store.selectedTab" />
    </div>
</template>
