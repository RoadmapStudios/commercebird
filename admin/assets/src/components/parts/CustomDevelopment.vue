<template>
    <Card class="p-6 text-center border border-gray-200 shadow-lg rounded-xl">
      <h2 class="text-2xl font-bold text-gray-900">🚀 Scale Your Business with Custom Development</h2>
      <p class="mt-2 text-lg text-gray-600">Let our experts build, migrate, or enhance your WooCommerce store.</p>

      <!-- Lottie Animation -->
      <div class="flex justify-center my-4">
        <DotLottieVue class="w-72 h-72" autoplay loop :src="lottiePath" />
      </div>

      <!-- Services List with Bigger Icons & Text -->
      <ul class="max-w-md mx-auto space-y-4 text-lg font-medium text-left text-gray-800">
        <li v-for="(service, index) in services" :key="index" class="flex items-center p-3 bg-gray-100 rounded-lg shadow-sm">
          <CheckCircleIcon class="flex-shrink-0 mr-3 text-green-500 w-7 h-7" />
          <span class="leading-snug">{{ service }}</span>
        </li>
      </ul>

      <!-- Call-to-Action -->
      <div class="mt-6">
        <a
          :href="links.customDevelopment.link"
          target="_blank"
          class="inline-block px-6 py-3 text-lg font-semibold text-white transition transform rounded-lg shadow-md bg-gradient-to-r from-blue-600 to-blue-400 hover:scale-105 hover:from-blue-700 hover:to-blue-500"
        >
          📩 Get a Free Consultation
        </a>
      </div>
    </Card>
  </template>

<script lang="ts" setup>
  import Card from "@/components/ui/Card.vue";
  import { DotLottieVue } from "@lottiefiles/dotlottie-vue";
  import { CheckCircleIcon } from "@heroicons/vue/24/solid";

  // Define services list
  const services = [
    "🚀 Performance Optimizations",
    "🛠️ Full-site builds & Migration",
    "⚙️ Custom functionality",
    "🔧 Site maintenance",
    "🔗 Third-party integrations",
  ];

  // Define CTA link
  const links = {
    customDevelopment: {
      link: "https://commercebird.com/contact/",
    },
  };

  // Correct path to Lottie file
  const isDev = import.meta.env.MODE === "development";
  const lottiePath = isDev
  ? new URL("../../assets/web-building.lottie", import.meta.url).href
  : new URL("web-building.lottie", import.meta.url).href;
</script>