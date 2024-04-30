<script lang="ts" setup>
import {
  Bars3Icon,
  BellAlertIcon,
  TagIcon,
  XMarkIcon,
} from "@heroicons/vue/24/outline";
import { useRouter } from "vue-router";
import { ref } from "vue";
import { useLoadingStore } from "@/stores/loading";
import { useHomepageStore } from "@/stores/homepage";
import { formatDate } from "@/composable/helpers";
import Sidebar from "../ui/Sidebar.vue";
import LoaderIcon from "../ui/LoaderIcon.vue";
import { useZohoInventoryStore } from "@/stores/zohoInventory";
import BaseButton from "../ui/BaseButton.vue";
import Logo from "@/components/logo.vue";
import { useExactOnlineStore } from "@/stores/exactOnline";
import { backendAction } from "@/keys";
import { useStorage } from "@/composable/storage";
import Swal from "sweetalert2";

const showMobileMenu = ref(false);
const loader = useLoadingStore();
const homepage = useHomepageStore();
const zoho = useZohoInventoryStore();
const exact = useExactOnlineStore();
const router = useRouter();

const clearCache = () => {
  loader.setLoading("clear_cache");
  useStorage().removeAll();
  loader.clearLoading("clear_cache");
};

const checkIfActiveIntegration = (integrationName) => {
  if (homepage.subscription.fee_lines) {
    const integration = homepage.subscription.fee_lines.find(
      (feeLine) => feeLine.name === integrationName
    );
    // This integration is active.
    if (!integration || !integration.name) {
      Swal.fire({
        title: "Not yet Activated",
        text: `Please activate this integration first via the App`,
        icon: "info",
        confirmButtonText: "Activate",
      }).then((action) => {
        if (action.isConfirmed) {
          location.href = "https://app.commercebird.com/integrations";
        }
      });
    }
  }
};
</script>
<template>
  <nav class="bg-white shadow">
    <div class="px-4 mx-auto max-w-7xl sm:px-8">
      <div class="relative flex justify-between h-16">
        <div class="absolute inset-y-0 left-0 flex items-center sm:hidden">
          <!-- Mobile menu button -->
          <button
            aria-expanded="false"
            type="button"
            @click.prevent="showMobileMenu = !showMobileMenu"
          >
            <span class="sr-only">Open main menu</span>
            <XMarkIcon v-if="showMobileMenu" />
            <Bars3Icon v-else />
          </button>
        </div>
        <div
          class="flex items-center justify-center flex-1 sm:items-stretch sm:justify-start"
        >
          <router-link class="flex items-center flex-shrink-0 gap-2" to="/">
            <Logo />
            <span class="font-sans text-xl italic font-bold">CommerceBird</span>
          </router-link>
          <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
            <router-link
              :class="{
                'border-b-2 border-indigo-500 text-gray-900':
                  router.currentRoute.value.path === '/',
                'border-b-2 border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700':
                  router.currentRoute.value.path !== '/',
              }"
              class="inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2 focus:ring-0"
              to="/"
              >Welcome
            </router-link>
            <router-link
              :class="{
                'border-b-2 border-indigo-500 text-gray-900':
                  router.currentRoute.value.path === '/zoho-inventory',
                'border-b-2 border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700':
                  router.currentRoute.value.path !== '/zoho-inventory',
              }"
              class="inline-flex items-center gap-2 px-1 pt-1 text-sm font-medium border-b-2 focus:ring-0"
              to="/zoho-inventory"
              @click.prevent="checkIfActiveIntegration('ZohoInventory')"
            >
              Zoho Inventory
              <span v-if="zoho.isConnected" class="relative flex w-3 h-3">
                <span
                  class="absolute inline-flex w-full h-full rounded-full opacity-75 bg-lime-400 animate-ping"
                ></span>
                <span
                  class="relative inline-flex w-3 h-3 bg-green-500 rounded-full"
                ></span>
              </span>
              <span v-else class="relative flex w-3 h-3">
                <span
                  class="absolute inline-flex w-full h-full rounded-full opacity-75 bg-rose-400 animate-ping"
                ></span>
                <span
                  class="relative inline-flex w-3 h-3 bg-red-500 rounded-full"
                ></span>
              </span>
            </router-link>
            <router-link
              :class="{
                'border-b-2 border-indigo-500 text-gray-900':
                  router.currentRoute.value.path === '/zoho-crm',
                'border-b-2 border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700':
                  router.currentRoute.value.path !== '/zoho-crm',
              }"
              class="inline-flex items-center gap-2 px-1 pt-1 text-sm font-medium border-b-2 focus:ring-0"
              to="/zoho-crm"
              @click.prevent="checkIfActiveIntegration('ZohoCRM')"
            >
              Zoho CRM
            </router-link>
            <router-link
              :class="{
                'border-b-2 border-indigo-500 text-gray-900':
                  router.currentRoute.value.path === '/exact-online',
                'border-b-2 border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700':
                  router.currentRoute.value.path !== '/exact-online',
              }"
              class="inline-flex items-center gap-2 px-1 pt-1 text-sm font-medium border-b-2 focus:ring-0"
              to="/exact-online"
              @click.prevent="checkIfActiveIntegration('ExactOnline')"
            >
              Exact Online
            </router-link>
          </div>
        </div>
        <div
          class="absolute inset-y-0 right-0 flex items-center gap-4 pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0"
        >
          <div class="hidden sm:flex">
            <BaseButton
              :loading="loader.isLoading('clear_cache')"
              type="lite"
              @click.prevent="clearCache"
            >
              Clear Cache
            </BaseButton>
          </div>
          <div>
            <Sidebar
              title="Announcement"
              toggle-class="flex items-center justify-center w-6 h-6 cursor-pointer sm:w-8 sm:h-8 whitespace-nowrap "
            >
              <template #toggleIcon>
                <BellAlertIcon />
              </template>
              <template #content>
                <div class="flow-root">
                  <LoaderIcon
                    v-if="loader.isLoading(backendAction.get_changelog)"
                  />
                  <ul
                    v-if="Object.keys(homepage.changelog).length > 0"
                    class="-mb-8"
                  >
                    <li
                      v-for="(item, index) in homepage.changelog"
                      :key="index"
                    >
                      <div class="relative pb-8">
                        <span
                          aria-hidden="true"
                          class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200"
                        ></span>
                        <div class="relative flex items-start space-x-3">
                          <div class="relative px-1">
                            <div
                              class="flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full ring-8 ring-white"
                            >
                              <TagIcon class="w-5 h-5 text-gray-600" />
                            </div>
                          </div>
                          <div class="flex-1 min-w-0">
                            <div>
                              <div class="text-sm">
                                <a
                                  :href="item.link"
                                  class="font-medium text-gray-900"
                                  target="_blank"
                                  v-html="item.title.rendered"
                                ></a>
                              </div>
                              <p class="mt-0.5 text-sm text-gray-500">
                                {{ formatDate(item.modified_gmt) }}
                              </p>
                            </div>
                            <div
                              class="mt-2 text-sm text-gray-700"
                              v-html="item.content.rendered"
                            ></div>
                          </div>
                        </div>
                      </div>
                    </li>
                  </ul>
                </div>
              </template>
            </Sidebar>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile menu, show/hide based on menu state. -->
    <div v-if="showMobileMenu" id="mobile-menu" class="sm:hidden">
      <div class="space-y-1">
        <router-link
          :class="{
            'bg-indigo-50 border-indigo-500 text-indigo-700':
              router.currentRoute.value.path === '/',
            'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700':
              router.currentRoute.value.path !== '/',
          }"
          class="block p-2 text-base font-medium border-l-4"
          to="/"
          >Welcome
        </router-link>
        <router-link
          :class="{
            'bg-indigo-50 border-indigo-500 text-indigo-700':
              router.currentRoute.value.path === '/zoho-inventory',
            'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700':
              router.currentRoute.value.path !== '/zoho-inventory',
          }"
          class="block p-2 text-base font-medium border-l-4"
          to="/zoho-inventory"
        >
          <span
            class="flex items-baseline justify-between gap-2"
            @click.prevent="checkIfActiveIntegration('ZohoInventory')"
          >
            Zoho Inventory
            <span v-if="zoho.isConnected" class="relative flex w-3 h-3">
              <span
                class="absolute inline-flex w-full h-full rounded-full opacity-75 bg-lime-400 animate-ping"
              ></span>
              <span
                class="relative inline-flex w-3 h-3 bg-green-500 rounded-full"
              ></span>
            </span>
            <span v-else class="relative flex w-3 h-3">
              <span
                class="absolute inline-flex w-full h-full rounded-full opacity-75 bg-rose-400 animate-ping"
              ></span>
              <span
                class="relative inline-flex w-3 h-3 bg-red-500 rounded-full"
              ></span>
            </span>
          </span>
        </router-link>

        <router-link
          :class="{
            'bg-indigo-50 border-indigo-500 text-indigo-700':
              router.currentRoute.value.path === '/zoho-crm',
            'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700':
              router.currentRoute.value.path !== '/zoho-crm',
          }"
          class="block p-2 text-base font-medium border-l-4"
          to="/zoho-crm"
          @click.prevent="checkIfActiveIntegration('ZohoCRM')"
        >
          Zoho CRM
        </router-link>

        <router-link
          :class="{
            'bg-indigo-50 border-indigo-500 text-indigo-700':
              router.currentRoute.value.path === '/exact-online',
            'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700':
              router.currentRoute.value.path !== '/exact-online',
          }"
          class="block p-2 text-base font-medium border-l-4"
          to="/exact-online"
        >
          <span
            class="flex items-baseline justify-between gap-2"
            @click.prevent="checkIfActiveIntegration('ExactOnline')"
          >
            Exact Online
          </span>
        </router-link>
      </div>
      <div class="flex p-2">
        <BaseButton type="lite" @click.prevent="clearCache"
          >Clear Cache
        </BaseButton>
      </div>
    </div>
  </nav>
</template>
