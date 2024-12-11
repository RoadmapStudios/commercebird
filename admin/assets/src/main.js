import "./main.css";

import { createApp } from "vue";
import { createPinia } from "pinia";

import App from "./App.vue";
import router from "@/route";

import VueSweetalert2 from 'vue-sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { loadScript } from "@/utils/script-loader";

const app = createApp(App);

app.use(createPinia());
app.use(VueSweetalert2);
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
