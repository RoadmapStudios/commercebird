import {createMemoryHistory, createRouter} from "vue-router";
import HomePage from "@/page/HomePage.vue";
import ZohoInventory from "@/page/ZohoInventory.vue";
import {storeKey, useStorage} from "@/composables";
import type {Subscription} from "@/type";

const router =  createRouter({
    history: createMemoryHistory(import.meta.env.BASE_URL),
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
                let subscription: Subscription = useStorage().get(storeKey.subscription);
                let pass = false;
                if (subscription) {
                    pass =
                        subscription.status === "active" &&
                        subscription.fee_lines.find(
                            (item) => item.name === "ZohoInventory"
                        ) !== undefined &&
                        (subscription.variation_id.includes(18) || subscription.variation_id.includes(16));
                }
                return pass;
            },
        },
    ],
});

export default router;