import type { BackendAction, StoreKey } from "@/types";

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
        payment_status: 'exactOnline_payment_status',
        webhooks: 'exactOnline_webhooks',
        gl_account: 'exactOnline_gl_account',
    },
    zohoCrm: {
        connected: 'zcrm_connected',
        connect: 'zcrm_connect',
        order: 'zcrm_order',
        fields: 'zoho_custom_fields',
        refresh_zoho_fields: 'refresh_zoho_fields',
        sales_orders_fields: 'zcrm_sales_orders_fields',
        contacts_fields: 'zcrm_contacts_fields',
        products_fields: 'zcrm_products_fields',
        sales_orders_custom_fields: 'sales_orders_custom_fields',
        contacts_custom_fields: 'contacts_custom_fields',
        products_custom_fields: 'products_custom_fields',
    },

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
        gl_account: {
            get: 'get_exact_online_gl_account',
            save: 'save_exact_online_gl_account',
            reset: 'reset_exact_online_gl_account'
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
        },
        payment_status: {
            get: 'get_exact_online_payment_status',
            save: 'save_exact_online_payment_status',
            reset: 'reset_exact_online_payment_status'
        },
        webhooks: {
            get: 'get_exact_online_webhooks',
            save: 'save_exact_online_webhooks',
            reset: 'reset_exact_online_webhooks'
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
            get: 'zcrm_get_custom_fields',
            save: 'zcrm_save_custom_fields',
            reset: 'zcrm_reset_custom_fields'
        },
        connection: 'is_zcrm_connected',
        custom_fields: 'get_all_custom_fields',
        refresh_zcrm_fields: 'refresh_zcrm_fields',
        zcrm_fields: 'zcrm_fields',
    },
    acf_fields: {
        get_acf_fields: 'get_acf_fields',
    }
}