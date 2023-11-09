<template>
  <transition enter-active-class="duration-500 ease-in-out bg-black" enter-from-class="opacity-0"
              enter-to-class="opacity-25" leave-active-class="duration-500 ease-in-out bg-black"
              leave-from-class="opacity-25"
              leave-to-class="opacity-0">
    <slot/>
  </transition>
</template>
  