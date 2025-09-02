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
          <div class="card stat-card h-100 border-0 shadow-sm">
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
export default {
  name: 'HomeView',
  data () {
    return {
      currentDateTime: '',
      statistics: [
        {
          title: 'Tổng bệnh nhân',
          value: '2,847',
          icon: 'bi bi-people-fill',
          gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
          trend: { isPositive: true, percentage: '12.5' }
        },
        {
          title: 'Hồ sơ y tế',
          value: '5,234',
          icon: 'bi bi-file-medical-fill',
          gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
          trend: { isPositive: true, percentage: '8.2' }
        },
        {
          title: 'Cuộc hẹn hôm nay',
          value: '156',
          icon: 'bi bi-calendar-check-fill',
          gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
          trend: { isPositive: false, percentage: '3.1' }
        },
        {
          title: 'Doanh thu tháng',
          value: '₫45.2M',
          icon: 'bi bi-graph-up-arrow',
          gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
          trend: { isPositive: true, percentage: '15.8' }
        }
      ],
      quickActions: [
        {
          title: 'Thêm bệnh nhân mới',
          description: 'Đăng ký bệnh nhân mới vào hệ thống',
          icon: 'bi bi-person-plus-fill',
          color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
          route: 'patients'
        },
        {
          title: 'Tạo cuộc hẹn',
          description: 'Lên lịch khám bệnh cho bệnh nhân',
          icon: 'bi bi-calendar-plus-fill',
          color: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
          route: 'appointments'
        },
        {
          title: 'Hồ sơ y tế',
          description: 'Quản lý hồ sơ bệnh án',
          icon: 'bi bi-file-medical-fill',
          color: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
          route: 'medical-records'
        },
        {
          title: 'Xét nghiệm',
          description: 'Quản lý kết quả xét nghiệm',
          icon: 'bi bi-clipboard2-pulse-fill',
          color: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
          route: 'medical-tests'
        },
        {
          title: 'Đơn thuốc',
          description: 'Kê đơn và quản lý thuốc',
          icon: 'bi bi-capsule-pill',
          color: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
          route: 'medications'
        },
        {
          title: 'Hóa đơn',
          description: 'Quản lý thanh toán và hóa đơn',
          icon: 'bi bi-receipt-cutoff',
          color: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
          route: 'invoices'
        }
      ],
      patientManagement: [
        { title: 'Bệnh nhân', icon: 'bi bi-people-fill', colorClass: 'text-primary', route: 'patients', count: '2,847' },
        { title: 'Hồ sơ y tế', icon: 'bi bi-file-medical-fill', colorClass: 'text-danger', route: 'medical-records', count: '5,234' },
        { title: 'Cuộc hẹn', icon: 'bi bi-calendar-check-fill', colorClass: 'text-info', route: 'appointments', count: '1,456' },
        { title: 'Xét nghiệm', icon: 'bi bi-clipboard2-pulse-fill', colorClass: 'text-success', route: 'medical-tests', count: '892' },
        { title: 'Điều trị', icon: 'bi bi-heart-pulse-fill', colorClass: 'text-warning', route: 'treatments', count: '634' },
        { title: 'Đơn thuốc', icon: 'bi bi-capsule-pill', colorClass: 'text-secondary', route: 'medications', count: '1,123' }
      ],
      systemManagement: [
        { title: 'Bác sĩ', icon: 'bi bi-person-badge-fill', colorClass: 'text-primary', route: 'doctors', count: '89' },
        { title: 'Nhân viên', icon: 'bi bi-person-workspace', colorClass: 'text-info', route: 'staff', count: '156' },
        { title: 'Người dùng', icon: 'bi bi-people-fill', colorClass: 'text-success', route: 'users', count: '245' },
        { title: 'Phân quyền', icon: 'bi bi-shield-lock-fill', colorClass: 'text-warning', route: 'roles', count: '12' },
        { title: 'Hóa đơn', icon: 'bi bi-receipt-cutoff', colorClass: 'text-danger', route: 'invoices', count: '3,421' },
        { title: 'Báo cáo', icon: 'bi bi-bar-chart-fill', colorClass: 'text-secondary', route: 'reports', count: '45' }
      ],
      recentActivities: [
        {
          id: 1,
          icon: 'bi bi-person-plus-fill',
          iconClass: 'activity-icon-primary',
          text: 'Bệnh nhân mới đăng ký: Nguyễn Văn A',
          time: '5 phút trước'
        },
        {
          id: 2,
          icon: 'bi bi-file-medical-fill',
          iconClass: 'activity-icon-success',
          text: 'Hồ sơ y tế được cập nhật',
          time: '12 phút trước'
        },
        {
          id: 3,
          icon: 'bi bi-calendar-check-fill',
          iconClass: 'activity-icon-info',
          text: 'Cuộc hẹn mới được tạo',
          time: '28 phút trước'
        },
        {
          id: 4,
          icon: 'bi bi-clipboard2-pulse-fill',
          iconClass: 'activity-icon-warning',
          text: 'Kết quả xét nghiệm sẵn sàng',
          time: '1 giờ trước'
        },
        {
          id: 5,
          icon: 'bi bi-receipt-cutoff',
          iconClass: 'activity-icon-danger',
          text: 'Hóa đơn mới được thanh toán',
          time: '2 giờ trước'
        }
      ]
    }
  },
  mounted () {
    this.updateDateTime()
    setInterval(this.updateDateTime, 60000) // Cập nhật mỗi phút
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
</style>
