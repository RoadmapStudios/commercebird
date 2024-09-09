import "./main.css";

import { createApp } from "vue";
import { createPinia } from "pinia";

import App from "./App.vue";
import router from "@/route";

import VueSweetalert2 from 'vue-sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const app = createApp(App);

app.use(createPinia());
app.use(VueSweetalert2);
app.use(router);

app.mount("#commercebird-app");
