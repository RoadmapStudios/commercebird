import {
    backendAction,
    extractOptions,
    fetchData,
    notify,
    redirect_uri,
    resetData,
    sendData,
    storeKey,
    useStorage,
} from "@/composables";
import type {
    ConnectionSettings,
    CronSettings,
    OrderSettings,
    PriceSettings,
    ProductSettings,
    TaxSettings,
    ZohoTax
} from '@/type'
import { acceptHMRUpdate, defineStore } from "pinia";
import type { Ref, UnwrapRef } from "vue";
import { reactive, ref, watch } from "vue";
import { useLoadingStore } from "@/stores/loading";


export const useZohoInventoryStore = defineStore("zohoInventory", () => {
    const loader = useLoadingStore();
    const storage = useStorage();
    const notSubscribed = ref(false);
    const isConnected = ref(false);
    /*
     * -----------------------------------------------------------------------------------------------------------------
     *  Tab Settings
     * -----------------------------------------------------------------------------------------------------------------
     */
    const selectedTab = ref("");
    const selectTab = (tab: string) => (selectedTab.value = tab);

    /*
     * -----------------------------------------------------------------------------------------------------------------
     *  Connection Settings
     * -----------------------------------------------------------------------------------------------------------------
     */
    const connectionSettingsInvalid = ref(false);
    const connection: ConnectionSettings = reactive({
        organization_id: "",
        client_id: "",
        client_secret: "",
        redirect_uri: redirect_uri,
        account_domain: "",
    });

    const isConnectionValid = async () => {
        if (loader.isLoading(backendAction.is_connected)) return;
        loader.setLoading(backendAction.is_connected);
        const response = await fetchData(backendAction.is_connected, storeKey.connected);
        isConnected.value = response;
        loader.clearLoading(backendAction.is_connected);
    };


    /*
     * -----------------------------------------------------------------------------------------------------------------
     *  Tax Settings
     * -----------------------------------------------------------------------------------------------------------------
     */

    const wc_taxes = ref<WC_TAX[]>([]);
    const zoho_taxes = ref<ZohoTax[]>([]);
    const tax_settings = reactive(<TaxSettings>{
        decimalTax: false,
        selectedTaxRates: [],
        selectedVatExempt: "",
    });
    const get_wc_taxes = async () => {
        const instore = storage.get(storeKey.wc_tax);
        if (instore) {
            wc_taxes.value = instore;
        } else {
            if (loader.isLoading(backendAction.get_wc_taxes)) return;
            loader.setLoading(backendAction.get_wc_taxes);
            wc_taxes.value = await fetchData(backendAction.get_wc_taxes, storeKey.wc_tax);
            loader.clearLoading(backendAction.get_wc_taxes);
        }
    };
    const get_zoho_taxes = async () => {
        const in_store = storage.get(storeKey.zoho_tax);
        if (in_store) {
            zoho_taxes.value = in_store;
        } else {
            if (loader.isLoading(backendAction.get_zoho_taxes)) return;
            loader.setLoading(backendAction.get_zoho_taxes);
            zoho_taxes.value = await fetchData(backendAction.get_zoho_taxes, storeKey.zoho_tax);
            loader.clearLoading(backendAction.get_zoho_taxes);
        }
    };

    const encodeTax = (zoho_tax_rate: ZohoTax): string =>
        `${zoho_tax_rate.tax_id}##${zoho_tax_rate.tax_name.replace(
            " ",
            "@@"
        )}##${zoho_tax_rate.tax_type.replace(" ", "@@")}##${zoho_tax_rate.tax_percentage
        }`;

    const taxOptions = (woocommerce_tax_id) => {
        const taxOptions = {};
        for (const zoho_tax_rate of zoho_taxes.value) {
            taxOptions[woocommerce_tax_id + "^^" + encodeTax(zoho_tax_rate)] =
                zoho_tax_rate.tax_name;
        }
        return taxOptions;
    };

    const vatExemptOptions = () => {
        const vatExemptOptions = {};
        for (const zoho_tax_rate of zoho_taxes.value) {
            vatExemptOptions[zoho_tax_rate.tax_id] = zoho_tax_rate.tax_name;
        }
        return vatExemptOptions;
    };

    /*
     * -----------------------------------------------------------------------------------------------------------------
     *  Product Settings
     * -----------------------------------------------------------------------------------------------------------------
     */
    const syncResponse = ref<any>([]);
    const product_settings = reactive(<ProductSettings>{
        item_from_zoho: false,
        disable_stock_sync: false,
        disable_product_sync: false,
        enable_accounting_stock: false,
    });

    const sync = async (action: string) => {
        if (loader.isLoading(action)) return;
        loader.setLoading(action);
        let url = `${zoho_inventory_admin.url}?action=zoho_ajax_call_${action}`;
        if (
            product_settings.item_from_zoho &&
            (action === "variable_item" ||
                action === "item" ||
                action === "composite_item")
        ) {
            url = `${url}_from_zoho`;
        }
        syncResponse.value = [];
        await fetch(url)
            .then((response) => response.text())
            .then((response) => {
                if (!response) return;
                let data = JSON.parse(response);
                if (data.hasOwnProperty("success")) {
                    notify.success("Done!");
                    return;
                }
                if (data.hasOwnProperty("message")) {
                    notify.success(data.message);
                    return;
                }
                syncResponse.value = data;
            })
            .finally(() => {
                loader.clearLoading(action);
            });
    };

    /*
     * -----------------------------------------------------------------------------------------------------------------
     *  Cron Settings
     * -----------------------------------------------------------------------------------------------------------------
     */
    const cron_settings = reactive(<CronSettings>{
        disable_name_sync: false,
        disable_price_sync: false,
        disable_image_sync: false,
        disable_description_sync: false,
        zi_cron_interval: 'none'
    })

    const intervals: Intervals = {
        none: 'None',
        twicedaily: 'Twice per day',
        daily: 'Once a day'
    }

    const selected_categories: Ref<string[]> = ref([]);
    const zoho_categories = ref({});
    const toggleSelectAll = (event) => {
        if (event.target.id === 'toggle-all') {
            if (event.target.checked) {
                selected_categories.value = Object.keys(zoho_categories.value);
            } else {
                selected_categories.value = [];
            }
        }


    };
    const get_zoho_categories = async () => {
        if (loader.isLoading(backendAction.get_zoho_categories)) return;
        loader.setLoading(backendAction.get_zoho_categories);
        zoho_categories.value = await fetchData(
            backendAction.get_zoho_categories,
            storeKey.zoho_categories
        );
        loader.clearLoading(backendAction.get_zoho_categories);
    };

    /*
    * -----------------------------------------------------------------------------------------------------------------
    *  Order Settings
    * -----------------------------------------------------------------------------------------------------------------
    */
    const zoho_warehouses = ref({});
    const order_settings = reactive(<OrderSettings>{
        package_sync: false,
        disable_sync: false,
        enable_auto_number: false,
        enable_order_status: false,
        enable_multicurrency: false,
        enable_warehousestock: false,
        order_prefix: '',
        warehouse_id: ''

    })
    const get_zoho_warehouses = async () => {
        const instore = storage.get(storeKey.zoho_warehouses);
        if (instore) {
            zoho_warehouses.value = instore;
        }
        if (loader.isLoading(backendAction.get_zoho_warehouses)) return;
        loader.setLoading(backendAction.get_zoho_warehouses);
        zoho_warehouses.value = await fetchData(
            backendAction.get_zoho_warehouses,
            storeKey.zoho_warehouses
        );
        loader.clearLoading(backendAction.get_zoho_warehouses);
    };
    /*
    * -----------------------------------------------------------------------------------------------------------------
    *  Price Settings
    * -----------------------------------------------------------------------------------------------------------------
    */
    const zoho_prices = ref({});
    const price_settings = reactive(<PriceSettings>{
        zoho_inventory_pricelist: '',
        wp_user_role: ''
    })
    const get_zoho_prices = async () => {
        const instore = storage.get(storeKey.zoho_prices);
        if (instore) {
            zoho_prices.value = instore;
        }
        if (loader.isLoading(backendAction.get_zoho_prices)) return;
        loader.setLoading(backendAction.get_zoho_prices);
        zoho_prices.value = await fetchData(
            backendAction.get_zoho_prices,
            storeKey.zoho_prices
        );
        loader.clearLoading(backendAction.get_zoho_prices);
    }
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

    function removeField(index) {
        fields.value.splice(index, 1);
    }

    const get_all_custom_fields = async () => {
        const instore = storage.get(storeKey.order_custom_fields);
        if (instore) {
            customFields.value = instore;
        }
        if (loader.isLoading(backendAction.get_all_custom_fields)) return;
        loader.setLoading(backendAction.get_all_custom_fields);
        customFields.value = await fetchData(
            backendAction.get_all_custom_fields,
            storeKey.order_custom_fields
        );
        loader.clearLoading(backendAction.get_all_custom_fields);
    }

    /*
     * -----------------------------------------------------------------------------------------------------------------
     *  Form Submit
     * -----------------------------------------------------------------------------------------------------------------
     */

    const handleSubmit = async (action: string) => {
        if (loader.isLoading(action)) return;
        loader.setLoading(action);

        let response: any = false;
        let data: any = {};
        let store: string = ''

        switch (action) {
            case backendAction.save_connect:
                data = connection;
                store = storeKey.connection
                break;
            case backendAction.save_tax:
                data = tax_settings;
                store = storeKey.tax
                break;
            case backendAction.save_product:
                data = product_settings;
                store = storeKey.product
                break;
            case backendAction.save_cron:
                data = {
                    form: JSON.stringify(cron_settings),
                    categories: JSON.stringify(selected_categories.value)
                };
                store = storeKey.cron
                break;
            case backendAction.save_order:
                data = order_settings
                store = storeKey.order
                break;
            case backendAction.save_price:
                data = price_settings
                store = storeKey.price
                break;
            case backendAction.save_fields:
                const fieldData = extractOptions(fields.value, 'key', 'value')
                data = {
                    form: JSON.stringify(fieldData),
                }
                store = storeKey.fields
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
                case backendAction.save_connect:
                    setTimeout(() => {
                        window.location.href = response.redirect;
                    }, 1000);
                    break;
                case backendAction.save_fields:
                    if (fields.value.length === 0) {
                        fields.value.push({ key: "", value: "" });
                    }
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
            case backendAction.reset_connect:
                response = await resetData(action, storeKey.connection);
                storage.remove(storeKey.connection);
                break;
            case backendAction.reset_tax:
                response = await resetData(action, storeKey.tax);
                break;
            case backendAction.reset_product:
                response = await resetData(action, storeKey.product);
                storage.remove(storeKey.product);
                break;
            case backendAction.reset_cron:
                response = await resetData(action, storeKey.cron);
                storage.remove(storeKey.cron);
                break;
            case backendAction.reset_order:
                response = await resetData(action, storeKey.order);
                storage.remove(storeKey.order);
                break;
            case backendAction.reset_price:
                response = await resetData(action, storeKey.price);
                storage.remove(storeKey.price);
                break;
            case backendAction.reset_fields:
                response = await resetData(action, storeKey.fields);
                storage.remove(storeKey.fields);
                break;
            default:
                break;
        }

        if (response) {

            notify.success(response.message);
            switch (action) {
                case backendAction.reset_connect:
                    connection.organization_id = "";
                    connection.client_id = "";
                    connection.client_secret = "";
                    connection.redirect_uri = redirect_uri;
                    connection.account_domain = "";
                    break;
                case backendAction.reset_tax:
                    tax_settings.decimalTax = false;
                    tax_settings.selectedTaxRates = [];
                    tax_settings.selectedVatExempt = "";
                    break;
                case backendAction.reset_product:
                    product_settings.disable_product_sync = false;
                    product_settings.enable_accounting_stock = false;
                    product_settings.disable_stock_sync = false;
                    product_settings.item_from_zoho = false;
                    break;
                case backendAction.reset_cron:
                    cron_settings.disable_description_sync = false;
                    cron_settings.disable_image_sync = false;
                    cron_settings.disable_name_sync = false;
                    cron_settings.disable_price_sync = false;
                    cron_settings.zi_cron_interval = 'none'
                    selected_categories.value = [];
                    break;
                case backendAction.reset_order:
                    order_settings.package_sync = false
                    order_settings.disable_sync = false
                    order_settings.enable_auto_number = false
                    order_settings.enable_order_status = false
                    order_settings.enable_multicurrency = false
                    order_settings.enable_warehousestock = false
                    order_settings.order_prefix = ''
                    order_settings.warehouse_id = ''
                    break;
                case backendAction.reset_price:
                    price_settings.zoho_inventory_pricelist = '';
                    price_settings.wp_user_role = ''
                    break;
                case backendAction.reset_fields:
                    fields.value = [];
                    break;
                default:
                    break;
            }

        }
        loader.clearLoading(action);
    };
    const loadData = async (storeKey: string, action: string) => {
        let instore = storage.get(storeKey);
        if (!instore || !instore.organization_id) {
            if (loader.isLoading(action)) return;
            loader.setLoading(action);
            instore = await fetchData(action, storeKey);
            loader.clearLoading(action);
        }

        return instore;
    };

    const tabWatcher = async (tab: string) => {
        let response;
        notSubscribed.value = storage.get(storeKey.subscription) && storage.get(storeKey.subscription).length;
        isConnected.value = storage.get(storeKey.connected);


        if (tab !== "connect") {
            if (!isConnected.value) {
                selectedTab.value = "connect";
                return false;
            }
        }

        switch (tab) {
            case "connect":
                response = await loadData(storeKey.connection, backendAction.get_connect);
                if (response) {
                    connection.organization_id = response.organization_id;
                    connection.client_id = response.client_id;
                    connection.client_secret = response.client_secret;
                    connection.redirect_uri = redirect_uri;
                    connection.account_domain = response.account_domain;
                }
                break;
            case "tax":
                get_wc_taxes();
                get_zoho_taxes();
                response = await loadData(storeKey.tax, backendAction.get_tax);
                if (response) {
                    tax_settings.decimalTax = response.decimalTax;
                    tax_settings.selectedTaxRates = response.selectedTaxRates;
                    tax_settings.selectedVatExempt = response.selectedVatExempt;
                }
                break;
            case "product":
                response = await loadData(storeKey.product, backendAction.get_product);
                if (response) {
                    product_settings.item_from_zoho = response.item_from_zoho;
                    product_settings.disable_stock_sync = response.disable_stock_sync;
                    product_settings.disable_product_sync = response.disable_product_sync;
                    product_settings.enable_accounting_stock = response.enable_accounting_stock;
                }
                break;
            case "cron":
                get_zoho_categories();
                response = await loadData(storeKey.cron, backendAction.get_cron);
                if (response) {
                    let parsed = response.form
                    if (typeof response.form === 'string') {
                        parsed = JSON.parse(response.form)
                    }
                    let parsedCategories = response.categories
                    if (typeof parsedCategories === 'string') {
                        parsedCategories = JSON.parse(parsedCategories)
                    }

                    cron_settings.disable_name_sync = parsed.disable_name_sync;
                    cron_settings.disable_price_sync = parsed.disable_price_sync;
                    cron_settings.disable_image_sync = parsed.disable_image_sync;
                    cron_settings.disable_description_sync = parsed.disable_description_sync;
                    cron_settings.zi_cron_interval = parsed.zi_cron_interval
                    selected_categories.value = parsedCategories;
                }
                break;
            case "order":
                get_zoho_warehouses();
                response = await loadData(storeKey.order, backendAction.get_order);
                if (response) {
                    order_settings.package_sync = response.package_sync;
                    order_settings.disable_sync = response.disable_sync;
                    order_settings.enable_order_status = response.enable_order_status;
                    order_settings.enable_multicurrency = response.enable_multicurrency;
                    order_settings.order_prefix = response.order_prefix;
                    order_settings.warehouse_id = response.warehouse_id;
                    order_settings.enable_warehousestock = response.enable_warehousestock;
                }

                break;
            case "price":
                get_zoho_prices();
                response = await loadData(storeKey.price, backendAction.get_price);
                if (response) {
                    price_settings.zoho_inventory_pricelist = response.zoho_inventory_pricelist;
                    price_settings.wp_user_role = response.wp_user_role;
                }
                break;
            case "field":
                get_all_custom_fields();
                response = await loadData(storeKey.fields, backendAction.get_fields);
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
    return {
        selectTab,
        selectedTab,
        notSubscribed,
        isConnected,
        isConnectionValid,
        loadData,
        connection,
        connectionSettingsInvalid,
        wc_taxes,
        zoho_taxes,
        tax_settings,
        taxOptions,
        vatExemptOptions,
        product_settings,
        sync,
        syncResponse,
        cron_settings,
        intervals,
        selected_categories,
        zoho_categories,
        toggleSelectAll,
        order_settings,
        zoho_warehouses,
        zoho_prices,
        price_settings,
        customFields,
        fields,
        addField,
        removeField,
        handleSubmit,
        handleReset,
    };
});

if (import.meta.hot) {
    import.meta.hot.accept(
        acceptHMRUpdate(useZohoInventoryStore, import.meta.hot)
    );
}
