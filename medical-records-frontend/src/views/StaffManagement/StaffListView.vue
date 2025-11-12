<template>
  <section class="staff-management">
    <!-- Header Section -->
    <section class="header-section">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="bi bi-people-fill"></i>
            Quản lý Nhân viên
          </h1>
          <p class="page-subtitle">Quản lý thông tin nhân viên và ca làm việc</p>
        </div>
        <div class="header-actions">
          <div class="stats-badge">
            <i class="bi bi-database"></i>
            <span>{{ total }} nhân viên</span>
          </div>
          <button class="btn-action btn-refresh" @click="refreshPage" :disabled="loading">
            <i class="bi bi-arrow-clockwise"></i>
          </button>
          <button class="btn-action btn-primary" @click="openCreate" :disabled="loading">
            <i class="bi bi-plus-lg"></i>
            Thêm mới
          </button>
          <button class="btn-action btn-back" @click="goHome">
            <i class="bi bi-house-door"></i>
            Trang chủ
          </button>
        </div>
      </div>
    </section>

    <!-- Search Section -->
    <section class="search-section">
      <div class="search-container">
        <div class="search-input-group">
          <i class="bi bi-search search-icon"></i>
          <input
            v-model.trim="q"
            class="search-input"
            placeholder="Tìm theo tên, email, SĐT..."
            @keyup.enter="search"
          />
          <button class="search-btn" @click="search">
            <i class="bi bi-search"></i>
            Tìm kiếm
          </button>
        </div>
      </div>
    </section>

    <!-- Content Section -->
    <section class="content-section">
      <!-- Error Alert -->
      <div v-if="error" class="alert alert-error">
        <i class="bi bi-exclamation-triangle-fill"></i>
        {{ error }}
      </div>

      <!-- Loading State -->
      <div v-if="loading && !items.length" class="loading-state">
        <div class="spinner"></div>
        <span>Đang tải dữ liệu...</span>
      </div>

      <!-- Table Container -->
      <div v-else class="table-container">
        <table class="staff-table">
          <thead>
            <tr>
              <th class="col-number">#</th>
              <th class="col-name">Họ tên</th>
              <th class="col-type">Loại NV</th>
              <th class="col-gender">Giới tính</th>
              <th class="col-phone">SĐT</th>
              <th class="col-email">Email</th>
              <th class="col-department">Phòng ban</th>
              <th class="col-shift">Ca làm</th>
              <th class="col-status">Trạng thái</th>
              <th class="col-actions">Tác vụ</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="(s, idx) in paginatedItems" :key="s._id || s.id || idx">
              <tr class="staff-row" :class="{ expanded: isExpanded(s) }">
                <td class="cell-number">
                  <span class="row-number">{{ idx + 1 + (currentPage - 1) * itemsPerPage }}</span>
                </td>
                <td class="staff-name">
                  <strong>{{ s.full_name }}</strong>
                </td>
                <td>{{ renderStaffType(s.staff_type) }}</td>
                <td>{{ renderGender(s.gender) }}</td>
                <td>{{ s.phone || '-' }}</td>
                <td>{{ s.email || '-' }}</td>
                <td>{{ s.department || '-' }}</td>
                <td>
                  <div v-if="s.shift" class="shift-compact">
                    <span class="shift-badge shift-badge-days">
                      <i class="bi bi-calendar-week"></i>
                      {{ renderShiftDaysCompact(s.shift?.days) }}
                    </span>
                    <span class="shift-badge shift-badge-time">
                      <i class="bi bi-clock"></i>
                      {{ renderShiftTime(s.shift) }}
                    </span>
                  </div>
                  <span v-else>-</span>
                </td>
                <td>
                  <span class="status-badge" :class="s.status === 'active' ? 'status-active' : 'status-inactive'">
                    <i class="bi" :class="s.status === 'active' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"></i>
                    {{ s.status === 'active' ? 'Hoạt động' : 'Không hoạt động' }}
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button class="action-btn view-btn" @click="toggleRow(s)" :title="isExpanded(s) ? 'Ẩn' : 'Xem'">
                      <i class="bi" :class="isExpanded(s) ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                    <button class="action-btn edit-btn" @click="openEdit(s)" title="Sửa">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="action-btn delete-btn" @click="remove(s)" :disabled="loading" title="Xóa">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Detail Row -->
              <tr v-if="isExpanded(s)" class="detail-row">
                <td colspan="10" class="detail-cell">
                  <div class="staff-detail-card">
                    <div class="detail-header">
                      <h3 class="detail-title">
                        <i class="bi bi-info-circle"></i>
                        Chi tiết nhân viên
                      </h3>
                    </div>
                    <div class="detail-content">
                      <div class="detail-section">
                        <h4 class="section-title">
                          <i class="bi bi-person-badge"></i>
                          Thông tin liên hệ
                        </h4>
                        <div class="info-grid">
                          <div class="info-item">
                            <label>Họ tên</label>
                            <div class="info-value">{{ s.full_name }}</div>
                          </div>
                          <div class="info-item">
                            <label>Giới tính</label>
                            <div class="info-value">{{ renderGender(s.gender) }}</div>
                          </div>
                          <div class="info-item">
                            <label>Điện thoại</label>
                            <div class="info-value">{{ s.phone || '-' }}</div>
                          </div>
                          <div class="info-item">
                            <label>Email</label>
                            <div class="info-value">{{ s.email || '-' }}</div>
                          </div>
                        </div>
                      </div>

                      <div class="detail-section">
                        <h4 class="section-title">
                          <i class="bi bi-briefcase"></i>
                          Công việc
                        </h4>
                        <div class="info-grid">
                          <div class="info-item">
                            <label>Loại nhân viên</label>
                            <div class="info-value">{{ renderStaffType(s.staff_type) }}</div>
                          </div>
                          <div class="info-item">
                            <label>Phòng ban</label>
                            <div class="info-value">{{ s.department || '-' }}</div>
                          </div>
                          <div class="info-item">
                            <label>Ngày làm việc</label>
                            <div class="info-value">{{ renderShiftDays(s.shift?.days) }}</div>
                          </div>
                          <div class="info-item">
                            <label>Giờ làm</label>
                            <div class="info-value">{{ renderShiftTime(s.shift) }}</div>
                          </div>
                        </div>
                      </div>

                      <div class="detail-section">
                        <h4 class="section-title">
                          <i class="bi bi-gear"></i>
                          Thông tin hệ thống
                        </h4>
                        <div class="info-grid">
                          <div class="info-item">
                            <label>ID</label>
                            <div class="info-value"><code>{{ s._id || s.id || '-' }}</code></div>
                          </div>
                          <div class="info-item">
                            <label>Revision</label>
                            <div class="info-value"><code>{{ s._rev || '-' }}</code></div>
                          </div>
                          <div class="info-item">
                            <label>Tạo lúc</label>
                            <div class="info-value">{{ fmtDateTime(s.created_at) }}</div>
                          </div>
                          <div class="info-item">
                            <label>Cập nhật</label>
                            <div class="info-value">{{ fmtDateTime(s.updated_at) }}</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            </template>

            <tr v-if="!items.length" class="empty-row">
              <td colspan="10">
                <div class="empty-state">
                  <i class="bi bi-inbox"></i>
                  <h3>Không có dữ liệu</h3>
                  <p>Chưa có nhân viên nào trong hệ thống</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="items.length > 0" class="pagination-section">
          <div class="pagination-info-row">
            <span class="page-info">
              <i class="bi bi-file-earmark-text"></i>
              Trang {{ currentPage }}/{{ totalPages }}
            </span>
            <span class="total-info">({{ total }} nhân viên)</span>
          </div>
          <div class="pagination-controls-center">
            <button class="pagination-btn" @click="prevPage" :disabled="currentPage === 1">
              <i class="bi bi-chevron-left"></i>
            </button>
            <div class="page-numbers">
              <button
                v-for="page in getPageNumbers()"
                :key="page"
                class="page-number-btn"
                :class="{ active: page === currentPage, ellipsis: page === '...' }"
                @click="page !== '...' && goToPage(page)"
                :disabled="page === '...'"
              >
                {{ page }}
              </button>
            </div>
            <button class="pagination-btn" @click="nextPage" :disabled="currentPage === totalPages">
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Modal Thêm/Sửa -->
    <div v-if="showModal" class="modal-backdrop" @mousedown.self="close">
      <div class="modal-card">
        <h2 class="h5 mb-3">{{ editingId ? 'Sửa thông tin nhân viên' : 'Thêm nhân viên' }}</h2>

        <form @submit.prevent="save">
          <div class="section-title">Thông tin cơ bản</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Họ tên <span class="text-danger">*</span></label>
              <input v-model.trim="form.full_name" type="text" class="form-control" required />
            </div>
            <div class="col-md-3">
              <label class="form-label">Giới tính</label>
              <select v-model="form.gender" class="form-select">
                <option value="">-- chọn --</option>
                <option value="male">Nam</option>
                <option value="female">Nữ</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Loại NV</label>
              <select v-model="form.staff_type" class="form-select">
                <option value="">-- chọn --</option>
                <option value="nurse">Nurse</option>
                <option value="receptionist">Receptionist</option>
                <option value="pharmacist">Pharmacist</option>
                <option value="lab">Lab</option>
                <option value="admin">Admin</option>
                <option value="accountant">Accountant</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Điện thoại</label>
              <input v-model.trim="form.phone" class="form-control" maxlength="10" pattern="\d{10}" @input="onPhoneInput" />
            </div>
            <div class="col-md-8">
              <label class="form-label">Email</label>
              <input v-model.trim="form.email" type="email" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Phòng ban</label>
              <input v-model.trim="form.department" class="form-control" />
            </div>
          </div>

          <div class="section-title">Ca làm (Cố định)</div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Ngày làm việc</label>
              <div class="alert alert-info py-2">
                <strong>Cố định:</strong> Thứ 2, Thứ 3, Thứ 4, Thứ 5, Thứ 6
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Bắt đầu</label>
              <input type="text" class="form-control" value="08:00" readonly />
              <small class="text-muted">Cố định: 08:00 (24 giờ)</small>
            </div>
            <div class="col-md-3">
              <label class="form-label">Kết thúc</label>
              <input type="text" class="form-control" value="17:00" readonly />
              <small class="text-muted">Cố định: 17:00 (24 giờ)</small>
            </div>
          </div>

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
import StaffService from '@/api/staffService'

