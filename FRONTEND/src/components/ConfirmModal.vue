<script setup>
import { useConfirm } from '../composables/useConfirm';

const { isOpen, modalTitle, modalMessage, confirmBtnText, cancelBtnText, confirmAction, cancelAction } = useConfirm();
</script>

<template>
  <Transition name="fade">
    <div v-if="isOpen" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-gray-900 bg-opacity-75 backdrop-blur-sm">
      
      <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full overflow-hidden transform transition-all border-t-4 border-red-600">
        <div class="p-6">
          <div class="flex items-center gap-4 mb-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 leading-tight">{{ modalTitle }}</h3>
          </div>
          
          <p class="text-sm text-gray-600 font-medium ml-16 leading-relaxed">
            {{ modalMessage }}
          </p>
        </div>
        
        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-100">
          <button 
            @click="cancelAction" 
            class="px-4 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50 text-gray-700 font-medium transition"
          >
            {{ cancelBtnText }}
          </button>
          <button 
            @click="confirmAction" 
            class="px-4 py-2 bg-red-600 rounded hover:bg-red-700 text-white font-bold shadow transition"
          >
            {{ confirmBtnText }}
          </button>
        </div>
      </div>

    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>