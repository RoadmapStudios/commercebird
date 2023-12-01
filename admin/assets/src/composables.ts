import { Notyf } from "notyf";
import type { BackendAction, StoreKey, UseStorage } from "@/type";

export const storeKey: StoreKey = {
    connected: 'connected',
    changelog: 'changelog',
    subscription: 'subscription',
    settings: 'settings',
    connection: 'connection_settings',
    tax: 'tax_settings',
    wc_tax: 'wc_taxes',
    zoho_tax: 'zoho_taxes',
    product: 'product_settings',
    cron: 'cron_settings',
    order: 'order_settings',
    price: 'price_settings',
    fields: 'fields_settings',
    zoho_categories: 'zoho_categories',
    zoho_warehouses: 'zoho_warehouses',
    order_custom_fields: 'order_custom_fields',
}


export const backendAction: BackendAction = {
    get_settings: 'get_settings',
    save_settings: 'save_settings',
    reset_settings: 'reset_settings',
    get_changelog: 'get_changelog',
    is_connected: 'is_connected',
    get_subscription: 'get_subscription',
    get_zoho_warehouses: 'get_zoho_warehouses',
    get_zoho_prices: 'get_zoho_prices',
    get_zoho_categories: 'get_zoho_categories',
    get_zoho_taxes: 'get_zoho_taxes',
    get_all_custom_fields: 'get_all_custom_fields',
    get_wc_taxes: 'get_wc_taxes',
    save_connect: 'save_connection',
    reset_connect: 'reset_connection',
    get_connect: 'get_connection',
    save_tax: 'save_tax_settings',
    reset_tax: 'reset_tax_settings',
    get_tax: 'get_tax_settings',
    save_product: 'save_product_settings',
    reset_product: 'reset_product_settings',
    get_product: 'get_product_settings',
    save_cron: 'save_cron_settings',
    reset_cron: 'reset_cron_settings',
    get_cron: 'get_cron_settings',
    save_order: 'save_order_settings',
    reset_order: 'reset_order_settings',
    get_order: 'get_order_settings',
    save_price: 'save_price_settings',
    reset_price: 'reset_price_settings',
    get_price: 'get_price_settings',
    save_fields: 'save_fields_settings',
    reset_fields: 'reset_fields_settings',
    get_fields: 'get_fields_settings',
}
export const taxEnabled = window.zoho_inventory_admin.wc_tax_enabled === '1'
export const roles = window.zoho_inventory_admin.roles
export const b2b_enabled = window.zoho_inventory_admin.b2b_enabled === '1'
export const imagick_enabled = window.zoho_inventory_admin.imagick_enabled === '1'
export const redirect_uri = window.zoho_inventory_admin.redirect_uri
export const origin = window.location.origin;
/**
 * Generates the AJAX URL for a given action.
 *
 * @param {string} action - The action to be performed.
 * @return {string} The generated AJAX URL.
 */
export const ajaxUrl = (action: string): string => {
    const url = `${window.zoho_inventory_admin.url}`;
    const securityToken = `${window.zoho_inventory_admin.security_token}`;
    return `${url}?security_token=${securityToken}&action=wooventory-app-${action}`;
}


/**
 * Extracts options from data based on warehouse ID and name.
 * @param {Array<Object>} data - The data to extract options from.
 * @param {string} key - The key for the warehouse ID in the data.
 * @param {string} value - The key for the warehouse name in the data.
 * @returns {Object} - The extracted options.
 */
export function extractOptions(
    data: Array<Object>,
    key: string,
    value: string
): Object {
    const result = {};

    for (const item of data) {
        result[item.key] = item.value;
    }
    return result;
}


/**
 * Returns an object with functions to interact with the browser's local storage.
 *
 * @return {Object} An object with the following methods:
 * - `save(key: string, data: Object)`: Saves `data` to local storage with the given `key`.
 * - `get(key: string)`: Retrieves data from local storage with the given `key`.
 * - `remove(key: string)`: Removes data from local storage with the given `key`.
 * - `hasNew(data: Object)`: Checks if `data` is different from the data stored with the same key.
 */
export const useStorage = (): UseStorage => {
    const prefix: string = "wooventory-app-storage";

    const getKey = (key: string) => `${prefix}-${key}`;
    const hasNew = (key: string, data: Object) => {
        const oldItems = get(getKey(key));
        return oldItems !== JSON.stringify(data);
    };
    const save = (key: string, data: Object) => {
        if (hasNew(key, data)) {
            localStorage.setItem(getKey(key), JSON.stringify(data));
        }
    };
    const get = (key: string) => {
        const data = localStorage.getItem(getKey(key));
        return data ? JSON.parse(data) : false;
    };

    const remove = (key: string) => {
        localStorage.removeItem(getKey(key));
    }

    const removeAll = () => {
        localStorage.clear();
        location.reload();
    }

    return {
        save,
        get,
        remove,
        removeAll,
        hasNew
    };
};


/**
 * Formats a given date string into a localized date and time string.
 *
 * @param {string} dateString - The date string to be formatted.
 * @return {string} The formatted date string.
 */
export const formatDate = (dateString: string): string => {
    const date = new Date(dateString);

    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}


/**
 * Generates a basic authentication string for the API.
 *
 * @return {string} The basic authentication string.
 */
export const basicAuth = (): string => {
    let hash = btoa(`${import.meta.env.VITE_APP_API_KEY}:${import.meta.env.VITE_APP_API_SECRET}`);
    return "Basic " + hash;
};

export const ucwords = (str: string): string => str.replace(/\b\w/g, char => char.toUpperCase());

export const notify: Notyf = new Notyf({
    position: { x: 'center', y: 'bottom' },
    dismissible: true,
    ripple: true,
});


/**
 * Fetches data from the server based on the provided action and stores it in the specified location.
 *
 * @param {string} action - The action to be performed on the server.
 * @param {string} storeKey - The key to store the fetched data in.
 * @return {Promise<any>} The fetched data.
 */
export const fetchData = async (action: string, storeKey: string): Promise<any> => {
    if (action === undefined) return;
    let response = await fetch(ajaxUrl(action));
    if (!response) return;
    let data = await response.json();
    if (data.success) {
        useStorage().save(storeKey, data.data);
        return data.data;
    } else {
        if (data.message) {
            notify.error(data.message);
        }
        return false;
    }
};

/**
 * Sends data to the server using AJAX.
 *
 * @param {string} action - The action to be performed on the server.
 * @param request
 * @param {string} storageKey - The key to store the returned data in local storage.
 * @return {Promise<any>} A promise that resolves to the returned data from the server.
 */
export const sendData = async (action: string, request: Object, storageKey: string): Promise<any> => {
    const headers = { 'Content-Type': 'application/json' };
    const requestBody = JSON.stringify(request);

    let response = await fetch(ajaxUrl(action), { method: 'POST', headers, body: requestBody });
    let data: any = await response.json();
    if (data.success) {
        useStorage().save(storageKey, request);
        return data.data;
    } else {
        notify.error(data.data.message);
    }
}

/**
 * Resets the data by performing an asynchronous action and removing data from storage.
 *
 * @param {string} action - The action to perform.
 * @param {string} storeKey - The key of the data to remove from storage.
 * @return {Promise<any>} The data returned from the action, if successful.
 */
export const resetData = async (action: string, storeKey: string): Promise<any> => {
    const response = await fetch(ajaxUrl(action));

    const data = await response.json()
    if (data.success) {
        useStorage().remove(storeKey)
        return data.data
    } else {
        if (data.message) notify.error(data.message)
        return
    }
}