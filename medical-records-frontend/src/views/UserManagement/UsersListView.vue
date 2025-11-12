<template>
  <section class="user-management">
    <!-- Header Section -->
    <section class="header-section">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="bi bi-person-circle"></i>
            Quản lý Người dùng
          </h1>
          <p class="page-subtitle">Quản lý tài khoản người dùng và phân quyền</p>
        </div>
        <div class="header-actions">
          <div class="stats-badge">
            <i class="bi bi-database"></i>
            <span>{{ total }} người dùng</span>
          </div>
          <button class="btn-action btn-refresh" @click="reload" :disabled="loading">
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
            placeholder="Tìm theo username, email..."
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
        <table class="user-table">
          <thead>
            <tr>
              <th class="col-number">#</th>
              <th class="col-username">Username</th>
              <th class="col-email">Email</th>
              <th class="col-role">Vai trò</th>
              <th class="col-type">Loại TK</th>
              <th class="col-link">Liên kết</th>
              <th class="col-status">Trạng thái</th>
              <th class="col-actions">Tác vụ</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="(u, idx) in paginatedItems" :key="u._id || u.id || idx">
              <tr class="user-row" :class="{ expanded: isExpanded(u) }">
                <td class="cell-number">
                  <span class="row-number">{{ idx + 1 + (currentPage - 1) * itemsPerPage }}</span>
                </td>
                <td class="user-username">
                  <strong>{{ u.username }}</strong>
                </td>
                <td>{{ u.email }}</td>
                <td>
                  <span class="role-badge">{{ joinRoles(u.role_names) }}</span>
                </td>
                <td>{{ u.account_type || '-' }}</td>
                <td>{{ linkedAny(u) }}</td>
                <td>
                  <span class="status-badge" :class="u.status === 'active' ? 'status-active' : 'status-inactive'">
                    <i class="bi" :class="u.status === 'active' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"></i>
                    {{ u.status === 'active' ? 'Hoạt động' : 'Không hoạt động' }}
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button class="action-btn view-btn" @click="toggleRow(u)" :title="isExpanded(u) ? 'Ẩn' : 'Xem'">
                      <i class="bi" :class="isExpanded(u) ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                    <button class="action-btn edit-btn" @click="openEdit(u)" title="Sửa">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="action-btn delete-btn" @click="remove(u)" :disabled="loading" title="Xóa">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Detail Row -->
              <tr v-if="isExpanded(u)" class="detail-row">
                <td colspan="8" class="detail-cell">
                  <div class="user-detail-card">
                    <div class="detail-header">
                      <h3 class="detail-title">
                        <i class="bi bi-info-circle"></i>
                        Chi tiết người dùng
                      </h3>
                    </div>
                    <div class="detail-content">
                      <div class="detail-section">
                        <h4 class="section-title">
                          <i class="bi bi-person-badge"></i>
                          Thông tin tài khoản
                        </h4>
                        <div class="info-grid">
                          <div class="info-item">
                            <label>Username</label>
                            <div class="info-value">{{ u.username }}</div>
                          </div>
                          <div class="info-item">
                            <label>Email</label>
                            <div class="info-value">{{ u.email }}</div>
                          </div>
                          <div class="info-item">
                            <label>Vai trò</label>
                            <div class="info-value">{{ joinRoles(u.role_names) }}</div>
                          </div>
                          <div class="info-item">
                            <label>Trạng thái</label>
                            <div class="info-value">{{ u.status || '-' }}</div>
                          </div>
                        </div>
                      </div>

                      <div class="detail-section">
                        <h4 class="section-title">
                          <i class="bi bi-link-45deg"></i>
                          Liên kết
                        </h4>
                        <div class="info-grid">
                          <div class="info-item">
                            <label>Loại tài khoản</label>
                            <div class="info-value">{{ u.account_type || '-' }}</div>
                          </div>
                          <div class="info-item">
                            <label>Linked Staff ID</label>
                            <div class="info-value"><code>{{ u.linked_staff_id || '-' }}</code></div>
                          </div>
                          <div class="info-item">
                            <label>Linked Doctor ID</label>
                            <div class="info-value"><code>{{ u.linked_doctor_id || '-' }}</code></div>
                          </div>
                          <div class="info-item">
                            <label>Linked Patient ID</label>
                            <div class="info-value"><code>{{ u.linked_patient_id || '-' }}</code></div>
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
                            <div class="info-value"><code>{{ u._id || u.id || '-' }}</code></div>
                          </div>
                          <div class="info-item">
                            <label>Revision</label>
                            <div class="info-value"><code>{{ u._rev || '-' }}</code></div>
                          </div>
                          <div class="info-item">
                            <label>Tạo lúc</label>
                            <div class="info-value">{{ fmtDateTime(u.created_at) }}</div>
                          </div>
                          <div class="info-item">
                            <label>Cập nhật</label>
                            <div class="info-value">{{ fmtDateTime(u.updated_at) }}</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            </template>

            <tr v-if="!items.length" class="empty-row">
              <td colspan="8">
                <div class="empty-state">
                  <i class="bi bi-inbox"></i>
                  <h3>Không có dữ liệu</h3>
                  <p>Chưa có người dùng nào trong hệ thống</p>
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
            <span class="total-info">({{ total }} người dùng)</span>
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
    <div v-if="showForm" class="modal-backdrop" @mousedown.self="closeForm">
      <div class="modal-card">
        <h2 class="h5 mb-3">{{ editingId ? 'Sửa thông tin người dùng' : 'Thêm người dùng' }}</h2>

        <form @submit.prevent="save">
          <div class="section-title">Thông tin cơ bản</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Username <span class="text-danger">*</span></label>
              <input v-model.trim="form.username" type="text" class="form-control" required />
            </div>
            <div class="col-md-6">
              <label class="form-label">Email <span class="text-danger">*</span></label>
              <input v-model.trim="form.email" type="email" class="form-control" required />
            </div>

            <div class="col-md-6" v-if="!editingId">
              <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
              <input v-model="form.password" type="password" class="form-control" required
                     placeholder="Nhập mật khẩu cho tài khoản mới" />
            </div>
            <div class="col-md-6" v-else>
              <label class="form-label">Mật khẩu mới</label>
              <input v-model="form.newPassword" type="password" class="form-control"
                     placeholder="Bỏ trống nếu không đổi mật khẩu" />
            </div>

            <div class="col-md-6">
              <label class="form-label">Vai trò <span class="text-danger">*</span></label>
              <select v-model="form.role" class="form-select" required @change="onRoleChange">
                <option value="">-- chọn vai trò --</option>
                <option value="admin">admin</option>
                <option value="doctor">doctor</option>
                <option value="nurse">nurse</option>
                <option value="receptionist">receptionist</option>
                <option value="patient">patient</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Loại tài khoản <small class="text-muted">(auto từ vai trò)</small></label>
              <select v-model="form.account_type" class="form-select" disabled>
                <option value="staff">staff</option>
                <option value="doctor">doctor</option>
                <option value="patient">patient</option>
              </select>
            </div>
          </div>

          <div class="section-title">
            Liên kết
            <small class="text-muted">
              ({{ form.role === 'doctor' ? 'Bác sĩ' :
                   form.role === 'patient' ? 'Bệnh nhân' :
                   'Nhân viên' }})
            </small>
          </div>

          <div class="row g-3">
            <!-- Staff combobox - cho admin, nurse, receptionist -->
            <div class="col-md-12" v-if="form.account_type === 'staff'">
              <label class="form-label">
                Linked Staff
                <small class="text-muted">(cho {{ form.role || 'admin/nurse/receptionist' }})</small>
              </label>
              <select v-model="form.linked_staff_id" class="form-select">
                <option value="">-- chọn Staff chưa liên kết --</option>
                <option v-for="s in unlinked.staffs" :key="s.id" :value="s.id">
                  {{ s.code ? `${s.code} - ${s.name}` : s.name }}
                </option>
              </select>
            </div>

            <!-- Doctor combobox - chỉ cho doctor -->
            <div class="col-md-12" v-else-if="form.account_type === 'doctor'">
              <label class="form-label">
                Linked Doctor
                <small class="text-muted">(cho vai trò doctor)</small>
              </label>
              <select v-model="form.linked_doctor_id" class="form-select">
                <option value="">-- chọn Doctor chưa liên kết --</option>
                <option v-for="d in unlinked.doctors" :key="d.id" :value="d.id">
                  {{ d.code ? `${d.code} - ${d.name}` : d.name }}
                </option>
              </select>
            </div>

            <!-- Patient combobox - chỉ cho patient -->
            <div class="col-md-12" v-else-if="form.account_type === 'patient'">
              <label class="form-label">
                Linked Patient
                <small class="text-muted">(cho vai trò patient)</small>
              </label>
              <select v-model="form.linked_patient_id" class="form-select">
                <option value="">-- chọn Patient chưa liên kết --</option>
                <option v-for="p in unlinked.patients" :key="p.id" :value="p.id">
                  {{ p.code ? `${p.code} - ${p.name}` : p.name }}
                </option>
              </select>
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
import UserService from '@/api/userService'

