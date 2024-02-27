import { createMemoryHistory, createRouter } from "vue-router";
import HomePage from "@/page/HomePage.vue";
import ZohoInventory from "@/page/ZohoInventory.vue";
import type { Subscription } from "@/types";
import ExactOnline from "@/page/ExactOnline.vue";
import { useStorage } from "@/composable/storage";
import { storeKey } from "@/keys";
import ZohoCRM from "./page/ZohoCRM.vue";

const router = createRouter({
  history: createMemoryHistory(
    localStorage.getItem(storeKey.currentRoute) || "/"
  ),
  routes: [
    {
      path: "/",
      name: "Welcome",
      component: HomePage,
    },
    {
      path: "/zoho-inventory",
      name: "Zoho Inventory",
      component: ZohoInventory,
      beforeEnter: (to, from) => {
        let subscription: Subscription = useStorage().get(
          storeKey.homepage.subscription
        );
        let pass = false;
        if (subscription) {
          pass =
            subscription.status === "active" &&
            subscription.fee_lines.find(
              (item) => item.name === "ZohoInventory"
            ) !== undefined
        }
        return pass;
      },
    },
    {
      path: "/zoho-crm",
      name: "Zoho CRM",
      component: ZohoCRM,
      beforeEnter: (to, from) => {
        let subscription: Subscription = useStorage().get(
          storeKey.homepage.subscription
        );
        let pass = false;
        if (subscription) {
          pass =
            subscription.status === "active" &&
            subscription.fee_lines.find(
              (item) => item.name === "ZohoCRM"
            ) !== undefined
        }
        return pass;
      },
    },
    {
      path: "/exact-online",
      name: "Exact Online",
      component: ExactOnline,
      beforeEnter: (to, from) => {
        let subscription: Subscription = useStorage().get(
          storeKey.homepage.subscription
        );
        let pass = false;
        if (subscription) {
          pass =
            subscription.status === "active" &&
            subscription.fee_lines.find(
              (item) => item.name === "ExactOnline"
            ) !== undefined
        }
        return pass;
      },
    },
  ],
});
router.beforeEach((to, from, next) => {
  let subscription: Subscription = useStorage().get(
    storeKey.homepage.subscription
  );
  if (
    subscription &&
    subscription.status === "active" &&
    to.path === from.path &&
    null !== localStorage.getItem(storeKey.currentRoute) &&
    to.path !== localStorage.getItem(storeKey.currentRoute)
  ) {
    next({ path: localStorage.getItem(storeKey.currentRoute) });
  } else {
    next();
  }
});
router.afterEach((to) => localStorage.setItem(storeKey.currentRoute, to.path));
export default router;
