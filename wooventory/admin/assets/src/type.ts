import type {FunctionalComponent} from "vue";

export interface Message {
    icon: FunctionalComponent;
    message: string;
    link: string;
    linkText: string;
}

export interface ConnectionSettings {
    organization_id: string;
    client_id: string;
    client_secret: string;
    redirect_uri: string;
    account_domain: string;
}


export interface TaxSettings {
    decimalTax: boolean;
    selectedTaxRates: any[];
    selectedVatExempt: string;
}

export interface ProductSettings {
    item_from_zoho: boolean;
    disable_stock_sync: boolean;
    disable_product_sync: boolean;
    enable_accounting_stock: boolean;
}

export interface CronSettings {
    disable_name_sync: boolean;
    disable_price_sync: boolean;
    disable_image_sync: boolean;
    disable_description_sync: boolean;
    zi_cron_interval: string;
}

export interface Intervals {
    none: string;
    twicedaily: string;
    daily: string;
}

export interface OrderSettings {
    package_sync: boolean;
    disable_sync: boolean;
    enable_auto_number: boolean;
    enable_order_status: boolean;
    enable_multicurrency: boolean;
    enable_warehousestock: boolean;
    order_prefix: string;
    warehouse_id: string;
}

export interface PriceSettings {
    zoho_inventory_pricelist: string;
    wp_user_role: string;
}

export interface StoreKey {
    connected: string;
    changelog: string;
    subscription: string;
    settings: string;
    connection: string;
    tax: string;
    wc_tax: string;
    zoho_tax: string;
    product: string;
    cron: string;
    order: string;
    price: string;
    fields: string;
    zoho_categories: string;
    zoho_warehouses: string;
    order_custom_fields: string;

    [key: string]: string;
}

export interface BackendAction {
    get_settings: string;
    save_settings: string;
    reset_settings: string;
    get_changelog: string;
    is_connected: string;
    get_subscription: string;
    get_zoho_warehouses: string;
    get_zoho_prices: string;
    get_zoho_categories: string;
    get_zoho_taxes: string;
    get_all_custom_fields: string;
    get_wc_taxes: string;
    save_connect: string;
    reset_connect: string;
    get_connect: string;
    save_tax: string;
    reset_tax: string;
    get_tax: string;
    save_product: string;
    reset_product: string;
    get_product: string;
    save_cron: string;
    reset_cron: string;
    get_cron: string;
    save_order: string;
    reset_order: string;
    get_order: string;
    save_price: string;
    reset_price: string;
    get_price: string;
    save_fields: string;
    reset_fields: string;
    get_fields: string;
}


export interface UseStorage {
    save: (key: string, data: Object) => void;
    get: (key: string) => any;
    remove: (key: string) => void;
    removeAll: () => void;
    hasNew: (key: string, data: Object) => boolean;
}

export interface WcTax {
    tax_rate_id: string;
    tax_rate_country: string;
    tax_rate_state: string;
    tax_rate: string;
    tax_rate_name: string;
    tax_rate_priority: string;
    tax_rate_compound: string;
    tax_rate_shipping: string;
    tax_rate_order: number;
    tax_rate_class: string;
    postcode_count: number;
    city_count: number;
    id: number;
}

export interface ZohoTax {
    tax_id: string;
    tax_name: string;
    tax_percentage: number;
    tax_type: string;
    tax_specific_type: string;
    output_tax_account_name: string;
    purchase_tax_account_name: string;
    tax_account_id: string;
    purchase_tax_account_id: string;
    is_inactive: boolean;
    is_value_added: boolean;
    is_default_tax: boolean;
    is_editable: boolean;
    last_modified_time: string;
    status: string;
}