export default {
  name: 'UsersListView',
  data () {
    return {
      items: [],
      total: 0,
      q: '',
      page: 1,
      pageSize: 50,
      hasMore: false,
      currentPage: 1,
      itemsPerPage: 10,
      loading: false,
      error: '',
      // modal
      showForm: false,
      saving: false,
      editingId: null,
      form: this.emptyForm(),
      // expand
      expanded: {},
      // unlinked lists for combobox
      unlinked: {
        staffs: [],
        doctors: [],
        patients: []
      }
    }
  },
  computed: {
    isEdit () { return !!this.editingId },
    paginatedItems () {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.items.slice(start, end)
    },
    totalPages () {
      return Math.ceil(this.total / this.itemsPerPage) || 1
    }
  },
  created () { this.fetch() },
  methods: {
    emptyForm () {
      return {
        _id: null,
        _rev: null,
        username: '',
        email: '',
        password: '',
        newPassword: '',
        role: '',
        role_names: [],
        account_type: 'staff',
        linked_staff_id: '',
        linked_doctor_id: '',
        linked_patient_id: '',
        status: 'active'
      }
    },

    // ================= Helpers =================
    joinRoles (v) { return Array.isArray(v) ? v.join(', ') : (v || '-') },
    linkedAny (row) {
      if (row.account_type === 'doctor') return row.linked_doctor_id || '-'
      if (row.account_type === 'patient') return row.linked_patient_id || '-'
      return row.linked_staff_id || '-'
    },
    fmtDateTime (v) { if (!v) return '-'; try { return new Date(v).toLocaleString('vi-VN') } catch { return v } },
    fmtDate (v) { if (!v) return '-'; try { return new Date(v).toLocaleDateString('vi-VN') } catch { return v } },

    rowId (row) { return row._id || row.id || row.username },
    isExpanded (row) { return !!this.expanded[this.rowId(row)] },
    toggleRow (row) {
      const id = this.rowId(row)
      this.expanded = { ...this.expanded, [id]: !this.expanded[id] }
    },

    // => RÚT MẢNG TỪ NHIỀU KIỂU RESPONSE
    arrFromResponse (res) {
      if (!res) return []
      if (Array.isArray(res)) return res
      if (Array.isArray(res.items)) return res.items
      if (Array.isArray(res.data)) return res.data
      if (res.rows && Array.isArray(res.rows)) return res.rows.map(r => r.doc || r.value || r)
      if (res.data?.items && Array.isArray(res.data.items)) return res.data.items
      if (Array.isArray(res.result)) return res.result
      if (res.result?.items && Array.isArray(res.result.items)) return res.result.items
      return []
    },

    // ======== Unlinked helpers (lọc FE) ========
    _isUnlinked (x) { return !x?.userId && !x?.linkedUserId && !x?.hasAccount },
    _pick (o, keys, fb) { for (const k of keys) if (o && o[k] != null && o[k] !== '') return o[k]; return fb },
    _toItems (arr, ids, codes, names) {
      return (arr || []).map(x => ({
        id: this._pick(x, ids, x.id || x._id),
        code: this._pick(x, codes, ''),
        name: this._pick(x, names, x.fullName || x.name || `#${x.id || x._id}`)
      }))
    },

    // ======== NẠP COMBOBOX - SỬ DỤNG ENDPOINTS MỚI ========
    async _loadUnlinkedStaffs () {
      try {
        const res = await UserService.getAvailableStaffs({ limit: 1000 })
        const rawAll = this.arrFromResponse(res)
        this.unlinked.staffs = this._toItems(rawAll, ['id', 'staffId', '_id'], ['code', 'staffCode'], ['fullName', 'name', 'displayName'])
      } catch (e) {
        console.error('Load available staffs failed', e)
        this.unlinked.staffs = []
      }
    },
    async _loadUnlinkedDoctors () {
      try {
        const res = await UserService.getAvailableDoctors({ limit: 1000 })
        const rawAll = this.arrFromResponse(res)
        this.unlinked.doctors = this._toItems(rawAll, ['id', 'doctorId', '_id'], ['licenseNumber', 'code'], ['fullName', 'name', 'displayName'])
      } catch (e) {
        console.error('Load available doctors failed', e)
        this.unlinked.doctors = []
      }
    },
    async _loadUnlinkedPatients () {
      try {
        const res = await UserService.getAvailablePatients({ limit: 1000 })
        const rawAll = this.arrFromResponse(res)
        this.unlinked.patients = this._toItems(rawAll, ['id', 'patientId', '_id'], ['patientCode', 'code'], ['fullName', 'name', 'displayName'])
      } catch (e) {
        console.error('Load available patients failed', e)
        this.unlinked.patients = []
      }
    },

    async onAccountTypeChange () {
      // reset các field liên kết
      this.form.linked_staff_id = ''
      this.form.linked_doctor_id = ''
      this.form.linked_patient_id = ''
      // nạp danh sách phù hợp (không param)
      if (this.form.account_type === 'staff') await this._loadUnlinkedStaffs()
      else if (this.form.account_type === 'doctor') await this._loadUnlinkedDoctors()
      else if (this.form.account_type === 'patient') await this._loadUnlinkedPatients()
    },

    // ================= Pagination =================
    getPageNumbers () {
      const total = this.totalPages
      const current = this.currentPage
      const pages = []
      if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i)
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) pages.push(i)
          pages.push('...')
          pages.push(total)
        } else if (current >= total - 3) {
          pages.push(1)
          pages.push('...')
          for (let i = total - 4; i <= total; i++) pages.push(i)
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
    goToPage (page) { this.currentPage = page },
    nextPage () { if (this.currentPage < this.totalPages) this.currentPage++ },
    prevPage () { if (this.currentPage > 1) this.currentPage-- },

    // ================= API =================
    async fetch () {
      this.loading = true
      this.error = ''
      try {
        const skip = (this.page - 1) * this.pageSize
        const params = {
          limit: this.pageSize,
          offset: skip,
          skip
        }

        if (this.q?.trim()) {
          params.username = this.q.trim()
        }

        const res = await UserService.list(params)

        // normalize response
        let items = []; let total = 0; let offset = null
        if (res && Array.isArray(res.rows)) {
          items = (res.rows || []).map(r => r.doc || r.value || r)
          total = res.total_rows ?? items.length
          offset = res.offset ?? 0
        } else if (res && res.data && Array.isArray(res.data)) {
          items = res.data; total = res.total ?? items.length
        } else if (Array.isArray(res)) {
          items = res; total = res.length
        }

        this.items = items.map(d => ({
          ...d,
          role_names: Array.isArray(d.role_names) ? d.role_names : (d.role ? [d.role] : [])
        }))
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
      this.currentPage = 1
      const kw = (this.q || '').trim().toLowerCase()
      if (!kw) {
        this.fetch()
        return
      }
      // Filter client-side
      this.items = this.items.filter(u => {
        const username = (u.username || '').toLowerCase()
        const email = (u.email || '').toLowerCase()
        return username.includes(kw) || email.includes(kw)
      })
      this.total = this.items.length
    },

    reload () {
      this.q = ''
      this.page = 1
      this.currentPage = 1
      this.fetch()
    },

    changePageSize () { this.page = 1; this.fetch() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },

    // ================= CRUD =================
    openCreate () {
      this.editingId = null
      this.form = this.emptyForm()
      this.showForm = true
      this.onAccountTypeChange()
    },

    openEdit (row) {
      this.editingId = row._id || row.id || row.username
      this.form = {
        _id: row._id,
        _rev: row._rev,
        username: row.username || '',
        email: row.email || '',
        password: '',
        newPassword: '',
        role: Array.isArray(row.role_names) ? (row.role_names[0] || '') : (row.role || ''),
        role_names: Array.isArray(row.role_names) ? row.role_names : (row.role ? [row.role] : []),
        account_type: row.account_type || 'staff',
        linked_staff_id: row.linked_staff_id || '',
        linked_doctor_id: row.linked_doctor_id || '',
        linked_patient_id: row.linked_patient_id || '',
        status: row.status || 'active'
      }
      this.showForm = true
      this.onAccountTypeChange()
    },

    closeForm () {
      if (!this.saving) this.showForm = false
    },

    async save () {
      if (this.saving) return

      // Validate required fields
      if (!this.form.username?.trim()) {
        alert('Username là bắt buộc')
        return
      }
      if (!this.form.email?.trim()) {
        alert('Email là bắt buộc')
        return
      }
      if (!this.form.role?.trim()) {
        alert('Vai trò là bắt buộc')
        return
      }
      if (!this.editingId && !this.form.password?.trim()) {
        alert('Mật khẩu là bắt buộc khi tạo mới')
        return
      }

      // Check for duplicate email or username (only when creating new)
      if (!this.editingId) {
        const username = this.form.username.trim().toLowerCase()
        const email = this.form.email.trim().toLowerCase()
        const duplicate = this.items.find(u =>
          (u.username && u.username.trim().toLowerCase() === username) ||
          (u.email && u.email.trim().toLowerCase() === email)
        )
        if (duplicate) {
          alert('Không được trùng username hoặc email với tài khoản khác!')
          return
        }
      }

      this.saving = true
      try {
        const payload = {
          type: 'user',
          username: this.form.username.trim(),
          email: this.form.email.trim(),
          role_names: this.form.role ? [this.form.role] : [],
          account_type: this.form.account_type,
          linked_staff_id: this.form.account_type === 'staff' ? (this.form.linked_staff_id || undefined) : undefined,
          linked_doctor_id: this.form.account_type === 'doctor' ? (this.form.linked_doctor_id || undefined) : undefined,
          linked_patient_id: this.form.account_type === 'patient' ? (this.form.linked_patient_id || undefined) : undefined,
          status: this.form.status
        }

        // Password handling
        if (!this.editingId && this.form.password?.trim()) {
          payload.password = this.form.password.trim()
        }
        if (this.editingId && this.form.newPassword?.trim()) {
          payload.password = this.form.newPassword.trim()
        }

        // CouchDB fields
        if (this.form._id) payload._id = this.form._id
        if (this.form._rev) payload._rev = this.form._rev

        console.log('Payload being sent:', payload)

        if (this.editingId) {
          await UserService.update(this.editingId, payload)
        } else {
          await UserService.create(payload)
        }

        this.showForm = false
        await this.fetch()
      } catch (e) {
        console.error('Save error details:', e)
        console.error('Response data:', e?.response?.data)
        alert(e?.response?.data?.message || e?.message || 'Lưu thất bại')
      } finally {
        this.saving = false
      }
    },

    async remove (row) {
      if (!confirm(`Xóa người dùng "${row.username}"?`)) return

      try {
        const id = row._id || row.id
        if (!id) {
          alert('Không tìm thấy ID người dùng')
          return
        }

        const rev = row._rev
        if (!rev) {
          alert('Không tìm thấy revision của document')
          return
        }

        await UserService.remove(id, rev)
        alert('Xóa thành công!')
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Xóa thất bại')
      }
    },

    // ✅ THÊM: Auto mapping role → account_type
    getRoleMapping (role) {
      const mapping = {
        doctor: 'doctor',
        patient: 'patient',
        admin: 'staff',
        nurse: 'staff',
        receptionist: 'staff'
      }
      return mapping[role] || 'staff'
    },

    // ✅ SỬA: onRoleChange - auto update account_type khi đổi role
    onRoleChange () {
      // Auto update account_type dựa vào role
      this.form.account_type = this.getRoleMapping(this.form.role)

      // Reset các field liên kết
      this.form.linked_staff_id = ''
      this.form.linked_doctor_id = ''
      this.form.linked_patient_id = ''

      // Load danh sách tương ứng
      this.onAccountTypeChange()
    },

    goHome () {
      this.$router.push('/')
    }
  }
}
</script>

