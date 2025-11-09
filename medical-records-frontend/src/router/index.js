// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import store from '@/store'

// Core pages
const HomeView = () => import('@/views/HomeView.vue')
const LoginView = () => import('@/views/LoginView.vue')

// Management pages (mỗi folder 1 view)
const PatientsListView = () => import('@/views/PatientManagement/PatientsListView.vue')
const DoctorsListView = () => import('@/views/DoctorManagement/DoctorsListView.vue')
const StaffListView = () => import('@/views/StaffManagement/StaffListView.vue')
const UsersListView = () => import('@/views/UserManagement/UsersListView.vue')
const RolesListView = () => import('@/views/RoleManagement/RolesListView.vue')
const MedicalRecordsListView = () => import('@/views/MedicalRecordManagement/MedicalRecordsListView.vue')
const MedicalTestsListView = () => import('@/views/MedicalTestManagement/MedicalTestsListView.vue')
const MedicationsListView = () => import('@/views/MedicationManagement/MedicationsListView.vue')
const TreatmentsListView = () => import('@/views/TreatmentManagement/TreatmentsListView.vue')
const AppointmentsListView = () => import('@/views/AppointmentManagement/AppointmentsListView.vue')
const InvoicesListView = () => import('@/views/InvoiceManagement/InvoicesListView.vue')
const ConsultationChatView = () => import('@/views/ConsultationManagement/ConsultationChatView.vue')

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes: [
    { path: '/login', name: 'login', component: LoginView, meta: { guestOnly: true, fullWidth: true } },

    { path: '/', name: 'home', component: HomeView, meta: { requiresAuth: true } }, // Home yêu cầu đăng nhập

    // 11 trang quản lý
    { path: '/patients', name: 'patients', component: PatientsListView, meta: { requiresAuth: true } },
    { path: '/doctors', name: 'doctors', component: DoctorsListView, meta: { requiresAuth: true } },
    { path: '/staff', name: 'staff', component: StaffListView, meta: { requiresAuth: true } },
    { path: '/users', name: 'users', component: UsersListView, meta: { requiresAuth: true } },
    { path: '/roles', name: 'roles', component: RolesListView, meta: { requiresAuth: true } },
    { path: '/medical-records', name: 'medical-records', component: MedicalRecordsListView, meta: { requiresAuth: true } },
    { path: '/medical-tests', name: 'medical-tests', component: MedicalTestsListView, meta: { requiresAuth: true } },
    { path: '/medications', name: 'medications', component: MedicationsListView, meta: { requiresAuth: true } },
    { path: '/treatments', name: 'treatments', component: TreatmentsListView, meta: { requiresAuth: true } },
    { path: '/appointments', name: 'appointments', component: AppointmentsListView, meta: { requiresAuth: true } },
    { path: '/invoices', name: 'invoices', component: InvoicesListView, meta: { requiresAuth: true } },

    // Consultation Chat (cho staff, admin, doctor)
    { path: '/consultations', name: 'consultations', component: ConsultationChatView, meta: { requiresAuth: true, roles: ['admin', 'staff', 'nurse', 'receptionist', 'doctor'] } },

    // bắt các đường dẫn lạ
    { path: '/:pathMatch(.*)*', redirect: '/' }
  ],
  scrollBehavior () { return { top: 0 } }
})

let ensuringUser = false
async function ensureUser () {
  if (ensuringUser) return
  const hasToken =
        store.getters.isAuthenticated ||
        !!localStorage.getItem('access_token') ||
        !!localStorage.getItem('token')
  if (hasToken && !store.state.user) {
    ensuringUser = true
    try { await store.dispatch('fetchMe') } catch (_) {}
    ensuringUser = false
  }
}

// Chuẩn hoá role về chữ thường, chấp nhận nhiều định dạng
function getUserRolesLower () {
  const u = store.state.user || {}
  let raw = store.getters?.roles ?? u.role_names ?? u.roles ?? []

  if (typeof raw === 'string') raw = [raw]
  if (!Array.isArray(raw)) raw = Object.values(raw || {})

  const lower = raw
    .map(r => {
      if (typeof r === 'string') return r
      if (!r || typeof r !== 'object') return ''
      return r.name || r.role || r.code || r.key || r.role_name || r.RoleName || ''
    })
    .filter(Boolean)
    .map(s => s.toString().toLowerCase())

  return new Set(lower)
}

router.beforeEach(async (to, from, next) => {
  await ensureUser()

  const isAuthed =
        store.getters.isAuthenticated ||
        !!localStorage.getItem('access_token') ||
        !!localStorage.getItem('token')

  if (to.meta.guestOnly && isAuthed) return next({ name: 'home' })
  if (to.meta.requiresAuth && !isAuthed) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  if (to.meta.roles?.length) {
    const userRoles = getUserRolesLower()
    console.log('🔍 Route requires roles:', to.meta.roles)
    console.log('🔍 User has roles:', Array.from(userRoles))

    // Admin qua tất cả
    if (!userRoles.has('admin')) {
      const required = to.meta.roles.map(r => r.toLowerCase())
      const ok = required.some(r => userRoles.has(r))
      console.log('🔍 Role check:', { required, userRoles: Array.from(userRoles), ok })
      if (!ok) {
        console.warn('⚠️ Access denied: user roles do not match')
        return next({ name: 'home' })
      }
    } else {
      console.log('✅ Admin role detected, allowing access')
    }
  }

  next()
})

export default router
