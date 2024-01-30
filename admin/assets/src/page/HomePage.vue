<template>
  <div class="grid gap-4 lg:grid-cols-2">
    <Settings/>
    <Subscription/>
    <AppLogin/>
    <Support/>
  </div>
</template>
<script lang="ts" setup>
import Subscription from "@/components/parts/Subscription.vue";
import Settings from "@/components/forms/Settings.vue";
import AppLogin from "@/components/parts/AppLogin.vue";
import Support from "@/components/parts/Support.vue";
import {onMounted} from "vue";
import {useHomepageStore} from "@/stores/homepage";

const store = useHomepageStore();
onMounted(store.load);

</script>
