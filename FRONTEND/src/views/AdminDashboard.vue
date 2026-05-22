<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../axios';
import { useToast } from '../composables/useToast';
import { useConfirm } from '../composables/useConfirm';

const router = useRouter();
const { triggerToast } = useToast();
const { triggerConfirm } = useConfirm();

// --- STATE ---
const userName = ref(localStorage.getItem('name') || 'Administrator');
const activeTab = ref('capstones');
const theses = ref([]);
const users = ref([]);
const loading = ref(true);

const csuCourses = [
  "AB-SOCIO", "BEED", "BSABE", "BSA", "BSA-AGECON", "BSA-AGRON", "BSA-ANSCI",
  "BSA-CROPPROT", "BSA-HORTI", "BSA-SOILSCI", "BSAF", "BSAM", "BSArch",
  "BSBIO", "BSBIO BIOCON", "BSBIO MEDBIO", "BSBIO MICRO", "BSBIO PLANTBIO",
  "BSCHEM", "BSCE", "BSCS", "BSEcE", "BSES", "BSFT", "BSF", "BSGE", "BSGeol",
  "BSIS", "BSIT", "BS Marine Bio", "BSMATH", "BSME", "BSEM", "BS-PHYS",
  "BS PSYCH", "BSSW", "BSED ENG", "BSED FIL", "BSED-MATH", "BSED SCI"
];

// --- SEARCH & FILTER STATES ---
const searchUsers = ref('');
const searchTheses = ref('');
const selectedUsers = ref([]);
const selectedTheses = ref([]);

// NEW: Table Filters
const thesisCourseFilter = ref('All Courses');
const thesisYearFilter = ref('All Years');
const userRoleFilter = ref('All Roles');
const userSortOrder = ref('newest'); // 'newest' or 'oldest'

const showThesisModal = ref(false);
const showUserModal = ref(false);
const isEditingThesis = ref(false);
const isEditingUser = ref(false);
const fileInput = ref(null);
const selectedFile = ref(null);
const showCourseDropdown = ref(false);
const courseSearchQuery = ref('');
const showUserPassword = ref(false);

const thesisForm = ref({
  id: null, title: '', abstract: '', publication_year: '', course_category: 'BSIS', authors: [{ first_name: '', last_name: '' }]
});
const userForm = ref({
  id: null, identifier: '', name: '', role: 'student', course: 'BSIS', year_level: '3', password: ''
});

const isInvalidID = ref(false);
const showEmailSuggestion = ref(false);

const logout = async () => {
  const isConfirmed = await triggerConfirm({ title: 'Confirm Logout', message: 'Are you sure you want to log out of the Admin Dashboard?', confirmText: 'Yes, Log Out' });
  if (!isConfirmed) return;
  localStorage.clear(); 
  router.push('/login');
  triggerToast('Successfully logged out.', 'success');
};

const fetchData = async () => {
  loading.value = true;
  try {
    const [thesesRes, usersRes] = await Promise.all([api.get('/theses'), api.get('/users')]);
    theses.value = thesesRes.data; users.value = usersRes.data;
  } catch (error) { triggerToast('Failed to load database.', 'error'); } finally { loading.value = false; }
};

// --- DYNAMIC COMPUTED PROPERTIES ---

// Extracts unique years from the database to populate the Year dropdown
const availableYears = computed(() => {
  const years = [...new Set(theses.value.map(t => t.publication_year))];
  return ['All Years', ...years.sort((a, b) => b - a)];
});

// Filters Capstones
const filteredTheses = computed(() => {
  return theses.value.filter(t => {
    const matchesSearch = !searchTheses.value || t.title.toLowerCase().includes(searchTheses.value.toLowerCase()) || t.course_category.toLowerCase().includes(searchTheses.value.toLowerCase());
    const matchesCourse = thesisCourseFilter.value === 'All Courses' || t.course_category === thesisCourseFilter.value;
    const matchesYear = thesisYearFilter.value === 'All Years' || t.publication_year.toString() === thesisYearFilter.value.toString();
    return matchesSearch && matchesCourse && matchesYear;
  });
});

