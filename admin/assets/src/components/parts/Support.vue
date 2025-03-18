<template>
  <Card title="Need a Hand?" class="my-4">
    <div class="p-4">
      <ul class="divide-y divide-gray-200" role="list">
        <li v-for="(link, name) in links" :key="name" class="py-2 space-y-4">
          <div class="flex flex-wrap items-center justify-center space-x-4">
            <div class="flex-shrink-0">
              <component :is="link.icon" aria-hidden="true" class="w-6 h-6" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">
                {{ link.title }}
              </p>
              <p class="text-sm text-gray-500">{{ link.subtitle }}</p>
            </div>
            <div>
              <BaseLink :href="link.link" target="_blank">{{
                link.button
              }}</BaseLink>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </Card>
</template>
<script lang="ts" setup>
import Card from "@/components/ui/Card.vue";
import {
  BookOpenIcon,
  CalendarDaysIcon,
  ChatBubbleBottomCenterIcon,
} from "@heroicons/vue/24/outline";
import BaseLink from "@/components/ui/BaseLink.vue";

const links = {
  meeting: {
    title: "Schedule a Meeting",
    subtitle: "Book a Demo with our Sales Team",
    link: "https://book.commercebird.com/#/customer/roadmapstudios",
    icon: CalendarDaysIcon,
    button: "Book Now",
  },
  knowledge: {
    title: "Knowledge Base",
    subtitle: "Find answers to your questions",
    link: "https://support.commercebird.com/portal/en/kb",
    icon: BookOpenIcon,
    button: "Open Knowledge Base",
  },
  chat: {
    title: "Chat with us",
    subtitle: "Find answers to your questions",
    link: "https://commercebird.com/contact/",
    icon: ChatBubbleBottomCenterIcon,
    button: "Chat Now",
  },
};
</script>
