import type {BackendAction, StoreKey} from "@/types";

export const storeKey: StoreKey = {
    currentRoute: 'commercebird_current_route',
    homepage: {
        changelog: 'changelog',
        settings: 'settings',
        subscription: 'subscription',
    },
    zohoInventory: {
        connected: 'zoho_connected',
        connect: 'zoho_connect',
        cron: 'zoho_cron',
        order: 'zoho_order',
        contact: 'zoho_contact',
        price: 'zoho_price',
        product: 'zoho_product',
        tax: 'zoho_tax',
        fields: 'zoho_custom_fields',
        wc_tax: 'wc_taxes',
        zoho_categories: 'zoho_categories',
        zoho_tax: 'zoho_taxes',
        zoho_warehouses: 'zoho_warehouses',
    },
    exactOnline: {
        connect: 'exactOnline_connect',
        product: 'exactOnline_product',
        order: 'exactOnline_order',
        customer: 'exactOnline_customer',
        cost_center: 'exactOnline_cost_center',
        cost_unit: 'exactOnline_cost_unit',
    },
    zohoCrm:{
        connect: 'zcrm_connect',
        order: 'zcrm_order',
        fields: 'zcrm_custom_fields',

    }

}


export const backendAction: BackendAction = {
    exactOnline: {
        connect: {
            get: 'get_exact_online_connect',
            save: 'save_exact_online_connect',
            reset: 'reset_exact_online_connect'
        },
        product: {
            map: 'map_exact_online_product',
        },
        customer: {
            map: 'map_exact_online_customer'
        },
        order: {
            map: 'map_exact_online_order',
            export: 'export_exact_online_order',
            sync: 'save_sync_order_via_cron'
        },
        cost_center: {
            get: 'get_exact_online_cost_center',
            save: 'save_exact_online_cost_center',
            reset: 'reset_exact_online_cost_center'
        },
        cost_unit: {
            get: 'get_exact_online_cost_unit',
            save: 'save_exact_online_cost_unit',
            reset: 'reset_exact_online_cost_unit'
        }
    },
    homepage: {
        changelog: 'get_changelog',
        settings: {
            get: 'get_settings',
            save: 'save_settings',
            reset: 'reset_settings'
        },
        subscription: 'get_subscription'

    },
    zohoInventory: {
        connect: {
            get: 'get_zoho_connect',
            save: 'save_zoho_connect',
            reset: 'reset_zoho_connect'
        },
        tax: {
            get: 'get_zoho_tax',
            save: 'save_zoho_tax',
            reset: 'reset_zoho_tax'
        },
        product: {
            get: 'get_zoho_product',
            save: 'save_zoho_product',
            reset: 'reset_zoho_product'
        },
        cron: {
            get: 'get_zoho_cron',
            save: 'save_zoho_cron',
            reset: 'reset_zoho_cron'
        },
        order: {
            get: 'get_zoho_order',
            save: 'save_zoho_order',
            reset: 'reset_zoho_order'
        },
        contact: {
            get: 'get_zoho_contact',
            save: 'save_zoho_contact',
            reset: 'reset_zoho_contact'
        },
        price: {
            get: 'get_zoho_price',
            save: 'save_zoho_price',
            reset: 'reset_zoho_price'
        },
        field: {
            get: 'get_zoho_fields',
            save: 'save_zoho_fields',
            reset: 'reset_zoho_fields'
        },
        connection: 'is_connected',
        wc_taxes: 'get_wc_taxes',
        zoho_taxes: 'get_zoho_taxes',
        zoho_categories: 'get_zoho_categories',
        custom_fields: 'get_all_custom_fields',
        zoho_prices: 'get_zoho_prices',
        zoho_warehouses: 'get_zoho_warehouses'
    },
    zohoCrm: {
        connect: {
            get: 'get_zcrm_connect',
            save: 'save_zcrm_connect',
            reset: 'reset_zcrm_connect'
        },
        order: {
            export: 'export_zcrm_order',
        },
        field: {
            get: 'get_zcrm_fields',
            save: 'save_zcrm_fields',
            reset: 'reset_zcrm_fields'
        },
        connection: 'is_connected',
        custom_fields: 'get_all_custom_fields',
        zcrm_fields:'get_all_zcrm_fields'

    }
}