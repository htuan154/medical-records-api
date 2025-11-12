<template>
  <section class="doctors-management">
    <!-- Header Section -->
    <div class="header-section">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="bi bi-person-badge-fill"></i>
            Quản lý Bác sĩ
          </h1>
          <p class="page-subtitle">Quản lý thông tin bác sĩ và lịch làm việc</p>
        </div>
        <div class="header-actions">
          <button class="btn-action btn-back" @click="goHome" title="Quay lại Trang chủ">
            <i class="bi bi-arrow-left"></i>
            Trang chủ
          </button>
          <div class="stats-badge">
            <i class="bi bi-bar-chart-fill"></i>
            <span>Tổng: <strong>{{ total }}</strong></span>
          </div>
          <button class="btn-action btn-refresh" @click="reload" :disabled="loading" title="Tải lại">
            <i class="bi bi-arrow-clockwise"></i>
          </button>
          <button class="btn-action btn-primary" @click="openCreate" :disabled="loading">
            <i class="bi bi-plus-circle"></i>
            Thêm mới
          </button>
        </div>
      </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="search-section">
      <div class="search-container">
        <div class="search-input-group">
          <i class="bi bi-search search-icon"></i>
          <input
            v-model.trim="q"
            class="search-input"
            placeholder="Tìm theo tên / chuyên khoa / điện thoại / email..."
            @keyup.enter="search"
          />
          <button class="search-btn" @click="search" :disabled="loading">
            <i class="bi bi-search"></i>
            Tìm kiếm
          </button>
        </div>
      </div>
    </div>

    <!-- Content Section -->
    <div class="content-section">
      <div v-if="error" class="alert alert-error">
        <i class="bi bi-exclamation-triangle"></i>
        {{ error }}
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <span>Đang tải danh sách...</span>
      </div>

      <template v-else>
        <div class="table-container">
          <table class="doctors-table">
            <thead>
              <tr>
                <th class="col-number">#</th>
                <th class="col-name">Họ tên</th>
                <th class="col-specialty">Chuyên khoa</th>
                <th class="col-subspecialty">Phân chuyên khoa</th>
                <th class="col-experience">KN (năm)</th>
                <th class="col-phone">Điện thoại</th>
                <th class="col-email">Email</th>
                <th class="col-status">Trạng thái</th>
                <th class="col-actions">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(d, idx) in paginatedItems" :key="d._id || d.id || idx">
                <tr class="doctor-row" :class="{ 'expanded': isExpanded(d) }">
                  <td class="cell-number">
                    <span class="row-number">{{ idx + 1 + (page - 1) * pageSize }}</span>
                  </td>
                  <td class="cell-name">
                    <div class="doctor-name">
                      <strong>{{ d.personal_info?.full_name || '-' }}</strong>
                    </div>
                  </td>
                  <td class="cell-specialty">{{ d.professional_info?.specialty || '-' }}</td>
                  <td class="cell-subspecialty">{{ joinArr(d.professional_info?.sub_specialties) }}</td>
                  <td class="cell-experience">{{ d.professional_info?.experience_years ?? '-' }}</td>
                  <td class="cell-phone">{{ d.personal_info?.phone || '-' }}</td>
                  <td class="cell-email">{{ d.personal_info?.email || '-' }}</td>
                  <td class="cell-status">
                    <span class="status-badge" :class="d.status === 'active' ? 'status-active' : 'status-inactive'">
                      <i :class="d.status === 'active' ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill'"></i>
                      {{ d.status === 'active' ? 'Hoạt động' : 'Không hoạt động' }}
                    </span>
                  </td>
                  <td class="cell-actions">
                    <div class="action-buttons">
                      <button
                        class="action-btn view-btn"
                        @click="toggleRow(d)"
                        :title="isExpanded(d) ? 'Ẩn chi tiết' : 'Xem chi tiết'"
                      >
                        <i :class="isExpanded(d) ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                      </button>
                      <button
                        class="action-btn edit-btn"
                        @click="openEdit(d)"
                        title="Chỉnh sửa"
                      >
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button
                        class="action-btn delete-btn"
                        @click="remove(d)"
                        :disabled="loading"
                        title="Xóa bác sĩ"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>

                <!-- Row details -->
                <tr v-if="isExpanded(d)" class="detail-row">
                  <td colspan="9" class="detail-cell">
                    <div class="doctor-detail-card">
                      <div class="detail-header">
                        <h4 class="detail-title">
                          <i class="bi bi-info-circle"></i>
                          Chi tiết bác sĩ: {{ d.personal_info?.full_name }}
                        </h4>
                      </div>

                      <div class="detail-content">
                        <div class="detail-section">
                          <h5 class="section-title">
                            <i class="bi bi-info-square"></i>
                            Thông tin cá nhân
                          </h5>
                          <div class="info-grid">
                            <div class="info-item">
                              <label>Họ tên:</label>
                              <span class="info-value">{{ d.personal_info?.full_name || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Giới tính:</label>
                              <span class="info-value">{{ renderGender(d.personal_info?.gender) }}</span>
                            </div>
                            <div class="info-item">
                              <label>Ngày sinh:</label>
                              <span class="info-value">{{ fmtDate(d.personal_info?.birth_date) }}</span>
                            </div>
                            <div class="info-item">
                              <label>Điện thoại:</label>
                              <span class="info-value">{{ d.personal_info?.phone || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Email:</label>
                              <span class="info-value">{{ d.personal_info?.email || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Trạng thái:</label>
                              <span class="status-badge" :class="d.status === 'active' ? 'status-active' : 'status-inactive'">
                                <i :class="d.status === 'active' ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill'"></i>
                                {{ d.status === 'active' ? 'Hoạt động' : 'Không hoạt động' }}
                              </span>
                            </div>
                          </div>
                        </div>

                        <div class="detail-section">
                          <h5 class="section-title">
                            <i class="bi bi-briefcase"></i>
                            Nghề nghiệp
                          </h5>
                          <div class="info-grid">
                            <div class="info-item">
                              <label>Số giấy phép:</label>
                              <span class="info-value">{{ d.professional_info?.license_number || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Chuyên khoa:</label>
                              <span class="info-value">{{ d.professional_info?.specialty || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Phân chuyên khoa:</label>
                              <span class="info-value">{{ joinArr(d.professional_info?.sub_specialties) }}</span>
                            </div>
                            <div class="info-item">
                              <label>Kinh nghiệm:</label>
                              <span class="info-value">{{ d.professional_info?.experience_years ?? '-' }} năm</span>
                            </div>
                          </div>
                        </div>

                        <div class="detail-section" v-if="(d.professional_info?.education || []).length">
                          <h5 class="section-title">
                            <i class="bi bi-mortarboard"></i>
                            Học vấn
                          </h5>
                          <div class="info-grid">
                            <div class="info-item full-width" v-for="(e, ei) in (d.professional_info?.education || [])" :key="ei">
                              <label>•</label>
                              <span class="info-value">
                                {{ e.degree || '-' }} — {{ e.university || e.institution || '-' }}
                                <template v-if="e.year"> ({{ e.year }})</template>
                              </span>
                            </div>
                          </div>
                        </div>

                        <div class="detail-section" v-if="(d.professional_info?.certifications || []).length">
                          <h5 class="section-title">
                            <i class="bi bi-award"></i>
                            Chứng chỉ
                          </h5>
                          <div class="info-grid">
                            <div class="info-item full-width" v-for="(c, ci) in (d.professional_info?.certifications || [])" :key="ci">
                              <label>•</label>
                              <span class="info-value">
                                {{ c.name || '-' }} — {{ c.issuer || '-' }}
                                <template v-if="c.valid_until"> (Hết hạn: {{ fmtDate(c.valid_until) }})</template>
                              </span>
                            </div>
                          </div>
                        </div>

                        <div class="detail-section">
                          <h5 class="section-title">
                            <i class="bi bi-calendar-week"></i>
                            Lịch làm việc
                          </h5>
                          <div class="info-grid">
                            <div class="info-item">
                              <label>Ngày làm:</label>
                              <span class="info-value">{{ renderDays(d.schedule?.working_days) }}</span>
                            </div>
                            <div class="info-item">
                              <label>Giờ làm:</label>
                              <span class="info-value">{{ renderHours(d.schedule?.working_hours) }}</span>
                            </div>
                            <div class="info-item">
                              <label>Giờ nghỉ:</label>
                              <span class="info-value">{{ renderHours(d.schedule?.break_time) }}</span>
                            </div>
                          </div>
                        </div>

                        <div class="detail-section">
                          <h5 class="section-title">
                            <i class="bi bi-clock"></i>
                            Thời gian
                          </h5>
                          <div class="info-grid">
                            <div class="info-item">
                              <label>ID:</label>
                              <span class="info-value"><code>{{ d._id || d.id || '-' }}</code></span>
                            </div>
                            <div class="info-item">
                              <label>Rev:</label>
                              <span class="info-value"><code>{{ d._rev || '-' }}</code></span>
                            </div>
                            <div class="info-item">
                              <label>Tạo lúc:</label>
                              <span class="info-value">{{ fmtDateTime(d.created_at) }}</span>
                            </div>
                            <div class="info-item">
                              <label>Cập nhật:</label>
                              <span class="info-value">{{ fmtDateTime(d.updated_at) }}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>

              <tr v-if="!loading && !paginatedItems.length" class="empty-row">
                <td colspan="9" class="empty-cell">
                  <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h3>Không có dữ liệu</h3>
                    <p>Chưa có bác sĩ nào hoặc không tìm thấy kết quả phù hợp</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Section -->
        <div class="pagination-section">
          <div class="pagination-info-row">
            <span class="page-info">
              <i class="bi bi-file-earmark-text"></i>
              Trang <b>{{ page }} / {{ totalPages }}</b>
              <span class="total-info">- Hiển thị {{ paginatedItems.length }} trong tổng số {{ items.length }} bác sĩ</span>
            </span>
          </div>
          <div class="pagination-controls-center">
            <button
              class="pagination-btn prev-btn"
              @click="prevPage"
              :disabled="page <= 1"
              title="Trang trước"
            >
              <i class="bi bi-chevron-left"></i>
            </button>
            <div class="page-numbers">
              <button
                v-for="pageNum in getPageNumbers()"
                :key="pageNum"
                class="page-number-btn"
                :class="{ 'active': pageNum === page, 'ellipsis': pageNum === '...' }"
                @click="goToPage(pageNum)"
                :disabled="pageNum === '...' || pageNum === page"
              >
                {{ pageNum }}
              </button>
            </div>
            <button
              class="pagination-btn next-btn"
              @click="nextPage"
              :disabled="page >= totalPages"
              title="Trang sau"
            >
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </div>
      </template>
    </div>

    <!-- Modal Thêm/Sửa -->
    <div v-if="showModal" class="modal-backdrop" @mousedown.self="close">
      <div class="modal-card">
        <h2 class="h5 mb-3">{{ editingId ? 'Sửa thông tin bác sĩ' : 'Thêm bác sĩ' }}</h2>

        <form @submit.prevent="save">
          <!-- Personal -->
          <div class="section-title">Thông tin cá nhân</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Họ tên <span class="text-danger">*</span></label>
              <input v-model.trim="form.full_name" type="text" class="form-control" required />
            </div>
            <div class="col-md-3">
              <label class="form-label">Ngày sinh</label>
              <input v-model="form.birth_date" type="date" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Giới tính</label>
              <select v-model="form.gender" class="form-select">
                <option value="">-- chọn --</option>
                <option value="male">Nam</option>
                <option value="female">Nữ</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Điện thoại</label>
              <input v-model.trim="form.phone" class="form-control" maxlength="10" pattern="\d{10}" @input="onPhoneInput" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input v-model.trim="form.email" type="email" class="form-control" />
            </div>
          </div>

          <!-- Professional -->
          <div class="section-title">Nghề nghiệp</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Số giấy phép</label>
              <input v-model.trim="form.license_number" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Chuyên khoa</label>
              <input v-model.trim="form.specialty" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Phân chuyên khoa (phân tách dấu phẩy)</label>
              <input v-model.trim="form.sub_specialties" class="form-control" placeholder="vd: interventional_cardiology, ..." />
            </div>
            <div class="col-md-3">
              <label class="form-label">Kinh nghiệm (năm)</label>
              <input v-model.number="form.experience_years" type="number" min="0" class="form-control" />
            </div>

            <div class="col-12">
              <label class="form-label">Học vấn (mỗi dòng: degree;institution;year)</label>
              <textarea v-model="form.education_text" rows="3" class="form-control" placeholder="Doctor of Medicine;Đại học Y Dược TP.HCM;2005&#10;Specialist Level 2;Bệnh viện Chợ Rẫy;2010"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Chứng chỉ (mỗi dòng: name;issuer;valid_until YYYY-MM-DD)</label>
              <textarea v-model="form.certifications_text" rows="3" class="form-control" placeholder="Cardiac Intervention Certification;Vietnam Heart Association;2025-12-31"></textarea>
            </div>
          </div>

          <!-- Schedule -->
          <div class="section-title">Lịch làm việc (Cố định)</div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Ngày làm việc</label>
              <div class="alert alert-info py-2">
                <strong>Cố định:</strong> Thứ 2, Thứ 3, Thứ 4, Thứ 5, Thứ 6
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Giờ bắt đầu</label>
              <input type="text" class="form-control" value="08:00" readonly />
              <small class="text-muted">Cố định: 08:00 (24 giờ)</small>
            </div>
            <div class="col-md-3">
              <label class="form-label">Giờ kết thúc</label>
              <input type="text" class="form-control" value="17:00" readonly />
              <small class="text-muted">Cố định: 17:00 (24 giờ)</small>
            </div>
            <div class="col-md-3">
              <label class="form-label">Nghỉ trưa từ</label>
              <input type="text" class="form-control" value="12:00" readonly />
              <small class="text-muted">Cố định: 12:00 (24 giờ)</small>
            </div>
            <div class="col-md-3">
              <label class="form-label">Nghỉ trưa đến</label>
              <input type="text" class="form-control" value="13:00" readonly />
              <small class="text-muted">Cố định: 13:00 (24 giờ)</small>
            </div>
          </div>

          <!-- Status -->
          <div class="section-title">Khác</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Trạng thái</label>
              <select v-model="form.status" class="form-select">
                <option value="active">Hoạt động</option>
                <option value="inactive">Không hoạt động</option>
              </select>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary" @click="close">Huỷ</button>
            <button class="btn btn-primary" type="submit" :disabled="saving">{{ saving ? 'Đang lưu…' : 'Lưu' }}</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script>
import DoctorService from '@/api/doctorService'

const dayLabels = {
  monday: 'Thứ 2', tuesday: 'Thứ 3', wednesday: 'Thứ 4', thursday: 'Thứ 5', friday: 'Thứ 6', saturday: 'Thứ 7', sunday: 'Chủ nhật'
}

export default {
  name: 'DoctorsListView',
  data () {
    return {
      items: [],
      allItems: [],
      total: 0,
      q: '',
      page: 1,
      pageSize: 25,
      hasMore: false,
      loading: false,
      error: '',
      // modal
      showModal: false,
      saving: false,
      editingId: null,
      form: this.emptyForm(),
      // expand
      expanded: {},
      // day options
      dayOptions: [
        { value: 'monday', label: 'Thứ 2' },
        { value: 'tuesday', label: 'Thứ 3' },
        { value: 'wednesday', label: 'Thứ 4' },
        { value: 'thursday', label: 'Thứ 5' },
        { value: 'friday', label: 'Thứ 6' },
        { value: 'saturday', label: 'Thứ 7' },
        { value: 'sunday', label: 'Chủ nhật' }
      ]
    }
  },
  computed: {
    paginatedItems () {
      const start = (this.page - 1) * this.pageSize
      const end = start + this.pageSize
      return this.items.slice(start, end)
    },
    totalPages () {
      return Math.ceil(this.items.length / this.pageSize) || 1
    }
  },
  created () { this.fetch() },
  methods: {
    goHome () {
      window.location.href = '/#/home'
    },
    getPageNumbers () {
      const totalPagesValue = this.totalPages
      const current = this.page
      const pages = []

      if (totalPagesValue <= 7) {
        for (let i = 1; i <= totalPagesValue; i++) {
          pages.push(i)
        }
      } else {
        if (current <= 4) {
          pages.push(1, 2, 3, 4, 5, '...', totalPagesValue)
        } else if (current >= totalPagesValue - 3) {
          pages.push(1, '...', totalPagesValue - 4, totalPagesValue - 3, totalPagesValue - 2, totalPagesValue - 1, totalPagesValue)
        } else {
          pages.push(1, '...', current - 1, current, current + 1, '...', totalPagesValue)
        }
      }
      return pages
    },
    goToPage (pageNum) {
      if (pageNum !== '...' && pageNum !== this.page) {
        this.page = pageNum
      }
    },
    nextPage () {
      if (this.page < this.totalPages) {
        this.page++
      }
    },
    prevPage () {
      if (this.page > 1) {
        this.page--
      }
    },
    onPhoneInput (e) {
      // Chỉ cho phép nhập số và tối đa 10 ký tự
      const val = e.target.value.replace(/\D/g, '').slice(0, 10)
      this.form.phone = val
    },
    emptyForm () {
      return {
        _id: null,
        _rev: null,
        // personal
        full_name: '',
        birth_date: '',
        gender: '',
        phone: '',
        email: '',
        // professional
        license_number: '',
        specialty: '',
        sub_specialties: '',
        experience_years: null,
        education_text: '',
        certifications_text: '',
        // schedule
        working_days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], // Cố định thứ 2-6
        working_start: '08:00', // Cố định 8:00
        working_end: '17:00', // Cố định 17:00
        break_start: '12:00', // Cố định nghỉ trưa
        break_end: '13:00', // Cố định nghỉ trưa
        // status
        status: 'active'
      }
    },

    // --- UI helpers ---
    joinArr (a) { return Array.isArray(a) ? a.join(', ') : (a || '-') },
    renderGender (g) {
      const s = String(g ?? '').toLowerCase()
      if (['m', 'male', 'nam', '1', 'true'].includes(s)) return 'Nam'
      if (['f', 'female', 'nữ', '0', 'false'].includes(s)) return 'Nữ'
      return s || '-'
    },
    renderDays (days) {
      if (!Array.isArray(days) || !days.length) return '-'
      return days.map(d => dayLabels[d] || d).join(', ')
    },
    renderHours (obj) {
      if (!obj || (!obj.start && !obj.end)) return '-'
      if (obj.start === '12:00' && obj.end === '13:00') return '12g đến 13g'
      return `${obj.start || '--:--'} - ${obj.end || '--:--'}`
    },
    fmtDate (v) { if (!v) return '-'; try { return new Date(v).toLocaleDateString() } catch { return v } },
    fmtDateTime (v) { if (!v) return '-'; try { return new Date(v).toLocaleString() } catch { return v } },

    rowId (row) { return row._id || row.id || row.personal_info?.full_name },
    isExpanded (row) { return !!this.expanded[this.rowId(row)] },
    toggleRow (row) {
      const id = this.rowId(row)
      this.expanded = { ...this.expanded, [id]: !this.expanded[id] }
    },

    // --- Data ---
    async fetch () {
      this.loading = true
      this.error = ''
      try {
        // Always fetch all data for robust search
        const res = await DoctorService.list({})
        let items = []
        if (res && Array.isArray(res.rows)) {
          items = (res.rows || []).map(r => r.doc || r.value || r)
        } else if (res && res.data && Array.isArray(res.data)) {
          items = res.data
        } else if (Array.isArray(res)) { items = res }
        // đảm bảo có cấu trúc tối thiểu và ràng buộc giờ nghỉ trưa 12:00-13:00
        this.allItems = items.map(d => {
          const schedule = d.schedule || {}
          schedule.break_time = { start: '12:00', end: '13:00' }
          return {
            personal_info: d.personal_info || {},
            professional_info: d.professional_info || {},
            schedule,
            status: d.status || 'active',
            _id: d._id || d.id,
            _rev: d._rev,
            created_at: d.created_at,
            updated_at: d.updated_at
          }
        })
        this.items = [...this.allItems]
        this.total = this.items.length
        this.hasMore = false
      } catch (e) {
        console.error(e)
        this.error = e?.response?.data?.message || e?.message || 'Không tải được dữ liệu'
      } finally {
        this.loading = false
      }
    },
    async search () {
      this.page = 1
      if (!this.allItems.length) await this.fetch()
      const kw = (this.q || '').trim().toLowerCase()
      if (!kw) {
        this.items = [...this.allItems]
        this.total = this.items.length
        return
      }
      this.items = this.allItems.filter(d => {
        const pi = d.personal_info || {}
        const pro = d.professional_info || {}
        let subSpecialties = ''
        if (Array.isArray(pro.sub_specialties)) {
          subSpecialties = pro.sub_specialties.join(', ')
        } else if (typeof pro.sub_specialties === 'string') {
          subSpecialties = pro.sub_specialties
        }
        const fields = [
          pi.full_name,
          pro.specialty,
          subSpecialties,
          pi.phone,
          pi.email
        ].map(f => (f || '').toString().toLowerCase())
        return fields.some(field => field.includes(kw))
      })
      this.total = this.items.length
    },
    reload () { this.fetch() },
    changePageSize () { this.page = 1; this.fetch() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },

    // --- CRUD ---
    openCreate () {
      this.editingId = null
      this.form = this.emptyForm()
      // Force set giá trị cố định
      this.form.working_days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
      this.form.working_start = '08:00'
      this.form.working_end = '17:00'
      this.form.break_start = '12:00'
      this.form.break_end = '13:00'
      this.showModal = true
    },
    openEdit (row) {
      this.editingId = row._id || row.id || row.personal_info?.full_name
      const pi = row.personal_info || {}
      const pro = row.professional_info || {}

      // education -> text lines
      const eduLines = Array.isArray(pro.education)
        ? pro.education.map(e => {
          const inst = e.university || e.institution || ''
          return [e.degree || '', inst, e.year || ''].filter(x => x !== null && x !== undefined).join(';')
        })
        : []
      // certs -> text lines
      const certLines = Array.isArray(pro.certifications)
        ? pro.certifications.map(c =>
          [c.name || '', c.issuer || '', c.valid_until || ''].join(';')
        )
        : []

      this.form = {
        _id: row._id,
        _rev: row._rev,
        // personal
        full_name: pi.full_name || '',
        birth_date: pi.birth_date || '',
        gender: pi.gender || '',
        phone: pi.phone || '',
        email: pi.email || '',
        // professional
        license_number: pro.license_number || '',
        specialty: pro.specialty || '',
        sub_specialties: Array.isArray(pro.sub_specialties) ? pro.sub_specialties.join(', ') : (pro.sub_specialties || ''),
        experience_years: pro.experience_years ?? null,
        education_text: eduLines.join('\n'),
        certifications_text: certLines.join('\n'),
        // schedule - Force set giá trị cố định
        working_days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        working_start: '08:00',
        working_end: '17:00',
        break_start: '12:00', // Luôn cố định nghỉ trưa
        break_end: '13:00', // Luôn cố định nghỉ trưa
        // status
        status: row.status || 'active'
      }
      this.showModal = true
    },
    close () { if (!this.saving) this.showModal = false },

    parseEducation (text) {
      // mỗi dòng: degree;institution;year
      return (text || '')
        .split('\n')
        .map(s => s.trim())
        .filter(Boolean)
        .map(line => {
          const [degree, institution, year] = line.split(';').map(x => (x || '').trim())
          const obj = { degree: degree || undefined }
          if (institution) obj.institution = institution
          // chấp nhận cả "university" nếu trước đó đã dùng trường này
          if (institution && !obj.university) obj.university = institution
          if (year) obj.year = isNaN(+year) ? year : +year
          return obj
        })
    },
    parseCerts (text) {
      // mỗi dòng: name;issuer;valid_until
      return (text || '')
        .split('\n')
        .map(s => s.trim())
        .filter(Boolean)
        .map(line => {
          const [name, issuer, valid] = line.split(';').map(x => (x || '').trim())
          const obj = { name: name || undefined, issuer: issuer || undefined }
          if (valid) obj.valid_until = valid
          return obj
        })
    },

    async save () {
      if (this.saving) return
      this.saving = true
      try {
        // Ràng buộc giờ nghỉ trưa 12:00-13:00 cho tất cả bác sĩ
        const breakStart = '12:00'
        const breakEnd = '13:00'
        const payload = {
          type: 'doctor',
          personal_info: {
            full_name: this.form.full_name,
            birth_date: this.form.birth_date || undefined,
            gender: this.form.gender || undefined,
            phone: this.form.phone || undefined,
            email: this.form.email || undefined
          },
          professional_info: {
            license_number: this.form.license_number || undefined,
            specialty: this.form.specialty || undefined,
            sub_specialties: this.form.sub_specialties
              ? this.form.sub_specialties.split(',').map(s => s.trim()).filter(Boolean)
              : [],
            experience_years: (this.form.experience_years ?? '') === '' ? undefined : Number(this.form.experience_years),
            education: this.parseEducation(this.form.education_text),
            certifications: this.parseCerts(this.form.certifications_text)
          },
          schedule: {
            working_days: Array.isArray(this.form.working_days) ? this.form.working_days : [],
            working_hours: {
              start: this.form.working_start || undefined,
              end: this.form.working_end || undefined
            },
            break_time: {
              start: breakStart,
              end: breakEnd
            }
          },
          status: this.form.status
        }
        if (this.form._id) payload._id = this.form._id
        if (this.form._rev) payload._rev = this.form._rev

        if (this.editingId) {
          await DoctorService.update(this.editingId, payload)
        } else {
          await DoctorService.create(payload)
        }

        this.showModal = false
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Lưu thất bại')
      } finally {
        this.saving = false
      }
    },

    async remove (row) {
      if (!confirm(`Xóa bác sĩ "${row.personal_info?.full_name || row._id}"?`)) return
      try {
        const id = row._id || row.id
        // Always fetch latest _rev before delete
        const fresh = await DoctorService.get(id)
        const rev = fresh?._rev
        if (!rev) throw new Error('Không lấy được revision của document')
        const result = await DoctorService.remove(id, rev)
        if (result && (result.ok === true || result.status === 'success')) {
          await this.fetch()
        } else {
          throw new Error(result?.message || 'Xóa thất bại')
        }
      } catch (e) {
        // Retry if conflict
        if (e?.message?.includes('conflict') || e?.message?.includes('409')) {
          try {
            const newer = await DoctorService.get(row._id || row.id)
            const newerRev = newer?._rev
            if (newerRev) {
              const retryResult = await DoctorService.remove(row._id || row.id, newerRev)
              if (retryResult && (retryResult.ok === true || retryResult.status === 'success')) {
                await this.fetch()
                return
              }
            }
            alert('Xóa thất bại: Document đã được thay đổi, vui lòng tải lại trang')
          } catch (retryError) {
            alert('Xóa thất bại sau khi thử lại')
          }
        } else {
          alert(e?.response?.data?.message || e?.message || 'Xóa thất bại')
        }
      }
    }
  }
}
</script>

<style scoped>
@import 'bootstrap-icons/font/bootstrap-icons.css';

/* Main Container */
.doctors-management {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 0;
  margin: 0;
  font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Header Section */
.header-section {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  padding: 2rem 3rem;
  box-shadow: 0 4px 20px rgba(59, 130, 246, 0.15);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1400px;
  margin: 0 auto;
}

.header-left .page-title {
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.header-left .page-title i {
  font-size: 1.75rem;
  color: #dbeafe;
}

.header-left .page-subtitle {
  font-size: 1rem;
  color: #bfdbfe;
  margin: 0;
  opacity: 0.9;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stats-badge {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  padding: 0.75rem 1rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.stats-badge i {
  font-size: 1.1rem;
  color: #dbeafe;
}

.btn-action {
  padding: 0.75rem 1.25rem;
  border-radius: 10px;
  border: none;
  font-weight: 600;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  cursor: pointer;
  text-decoration: none;
}

.btn-refresh {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 0.75rem;
}

.btn-refresh:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.05);
}

.btn-primary {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.btn-back {
  background: linear-gradient(135deg, #64748b 0%, #3b82f6 100%);
  color: #fff;
  border: none;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.12);
  font-weight: 600;
  transition: all 0.2s;
}

.btn-back:hover:not(:disabled) {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: #fff;
  transform: translateY(-1px);
  box-shadow: 0 4px 16px rgba(59, 130, 246, 0.18);
}

/* Search Section */
.search-section {
  background: white;
  padding: 2rem 3rem;
  border-bottom: 1px solid #e5e7eb;
}

.search-container {
  max-width: 1400px;
  margin: 0 auto;
}

.search-input-group {
  max-width: 600px;
  position: relative;
  display: flex;
  align-items: center;
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.search-input-group:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  background: white;
}

.search-icon {
  color: #64748b;
  font-size: 1.1rem;
  margin: 0 1rem;
}

.search-input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 1rem 0.5rem;
  font-size: 1rem;
  color: #1e293b;
  outline: none;
}

.search-input::placeholder {
  color: #64748b;
}

.search-btn {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border: none;
  padding: 1rem 1.5rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  cursor: pointer;
}

.search-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
}

/* Content Section */
.content-section {
  padding: 2rem 3rem;
  max-width: 1400px;
  margin: 0 auto;
}

.alert {
  padding: 1rem 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 500;
}

.alert-error {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 3rem;
  color: #64748b;
  font-size: 1.1rem;
}

.spinner {
  width: 2rem;
  height: 2rem;
  border: 3px solid #e2e8f0;
  border-top: 3px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Table Container */
.table-container {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
}

.doctors-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
}

.doctors-table thead {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.doctors-table th {
  padding: 1.25rem 1rem;
  text-align: left;
  font-weight: 700;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.col-number { width: 80px; text-align: center; }
.col-name { width: 180px; }
.col-specialty { width: 140px; }
.col-subspecialty { width: 160px; }
.col-experience { width: 90px; text-align: center; }
.col-phone { width: 120px; }
.col-email { width: 180px; }
.col-status { width: 140px; text-align: center; }
.col-actions { width: 140px; text-align: center; }

.doctor-row {
  transition: all 0.3s ease;
  border-bottom: 1px solid #f1f5f9;
}

.doctor-row:hover {
  background: #f8fafc;
}

.doctor-row.expanded {
  background: #eff6ff;
}

.doctors-table td {
  padding: 1.25rem 1rem;
  vertical-align: middle;
  color: #374151;
}

.cell-number {
  text-align: center;
}

.row-number {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.85rem;
  display: inline-block;
  min-width: 2.5rem;
}

.doctor-name strong {
  color: #1e293b;
  font-weight: 600;
}

.cell-experience {
  text-align: center;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.85rem;
  border: 1px solid;
  display: inline-flex;
}

.status-active {
  background: #f0fdf4;
  color: #166534;
  border-color: #bbf7d0;
}

.status-inactive {
  background: #f8fafc;
  color: #64748b;
  border-color: #e2e8f0;
}

.action-buttons {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
}

.action-btn {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 8px;
  border: 1px solid;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  cursor: pointer;
  font-size: 1rem;
}

.view-btn {
  color: #3b82f6;
  border-color: #bfdbfe;
}

.view-btn:hover:not(:disabled) {
  background: #eff6ff;
  color: #1d4ed8;
  transform: scale(1.1);
}

.edit-btn {
  color: #f59e0b;
  border-color: #fed7aa;
}

.edit-btn:hover:not(:disabled) {
  background: #fffbeb;
  color: #d97706;
  transform: scale(1.1);
}

.delete-btn {
  color: #ef4444;
  border-color: #fecaca;
}

.delete-btn:hover:not(:disabled) {
  background: #fef2f2;
  color: #dc2626;
  transform: scale(1.1);
}

.delete-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Detail Row */
.detail-row td {
  padding: 0;
  background: #eff6ff;
  border-top: 2px solid #bfdbfe;
}

.detail-cell {
  padding: 0 !important;
}

.doctor-detail-card {
  margin: 2rem;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
}

.detail-header {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  padding: 1.5rem 2rem;
}

.detail-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.detail-content {
  padding: 2rem;
}

.detail-section {
  margin-bottom: 2rem;
}

.detail-section:last-child {
  margin-bottom: 0;
}

.section-title {
  color: #374151;
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e5e7eb;
}

.section-title i {
  color: #3b82f6;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-item.full-width {
  grid-column: 1 / -1;
}

.info-item label {
  color: #64748b;
  font-weight: 600;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-value {
  color: #1e293b;
  font-weight: 500;
}

.info-value code {
  background: #eff6ff;
  color: #1d4ed8;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  font-weight: 600;
}

/* Empty State */
.empty-row td {
  padding: 4rem 2rem;
  text-align: center;
}

.empty-state {
  color: #64748b;
}

.empty-state i {
  font-size: 4rem;
  color: #cbd5e1;
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.empty-state p {
  font-size: 1rem;
  margin: 0;
}

/* Pagination Section */
.pagination-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 0 0.5rem;
  border-top: 1px solid #e5e7eb;
  margin-top: 1.5rem;
  background: transparent;
  gap: 0.5rem;
}

.pagination-info-row {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.04rem;
  color: #374151;
  margin-bottom: 0.15rem;
  font-weight: 500;
}

.page-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.95rem;
}

.page-info i {
  color: #3b82f6;
}

.total-info {
  font-size: 0.85rem;
  color: #64748b;
  font-weight: 400;
  margin-left: 0.5rem;
}

.pagination-controls-center {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border-radius: 2rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  border: 1px solid #e5e7eb;
  padding: 0.15rem 0.5rem;
  min-width: 120px;
  max-width: 180px;
  gap: 0;
  height: 2.6rem;
}

.pagination-btn {
  width: 2.4rem;
  height: 2.4rem;
  border: none;
  background: transparent;
  color: #b0b6be;
  border-radius: 50%;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s, color 0.15s;
  cursor: pointer;
  font-size: 1.2rem;
  margin: 0 0.1rem;
}

.pagination-btn:hover:not(:disabled) {
  background: #f3f4f6;
  color: #2563eb;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: transparent;
  color: #e5e7eb;
}

.page-numbers {
  display: flex;
  align-items: center;
  gap: 0;
  margin: 0;
}

.page-number-btn {
  width: 2.4rem;
  height: 2.4rem;
  border: none;
  background: transparent;
  color: #2563eb;
  border-radius: 50%;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s, color 0.15s;
  cursor: pointer;
  font-size: 1.1rem;
  margin: 0 0.1rem;
}

.page-number-btn:hover:not(:disabled):not(.ellipsis) {
  background: #f3f4f6;
  color: #2563eb;
}

.page-number-btn.active {
  background: #2563eb;
  color: #fff;
  border-radius: 50%;
  box-shadow: 0 2px 8px rgba(37,99,235,0.10);
  z-index: 1;
}

.page-number-btn.ellipsis {
  border: none;
  background: transparent;
  cursor: default;
  color: #b0b6be;
  font-weight: 400;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .header-content {
    flex-direction: column;
    gap: 1.5rem;
    align-items: flex-start;
  }

  .header-actions {
    align-self: stretch;
    justify-content: space-between;
    flex-wrap: wrap;
  }
}

@media (max-width: 768px) {
  .header-section {
    padding: 1.5rem 1rem;
  }

  .search-section {
    padding: 1.5rem 1rem;
  }

  .content-section {
    padding: 1.5rem 1rem;
  }

  .doctors-table {
    font-size: 0.85rem;
  }

  .doctors-table th,
  .doctors-table td {
    padding: 1rem 0.5rem;
  }

  .action-buttons {
    flex-direction: column;
    gap: 0.25rem;
  }
}

/* Modal */
.modal-backdrop{ position: fixed; inset: 0; background: rgba(0,0,0,.45); display: grid; place-items: center; z-index: 1050; }
.modal-card{ width: min(980px, 96vw); background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 20px 50px rgba(0,0,0,.25); max-height: 92vh; overflow: auto; }
.section-title{ font-weight: 600; margin: 14px 0 8px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; }
</style>
