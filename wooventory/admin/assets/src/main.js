import "./main.css";

import {createApp} from "vue";
import {createPinia} from "pinia";

import App from "./App.vue";
import ZohoInventory from "@/page/ZohoInventory.vue";
import HomePage from "@/page/HomePage.vue";
import {createMemoryHistory, createRouter} from "vue-router";
import {storeKey, useStorage} from "@/composables";

const app = createApp(App);

app.use(createPinia());
app.use(
    createRouter({
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
                let subscription = useStorage().get(storeKey.subscription);
                let pass = false;
                if (subscription) {
                    pass =
                        subscription.status === "active" &&
                        subscription.fee_lines.find(
                            (item) => item.name === "ZohoInventory"
                        ) !== undefined;
                }
                return pass;
            },
        },
    ],
    })
);

app.mount("#wooventory-app");