<style scoped>
.user-management {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
  padding: 0;
}

/* Header Section */
.header-section {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  border-radius: 0;
  padding: 24px 32px;
  margin-bottom: 0;
  box-shadow: 0 4px 20px rgba(59, 130, 246, 0.3);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 24px;
}

.header-left .page-title {
  color: white;
  font-size: 32px;
  font-weight: 700;
  margin: 0 0 8px 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-left .page-title i {
  font-size: 36px;
}

.header-left .page-subtitle {
  color: rgba(255, 255, 255, 0.9);
  font-size: 16px;
  margin: 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.stats-badge {
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  color: white;
  padding: 10px 16px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  font-size: 14px;
}

.stats-badge i {
  font-size: 18px;
}

.btn-action {
  padding: 10px 20px;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.btn-action i {
  font-size: 16px;
}

.btn-action:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-refresh {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  padding: 10px;
}

.btn-refresh:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(180deg);
}

.btn-primary {
  background: white;
  color: #3b82f6;
}

.btn-primary:hover:not(:disabled) {
  background: #f0f9ff;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
}

.btn-back {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.btn-back:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.3);
  transform: translateX(-2px);
}

/* Search Section */
.search-section {
  background: white;
  border-radius: 0;
  padding: 16px 24px;
  margin-bottom: 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.search-container {
  max-width: 600px;
  margin: 0;
}

.search-input-group {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 4px 4px 4px 16px;
  transition: all 0.3s ease;
}

.search-input-group:focus-within {
  border-color: #3b82f6;
  background: white;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-icon {
  color: #94a3b8;
  font-size: 18px;
}

.search-input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 12px 8px;
  font-size: 15px;
  outline: none;
  color: #1e293b;
}

.search-input::placeholder {
  color: #94a3b8;
}

.search-btn {
  padding: 10px 24px;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
}

.search-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

/* Content Section */
.content-section {
  background: white;
  border-radius: 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

/* Alerts */
.alert {
  padding: 16px 20px;
  border-radius: 12px;
  margin: 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 14px;
  font-weight: 500;
}

.alert i {
  font-size: 20px;
}

.alert-error {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

/* Loading State */
.loading-state {
  padding: 60px 20px;
  text-align: center;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Table Container */
.table-container {
  overflow-x: auto;
}

.user-table {
  width: 100%;
  border-collapse: collapse;
}

.user-table thead {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
}

.user-table th {
  padding: 16px;
  text-align: left;
  font-weight: 600;
  font-size: 14px;
  white-space: nowrap;
}

.user-table th.col-number { width: 60px; text-align: center; }
.user-table th.col-actions { width: 140px; text-align: center; }

.user-table tbody tr.user-row {
  border-bottom: 1px solid #e2e8f0;
  transition: all 0.3s ease;
}

.user-table tbody tr.user-row:hover {
  background: #f8fafc;
}

.user-table tbody tr.user-row.expanded {
  background: #eff6ff;
}

.user-table td {
  padding: 16px;
  font-size: 14px;
  color: #475569;
}

.cell-number {
  text-align: center;
}

.row-number {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border-radius: 8px;
  font-weight: 600;
  font-size: 13px;
}

.user-username {
  color: #1e293b;
  font-weight: 600;
}

.role-badge {
  display: inline-block;
  padding: 6px 12px;
  background: #eff6ff;
  color: #3b82f6;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
}

.status-active {
  background: #dcfce7;
  color: #16a34a;
}

.status-inactive {
  background: #fee2e2;
  color: #dc2626;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 6px;
  justify-content: center;
}

.action-btn {
  padding: 8px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
}

.action-btn i {
  font-size: 14px;
}

.view-btn {
  background: #eff6ff;
  color: #3b82f6;
}

.view-btn:hover {
  background: #dbeafe;
  transform: translateY(-2px);
}

.edit-btn {
  background: #fef3c7;
  color: #f59e0b;
}

.edit-btn:hover {
  background: #fde68a;
  transform: translateY(-2px);
}

.delete-btn {
  background: #fee2e2;
  color: #dc2626;
}

.delete-btn:hover:not(:disabled) {
  background: #fecaca;
  transform: translateY(-2px);
}

.delete-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Detail Row */
.detail-row .detail-cell {
  background: #f8fafc;
  padding: 0;
}

.user-detail-card {
  margin: 16px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.detail-header {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  padding: 16px 24px;
}

.detail-title {
  color: white;
  font-size: 18px;
  font-weight: 600;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.detail-title i {
  font-size: 20px;
}

.detail-content {
  padding: 24px;
}

.detail-section {
  margin-bottom: 24px;
}

.detail-section:last-child {
  margin-bottom: 0;
}

.section-title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 16px 0;
  padding-bottom: 10px;
  border-bottom: 2px solid #3b82f6;
  display: flex;
  align-items: center;
  gap: 8px;
}

.section-title i {
  color: #3b82f6;
  font-size: 18px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.info-item label {
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-value {
  font-size: 14px;
  color: #0f172a;
  font-weight: 600;
  word-break: break-all;
}

.info-value code {
  background: #dbeafe;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 13px;
  color: #1e40af;
  font-family: 'Courier New', monospace;
  font-weight: 600;
}

/* Empty State */
.empty-row td {
  padding: 0;
}

.empty-state {
  padding: 60px 20px;
  text-align: center;
  color: #94a3b8;
}

.empty-state i {
  font-size: 64px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-state h3 {
  font-size: 20px;
  font-weight: 600;
  margin: 16px 0 8px 0;
  color: #64748b;
}

.empty-state p {
  font-size: 14px;
  margin: 0;
}

/* Pagination Section */
.pagination-section {
  padding: 24px;
  border-top: 1px solid #e2e8f0;
}

.pagination-info-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 16px;
  font-size: 14px;
  color: #64748b;
}

.page-info, .total-info {
  display: flex;
  align-items: center;
  gap: 6px;
}

.pagination-controls-center {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
}

.pagination-btn {
  width: 36px;
  height: 36px;
  border: 2px solid #e2e8f0;
  background: white;
  color: #64748b;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.pagination-btn:hover:not(:disabled) {
  border-color: #3b82f6;
  color: #3b82f6;
  transform: translateY(-1px);
}

.pagination-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 6px;
}

.page-number-btn {
  min-width: 36px;
  height: 36px;
  padding: 0 12px;
  border: 2px solid #e2e8f0;
  background: white;
  color: #64748b;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.3s ease;
}

.page-number-btn:hover:not(:disabled):not(.active) {
  border-color: #3b82f6;
  color: #3b82f6;
}

.page-number-btn.active {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border-color: transparent;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.page-number-btn.ellipsis {
  border: none;
  background: transparent;
  cursor: default;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
  overflow-y: auto;
}

.modal-container {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-header-custom {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  padding: 24px 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title-custom {
  color: white;
  font-size: 24px;
  font-weight: 700;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.modal-close-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.modal-close-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

.modal-body-custom {
  padding: 32px;
  overflow-y: auto;
  flex: 1;
}

.form-section {
  margin-bottom: 32px;
}

.form-section:last-child {
  margin-bottom: 0;
}

.form-section-title {
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 20px 0;
  padding-bottom: 12px;
  border-bottom: 2px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.form-section-title i {
  color: #3b82f6;
  font-size: 20px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label-custom {
  font-size: 14px;
  font-weight: 600;
  color: #475569;
  display: flex;
  align-items: center;
  gap: 6px;
}

.form-label-custom i {
  color: #3b82f6;
  font-size: 16px;
}

.text-required {
  color: #dc2626;
}

.form-label-hint {
  font-size: 12px;
  font-weight: 400;
  color: #94a3b8;
  font-style: italic;
}

.form-input-custom {
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  color: #1e293b;
  transition: all 0.3s ease;
  background: white;
}

.form-input-custom:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input-custom:disabled {
  background: #f1f5f9;
  cursor: not-allowed;
}

.form-input-custom::placeholder {
  color: #94a3b8;
}

/* Roles Checkbox */
.roles-checkbox-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
  background: #f8fafc;
  padding: 16px;
  border-radius: 12px;
  border: 2px solid #e2e8f0;
  max-height: 240px;
  overflow-y: auto;
}

.role-checkbox-item {
  display: flex;
  align-items: center;
  gap: 10px;
}

.role-checkbox-input {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #3b82f6;
}

.role-checkbox-label {
  font-size: 14px;
  color: #475569;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  user-select: none;
}

.role-checkbox-label i {
  color: #3b82f6;
  font-size: 14px;
}

.empty-roles {
  grid-column: 1 / -1;
  text-align: center;
  color: #94a3b8;
  padding: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

/* Modal Footer */
.modal-footer-custom {
  padding: 20px 32px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.btn-modal-cancel {
  padding: 12px 24px;
  background: white;
  color: #64748b;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
}

.btn-modal-cancel:hover:not(:disabled) {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.btn-modal-save {
  padding: 12px 24px;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
}

.btn-modal-save:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(59, 130, 246, 0.4);
}

.btn-modal-cancel:disabled,
.btn-modal-save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
  .user-management {
    padding: 16px;
  }

  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .header-actions {
    width: 100%;
    flex-wrap: wrap;
  }

  .form-grid,
  .info-grid {
    grid-template-columns: 1fr;
  }

  .pagination-controls-center {
    flex-wrap: wrap;
  }

  .modal-container {
    margin: 0;
    max-height: 100vh;
    border-radius: 0;
  }
}
</style>
