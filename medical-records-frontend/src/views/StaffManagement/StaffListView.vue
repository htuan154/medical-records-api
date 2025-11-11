<template>
  <section class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0">Quản lý Nhân viên</h1>
      <div class="d-flex gap-2 align-items-center">
        <span class="text-muted me-2">Tổng: {{ total }}</span>
        <select v-model.number="pageSize" class="form-select" style="width:120px" @change="changePageSize" :disabled="loading">
          <option :value="10">10 / trang</option>
          <option :value="25">25 / trang</option>
          <option :value="50">50 / trang</option>
          <option :value="100">100 / trang</option>
        </select>
  <button class="btn btn-outline-secondary" @click="refreshPage" :disabled="loading">Tải lại</button>
        <button class="btn btn-primary" @click="openCreate" :disabled="loading">+ Thêm mới</button>
      </div>
    </div>

    <!-- Tools -->
    <div class="mt-3 d-flex justify-content-between align-items-center">
      <div class="input-group" style="max-width: 440px">
        <input v-model.trim="q" class="form-control" placeholder="Tìm theo tên / email / SĐT ..." @keyup.enter="search" />
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
            <th>Họ tên</th>
            <th>Loại NV</th>
            <th>Giới tính</th>
            <th>SĐT</th>
            <th>Email</th>
            <th>Phòng ban</th>
            <th>Ca làm</th>
            <th>Trạng thái</th>
            <th style="width:180px">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="(s, idx) in items" :key="s._id || s.id || idx">
            <tr>
              <td>{{ idx + 1 + (page - 1) * pageSize }}</td>
              <td>{{ s.full_name }}</td>
              <td>{{ renderStaffType(s.staff_type) }}</td>
              <td>{{ renderGender(s.gender) }}</td>
              <td>{{ s.phone || '-' }}</td>
              <td>{{ s.email || '-' }}</td>
              <td>{{ s.department || '-' }}</td>
              <td>{{ renderShift(s.shift) }}</td>
              <td>
                <span :class="['badge', s.status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary']">
                  {{ s.status || '-' }}
                </span>
              </td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary" @click="toggleRow(s)">{{ isExpanded(s) ? 'Ẩn' : 'Xem' }}</button>
                  <button class="btn btn-sm btn-outline-primary" @click="openEdit(s)">Sửa</button>
                  <button class="btn btn-sm btn-outline-danger" @click="remove(s)" :disabled="loading">Xóa</button>
                </div>
              </td>
            </tr>

            <!-- Row details -->
            <tr v-if="isExpanded(s)" class="row-detail">
              <td :colspan="10">
                <div class="detail-sections">
                  <div class="detail-title">Thông tin liên hệ</div>
                  <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Họ tên:</span> <span class="detail-value">{{ s.full_name }}</span></div>
                    <div class="detail-item"><span class="detail-label">Giới tính:</span> <span class="detail-value">{{ renderGender(s.gender) }}</span></div>
                    <div class="detail-item"><span class="detail-label">Điện thoại:</span> <span class="detail-value">{{ s.phone || '-' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Email:</span> <span class="detail-value">{{ s.email || '-' }}</span></div>
                  </div>

                  <div class="detail-title">Công việc</div>
                  <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Loại nhân viên:</span> <span class="detail-value">{{ renderStaffType(s.staff_type) }}</span></div>
                    <div class="detail-item"><span class="detail-label">Phòng ban:</span> <span class="detail-value">{{ s.department || '-' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Ngày làm việc:</span> <span class="detail-value">{{ renderShiftDays(s.shift?.days) }}</span></div>
                    <div class="detail-item"><span class="detail-label">Giờ làm:</span> <span class="detail-value">{{ renderShiftTime(s.shift) }}</span></div>
                  </div>

                  <div class="detail-title">Khác</div>
                  <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">ID:</span> <span class="detail-value">{{ s._id || s.id || '-' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Rev:</span> <span class="detail-value">{{ s._rev || '-' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Tạo lúc:</span> <span class="detail-value">{{ fmtDateTime(s.created_at) }}</span></div>
                    <div class="detail-item"><span class="detail-label">Cập nhật:</span> <span class="detail-value">{{ fmtDateTime(s.updated_at) }}</span></div>
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
      ]
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