// Filters and Sorts Users
const filteredUsers = computed(() => {
  let result = users.value.filter(u => {
    const matchesSearch = !searchUsers.value || u.name.toLowerCase().includes(searchUsers.value.toLowerCase()) || u.identifier.toLowerCase().includes(searchUsers.value.toLowerCase());
    const matchesRole = userRoleFilter.value === 'All Roles' || u.role === userRoleFilter.value;
    return matchesSearch && matchesRole;
  });

  // Sort Logic (Assuming higher ID = newer user)
  result = result.sort((a, b) => {
    return userSortOrder.value === 'newest' ? b.id - a.id : a.id - b.id;
  });

  return result;
});

const selectAllUsers = computed({
  get: () => filteredUsers.value.length > 0 && selectedUsers.value.length === filteredUsers.value.length,
  set: (val) => { selectedUsers.value = val ? filteredUsers.value.map(u => u.id) : []; }
});

const selectAllTheses = computed({
  get: () => filteredTheses.value.length > 0 && selectedTheses.value.length === filteredTheses.value.length,
  set: (val) => { selectedTheses.value = val ? filteredTheses.value.map(t => t.id) : []; }
});

const toggleDropdown = () => { showCourseDropdown.value = !showCourseDropdown.value; if (!showCourseDropdown.value) courseSearchQuery.value = ''; };
const selectCourse = (course, isUserForm = true) => { if (isUserForm) userForm.value.course = course; else thesisForm.value.course_category = course; showCourseDropdown.value = false; courseSearchQuery.value = ''; };
const closeDropdown = (e) => { if (!e.target.closest('.course-dropdown-container')) { showCourseDropdown.value = false; courseSearchQuery.value = ''; } };
const filteredModalCourses = computed(() => { if (!courseSearchQuery.value) return csuCourses; return csuCourses.filter(c => c.toLowerCase().includes(courseSearchQuery.value.toLowerCase())); });

const handleRoleChange = () => { userForm.value.identifier = ''; isInvalidID.value = false; showEmailSuggestion.value = false; };
const formatName = () => { userForm.value.name = userForm.value.name.replace(/[0-9]/g, ''); };
const formatIdentifier = () => {
  let val = userForm.value.identifier;
  if (!val) { isInvalidID.value = false; showEmailSuggestion.value = false; return; }
  if (userForm.value.role === 'student') {
    let digits = val.replace(/\D/g, '').substring(0, 8); 
    userForm.value.identifier = digits.length > 3 ? digits.substring(0, 3) + '-' + digits.substring(3) : digits;
    isInvalidID.value = userForm.value.identifier.length < 9;
    showEmailSuggestion.value = false;
  } else if (userForm.value.role === 'admin') {
    userForm.value.identifier = val.replace(/\s/g, '');
    isInvalidID.value = false; showEmailSuggestion.value = !val.includes('@');
  }
};
const handleTab = (e) => { if (userForm.value.role === 'admin' && showEmailSuggestion.value) { e.preventDefault(); userForm.value.identifier += '@carsu.edu.ph'; showEmailSuggestion.value = false; } };

const openUserModal = (user = null) => {
  showUserPassword.value = false;
  if (user) { isEditingUser.value = true; userForm.value = { ...user, password: '' }; formatIdentifier(); } 
  else { isEditingUser.value = false; userForm.value = { id: null, identifier: '', name: '', role: 'student', course: 'BSIS', year_level: '3', password: '' }; isInvalidID.value = false; showEmailSuggestion.value = false; }
  showUserModal.value = true;
};

const saveUser = async () => {
  if (userForm.value.role === 'student' && isInvalidID.value) return triggerToast("Fix Student ID format.", "error");
  const isDuplicate = users.value.some(u => u.identifier.toLowerCase() === userForm.value.identifier.toLowerCase() && u.id !== userForm.value.id);
  if (isDuplicate) return triggerToast(`Error: The ID/Email "${userForm.value.identifier}" is already registered!`, 'error');
  if (isEditingUser.value) { const isConfirmed = await triggerConfirm({ title: 'Update User Record', message: `Are you sure you want to update ${userForm.value.name}?`, confirmText: 'Yes, Update User' }); if (!isConfirmed) return; }
  try {
    const payload = { ...userForm.value }; if (isEditingUser.value && !payload.password) delete payload.password; 
    if (isEditingUser.value) { await api.put(`/users/${userForm.value.id}`, payload); triggerToast(`${userForm.value.name} User Updated`, 'success'); } 
    else { await api.post('/users', payload); triggerToast(`${userForm.value.name} User Created`, 'success'); }
    showUserModal.value = false; fetchData();
  } catch (error) { triggerToast(error.response?.data?.message || 'Failed to save user.', 'error'); }
};

