import {acceptHMRUpdate, defineStore} from 'pinia'
import type {Ref} from 'vue'
import {reactive, ref, watch} from 'vue'
import {useLoadingStore} from './loading'
import type {Changelog, Subscription} from '@/types'
import {useStorage} from "@/composable/storage";
import {backendAction, storeKey} from "@/keys";
import {fetchData, resetData, sendData} from "@/composable/http";
import {notify} from "@/composable/helpers";

export const useHomepageStore = defineStore('homepage', () => {
    const storage = useStorage()
    const loader = useLoadingStore()
    const settings = reactive({
        cors: false,
        id: ''
    })
    const subscription: Ref<Subscription> = ref({})
    const changelog: Ref<Changelog[]> = ref([])
    const invalidId = ref(false)


    const get_changelog = async () => {
        const log = storeKey.homepage.changelog;
        const instore = storage.get(log)
        if (instore) {
            changelog.value = instore
        }
        const action = backendAction.homepage.changelog;
        if (loader.isLoading(action)) return
        loader.setLoading(action)
        await fetch('https://commercebird.com/wp-json/wp/v2/changelog')
            .then(response => response.json())
            .then(data => {
                changelog.value = data
                storage.save(log, data)
            }).finally(() => {
                loader.clearLoading(action)
            })
    }

    const get_subscription = async () => {
        const key = storeKey.homepage.subscription;
        const instore = storage.get(key)
        if (instore) {
            subscription.value = instore
        }
        if (settings.id === '' || settings.id === null || settings.id === undefined) return
        const action = backendAction.homepage.subscription;
        if (loader.isLoading(action)) return
        loader.setLoading(action)
        const response = await fetchData(action, key);
        if (response) {
            subscription.value = response
            storage.save(key, response)
        }
        loader.clearLoading(action)

    }
    const load = async () => {
        await get_settings();
        await get_subscription();
        await get_changelog();
    }
    const get_settings = async () => {
        const action = backendAction.homepage.settings.get;
        const key = storeKey.homepage.settings;
        if (loader.isLoading(action)) return
        loader.setLoading(action)
        const store: { cors: boolean, id: string } = storage.get(key)
        if (store) {
            settings.cors = store.cors
            settings.id = store.id
        } else {
            const response = await fetchData(action, key);
            if (response) {
                settings.cors = response.cors
                settings.id = response.id || ''
                await get_subscription()
            }
        }
        loader.clearLoading(action)
    }


    const save_settings = async () => {
        const action = backendAction.homepage.settings.save;
        const key = storeKey.homepage.settings;
        if (loader.isLoading(action)) return
        loader.setLoading(action)
        const response = await sendData(action, settings, key);
        if (response) {
            storage.save(key, settings)
            if (response.message) {
                notify.success(response.message)
            }
            await get_subscription();
        }
        loader.clearLoading(action)

    }

    const reset_settings = async () => {
        const action = backendAction.homepage.settings.reset;
        const key = storeKey.homepage.settings;
        if (loader.isLoading(action)) return
        loader.setLoading(action)
        await resetData(action, key).then(() => {
            settings.cors = false
            settings.id = ''
            subscription.value = {
                currency: "",
                fee_lines: [],
                needs_payment: false,
                next_payment_date_gmt: "",
                payment_url: "",
                plan: [],
                status: "",
                total: "",
                variation_id: []
            }
            invalidId.value = false
            storage.remove(storeKey.homepage.subscription);
        });

        loader.clearLoading(action)
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

