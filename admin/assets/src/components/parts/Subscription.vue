<template>
  <Card
    :foot="true"
    :title="`${
      store.subscription.plan && store.subscription.plan.length > 0
        ? store.subscription.plan
        : 'Your Plan'
    }`"
  >
    <template #title>
      <Badge :text="store.subscription.status" />
    </template>
    <template #action>
      <LoaderIcon
        v-if="loader.isLoading(backendAction.get_subscription)"
        :loading="loader.isLoading(backendAction.get_subscription)"
      />
      <span v-else>
        <a
          class="py-1 font-medium text-rose-600"
          v-if="store.subscription && store.subscription.needs_payment"
          :href="store.subscription.payment_url"
          target="_blank"
        >
          Reactivate Plan
        </a>
      </span>
    </template>
    <div v-if="store.subscription.fee_lines" class="px-4 py-2 space-y-4">
      <p class="text-sm font-medium text-gray-700">Activated Integrations</p>
      <ul class="flex flex-wrap items-center gap-2">
        <li v-for="item in store.subscription.fee_lines" :key="item">
          <span
            class="inline-flex rounded-md shadow items-center border px-3 py-0.5 text-sm font-medium"
            >{{ item.name }}</span
          >
        </li>
      </ul>
      <p class="text-sm font-medium text-gray-700">Payment</p>
      <p class="text-sm leading-relaxed text-gray-600">
        Your next bill is for
        <span class="font-medium text-gray-900"
          >{{ store.subscription.total }}
          {{ store.subscription.currency }}</span
        >
        on
        <span class="font-medium text-gray-900">
          {{ formatDate(store.subscription.next_payment_date_gmt) }}
        </span>
      </p>
    </div>
    <div v-else class="p-4 my-6">
      <a
        class="relative w-full text-center rounded-lg hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
        href="https://commercebird.com/pricing"
        target="_blank"
        type="button"
      >
        <span
          class="flex items-center justify-center gap-4 mt-2 text-sm font-medium text-gray-900"
          >Subscribe for</span
        >
        <span
          class="flex items-center justify-center gap-4 mt-2 text-sm font-medium text-gray-900"
        >
          Live Notifications, Fastest IOS/Android App, Staff Members,
          Integrations and more
        </span>
      </a>
    </div>
    <template #footer>
      <div class="flex gap-4">
        <BaseLink href="https://commercebird.com/pricing" target="_blank">
          View Plans
        </BaseLink>
      </div>
    </template>
  </Card>
</template>
<script lang="ts" setup>
import { useHomepageStore } from "@/stores/homepage";
import { useLoadingStore } from "@/stores/loading";
import { backendAction, formatDate } from "@/composables";
import Card from "../ui/Card.vue";
import BaseLink from "@/components/ui/BaseLink.vue";
import LoaderIcon from "@/components/ui/LoaderIcon.vue";
import Badge from "@/components/ui/Badge.vue";

const store = useHomepageStore();
const loader = useLoadingStore();
</script>