const dayLabels = {
  mon: 'Thứ 2', tue: 'Thứ 3', wed: 'Thứ 4', thu: 'Thứ 5', fri: 'Thứ 6', sat: 'Thứ 7', sun: 'Chủ nhật'
}

export default {
  name: 'StaffListView',
  data () {
    return {
      items: [],
      total: 0,
      q: '',
      page: 1,
      pageSize: 5,
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
        { value: 'mon', label: 'Thứ 2' },
        { value: 'tue', label: 'Thứ 3' },
        { value: 'wed', label: 'Thứ 4' },
        { value: 'thu', label: 'Thứ 5' },
        { value: 'fri', label: 'Thứ 6' },
        { value: 'sat', label: 'Thứ 7' },
        { value: 'sun', label: 'Chủ nhật' }
      ],
      // Pagination
      currentPage: 1,
      itemsPerPage: 10
    }
  },
  computed: {
    paginatedItems () {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.items.slice(start, end)
    },
    totalPages () {
      return Math.ceil(this.items.length / this.itemsPerPage)
    }
  },
  created () { this.fetch() },
  methods: {
    emptyForm () {
      return {
        _id: null,
        _rev: null,
        full_name: '',
        staff_type: '',
        gender: '',
        phone: '',
        email: '',
        department: '',
        shift: {
          days: ['mon', 'tue', 'wed', 'thu', 'fri'], // Cố định thứ 2-6
          start: '08:00', // Cố định 8:00
          end: '17:00' // Cố định 17:00
        },
        status: 'active'
      }
    },

    // --- UI helpers
    renderGender (g) {
      const s = String(g ?? '').toLowerCase()
      if (['m', 'male', 'nam', '1', 'true'].includes(s)) return 'Nam'
      if (['f', 'female', 'nữ', '0', 'false'].includes(s)) return 'Nữ'
      return s || '-'
    },
    renderStaffType (t) { return t || '-' },
    renderShiftDays (days) {
      if (!Array.isArray(days) || !days.length) return '-'
      return days.map(d => dayLabels[d] || d).join(', ')
    },
    renderShiftDaysCompact (days) {
      if (!Array.isArray(days) || !days.length) return '-'
      // Hiển thị gọn: T2-T6 hoặc danh sách ngắn
      const labels = days.map(d => dayLabels[d] || d)
      if (labels.length === 5 && labels.join(',') === 'Thứ 2,Thứ 3,Thứ 4,Thứ 5,Thứ 6') {
        return 'T2-T6'
      }
      // Rút gọn: Thứ 2 -> T2
      return labels.map(l => l.replace('Thứ ', 'T').replace('Chủ nhật', 'CN')).join(', ')
    },
    renderShiftTime (shift) {
      if (!shift || (!shift.start && !shift.end)) return '-'
      return `${shift.start || '--:--'} - ${shift.end || '--:--'}`
    },
    renderShift (shift) {
      if (!shift) return '-'
      const days = this.renderShiftDays(shift.days)
      const time = this.renderShiftTime(shift)
      return days === '-' && time === '-' ? '-' : `${days}; ${time}`
    },
    fmtDateTime (v) { if (!v) return '-'; try { return new Date(v).toLocaleString() } catch { return v } },

    rowId (row) { return row._id || row.id || row.full_name },
    isExpanded (row) { return !!this.expanded[this.rowId(row)] },
    toggleRow (row) {
      const id = this.rowId(row)
      this.expanded = { ...this.expanded, [id]: !this.expanded[id] }
    },

    // --- Data
    async fetch () {
      this.loading = true
      this.error = ''
      try {
        const skip = (this.page - 1) * this.pageSize
        // Build params for API
        // Build params for API
        const params = {
          limit: this.pageSize,
          offset: skip,
          skip
        }
        if (typeof this.q === 'string' && this.q.trim().length > 0) {
          params.q = this.q.trim()
        }
        const res = await StaffService.list(params)
        let items = []; let total = 0; let offset = null
        // Chuẩn hóa dữ liệu trả về
        if (res && Array.isArray(res.rows)) {
          items = (res.rows || []).map(r => r.doc || r.value || r)
          total = res.total_rows ?? items.length
          offset = res.offset ?? 0
        } else if (res && res.data && Array.isArray(res.data)) {
          items = res.data; total = res.total ?? items.length
        } else if (Array.isArray(res)) { items = res; total = res.length }

        // Nếu có từ khóa tìm kiếm, lọc trên frontend nếu backend chưa hỗ trợ
        if (typeof this.q === 'string' && this.q.trim().length > 0) {
          const keyword = this.q.trim().toLowerCase()
          items = items.filter(s => {
            return (
              (s.full_name && s.full_name.toLowerCase().includes(keyword)) ||
              (s.email && s.email.toLowerCase().includes(keyword)) ||
              (s.phone && s.phone.includes(keyword))
            )
          })
          total = items.length
        }
        this.items = items
        this.total = total
        this.hasMore = (offset != null)
          ? (offset + this.items.length) < (this.total || 0)
          : (this.page * this.pageSize) < (this.total || 0)
      } catch (e) {
        console.error(e)
        this.error = e?.response?.data?.message || e?.message || 'Không tải được dữ liệu'
      } finally {
        this.loading = false
      }
    },
    search () {
      this.page = 1
      this.fetch()
    },
    reload () { this.fetch() },
    refreshPage () {
      // Reset tất cả bộ lọc và trạng thái
      this.q = ''
      this.page = 1
      this.expanded = {}
      this.error = ''
      // Tải lại dữ liệu
      this.fetch()
    },
    changePageSize () { this.page = 1; this.fetch() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },

    // --- CRUD
    openCreate () {
      this.editingId = null
      this.form = this.emptyForm()
      // Force set giá trị cố định
      this.form.shift = {
        days: ['mon', 'tue', 'wed', 'thu', 'fri'],
        start: '08:00',
        end: '17:00'
      }
      this.showModal = true
    },
    openEdit (row) {
      this.editingId = row._id || row.id || row.full_name
      this.form = {
        _id: row._id,
        _rev: row._rev,
        full_name: row.full_name || '',
        staff_type: row.staff_type || '',
        gender: row.gender || '',
        phone: row.phone || '',
        email: row.email || '',
        department: row.department || '',
        shift: {
          days: ['mon', 'tue', 'wed', 'thu', 'fri'], // Force cố định thứ 2-6
          start: '08:00', // Force cố định 8:00
          end: '17:00' // Force cố định 17:00
        },
        status: row.status || 'active'
      }
      this.showModal = true
    },
    close () { if (!this.saving) this.showModal = false },

    async save () {
      if (this.saving) return
      this.saving = true
      try {
        const payload = {
          type: 'staff',
          full_name: this.form.full_name,
          staff_type: this.form.staff_type || undefined,
          gender: this.form.gender || undefined,
          phone: this.form.phone || undefined,
          email: this.form.email || undefined,
          department: this.form.department || undefined,
          shift: {
            days: Array.isArray(this.form.shift?.days) ? this.form.shift.days : [],
            start: this.form.shift?.start || undefined,
            end: this.form.shift?.end || undefined
          },
          status: this.form.status
        }
        if (this.form._id) payload._id = this.form._id
        if (this.form._rev) payload._rev = this.form._rev

        if (this.editingId) {
          await StaffService.update(this.editingId, payload)
        } else {
          await StaffService.create(payload)
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
      if (!confirm(`Xóa nhân viên "${row.full_name}"?`)) return
      try {
        const id = row._id || row.id
        await StaffService.remove(id)
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Xóa thất bại')
      }
    },
    onPhoneInput (e) {
      // Chỉ cho phép nhập số và tối đa 10 ký tự
      const val = e.target.value.replace(/\D/g, '').slice(0, 10)
      this.form.phone = val
    },

    // Pagination methods
    goHome () {
      this.$router.push('/')
    },
    getPageNumbers () {
      const pages = []
      const total = this.totalPages
      const current = this.currentPage

      if (total <= 7) {
        for (let i = 1; i <= total; i++) {
          pages.push(i)
        }
      } else {
        if (current <= 3) {
          for (let i = 1; i <= 4; i++) pages.push(i)
          pages.push('...')
          pages.push(total)
        } else if (current >= total - 2) {
          pages.push(1)
          pages.push('...')
          for (let i = total - 3; i <= total; i++) pages.push(i)
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = current - 1; i <= current + 1; i++) pages.push(i)
          pages.push('...')
          pages.push(total)
        }
      }
      return pages
    },
    goToPage (page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page
      }
    },
    nextPage () {
      if (this.currentPage < this.totalPages) {
        this.currentPage++
      }
    },
    prevPage () {
      if (this.currentPage > 1) {
        this.currentPage--
      }
    }
  }
}
</script>

