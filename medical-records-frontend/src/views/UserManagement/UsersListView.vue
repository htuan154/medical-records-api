<template>
  <section class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0">Quản lý Người dùng</h1>
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

    <!-- Tools -->
    <div class="mt-3 d-flex justify-content-between align-items-center">
      <div class="input-group" style="max-width: 440px">
        <input v-model.trim="q" class="form-control" placeholder="Tìm theo username / email ..." @keyup.enter="search" />
        <button class="btn btn-outline-secondary" @click="search">Tìm</button>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error" class="alert alert-danger my-3">{{ error }}</div>

    <!-- Table -->
    <div class="table-responsive mt-3">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th style="width:56px">#</th>
            <th>Username</th>
            <th>Email</th>
            <th>Vai trò</th>
            <th>Loại TK</th>
            <th>Liên kết</th>
            <th>Trạng thái</th>
            <th>Tạo lúc</th>
            <th>Cập nhật</th>
            <th style="width:180px">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="(u, idx) in items" :key="u._id || u.id || u.username || idx">
            <tr>
              <td>{{ idx + 1 + (page - 1) * pageSize }}</td>
              <td>{{ u.username }}</td>
              <td>{{ u.email }}</td>
              <td>{{ joinRoles(u.role_names) }}</td>
              <td>{{ u.account_type || '-' }}</td>
              <td>{{ linkedAny(u) }}</td>
              <td>
                <span :class="['badge', u.status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary']">
                  {{ u.status || '-' }}
                </span>
              </td>
              <td>{{ fmtDateTime(u.created_at) }}</td>
              <td>{{ fmtDateTime(u.updated_at) }}</td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary" @click="toggleRow(u)">{{ isExpanded(u) ? 'Ẩn' : 'Xem' }}</button>
                  <button class="btn btn-sm btn-outline-primary" @click="openEdit(u)">Sửa</button>
                  <button class="btn btn-sm btn-outline-danger" @click="remove(u)" :disabled="loading">Xóa</button>
                </div>
              </td>
            </tr>

            <!-- Row details (giống mẫu Bệnh nhân) -->
            <tr v-if="isExpanded(u)" class="row-detail">
              <td :colspan="10">
                <div class="detail-sections">
                  <div class="detail-title">Thông tin tài khoản</div>
                  <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Username:</span> <span class="detail-value">{{ u.username }}</span></div>
                    <div class="detail-item"><span class="detail-label">Email:</span> <span class="detail-value">{{ u.email }}</span></div>
                    <div class="detail-item"><span class="detail-label">Vai trò:</span> <span class="detail-value">{{ joinRoles(u.role_names) }}</span></div>
                    <div class="detail-item"><span class="detail-label">Trạng thái:</span> <span class="detail-value">{{ u.status || '-' }}</span></div>
                  </div>

                  <div class="detail-title">Liên kết</div>
                  <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Loại tài khoản:</span> <span class="detail-value">{{ u.account_type || '-' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Linked Staff Id:</span> <span class="detail-value">{{ u.linked_staff_id || '-' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Linked Doctor Id:</span> <span class="detail-value">{{ u.linked_doctor_id || '-' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Linked Patient Id:</span> <span class="detail-value">{{ u.linked_patient_id || '-' }}</span></div>
                  </div>

                  <div class="detail-title">Khác</div>
                  <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">ID:</span> <span class="detail-value">{{ u._id || u.id || '-' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Rev:</span> <span class="detail-value">{{ u._rev || '-' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Tạo lúc:</span> <span class="detail-value">{{ fmtDateTime(u.created_at) }}</span></div>
                    <div class="detail-item"><span class="detail-label">Cập nhật:</span> <span class="detail-value">{{ fmtDateTime(u.updated_at) }}</span></div>
                  </div>
                </div>
              </td>
            </tr>
          </template>

          <tr v-if="!items.length">
            <td colspan="10" class="text-center text-muted">Không có dữ liệu</td>
          </tr>
        </tbody>
      </table>

      <div class="d-flex justify-content-between align-items-center">
        <div>Trang {{ page }} / {{ Math.max(1, Math.ceil((total || 0) / pageSize)) }}</div>
        <div class="btn-group">
          <button class="btn btn-outline-secondary" @click="prev" :disabled="page <= 1 || loading">‹ Trước</button>
          <button class="btn btn-outline-secondary" @click="next" :disabled="!hasMore || loading">Sau ›</button>
        </div>
      </div>
    </div>

    <!-- Modal Thêm/Sửa -->
    <div v-if="showModal" class="modal-backdrop" @mousedown.self="close">
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
              <label class="form-label">Mật khẩu (tạo mới)</label>
              <input v-model="form.password" type="text" class="form-control" placeholder="Để trống nếu không đổi" />
            </div>
            <div class="col-md-6" v-else>
              <label class="form-label">Mật khẩu mới (khi sửa)</label>
              <input v-model="form.newPassword" type="text" class="form-control" placeholder="Bỏ trống nếu không đổi" />
            </div>

            <div class="col-md-6">
              <label class="form-label">Vai trò</label>
              <select v-model="form.role" class="form-select">
                <option value="">-- chọn --</option>
                <option value="admin">admin</option>
                <option value="doctor">doctor</option>
                <option value="nurse">nurse</option>
                <option value="receptionist">receptionist</option>
                <option value="patient">patient</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Loại tài khoản</label>
              <select v-model="form.account_type" class="form-select">
                <option value="staff">staff</option>
                <option value="doctor">doctor</option>
                <option value="patient">patient</option>
              </select>
            </div>
          </div>

          <div class="section-title">Liên kết</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Linked Staff Id</label>
              <input v-model.trim="form.linked_staff_id" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Linked Doctor Id</label>
              <input v-model.trim="form.linked_doctor_id" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Linked Patient Id</label>
              <input v-model.trim="form.linked_patient_id" class="form-control" />
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
      loading: false,
      error: '',
      // modal
      showModal: false,
      saving: false,
      editingId: null,
      form: this.emptyForm(),
      // expand
      expanded: {}
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

    // --- UI helpers
    joinRoles (v) { return Array.isArray(v) ? v.join(', ') : (v || '-') },
    linkedAny (row) {
      if (row.account_type === 'doctor') return row.linked_doctor_id || '-'
      if (row.account_type === 'patient') return row.linked_patient_id || '-'
      return row.linked_staff_id || '-'
    },
    fmtDateTime (v) { if (!v) return '-'; try { return new Date(v).toLocaleString() } catch { return v } },

    rowId (row) { return row._id || row.id || row.username },
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
        const res = await UserService.list({
          q: this.q || undefined,
          limit: this.pageSize,
          offset: skip,
          skip
        })
        // normalize CouchDB / Laravel
        let items = []; let total = 0; let offset = null
        if (res && Array.isArray(res.rows)) {
          items = (res.rows || []).map(r => r.doc || r.value || r)
          total = res.total_rows ?? items.length
          offset = res.offset ?? 0
        } else if (res && res.data && Array.isArray(res.data)) {
          items = res.data; total = res.total ?? items.length
        } else if (Array.isArray(res)) { items = res; total = res.length }

        // map tối thiểu
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
    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },
    changePageSize () { this.page = 1; this.fetch() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },

    // --- CRUD
    openCreate () {
      this.editingId = null
      this.form = this.emptyForm()
      this.showModal = true
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
      this.showModal = true
    },
    close () { if (!this.saving) this.showModal = false },

    async save () {
      if (this.saving) return
      this.saving = true
      try {
        const payload = {
          type: 'user',
          username: this.form.username,
          email: this.form.email,
          role_names: this.form.role ? [this.form.role] : (Array.isArray(this.form.role_names) ? this.form.role_names : []),
          account_type: this.form.account_type,
          linked_staff_id: this.form.linked_staff_id || undefined,
          linked_doctor_id: this.form.linked_doctor_id || undefined,
          linked_patient_id: this.form.linked_patient_id || undefined,
          status: this.form.status
        }
        if (!this.editingId && this.form.password) payload.password = this.form.password
        if (this.editingId && this.form.newPassword) payload.password = this.form.newPassword
        if (this.form._id) payload._id = this.form._id
        if (this.form._rev) payload._rev = this.form._rev

        if (this.editingId) {
          await UserService.update(this.editingId, payload)
        } else {
          await UserService.create(payload)
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
      if (!confirm(`Xóa người dùng "${row.username}"?`)) return
      try {
        const id = row._id || row.id || row.username
        await UserService.remove(id)
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Xóa thất bại')
      }
    }
  }
}
</script>

<style scoped>
/* table */
:deep(table.table) th, :deep(table.table) td { vertical-align: middle; }

/* row detail style (giống mẫu bệnh nhân) */
.row-detail td { background: #fff; }
.detail-sections { border-top: 1px solid #e5e7eb; padding: 12px 10px 6px; }
.detail-title { font-weight: 700; color: #111827; margin: 8px 0; }
.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 8px 16px;
}
.detail-item { line-height: 1.6; }
.detail-label { font-weight: 700; }
.detail-value { margin-left: 6px; }

/* Modal */
.modal-backdrop{ position: fixed; inset: 0; background: rgba(0,0,0,.45); display: grid; place-items: center; z-index: 1050; }
.modal-card{ width: min(940px, 96vw); background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 20px 50px rgba(0,0,0,.25); max-height: 92vh; overflow: auto; }
.section-title{ font-weight: 600; margin: 14px 0 8px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; }
</style>
