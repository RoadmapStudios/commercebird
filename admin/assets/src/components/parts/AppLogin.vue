<template>
  <Card :foot="true" title="CommerceBird App">
    <img
        alt="CommerceBird App"
        class="w-full px-0 h-fit"
        src="https://app.commercebird.com/assets/images/wooventory-login-banner.png"
    />
    <template #footer>
      <div class="flex items-center justify-between gap-4 px-2">
        <div class="font-medium ">
          Manage Orders, Products, Customers, Coupons and more.
        </div>
        <div class="min-w-fit">
          <BaseLink href="https://app.commercebird.com" target="_blank">
            <ArrowTopRightOnSquareIcon class="w-4 h-4"/>
            Launch App
          </BaseLink>
        </div>
      </div>
    </template>
  </Card>
</template>
<script lang="ts" setup>
import Card from "@/components/ui/Card.vue";
import {ArrowTopRightOnSquareIcon} from "@heroicons/vue/24/solid";
import BaseLink from "@/components/ui/BaseLink.vue";
</script>
