import { acceptHMRUpdate, defineStore } from "pinia";
import type { Ref, UnwrapRef } from "vue";
import { reactive, ref, watch } from "vue";
import { useLoadingStore } from "@/stores/loading";
import { extractOptions, notify, site_url } from "@/composable/helpers";
import { backendAction, storeKey } from "@/keys";
import { fetchData, resetData, sendData } from "@/composable/http";
import { useStorage } from "@/composable/storage";
import { get_acf_fields } from "@/composable/common";
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
    const selectedFieldTab = ref("");
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
                selectedFieldTab.value = "Sales_Orders";
                get_fields('shop_order');
                get_zcrm_fields();
                get_zcrm_custom_fields();
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

   /**
    * @description Function to get woocommerce fields
   */
    const get_woo_fields = async () => {
        const key = keys.fields;
        const instore = storage.get(key);
        if (instore) {
            customFields.value = instore;
        }
        else{
            const action = actions.custom_fields;
            if (loader.isLoading(action)) return;
            loader.setLoading(action);
            customFields.value = await fetchData(
                action,
                key,

            );
            loader.clearLoading(action);
        }
    }

     /**
    * @description Function to get woocommerce and acf fields.
   */
    async function get_fields(postType:string){
       customFields.value={};
       if(postType==='shop_order'){
        get_woo_fields();
       }
       const acfFields=await get_acf_fields(postType);
       customFields.value={...customFields.value,...acfFields};
    }

     async function get_zcrm_custom_fields(){
        let response;
        fields.value =[];
        const moduleName=selectedFieldTab.value;
        const key =`${moduleName.toLowerCase()}_custom_fields`;
        const instore = storage.get(key);
        if (instore){
            response = instore;
        }
        else{
            response = await fetchData(actions.field.get, key,{module:moduleName});

        }

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
    }

   /**
    * @param moduleName Module for refreshing fields
    * @description Function to refresh zoho fields
    */
    async function refresh_zoho(moduleName:string){
        const storeKey =`${moduleName.toLowerCase()}_fields`;
        storage.remove(storeKey);
        const key = keys.refresh_zoho_fields;
        const action =actions.refresh_zcrm_fields;
        if (loader.isLoading(action)) return;
        loader.setLoading(action);
        const response = await fetchData(
            action,
            key,
            {module:moduleName}
        );
        if(response){
            notify.success(response.message);
            if(Array.isArray(response.fields)&&response.fields.length>0){
                let obj: { [key: string]: string } = {};
                response.fields.forEach((field:any)=>{
                    obj[field.id]=field.displayLabel;
                });
                response.fields=obj;
                zcrm_fields.value = response.fields;
            }
            else{
                zcrm_fields.value = {};
            }
        }
        loader.clearLoading(action);
    }



    /**
     * @description Function to get zoho fields from wordpress database
     */
    const zcrm_fields = ref({});
    async function get_zcrm_fields(){
        let response;
        const moduleName=selectedFieldTab.value;
        const key =`${moduleName.toLowerCase()}_fields`;
        const action = actions.zcrm_fields;
        const instore = storage.get(key);
        if(instore){
          response = instore;
        }
        else{
            if (loader.isLoading(action)) return;
            loader.setLoading(action);
             response = await fetchData(
                action,
                key  ,
                {module:moduleName}
            );
        }

           if(Array.isArray(response.fields)&&response.fields.length>0){
            let obj: { [key: string]: string } = {};
            response.fields.forEach((field:any)=>{
                obj[field.id]=field.displayLabel;
            });
            response.fields=obj;
            zcrm_fields.value = response.fields;
        }
        else{
            zcrm_fields.value = {};
        }
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
                const moduleName=selectedFieldTab.value;
                const key =`${moduleName.toLowerCase()}_custom_fields`;
                data = {
                    form: JSON.stringify(fieldData),
                    module: moduleName
                }
                store = key
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
                const moduleName=selectedFieldTab.value;
                const key =`${moduleName.toLowerCase()}_custom_fields`;
                response = await resetData(action, key, {module:moduleName});
                storage.remove(key);
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
        selectedFieldTab,
        notSubscribed,
        isConnected,
        connection,
        customFields,
        zcrm_fields,
        fields,
        dateRange,
        get_zcrm_fields,
        get_zcrm_custom_fields,
        refresh_zoho,
        get_fields,
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
