<template>
  <section class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 mb-0">📊 Báo cáo & Thống kê</h1>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" @click="refreshDashboard" :disabled="loading">
          🔄 Làm mới
        </button>
        <button class="btn btn-primary" @click="exportReport" :disabled="loading">
          📥 Xuất báo cáo
        </button>
      </div>
    </div>

    <!-- Dashboard Cards cho Admin -->
    <div class="row mb-4" v-if="userRole === 'admin'">
      <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h5 class="card-title">👥 Bệnh nhân</h5>
                <h2>{{ dashboardData.totalPatients }}</h2>
                <small>Tổng số bệnh nhân</small>
              </div>
              <div class="align-self-center">
                <i class="fas fa-users fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h5 class="card-title">👨‍⚕️ Bác sĩ</h5>
                <h2>{{ dashboardData.totalDoctors }}</h2>
                <small>Tổng số bác sĩ</small>
              </div>
              <div class="align-self-center">
                <i class="fas fa-user-md fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h5 class="card-title">📋 Hồ sơ</h5>
                <h2>{{ dashboardData.totalRecords }}</h2>
                <small>Hồ sơ trong tháng</small>
              </div>
              <div class="align-self-center">
                <i class="fas fa-clipboard fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h5 class="card-title">💰 Doanh thu</h5>
                <h2>{{ formatCurrency(dashboardData.revenue) }}</h2>
                <small>Tháng này</small>
              </div>
              <div class="align-self-center">
                <i class="fas fa-dollar-sign fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bộ lọc báo cáo -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">🔍 Bộ lọc báo cáo</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Loại báo cáo</label>
            <select v-model="selectedReportType" class="form-select" @change="onReportTypeChange">
              <option value="">-- Chọn loại báo cáo --</option>
              <option value="patient_stats">Thống kê bệnh nhân</option>
              <option value="doctor_records">Thống kê hồ sơ theo Bác sĩ</option>
              <option value="disease_stats">Thống kê loại bệnh</option>
              <option value="revenue_stats">Thống kê doanh thu</option>
              <option value="appointment_stats">Thống kê lịch hẹn</option>
              <option value="medication_stats">Thống kê thuốc</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label">Từ ngày</label>
            <input
              v-model="filters.startDate"
              type="date"
              class="form-control"
              @change="onStartDateChange"
            />
          </div>

          <div class="col-md-3">
            <label class="form-label">Đến ngày</label>
            <input
              v-model="filters.endDate"
              type="date"
              class="form-control"
              :min="filters.startDate"
              @change="onEndDateChange"
            />
          </div>

          <div class="col-md-2" v-if="selectedReportType === 'revenue_stats'">
            <div class="form-check mt-4 pt-2">
              <input
                id="senior-filter"
                v-model="onlySeniorPatients"
                class="form-check-input"
                type="checkbox"
              >
              <label class="form-check-label" for="senior-filter">
                Chỉ tính bệnh nhân ≥ 40 tuổi
              </label>
            </div>
          </div>

          <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100" @click="generateReport" :disabled="!selectedReportType || loading">
              {{ loading ? '🔄 Đang tải...' : '📊 Tạo báo cáo' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Advanced revenue summary -->
    <div
      class="card mb-4 border-success"
      v-if="selectedReportType === 'revenue_stats' && revenueSummary"
    >
      <div class="card-body">
        <div class="row text-center">
          <div class="col-md-3 mb-3 mb-md-0">
            <div class="text-muted text-uppercase small">Tổng doanh thu</div>
            <div class="h4 mb-0 text-success">{{ formatCurrency(revenueSummary.total_revenue) }}</div>
          </div>
          <div class="col-md-3 mb-3 mb-md-0">
            <div class="text-muted text-uppercase small">Số hóa đơn hợp lệ</div>
            <div class="h4 mb-0">{{ revenueSummary.invoice_count }}</div>
          </div>
          <div class="col-md-3 mb-3 mb-md-0">
            <div class="text-muted text-uppercase small">Bệnh nhân đáp ứng</div>
            <div class="h4 mb-0">{{ revenueSummary.patient_count }}</div>
          </div>
          <div class="col-md-3">
            <div class="text-muted text-uppercase small">Điều kiện lọc</div>
            <div class="fw-semibold">
              {{ formatDate(revenueSummary.start_date) }} → {{ formatDate(revenueSummary.end_date) }}
            </div>
            <small class="text-muted">
              {{ revenueSummary.min_age > 0 ? `Tuổi bệnh nhân ≥ ${revenueSummary.min_age}` : 'Không giới hạn độ tuổi' }}
            </small>
          </div>
        </div>
        <div
          v-if="revenueTrend"
          class="row text-center mt-4 g-3 border-top pt-3"
        >
          <div class="col-md-4">
            <div class="text-muted text-uppercase small">Xu hướng</div>
            <div class="h5 mb-0">
              {{ revenueTrend.direction === 'up' ? '📈 Tăng' : revenueTrend.direction === 'down' ? '📉 Giảm' : '➖ Ổn định' }}
            </div>
          </div>
          <div class="col-md-4">
            <div class="text-muted text-uppercase small">Tháng cao nhất</div>
            <div class="h6 mb-0" v-if="revenueTrend.highest_month">
              {{ revenueTrend.highest_month.label }}
              <small class="d-block text-success">{{ formatCurrency(revenueTrend.highest_month.revenue) }}</small>
            </div>
            <div v-else class="text-muted">---</div>
          </div>
          <div class="col-md-4">
            <div class="text-muted text-uppercase small">Tháng thấp nhất</div>
            <div class="h6 mb-0" v-if="revenueTrend.lowest_month">
              {{ revenueTrend.lowest_month.label }}
              <small class="d-block text-danger">{{ formatCurrency(revenueTrend.lowest_month.revenue) }}</small>
            </div>
            <div v-else class="text-muted">---</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Kết quả báo cáo -->
    <div class="card" v-if="reportData && reportData.length > 0">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">📈 {{ getReportTitle() }}</h5>
        <div class="d-flex gap-2">
          <!-- Ô tìm kiếm -->
          <div class="input-group" style="width: 300px;">
            <span class="input-group-text">🔍</span>
            <input
              v-model="searchTerm"
              type="text"
              class="form-control form-control-sm"
              placeholder="Tìm kiếm trong báo cáo..."
              @input="onSearchChange"
            />
          </div>
          <button class="btn btn-sm btn-outline-primary" @click="toggleChart">
            {{ showChart ? '📋 Xem bảng' : '📊 Xem biểu đồ' }}
          </button>
        </div>
      </div>
      <div class="card-body">
        <!-- Biểu đồ -->
        <div v-if="showChart" class="mb-4">
          <div v-if="!chartSections.length" class="text-center text-muted py-5 border rounded">
            <i class="fas fa-chart-pie fa-2x mb-2"></i>
            <p class="mb-0">Không có dữ liệu để vẽ biểu đồ</p>
          </div>
          <div v-else class="row g-4">
            <div
              class="col-12 col-lg-6"
              v-for="(chart, index) in chartSections"
              :key="chart.id || index"
            >
              <div class="border rounded p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="mb-0">{{ chart.title }}</h6>
                  <small class="text-muted" v-if="chart.legend">{{ chart.legend }}</small>
                </div>
                <canvas :ref="`chart-${index}`" width="480" height="320"></canvas>
                <div v-if="chart.meta" class="small text-muted mt-2">{{ chart.meta }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Bảng dữ liệu -->
        <div v-if="!showChart" class="table-responsive">
          <!-- Hiển thị số kết quả tìm kiếm -->
          <div class="mb-2 text-muted" v-if="searchTerm">
            <small>Hiển thị {{ filteredReportData.length }} / {{ reportData.length }} kết quả cho "{{ searchTerm }}"</small>
          </div>

          <table class="table table-striped">
            <thead>
              <tr>
                <th v-for="col in tableColumns" :key="col.key">#{{ col.label }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, idx) in pagedReportData" :key="idx">
                <td v-for="col in tableColumns" :key="col.key">
                  {{ formatCellValue(row[col.key], col.type) }}
                </td>
              </tr>
            </tbody>
          </table>
          <!-- Phân trang -->
          <nav v-if="totalPages > 1" class="mt-3">
            <ul class="pagination justify-content-center">
              <li class="page-item" :class="{ disabled: currentPage === 1 }">
                <button class="page-link" @click="goToPage(currentPage - 1)">Trước</button>
              </li>
              <li class="page-item" v-for="page in totalPages" :key="page" :class="{ active: page === currentPage }">
                <button class="page-link" @click="goToPage(page)">{{ page }}</button>
              </li>
              <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                <button class="page-link" @click="goToPage(currentPage + 1)">Sau</button>
              </li>
            </ul>
          </nav>
        </div>

        <!-- Tóm tắt -->
        <div class="row mt-3">
          <div class="col-md-6">
            <p><strong>Tổng số bản ghi:</strong> {{ reportData.length }}</p>
            <!-- Hiển thị thông tin lọc ngày -->
            <div v-if="filters.startDate && filters.endDate" class="text-muted">
              <small>📅 Dữ liệu từ {{ formatDate(filters.startDate) }} đến {{ formatDate(filters.endDate) }}</small>
            </div>
          </div>
          <div class="col-md-6 text-end">
            <small class="text-muted">Cập nhật: {{ formatDateTime(new Date()) }}</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Không có dữ liệu -->
    <div class="card" v-else-if="reportGenerated && !loading">
      <div class="card-body text-center py-5">
        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Không có dữ liệu</h5>
        <p class="text-muted" v-if="filters.startDate && filters.endDate">
          📅 Không tìm thấy dữ liệu trong khoảng từ <strong>{{ formatDate(filters.startDate) }}</strong> đến <strong>{{ formatDate(filters.endDate) }}</strong>
        </p>
        <p class="text-muted" v-else>
          Không tìm thấy dữ liệu cho bộ lọc đã chọn. Vui lòng thử lại với khoảng thời gian khác.
        </p>
        <div class="mt-3">
          <button class="btn btn-outline-primary btn-sm" @click="clearDateFilter">
            🔄 Xóa bộ lọc ngày
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import PatientService from '@/api/patientService'
import DoctorService from '@/api/doctorService'
import MedicalRecordService from '@/api/medicalRecordService'
import ReportService from '@/api/reportService'
import MedicationService from '@/api/medicationService'
import TreatmentService from '@/api/treatmentService'
import InvoiceService from '@/api/invoiceService'

export default {
  name: 'ReportsView',
  data () {
    return {
      userRole: 'admin', // Lấy từ store hoặc auth
      loading: false,
      reportGenerated: false,
      showChart: false,
      selectedReportType: '',
      searchTerm: '', // Thêm tìm kiếm
      filters: {
        startDate: this.getDateString(-30), // 30 ngày trước
        endDate: this.getDateString(0) // Hôm nay
      },
      dashboardData: {
        totalPatients: 0,
        totalDoctors: 0,
        totalRecords: 0,
        revenue: 0
      },
      reportData: [],
      revenueSummary: null,
      advancedRevenueMinAge: 40,
      onlySeniorPatients: true,
      tableColumns: [],
      currentPage: 1,
      pageSize: 10,
      chartSections: [],
      revenueTrend: null
    }
  },
  computed: {
    // Filtered report data based on search term
    filteredReportData () {
      if (!this.searchTerm || !this.reportData.length) {
        return this.reportData
      }

      const searchLower = this.searchTerm.toLowerCase()
      return this.reportData.filter(row => {
        return this.tableColumns.some(col => {
          const value = row[col.key]
          if (value === null || value === undefined) return false
          return String(value).toLowerCase().includes(searchLower)
        })
      })
    },
    pagedReportData () {
      const start = (this.currentPage - 1) * this.pageSize
      const end = start + this.pageSize
      return this.filteredReportData.slice(start, end)
    },
    totalPages () {
      return Math.ceil(this.filteredReportData.length / this.pageSize) || 1
    }
  },
  created () {
    this.loadDashboard()
  },
  watch: {
    onlySeniorPatients (val) {
      this.advancedRevenueMinAge = val ? 40 : 0
      if (this.selectedReportType === 'revenue_stats' && this.reportGenerated) {
        this.generateReport()
      }
    },
    showChart (val) {
      if (val) {
        this.$nextTick(() => this.renderCharts())
      }
    },
    chartSections: {
      deep: true,
      handler () {
        if (this.showChart) {
          this.$nextTick(() => this.renderCharts())
        }
      }
    }
  },
  methods: {
    // Utility functions
    getDateString (daysFromNow) {
      const date = new Date()
      date.setDate(date.getDate() + daysFromNow)
      return date.toISOString().split('T')[0]
    },

    formatDateTime (date) {
      return new Date(date).toLocaleString('vi-VN')
    },

    formatCurrency (amount) {
      return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
      }).format(amount || 0)
    },

    formatCellValue (value, type) {
      if (type === 'currency') return this.formatCurrency(value)
      if (type === 'date') return this.formatDateTime(value)
      if (type === 'number') return (value || 0).toLocaleString('vi-VN')
      return value || '-'
    },

    getReportTitle () {
      const titles = {
        patient_stats: 'Thống kê bệnh nhân',
        doctor_records: 'Thống kê hồ sơ theo Bác sĩ',
        disease_stats: 'Thống kê loại bệnh',
        revenue_stats: 'Thống kê doanh thu',
        appointment_stats: 'Thống kê lịch hẹn',
        medication_stats: 'Thống kê thuốc'
      }
      return titles[this.selectedReportType] || 'Báo cáo'
    },

    // Dashboard functions
    async loadDashboard () {
      // Temporarily remove admin check for testing
      // if (this.userRole !== 'admin') return

      this.loading = true
      try {
        // Thử gọi API dashboard trước
        try {
          const dashboardStats = await ReportService.getDashboardStats()
          if (dashboardStats) {
            this.dashboardData = {
              totalPatients: dashboardStats.total_patients || 0,
              totalDoctors: dashboardStats.total_doctors || 0,
              totalRecords: dashboardStats.total_records || 0,
              revenue: dashboardStats.revenue || 0
            }
            return
          }
        } catch (e) {
          console.warn('Dashboard API failed, using fallback method:', e.message)
        }

        // Fallback: Lấy dữ liệu từ các service khác
        const [patients, doctors, records, invoicesRes] = await Promise.all([
          PatientService.list({ limit: 10000 }).catch(() => ({ rows: [] })),
          DoctorService.list({ limit: 10000 }).catch(() => ({ rows: [] })),
          MedicalRecordService.list({ limit: 10000 }).catch(() => ({ rows: [] })),
          InvoiceService.list({ limit: 10000 }).catch(() => ({ rows: [] }))
        ])

        // Tính toán doanh thu từ hóa đơn tháng này
        const invoices = invoicesRes?.rows || invoicesRes?.data || []
        const now = new Date()
        const thisMonth = now.getMonth()
        const thisYear = now.getFullYear()
        let revenue = 0
        invoices.forEach(item => {
          const invoice = item.doc || item
          const date = new Date(invoice.created_at || invoice.invoice_date)
          if (date.getMonth() === thisMonth && date.getFullYear() === thisYear) {
            let amount = 0
            if (invoice.payment_info) {
              if (typeof invoice.payment_info === 'string') {
                try {
                  invoice.payment_info = JSON.parse(invoice.payment_info)
                } catch (e) {}
              }
              amount = Number(invoice.payment_info.total_amount) || Number(invoice.payment_info.patient_payment) || 0
            }
            revenue += amount
          }
        })

        this.dashboardData = {
          totalPatients: this.extractCount(patients),
          totalDoctors: this.extractCount(doctors),
          totalRecords: this.extractCount(records),
          revenue
        }

        console.log('📊 Dashboard using REAL data:', this.dashboardData)
        console.log('Raw API responses:', { patients, doctors, records, invoices })
      } catch (e) {
        console.error('Load dashboard failed:', e)
      } finally {
        this.loading = false
      }
    },

    extractCount (response) {
      if (response?.rows) return response.rows.length
      if (response?.total_rows) return response.total_rows
      if (Array.isArray(response)) return response.length
      return 0
    },

    calculateRevenue (records) {
      // Giả lập tính doanh thu từ records trong tháng này
      const thisMonth = new Date().getMonth()
      const thisYear = new Date().getFullYear()

      let revenue = 0
      const recordList = records?.rows || records || []

      recordList.forEach(record => {
        const doc = record.doc || record
        if (doc.created_at) {
          const createdDate = new Date(doc.created_at)
          if (createdDate.getMonth() === thisMonth && createdDate.getFullYear() === thisYear) {
            // Lấy số tiền thực tế từ payment_info
            let amount = 0
            if (doc.payment_info) {
              if (typeof doc.payment_info === 'string') {
                try {
                  doc.payment_info = JSON.parse(doc.payment_info)
                } catch (e) {}
              }
              amount = Number(doc.payment_info.total_amount) || Number(doc.payment_info.patient_payment) || 0
            }
            revenue += amount
          }
        }
      })

      return revenue
    },

    // Report generation
    onReportTypeChange () {
      this.reportData = []
      this.reportGenerated = false
      this.showChart = false
      this.revenueSummary = null
      this.setupTableColumns()
    },

    setupTableColumns () {
      const columnMaps = {
        patient_stats: [
          { key: 'name', label: 'Tên bệnh nhân', type: 'text' },
          { key: 'age', label: 'Tuổi', type: 'number' },
          { key: 'gender', label: 'Giới tính', type: 'text' },
          { key: 'phone', label: 'Số điện thoại', type: 'text' },
          { key: 'created_at', label: 'Ngày tạo', type: 'date' }
        ],
        doctor_records: [
          { key: 'doctor_name', label: 'Tên bác sĩ', type: 'text' },
          { key: 'specialty', label: 'Chuyên khoa', type: 'text' },
          { key: 'record_count', label: 'Số hồ sơ', type: 'number' },
          { key: 'patient_count', label: 'Số bệnh nhân', type: 'number' }
        ],
        disease_stats: [
          { key: 'disease_name', label: 'Tên bệnh', type: 'text' },
          { key: 'patient_count', label: 'Số bệnh nhân', type: 'number' },
          { key: 'percentage', label: 'Tỷ lệ %', type: 'number' }
        ],
        revenue_stats: [
          { key: 'invoice_number', label: 'Mã hóa đơn', type: 'text' },
          { key: 'patient_name', label: 'Bệnh nhân', type: 'text' },
          { key: 'invoice_date', label: 'Ngày hóa đơn', type: 'date' },
          { key: 'total_amount', label: 'Doanh thu', type: 'currency' },
          { key: 'patient_age', label: 'Tuổi bệnh nhân', type: 'number' },
          { key: 'payment_status', label: 'Trạng thái', type: 'text' }
        ],
        appointment_stats: [
          { key: 'date', label: 'Ngày', type: 'date' },
          { key: 'total_appointments', label: 'Tổng lịch hẹn', type: 'number' },
          { key: 'completed', label: 'Hoàn thành', type: 'number' },
          { key: 'cancelled', label: 'Hủy bỏ', type: 'number' }
        ],
        medication_stats: [
          { key: 'medication_name', label: 'Tên thuốc', type: 'text' },
          { key: 'usage_count', label: 'Số lần sử dụng', type: 'number' },
          { key: 'total_quantity', label: 'Tổng số lượng', type: 'number' }
        ]
      }

      this.tableColumns = columnMaps[this.selectedReportType] || []
    },

    async generateReport () {
      if (!this.selectedReportType) return

      this.loading = true
      this.reportGenerated = false
      this.chartSections = []
      this.revenueTrend = null
      if (this.selectedReportType !== 'revenue_stats') {
        this.revenueSummary = null
      }
      try {
        // Gọi API lấy dữ liệu báo cáo
        const data = await this.fetchReportData()
        this.reportData = data
        if (!this.chartSections.length) {
          this.chartSections = this.buildDefaultChartFromTable()
        }
        this.reportGenerated = true
      } catch (e) {
        console.error('Generate report failed:', e)
        this.reportData = []
        this.reportGenerated = true
      } finally {
        this.loading = false
      }
    },

    async fetchReportData () {
      try {
        console.log('🔄 Fetching REAL data for:', this.selectedReportType)
        console.log('📅 Date filters:', this.filters)

        // Thay vì gọi API báo cáo chưa có, lấy data từ APIs hiện có
        switch (this.selectedReportType) {
          case 'patient_stats':
            return await this.getPatientStatsFromAPI()
          case 'doctor_records':
            return await this.getDoctorRecordsFromAPI()
          case 'disease_stats':
            return await this.getDiseaseStatsFromAPI()
          case 'revenue_stats':
            return await this.getRevenueStatsFromAPI()
          case 'appointment_stats':
            return await this.getAppointmentStatsFromAPI()
          case 'medication_stats':
            return await this.getMedicationStatsFromAPI()
          default:
            throw new Error('Unknown report type')
        }
      } catch (e) {
        console.warn('📊 Real data fetch failed, using mock data:', e.message)
        return this.getMockData()
      }
    },

    // Lấy data thật từ Patient API với bộ lọc ngày
    async getPatientStatsFromAPI () {
      const response = await PatientService.list({ limit: 10000 })
      const patients = response?.rows || response?.data || []

      return patients
        .map(item => {
          const patient = item.doc || item
          const createdDate = patient.created_at || new Date().toISOString()
          return {
            name: patient.personal_info?.full_name || patient.name || 'N/A',
            age: this.calculateAge(patient.personal_info?.birth_date) || 0,
            gender: patient.personal_info?.gender || 'N/A',
            phone: patient.personal_info?.phone || patient.contact_info?.phone || 'N/A',
            created_at: createdDate
          }
        })
        .filter(patient => this.isWithinDateRange(patient.created_at))
    },

    // Lấy data thật từ Doctor API
    async getDoctorRecordsFromAPI () {
      // Lấy danh sách bác sĩ
      const doctorResponse = await DoctorService.list({ limit: 10000 })
      let doctors = doctorResponse?.rows || doctorResponse?.data || []

      // Lọc bác sĩ theo ngày tạo
      doctors = doctors.filter(item => {
        const doc = item.doc || item
        return this.isWithinDateRange(doc.created_at || doc.date_created || doc.createdAt || doc.date)
      })

      // Lấy danh sách hồ sơ bệnh án
      const recordResponse = await MedicalRecordService.list({ limit: 10000 })
      const records = recordResponse?.rows || recordResponse?.data || []

      // Lọc hồ sơ theo ngày
      const filteredRecords = records.filter(item => {
        const record = item.doc || item
        // Lọc theo ngày tạo hồ sơ
        return this.isWithinDateRange(record.created_at || record.date_created || record.createdAt || record.date)
      })

      // Gom hồ sơ theo bác sĩ
      const doctorStats = {}
      filteredRecords.forEach(item => {
        const record = item.doc || item
        // Tìm id bác sĩ
        const doctorId = record.doctor_id || record.doctor || record.doctorId || record.bacsi_id || 'unknown'
        if (!doctorStats[doctorId]) {
          doctorStats[doctorId] = {
            record_count: 0,
            patient_ids: new Set()
          }
        }
        doctorStats[doctorId].record_count++
        // Thêm bệnh nhân vào set
        if (record.patient_id || record.patient || record.patientId) {
          doctorStats[doctorId].patient_ids.add(record.patient_id || record.patient || record.patientId)
        }
      })

      // Luôn hiển thị tất cả bác sĩ đã lọc theo ngày tạo, số hồ sơ và số bệnh nhân dựa vào ngày lọc
      return doctors.map(item => {
        const doc = item.doc || item
        const doctorId = doc._id || doc.id || doc.doctor_id
        const stats = doctorStats[doctorId] || { record_count: 0, patient_ids: new Set() }
        return {
          doctor_name: doc.personal_info?.full_name || doc.name || doctorId || 'N/A',
          specialty: doc.professional_info?.specialty || doc.specialty || 'N/A',
          record_count: stats.record_count,
          patient_count: stats.patient_ids.size
        }
      })
    },

    // Lấy data thật từ Medical Records với bộ lọc ngày
    async getDiseaseStatsFromAPI () {
      const response = await MedicalRecordService.list({ limit: 10000 })
      const records = response?.rows || response?.data || []

      // Lọc theo ngày tháng
      const filteredRecords = records.filter(item => {
        const record = item.doc || item
        return this.isWithinDateRange(record.created_at || record.date_created || record.createdAt || record.date)
      })

      const diseaseCount = {}
      filteredRecords.forEach(item => {
        const record = item.doc || item
        let diagnosis = record.diagnosis
        // Nếu diagnosis là object, lấy primary hoặc name hoặc stringify
        if (diagnosis && typeof diagnosis === 'object') {
          diagnosis = diagnosis.primary || diagnosis.name || JSON.stringify(diagnosis)
        }
        if (!diagnosis || typeof diagnosis !== 'string') diagnosis = 'Khác'
        diseaseCount[diagnosis] = (diseaseCount[diagnosis] || 0) + 1
      })

      const total = Object.values(diseaseCount).reduce((a, b) => a + b, 0)

      return Object.entries(diseaseCount).map(([disease, count]) => ({
        disease_name: disease,
        patient_count: count,
        percentage: total > 0 ? Math.round((count / total) * 100) : 0
      }))
    },

    async getRevenueStatsFromAPI () {
      const params = {
        start_date: this.filters.startDate,
        end_date: this.filters.endDate,
        min_age: this.advancedRevenueMinAge
      }

      const revenueStatsPromise = ReportService.getRevenueStats(params)
        .then(stats => this.applyRevenueChartData(stats))
        .catch(err => {
          console.warn('Revenue stats API failed', err?.message || err)
          this.chartSections = []
        })

      try {
        const summary = await ReportService.getAdvancedRevenueStats(params)
        this.revenueSummary = summary
        await revenueStatsPromise
        return (summary?.invoices || []).map(item => ({
          invoice_number: item.invoice_number || item.invoice_id,
          patient_name: item.patient_name,
          invoice_date: item.invoice_date,
          total_amount: item.total_amount,
          patient_age: item.patient_age,
          payment_status: item.payment_status || '-'
        }))
      } catch (e) {
        console.warn('Advanced revenue API failed, falling back to local calculation', e)
        const fallback = await this.buildRevenueRowsFromInvoices()
        this.revenueSummary = fallback.summary
        await revenueStatsPromise
        return fallback.rows
      }
    },

    async buildRevenueRowsFromInvoices () {
      const [invoiceResponse, patientResponse] = await Promise.all([
        InvoiceService.list({ limit: 10000 }).catch(() => ({ rows: [] })),
        PatientService.list({ limit: 10000 }).catch(() => ({ rows: [] }))
      ])

      const invoices = invoiceResponse?.rows || invoiceResponse?.data || []
      const patients = patientResponse?.rows || patientResponse?.data || []
      const patientMap = {}

      patients.forEach(item => {
        const doc = item.doc || item
        if (doc && doc._id) {
          patientMap[doc._id] = doc
        }
      })

      const minAge = this.advancedRevenueMinAge
      const rows = []
      let totalRevenue = 0
      const patientSet = new Set()

      invoices.forEach(item => {
        const invoice = item.doc || item
        const invoiceDate = invoice.invoice_info?.invoice_date || invoice.created_at
        if (!this.isWithinDateRange(invoiceDate)) return

        const patientId = invoice.patient_id
        if (!patientId || !patientMap[patientId]) return

        const patientDoc = patientMap[patientId]
        const birthDate = patientDoc.personal_info?.birth_date || patientDoc.birth_date
        if (!birthDate) return

        const age = this.calculateAge(birthDate)
        if (age < minAge) return

        const amount = this.extractInvoiceAmount(invoice)
        if (!amount) return

        rows.push({
          invoice_number: invoice.invoice_info?.invoice_number || invoice.invoice_number || invoice._id,
          patient_name: patientDoc.personal_info?.full_name || patientDoc.full_name || patientDoc.name || 'Không rõ',
          invoice_date: invoiceDate,
          total_amount: amount,
          patient_age: age,
          payment_status: invoice.payment_status || '-'
        })

        totalRevenue += amount
        patientSet.add(patientId)
      })

      rows.sort((a, b) => {
        return new Date(b.invoice_date || 0) - new Date(a.invoice_date || 0)
      })

      return {
        rows,
        summary: {
          start_date: this.filters.startDate,
          end_date: this.filters.endDate,
          min_age: minAge,
          invoice_count: rows.length,
          patient_count: patientSet.size,
          total_revenue: totalRevenue,
          currency: 'VND'
        }
      }
    },

    extractInvoiceAmount (invoice) {
      let paymentInfo = invoice.payment_info
      if (typeof paymentInfo === 'string') {
        try {
          paymentInfo = JSON.parse(paymentInfo)
        } catch (err) {
          paymentInfo = null
        }
      }

      let amount = 0
      if (paymentInfo && typeof paymentInfo === 'object') {
        amount = paymentInfo.total_amount ?? paymentInfo.patient_payment ?? paymentInfo.subtotal ?? 0
      } else {
        amount = invoice.total_amount ?? invoice.amount ?? invoice.payment_amount ?? 0
      }

      if (typeof amount === 'string') {
        amount = amount.replace(/[^\d.-]/g, '')
      }

      return Number(amount) || 0
    },

    applyRevenueChartData (stats) {
      if (!stats) return
      const sections = []

      if (Array.isArray(stats.monthly) && stats.monthly.length) {
        sections.push({
          id: 'revenue-monthly',
          type: 'bar',
          title: 'Doanh thu theo tháng',
          labels: stats.monthly.map(item => item.label),
          values: stats.monthly.map(item => item.revenue || 0),
          meta: `Tổng doanh thu: ${this.formatCurrency(stats.total_revenue || 0)}`
        })
      }

      if (stats.age_distribution) {
        const older = stats.age_distribution.over_40 || {}
        const younger = stats.age_distribution.under_40 || {}
        sections.push({
          id: 'revenue-age',
          type: 'pie',
          title: 'Tỷ lệ tuổi bệnh nhân (theo hóa đơn)',
          labels: [older.label || '>= 40', younger.label || '< 40'],
          values: [older.count || 0, younger.count || 0],
          legend: 'Nguồn: hoá đơn trong khoảng thời gian đã chọn'
        })
      }

      this.chartSections = sections
      this.revenueTrend = stats.trend || null
    },

    async getAppointmentStatsFromAPI () {
      const params = {
        start_date: this.filters.startDate,
        end_date: this.filters.endDate
      }

      try {
        const stats = await ReportService.getAppointmentStats(params)
        this.chartSections = this.buildAppointmentTypeChart(stats?.type_distribution || [])
        return (stats?.daily || []).map(item => ({
          date: item.date,
          total_appointments: item.total_appointments,
          completed: item.completed,
          cancelled: item.cancelled,
          scheduled: item.scheduled
        }))
      } catch (e) {
        console.warn('Appointment stats API failed, using fallback data', e?.message || e)
        const fallback = this.buildAppointmentFallback()
        this.chartSections = this.buildAppointmentTypeChart(fallback.type_distribution)
        return fallback.daily
      }
    },

    buildAppointmentTypeChart (distribution = []) {
      if (!distribution.length) return this.buildDefaultChartFromTable()

      return [{
        id: 'appointment-types',
        type: 'pie',
        title: 'Tỷ lệ loại lịch hẹn',
        labels: distribution.map(item => this.translateAppointmentType(item.type)),
        values: distribution.map(item => item.count),
        legend: 'Tính theo % số lượng lịch hẹn'
      }]
    },

    buildAppointmentFallback () {
      const startDate = new Date(this.filters.startDate)
      const endDate = new Date(this.filters.endDate)
      const result = []
      const typeDistribution = {}

      const diffTime = Math.abs(endDate - startDate)
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

      const possibleTypes = ['consultation', 'follow_up', 'checkup', 'emergency', 'procedure']

      for (let i = 0; i <= diffDays; i++) {
        const currentDate = new Date(startDate)
        currentDate.setDate(startDate.getDate() + i)
        const dateStr = currentDate.toISOString().split('T')[0]

        const total = Math.floor(Math.random() * 20) + 5
        const completed = Math.floor(total * 0.7)
        const cancelled = Math.floor(total * 0.15)
        const scheduled = total - completed - cancelled

        result.push({
          date: dateStr,
          total_appointments: total,
          completed,
          cancelled,
          scheduled
        })

        const randomType = possibleTypes[Math.floor(Math.random() * possibleTypes.length)]
        typeDistribution[randomType] = (typeDistribution[randomType] || 0) + 1
      }

      const distributionArray = Object.entries(typeDistribution).map(([type, count]) => ({
        type,
        count,
        percentage: 0
      }))

      return { daily: result, type_distribution: distributionArray }
    },

    translateAppointmentType (type) {
      const map = {
        consultation: 'Tư vấn',
        follow_up: 'Tái khám',
        checkup: 'Khám sức khỏe',
        emergency: 'Cấp cứu',
        procedure: 'Thủ thuật'
      }
      return map[type] || type || 'Khác'
    },

    async getMedicationStatsFromAPI () {
      console.log('🔄 Starting medication stats collection...')

      // First, try to get data from ALL possible sources
      let medicationData = []
      let treatmentData = []

      // Try Medication API
      try {
        console.log('📊 Trying Medication API...')
        const medicationResponse = await MedicationService.list({ limit: 10000 }) // Tăng limit để lấy hết thuốc
        medicationData = medicationResponse?.rows || medicationResponse?.data || medicationResponse || []
        console.log('✅ Medication API success:', medicationResponse)
        console.log('✅ Processed medication data:', medicationData)

        // Log sample structure if we have data
        if (medicationData.length > 0) {
          console.log('🔍 Sample medication structure:', medicationData[0])
          console.log('🔍 Sample medication doc:', medicationData[0]?.doc || medicationData[0])
        }
      } catch (e) {
        console.warn('❌ Medication API failed:', e.message)
      }

      // Try Treatment API
      try {
        console.log('📊 Trying Treatment API...')
        const treatmentResponse = await TreatmentService.list({ limit: 10000 }) // Tăng limit để lấy hết treatment
        treatmentData = treatmentResponse?.rows || treatmentResponse?.data || treatmentResponse || []
        console.log('✅ Treatment API success:', treatmentResponse)
        console.log('✅ Processed treatment data:', treatmentData)

        // Log sample structure if we have data
        if (treatmentData.length > 0) {
          console.log('🔍 Sample treatment structure:', treatmentData[0])
          console.log('🔍 Sample treatment doc:', treatmentData[0]?.doc || treatmentData[0])
        }
      } catch (e) {
        console.warn('❌ Treatment API failed:', e.message)
      }

      console.log('📊 Total medication records:', medicationData.length)
      console.log('📊 Total treatment records:', treatmentData.length)

      const medicationCount = {} // Process medication data
      if (medicationData.length > 0) {
        console.log('📋 Processing direct medication data...')
        medicationData.forEach((item, index) => {
          const medication = item.doc || item
          console.log(`📋 Medication ${index + 1}:`, medication)
          console.log('📋 All keys in medication:', Object.keys(medication))

          // ✅ Kiểm tra ngày tạo thuốc có nằm trong khoảng lọc không
          const createdDate = medication.created_at ||
                             medication.date_created ||
                             medication.createdAt ||
                             medication.date ||
                             medication.created_date ||
                             medication.timestamp

          console.log('📅 Medication created date:', createdDate)

          // Lọc theo ngày nếu có filter
          if (!this.isWithinDateRange(createdDate)) {
            console.log('❌ Medication filtered out - not in date range')
            return // Skip thuốc này nếu không trong khoảng ngày
          }

          // Try multiple possible field names for medication name
          const rawName =
            medication.name || // ✅ Ưu tiên field name
            medication.medication_name ||
            medication.drug_name ||
            medication.medicine_name ||
            medication.title ||
            medication.brand_name ||
            medication.generic_name ||
            medication.product_name ||
            medication.substance ||
            medication.active_ingredient ||
            medication._id ||
            `Unknown Medication ${index + 1}`

          // Clean and format medication name
          const name = this.cleanMedicationName(rawName)

          console.log(`📋 Raw name: "${rawName}" -> Clean name: "${name}"`)

          if (!medicationCount[name]) {
            medicationCount[name] = { usage_count: 0, total_quantity: 0 }
          }

          medicationCount[name].usage_count++
          let quantity = 0
          if (isFinite(medication.quantity)) quantity = Number(medication.quantity)
          else if (isFinite(medication.dosage)) quantity = Number(medication.dosage)
          else if (isFinite(medication.amount)) quantity = Number(medication.amount)
          else if (isFinite(medication.stock)) quantity = Number(medication.stock)
          else if (isFinite(medication.dose)) quantity = Number(medication.dose)
          else if (isFinite(medication.units)) quantity = Number(medication.units)
          // Lấy từ inventory.current_stock nếu có
          else if (medication.inventory && isFinite(medication.inventory.current_stock)) quantity = Number(medication.inventory.current_stock)
          medicationCount[name].total_quantity += quantity
        })
      }

      // Process treatment data for additional medications
      if (treatmentData.length > 0) {
        console.log('📋 Processing treatment medication data...')
        treatmentData.forEach((item, index) => {
          const treatment = item.doc || item
          console.log(`📋 Treatment ${index + 1}:`, treatment)
          console.log('📋 All keys in treatment:', Object.keys(treatment))

          // ✅ Kiểm tra ngày tạo treatment có nằm trong khoảng lọc không
          const treatmentCreatedDate = treatment.created_at ||
                                      treatment.date_created ||
                                      treatment.createdAt ||
                                      treatment.date ||
                                      treatment.treatment_date ||
                                      treatment.created_date ||
                                      treatment.timestamp

          console.log('📅 Treatment created date:', treatmentCreatedDate)

          // Lọc theo ngày nếu có filter
          if (!this.isWithinDateRange(treatmentCreatedDate)) {
            console.log('❌ Treatment filtered out - not in date range')
            return // Skip treatment này nếu không trong khoảng ngày
          }

          const medications =
            treatment.medications ||
            treatment.prescriptions ||
            treatment.drugs ||
            treatment.medicine_list ||
            treatment.prescription_details ||
            []

          console.log('📋 Treatment medications:', medications)

          if (Array.isArray(medications)) {
            medications.forEach((med, medIndex) => {
              console.log(`📋 Processing medication ${medIndex + 1} from treatment:`, med)
              console.log('📋 Medication keys:', Object.keys(med))

              const rawName =
                med.name || // ✅ Ưu tiên field name
                med.medication_name ||
                med.drug_name ||
                med.medicine_name ||
                med.brand_name ||
                med.generic_name ||
                med.active_ingredient ||
                med.substance ||
                med._id ||
                `Treatment Medication ${medIndex + 1}`

              // Clean and format medication name
              const name = this.cleanMedicationName(rawName)

              console.log(`📋 Treatment raw name: "${rawName}" -> Clean name: "${name}"`)

              if (!medicationCount[name]) {
                medicationCount[name] = { usage_count: 0, total_quantity: 0 }
              }
              medicationCount[name].usage_count++
              medicationCount[name].total_quantity += parseInt(
                (isFinite(med.quantity)
                  ? Number(med.quantity)
                  : isFinite(med.dosage)
                    ? Number(med.dosage)
                    : isFinite(med.dose)
                      ? Number(med.dose)
                      : isFinite(med.amount)
                        ? Number(med.amount)
                        : isFinite(med.units)
                          ? Number(med.units)
                          : 0)
              )
            })
          }
        })
      }

      console.log('📊 Final medication count:', medicationCount)

      // If no real data, check if it's because of date filter or no data at all
      if (Object.keys(medicationCount).length === 0) {
        if (this.filters.startDate && this.filters.endDate) {
          console.warn(`⚠️  No medication data found in date range: ${this.filters.startDate} to ${this.filters.endDate}`)
          return [] // Return empty array instead of sample data when filtering by date
        } else {
          console.warn('⚠️  No medication data found, returning sample data')
          return [
            { medication_name: 'Paracetamol', usage_count: 45, total_quantity: 450 },
            { medication_name: 'Amoxicillin', usage_count: 32, total_quantity: 320 },
            { medication_name: 'Ibuprofen', usage_count: 28, total_quantity: 280 },
            { medication_name: 'Aspirin', usage_count: 35, total_quantity: 350 },
            { medication_name: 'Vitamin C', usage_count: 25, total_quantity: 250 }
          ]
        }
      }

      const result = Object.entries(medicationCount)
        .sort(([, a], [, b]) => b.usage_count - a.usage_count)
        // .slice(0, 10) // ❌ Bỏ giới hạn 10 để hiển thị tất cả thuốc
        .map(([name, stats]) => ({
          medication_name: name,
          usage_count: stats.usage_count,
          total_quantity: stats.total_quantity
        }))

      console.log('📊 Final result:', result)
      return result
    },

    // Helper function để làm sạch tên thuốc
    cleanMedicationName (rawName) {
      if (!rawName || typeof rawName !== 'string') {
        return 'Unknown Medication'
      }

      let cleanName = rawName

      // Remove med_ prefix
      cleanName = cleanName.replace(/^med_/i, '')

      // Replace underscores with spaces
      cleanName = cleanName.replace(/_/g, ' ')

      // Remove dosage information (mg, mcg, g, ml, etc.)
      cleanName = cleanName.replace(/\s*\d+\s*(mg|mcg|g|ml|ug|kg|l|%)\s*/gi, '')

      // Remove any numbers at the end
      cleanName = cleanName.replace(/\s*\d+\s*$/, '')

      // Capitalize first letter of each word
      cleanName = cleanName.replace(/\b\w/g, l => l.toUpperCase())

      return cleanName.trim()
    },

    // Helper function to get week key
    getWeekKey (date) {
      const startOfMonth = new Date(date.getFullYear(), date.getMonth(), 1)
      const weekNumber = Math.ceil((date.getDate() + startOfMonth.getDay()) / 7)
      return `Tuần ${weekNumber}/${date.getMonth() + 1}`
    },

    // Helper function để kiểm tra ngày trong khoảng lọc
    isWithinDateRange (dateString) {
      if (!dateString || !this.filters.startDate || !this.filters.endDate) {
        return true // Nếu không có filter thì hiển thị tất cả
      }

      const checkDate = new Date(dateString)
      const startDate = new Date(this.filters.startDate)
      const endDate = new Date(this.filters.endDate)

      // Set time to start/end of day để so sánh chính xác
      startDate.setHours(0, 0, 0, 0)
      endDate.setHours(23, 59, 59, 999)
      checkDate.setHours(12, 0, 0, 0) // Giữa ngày để tránh lỗi timezone

      return checkDate >= startDate && checkDate <= endDate
    },

    // Helper function để tạo period key dựa trên khoảng thời gian
    getPeriodKey (date) {
      const startDate = new Date(this.filters.startDate)
      const endDate = new Date(this.filters.endDate)
      const diffTime = Math.abs(endDate - startDate)
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

      // Nếu khoảng thời gian <= 31 ngày: group theo tuần
      // Nếu > 31 ngày: group theo tháng
      if (diffDays <= 31) {
        return this.getWeekKey(date)
      } else {
        return `${date.getMonth() + 1}/${date.getFullYear()}`
      }
    },

    getMockData () {
      console.log('📊 Using MOCK data for:', this.selectedReportType)

      const sampleData = {
        patient_stats: [
          { name: 'Nguyễn Văn A', age: 35, gender: 'Nam', phone: '0123456789', created_at: '2025-11-01T10:00:00Z' },
          { name: 'Trần Thị B', age: 28, gender: 'Nữ', phone: '0987654321', created_at: '2025-11-02T14:30:00Z' },
          { name: 'Lê Văn C', age: 45, gender: 'Nam', phone: '0345678901', created_at: '2025-11-03T09:15:00Z' },
          { name: 'Phạm Thị D', age: 32, gender: 'Nữ', phone: '0567890123', created_at: '2025-11-04T16:45:00Z' }
        ],
        doctor_records: [
          { doctor_name: 'BS. Nguyễn Thanh', specialty: 'Tim mạch', record_count: 25, patient_count: 20 },
          { doctor_name: 'BS. Trần Hương', specialty: 'Nhi khoa', record_count: 18, patient_count: 15 },
          { doctor_name: 'BS. Lê Minh', specialty: 'Thần kinh', record_count: 22, patient_count: 18 },
          { doctor_name: 'BS. Phạm Lan', specialty: 'Da liễu', record_count: 15, patient_count: 12 }
        ],
        disease_stats: [
          { disease_name: 'Cao huyết áp', patient_count: 15, percentage: 30 },
          { disease_name: 'Tiểu đường', patient_count: 12, percentage: 24 },
          { disease_name: 'Viêm gan B', patient_count: 8, percentage: 16 },
          { disease_name: 'Dạ dày', patient_count: 10, percentage: 20 },
          { disease_name: 'Khác', patient_count: 5, percentage: 10 }
        ],
        revenue_stats: [
          { period: 'Tuần 1/11', revenue: 15000000, invoice_count: 30 },
          { period: 'Tuần 2/11', revenue: 18500000, invoice_count: 37 },
          { period: 'Tuần 3/11', revenue: 22000000, invoice_count: 44 },
          { period: 'Tuần 4/11', revenue: 17500000, invoice_count: 35 }
        ],
        appointment_stats: [
          { date: '2025-11-01', total_appointments: 25, completed: 20, cancelled: 5 },
          { date: '2025-11-02', total_appointments: 30, completed: 28, cancelled: 2 },
          { date: '2025-11-03', total_appointments: 18, completed: 15, cancelled: 3 },
          { date: '2025-11-04', total_appointments: 22, completed: 20, cancelled: 2 }
        ],
        medication_stats: [
          { medication_name: 'Paracetamol', usage_count: 45, total_quantity: 450 },
          { medication_name: 'Amoxicillin', usage_count: 32, total_quantity: 320 },
          { medication_name: 'Ibuprofen', usage_count: 28, total_quantity: 280 },
          { medication_name: 'Aspirin', usage_count: 35, total_quantity: 350 }
        ]
      }

      return sampleData[this.selectedReportType] || []
    },

    // Utility function để tính tuổi
    calculateAge (birthDate) {
      if (!birthDate) return null
      const today = new Date()
      const birth = new Date(birthDate)
      let age = today.getFullYear() - birth.getFullYear()
      const monthDiff = today.getMonth() - birth.getMonth()
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--
      }
      return age > 0 ? age : null
    },

    // Format date để hiển thị đẹp
    formatDate (dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
      })
    },

    toggleChart () {
      this.showChart = !this.showChart
      if (this.showChart) {
        this.$nextTick(() => {
          this.renderCharts()
        })
      }
    },

    renderCharts () {
      if (!this.showChart || !this.chartSections.length) return

      this.chartSections.forEach((chart, index) => {
        let canvas = this.$refs[`chart-${index}`]
        if (Array.isArray(canvas)) {
          canvas = canvas[0]
        }
        if (!canvas) return

        const ctx = canvas.getContext('2d')
        ctx.clearRect(0, 0, canvas.width, canvas.height)

        if (chart.type === 'pie') {
          this.drawPieChart(ctx, chart)
        } else {
          this.drawBarChart(ctx, chart)
        }
      })
    },

    prepareChartData () {
      if (!this.reportData.length) return { labels: [], values: [] }

      const firstNumericCol = this.tableColumns.find(col => col.type === 'number' || col.type === 'currency')
      const labelCol = this.tableColumns[0]

      if (!firstNumericCol || !labelCol) return { labels: [], values: [] }

      return {
        labels: this.reportData.map(row => row[labelCol.key]),
        values: this.reportData.map(row => row[firstNumericCol.key] || 0)
      }
    },

    buildDefaultChartFromTable () {
      const data = this.prepareChartData()
      if (!data.labels.length) return []

      const maxBars = 12
      return [{
        id: `${this.selectedReportType}-default`,
        type: 'bar',
        title: `Biểu đồ ${this.getReportTitle().toLowerCase()}`,
        labels: data.labels.slice(0, maxBars),
        values: data.values.slice(0, maxBars)
      }]
    },

    drawBarChart (ctx, chart) {
      const labels = chart.labels || []
      const values = chart.values || []
      if (!labels.length || !values.length) {
        ctx.fillStyle = '#6c757d'
        ctx.font = '16px Arial'
        ctx.textAlign = 'center'
        ctx.fillText('Không đủ dữ liệu để vẽ biểu đồ', ctx.canvas.width / 2, ctx.canvas.height / 2)
        return
      }

      const canvas = ctx.canvas
      const padding = 40
      // Tăng chiều rộng canvas nếu nhiều label hoặc ít label
      // Luôn đặt canvas rộng 1000px, cao 400px cho dễ nhìn
      canvas.width = 1000
      canvas.height = 400
      const chartWidth = canvas.width - padding * 2
      const chartHeight = canvas.height - padding * 2

      const maxValue = Math.max(...values) || 1
      const barWidth = chartWidth / labels.length * 0.6
      const barSpacing = chartWidth / labels.length * 0.4

      // Vẽ trục
      ctx.strokeStyle = '#333'
      ctx.lineWidth = 2
      ctx.beginPath()
      ctx.moveTo(padding, padding)
      ctx.lineTo(padding, canvas.height - padding)
      ctx.lineTo(canvas.width - padding, canvas.height - padding)
      ctx.stroke()

      // Vẽ bars
      values.forEach((value, index) => {
        const barHeight = (value / maxValue) * chartHeight
        const x = padding + index * (barWidth + barSpacing) + barSpacing / 2
        const y = canvas.height - padding - barHeight

        // Vẽ bar
        ctx.fillStyle = `hsl(${index * 60}, 70%, 50%)`
        ctx.fillRect(x, y, barWidth, barHeight)

        // Vẽ label thẳng ngang
        ctx.fillStyle = '#333'
        ctx.font = '13px Arial'
        ctx.textAlign = 'center'
        ctx.fillText(labels[index], x + barWidth / 2, canvas.height - padding + 15)

        // Vẽ giá trị
        ctx.fillStyle = '#333'
        ctx.font = 'bold 13px Arial'
        ctx.textAlign = 'center'
        ctx.fillText(value.toLocaleString(), x + barWidth / 2, y - 8)
      })
    },

    drawPieChart (ctx, chart) {
      const values = chart.values || []
      const labels = chart.labels || []
      const total = values.reduce((sum, val) => sum + val, 0)
      const canvas = ctx.canvas
      canvas.width = 480
      canvas.height = 320
      const centerX = canvas.width / 2
      const centerY = canvas.height / 2
      const radius = Math.min(centerX, centerY) - 20

      if (!total) {
        ctx.fillStyle = '#6c757d'
        ctx.font = '16px Arial'
        ctx.textAlign = 'center'
        ctx.fillText('Không có dữ liệu', centerX, centerY)
        return
      }

      let startAngle = -Math.PI / 2
      const colors = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#20c997', '#6f42c1']

      values.forEach((value, index) => {
        const sliceAngle = (value / total) * Math.PI * 2
        ctx.beginPath()
        ctx.moveTo(centerX, centerY)
        ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle)
        ctx.closePath()
        ctx.fillStyle = colors[index % colors.length]
        ctx.fill()

        const midAngle = startAngle + sliceAngle / 2
        const labelX = centerX + (radius - 30) * Math.cos(midAngle)
        const labelY = centerY + (radius - 30) * Math.sin(midAngle)
        const percent = Math.round((value / total) * 100)

        ctx.fillStyle = '#fff'
        ctx.font = 'bold 13px Arial'
        ctx.textAlign = 'center'
        ctx.fillText(`${percent}%`, labelX, labelY)

        startAngle += sliceAngle
      })

      ctx.font = '13px Arial'
      ctx.textAlign = 'left'
      labels.forEach((label, index) => {
        const y = 20 + index * 18
        ctx.fillStyle = colors[index % colors.length]
        ctx.fillRect(canvas.width - 150, y - 10, 12, 12)
        ctx.fillStyle = '#333'
        ctx.fillText(`${label} (${values[index]})`, canvas.width - 130, y)
      })
    },

    refreshDashboard () {
      this.loadDashboard()
    },

    // Validation cho date picker
    onStartDateChange () {
      // Nếu end date nhỏ hơn start date, tự động set end date = start date
      if (this.filters.endDate && this.filters.startDate > this.filters.endDate) {
        this.filters.endDate = this.filters.startDate
      }

      // Tự động tạo lại báo cáo nếu đã chọn loại báo cáo
      if (this.selectedReportType && this.reportGenerated) {
        this.generateReport()
      }
    },

    onEndDateChange () {
      // Đảm bảo end date không nhỏ hơn start date
      if (this.filters.startDate && this.filters.endDate < this.filters.startDate) {
        this.filters.endDate = this.filters.startDate
      }

      // Tự động tạo lại báo cáo nếu đã chọn loại báo cáo
      if (this.selectedReportType && this.reportGenerated) {
        this.generateReport()
      }
    },

    // Xử lý tìm kiếm
    onSearchChange () {
      // Tìm kiếm real-time, không cần làm gì thêm
      // filteredReportData computed sẽ tự động update
    },

    // Clear date filter
    clearDateFilter () {
      this.filters.startDate = this.getDateString(-30) // Reset về 30 ngày trước
      this.filters.endDate = this.getDateString(0) // Reset về hôm nay

      // Tự động tạo lại báo cáo
      if (this.selectedReportType) {
        this.generateReport()
      }
    },

    exportReport () {
      const dataToExport = this.searchTerm ? this.filteredReportData : this.reportData

      if (!dataToExport.length) {
        alert('Không có dữ liệu để xuất!')
        return
      }

      // Xuất CSV đơn giản
      const headers = this.tableColumns.map(col => col.label).join(',')
      const rows = dataToExport.map(row =>
        this.tableColumns.map(col => `"${row[col.key] || ''}"`).join(',')
      )

      const csv = [headers, ...rows].join('\n')
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
      const link = document.createElement('a')
      link.href = URL.createObjectURL(blob)
      const filename = this.searchTerm
        ? `bao-cao-${this.selectedReportType}-filtered-${Date.now()}.csv`
        : `bao-cao-${this.selectedReportType}-${Date.now()}.csv`
      link.download = filename
      link.click()
    },
    goToPage (page) {
      if (page < 1 || page > this.totalPages) return
      this.currentPage = page
    }
  }
}
</script>

<style scoped>
.card {
  border: none;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
}

.table th {
  background-color: #f8f9fa;
  font-weight: 600;
}

.btn {
  border-radius: 8px;
}

canvas {
  max-width: 100%;
  border: 1px solid #dee2e6;
  border-radius: 8px;
}

.text-muted {
  color: #6c757d !important;
}
</style>
