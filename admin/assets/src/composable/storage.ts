import type {UseStorage} from "@/types";


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
    const prefix: string = "commercebird-app-storage";

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
