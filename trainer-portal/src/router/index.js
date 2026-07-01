import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/LoginView.vue'),
    meta: { guest: true }
  },
  {
    path: '/',
    name: 'Dashboard',
    component: () => import('@/views/DashboardView.vue'),
    meta: { requiresAuth: true, role: 'trainer' }
  },
  {
    path: '/courses',
    name: 'Courses',
    component: () => import('@/views/CoursesView.vue'),
    meta: { requiresAuth: true, role: 'trainer' }
  },
  {
    path: '/courses/create',
    name: 'CreateCourse',
    component: () => import('@/views/CourseWizardView.vue'),
    meta: { requiresAuth: true, role: 'trainer' }
  },
  {
    path: '/courses/:id/edit',
    name: 'EditCourse',
    component: () => import('@/views/CourseWizardView.vue'),
    meta: { requiresAuth: true, role: 'trainer' }
  },
  {
    path: '/grading',
    name: 'Grading',
    component: () => import('@/views/GradingView.vue'),
    meta: { requiresAuth: true, role: 'trainer' }
  },
  {
    path: '/grading/:attemptId',
    name: 'GradeSubmission',
    component: () => import('@/views/GradeSubmissionView.vue'),
    meta: { requiresAuth: true, role: 'trainer' }
  },
  {
    path: '/earnings',
    name: 'Earnings',
    component: () => import('@/views/EarningsView.vue'),
    meta: { requiresAuth: true, role: 'trainer' }
  },
  {
    path: '/qa',
    name: 'QAInbox',
    component: () => import('@/views/QAInboxView.vue'),
    meta: { requiresAuth: true, role: 'trainer' }
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
    next({ name: 'Dashboard' })
  } else if (to.meta.role && authStore.user && authStore.user.role !== to.meta.role) {
    next({ name: 'Dashboard' })
  } else {
    next()
  }
})

export default router