const deleteUser = async (user) => {
  const isConfirmed = await triggerConfirm({ title: 'Delete System User', message: `Are you sure you want to permanently delete ${user.name}?`, confirmText: 'Yes, Delete' });
  if (!isConfirmed) return;
  try { await api.delete(`/users/${user.id}`); triggerToast(`${user.name} has been deleted.`, 'success'); selectedUsers.value = selectedUsers.value.filter(id => id !== user.id); fetchData(); } catch (error) { triggerToast('Deletion failed.', 'error'); }
};

const bulkDeleteUsers = async () => {
  const count = selectedUsers.value.length;
  const isConfirmed = await triggerConfirm({ title: 'Bulk Delete Users', message: `Permanently delete ${count} selected users?`, confirmText: `Yes, Delete ${count} Users` });
  if (!isConfirmed) return;
  try { await Promise.all(selectedUsers.value.map(id => api.delete(`/users/${id}`))); triggerToast(`${count} Users have been deleted.`, 'success'); selectedUsers.value = []; fetchData(); } catch (error) { triggerToast('Bulk deletion failed.', 'error'); }
};

const addAuthorField = () => thesisForm.value.authors.push({ first_name: '', last_name: '' });
const removeAuthorField = (index) => thesisForm.value.authors.splice(index, 1);
const handleFileUpload = (event) => selectedFile.value = event.target.files[0];

const openThesisModal = (thesis = null) => {
  if (thesis) { isEditingThesis.value = true; thesisForm.value = { ...thesis, authors: thesis.authors.map(a => ({ ...a })) }; } 
  else { isEditingThesis.value = false; thesisForm.value = { id: null, title: '', abstract: '', publication_year: new Date().getFullYear(), course_category: 'BSIS', authors: [{ first_name: '', last_name: '' }] }; }
  selectedFile.value = null; if(fileInput.value) fileInput.value.value = ''; showThesisModal.value = true;
};

const saveThesis = async () => {
  if (isEditingThesis.value) { const isConfirmed = await triggerConfirm({ title: 'Update Capstone', message: `Update "${thesisForm.value.title}"?`, confirmText: 'Yes, Update' }); if (!isConfirmed) return; }
  const formData = new FormData(); formData.append('title', thesisForm.value.title); formData.append('abstract', thesisForm.value.abstract); formData.append('publication_year', thesisForm.value.publication_year); formData.append('course_category', thesisForm.value.course_category); formData.append('authors', JSON.stringify(thesisForm.value.authors));
  try {
    if (isEditingThesis.value) { formData.append('_method', 'PUT'); if (selectedFile.value) formData.append('file', selectedFile.value); await api.post(`/theses/${thesisForm.value.id}`, formData, { headers: { 'Content-Type': 'multipart/form-data' }}); triggerToast('Capstone Archive Updated', 'success'); } 
    else { if (!selectedFile.value) return triggerToast("Please select a PDF file!", "error"); formData.append('file', selectedFile.value); await api.post('/theses', formData, { headers: { 'Content-Type': 'multipart/form-data' }}); triggerToast('Capstone Added to Archive', 'success'); }
    showThesisModal.value = false; fetchData();
  } catch (error) { triggerToast('Error saving thesis.', 'error'); }
};

const deleteThesis = async (thesis) => {
  const isConfirmed = await triggerConfirm({ title: 'Delete Capstone', message: `Permanently delete "${thesis.title}"?`, confirmText: 'Yes, Delete' });
  if (!isConfirmed) return;
  try { await api.delete(`/theses/${thesis.id}`); triggerToast(`Capstone has been deleted.`, 'success'); selectedTheses.value = selectedTheses.value.filter(id => id !== thesis.id); fetchData(); } catch (error) { triggerToast('Deletion failed.', 'error'); }
};

const bulkDeleteTheses = async () => {
  const count = selectedTheses.value.length;
  const isConfirmed = await triggerConfirm({ title: 'Bulk Delete Capstones', message: `Permanently delete ${count} selected capstones?`, confirmText: `Yes, Delete ${count} Capstones` });
  if (!isConfirmed) return;
  try { await Promise.all(selectedTheses.value.map(id => api.delete(`/theses/${id}`))); triggerToast(`${count} Capstones have been deleted.`, 'success'); selectedTheses.value = []; fetchData(); } catch (error) { triggerToast('Bulk deletion failed.', 'error'); }
};

