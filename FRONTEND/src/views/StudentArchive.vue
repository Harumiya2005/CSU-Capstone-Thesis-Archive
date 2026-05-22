<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../axios';
import { useToast } from '../composables/useToast';
import { useConfirm } from '../composables/useConfirm';

const router = useRouter();
const { triggerToast } = useToast();
const { triggerConfirm } = useConfirm();

const theses = ref([]);
const loading = ref(true);
const userName = ref(localStorage.getItem('name') || 'Student');

// --- FILTER STATES ---
const searchQuery = ref('');

// Course Dropdown State
const selectedCourse = ref('All Courses');
const showDropdown = ref(false); 
const courseSearchQuery = ref('');

// NEW: Year Dropdown State
const thesisYearFilter = ref('All Years');
const showYearDropdown = ref(false);

const csuCourses = [
  "AB-SOCIO", "BEED", "BSABE", "BSA", "BSA-AGECON", "BSA-AGRON", "BSA-ANSCI",
  "BSA-CROPPROT", "BSA-HORTI", "BSA-SOILSCI", "BSAF", "BSAM", "BSArch",
  "BSBIO", "BSBIO BIOCON", "BSBIO MEDBIO", "BSBIO MICRO", "BSBIO PLANTBIO",
  "BSCHEM", "BSCE", "BSCS", "BSEcE", "BSES", "BSFT", "BSF", "BSGE", "BSGeol",
  "BSIS", "BSIT", "BS Marine Bio", "BSMATH", "BSME", "BSEM", "BS-PHYS",
  "BS PSYCH", "BSSW", "BSED ENG", "BSED FIL", "BSED-MATH", "BSED SCI"
];

const logout = async () => {
  const isConfirmed = await triggerConfirm({
    title: 'Confirm Logout',
    message: 'Are you sure you want to log out of the Digital Archive?',
    confirmText: 'Yes, Log Out'
  });

  if (!isConfirmed) return;

  localStorage.clear();
  router.push('/login');
  triggerToast('Successfully logged out.', 'success');
};

const fetchTheses = async () => {
  try {
    const response = await api.get('/theses');
    theses.value = response.data;
  } catch (error) {
    console.error("Error fetching data:", error);
    triggerToast("Failed to load the archive.", "error");
  } finally {
    loading.value = false;
  }
};

// --- DYNAMIC COMPUTED PROPERTIES ---

// Extracts unique years from the database for the dropdown
const availableYears = computed(() => {
  const years = [...new Set(theses.value.map(t => t.publication_year))];
  return ['All Years', ...years.sort((a, b) => b - a)];
});

// --- DROPDOWN LOGIC ---
const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value;
  showYearDropdown.value = false; // Close the other dropdown
  if (!showDropdown.value) courseSearchQuery.value = '';
};

const toggleYearDropdown = () => {
  showYearDropdown.value = !showYearDropdown.value;
  showDropdown.value = false; // Close the other dropdown
};

const selectCourse = (course) => {
  selectedCourse.value = course;
  showDropdown.value = false;
  courseSearchQuery.value = '';
};

const selectYear = (year) => {
  thesisYearFilter.value = year;
  showYearDropdown.value = false;
};

const closeDropdown = (e) => {
  if (!e.target.closest('.course-dropdown-container')) {
    showDropdown.value = false;
    courseSearchQuery.value = '';
  }
  if (!e.target.closest('.year-dropdown-container')) {
    showYearDropdown.value = false;
  }
};

const filteredCoursesList = computed(() => {
  if (!courseSearchQuery.value) return csuCourses;
  return csuCourses.filter(course => 
    course.toLowerCase().includes(courseSearchQuery.value.toLowerCase())
  );
});

// NEW: Filtering logic now includes the Year
const filteredTheses = computed(() => {
  return theses.value.filter(thesis => {
    const matchesSearch = thesis.title.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                          thesis.abstract.toLowerCase().includes(searchQuery.value.toLowerCase());
    
    const matchesCourse = selectedCourse.value === 'All Courses' || thesis.course_category === selectedCourse.value;
    const matchesYear = thesisYearFilter.value === 'All Years' || thesis.publication_year.toString() === thesisYearFilter.value.toString();
    
    return matchesSearch && matchesCourse && matchesYear;
  });
});

