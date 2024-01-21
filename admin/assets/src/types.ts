import type {FunctionalComponent} from "vue";

export type PluginObject = {
    security_token: string;
    redirect_uri: string;
    url: string;
    wc_tax_enabled: string;
    roles: {
        editor: string;
        administrator: string;
        contributor: string;
        subscriber: string;
        shop_manager: string;
        author: string;
        customer: string
    };
    b2b_enabled: string;
    fileinfo_enabled: string;
    acf_enabled: string;
    site_url: string;
}


export type Message = {
    icon: FunctionalComponent;
    message: string;
    link: string;
    linkText: string;
}

export type ConnectionSettings = {
    organization_id: string;
    client_id: string;
    client_secret: string;
    redirect_uri: string;
    account_domain: string;
}


export type TaxSettings = {
    decimalTax: boolean;
    selectedTaxRates: any[];
    selectedVatExempt: string;
}

export type ProductSettings = {
    item_from_zoho: boolean;
    disable_stock_sync: boolean;
    disable_product_sync: boolean;
    enable_accounting_stock: boolean;
}

export type CronSettings = {
    disable_name_sync: boolean;
    disable_price_sync: boolean;
    disable_image_sync: boolean;
    disable_description_sync: boolean;
    zi_cron_interval: string;
}

export type Intervals = {
    none: string;
    twicedaily: string;
    daily: string;
}

export type OrderSettings = {
    package_sync: boolean;
    disable_sync: boolean;
    enable_auto_number: boolean;
    enable_order_status: boolean;
    enable_multicurrency: boolean;
    enable_warehousestock: boolean;
    order_prefix: string;
    warehouse_id: string;
}

export type PriceSettings = {
    zoho_inventory_pricelist: string;
    wp_user_role: string;
}

export type StoreKey = {
    homepage: {
        settings: string
        changelog: string
        subscription: string;
    }
    exactOnline: {
        connect: string;
        cost_center: string;
        cost_unit: string;
        customer: string;
        product: string;
    };

    zohoInventory: {
        connected: string;
        connect: string;
        tax: string;
        product: string;
        cron: string;
        order: string;
        price: string;
        fields: string;
        wc_tax: string;
        zoho_categories: string;
        zoho_tax: string;
        zoho_warehouses: string;
    };
}

export type BackendAction = {
    homepage: {
        settings: { get: string; save: string; reset: string; };
        changelog: string;
        subscription: string;
    }
    exactOnline: {
        connect: { get: string; save: string; reset: string; };
        product: { get: string; save: string; reset: string; };
        customer: { get: string; save: string; reset: string; };
        cost_center: { get: string; save: string; reset: string; };
        cost_unit: { get: string; save: string; reset: string; };
    };

    zohoInventory: {
        connect: { get: string; save: string; reset: string; };
        tax: { get: string; save: string; reset: string; };
        product: { get: string; save: string; reset: string; };
        cron: { get: string; save: string; reset: string; };
        order: { get: string; save: string; reset: string; };
        price: { get: string; save: string; reset: string; };
        field: { get: string; save: string; reset: string; };
        custom_fields: string;
        wc_taxes: string;
        zoho_categories: string;
        zoho_prices: string;
        zoho_taxes: string;
        zoho_warehouses: string;
        connection: string;
    };
}


export type UseStorage = {
    save: (key: string, data: Object) => void;
    get: (key: string) => any;
    remove: (key: string) => void;
    removeAll: () => void;
    hasNew: (key: string, data: Object) => boolean;
}

export type WcTax = {
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

export type ZohoTax = {
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


export type Subscription = {
    status: string;
    currency: string;
    total: string;
    fee_lines: FeeLine[];
    payment_url: string;
    needs_payment: boolean;
    next_payment_date_gmt: string;
    variation_id: number[];
    plan: string[];
}

export type FeeLine = {
    id: number;
    name: string;
    tax_class: string;
    tax_status: string;
    amount: string;
    total: string;
    total_tax: string;
    taxes: Tax[];
    meta_data: any[];
}

export type Tax = {
    id: number;
    total: string;
    subtotal: string;
}


export type Changelog = {
    id: number;
    date: string;
    date_gmt: string;
    guid: { rendered: string };
    modified: string;
    modified_gmt: string;
    slug: string;
    status: string;
    type: string;
    link: string;
    title: { rendered: string };
    content: { rendered: string; protected: boolean };
    template: string;
    logtype: number[];
    acf: any[];
    aioseo_notices: any[];
    _links: {
        curies: { templated: boolean; name: string; href: string }[];
        "wp:term": { taxonomy: string; href: string; embeddable: boolean }[];
        about: { href: string }[];
        self: { href: string }[];
        collection: { href: string }[];
        "wp:attachment": { href: string }[]
    };
}