onMounted(() => { fetchData(); document.addEventListener('click', closeDropdown); });
onUnmounted(() => document.removeEventListener('click', closeDropdown));
</script>

<template>
  <div class="min-h-screen bg-gray-50 pb-12 font-sans selection:bg-[#16a34a] selection:text-white">
    
    <nav class="bg-[#16a34a]/95 backdrop-blur-md text-white shadow-lg sticky top-0 z-40 border-b border-white/20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center space-x-3">
            <div class="bg-white/10 p-2 rounded-lg backdrop-blur-sm border border-white/20 shadow-inner">
              <svg class="w-6 h-6 text-yellow-300" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72l5 2.73 5-2.73v3.72z"/></svg>
            </div>
            <div>
              <span class="font-extrabold text-lg tracking-wide block leading-tight text-white drop-shadow-sm">CARAGA STATE UNIVERSITY</span>
              <span class="text-xs text-yellow-300 block leading-tight tracking-wider font-semibold">CCIS System Administrator</span>
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

    <div class="relative pb-24 pt-12 overflow-hidden shadow-inner bg-gradient-to-br from-[#16a34a] via-green-700 to-green-900">
      <div class="absolute inset-0">
        <img src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=2070&auto=format&fit=crop" alt="Campus" class="w-full h-full object-cover opacity-10 mix-blend-overlay">
      </div>
      <div class="absolute top-0 right-0 -mr-20 -mt-20 w-64 h-64 rounded-full bg-white opacity-5 blur-3xl"></div>
      <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-full bg-yellow-400 opacity-10 blur-3xl"></div>
      
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 z-10 text-center">
        <h1 class="text-3xl md:text-5xl font-extrabold text-white tracking-tight mb-3 drop-shadow-md">Admin Dashboard</h1>
        <p class="text-green-100 text-lg font-medium tracking-wide">Manage Capstone Archives and System Users</p>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-10 relative z-10">
      <div class="flex bg-white shadow-xl rounded-t-xl overflow-hidden border-b border-gray-100">
        <button @click="activeTab = 'capstones'" :class="activeTab === 'capstones' ? 'bg-green-50/50 text-[#16a34a] border-b-4 border-[#16a34a]' : 'text-gray-500 hover:bg-gray-50 border-b-4 border-transparent hover:text-gray-700'" class="flex-1 py-4 font-bold transition-all duration-300 flex justify-center items-center gap-3">
          <svg class="w-5 h-5" :class="activeTab === 'capstones' ? 'text-[#16a34a]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
          Manage Capstones
        </button>
        <button @click="activeTab = 'users'" :class="activeTab === 'users' ? 'bg-green-50/50 text-[#16a34a] border-b-4 border-[#16a34a]' : 'text-gray-500 hover:bg-gray-50 border-b-4 border-transparent hover:text-gray-700'" class="flex-1 py-4 font-bold transition-all duration-300 flex justify-center items-center gap-3">
          <svg class="w-5 h-5" :class="activeTab === 'users' ? 'text-[#16a34a]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
          Manage Users
        </button>
      </div>
      
      <div class="bg-white p-6 md:p-8 rounded-b-xl shadow-xl border-x border-b border-gray-100 min-h-[500px]">
        
        <div v-if="activeTab === 'capstones'" class="animate-fade-in">
          
          <div class="flex flex-col lg:flex-row gap-3 items-start lg:items-center mb-6 w-full">
            
            <div class="relative group w-full lg:flex-1">
              <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400 group-focus-within:text-[#16a34a] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
              <input v-model="searchTheses" type="text" placeholder="Search capstones..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] focus:bg-white transition-all shadow-sm text-sm font-medium">
            </div>
            
            <select v-model="thesisCourseFilter" class="w-full lg:w-48 px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] shadow-sm text-sm font-medium text-gray-700 cursor-pointer">
              <option value="All Courses">All Courses</option>
              <option v-for="course in csuCourses" :key="course" :value="course">{{ course }}</option>
            </select>

            <select v-model="thesisYearFilter" class="w-full lg:w-36 px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] shadow-sm text-sm font-medium text-gray-700 cursor-pointer">
              <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
            </select>

            <button v-if="selectedTheses.length > 0" @click="bulkDeleteTheses" class="w-full lg:w-auto bg-red-500 hover:bg-red-600 text-white px-4 py-2.5 rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all font-bold text-sm flex items-center justify-center gap-2 whitespace-nowrap">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
              Delete ({{ selectedTheses.length }})
            </button>
            <button @click="openThesisModal()" class="w-full lg:w-auto bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 text-yellow-950 px-6 py-2.5 rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all font-bold flex items-center justify-center gap-2 text-sm whitespace-nowrap">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
              Upload Capstone
            </button>
            
          </div>

          <div v-if="loading" class="flex justify-center py-16"><div class="animate-spin rounded-full h-12 w-12 border-b-4 border-[#16a34a]"></div></div>
          
          <div v-else class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50/80 text-gray-600 border-b border-gray-200 text-sm">
                  <th class="p-4 w-12 text-center"><input type="checkbox" v-model="selectAllTheses" class="w-4 h-4 cursor-pointer text-[#16a34a] rounded border-gray-300 focus:ring-[#16a34a]"></th>
                  <th class="p-4 font-bold uppercase tracking-wider">Title</th>
                  <th class="p-4 font-bold uppercase tracking-wider">Course</th>
                  
                  <th class="p-4 font-bold uppercase tracking-wider">Year</th>
                  
                  <th class="p-4 font-bold uppercase tracking-wider text-center">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 bg-white">
                <tr v-for="thesis in filteredTheses" :key="thesis.id" class="hover:bg-green-50/50 transition-colors group" :class="{'bg-green-50/80': selectedTheses.includes(thesis.id)}">
                  <td class="p-4 text-center"><input type="checkbox" :value="thesis.id" v-model="selectedTheses" class="w-4 h-4 cursor-pointer text-[#16a34a] rounded border-gray-300 focus:ring-[#16a34a]"></td>
                  <td class="p-4 font-medium text-gray-800 max-w-md lg:max-w-lg truncate" :title="thesis.title">{{ thesis.title }}</td>
                  <td class="p-4"><span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-xs font-bold border border-gray-200 shadow-sm whitespace-nowrap">{{ thesis.course_category }}</span></td>
                  
                  <td class="p-4 text-gray-600 font-medium">{{ thesis.publication_year }}</td>
                  
                  <td class="p-4 flex justify-center gap-2 opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity duration-200">
                    <button @click="openThesisModal(thesis)" class="bg-white text-blue-600 border border-blue-200 px-3 py-1.5 rounded-lg text-sm hover:bg-blue-50 transition-colors font-medium shadow-sm flex items-center gap-1.5">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                      Edit
                    </button>
                    <button @click="deleteThesis(thesis)" class="bg-white text-red-600 border border-red-200 px-3 py-1.5 rounded-lg text-sm hover:bg-red-50 transition-colors font-medium shadow-sm flex items-center gap-1.5">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                  </td>
                </tr>
                <tr v-if="filteredTheses.length === 0"><td colspan="5" class="p-12 text-center text-gray-500 bg-gray-50/50"><div class="flex flex-col items-center gap-2"><svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg><span class="font-medium">No capstones found.</span></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-if="activeTab === 'users'" class="animate-fade-in">
          
          <div class="flex flex-col lg:flex-row gap-3 items-start lg:items-center mb-6 w-full">
            
            <div class="relative group w-full lg:flex-1">
              <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400 group-focus-within:text-[#16a34a] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
              <input v-model="searchUsers" type="text" placeholder="Search users by name or ID..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] focus:bg-white transition-all shadow-sm text-sm font-medium">
            </div>

            <select v-model="userRoleFilter" class="w-full lg:w-40 px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] shadow-sm text-sm font-medium text-gray-700 cursor-pointer">
              <option value="All Roles">All Roles</option>
              <option value="admin">Admins Only</option>
              <option value="student">Students Only</option>
            </select>

            <select v-model="userSortOrder" class="w-full lg:w-40 px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] shadow-sm text-sm font-medium text-gray-700 cursor-pointer">
              <option value="newest">Newest First</option>
              <option value="oldest">Oldest First</option>
            </select>

            <button v-if="selectedUsers.length > 0" @click="bulkDeleteUsers" class="w-full lg:w-auto bg-red-500 hover:bg-red-600 text-white px-4 py-2.5 rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all font-bold text-sm flex items-center justify-center gap-2 whitespace-nowrap">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
              Delete ({{ selectedUsers.length }})
            </button>
            <button @click="openUserModal()" class="w-full lg:w-auto bg-gradient-to-r from-[#16a34a] to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-2.5 rounded-lg shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all font-bold flex items-center justify-center gap-2 text-sm whitespace-nowrap">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
              Create User
            </button>
            
          </div>

          <div v-if="loading" class="flex justify-center py-16"><div class="animate-spin rounded-full h-12 w-12 border-b-4 border-[#16a34a]"></div></div>
          
          <div v-else class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50/80 text-gray-600 border-b border-gray-200 text-sm">
                  <th class="p-4 w-12 text-center"><input type="checkbox" v-model="selectAllUsers" class="w-4 h-4 cursor-pointer text-[#16a34a] rounded border-gray-300 focus:ring-[#16a34a]"></th>
                  <th class="p-4 font-bold uppercase tracking-wider">ID / Email</th>
                  <th class="p-4 font-bold uppercase tracking-wider">Name</th>
                  <th class="p-4 font-bold uppercase tracking-wider">Role</th>
                  <th class="p-4 font-bold uppercase tracking-wider">Course</th>
                  <th class="p-4 font-bold uppercase tracking-wider text-center">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 bg-white">
                <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-green-50/50 transition-colors group" :class="{'bg-green-50/80': selectedUsers.includes(user.id)}">
                  <td class="p-4 text-center"><input type="checkbox" :value="user.id" v-model="selectedUsers" class="w-4 h-4 cursor-pointer text-[#16a34a] rounded border-gray-300 focus:ring-[#16a34a]"></td>
                  <td class="p-4 font-medium text-gray-900">{{ user.identifier }}</td>
                  <td class="p-4 text-gray-600">{{ user.name }}</td>
                  <td class="p-4">
                    <span :class="user.role === 'admin' ? 'bg-yellow-100 text-yellow-800 border-yellow-200' : 'bg-green-100 text-[#16a34a] border-green-200'" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border shadow-sm">
                      {{ user.role }}
                    </span>
                  </td>
                  <td class="p-4 text-gray-500 font-medium">{{ user.course || '—' }}</td>
                  <td class="p-4 flex justify-center gap-2 opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity duration-200">
                    <button @click="openUserModal(user)" class="bg-white text-blue-600 border border-blue-200 px-3 py-1.5 rounded-lg text-sm hover:bg-blue-50 transition-colors font-medium shadow-sm flex items-center gap-1.5">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                      Edit
                    </button>
                    <button @click="deleteUser(user)" class="bg-white text-red-600 border border-red-200 px-3 py-1.5 rounded-lg text-sm hover:bg-red-50 transition-colors font-medium shadow-sm flex items-center gap-1.5">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                  </td>
                </tr>
                <tr v-if="filteredUsers.length === 0"><td colspan="6" class="p-12 text-center text-gray-500 bg-gray-50/50"><div class="flex flex-col items-center gap-2"><svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg><span class="font-medium">No users found.</span></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showUserModal" class="fixed inset-0 bg-gray-900/80 flex items-center justify-center p-4 z-50 backdrop-blur-sm animate-fade-in">
      <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl border-t-4 border-[#16a34a] transform transition-all">
        <h2 class="text-2xl font-extrabold mb-4 border-b pb-2 text-gray-800">{{ isEditingUser ? 'Edit User Record' : 'Create New User' }}</h2>
        <form @submit.prevent="saveUser" class="space-y-4">
          
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Role</label>
            <select v-model="userForm.role" @change="handleRoleChange" class="w-full border-gray-300 border p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] bg-gray-50 font-medium transition-colors">
              <option value="student">Student</option>
              <option value="admin">System Admin</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">{{ userForm.role === 'student' ? 'ID Number' : 'Email Address' }}</label>
            <div class="relative flex items-center">
              <input v-model="userForm.identifier" @input="formatIdentifier" @keydown.tab="handleTab" type="text" required :placeholder="userForm.role === 'student' ? 'XXX-XXXXX' : 'admin'" :maxlength="userForm.role === 'student' ? 9 : 255" :class="{'border-red-400 text-red-600 focus:ring-red-100': isInvalidID, 'border-gray-300 focus:ring-green-100': !isInvalidID}" class="w-full border p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:border-[#16a34a] bg-transparent font-medium relative z-10 transition-colors">
              <div v-if="userForm.role === 'admin' && showEmailSuggestion" class="absolute left-0 pl-3 py-2.5 text-gray-400 pointer-events-none z-0 flex whitespace-pre font-medium"><span class="opacity-0">{{ userForm.identifier }}</span><span>@carsu.edu.ph</span></div>
            </div>
            <p v-if="userForm.role === 'admin' && showEmailSuggestion" class="text-xs text-gray-500 mt-1 font-medium italic animate-pulse">Press <kbd class="bg-gray-100 border px-1 rounded shadow-sm">Tab</kbd> to autocomplete</p>
            <p v-if="userForm.role === 'student' && isInvalidID" class="text-xs text-red-500 mt-1 font-medium">Must be exactly 9 characters (XXX-XXXXX)</p>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Full Name</label>
            <input v-model="userForm.name" @input="formatName" type="text" placeholder="Firstname Lastname" required class="w-full border-gray-300 border p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] font-medium transition-colors">
          </div>
          
          <div v-if="userForm.role === 'student'" class="grid grid-cols-2 gap-4">
            <div class="relative course-dropdown-container">
              <label class="block text-sm font-bold text-gray-700 mb-1">Course</label>
              <button type="button" @click="toggleDropdown" class="w-full p-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] bg-gray-50 flex justify-between items-center text-gray-700 font-medium transition-colors">
                <span class="truncate">{{ userForm.course }}</span>
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
              </button>
              <div v-if="showCourseDropdown" class="absolute z-[9999] w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl flex flex-col max-h-48 overflow-hidden">
                <div class="p-2 border-b bg-gray-50 shrink-0"><input v-model="courseSearchQuery" type="text" placeholder="Find..." class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:outline-none focus:ring-1 focus:ring-[#16a34a]" @click.stop></div>
                <div class="overflow-y-auto grow p-1">
                  <div v-for="course in filteredModalCourses" :key="course" @click="selectCourse(course, true)" class="px-3 py-2 rounded hover:bg-[#16a34a] hover:text-white cursor-pointer text-sm font-medium transition-colors" :class="{'bg-[#16a34a] text-white': userForm.course === course}">{{ course }}</div>
                  <div v-if="filteredModalCourses.length === 0" class="px-3 py-2 text-sm text-gray-500 italic">No match.</div>
                </div>
              </div>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Year Level</label>
              <input v-model="userForm.year_level" type="number" min="1" max="5" required class="w-full border-gray-300 border p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] transition-colors">
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">{{ isEditingUser ? 'New Password (Optional)' : 'Temporary Password' }}</label>
            <div class="relative flex items-center">
              <input v-model="userForm.password" :type="showUserPassword ? 'text' : 'password'" maxlength="8" placeholder="••••••••" :required="!isEditingUser" class="w-full border-gray-300 border p-2.5 pr-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-100 focus:border-[#16a34a] font-medium transition-colors">
              <button type="button" @click="showUserPassword = !showUserPassword" class="absolute right-3 text-gray-400 hover:text-gray-600 focus:outline-none transition-colors" title="Toggle Visibility">
                <svg v-if="showUserPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
              </button>
            </div>
            <p class="text-xs text-gray-500 mt-1 font-medium">Max 8 characters.</p>
          </div>

          <div class="flex justify-end space-x-3 pt-6 mt-2 border-t border-gray-100">
            <button type="button" @click="showUserModal = false" class="bg-white border border-gray-300 px-5 py-2.5 rounded-lg hover:bg-gray-50 text-gray-700 font-bold transition-colors shadow-sm">Cancel</button>
            <button type="submit" class="bg-gradient-to-r from-[#16a34a] to-green-700 px-6 py-2.5 rounded-lg hover:from-green-700 hover:to-green-800 text-white font-bold shadow-md transform hover:-translate-y-0.5 transition-all">{{ isEditingUser ? 'Update User' : 'Save User' }}</button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showThesisModal" class="fixed inset-0 bg-gray-900/80 flex items-center justify-center p-4 z-50 backdrop-blur-sm animate-fade-in">
      <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl border-t-4 border-yellow-500 transform transition-all">
        <h2 class="text-2xl font-extrabold mb-4 border-b pb-2 text-gray-800">{{ isEditingThesis ? 'Edit Capstone Archive' : 'Upload New Capstone' }}</h2>
        <form @submit.prevent="saveThesis" class="space-y-4">
          <div><label class="block text-sm font-bold text-gray-700 mb-1">Title</label><input v-model="thesisForm.title" type="text" required class="w-full border-gray-300 border p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-100 focus:border-yellow-500 font-medium transition-colors"></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">Abstract</label><textarea v-model="thesisForm.abstract" rows="4" required class="w-full border-gray-300 border p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-100 focus:border-yellow-500 transition-colors"></textarea></div>
          
          <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-sm font-bold text-gray-700 mb-1">Publication Year</label><input v-model="thesisForm.publication_year" type="number" required class="w-full border-gray-300 border p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-100 focus:border-yellow-500 transition-colors"></div>
            
            <div class="relative course-dropdown-container">
              <label class="block text-sm font-bold text-gray-700 mb-1">Course</label>
              <button type="button" @click="toggleDropdown" class="w-full p-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-100 focus:border-yellow-500 bg-gray-50 flex justify-between items-center text-gray-700 font-medium transition-colors">
                <span class="truncate">{{ thesisForm.course_category }}</span>
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
              </button>
              <div v-if="showCourseDropdown" class="absolute z-[9999] w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl flex flex-col max-h-48 overflow-hidden">
                <div class="p-2 border-b bg-gray-50 shrink-0"><input v-model="courseSearchQuery" type="text" placeholder="Find..." class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:outline-none focus:ring-1 focus:ring-yellow-500" @click.stop></div>
                <div class="overflow-y-auto grow p-1">
                  <div v-for="course in filteredModalCourses" :key="course" @click="selectCourse(course, false)" class="px-3 py-2 rounded hover:bg-yellow-500 hover:text-white cursor-pointer text-sm font-medium transition-colors" :class="{'bg-yellow-500 text-white': thesisForm.course_category === course}">{{ course }}</div>
                  <div v-if="filteredModalCourses.length === 0" class="px-3 py-2 text-sm text-gray-500 italic">No match.</div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="border border-gray-200 p-4 rounded-lg bg-gray-50/50 shadow-inner">
            <div class="flex justify-between items-center mb-3">
              <label class="block text-sm font-bold text-gray-700">Authors</label>
              <button type="button" @click="addAuthorField" class="text-xs bg-white text-gray-700 border border-gray-300 px-3 py-1.5 rounded-lg hover:bg-gray-100 transition-colors font-bold shadow-sm flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Add Author</button>
            </div>
            <div v-for="(author, index) in thesisForm.authors" :key="index" class="flex space-x-2 mb-2 items-center">
              <input v-model="author.first_name" type="text" placeholder="First Name" required class="w-1/2 border-gray-300 border p-2.5 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-100 focus:border-yellow-500 transition-colors">
              <input v-model="author.last_name" type="text" placeholder="Last Name" required class="w-1/2 border-gray-300 border p-2.5 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-100 focus:border-yellow-500 transition-colors">
              <button v-if="thesisForm.authors.length > 1" type="button" @click="removeAuthorField(index)" class="text-red-400 hover:text-red-600 p-1 transition-colors" title="Remove"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">PDF Document</label>
            <input type="file" accept="application/pdf" ref="fileInput" @change="handleFileUpload" :required="!isEditingThesis" class="w-full border border-gray-300 p-2 rounded-lg bg-white cursor-pointer text-sm">
            <p v-if="isEditingThesis" class="text-xs text-gray-500 mt-1 font-medium">Leave blank to keep current file.</p>
          </div>
          
          <div class="flex justify-end space-x-3 pt-6 mt-2 border-t border-gray-100">
            <button type="button" @click="showThesisModal = false" class="bg-white border border-gray-300 px-5 py-2.5 rounded-lg hover:bg-gray-50 text-gray-700 font-bold transition-colors shadow-sm">Cancel</button>
            <button type="submit" class="bg-gradient-to-r from-yellow-400 to-yellow-500 px-6 py-2.5 rounded-lg hover:from-yellow-500 hover:to-yellow-600 text-yellow-950 font-bold shadow-md transform hover:-translate-y-0.5 transition-all">{{ isEditingThesis ? 'Update Archive' : 'Save to Archive' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style>
.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>