<style scoped>
@import 'bootstrap-icons/font/bootstrap-icons.css';

/* Main Container */
.staff-management {
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

.staff-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
}

.staff-table thead {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.staff-table th {
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
.col-name { width: 160px; }
.col-type { width: 120px; }
.col-gender { width: 90px; }
.col-phone { width: 120px; }
.col-email { width: 180px; }
.col-department { width: 120px; }
.col-shift { width: 180px; }
.col-status { width: 140px; text-align: center; }
.col-actions { width: 140px; text-align: center; }

/* Shift Compact Styling */
.shift-compact {
  display: flex;
  flex-direction: row;
  gap: 0.4rem;
  flex-wrap: wrap;
}

.shift-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.65rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
  white-space: nowrap;
  border: 1px solid;
}

.shift-badge i {
  font-size: 0.85rem;
}

.shift-badge-days {
  background: #eff6ff;
  color: #1d4ed8;
  border-color: #bfdbfe;
}

.shift-badge-days i {
  color: #3b82f6;
}

.shift-badge-time {
  background: #f8fafc;
  color: #475569;
  border-color: #e2e8f0;
}

.shift-badge-time i {
  color: #64748b;
}

.staff-row {
  transition: all 0.3s ease;
  border-bottom: 1px solid #f1f5f9;
}

.staff-row:hover {
  background: #f8fafc;
}

.staff-row.expanded {
  background: #eff6ff;
}

.staff-table td {
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

.staff-name strong {
  color: #1e293b;
  font-weight: 600;
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

.staff-detail-card {
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
  word-break: break-all;
  white-space: normal;
  display: inline-block;
  max-width: 100%;
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

  .staff-table {
    font-size: 0.85rem;
  }

  .staff-table th,
  .staff-table td {
    padding: 1rem 0.5rem;
  }

  .action-buttons {
    flex-direction: column;
    gap: 0.25rem;
  }
}

/* Modal */
.modal-backdrop{ position: fixed; inset: 0; background: rgba(0,0,0,.45); display: grid; place-items: center; z-index: 1050; }
.modal-card{ width: min(940px, 96vw); background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 20px 50px rgba(0,0,0,.25); max-height: 92vh; overflow: auto; }
.section-title{ font-weight: 600; margin: 14px 0 8px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; }
</style>
