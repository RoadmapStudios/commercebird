import {acceptHMRUpdate, defineStore} from 'pinia'
import {ref} from 'vue'
import {useStorage} from "@/composable/storage";
import {fetchData} from "@/composable/http";

export const useLoadingStore = defineStore('loading', () => {
    const loading = ref<string[]>([])
    /**
     * Sets the loading state for a given key.
     *
     * @param {string} key - The key to set the loading state for.
     */
    const setLoading = (key: string) => {
        loading.value.push(key)
    }

    /**
     * Check if a given key is in the loading array.
     *
     * @param {string} key - The key to check if it is in the loading array.
     * @return {boolean} - True if the key is in the loading array, otherwise false.
     */
    const isLoading = (key: string) => {
        return loading.value.includes(key)
    }

    /**
     * Remove the specified key from the loading value array.
     *
     * @param {string} key - The key to be removed from the loading value array.
     */
    const clearLoading = (key: string) => {
        loading.value.splice(loading.value.indexOf(key), 1)
    }

    const isRunning = () => loading.value.length !== 0
    /**
     * Loads data from storage based on the provided store key and action.
     *
     * @param {string} storeKey - The key used to identify the data in storage.
     * @param {string} action - The action associated with the data loading.
     * @return {Promise<any>} The loaded data from storage.
     */
    const loadData = async (storeKey: string, action: string) => {
        let instore = useStorage().get(storeKey);
        if (!instore || !instore.organization_id) {
            if (isLoading(action)) return;
            setLoading(action);
            instore = await fetchData(action, storeKey);
            clearLoading(action);
        }

        return instore;
    };
    return {
        loading,
        setLoading,
        isLoading,
        isRunning,
        clearLoading,
        loadData
    }
})

if (import.meta.hot) {
    import.meta.hot.accept(acceptHMRUpdate(useLoadingStore, import.meta.hot))
}
