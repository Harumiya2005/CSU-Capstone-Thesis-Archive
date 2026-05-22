// src/composables/useToast.js
import { ref } from 'vue';

// These variables exist outside the function so they act as a Global State
const show = ref(false);
const message = ref('');
const type = ref('success'); // 'success', 'error', or 'info'
let timeout = null;

export function useToast() {
  const triggerToast = (msg, msgType = 'success', duration = 3000) => {
    message.value = msg;
    type.value = msgType;
    show.value = true;

    // Clear any existing timer so toasts don't overlap weirdly
    if (timeout) clearTimeout(timeout);

    // Auto-hide after the duration
    timeout = setTimeout(() => {
      show.value = false;
    }, duration);
  };

  const closeToast = () => {
    show.value = false;
    if (timeout) clearTimeout(timeout);
  };

  return { show, message, type, triggerToast, closeToast };
}