<template>
  <div class="grid gap-4 lg:grid-cols-2">
    <Settings />
    <Subscription />
    <CustomDevelopment />
    <div class="gap-4 px-2">
      <AppLogin />
      <Support />
    </div>
    <!-- Next component here -->
  </div>
</template>
<script lang="ts" setup>
import Subscription from "@/components/parts/Subscription.vue";
import Settings from "@/components/forms/Settings.vue";
import AppLogin from "@/components/parts/AppLogin.vue";
import Support from "@/components/parts/Support.vue";
import CustomDevelopment from "@/components/parts/CustomDevelopment.vue";
import { onMounted } from "vue";
import { useHomepageStore } from "@/stores/homepage";

const store = useHomepageStore();
onMounted(store.load);
</script>
