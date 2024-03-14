<template>
    <div class="mt-4 space-y-2">
        <BaseForm :keys="action" @reset="store.handleReset(action.reset)" @submit="store.handleSubmit(action.save)">
            <InputGroup label="Enable Cron">
                <Toggle v-model="store.contact_settings.enable_cron" />
            </InputGroup>
        </BaseForm>
        <div class="relative">
            <div aria-hidden="true" class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="px-3 text-lg font-medium text-gray-900 bg-white">Sync Actions</span>
            </div>
        </div>

        <div class="flex flex-wrap gap-4 py-4">
            <BaseButton :disabled="loader.isLoading('category')" :loading="loader.isLoading('category')"
                @click.prevent="sync()">Contacts Sync
            </BaseButton>
        </div>
    </div>

</template>

<script setup lang="ts">
import BaseButton from "@/components/ui/BaseButton.vue";
import BaseForm from "@/components/ui/BaseForm.vue";
import InputGroup from "@/components/ui/inputs/InputGroup.vue";
import Toggle from "@/components/ui/inputs/Toggle.vue";
import { baseurl, notify } from "@/composable/helpers";
import { backendAction } from "@/keys";
import { useLoadingStore } from "@/stores/loading";
import { useZohoInventoryStore } from "@/stores/zohoInventory";
const action = backendAction.zohoInventory.contact;
const store = useZohoInventoryStore();
const loader = useLoadingStore();
const sync = async () => {
    const action = "import_zoho_contacts";
    if (loader.isLoading(action)) return;
    loader.setLoading(action);
    let url = `${baseurl}?action=${action}`;
    await fetch(url)
        .then((response) => response.json())
        .then((response) => {
            console.log(response);

            if (!response) return;
            if (response.success) {
                notify.success(response.message)
            } else {
                notify.error(response.message)
            }
            loader.clearLoading(action);
            return;
        })
};
</script>

<style scoped></style>