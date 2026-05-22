<script setup>
import ToastNotification from './components/ToastNotification.vue';
import ConfirmModal from './components/ConfirmModal.vue'; // <-- Import it here
</script>

<template>
  <router-view />
  
  <ToastNotification />
  <ConfirmModal /> 
</template>