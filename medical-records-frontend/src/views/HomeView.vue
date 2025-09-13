<template>
  <div class="home-dashboard">
    <!-- Header Section -->
    <div class="dashboard-header bg-gradient-primary text-white p-4 mb-4">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h1 class="h2 mb-2">
              <i class="bi bi-house-heart me-2"></i>
              Bảng điều khiển hệ thống
            </h1>
            <p class="mb-0 opacity-90">
              Chào mừng bạn đến với hệ thống quản lý hồ sơ bệnh án
            </p>
          </div>
          <div class="col-md-4 text-md-end">
            <div class="d-flex align-items-center justify-content-md-end">
              <div class="me-3">
                <small class="d-block opacity-75">Lần đăng nhập cuối</small>
                <small class="fw-bold">{{ currentDateTime }}</small>
              </div>
              <div class="user-avatar me-3">
                <i class="bi bi-person-circle" style="font-size: 2.5rem;"></i>
              </div>
              <!-- Logout Button -->
              <div class="dropdown">
                <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="bi bi-gear me-1"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <a class="dropdown-item" href="#" @click="goToProfile">
                      <i class="bi bi-person me-2"></i>Hồ sơ cá nhân
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item" href="#" @click="goToSettings">
                      <i class="bi bi-gear me-2"></i>Cài đặt
                    </a>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <a class="dropdown-item text-danger" href="#" @click="logout">
                      <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container-fluid">
      <!-- Statistics Cards -->
      <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3" v-for="stat in statistics" :key="stat.title">
          <div class="card stat-card h-100 border-0 shadow-sm" :class="{ loading: loading }">
            <div class="card-body p-3">
              <div class="d-flex align-items-center">
                <div class="stat-icon me-3" :style="{ background: stat.gradient }">
                  <i :class="stat.icon" class="text-white"></i>
                </div>
                <div class="flex-grow-1">
                  <div class="stat-number">{{ stat.value }}</div>
                  <div class="stat-title">{{ stat.title }}</div>
                </div>
              </div>
              <div class="stat-trend mt-2">
                <small :class="stat.trend.isPositive ? 'text-success' : 'text-danger'">
                  <i :class="stat.trend.isPositive ? 'bi bi-arrow-up' : 'bi bi-arrow-down'"></i>
                  {{ stat.trend.percentage }}% so với tháng trước
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions & Recent Activity -->
      <div class="row">
        <!-- Quick Actions -->
        <div class="col-lg-8 mb-4">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pb-0">
              <h5 class="mb-3">
                <i class="bi bi-lightning-charge text-primary me-2"></i>
                Truy cập nhanh
              </h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-lg-4 col-md-6" v-for="action in quickActions" :key="action.name">
                  <div class="quick-action-card" @click="navigateTo(action.route)">
                    <div class="action-icon" :style="{ background: action.color }">
                      <i :class="action.icon" class="text-white"></i>
                    </div>
                    <div class="action-content">
                      <h6 class="action-title">{{ action.title }}</h6>
                      <p class="action-desc">{{ action.description }}</p>
                    </div>
                    <div class="action-arrow">
                      <i class="bi bi-arrow-right"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-4 mb-4">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pb-0">
              <h5 class="mb-3">
                <i class="bi bi-clock-history text-primary me-2"></i>
                Hoạt động gần đây
              </h5>
            </div>
            <div class="card-body">
              <div class="activity-list">
                <div class="activity-item" v-for="activity in recentActivities" :key="activity.id">
                  <div class="activity-icon" :class="activity.iconClass">
                    <i :class="activity.icon"></i>
                  </div>
                  <div class="activity-content">
                    <div class="activity-text">{{ activity.text }}</div>
                    <div class="activity-time">{{ activity.time }}</div>
                  </div>
                </div>
              </div>
              <div class="text-center mt-3">
                <button class="btn btn-outline-primary btn-sm">
                  <i class="bi bi-eye me-1"></i>
                  Xem tất cả
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Management Sections -->
      <div class="row">
        <!-- Patient & Medical Management -->
        <div class="col-lg-6 mb-4">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
              <h5 class="mb-0">
                <i class="bi bi-person-hearts text-danger me-2"></i>
                Quản lý bệnh nhân & Y tế
              </h5>
            </div>
            <div class="card-body">
              <div class="row g-2">
                <div class="col-6" v-for="item in patientManagement" :key="item.name">
                  <div class="management-item" @click="navigateTo(item.route)">
                    <div class="item-icon">
                      <i :class="[item.icon, item.colorClass]"></i>
                    </div>
                    <div class="item-title">{{ item.title }}</div>
                    <div class="item-count">{{ item.count }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- System & User Management -->
        <div class="col-lg-6 mb-4">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
              <h5 class="mb-0">
                <i class="bi bi-gear-fill text-info me-2"></i>
                Quản lý hệ thống & Người dùng
              </h5>
            </div>
            <div class="card-body">
              <div class="row g-2">
                <div class="col-6" v-for="item in systemManagement" :key="item.name">
                  <div class="management-item" @click="navigateTo(item.route)">
                    <div class="item-icon">
                      <i :class="[item.icon, item.colorClass]"></i>
                    </div>
                    <div class="item-title">{{ item.title }}</div>
                    <div class="item-count">{{ item.count }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- System Status -->
      <div class="row">
        <div class="col-12">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                  <i class="bi bi-shield-check text-success me-2"></i>
                  Trạng thái hệ thống
                </h5>
                <div class="system-status">
                  <span class="badge bg-success">
                    <i class="bi bi-check-circle me-1"></i>
                    Hoạt động bình thường
                  </span>
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="row text-center">
                <div class="col-md-3 col-6 mb-3">
                  <div class="system-metric">
                    <div class="metric-value text-success">99.9%</div>
                    <div class="metric-label">Uptime</div>
                  </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                  <div class="system-metric">
                    <div class="metric-value text-primary">245ms</div>
                    <div class="metric-label">Độ trễ trung bình</div>
                  </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                  <div class="system-metric">
                    <div class="metric-value text-warning">15GB</div>
                    <div class="metric-label">Dung lượng sử dụng</div>
                  </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                  <div class="system-metric">
                    <div class="metric-value text-info">1,234</div>
                    <div class="metric-label">Phiên hoạt động</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
// Import các service để lấy dữ liệu thực
import PatientService from '@/api/patientService'
import StaffService from '@/api/staffService'
import DoctorService from '@/api/doctorService'
import UserService from '@/api/userService'
import RoleService from '@/api/roleService'
import MedicalRecordService from '@/api/medicalRecordService'
import AppointmentService from '@/api/appointmentService'
import MedicalTestService from '@/api/medicalTestService'
import TreatmentService from '@/api/treatmentService'
import MedicationService from '@/api/medicationService'

export default {
  name: 'HomeView',
  data () {
    return {
      currentDateTime: '',
      // Thống kê với dữ liệu động
      statistics: [
        {
          title: 'Tổng bệnh nhân',
          value: '0', // sẽ được cập nhật từ API
          icon: 'bi bi-people-fill',
          gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
          trend: { isPositive: true, percentage: '0' }
        },
        {
          title: 'Hồ sơ y tế',
          value: '0',
          icon: 'bi bi-file-medical-fill',
          gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
          trend: { isPositive: true, percentage: '0' }
        },
        {
          title: 'Nhân viên',
          value: '0',
          icon: 'bi bi-person-workspace',
          gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
          trend: { isPositive: false, percentage: '0' }
        },
        {
          title: 'Người dùng',
          value: '0',
          icon: 'bi bi-people-fill',
          gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
          trend: { isPositive: true, percentage: '0' }
        }
      ],
      // Quick actions giữ nguyên
      quickActions: [
        {
          title: 'Thêm bệnh nhân mới',
          description: 'Đăng ký bệnh nhân mới vào hệ thống',
          icon: 'bi bi-person-plus-fill',
          color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
          route: 'patients'
        },
        {
          title: 'Quản lý nhân viên',
          description: 'Quản lý thông tin nhân viên y tế',
          icon: 'bi bi-person-workspace',
          color: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
          route: 'staff'
        },
        {
          title: 'Hồ sơ y tế',
          description: 'Quản lý hồ sơ bệnh án',
          icon: 'bi bi-file-medical-fill',
          color: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
          route: 'medical-records'
        },
        {
          title: 'Quản lý người dùng',
          description: 'Quản lý tài khoản và phân quyền',
          icon: 'bi bi-people-fill',
          color: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
          route: 'users'
        },
        {
          title: 'Phân quyền',
          description: 'Quản lý vai trò và quyền hạn',
          icon: 'bi bi-shield-lock-fill',
          color: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
          route: 'roles'
        },
        {
          title: 'Báo cáo',
          description: 'Xem báo cáo và thống kê',
          icon: 'bi bi-bar-chart-fill',
          color: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
          route: 'reports'
        }
      ],
      // Cập nhật với dữ liệu động
      patientManagement: [
        { title: 'Bệnh nhân', icon: 'bi bi-people-fill', colorClass: 'text-primary', route: 'patients', count: '0' },
        { title: 'Hồ sơ y tế', icon: 'bi bi-file-medical-fill', colorClass: 'text-danger', route: 'medical-records', count: '0' },
        { title: 'Cuộc hẹn', icon: 'bi bi-calendar-check-fill', colorClass: 'text-info', route: 'appointments', count: '0' },
        { title: 'Xét nghiệm', icon: 'bi bi-clipboard2-pulse-fill', colorClass: 'text-success', route: 'medical-tests', count: '0' },
        { title: 'Điều trị', icon: 'bi bi-heart-pulse-fill', colorClass: 'text-warning', route: 'treatments', count: '0' },
        { title: 'Đơn thuốc', icon: 'bi bi-capsule-pill', colorClass: 'text-secondary', route: 'medications', count: '0' }
      ],
      systemManagement: [
        { title: 'Bác sĩ', icon: 'bi bi-person-badge-fill', colorClass: 'text-primary', route: 'doctors', count: '0' },
        { title: 'Nhân viên', icon: 'bi bi-person-workspace', colorClass: 'text-info', route: 'staff', count: '0' },
        { title: 'Người dùng', icon: 'bi bi-people-fill', colorClass: 'text-success', route: 'users', count: '0' },
        { title: 'Phân quyền', icon: 'bi bi-shield-lock-fill', colorClass: 'text-warning', route: 'roles', count: '0' },
        { title: 'Hóa đơn', icon: 'bi bi-receipt-cutoff', colorClass: 'text-danger', route: 'invoices', count: '0' },
        { title: 'Báo cáo', icon: 'bi bi-bar-chart-fill', colorClass: 'text-secondary', route: 'reports', count: '0' }
      ],
      recentActivities: [
        {
          id: 1,
          icon: 'bi bi-person-plus-fill',
          iconClass: 'activity-icon-primary',
          text: 'Hệ thống đã sẵn sàng hoạt động',
          time: 'Vừa xong'
        },
        {
          id: 2,
          icon: 'bi bi-shield-check-fill',
          iconClass: 'activity-icon-success',
          text: 'Đã tải xong dữ liệu thống kê',
          time: 'Vừa xong'
        }
      ],
      // Trạng thái loading
      loading: true
    }
  },
  async mounted () {
    this.updateDateTime()
    setInterval(this.updateDateTime, 60000)
    // Tải dữ liệu thống kê
    await this.loadStatistics()
  },
  methods: {
    updateDateTime () {
      const now = new Date()
      this.currentDateTime = now.toLocaleString('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      })
    },

    // Hàm tải dữ liệu thống kê từ API
    async loadStatistics () {
      this.loading = true
      try {
        // Tải tất cả dữ liệu song song
        const [
          patientsRes,
          staffRes,
          doctorsRes,
          usersRes,
          rolesRes,
          medicalRecordsRes,
          appointmentsRes,
          medicalTestsRes,
          treatmentsRes,
          medicationsRes
        ] = await Promise.allSettled([
          this.getPatientCount(),
          this.getStaffCount(),
          this.getDoctorCount(),
          this.getUserCount(),
          this.getRoleCount(),
          this.getMedicalRecordCount(),
          this.getAppointmentCount(),
          this.getMedicalTestCount(),
          this.getTreatmentCount(),
          this.getMedicationCount()
        ])

        // Cập nhật thống kê chính
        if (patientsRes.status === 'fulfilled') {
          this.statistics[0].value = this.formatNumber(patientsRes.value)
          this.updateManagementCount('Bệnh nhân', patientsRes.value)
        }

        if (medicalRecordsRes.status === 'fulfilled') {
          this.statistics[1].value = this.formatNumber(medicalRecordsRes.value)
          this.updateManagementCount('Hồ sơ y tế', medicalRecordsRes.value)
        }

        if (staffRes.status === 'fulfilled') {
          this.statistics[2].value = this.formatNumber(staffRes.value)
          this.updateManagementCount('Nhân viên', staffRes.value)
        }

        if (usersRes.status === 'fulfilled') {
          this.statistics[3].value = this.formatNumber(usersRes.value)
          this.updateManagementCount('Người dùng', usersRes.value)
        }

        // Cập nhật các management counts khác
        if (doctorsRes.status === 'fulfilled') {
          this.updateManagementCount('Bác sĩ', doctorsRes.value)
        }

        if (rolesRes.status === 'fulfilled') {
          this.updateManagementCount('Phân quyền', rolesRes.value)
        }

        if (appointmentsRes.status === 'fulfilled') {
          this.updateManagementCount('Cuộc hẹn', appointmentsRes.value)
        }

        if (medicalTestsRes.status === 'fulfilled') {
          this.updateManagementCount('Xét nghiệm', medicalTestsRes.value)
        }

        if (treatmentsRes.status === 'fulfilled') {
          this.updateManagementCount('Điều trị', treatmentsRes.value)
        }

        if (medicationsRes.status === 'fulfilled') {
          this.updateManagementCount('Đơn thuốc', medicationsRes.value)
        }

        // Cập nhật hoạt động gần đây
        this.updateRecentActivities()
      } catch (error) {
        console.error('Lỗi khi tải dữ liệu thống kê:', error)
      } finally {
        this.loading = false
      }
    },

    // Các hàm lấy số liệu từ API
    async getPatientCount () {
      try {
        const res = await PatientService.list({ limit: 1 })
        return this.extractTotal(res)
      } catch (error) {
        console.error('Lỗi khi lấy số lượng bệnh nhân:', error)
        return 0
      }
    },

    async getStaffCount () {
      try {
        const res = await StaffService.list({ limit: 1 })
        return this.extractTotal(res)
      } catch (error) {
        console.error('Lỗi khi lấy số lượng nhân viên:', error)
        return 0
      }
    },

    async getDoctorCount () {
      try {
        const res = await DoctorService.list({ limit: 1 })
        return this.extractTotal(res)
      } catch (error) {
        console.error('Lỗi khi lấy số lượng bác sĩ:', error)
        return 0
      }
    },

    async getUserCount () {
      try {
        const res = await UserService.list({ limit: 1 })
        return this.extractTotal(res)
      } catch (error) {
        console.error('Lỗi khi lấy số lượng người dùng:', error)
        return 0
      }
    },

    async getRoleCount () {
      try {
        const res = await RoleService.list({ limit: 1 })
        return this.extractTotal(res)
      } catch (error) {
        console.error('Lỗi khi lấy số lượng vai trò:', error)
        return 0
      }
    },

    async getMedicalRecordCount () {
      try {
        const res = await MedicalRecordService.list({ limit: 1 })
        return this.extractTotal(res)
      } catch (error) {
        console.error('Lỗi khi lấy số lượng hồ sơ y tế:', error)
        return 0
      }
    },

    async getAppointmentCount () {
      try {
        const res = await AppointmentService.list({ limit: 1 })
        return this.extractTotal(res)
      } catch (error) {
        console.error('Lỗi khi lấy số lượng cuộc hẹn:', error)
        return 0
      }
    },

    async getMedicalTestCount () {
      try {
        const res = await MedicalTestService.list({ limit: 1 })
        return this.extractTotal(res)
      } catch (error) {
        console.error('Lỗi khi lấy số lượng xét nghiệm:', error)
        return 0
      }
    },

    async getTreatmentCount () {
      try {
        const res = await TreatmentService.list({ limit: 1 })
        return this.extractTotal(res)
      } catch (error) {
        console.error('Lỗi khi lấy số lượng điều trị:', error)
        return 0
      }
    },

    async getMedicationCount () {
      try {
        const res = await MedicationService.list({ limit: 1 })
        return this.extractTotal(res)
      } catch (error) {
        console.error('Lỗi khi lấy số lượng đơn thuốc:', error)
        return 0
      }
    },

    // Helper function để extract total từ response
    extractTotal (res) {
      if (!res) return 0

      // Thử các cấu trúc response khác nhau
      if (typeof res.total === 'number') return res.total
      if (typeof res.total_rows === 'number') return res.total_rows
      if (typeof res.count === 'number') return res.count
      if (res.data && typeof res.data.total === 'number') return res.data.total
      if (res.data && typeof res.data.total_rows === 'number') return res.data.total_rows
      if (res.meta && typeof res.meta.total === 'number') return res.meta.total
      if (res.pagination && typeof res.pagination.total === 'number') return res.pagination.total

      // Nếu trả về array, đếm length
      if (Array.isArray(res)) return res.length
      if (Array.isArray(res.data)) return res.data.length
      if (Array.isArray(res.items)) return res.items.length
      if (Array.isArray(res.rows)) return res.rows.length

      return 0
    },

    // Format số với dấu phẩy
    formatNumber (num) {
      if (num === 0) return '0'
      if (num < 1000) return num.toString()
      if (num < 1000000) return (num / 1000).toFixed(1) + 'K'
      return (num / 1000000).toFixed(1) + 'M'
    },

    // Cập nhật số liệu trong management cards
    updateManagementCount (title, count) {
      // Cập nhật trong patientManagement
      const patientItem = this.patientManagement.find(item => item.title === title)
      if (patientItem) {
        patientItem.count = this.formatNumber(count)
      }

      // Cập nhật trong systemManagement
      const systemItem = this.systemManagement.find(item => item.title === title)
      if (systemItem) {
        systemItem.count = this.formatNumber(count)
      }
    },

    // Cập nhật hoạt động gần đây
    updateRecentActivities () {
      const now = new Date()
      this.recentActivities = [
        {
          id: 1,
          icon: 'bi bi-bar-chart-fill',
          iconClass: 'activity-icon-success',
          text: `Đã cập nhật thống kê lúc ${now.toLocaleTimeString('vi-VN')}`,
          time: 'Vừa xong'
        },
        {
          id: 2,
          icon: 'bi bi-shield-check-fill',
          iconClass: 'activity-icon-primary',
          text: 'Hệ thống hoạt động bình thường',
          time: 'Vừa xong'
        },
        ...this.recentActivities.slice(2) // Giữ lại các hoạt động cũ
      ]
    },

    // Reload dữ liệu thống kê
    async refreshStatistics () {
      await this.loadStatistics()
    },

    navigateTo (routeName) {
      if (routeName && this.$router.hasRoute(routeName)) {
        this.$router.push({ name: routeName })
      }
    },

    logout () {
      // Xóa token và thông tin user từ localStorage
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      localStorage.removeItem('jwt')
      localStorage.removeItem('access_token')

      // Xóa state trong Vuex store (nếu có)
      if (this.$store.dispatch) {
        this.$store.dispatch('logout').catch(() => {})
      }

      // Clear Vuex state manually (fallback)
      if (this.$store.commit) {
        this.$store.commit('SET_TOKEN', null)
        this.$store.commit('SET_USER', null)
      }

      // Redirect về trang login với replace để không back được
      this.$router.replace({ name: 'login' })

      // Hiển thị thông báo (nếu có toast)
      if (this.$toast) {
        this.$toast.success('Đăng xuất thành công!')
      } else {
        console.log('Đăng xuất thành công!')
      }
    },

    goToProfile () {
      this.$router.push({ name: 'profile' })
    },

    goToSettings () {
      this.$router.push({ name: 'settings' })
    }
  }
}
</script>

<style scoped>
.bg-gradient-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card {
  transition: all 0.3s ease;
  cursor: pointer;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.stat-number {
  font-size: 1.5rem;
  font-weight: bold;
  line-height: 1;
}

.stat-title {
  font-size: 0.875rem;
  color: #6c757d;
  font-weight: 500;
}

.quick-action-card {
  background: white;
  border: 2px solid #f8f9fa;
  border-radius: 12px;
  padding: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  text-decoration: none;
  color: inherit;
}

.quick-action-card:hover {
  border-color: #667eea;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
}

.action-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 0.75rem;
  flex-shrink: 0;
}

.action-content {
  flex-grow: 1;
}

.action-title {
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  line-height: 1.2;
}

.action-desc {
  font-size: 0.75rem;
  color: #6c757d;
  margin: 0;
  line-height: 1.3;
}

.action-arrow {
  color: #dee2e6;
  font-size: 1rem;
  margin-left: 0.5rem;
}

.quick-action-card:hover .action-arrow {
  color: #667eea;
}

.management-item {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 0.75rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  height: 100%;
}

.management-item:hover {
  border-color: #667eea;
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
}

.item-icon {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}

.item-title {
  font-size: 0.75rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.item-count {
  font-size: 0.875rem;
  font-weight: bold;
  color: #495057;
}

.activity-list {
  max-height: 300px;
  overflow-y: auto;
}

.activity-item {
  display: flex;
  align-items: flex-start;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f8f9fa;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 0.75rem;
  flex-shrink: 0;
  font-size: 0.875rem;
}

.activity-icon-primary { background: rgba(102, 126, 234, 0.1); color: #667eea; }
.activity-icon-success { background: rgba(40, 167, 69, 0.1); color: #28a745; }
.activity-icon-info { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
.activity-icon-warning { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
.activity-icon-danger { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

.activity-content {
  flex-grow: 1;
}

.activity-text {
  font-size: 0.875rem;
  line-height: 1.4;
  margin-bottom: 0.25rem;
}

.activity-time {
  font-size: 0.75rem;
  color: #6c757d;
}

.system-metric {
  padding: 0.5rem;
}

.metric-value {
  font-size: 1.25rem;
  font-weight: bold;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.metric-label {
  font-size: 0.75rem;
  color: #6c757d;
  font-weight: 500;
}

.user-avatar {
  opacity: 0.9;
}

.card {
  border-radius: 12px;
}

.card-header {
  border-radius: 12px 12px 0 0 !important;
}

/* Responsive */
@media (max-width: 768px) {
  .dashboard-header {
    text-align: center;
  }

  .dashboard-header .col-md-4 {
    margin-top: 1rem;
  }

  .quick-action-card {
    margin-bottom: 0.5rem;
  }

  .action-title {
    font-size: 0.8rem;
  }

  .action-desc {
    display: none;
  }
}

/* Thêm loading state cho statistics */
.stat-card.loading {
  opacity: 0.6;
  pointer-events: none;
}

.stat-card.loading .stat-number::after {
  content: '...';
  animation: loading-dots 1.5s infinite;
}

@keyframes loading-dots {
  0%, 20% { content: ''; }
  40% { content: '.'; }
  60% { content: '..'; }
  80%, 100% { content: '...'; }
}
</style>
