import { createRouter, createWebHistory } from 'vue-router';
import Welcome from '../views/Welcome.vue'; // <-- 1. Import the new page
import Login from '../views/Login.vue';
import StudentArchive from '../views/StudentArchive.vue';
import AdminDashboard from '../views/AdminDashboard.vue';

const routes = [
  { path: '/', component: Welcome },
  { path: '/login', component: Login },
  { 
    path: '/student', 
    component: StudentArchive,
    meta: { requiresAuth: true, role: 'student' } 
  },
  { 
    path: '/admin', 
    component: AdminDashboard,
    meta: { requiresAuth: true, role: 'admin' } 
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation Guard: Protect pages so you can't enter without logging in
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token');
  const role = localStorage.getItem('role');

  if (to.meta.requiresAuth && !token) {
    next('/login'); // Not logged in? Go back to login
  } else if (to.meta.requiresAuth && to.meta.role !== role) {
    // Prevent students from accessing admin, and admins from accessing student view
    next(role === 'admin' ? '/admin' : '/student'); 
  } else {
    next(); // Proceed normally
  }
});

export default router;