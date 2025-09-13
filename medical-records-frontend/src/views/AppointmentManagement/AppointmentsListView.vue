
<template>
  <section class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h4 mb-1">Quản lý Lịch hẹn</h1>
        <p class="text-muted mb-0">Đặt lịch, duyệt, hủy, đổi lịch hẹn.</p>
      </div>
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

    <!-- Filters -->
    <div class="mt-3 row g-2">
      <div class="col-12 col-md-3">
        <input v-model.trim="filters.patient_id" class="form-control" placeholder="patient_id" @keyup.enter="search" />
      </div>
      <div class="col-12 col-md-3">
        <input v-model.trim="filters.doctor_id" class="form-control" placeholder="doctor_id" @keyup.enter="search" />
      </div>
      <div class="col-12 col-md-2">
        <select v-model="filters.status" class="form-select">
          <option value="">-- Trạng thái --</option>
          <option value="scheduled">scheduled</option>
          <option value="completed">completed</option>
          <option value="cancelled">cancelled</option>
          <option value="no_show">no_show</option>
        </select>
      </div>
      <div class="col-12 col-md-2">
        <input v-model="filters.start_local" type="datetime-local" class="form-control" placeholder="start" />
      </div>
      <div class="col-12 col-md-2">
        <div class="input-group">
          <input v-model="filters.end_local" type="datetime-local" class="form-control" placeholder="end" />
          <button class="btn btn-outline-secondary" @click="search">Tìm</button>
        </div>
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
            <th>Mã</th>
            <th>Patient</th>
            <th>Doctor</th>
            <th>Thời gian</th>
            <th>Thời lượng</th>
            <th>Loại</th>
            <th>Ưu tiên</th>
            <th>Trạng thái</th>
            <th style="width:200px">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="(a, idx) in items" :key="a._id || a.id || idx">
            <tr>
              <td>{{ idx + 1 + (page - 1) * pageSize }}</td>
              <td>{{ a._id || a.id }}</td>
              <td>{{ a.patient_id }}</td>
              <td>{{ a.doctor_id }}</td>
              <td>{{ fmtDateTime(a.appointment_info?.scheduled_date) }}</td>
              <td>{{ a.appointment_info?.duration ?? '-' }} phút</td>
              <td>{{ a.appointment_info?.type || '-' }}</td>
              <td>{{ a.appointment_info?.priority || '-' }}</td>
              <td>
                <span :class="['badge', badgeClass(a.status)]">{{ a.status || '-' }}</span>
              </td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary" @click="toggleRow(a)">{{ isExpanded(a) ? 'Ẩn' : 'Xem' }}</button>
                  <button class="btn btn-sm btn-outline-primary" @click="openEdit(a)">Sửa</button>
                  <button class="btn btn-sm btn-outline-danger" @click="remove(a)" :disabled="loading">Xóa</button>
                </div>
              </td>
            </tr>

            <!-- Row details -->
            <tr v-if="isExpanded(a)" class="row-detail">
              <td :colspan="10">
                <div class="detail-sections">
                  <div class="detail-title">Chi tiết lịch hẹn</div>
                  <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Thời gian:</span> <span class="detail-value">{{ fmtDateTime(a.appointment_info?.scheduled_date) }}</span></div>
                    <div class="detail-item"><span class="detail-label">Thời lượng:</span> <span class="detail-value">{{ a.appointment_info?.duration ?? '-' }} phút</span></div>
                    <div class="detail-item"><span class="detail-label">Loại:</span> <span class="detail-value">{{ a.appointment_info?.type || '-' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Ưu tiên:</span> <span class="detail-value">{{ a.appointment_info?.priority || '-' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Lý do:</span> <span class="detail-value">{{ a.reason || '-' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Ghi chú:</span> <span class="detail-value">{{ a.notes || '-' }}</span></div>
                  </div>

                  <div class="detail-title">Nhắc lịch</div>
                  <div class="detail-grid">
                    <div class="detail-item" v-for="(r,i) in (a.reminders || [])" :key="i">
                      <span class="detail-label">•</span>
                      <span class="detail-value">{{ r.type }} — {{ fmtDateTime(r.sent_at) }} ({{ r.status }})</span>
                    </div>
                    <div v-if="!a.reminders || !a.reminders.length" class="text-muted">Không có nhắc lịch</div>
                  </div>

                  <div class="detail-title">Khác</div>
                  <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Tạo lúc:</span> <span class="detail-value">{{ fmtDateTime(a.created_at) }}</span></div>
                    <div class="detail-item"><span class="detail-label">Cập nhật:</span> <span class="detail-value">{{ fmtDateTime(a.updated_at) }}</span></div>
                    <div class="detail-item"><span class="detail-label">Người tạo:</span> <span class="detail-value">{{ a.created_by || '-' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Rev:</span> <span class="detail-value">{{ a._rev || '-' }}</span></div>
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

      <!-- Pager -->
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
        <h2 class="h5 mb-3">{{ editingId ? 'Sửa lịch hẹn' : 'Thêm lịch hẹn' }}</h2>

        <form @submit.prevent="save">
          <div class="section-title">Thông tin cơ bản</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Patient ID <span class="text-danger">*</span></label>
              <input v-model.trim="form.patient_id" class="form-control" required />
            </div>
            <div class="col-md-4">
              <label class="form-label">Doctor ID <span class="text-danger">*</span></label>
              <input v-model.trim="form.doctor_id" class="form-control" required />
            </div>
            <div class="col-md-4">
              <label class="form-label">Trạng thái</label>
              <select v-model="form.status" class="form-select">
                <option value="scheduled">scheduled</option>
                <option value="completed">completed</option>
                <option value="cancelled">cancelled</option>
                <option value="no_show">no_show</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Thời gian hẹn</label>
              <input v-model="form.scheduled_local" type="datetime-local" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Thời lượng (phút)</label>
              <input v-model.number="form.duration" type="number" min="0" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Loại</label>
              <select v-model="form.type" class="form-select">
                <option value="consultation">consultation</option>
                <option value="follow_up">follow_up</option>
                <option value="procedure">procedure</option>
                <option value="other">other</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Ưu tiên</label>
              <select v-model="form.priority" class="form-select">
                <option value="low">low</option>
                <option value="normal">normal</option>
                <option value="high">high</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Lý do</label>
              <input v-model.trim="form.reason" class="form-control" placeholder="Lý do khám" />
            </div>

            <div class="col-12">
              <label class="form-label">Ghi chú</label>
              <textarea v-model="form.notes" rows="3" class="form-control"></textarea>
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
import AppointmentService from '@/api/appointmentService'

export default {
  name: 'AppointmentsListView',
  data () {
    return {
      items: [],
      total: 0,
      page: 1,
      pageSize: 50,
      hasMore: false,
      loading: false,
      error: '',
      // filters
      filters: {
        patient_id: '',
        doctor_id: '',
        status: '',
        start_local: '', // datetime-local
        end_local: ''
      },
      // modal
      showModal: false,
      saving: false,
      editingId: null,
      form: this.emptyForm(),
      // expand rows
      expanded: {}
    }
  },
  created () { this.fetch() },
  methods: {
    // --- helpers ---
    fmtDateTime (v) { if (!v) return '-'; try { return new Date(v).toLocaleString() } catch { return v } },
    badgeClass (s) {
      if (s === 'scheduled') return 'bg-info-subtle text-info'
      if (s === 'completed') return 'bg-success-subtle text-success'
      if (s === 'cancelled') return 'bg-danger-subtle text-danger'
      if (s === 'no_show') return 'bg-warning-subtle text-warning'
      return 'bg-secondary-subtle text-secondary'
    },
    rowId (r) { return r._id || r.id },
    isExpanded (r) { return !!this.expanded[this.rowId(r)] },
    toggleRow (r) {
      const id = this.rowId(r)
      this.expanded = { ...this.expanded, [id]: !this.expanded[id] }
    },
    toLocalInput (iso) {
      if (!iso) return ''
      const d = new Date(iso)
      if (isNaN(d)) return ''
      const pad = n => String(n).padStart(2, '0')
      const y = d.getFullYear()
      const m = pad(d.getMonth() + 1)
      const day = pad(d.getDate())
      const hh = pad(d.getHours())
      const mm = pad(d.getMinutes())
      return `${y}-${m}-${day}T${hh}:${mm}`
    },
    toIso (localStr) {
      if (!localStr) return undefined
      const d = new Date(localStr)
      if (isNaN(d)) return undefined
      return d.toISOString()
    },

    // --- form ---
    emptyForm () {
      return {
        _id: null,
        _rev: null,
        patient_id: '',
        doctor_id: '',
        scheduled_local: '',
        duration: 30,
        type: 'consultation',
        priority: 'normal',
        status: 'scheduled',
        reason: '',
        notes: ''
      }
    },
    openCreate () {
      this.editingId = null
      this.form = this.emptyForm()
      this.showModal = true
    },
    openEdit (row) {
      this.editingId = row._id || row.id
      this.form = {
        _id: row._id,
        _rev: row._rev,
        patient_id: row.patient_id || '',
        doctor_id: row.doctor_id || '',
        scheduled_local: this.toLocalInput(row.appointment_info?.scheduled_date),
        duration: row.appointment_info?.duration ?? 30,
        type: row.appointment_info?.type || 'consultation',
        priority: row.appointment_info?.priority || 'normal',
        status: row.status || 'scheduled',
        reason: row.reason || '',
        notes: row.notes || ''
      }
      this.showModal = true
    },
    close () { if (!this.saving) this.showModal = false },

    // --- data ---
    async fetch () {
      this.loading = true
      this.error = ''
      try {
        const skip = (this.page - 1) * this.pageSize
        const params = {
          limit: this.pageSize,
          skip,
          patient_id: this.filters.patient_id || undefined,
          doctor_id: this.filters.doctor_id || undefined,
          status: this.filters.status || undefined,
          start: this.toIso(this.filters.start_local),
          end: this.toIso(this.filters.end_local)
        }
        const res = await AppointmentService.list(params)

        let items = []; let total = 0; let offset = null
        if (res && Array.isArray(res.rows)) {
          items = (res.rows || []).map(r => r.doc || r.value || r)
          total = res.total_rows ?? items.length
          offset = res.offset ?? 0
        } else if (res && res.data && Array.isArray(res.data)) {
          items = res.data; total = res.total ?? items.length
        } else if (Array.isArray(res)) { items = res; total = res.length }

        // đảm bảo trường tối thiểu
        this.items = items.map(d => ({
          _id: d._id || d.id,
          _rev: d._rev,
          patient_id: d.patient_id,
          doctor_id: d.doctor_id,
          appointment_info: d.appointment_info || {},
          reason: d.reason || '',
          status: d.status || 'scheduled',
          notes: d.notes || '',
          reminders: d.reminders || [],
          created_at: d.created_at,
          updated_at: d.updated_at,
          created_by: d.created_by
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

    // --- CRUD ---
    async save () {
      if (this.saving) return
      this.saving = true
      try {
        const payload = {
          type: 'appointment',
          patient_id: this.form.patient_id,
          doctor_id: this.form.doctor_id,
          appointment_info: {
            scheduled_date: this.toIso(this.form.scheduled_local),
            duration: (this.form.duration ?? '') === '' ? undefined : Number(this.form.duration),
            type: this.form.type || undefined,
            priority: this.form.priority || undefined
          },
          reason: this.form.reason || undefined,
          status: this.form.status,
          notes: this.form.notes || undefined
        }
        if (this.form._id) payload._id = this.form._id
        if (this.form._rev) payload._rev = this.form._rev

        if (this.editingId) {
          await AppointmentService.update(this.editingId, payload)
        } else {
          await AppointmentService.create(payload)
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
      if (!confirm(`Xóa lịch hẹn "${row._id}"?`)) return
      try {
        await AppointmentService.remove(row._id || row.id)
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
:deep(table.table) th, :deep(table.table) td { vertical-align: middle; }
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
.modal-backdrop{ position: fixed; inset: 0; background: rgba(0,0,0,.45); display: grid; place-items: center; z-index: 1050; }
.modal-card{ width: min(900px, 96vw); background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 20px 50px rgba(0,0,0,.25); max-height: 92vh; overflow: auto; }
.section-title{ font-weight: 600; margin: 14px 0 8px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; }
</style>
