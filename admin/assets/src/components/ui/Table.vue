<script lang="ts" setup>
defineProps({
  rows: {
    type: Array,
    default: []
  }
})
</script>

<template>
  <div v-if="rows && Object.keys(rows).length" class="relative flex flex-col gap-8 mt-4">
    <div class="relative">
      <div aria-hidden="true" class="absolute inset-0 flex items-center">
        <div class="w-full border-t border-gray-300"></div>
      </div>
      <div class="relative flex justify-center">
        <span class="px-3 text-lg font-medium text-gray-900 bg-white">Results</span>
      </div>
    </div>
    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
      <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
          <table class="min-w-full">
            <thead class="bg-white">
            <tr>
              <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6"
                  scope="col">Action
              </th>
              <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900" scope="col">
                WooCommerce ID
              </th>
              <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900" scope="col">Log
                messages
              </th>
            </tr>
            </thead>
            <tbody class="bg-white">

            <tr v-for="(row, index) in rows" :key="index" class="border-t border-gray-300">
              <td class="py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{
                  row.resp_id
                }}
              </td>
              <td class="px-3 py-4 text-sm text-gray-500 ">{{ row.woo_prod_id }}</td>
              <td class="px-3 py-4 text-sm text-gray-500 ">{{ row.message }}</td>
            </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
