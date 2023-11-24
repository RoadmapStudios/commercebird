<template>
  <transition enter-active-class="transition duration-500 ease-in-out transform sm:duration-700"
              enter-from-class="translate-x-full" enter-to-class="translate-x-0"
              leave-active-class="transition duration-500 ease-in-out transform sm:duration-700"
              leave-from-class="translate-x-0"
              leave-to-class="translate-x-full">
    <slot/>
  </transition>
</template>