import { backendAction, fetchData, notify, resetData, sendData, storeKey, useStorage } from '@/composables'
import { acceptHMRUpdate, defineStore } from 'pinia'
import { reactive, ref, watch } from 'vue'
import { useLoadingStore } from './loading'
import type { Ref, UnwrapRef } from 'vue'
import type { Subscription } from '@/type'
export const useHomepageStore = defineStore('homepage', () => {
    const storage = useStorage()
    const loader = useLoadingStore()
    const settings = reactive({
        cors: false,
        id: ''
    })
    const subscription: Ref<Subscription> = ref({})
    const changelog = ref('')
    const invalidId = ref(false)


    const get_changelog = async () => {
        const instore = storage.get(storeKey.changelog)
        if (instore) {
            changelog.value = instore
        }
        if (loader.isLoading(backendAction.get_changelog)) return
        loader.setLoading(backendAction.get_changelog)
        await fetch('https://wooventory.com/wp-json/wp/v2/changelog')
            .then(response => response.json())
            .then(data => {
                changelog.value = data
                storage.save(storeKey.changelog, data)
            }).finally(() => {
                loader.clearLoading(backendAction.get_changelog)
            })
    }

    const get_subscription = async () => {
        const instore = storage.get(storeKey.subscription)
        if (instore) {
            subscription.value = instore
        }
        if (settings.id === '' || settings.id === null || settings.id === undefined) return
        if (loader.isLoading(backendAction.get_subscription)) return
        loader.setLoading(backendAction.get_subscription)
        const response = await fetchData(backendAction.get_subscription, storeKey.subscription);
        if (response) {
            subscription.value = response
            storage.save(storeKey.subscription, response)
        }
        loader.clearLoading(backendAction.get_subscription)

    }
    const load = async () => {
        await get_settings();
        await get_subscription();
        await get_changelog();
    }
    const get_settings = async () => {
        if (loader.isLoading(backendAction.get_settings)) return
        loader.setLoading(backendAction.get_settings)
        const store: { cors: boolean, id: string } = storage.get(storeKey.settings)
        if (store) {
            settings.cors = store.cors
            settings.id = store.id
        } else {
            const response = await fetchData(backendAction.get_settings, storeKey.settings);
            if (response) {
                settings.cors = response.cors
                settings.id = response.id || ''
                await get_subscription()
            }
        }
        loader.clearLoading(backendAction.get_settings)
    }


    const save_settings = async () => {
        if (loader.isLoading(backendAction.save_settings)) return
        loader.setLoading(backendAction.save_settings)
        const response = await sendData(backendAction.save_settings, settings, storeKey.settings);
        if (response) {
            storage.save(storeKey.settings, settings)
            if (response.message) {
                notify.success(response.message)
            }
            await get_subscription();
        }
        loader.clearLoading(backendAction.save_settings)

    }

    const reset_settings = async () => {
        if (loader.isLoading(backendAction.reset_settings)) return
        loader.setLoading(backendAction.reset_settings)
        await resetData(backendAction.reset_settings, storeKey.settings).then(() => {
            settings.cors = false
            settings.id = ''
            subscription.value = []
            invalidId.value = false
            storage.remove(storeKey.subscription)
        });

        loader.clearLoading(backendAction.reset_settings)
    }

    watch(settings, () => {
        if (settings.id !== '') {
            invalidId.value = Number.isNaN(parseInt(settings.id))
        }
    })
    return {
        save_settings,
        reset_settings,
        settings,
        subscription,
        changelog,
        invalidId,
        load
    }
})

if (import.meta.hot) {
    import.meta.hot.accept(acceptHMRUpdate(useHomepageStore, import.meta.hot))
}

