import { acceptHMRUpdate, defineStore } from 'pinia'
import type { Ref } from 'vue'
import { reactive, ref, watch } from 'vue'
import { useLoadingStore } from './loading'
import type { Changelog, Subscription } from '@/types'
import { useStorage } from "@/composable/storage";
import { backendAction, storeKey } from "@/keys";
import { fetchData, resetData, sendData } from "@/composable/http";
import { notify } from "@/composable/helpers";
import Swal from 'sweetalert2'

export const useHomepageStore = defineStore('homepage', () => {
    const storage = useStorage()
    const loader = useLoadingStore()
    const settings = reactive({
        cors: false,
        id: '',
        email: ''
    })
    const subscription: Ref<Subscription | {}> = ref({})
    const changelog: Ref<Changelog[]> = ref([])
    const invalidId = ref(false)
    const invalidEmail = ref(false)


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
        // Validate the email
        if (!settings.email || !settings.email.includes('@')) {
            invalidEmail.value = true;
            return;
        }
        const action = backendAction.homepage.subscription;
        if (loader.isLoading(action)) return
        loader.setLoading(action)
        const response = await fetchData(action, key);
        if (response) {
            subscription.value = response
            // Check if the emails match
            if (response.billing.email === settings.email) {
                storage.save(key, response); // Save the subscription only if emails match
                invalidEmail.value = false; // Clear invalidEmail if the emails match
            } else {
                subscription.value = {};
                storage.remove(key);
                invalidEmail.value = true;
                Swal.fire({
                    title: 'Invalid Email Address',
                    text: 'The email address you entered does not match the email of the subscription. Please enter the correct email address.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        }
        loader.clearLoading(action)
    }
    const isPremiumSubscription = async () => {
        if (!subscription.value) await get_subscription();
        return (subscription.value as Subscription).plan.includes('Premium')
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
        const store: { cors: boolean, id: string, email: string } = storage.get(key)
        // if store.email is empty then remove the key
        if (store && !store.email) {
            subscription.value = {};
            storage.remove(key);
            storage.remove(storeKey.homepage.subscription);
            invalidEmail.value = true;
            loader.clearLoading(action);
        }
        if (store) {
            settings.cors = store.cors
            settings.id = store.id
            settings.email = store.email
        } else {
            const response = await fetchData(action, key);
            if (response) {
                settings.cors = response.cors
                settings.id = response.id || ''
                settings.email = response.email || ''
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
        if (response && settings.id !== '' && settings.email !== '') {
            storage.save(key, settings)
            if (response.message) {
                notify.success(response.message)
            }
            await get_subscription();
        } else {
            storage.remove(key)
            if (response.message) {
                notify.error(response.message)
            }
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
            settings.email = ''
            subscription.value = {
                currency: "",
                billing: {},
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
        // Update invalidEmail if email is empty or doesn't include '@'
        if (!settings.email || !settings.email.includes('@')) {
            invalidEmail.value = true;
        }
    })
    return {
        save_settings,
        reset_settings,
        get_subscription,
        isPremiumSubscription,
        settings,
        subscription,
        changelog,
        invalidId,
        invalidEmail,
        load
    }
})

if (import.meta.hot) {
    import.meta.hot.accept(acceptHMRUpdate(useHomepageStore, import.meta.hot))
}

