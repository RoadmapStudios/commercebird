import {createMemoryHistory, createRouter} from "vue-router";
import HomePage from "@/page/HomePage.vue";
import ZohoInventory from "@/page/ZohoInventory.vue";
import type {Subscription} from "@/types";
import ExactOnline from "@/page/ExactOnline.vue";
import {useStorage} from "@/composable/storage";
import {storeKey} from "@/keys";

const router = createRouter({
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
                let subscription: Subscription = useStorage().get(storeKey.homepage.subscription);
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
        {
            path: "/exact-online",
            name: "Exact Online",
            component: ExactOnline,
            beforeEnter: (to, from) => {
                let subscription: Subscription = useStorage().get(storeKey.homepage.subscription);
                let pass = false;
                if (subscription) {
                    pass =
                        subscription.status === "active" &&
                        subscription.fee_lines.find(
                            (item) => item.name === "ExactOnline"
                        ) !== undefined &&
                        (subscription.variation_id.includes(18) || subscription.variation_id.includes(16));
                }
                return pass;
            },
        },
    ],
});

export default router;