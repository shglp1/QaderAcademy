import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/HomeView.vue')
  },
  {
    path: '/courses',
    name: 'Courses',
    component: () => import('@/views/CoursesView.vue')
  },
  {
    path: '/courses/:id',
    name: 'CourseDetail',
    component: () => import('@/views/CourseDetailView.vue')
  },
  {
    path: '/learn/:enrollmentId',
    name: 'Learning',
    component: () => import('@/views/LearningView.vue'),
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/quiz/:quizId',
    name: 'QuizTaking',
    component: () => import('@/views/QuizTakingView.vue'),
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/checkout/:enrollmentId',
    name: 'Checkout',
    component: () => import('@/views/CheckoutView.vue'),
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('@/views/ProfileView.vue'),
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/LoginView.vue'),
    meta: { guest: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/RegisterView.vue'),
    meta: { guest: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login', query: { redirect: to.fullPath } })
  } else if (to.meta.guest && authStore.isAuthenticated) {
    next({ name: 'Home' })
  } else if (to.meta.role && authStore.user && authStore.user.role !== to.meta.role) {
    next({ name: 'Home' })
  } else {
    next()
  }
})

export default router
