import type {
    ConnectionSettings,
    OrderSettings
} from '@/types'
import { acceptHMRUpdate, defineStore } from "pinia";
import type { Ref, UnwrapRef } from "vue";
import { reactive, ref, watch } from "vue";
import { useLoadingStore } from "@/stores/loading";
import { extractOptions, notify, redirect_uri, site_url } from "@/composable/helpers";
import { backendAction, storeKey } from "@/keys";
import { fetchData, resetData, sendData } from "@/composable/http";
import { useStorage } from "@/composable/storage";

const actions = backendAction.zohoCrm;
const keys = storeKey.zohoCrm;

export const useZohoCrmStore = defineStore("zohoCrm", () => {
    const loader = useLoadingStore();
    const storage = useStorage();
    const notSubscribed = ref(false);
    /*
     * -----------------------------------------------------------------------------------------------------------------
     *  Tab Settings
     * -----------------------------------------------------------------------------------------------------------------
     */
    const selectedTab = ref("");
    const selectTab = (tab: string) => (selectedTab.value = tab);
    const tabWatcher = async (tab: string) => {
        let response;
        const notSubscribed = ref(false);
        const checkSubscription = () => {
          const key = storeKey.homepage.subscription;
          notSubscribed.value = storage.get(key) && storage.get(key).length;
        };

        checkSubscription();
        switch (tab) {
            case "connect":
                response = await loader.loadData(
                    keys.connect,
                    actions.connect.get
                  );
                  connection.token = response?.token;
                break;
            case "field":
                get_all_custom_fields();
                response = await loader.loadData(keys.fields, actions.field.get);
                if (response) {
                    let parsed;
                    if (typeof response.form === 'string') {
                        parsed = JSON.parse(response.form);
                    } else {
                        parsed = response.form
                    }

                    Object.entries(parsed).forEach(([key, value]) => {
                        const existingObject = fields.value.some(field => field.key === key && field.value === value);
                        if (!existingObject) {
                            fields.value.push({ key, value });
                        }

                    });
                    if (fields.value.length === 0) {
                        fields.value.push({ key: "", value: "" });
                    }

                }
                break;
            default:
                break;
        }
    }
    watch(selectedTab, tabWatcher);

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
    *  Custom Fields Settings
    * -----------------------------------------------------------------------------------------------------------------
    */
    const customFields = ref({});
    const fields: Ref<UnwrapRef<{ key: string, value: string }[]>> = ref([])

    function addField() {
        fields.value.push({ key: "", value: "" });
    }

    function removeField(index: number) {
        fields.value.splice(index, 1);
    }

    const get_all_custom_fields = async () => {
        const key = keys.fields
        const instore = storage.get(key);
        if (instore) {
            customFields.value = instore;
        }
        const action = actions.custom_fields;
        if (loader.isLoading(action)) return;
        loader.setLoading(action);
        customFields.value = await fetchData(
            action,
            key
        );
        console.log("customFields.value",customFields.value);
        
        loader.clearLoading(action);
    }

    /*
     * -----------------------------------------------------------------------------------------------------------------
     *  Form Submit
     * -----------------------------------------------------------------------------------------------------------------
     */
    const dateRange = ref([]);
    const handleSubmit = async (action: string) => {
        if (loader.isLoading(action)) return;
        loader.setLoading(action);

        let response: any = false;
        let data: any = {};
        let store: string = ''

        switch (action) {
                case actions.connect.save:
                    data = connection;
                    store =keys.connect;
                break;
            case actions.order.export:
                data = { range: dateRange.value };
                store = keys.order
                break;
            case actions.field.save:
                const fieldData = extractOptions(fields.value, 'key', 'value')
                data = {
                    form: JSON.stringify(fieldData),
                }
                store = keys.fields
                break;
            default:
                break;
        }

        if (Object.keys(data).length) {
            response = await sendData(action, data, store);
        }

        if (response) {
            notify.success(response.message);
            switch (action) {
                case actions.field.save:
                    if (fields.value.length === 0) {
                        fields.value.push({ key: "", value: "" });
                    }
                    break;
                default:
                    break;
            }

        }

        loader.clearLoading(action);
    };

    const handleReset = async (action: string) => {
        let response: any = false;
        if (loader.isLoading(action)) return;
        loader.setLoading(action);
        switch (action) {
            case actions.connect.reset:
                response = await resetData(action, keys.connect);
                storage.remove(keys.connect);
                break;
            case actions.field.reset:
                response = await resetData(action, keys.fields);
                storage.remove(keys.fields);
                break;
            default:
                break;
        }

        if (response) {

            notify.success(response.message);
            switch (action) {
                case actions.connect.reset:
                    connection.token = "";
                    connection.site = site_url;
                    break;
                case actions.field.reset:
                    fields.value = [];
                    break;
                default:
                    break;
            }

        }
        loader.clearLoading(action);
    };


  
    return {
        selectTab,
        selectedTab,
        notSubscribed,
        isConnected,
        connection,
        customFields,
        fields,
        dateRange,
        addField,
        removeField,
        handleSubmit,
        handleReset,
    };
});

if (import.meta.hot) {
    import.meta.hot.accept(
        acceptHMRUpdate(useZohoCrmStore, import.meta.hot)
    );
}
