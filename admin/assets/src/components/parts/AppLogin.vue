<template>
  <Card :foot="true" title="CommerceBird App">
    <img :src="bannerImage" alt="CommerceBird App" class="w-full px-0 h-fit" />
    <template #footer>
      <div class="flex items-center justify-between">
        <div class="font-medium">
          Manage Orders, Products, Customers, Coupons and more.
        </div>
        <div class="min-w-fit">
          <BaseLink href="https://app.commercebird.com" target="_blank">
            <component :is="arrow" class="w-4 h-4" />
            Launch App
          </BaseLink>
        </div>
      </div>
    </template>
  </Card>
</template>
<script lang="ts" setup>
import Card from "@/components/ui/Card.vue";
import { ArrowTopRightOnSquareIcon } from "@heroicons/vue/24/solid";
import BaseLink from "@/components/ui/BaseLink.vue";
import { bannerImage } from "@/composable/helpers";

const arrow = ArrowTopRightOnSquareIcon;
</script>
