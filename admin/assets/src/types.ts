import type { FunctionalComponent } from "vue";

export type PluginObject = {
    wcb2b_groups: any;
    cosw_enabled: string;
    api_token: any;
    security_token: string;
    redirect_uri: string;
    url: string;
    webhooks: {
        'Items': string,
        'Order Create': string,
        'Shipping Status': string
    }
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
    wcb2b_enabled: string;
    site_url: string;
    eo_sync: string;
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
    selectedTaxRates: any[];
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
    disable_sync: boolean;
    enable_auto_number: boolean;
    enable_order_status: boolean;
    enable_multicurrency: boolean;
    enable_warehousestock: boolean;
    order_prefix: string;
    warehouse_id: string;
}

export type ContactSettings = {
    enable_cron: boolean
}

export type PriceSettings = {
    zoho_inventory_pricelist: string;
    wp_user_role: string;
}
export type ExactWebhookSettings = {
    enable_SalesInvoices: boolean;
    enable_StockPosition: boolean;
    enable_Item: boolean;
}

export type StoreKey = {
    currentRoute: string
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
        order: string;
        webhooks: string;
    };

    zohoInventory: {
        connected: string;
        connect: string;
        tax: string;
        product: string;
        cron: string;
        order: string;
        contact: string;
        price: string;
        fields: string;
        wc_tax: string;
        zoho_categories: string;
        zoho_tax: string;
        zoho_warehouses: string;
    };

    zohoCrm: {
        connect: string;
        order: string;
        fields: string;
        refresh_zoho_fields: string;
        sales_orders_fields: string;
        contacts_fields: string;
        products_fields: string;
        sales_orders_custom_fields: string;
        contacts_custom_fields: string;
        products_custom_fields: string;
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
        product: { map: string }
        customer: { map: string };
        order: { map: string, export: string, sync: string };
        cost_center: { get: string; save: string; reset: string; };
        cost_unit: { get: string; save: string; reset: string; };
        webhooks: { get: string; save: string; reset: string; };
    };

    zohoInventory: {
        connect: { get: string; save: string; reset: string; };
        tax: { get: string; save: string; reset: string; };
        product: { get: string; save: string; reset: string; };
        cron: { get: string; save: string; reset: string; };
        order: { get: string; save: string; reset: string; };
        contact: { get: string; save: string; reset: string; };
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

    zohoCrm: {
        connect: { get: string; save: string; reset: string; };
        order: { export: string };
        field: { get: string; save: string; reset: string; };
        custom_fields: string;
        refresh_zcrm_fields: string;
        zcrm_fields: string;
        connection: string;
    };

    acf_fields: {
        get_acf_fields: string;
    }
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
