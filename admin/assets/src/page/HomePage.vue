<template>
  <div class="grid gap-4 lg:grid-cols-2">
    <Settings />
    <Subscription />
    <AppLogin />
    <Support />
  </div>
</template>
<script lang="ts" setup>
import Subscription from "@/components/parts/Subscription.vue";
import Settings from "@/components/forms/Settings.vue";
import AppLogin from "@/components/parts/AppLogin.vue";
import Support from "@/components/parts/Support.vue";
import { ajaxUrl, notify, redirect_uri } from "@/composables";
import { onMounted, watchEffect } from "vue";
import { useHomepageStore } from "@/stores/homepage";

const store = useHomepageStore();
onMounted(store.load);

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
