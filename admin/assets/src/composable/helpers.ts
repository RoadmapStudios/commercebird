import {Notyf} from "notyf";
import type {PluginObject} from "@/types";

declare global {
    interface Window {
        commercebird_admin: PluginObject
    }
}
export const baseurl = window.commercebird_admin.url
export const site_url = window.commercebird_admin.site_url
export const security_token = window.commercebird_admin.security_token
export const taxEnabled = window.commercebird_admin.wc_tax_enabled === '1'
export const roles = window.commercebird_admin.roles
export const b2b_enabled = window.commercebird_admin.b2b_enabled === '1'
export const fileinfo_enabled = window.commercebird_admin.fileinfo_enabled === '1'
export const redirect_uri = window.commercebird_admin.redirect_uri
export const acf_enabled = window.commercebird_admin.acf_enabled === '1'
export const origin = window.location.origin;


/**
 * Extracts options from data based on warehouse ID and name.
 * @param {Array<{ [key: string]: string}>} data - The data to extract options from.
 * @param {string} key - The key for the warehouse ID in the data.
 * @param {string} value - The key for the warehouse name in the data.
 * @returns {Object} - The extracted options.
 */
export function extractOptions(
    data: Array<{ [key: string]: string }>,
    key: string,
    value: string
): Object {
    return data.reduce((result, item) => {
        result[item[key]] = item[value];
        return result;
    }, {});
}


/**
 * Formats a given date string into a localized date and time string.
 *
 * @param {string} dateString - The date string to be formatted.
 * @return {string} The formatted date string.
 */
export const formatDate = (dateString: string): string => {
    const date = new Date(dateString);

    return date.toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'});
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

export const uc_words = (str: string): string => str.replace(/\b\w/g, char => char.toUpperCase());

export const notify: Notyf = new Notyf({
    position: {x: 'center', y: 'bottom'},
    dismissible: true,
    ripple: true,
});