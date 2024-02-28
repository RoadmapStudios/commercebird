import { acceptHMRUpdate, defineStore } from "pinia";
import { reactive, ref, watch } from "vue";
import { useLoadingStore } from "@/stores/loading";
import { useStorage } from "@/composable/storage";
import { backendAction, storeKey } from "@/keys";
import { fetchData, resetData, sendData } from "@/composable/http";
import { Toast, eo_sync, notify, site_url } from "@/composable/helpers";

const actionKey = backendAction.exactOnline;
const localKey = storeKey.exactOnline;
export const useExactOnlineStore = defineStore("exactOnline", () => {
  const storage = useStorage();
  const loader = useLoadingStore();
  /*
   * -----------------------------------------------------------------------------------------------------------------
   *  Connection Settings
   * -----------------------------------------------------------------------------------------------------------------
   */
  const isConnected = ref(true);
  const connection = reactive({
    token: "",
    site: site_url
  });

  /*
   * -----------------------------------------------------------------------------------------------------------------
   *  Cost Center and Units Settings
   * -----------------------------------------------------------------------------------------------------------------
   */
  const getCenters = async () => {
    if (loader.isLoading(actionKey.cost_center.save)) return;
    loader.setLoading(actionKey.cost_center.save);
    let response = await fetchData(
      actionKey.cost_center.save,
      localKey.cost_center
    );
    if (response) {
      notify.success(response.message);
    }
    loader.clearLoading(actionKey.cost_center.save);
  };
  const getUnits = async () => {
    if (loader.isLoading(actionKey.cost_unit.save)) return;
    loader.setLoading(actionKey.cost_unit.save);
    let response = await fetchData(
      actionKey.cost_unit.save,
      localKey.cost_unit
    );
    if (response) {
      notify.success(response.message);
    }
    loader.clearLoading(actionKey.cost_unit.save);
  };
  /*
   * -----------------------------------------------------------------------------------------------------------------
   *  Tab Settings
   * -----------------------------------------------------------------------------------------------------------------
   */
  const selectedTab = ref("");
  const notSubscribed = ref(false);
  const selectTab = (tab: string) => (selectedTab.value = tab);
  const checkSubscription = () => {
    const key = storeKey.homepage.subscription;
    notSubscribed.value = storage.get(key) && storage.get(key).length;
  };
  const tabWatcher = async (tab: string) => {
    let response;
    checkSubscription();
    switch (tab) {
      case "connect":
        response = await loader.loadData(
          localKey.connect,
          actionKey.connect.get
        );
        connection.token = response?.token;
        break;
      default:
        break;
    }
  };
  watch(selectedTab, tabWatcher);

  /*
   * -----------------------------------------------------------------------------------------------------------------
   *  Products action
   * -----------------------------------------------------------------------------------------------------------------
   */
  const importProducts = ref(false);
  const mapProducts = async () => {
    handleMap(
      actionKey.product.map,
      { importProducts: importProducts.value },
      localKey.product
    );
  };

  /*
   * -----------------------------------------------------------------------------------------------------------------
   *  Map Orders
   * -----------------------------------------------------------------------------------------------------------------
   */
  const dateRange = ref([]);
  const sync_order = ref(eo_sync);
  const mapOrders = async () => {
    handleMap(actionKey.order.map, { range: dateRange.value }, localKey.order);
  };
  const exportOrders = async () => {
    handleMap(
      actionKey.order.export,
      { range: dateRange.value },
      localKey.order
    );
  };

  watch(sync_order, value => {
    if (value) {
      handleMap(
        actionKey.order.sync,
        { sync: sync_order.value },
        localKey.order
      );
    }
  });
  /*
   * -----------------------------------------------------------------------------------------------------------------
   *  Map Customers
   * -----------------------------------------------------------------------------------------------------------------
   */
  const importCustomers = ref(false);
  const mapCustomers = async () => {
    handleMap(
      actionKey.customer.map,
      { importCustomers: importCustomers.value },
      localKey.customer
    );
  };
  /*
   * -----------------------------------------------------------------------------------------------------------------
   *  Form Submit
   * -----------------------------------------------------------------------------------------------------------------
   */
  const handleMap = async (key: string, data: any, storage: string) => {
    if (loader.isLoading(key)) return;

    loader.setLoading(key);

    let response = await sendData(key, data, storage);

    if (response) {
      Toast.fire({
        icon: response.success ? "success" : "error",
        text: response.message
      });
    }

    loader.clearLoading(key);
  };
  const handleSubmit = async (action: string) => {
    if (loader.isLoading(action)) return;
    loader.setLoading(action);

    let response: any = false;
    let data: any = {};
    let store: string = "";

    switch (action) {
      case actionKey.connect.save:
        data = connection;
        store = localKey.connect;
        break;
      default:
        break;
    }

    if (Object.keys(data).length) {
      response = await sendData(action, data, store);
    }

    if (response) {
      notify.success(response.message);
    }

    loader.clearLoading(action);
  };

  const handleReset = async (action: string) => {
    let response: any = false;
    if (loader.isLoading(action)) return;
    loader.setLoading(action);
    let store: string = "";
    switch (action) {
      case actionKey.connect.reset:
        store = localKey.connect;
        break;
      default:
        break;
    }
    response = await resetData(action, store);
    if (response) {
      storage.remove(store);
      notify.success(response.message);
      switch (action) {
        case actionKey.connect.reset:
          connection.token = "";
          connection.site = site_url;
          break;

        default:
          break;
      }
    }
    loader.clearLoading(action);
  };
  return {
    selectedTab,
    selectTab,
    notSubscribed,
    isConnected,
    connection,
    getCenters,
    getUnits,
    importProducts,
    mapProducts,
    dateRange,
    sync_order,
    mapOrders,
    exportOrders,
    importCustomers,
    mapCustomers,
    handleSubmit,
    handleReset
  };
});

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useExactOnlineStore, import.meta.hot));
}
