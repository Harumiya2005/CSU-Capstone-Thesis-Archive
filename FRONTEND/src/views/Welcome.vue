<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

const goToLogin = () => {
  router.push('/login');
};
</script>

<template>
  <div class="min-h-screen relative flex flex-col items-center justify-center font-sans bg-gray-900 selection:bg-yellow-400 selection:text-yellow-900 overflow-hidden">
    
    <div class="absolute inset-0 z-0">
      <img src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=2070&auto=format&fit=crop" alt="Campus" class="w-full h-full object-cover opacity-30 mix-blend-overlay transform scale-105">
      <div class="absolute inset-0 bg-gradient-to-br from-[#16a34a]/95 via-green-800/90 to-gray-900/95 backdrop-blur-[2px]"></div>
      
      <div class="absolute top-0 left-1/4 w-[30rem] h-[30rem] bg-white opacity-5 rounded-full blur-3xl"></div>
      <div class="absolute bottom-0 right-1/4 w-[30rem] h-[30rem] bg-yellow-400 opacity-5 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 flex flex-col items-center text-center px-4 max-w-4xl animate-fade-in-up">
      
      <div class="inline-flex items-center justify-center p-5 bg-white/10 rounded-2xl backdrop-blur-md border border-white/20 shadow-2xl mb-8 transform hover:scale-105 transition-transform duration-500">
        <svg class="w-16 h-16 text-yellow-400 drop-shadow-lg" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72l5 2.73 5-2.73v3.72z"/>
        </svg>
      </div>
      
      <h2 class="text-xl md:text-2xl font-medium text-green-200 mb-3 tracking-widest uppercase drop-shadow">
        Caraga State University
      </h2>
      <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold text-white mb-6 tracking-tight drop-shadow-lg leading-tight">
        CCIS Capstone & <br class="hidden md:block" />
        <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-yellow-500 filter drop-shadow-sm">Thesis Archive</span>
      </h1>
      
      <p class="text-lg md:text-xl text-green-100 max-w-2xl mb-12 font-medium leading-relaxed drop-shadow">
        The official digital repository for student research, innovations, and technical web projects developed by the College of Computing and Information Sciences.
      </p>

      <button 
        @click="goToLogin" 
        class="group relative inline-flex items-center justify-center bg-gradient-to-r from-yellow-400 to-yellow-500 text-yellow-950 font-extrabold text-lg md:text-xl py-4 px-10 rounded-full overflow-hidden shadow-[0_8px_30px_rgb(250,204,21,0.3)] hover:shadow-[0_8px_40px_rgb(250,204,21,0.5)] transform hover:-translate-y-1 transition-all duration-300"
      >
        <span class="relative z-10 flex items-center gap-3">
          Access the Archive
          <svg class="w-6 h-6 transform group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
        </span>
        <div class="absolute inset-0 -translate-x-full group-hover:animate-[shimmer_1.5s_infinite] bg-gradient-to-r from-transparent via-white/40 to-transparent z-0"></div>
      </button>
    </div>

    <div class="absolute bottom-0 w-full p-6 text-center z-10 flex flex-col items-center justify-center">
      <div class="w-24 h-1 bg-gradient-to-r from-transparent via-green-400/50 to-transparent mb-4"></div>
      <p class="text-green-200/70 text-sm font-medium tracking-wide">
        © {{ new Date().getFullYear() }} Caraga State University | Creating Futures. Empowering Communities.
      </p>
    </div>
  </div>
</template>

<style scoped>
.animate-fade-in-up {
  animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeInUp {
  from { 
    opacity: 0; 
    transform: translateY(30px) scale(0.95); 
  }
  to { 
    opacity: 1; 
    transform: translateY(0) scale(1); 
  }
}

@keyframes shimmer {
  100% { transform: translateX(100%); }
}
</style>