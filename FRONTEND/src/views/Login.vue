<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../axios';

const router = useRouter();
const identifier = ref('');
const password = ref('');
const errorMessage = ref('');
const loading = ref(false);

const remember = ref(false);
const showPassword = ref(false);

onMounted(() => {
  const savedId = localStorage.getItem('remembered_id');
  if (savedId) {
    identifier.value = savedId;
    remember.value = true;
  }
});

const inputMode = computed(() => {
  if (!identifier.value) return 'none';
  // If first char is digit, it's a student ID
  return /^[0-9]/.test(identifier.value) ? 'id' : 'email';
});

const emailSuggestion = computed(() => {
  if (inputMode.value === 'email' && !identifier.value.includes('@')) {
    return '@carsu.edu.ph';
  }
  return '';
});

// Enforce max length dynamically
const maxLength = computed(() => {
  return inputMode.value === 'id' ? 9 : 255;
});

// Handle input filtering and formatting
const handleInput = (e) => {
  if (inputMode.value === 'id') {
    // 1. Get raw digits only (remove everything else)
    let rawValue = e.target.value.replace(/[^0-9]/g, '');
    
    // 2. Limit to 8 digits (which is what 231-00001 adds up to)
    rawValue = rawValue.substring(0, 8);
    
    // 3. Format it: insert hyphen after the first 3 digits
    if (rawValue.length > 3) {
      identifier.value = rawValue.substring(0, 3) + '-' + rawValue.substring(3);
    } else {
      identifier.value = rawValue;
    }
  }
};

