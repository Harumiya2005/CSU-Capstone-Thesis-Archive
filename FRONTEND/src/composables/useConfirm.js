// src/composables/useConfirm.js
import { ref } from 'vue';

// Global state variables
const isOpen = ref(false);
const modalTitle = ref('');
const modalMessage = ref('');
const confirmBtnText = ref('Confirm');
const cancelBtnText = ref('Cancel');

// This will store our Promise so we can resolve it later
let resolvePromise = null;

export function useConfirm() {
  const triggerConfirm = (options) => {
    modalTitle.value = options.title || 'Are you sure?';
    modalMessage.value = options.message || 'This action cannot be undone.';
    confirmBtnText.value = options.confirmText || 'Yes, Delete';
    cancelBtnText.value = options.cancelText || 'Cancel';
    isOpen.value = true;

    // Return a Promise that pauses the code until resolvePromise is called
    return new Promise((resolve) => {
      resolvePromise = resolve;
    });
  };

  const confirmAction = () => {
    isOpen.value = false;
    if (resolvePromise) resolvePromise(true); // User clicked Yes
  };

  const cancelAction = () => {
    isOpen.value = false;
    if (resolvePromise) resolvePromise(false); // User clicked Cancel
  };

  return { 
    isOpen, modalTitle, modalMessage, confirmBtnText, cancelBtnText, 
    triggerConfirm, confirmAction, cancelAction 
  };
}