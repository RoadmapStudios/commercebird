import {acceptHMRUpdate, defineStore} from 'pinia'
import {ref} from 'vue'

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

    return {
        loading,
        setLoading,
        isLoading,
        isRunning,
        clearLoading
    }
})

if (import.meta.hot) {
    import.meta.hot.accept(acceptHMRUpdate(useLoadingStore, import.meta.hot))
}