const handleKeydown = (e) => {
  // If in Email mode and already have the full domain
  if (inputMode.value === 'email' && identifier.value.endsWith('@carsu.edu.ph')) {
    if (e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab') {
      e.preventDefault();
    }
  }

  // Handle Tab for Admin autocomplete
  if (e.key === 'Tab' && emailSuggestion.value) {
    e.preventDefault(); 
    identifier.value += emailSuggestion.value; 
  }
  
  // Mobile fix: If they hit backspace on the hyphen, remove the last digit
  if (e.key === 'Backspace' && identifier.value.endsWith('-')) {
    e.preventDefault();
    identifier.value = identifier.value.slice(0, -2);
  }
};
const handleLogin = async () => {
  // Final validation before sending
  if (inputMode.value === 'id' && identifier.value.length < 9) {
    errorMessage.value = "Student ID must be exactly 9 characters.";
    return;
  }

  loading.value = true;
  errorMessage.value = '';
  
  try {
    const response = await api.post('/login', {
      identifier: identifier.value,
      password: password.value
    });
    
    localStorage.setItem('token', response.data.token);
    localStorage.setItem('role', response.data.role);
    localStorage.setItem('name', response.data.name || response.data.user?.name); 

    if (remember.value) {
      localStorage.setItem('remembered_id', identifier.value);
    } else {
      localStorage.removeItem('remembered_id');
    }

    if (response.data.role === 'admin') {
      router.push('/admin');
    } else {
      router.push('/student');
    }
  } catch (error) {
    errorMessage.value = 'Invalid credentials. Please try again.';
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen relative flex flex-col items-center justify-center font-sans bg-gray-900 selection:bg-[#16a34a] selection:text-white">
    
    <div class="absolute inset-0 z-0 overflow-hidden">
      <img src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=2070&auto=format&fit=crop" alt="Campus" class="w-full h-full object-cover opacity-20 mix-blend-overlay transform scale-105">
      <div class="absolute inset-0 bg-gradient-to-br from-[#16a34a]/95 via-green-800/95 to-gray-900/95 backdrop-blur-[2px]"></div>
    </div>

    <div class="relative z-10 w-full max-w-md px-4 sm:px-6 animate-fade-in-up">
      
      <div class="flex flex-col items-center text-center mb-8">
        <div class="inline-flex items-center justify-center p-4 bg-white/10 rounded-2xl backdrop-blur-md border border-white/20 shadow-xl mb-4">
          <svg class="w-12 h-12 text-yellow-400 drop-shadow-md" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72l5 2.73 5-2.73v3.72z"/></svg>
        </div>
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Caraga State University</h1>
        <p class="text-green-200 font-medium mt-1">CSU Capstone & Thesis Archive</p>
      </div>

      <div class="bg-white/95 backdrop-blur-xl p-8 rounded-2xl shadow-2xl border border-white/50">
        <h2 class="text-2xl font-extrabold text-gray-900 text-center mb-6">Account Login</h2>

        <div v-if="errorMessage" class="mb-6 bg-red-50 border-l-4 border-red-500 flex items-start gap-3 text-red-700 p-4 text-sm rounded-r-lg shadow-sm">
          <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          <span class="font-medium">{{ errorMessage }}</span>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-4">
          
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">ID Number / Email</label>
            <div class="relative flex items-center group">
              <div class="absolute left-3.5 z-20 pointer-events-none text-gray-400 group-focus-within:text-[#16a34a] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
              </div>
              
              <input 
                v-model="identifier" 
                @input="handleInput"
                @keydown="handleKeydown"
                type="text" 
                :maxlength="maxLength"
                placeholder="e.g. 231-01506 or admin" 
                required 
                class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] focus:bg-white z-10 relative shadow-sm font-medium"
              >
              
              <div v-if="emailSuggestion" class="absolute left-0 pl-11 py-3 text-gray-400 pointer-events-none z-20 flex whitespace-pre font-medium">
                <span class="opacity-0">{{ identifier }}</span><span class="text-green-600">{{ emailSuggestion }}</span>
              </div>
            </div>
            <p v-if="emailSuggestion" class="text-[10px] text-gray-500 font-medium italic mt-1 ml-1">Press <kbd class="bg-gray-200 px-1 rounded">Tab</kbd> to autocomplete</p>
          </div>
          
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Password</label>
            <div class="relative flex items-center">
              <div class="absolute left-3.5 z-20 pointer-events-none text-gray-400 group-focus-within:text-[#16a34a] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
              </div>
              <input 
                v-model="password" 
                :type="showPassword ? 'text' : 'password'" 
                placeholder="••••••••" 
                required 
                maxlength="8"
                class="w-full pl-11 pr-12 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] focus:bg-white z-10 relative shadow-sm font-medium"
              >
              <button type="button" @click="showPassword = !showPassword" class="absolute right-3.5 z-20 text-gray-400 hover:text-gray-700 p-1">
                <svg v-if="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
              </button>
            </div>
          </div>

          <div class="flex items-center justify-between pt-1">
            <div class="flex items-center">
              <input v-model="remember" type="checkbox" id="remember" class="w-4 h-4 text-[#16a34a] bg-gray-50 border-gray-300 rounded focus:ring-[#16a34a] cursor-pointer transition-colors">
              <label for="remember" class="ml-2.5 text-sm text-gray-600 font-bold cursor-pointer hover:text-gray-800 transition-colors">Remember me</label>
            </div>
            <a href="#" class="text-sm font-bold text-[#16a34a] hover:text-green-800 hover:underline transition-all">
              Forgot password?
            </a>
          </div>

          


          

          <button type="submit" :disabled="loading" class="w-full bg-gradient-to-r from-[#16a34a] to-green-700 text-white font-bold py-3 px-4 rounded-xl hover:from-green-700 hover:to-green-800 transition-all shadow-lg text-lg mt-4">
            {{ loading ? 'Authenticating...' : 'Sign In to Archive' }}
          </button>
        </form>
        
      </div>
      <div class="mt-6 text-center">
        <p class="text-green-200/60 text-xs font-medium flex items-center justify-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          Cookies must be enabled in your browser.
        </p>
      </div>
    </div>
  </div>
</template>





























    
      