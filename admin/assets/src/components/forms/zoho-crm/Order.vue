<script setup lang="ts">
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseForm from '@/components/ui/BaseForm.vue';
import InputGroup from '@/components/ui/inputs/InputGroup.vue';
import { backendAction } from '@/keys';
import { useExactOnlineStore } from '@/stores/exactOnline';
import { useLoadingStore } from '@/stores/loading';
import { ref } from 'vue';
import VueTailwindDatepicker from "vue-tailwind-datepicker";
const store = useExactOnlineStore();
const loader = useLoadingStore();
const formatter = ref({
    date: 'DD MMM YYYY',
    month: 'MMM',
})
const action = backendAction.exactOnline.order;
</script>
<template>
    <div class="pt-4 space-y-4">
        <InputGroup label="Date Range">
            <vue-tailwind-datepicker v-model="store.dateRange" :formatter="formatter" as-single use-range />
        </InputGroup>
        <div class="flex gap-4">
            <BaseButton :loading="loader.isLoading(action.export)" @click="store.exportOrders()">
                Export Orders
            </BaseButton>
        </div>

    </div>
</template>