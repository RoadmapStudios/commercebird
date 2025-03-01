import "./main.css";

import { createApp } from "vue";
import { createPinia } from "pinia";

import App from "./App.vue";
import router from "@/route";

import { loadScript } from "@/utils/script-loader";
import vSelect from "vue-select";

const app = createApp(App);
app.component("v-select", vSelect);

app.use(createPinia());
app.use(router);

loadScript(
  "https://desk.zoho.eu/portal/api/web/asapApp/5446000013816001?orgId=20060551652",
  "zohodeskasapscript",
  "{place_your_nonce_value_here}"
)
  .then(() => {
    console.log("Zoho Desk script loaded globally!");
  })
  .catch((err) => console.error(err));

app.mount("#commercebird-app");
