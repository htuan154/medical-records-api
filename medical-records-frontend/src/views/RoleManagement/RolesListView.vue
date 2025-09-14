<template>
  <section class="container py-4">
    <div class="d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0">Quản lý Vai trò</h1>
      <div class="d-flex gap-2 align-items-center">
        <span class="text-muted me-2">Tổng: {{ total }}</span>
        <select v-model.number="pageSize" class="form-select" style="width:120px" @change="changePageSize" :disabled="loading">
          <option :value="10">10 / trang</option>
          <option :value="25">25 / trang</option>
          <option :value="50">50 / trang</option>
          <option :value="100">100 / trang</option>
        </select>
        <button class="btn btn-outline-secondary" @click="reload" :disabled="loading">Tải lại</button>
        <button class="btn btn-primary" @click="openCreate" :disabled="loading">+ Thêm mới</button>
      </div>
    </div>

    <div class="mt-3">
      <div class="input-group mb-3" style="max-width:520px">
        <input
          v-model.trim="q"
          class="form-control"
          placeholder="Tìm theo tên vai trò / mã..."
          @keyup.enter="search"
        />
        <button class="btn btn-outline-secondary" @click="search">Tìm</button>
      </div>

      <div v-if="error" class="alert alert-danger">{{ error }}</div>
      <div v-if="loading" class="text-muted">Đang tải danh sách…</div>

      <template v-else>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:56px">#</th>
                <th>Mã vai trò</th>
                <th>Tên hiển thị</th>
                <th>Mô tả</th>
                <th>Số quyền</th>
                <th>Trạng thái</th>
                <th style="width:180px">Hành động</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(r, idx) in items" :key="r._id || r.id || idx">
                <tr>
                  <td>{{ idx + 1 + (page - 1) * pageSize }}</td>
                  <td>
                    <code class="text-primary">{{ r.name || '-' }}</code>
                  </td>
                  <td>
                    <strong>{{ r.display_name || r.displayName || '-' }}</strong>
                  </td>
                  <td>
                    <div class="text-truncate" style="max-width: 200px;" :title="r.description">
                      {{ r.description || '-' }}
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-info">
                      {{ (r.permissions || []).length }} quyền
                    </span>
                  </td>
                  <td>
                    <span :class="['badge', r.status === 'active' ? 'bg-success' : 'bg-secondary']">
                      {{ r.status === 'active' ? 'Hoạt động' : 'Không hoạt động' }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group">
                      <button class="btn btn-sm btn-outline-info" @click="toggle(r)">
                        {{ expandedId === (r._id || r.id) ? 'Ẩn' : 'Xem' }}
                      </button>
                      <button class="btn btn-sm btn-outline-primary" @click="openEdit(r)">
                        Sửa
                      </button>
                      <button class="btn btn-sm btn-outline-danger" @click="remove(r)" :disabled="loading || isSystemRole(r)">
                        Xóa
                      </button>
                    </div>
                  </td>
                </tr>

                <!-- Hàng chi tiết -->
                <tr v-if="expandedId === (r._id || r.id)">
                  <td :colspan="7">
                    <div class="detail-grid">
                      <div>
                        <h6 class="text-muted">Thông tin cơ bản</h6>
                        <div><strong>Mã vai trò:</strong> <code>{{ r.name || '-' }}</code></div>
                        <div><strong>Tên hiển thị:</strong> {{ r.display_name || r.displayName || '-' }}</div>
                        <div><strong>Trạng thái:</strong>
                          <span :class="['badge', r.status === 'active' ? 'bg-success' : 'bg-secondary']">
                            {{ r.status === 'active' ? 'Hoạt động' : 'Không hoạt động' }}
                          </span>
                        </div>
                        <div><strong>Mô tả:</strong> {{ r.description || '-' }}</div>
                      </div>
                      <div>
                        <h6 class="text-muted">Quyền hạn ({{ (r.permissions || []).length }})</h6>
                        <div class="permissions-grid">
                          <template v-for="perm in (r.permissions || [])" :key="perm">
                            <span class="badge bg-light text-dark border permission-badge">
                              {{ formatPermission(perm) }}
                            </span>
                          </template>
                          <div v-if="!(r.permissions || []).length" class="text-muted">Không có quyền nào</div>
                        </div>
                      </div>
                      <div>
                        <h6 class="text-muted">Thời gian</h6>
                        <div><strong>Tạo lúc:</strong> {{ fmtDateTime(r.created_at || r.createdAt) }}</div>
                        <div><strong>Cập nhật:</strong> {{ fmtDateTime(r.updated_at || r.updatedAt) }}</div>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>

              <tr v-if="!items.length">
                <td colspan="7" class="text-center text-muted">Không có dữ liệu</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center">
          <div>Trang {{ page }} / {{ Math.ceil(total / pageSize) || 1 }}</div>
          <div class="btn-group">
            <button class="btn btn-outline-secondary" @click="prev" :disabled="page <= 1">‹ Trước</button>
            <button class="btn btn-outline-secondary" @click="next" :disabled="!hasMore">Sau ›</button>
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
          rows = payload.rows.map(r => r.doc || r.value || r)
        } else if (Array.isArray(payload.items)) {
          rows = payload.items
        } else if (Array.isArray(payload.data)) {
          rows = payload.data
        }
      }

      const items = rows.filter(item => item && item.type === 'role')
      const total = payload?.total_rows ?? payload?.total ?? items.length

      return { items, total: Number(total) || 0 }
    },

    async fetch () {
      this.loading = true
      this.error = ''
      try {
        const skip = (this.page - 1) * this.pageSize
        const res = await RoleService.list({
          q: this.q || undefined,
          limit: this.pageSize,
          skip
        })
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
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },
    changePageSize () { this.page = 1; this.fetch() },

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
.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
  padding: 12px 8px;
  border-top: 1px dashed #e5e7eb;
}

.permissions-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}

.permission-badge {
  font-size: 0.75rem;
  cursor: pointer;
  user-select: none;
}

.permission-badge:hover {
  opacity: 0.8;
}

.badge {
  font-weight: 500;
}

/* Modal styles */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.5);
  display: grid;
  place-items: center;
  z-index: 1050;
  overflow-y: auto;
  padding: 20px;
}

.modal-card {
  width: min(1000px, 95vw);
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 12px 30px rgba(0,0,0,.18);
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body {
  padding: 24px;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: end;
  gap: 12px;
}

.btn-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #6b7280;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
}

.btn-close:hover {
  background: #f3f4f6;
  color: #374151;
}

.form-section {
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid #f3f4f6;
}

.form-section:last-child {
  margin-bottom: 0;
  border-bottom: none;
}

.section-title {
  color: #374151;
  font-weight: 600;
  margin-bottom: 16px;
  padding-bottom: 8px;
  border-bottom: 2px solid #e5e7eb;
}

/* Permission selector styles */
.permissions-selector {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
}

.permission-category {
  background: #fff;
  border-radius: 6px;
  padding: 12px;
  border: 1px solid #e9ecef;
}

.category-title {
  color: #495057;
  font-weight: 600;
  margin-bottom: 12px;
  padding-bottom: 6px;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  align-items: center;
}

.permission-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-check {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 4px 0;
}

.form-check-input {
  margin-right: 8px;
}

.form-check-label {
  font-size: 0.9rem;
  cursor: pointer;
}

.selected-permissions {
  background: #fff;
  border-radius: 6px;
  padding: 12px;
  border: 1px solid #dee2e6;
}
</style>
