<template>
  <PageMenu/>
  <div class="hidden px-4 mx-auto sm:px-8 sm:block sm:py-6 max-w-7xl">
    <h1 class="text-3xl font-bold tracking-tight">
      {{ useRouter().currentRoute.value.name }}
    </h1>
  </div>
  <div class="max-w-screen-xl px-4 mx-auto sm:px-8 mt-4 sm:mt-0">
    <router-view/>
  </div>
</template>
<script lang="ts" setup>
import PageMenu from "@/components/parts/PageMenu.vue";
import {useRouter} from "vue-router";
</script>