onMounted(() => {
  fetchTheses();
  document.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown);
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 pb-12 font-sans selection:bg-[#16a34a] selection:text-white">
    
    <nav class="bg-[#16a34a]/95 backdrop-blur-md text-white shadow-lg sticky top-0 z-50 border-b border-white/20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center space-x-3">
            <div class="bg-white/10 p-2 rounded-lg backdrop-blur-sm border border-white/20 shadow-inner">
              <svg class="w-6 h-6 text-yellow-300" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72l5 2.73 5-2.73v3.72z"/>
              </svg>
            </div>
            <div>
              <span class="font-extrabold text-lg tracking-wide block leading-tight drop-shadow-sm">CARAGA STATE UNIVERSITY</span>
              <span class="text-xs text-yellow-300 block leading-tight tracking-wider font-semibold">Competence, Service and Uprightness</span>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <div class="hidden md:flex items-center gap-2 bg-white/10 px-4 py-1.5 rounded-full border border-white/20 shadow-inner">
              <svg class="w-4 h-4 text-green-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
              <span class="text-sm font-medium text-white">{{ userName }}</span>
            </div>
            <button @click="logout" class="bg-white/10 hover:bg-red-500/90 border border-white/20 hover:border-red-500 px-4 py-1.5 rounded-lg text-sm font-bold transition-all duration-300 shadow-sm flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
              Log Out
            </button>
          </div>
        </div>
      </div>
    </nav>

    <div class="relative pb-24 pt-16 overflow-hidden shadow-inner bg-gradient-to-br from-[#16a34a] via-green-700 to-green-900">
      <div class="absolute inset-0">
        <img src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=2070&auto=format&fit=crop" alt="Campus" class="w-full h-full object-cover opacity-10 mix-blend-overlay">
      </div>
      <div class="absolute top-0 right-0 -mr-20 -mt-20 w-64 h-64 rounded-full bg-white opacity-5 blur-3xl"></div>
      <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-full bg-yellow-400 opacity-10 blur-3xl"></div>
      
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center z-10 animate-fade-in-up">
        <h2 class="text-xl md:text-2xl font-light text-green-100 mb-2 italic drop-shadow">Creating Futures. Empowering Communities.</h2>
        <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-4 tracking-tight drop-shadow-md">
          Welcome, <br class="md:hidden" />
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-yellow-500">Golden Paddlers!</span>
        </h1>
        <div class="inline-flex items-center gap-2 bg-yellow-400/20 border-2 border-yellow-400 text-yellow-300 px-6 py-1.5 rounded-full font-bold tracking-widest mt-2 mb-6 backdrop-blur-sm shadow-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
          AY 2025-2026
        </div>
        <p class="text-green-50 text-lg max-w-2xl mx-auto mt-2 font-medium tracking-wide">
          CSU Capstone and Thesis Digital Archive
        </p>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 relative z-40 animate-fade-in-up" style="animation-delay: 0.1s;">
      <div class="bg-white/95 backdrop-blur-md rounded-xl border-t-4 border-[#16a34a] shadow-xl p-4 md:p-6 flex flex-col md:flex-row gap-4 items-center justify-between">
        
        <div class="w-full md:w-1/2 relative group">
          <svg class="absolute left-4 top-3.5 w-5 h-5 text-gray-400 group-focus-within:text-[#16a34a] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Search by title, keyword, or abstract..." 
            class="w-full pl-12 pr-4 py-3 bg-gray-50/50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] focus:bg-white transition-all shadow-sm text-gray-700 font-medium"
          >
        </div>

        <div class="flex flex-col sm:flex-row gap-4 w-full md:w-1/2">
          
          <div class="w-full sm:w-2/3 relative course-dropdown-container">
            <button 
              @click="toggleDropdown" 
              class="w-full py-3 px-4 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] bg-gray-50/50 hover:bg-white transition-all text-gray-700 flex justify-between items-center shadow-sm font-medium"
            >
              <span class="truncate pr-2 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#16a34a]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                {{ selectedCourse }}
              </span>
              <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>

            <div v-if="showDropdown" class="absolute z-[9999] w-full mt-2 bg-white border border-gray-100 rounded-xl shadow-2xl flex flex-col max-h-[300px] overflow-hidden transform opacity-100 scale-100 transition-all origin-top">
              <div class="p-2 border-b border-gray-100 bg-gray-50/80 shrink-0 backdrop-blur-sm">
                <div class="relative">
                  <svg class="absolute left-2.5 top-2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                  <input v-model="courseSearchQuery" type="text" placeholder="Find a course..." class="w-full pl-8 pr-3 py-1.5 border border-gray-200 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] font-medium" @click.stop>
                </div>
              </div>
              <div class="overflow-y-auto grow p-1">
                <div @click="selectCourse('All Courses')" class="px-4 py-2.5 rounded-lg hover:bg-[#16a34a] hover:text-white cursor-pointer transition-colors font-bold mb-1" :class="{'bg-[#16a34a] text-white': selectedCourse === 'All Courses'}">All Courses</div>
                <div v-for="course in filteredCoursesList" :key="course" @click="selectCourse(course)" class="px-4 py-2 rounded-lg hover:bg-green-50 hover:text-[#16a34a] cursor-pointer transition-colors text-sm font-medium" :class="{'bg-[#16a34a] text-white hover:bg-[#16a34a]': selectedCourse === course}">{{ course }}</div>
                <div v-if="filteredCoursesList.length === 0" class="px-4 py-3 text-sm text-gray-500 text-center italic">No courses match.</div>
              </div>
            </div>
          </div>

          <div class="w-full sm:w-1/3 relative year-dropdown-container">
            <button 
              @click="toggleYearDropdown" 
              class="w-full py-3 px-4 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] bg-gray-50/50 hover:bg-white transition-all text-gray-700 flex justify-between items-center shadow-sm font-medium"
            >
              <span class="truncate pr-2 flex items-center gap-2">
                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                {{ thesisYearFilter }}
              </span>
              <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>

            <div v-if="showYearDropdown" class="absolute z-[9999] w-full mt-2 bg-white border border-gray-100 rounded-xl shadow-2xl flex flex-col max-h-[300px] overflow-hidden transform opacity-100 scale-100 transition-all origin-top">
              <div class="overflow-y-auto grow p-1">
                <div v-for="year in availableYears" :key="year" @click="selectYear(year)" class="px-4 py-2.5 rounded-lg hover:bg-yellow-50 hover:text-yellow-700 cursor-pointer transition-colors text-sm font-bold" :class="{'bg-yellow-100 text-yellow-800 hover:bg-yellow-100': thesisYearFilter === year}">
                  {{ year }}
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12 relative z-10 pb-10">
      
      <div v-if="loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-4 border-[#16a34a]"></div>
      </div>

      <div v-else-if="filteredTheses.length === 0" class="text-center py-24 bg-white rounded-2xl shadow-sm border border-gray-100 animate-fade-in-up">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-50 mb-4">
          <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <h3 class="text-xl font-bold text-gray-900">No capstones found</h3>
        <p class="text-gray-500 mt-2 font-medium">Try adjusting your search keywords, course, or year filter.</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
        <div v-for="(thesis, index) in filteredTheses" :key="thesis.id" class="bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1.5 flex flex-col border border-gray-100 overflow-hidden group animate-fade-in-up" :style="`animation-delay: ${index * 0.05}s`">
          
          <div class="px-6 pt-6 pb-3 flex justify-between items-start border-b border-gray-50 bg-gradient-to-b from-gray-50/50 to-white">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide bg-green-50 text-[#16a34a] border border-green-200 shadow-sm">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
              {{ thesis.course_category }}
            </span>
            <span class="inline-flex items-center gap-1 text-xs font-bold text-gray-500 bg-white border border-gray-200 px-2.5 py-1 rounded-md shadow-sm">
              <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
              {{ thesis.publication_year }}
            </span>
          </div>

          <div class="px-6 py-5 flex-grow relative">
            <h3 class="text-xl font-extrabold text-gray-900 mb-3 leading-snug line-clamp-2 group-hover:text-[#16a34a] transition-colors" :title="thesis.title">
              {{ thesis.title }}
            </h3>
            <p class="text-gray-600 text-sm mb-6 line-clamp-4 leading-relaxed">
              {{ thesis.abstract }}
            </p>
            
            <div class="bg-gray-50 rounded-xl p-4 mt-auto border border-gray-100 relative overflow-hidden">
              <div class="absolute left-0 top-0 bottom-0 w-1 bg-[#16a34a]"></div>
              <div class="flex items-center gap-1.5 mb-1.5">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Research Authors</span>
              </div>
              <div class="text-sm text-gray-800 font-medium truncate pl-5">
                <span v-for="(author, index) in thesis.authors" :key="author.id">
                  {{ author.first_name }} {{ author.last_name }}<span v-if="index < thesis.authors.length - 1" class="text-gray-400">, </span>
                </span>
              </div>
            </div>
          </div>

          <div class="px-6 py-5 bg-gray-50 border-t border-gray-100">
            <a :href="`http://127.0.0.1:8000/storage/${thesis.file_path}`" 
               target="_blank" 
               rel="noopener noreferrer" 
               class="w-full flex items-center justify-center bg-gradient-to-r from-[#16a34a] to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold py-2.5 px-4 rounded-xl transition-all duration-300 shadow-md hover:shadow-lg transform group-hover:scale-[1.02] gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
              Read Full Document
            </a>
          </div>

        </div>
      </div>
      
    </div>
  </div>
</template>

<style scoped>
.animate-fade-in-up {
  opacity: 0;
  animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>