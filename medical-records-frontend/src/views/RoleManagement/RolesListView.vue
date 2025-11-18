<template>
  <section class="roles-management">
    <!-- Header Section -->
    <div class="header-section">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="bi bi-people-fill"></i>
            Quản lý Vai trò
          </h1>
          <p class="page-subtitle">Quản lý các vai trò và quyền hạn trong hệ thống</p>
        </div>
        <div class="header-actions">
      <button class="btn-action btn-back" @click="goHome" title="Quay lại Trang chủ">
        <i class="bi bi-arrow-left"></i>
      </button>
          <div class="stats-badge">
            <i class="bi bi-bar-chart-fill"></i>
            <span>Tổng: <strong>{{ total }}</strong></span>
          </div>
          <div class="page-size-selector">
            <select v-model.number="pageSize" @change="changePageSize" :disabled="loading">
              <option :value="10">10 / trang</option>
              <option :value="25">25 / trang</option>
              <option :value="50">50 / trang</option>
              <option :value="100">100 / trang</option>
            </select>
          </div>
          <button class="btn-action btn-refresh" @click="reloadPage" :disabled="loading" title="Tải lại">
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
            placeholder="Tìm theo tên vai trò, mã vai trò..."
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
          <table class="roles-table">
            <thead>
              <tr>
                <th class="col-number">#</th>
                <th class="col-code">Mã vai trò</th>
                <th class="col-name">Tên hiển thị</th>
                <th class="col-description">Mô tả</th>
                <th class="col-permissions">Quyền hạn</th>
                <th class="col-status">Trạng thái</th>
                <th class="col-actions">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(r, idx) in items" :key="r._id || r.id || idx">
                <tr class="role-row" :class="{ 'expanded': expandedId === (r._id || r.id) }">
                  <td class="cell-number">
                    <span class="row-number">{{ idx + 1 + (page - 1) * pageSize }}</span>
                  </td>
                  <td class="cell-code">
                    <div class="role-code">
                      <i class="bi bi-tag-fill"></i>
                      <code>{{ r.name || '-' }}</code>
                    </div>
                  </td>
                  <td class="cell-name">
                    <div class="role-name">
                      <strong>{{ r.display_name || r.displayName || '-' }}</strong>
                    </div>
                  </td>
                  <td class="cell-description">
                    <div class="role-description" :title="r.description">
                      {{ r.description || '-' }}
                    </div>
                  </td>
                  <td class="cell-permissions">
                    <div class="permissions-count">
                      <i class="bi bi-shield-check"></i>
                      <span>{{ (r.permissions || []).length }} quyền</span>
                    </div>
                  </td>
                  <td class="cell-status">
                    <span class="status-badge" :class="r.status === 'active' ? 'status-active' : 'status-inactive'">
                      <i :class="r.status === 'active' ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill'"></i>
                      {{ r.status === 'active' ? 'Hoạt động' : 'Không hoạt động' }}
                    </span>
                  </td>
                  <td class="cell-actions">
                    <div class="action-buttons">
                      <button
                        class="action-btn view-btn"
                        @click="toggle(r)"
                        :title="expandedId === (r._id || r.id) ? 'Ẩn chi tiết' : 'Xem chi tiết'"
                      >
                        <i :class="expandedId === (r._id || r.id) ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                      </button>
                      <button
                        class="action-btn edit-btn"
                        @click="openEdit(r)"
                        title="Chỉnh sửa"
                      >
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button
                        class="action-btn delete-btn"
                        @click="remove(r)"
                        :disabled="loading || isSystemRole(r)"
                        :title="isSystemRole(r) ? 'Không thể xóa vai trò hệ thống' : 'Xóa vai trò'"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>

                <!-- Detail Row -->
                <tr v-if="expandedId === (r._id || r.id)" class="detail-row">
                  <td colspan="7" class="detail-cell">
                    <div class="role-detail-card">
                      <div class="detail-header">
                        <h4 class="detail-title">
                          <i class="bi bi-info-circle"></i>
                          Chi tiết vai trò: {{ r.display_name || r.displayName }}
                        </h4>
                      </div>

                      <div class="detail-content">
                        <div class="detail-section">
                          <h5 class="section-title">
                            <i class="bi bi-info-square"></i>
                            Thông tin cơ bản
                          </h5>
                          <div class="info-grid">
                            <div class="info-item">
                              <label>Mã vai trò:</label>
                              <span class="info-value">
                                <code>{{ r.name || '-' }}</code>
                              </span>
                            </div>
                            <div class="info-item">
                              <label>Tên hiển thị:</label>
                              <span class="info-value">{{ r.display_name || r.displayName || '-' }}</span>
                            </div>
                            <div class="info-item full-width">
                              <label>Mô tả:</label>
                              <span class="info-value">{{ r.description || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Trạng thái:</label>
                              <span class="status-badge" :class="r.status === 'active' ? 'status-active' : 'status-inactive'">
                                <i :class="r.status === 'active' ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill'"></i>
                                {{ r.status === 'active' ? 'Hoạt động' : 'Không hoạt động' }}
                              </span>
                            </div>
                          </div>
                        </div>

                        <div class="detail-section">
                          <h5 class="section-title">
                            <i class="bi bi-shield-check"></i>
                            Quyền hạn ({{ (r.permissions || []).length }})
                          </h5>
                          <div class="permissions-display">
                            <template v-if="(r.permissions || []).length > 0">
                              <div class="permission-badge" v-for="perm in r.permissions" :key="perm">
                                <i class="bi bi-check-circle"></i>
                                {{ formatPermission(perm) }}
                              </div>
                            </template>
                            <div v-else class="no-permissions">
                              <i class="bi bi-exclamation-circle"></i>
                              Chưa có quyền nào được gán
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
                              <label>Tạo lúc:</label>
                              <span class="info-value">{{ fmtDateTime(r.created_at || r.createdAt) }}</span>
                            </div>
                            <div class="info-item">
                              <label>Cập nhật:</label>
                              <span class="info-value">{{ fmtDateTime(r.updated_at || r.updatedAt) }}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>

              <tr v-if="!items.length" class="empty-row">
                <td colspan="7" class="empty-cell">
                  <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h3>Không có dữ liệu</h3>
                    <p>Chưa có vai trò nào được tạo hoặc không tìm thấy kết quả phù hợp</p>
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
              Trang <b>{{ page }} / {{ Math.ceil(total / pageSize) || 1 }}</b>
              <span class="total-info">- Hiển thị {{ items.length }} trong tổng số {{ total }} vai trò</span>
            </span>
          </div>
          <div class="pagination-controls-center">
            <button
              class="pagination-btn prev-btn"
              @click="prev"
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
              @click="next"
              :disabled="!hasMore"
              title="Trang sau"
            >
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </div>
      </template>
    </div>

    <!-- Modal CRUD -->
    <div v-if="showModal" class="modal-backdrop" @mousedown.self="close">
      <div class="modal-card">
        <div class="modal-header">
          <h2 class="h5 mb-0">{{ editingId ? 'Sửa thông tin vai trò' : 'Thêm vai trò mới' }}</h2>
          <button class="btn-close" @click="close" :disabled="saving">×</button>
        </div>

        <form @submit.prevent="save" class="modal-body">
          <!-- Thông tin cơ bản -->
          <div class="form-section">
            <h6 class="section-title">Thông tin cơ bản</h6>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Mã vai trò <span class="text-danger">*</span></label>
                <input
                  v-model.trim="form.name"
                  class="form-control"
                  :disabled="!!editingId"
                  placeholder="role_name (không dấu, không khoảng trắng)"
                  required
                  pattern="[a-zA-Z0-9_]+"
                  title="Chỉ chấp nhận chữ, số và dấu gạch dưới"
                />
                <div class="form-text">Mã vai trò không thể thay đổi sau khi tạo</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Tên hiển thị <span class="text-danger">*</span></label>
                <input v-model.trim="form.display_name" class="form-control" required placeholder="Tên hiển thị cho người dùng" />
              </div>
              <div class="col-12 mb-3">
                <label class="form-label">Mô tả</label>
                <textarea v-model.trim="form.description" class="form-control" rows="3" placeholder="Mô tả vai trò và trách nhiệm"></textarea>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Trạng thái</label>
                <select v-model="form.status" class="form-select">
                  <option value="active">Hoạt động</option>
                  <option value="inactive">Không hoạt động</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Quyền hạn -->
          <div class="form-section">
            <h6 class="section-title">Quyền hạn</h6>
            <div class="permissions-selector">
              <div class="row">
                <template v-for="category in permissionCategories" :key="category.name">
                  <div class="col-md-6 col-lg-4 mb-4">
                    <div class="permission-category">
                      <h6 class="category-title">
                        <input
                          type="checkbox"
                          :checked="isCategorySelected(category)"
                          @change="toggleCategory(category)"
                          class="form-check-input me-2"
                        />
                        {{ category.label }}
                      </h6>
                      <div class="permission-list">
                        <template v-for="perm in category.permissions" :key="perm.value">
                          <label class="form-check">
                            <input
                              type="checkbox"
                              :value="perm.value"
                              v-model="form.permissions"
                              class="form-check-input"
                            />
                            <span class="form-check-label">{{ perm.label }}</span>
                          </label>
                        </template>
                      </div>
                    </div>
                  </div>
                </template>
              </div>

              <div class="selected-permissions mt-3">
                <h6>Quyền đã chọn ({{ form.permissions.length }}):</h6>
                <div class="permissions-grid">
                  <template v-for="perm in form.permissions" :key="perm">
                    <span class="badge bg-primary permission-badge" @click="removePermission(perm)">
                      {{ formatPermission(perm) }} ×
                    </span>
                  </template>
                  <div v-if="!form.permissions.length" class="text-muted">Chưa chọn quyền nào</div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" @click="close" :disabled="saving">Hủy</button>
            <button class="btn btn-primary" type="submit" :disabled="saving">
              <span v-if="saving">
                <span class="spinner-border spinner-border-sm me-1"></span>
                Đang lưu...
              </span>
              <span v-else>Lưu</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script>
import RoleService from '@/api/roleService'

export default {
  name: 'RolesListView',
  data () {
    return {
      items: [],
      total: 0,
      page: 1,
      pageSize: 25,
      q: '',
      loading: false,
      error: '',
      hasMore: false,
      expandedId: null,
      showModal: false,
      editingId: null,
      saving: false,
      form: this.getEmptyForm(),

      // Danh sách quyền theo danh mục
      permissionCategories: [
        {
          name: 'user',
          label: 'Người dùng',
          permissions: [
            { value: 'user:read', label: 'Xem danh sách' },
            { value: 'user:write', label: 'Thêm/sửa/xóa' }
          ]
        },
        {
          name: 'role',
          label: 'Vai trò',
          permissions: [
            { value: 'role:read', label: 'Xem danh sách' },
            { value: 'role:write', label: 'Thêm/sửa/xóa' }
          ]
        },
        {
          name: 'patient',
          label: 'Bệnh nhân',
          permissions: [
            { value: 'patient:read', label: 'Xem thông tin' },
            { value: 'patient:write', label: 'Thêm/sửa/xóa' }
          ]
        },
        {
          name: 'doctor',
          label: 'Bác sĩ',
          permissions: [
            { value: 'doctor:read', label: 'Xem danh sách' },
            { value: 'doctor:write', label: 'Thêm/sửa/xóa' }
          ]
        },
        {
          name: 'appointment',
          label: 'Lịch hẹn',
          permissions: [
            { value: 'appointment:read', label: 'Xem lịch hẹn' },
            { value: 'appointment:write', label: 'Đặt/sửa/hủy' }
          ]
        },
        {
          name: 'record',
          label: 'Hồ sơ bệnh án',
          permissions: [
            { value: 'record:read', label: 'Xem hồ sơ' },
            { value: 'record:write', label: 'Tạo/sửa hồ sơ' }
          ]
        },
        {
          name: 'treatment',
          label: 'Điều trị',
          permissions: [
            { value: 'treatment:read', label: 'Xem phác đồ' },
            { value: 'treatment:write', label: 'Kê đơn/phác đồ' }
          ]
        },
        {
          name: 'test',
          label: 'Xét nghiệm',
          permissions: [
            { value: 'test:read', label: 'Xem kết quả' },
            { value: 'test:write', label: 'Chỉ định/cập nhật' }
          ]
        },
        {
          name: 'invoice',
          label: 'Hóa đơn',
          permissions: [
            { value: 'invoice:read', label: 'Xem hóa đơn' },
            { value: 'invoice:write', label: 'Tạo/sửa hóa đơn' }
          ]
        }
      ]
    }
  },
  created () { this.fetch() },
  methods: {
    goHome () {
      this.$router.push({ name: 'home' })
    },
    getEmptyForm () {
      return {
        name: '',
        display_name: '',
        description: '',
        permissions: [],
        status: 'active'
      }
    },

    // Chuẩn hóa dữ liệu từ API
    normalize (res) {
      const payload = (res && typeof res === 'object' && 'data' in res) ? res.data : res
      let rows = []

      if (Array.isArray(payload)) {
        rows = payload
      } else if (payload && typeof payload === 'object') {
        if (Array.isArray(payload.rows)) {
          // CouchDB format: extract doc from rows
          rows = payload.rows.map(row => row.doc || row)
        } else if (Array.isArray(payload.items)) {
          rows = payload.items
        } else if (Array.isArray(payload.data)) {
          rows = payload.data
        }
      }

      // Lọc các document có type === 'role'
      let items = rows.filter(item => item && item.type === 'role')

      // Nếu có từ khóa tìm kiếm, lọc trên cả name và display_name
      if (this.q) {
        const qLower = this.q.trim().toLowerCase()
        items = items.filter(item => {
          const name = (item.name || '').toLowerCase()
          const display = (item.display_name || item.displayName || '').toLowerCase()
          return name.includes(qLower) || display.includes(qLower)
        })
      }
      const total = payload?.total_rows ?? payload?.total ?? items.length

      return { items, total: Number(total) || 0 }
    },

    async fetch () {
      this.loading = true
      this.error = ''
      try {
        const skip = (this.page - 1) * this.pageSize
        // Luôn lấy tất cả vai trò, chỉ lọc trên giao diện
        const params = {
          limit: this.pageSize,
          skip
        }
        const res = await RoleService.list(params)
        const meta = this.normalize(res)
        this.items = meta.items
        this.total = meta.total
        this.hasMore = (skip + this.items.length) < this.total
      } catch (e) {
        console.error(e)
        this.error = e?.response?.data?.message || e?.message || 'Không tải được danh sách vai trò'
      } finally {
        this.loading = false
      }
    },

    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },
    reloadPage () { window.location.reload() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },
    changePageSize () { this.page = 1; this.fetch() },

    // Pagination helpers
    getPageNumbers () {
      const totalPages = Math.ceil(this.total / this.pageSize) || 1
      const current = this.page
      const pages = []

      if (totalPages <= 7) {
        // Hiển thị tất cả nếu ít hơn 8 trang
        for (let i = 1; i <= totalPages; i++) {
          pages.push(i)
        }
      } else {
        // Logic phức tạp hơn cho nhiều trang
        if (current <= 4) {
          pages.push(1, 2, 3, 4, 5, '...', totalPages)
        } else if (current >= totalPages - 3) {
          pages.push(1, '...', totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages)
        } else {
          pages.push(1, '...', current - 1, current, current + 1, '...', totalPages)
        }
      }
      return pages
    },

    goToPage (pageNum) {
      if (pageNum !== '...' && pageNum !== this.page) {
        this.page = pageNum
        this.fetch()
      }
    },

    toggle (row) {
      const id = row._id || row.id
      this.expandedId = (this.expandedId === id) ? null : id
    },

    // Kiểm tra vai trò hệ thống không được xóa
    isSystemRole (role) {
      const systemRoles = ['role_admin', 'role_doctor', 'role_nurse', 'role_patient', 'role_receptionist']
      return systemRoles.includes(role._id || role.name)
    },

    // CRUD functions
    openCreate () {
      this.editingId = null
      this.form = this.getEmptyForm()
      this.showModal = true
    },

    openEdit (row) {
      this.editingId = row._id || row.id
      this.form = {
        ...this.getEmptyForm(),
        name: row.name || '',
        display_name: row.display_name || row.displayName || '',
        description: row.description || '',
        permissions: [...(row.permissions || [])],
        status: row.status || 'active',
        _rev: row._rev
      }
      this.showModal = true
    },

    close () {
      if (!this.saving) {
        this.showModal = false
        this.editingId = null
        this.form = this.getEmptyForm()
      }
    },

    async save () {
      if (this.saving) return
      this.saving = true
      try {
        const payload = {
          type: 'role',
          ...this.form,
          created_at: this.editingId ? undefined : new Date().toISOString(),
          updated_at: new Date().toISOString()
        }

        if (this.editingId) {
          await RoleService.update(this.editingId, payload)
        } else {
          payload._id = `role_${this.form.name}`
          await RoleService.create(payload)
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
      if (this.isSystemRole(row)) {
        alert('Không thể xóa vai trò hệ thống!')
        return
      }

      if (!confirm(`Xóa vai trò "${row.display_name || row.name}"?\nLưu ý: Điều này có thể ảnh hưởng đến người dùng đang có vai trò này.`)) return

      try {
        const id = row._id || row.id
        await RoleService.remove(id)
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Xóa thất bại')
      }
    },

    // Permission management
    formatPermission (perm) {
      const [resource, action] = perm.split(':')
      const resourceMap = {
        user: 'Người dùng',
        role: 'Vai trò',
        patient: 'Bệnh nhân',
        doctor: 'Bác sĩ',
        appointment: 'Lịch hẹn',
        record: 'Hồ sơ',
        treatment: 'Điều trị',
        test: 'Xét nghiệm',
        invoice: 'Hóa đơn'
      }
      const actionMap = {
        read: 'Xem',
        write: 'Ghi'
      }
      return `${resourceMap[resource] || resource}: ${actionMap[action] || action}`
    },

    isCategorySelected (category) {
      return category.permissions.every(p => this.form.permissions.includes(p.value))
    },

    toggleCategory (category) {
      const allSelected = this.isCategorySelected(category)
      if (allSelected) {
        // Bỏ chọn tất cả quyền trong danh mục
        category.permissions.forEach(p => {
          const index = this.form.permissions.indexOf(p.value)
          if (index > -1) this.form.permissions.splice(index, 1)
        })
      } else {
        // Chọn tất cả quyền trong danh mục
        category.permissions.forEach(p => {
          if (!this.form.permissions.includes(p.value)) {
            this.form.permissions.push(p.value)
          }
        })
      }
    },

    removePermission (perm) {
      const index = this.form.permissions.indexOf(perm)
      if (index > -1) this.form.permissions.splice(index, 1)
    },

    // Helper methods
    fmtDateTime (v) {
      if (!v) return '-'
      try {
        return new Date(v).toLocaleString('vi-VN')
      } catch {
        return v
      }
    }
  }
}
</script>

<style scoped>
@import 'bootstrap-icons/font/bootstrap-icons.css';

/* Main Container */
.roles-management {
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

.page-size-selector select {
  background: #fff;
  border: 1.5px solid #3b82f6;
  color: #1d4ed8;
  padding: 0.5rem 1.2rem;
  border-radius: 10px;
  font-weight: 600;
  min-width: 120px;
  font-size: 1.1rem;
  box-shadow: 0 0 0 2px #e0e7ef;
  transition: border 0.2s, box-shadow 0.2s;
}

.page-size-selector select:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 2px #93c5fd;
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
  background: #fff !important;
  color: #2563eb !important;
  border: 1.5px solid #3b82f6 !important;
  border-radius: 10px !important;
  font-weight: 700 !important;
  box-shadow: none !important;
  transition: all 0.2s !important;
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

.btn-primary:hover:not(:disabled) {
  background: #2563eb !important;
  color: #fff !important;
  border-color: #2563eb !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.12) !important;
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

.roles-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
}

.roles-table thead {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.roles-table th {
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
.col-code { width: 150px; }
.col-name { width: 200px; }
.col-description { width: 250px; }
.col-permissions { width: 120px; text-align: center; }
.col-status { width: 130px; text-align: center; }
.col-actions { width: 140px; text-align: center; }

.role-row {
  transition: all 0.3s ease;
  border-bottom: 1px solid #f1f5f9;
}

.role-row:hover {
  background: #f8fafc;
}

.role-row.expanded {
  background: #eff6ff;
}

.roles-table td {
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

.role-code {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.role-code i {
  color: #3b82f6;
}

.role-code code {
  background: #eff6ff;
  color: #1d4ed8;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.85rem;
}

.role-name strong {
  color: #1e293b;
  font-weight: 600;
}

.role-description {
  max-width: 250px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: #64748b;
}

.permissions-count {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: #f0f9ff;
  color: #0369a1;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  border: 1px solid #bae6fd;
}

.permissions-count i {
  font-size: 1rem;
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

.role-detail-card {
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

.permissions-display {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.permission-badge {
  background: #f0f9ff;
  color: #0369a1;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border: 1px solid #bae6fd;
}

.permission-badge i {
  color: #10b981;
}

.no-permissions {
  color: #64748b;
  font-style: italic;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px dashed #cbd5e1;
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

.pagination-info {
  text-align: center;
  color: #64748b;
}

.page-info {
  font-weight: 600;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.95rem;
  margin-bottom: 0.25rem;
}

.total-info {
  font-size: 0.85rem;
  color: #64748b;
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
  width: 2.25rem;
  height: 2.25rem;
  border: 1.5px solid #e2e8f0;
  background: white;
  color: #64748b;
  border-radius: 50%;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  cursor: pointer;
  font-size: 0.9rem;
}

.pagination-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border-color: #3b82f6;
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
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

.page-number-btn:hover:not(:disabled):not(.ellipsis) {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border-color: #3b82f6;
  color: #1d4ed8;
  transform: scale(1.05);
}

.page-number-btn.active {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border-color: #1d4ed8;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  transform: scale(1.1);
}

.page-number-btn.ellipsis {
  border: none;
  background: transparent;
  cursor: default;
  color: #64748b;
  font-weight: 400;
}

/* Modal styles (keeping existing modal styles) */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1050;
  overflow-y: auto;
  padding: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.btn-close:hover {
  background: #f3f4f6;
  color: #374151;
  transform: scale(1.1);
}

.form-section {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #f1f5f9;
}

.form-section:last-child {
  margin-bottom: 0;
  border-bottom: none;
}

.form-section .section-title {
  color: #374151;
  font-weight: 700;
  font-size: 1.1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e5e7eb;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-section .section-title i {
  color: #3b82f6;
}

/* Form Controls */
.form-label {
  color: #374151;
  font-weight: 600;
  margin-bottom: 0.5rem;
  display: block;
  font-size: 0.9rem;
}

.form-control, .form-select {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: white;
}

.form-control:focus, .form-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-text {
  color: #64748b;
  font-size: 0.85rem;
  margin-top: 0.25rem;
}

/* Permission selector styles */
.permissions-selector {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #e2e8f0;
}

.permission-category {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  border: 1px solid #e5e7eb;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.category-title {
  color: #374151;
  font-weight: 600;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  font-size: 0.95rem;
}

.permission-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-check {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: background 0.2s ease;
}

.form-check:hover {
  background: #f8fafc;
}

.form-check-input {
  margin-right: 0.75rem;
  width: 1.1rem;
  height: 1.1rem;
}

.form-check-label {
  font-size: 0.9rem;
  cursor: pointer;
  color: #374151;
}

.selected-permissions {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  border: 1px solid #e5e7eb;
  margin-top: 1rem;
}

.selected-permissions h6 {
  color: #374151;
  font-weight: 600;
  margin-bottom: 1rem;
  font-size: 1rem;
}

.selected-permissions .permissions-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.selected-permissions .permission-badge {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.selected-permissions .permission-badge:hover {
  transform: scale(0.95);
  opacity: 0.8;
}

/* Button Styles for Modal */
.modal-footer .btn {
  padding: 0.75rem 2rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.95rem;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-footer .btn-outline-secondary {
  background: white;
  color: #64748b;
  border: 2px solid #e5e7eb;
}

.modal-footer .btn-outline-secondary:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.modal-footer .btn-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.modal-footer .btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
}

.spinner-border {
  width: 1rem;
  height: 1rem;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 0.75s linear infinite;
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

  .roles-table {
    font-size: 0.85rem;
  }

  .roles-table th,
  .roles-table td {
    padding: 1rem 0.5rem;
  }

  .action-buttons {
    flex-direction: column;
    gap: 0.25rem;
  }

  .pagination-section {
    padding: 1rem 0.75rem;
    gap: 0.75rem;
    margin-top: 1rem;
  }

  .pagination-controls-center {
    flex-wrap: wrap;
    justify-content: center;
    padding: 0.5rem;
    gap: 0.25rem;
  }

  .pagination-btn {
    width: 2rem;
    height: 2rem;
    font-size: 0.8rem;
  }

  .page-number-btn {
    width: 1.75rem;
    height: 1.75rem;
    font-size: 0.75rem;
  }

  .page-numbers {
    margin: 0 0.25rem;
    gap: 0.15rem;
  }
}

/* Modal styles */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: grid;
  place-items: center;
  z-index: 1050;
  overflow-y: auto;
  padding: 1rem;
}

.modal-card {
  width: min(1000px, 95vw);
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  max-height: 90vh;
  overflow-y: auto;
  margin: 0;
  transform: none;
  animation: modalSlideIn 0.3s ease forwards;
}

@keyframes modalSlideIn {
  to {
    transform: scale(1);
  }
}

.modal-header {
  padding: 2rem 2.5rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.modal-header h2 {
  color: #374151;
  font-weight: 700;
  margin: 0;
}

.modal-body {
  padding: 2rem 2.5rem;
}

.modal-footer {
  padding: 1.5rem 2.5rem 2rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: end;
  gap: 1rem;
  background: #f8fafc;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.btn-close:hover {
  background: #f3f4f6;
  color: #374151;
  transform: scale(1.1);
}

.form-section {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #f1f5f9;
}

.form-section:last-child {
  margin-bottom: 0;
  border-bottom: none;
}

.form-section .section-title {
  color: #374151;
  font-weight: 700;
  margin-bottom: 1.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e5e7eb;
}

/* Permission selector styles */
.permissions-selector {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #e2e8f0;
}

.permission-category {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  border: 1px solid #e9ecef;
  margin-bottom: 1rem;
}

.category-title {
  color: #495057;
  font-weight: 700;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  align-items: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.category-title:hover {
  color: #3b82f6;
}

.permission-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-check {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 0.25rem 0;
  border-radius: 6px;
  transition: all 0.3s ease;
}

.form-check:hover {
  background: #f8fafc;
}

.form-check-input {
  margin-right: 0.75rem;
  cursor: pointer;
}

.form-check-label {
  font-size: 0.9rem;
  cursor: pointer;
  color: #374151;
}

.selected-permissions {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  border: 1px solid #dee2e6;
  margin-top: 1rem;
}

.selected-permissions h6 {
  color: #374151;
  font-weight: 700;
  margin-bottom: 0.75rem;
}

@media (max-width: 768px) {
  .roles-management {
    padding: 0;
  }

  .header-section {
    padding: 1.5rem 1rem;
  }

  .header-content {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .header-actions {
    flex-wrap: wrap;
    justify-content: center;
  }

  .search-section {
    padding: 1.5rem 1rem;
  }

  .content-section {
    padding: 1.5rem 1rem;
  }

  .table-container {
    overflow-x: auto;
  }

  .action-buttons {
    flex-direction: column;
    gap: 0.25rem;
  }

  .pagination-section {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .modal-card {
    width: 95vw;
    margin: 1rem;
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 1.5rem 1rem;
  }
}
</style>
