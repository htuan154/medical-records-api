<template>
  <section class="container py-4">
    <!-- Header + Tools -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 mb-0">Quản lý Lịch hẹn</h2>
      <div class="d-flex gap-2">
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
    <div class="row g-2 mb-3">
      <div class="col-md-3"><input v-model.trim="f.patient_id" class="form-control" placeholder="patient_id" @keyup.enter="search" /></div>
      <div class="col-md-3"><input v-model.trim="f.doctor_id" class="form-control" placeholder="doctor_id" @keyup.enter="search" /></div>
      <div class="col-md-2">
        <select v-model="f.status" class="form-select">
          <option value="">-- Trạng thái --</option>
          <option value="scheduled">scheduled</option>
          <option value="approved">approved</option>
          <option value="completed">completed</option>
          <option value="canceled">canceled</option>
          <option value="pending">pending</option>
        </select>
      </div>
      <div class="col-md-2"><input v-model="f.from" type="datetime-local" class="form-control" /></div>
      <div class="col-md-2 d-flex gap-2">
        <input v-model="f.to" type="datetime-local" class="form-control" />
        <button class="btn btn-outline-secondary" @click="search">Tìm</button>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <!-- Table -->
    <div class="table-responsive">
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
            <th style="width:180px">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="(a, idx) in items" :key="rowKey(a, idx)">
            <tr>
              <td>{{ idx + 1 + (page - 1) * pageSize }}</td>
              <td>{{ a._id || a.id }}</td>
              <td>{{ displayName(patientsMap[a.patient_id]) || a.patient_id }}</td>
              <td>{{ displayName(doctorsMap[a.doctor_id]) || a.doctor_id }}</td>
              <td>{{ fmtDateTime(a.scheduled_date) }}</td>
              <td>{{ a.duration }} phút</td>
              <td>{{ a.type || '-' }}</td>
              <td>{{ a.priority || '-' }}</td>
              <td><span :class="['badge', statusClass(a.status)]">{{ a.status || '-' }}</span></td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary" @click="toggleRow(a)">{{ isExpanded(a) ? 'Ẩn' : 'Xem' }}</button>
                  <button class="btn btn-sm btn-outline-primary" @click="openEdit(a)">Sửa</button>
                  <button class="btn btn-sm btn-outline-danger" @click="remove(a)" :disabled="loading">Xóa</button>
                </div>
              </td>
            </tr>

            <!-- Details -->
            <tr v-if="isExpanded(a)">
              <td :colspan="10">
                <div class="detail-wrap">
                  <div class="detail-title">Chi tiết lịch hẹn</div>
                  <div class="detail-grid">
                    <div><b>Thời gian:</b> {{ fmtDateTime(a.scheduled_date) }}</div>
                    <div><b>Thời lượng:</b> {{ a.duration }} phút</div>
                    <div><b>Loại:</b> {{ a.type }}</div>
                    <div><b>Ưu tiên:</b> {{ a.priority || '-' }}</div>
                  </div>

                  <div class="detail-grid">
                    <div><b>Lý do:</b> {{ a.reason || '-' }}</div>
                    <div><b>Ghi chú:</b> {{ a.notes || '-' }}</div>
                  </div>

                  <div class="detail-title">Nhắc lịch</div>
                  <ul>
                    <li v-for="(r, i) in a.reminders" :key="i">
                      • {{ r.type }} — {{ fmtDateTime(r.sent_at) }} <i>({{ r.status }})</i>
                    </li>
                    <li v-if="!a.reminders || !a.reminders.length" class="text-muted">-</li>
                  </ul>

                  <div class="detail-title">Khác</div>
                  <div class="detail-grid">
                    <div><b>Tạo lúc:</b> {{ fmtDateTime(a.created_at) }}</div>
                    <div><b>Cập nhật:</b> {{ fmtDateTime(a.updated_at) }}</div>
                    <div><b>Người tạo:</b> {{ a.created_by || '-' }}</div>
                    <div><b>Rev:</b> {{ a._rev || '-' }}</div>
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

    <!-- MODAL: Form đầy đủ + combobox id -->
    <div v-if="showModal" class="modal-backdrop" @mousedown.self="close">
      <div class="modal-card">
        <h3 class="h6 mb-3">{{ editingId ? 'Sửa lịch hẹn' : 'Thêm lịch hẹn' }}</h3>

        <form @submit.prevent="save">
          <div class="section-title">Liên kết</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Bệnh nhân</label>
              <select v-model="form.patient_id" class="form-select">
                <option value="">-- chọn bệnh nhân --</option>
                <option v-for="p in patientOptions" :key="p.value" :value="p.value">{{ p.label }}</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Bác sĩ</label>
              <select v-model="form.doctor_id" class="form-select">
                <option value="">-- chọn bác sĩ --</option>
                <option v-for="d in doctorOptions" :key="d.value" :value="d.value">{{ d.label }}</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Trạng thái</label>
              <select v-model="form.status" class="form-select">
                <option value="scheduled">scheduled</option>
                <option value="approved">approved</option>
                <option value="completed">completed</option>
                <option value="canceled">canceled</option>
                <option value="pending">pending</option>
              </select>
            </div>
          </div>

          <div class="section-title">Thông tin lịch hẹn</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Thời gian</label>
              <input v-model="form.scheduled_date" type="datetime-local" class="form-control" />
            </div>
            <div class="col-md-2">
              <label class="form-label">Thời lượng (phút)</label>
              <input v-model.number="form.duration" type="number" min="0" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Loại</label>
              <input v-model.trim="form.type" class="form-control" placeholder="follow_up / consultation…" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Ưu tiên</label>
              <select v-model="form.priority" class="form-select">
                <option value="low">low</option>
                <option value="normal">normal</option>
                <option value="high">high</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Lý do</label>
              <input v-model.trim="form.reason" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Ghi chú</label>
              <input v-model.trim="form.notes" class="form-control" />
            </div>
          </div>

          <div class="section-title">Nhắc lịch</div>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
              <tr>
                <th style="width:18%">Loại</th>
                <th style="width:26%">Gửi lúc</th>
                <th style="width:18%">Trạng thái</th>
                <th style="width:6%"></th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="(r, i) in form.reminders" :key="i">
                <td><input v-model.trim="r.type" class="form-control form-control-sm" placeholder="sms / email" /></td>
                <td><input v-model="r.sent_at" type="datetime-local" class="form-control form-control-sm" /></td>
                <td><input v-model.trim="r.status" class="form-control form-control-sm" placeholder="sent / failed / pending" /></td>
                <td class="text-end">
                  <button type="button" class="btn btn-sm btn-outline-danger" @click="removeReminder(i)">×</button>
                </td>
              </tr>
              <tr v-if="!form.reminders.length">
                <td colspan="4" class="text-muted small">Chưa có nhắc lịch — bấm “+ Thêm nhắc lịch”</td>
              </tr>
              </tbody>
            </table>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm" @click="addReminder">+ Thêm nhắc lịch</button>

          <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary" @click="close">Hủy</button>
            <button class="btn btn-primary" type="submit" :disabled="saving">{{ saving ? 'Đang lưu…' : 'Lưu' }}</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script>
import AppointmentService from '@/api/appointmentService'
import DoctorService from '@/api/doctorService'
import PatientService from '@/api/patientService'

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
      f: { patient_id: '', doctor_id: '', status: '', from: '', to: '' },
      // modal
      showModal: false,
      saving: false,
      editingId: null,
      form: this.emptyForm(),
      // expand
      expanded: {},
      // combos + map tên
      doctorOptions: [],
      patientOptions: [],
      doctorsMap: {},
      patientsMap: {},
      optionsLoaded: false
    }
  },
  created () { this.fetch() },
  methods: {
    /* ===== helpers ===== */
    fmtDateTime (v) { if (!v) return '-'; try { return new Date(v).toLocaleString() } catch { return v } },
    rowKey (r, i) { return r._id || r.id || `${i}` },
    isExpanded (r) { return !!this.expanded[this.rowKey(r, 0)] },
    toggleRow (r) { const k = this.rowKey(r, 0); this.expanded = { ...this.expanded, [k]: !this.expanded[k] } },
    displayName (o) { return o?.full_name || o?.name || o?.display_name || o?.code || o?.username },
    statusClass (s) {
      return s === 'scheduled'
        ? 'bg-info-subtle text-info'
        : s === 'approved'
          ? 'bg-primary-subtle text-primary'
          : s === 'completed'
            ? 'bg-success-subtle text-success'
            : s === 'canceled'
              ? 'bg-danger-subtle text-danger'
              : 'bg-secondary-subtle text-secondary'
    },

    flattenAppointment (d = {}) {
      const ai = d.appointment_info || {}
      return {
        ...d,
        patient_id: d.patient_id || '',
        doctor_id: d.doctor_id || '',
        scheduled_date: ai.scheduled_date || d.scheduled_date || '',
        duration: ai.duration ?? d.duration ?? 0,
        type: ai.type || d.type || '',
        priority: ai.priority || d.priority || '',
        reason: d.reason || '',
        status: d.status || 'scheduled',
        notes: d.notes || '',
        reminders: (d.reminders || []).map(r => ({
          type: r.type || 'sms',
          sent_at: r.sent_at || '',
          status: r.status || ''
        })),
        created_at: d.created_at || null,
        updated_at: d.updated_at || null,
        created_by: d.created_by || '',
        _rev: d._rev
      }
    },

    emptyForm () {
      return {
        _id: null,
        _rev: null,
        patient_id: '',
        doctor_id: '',
        scheduled_date: '',
        duration: 30,
        type: 'follow_up',
        priority: 'normal',
        reason: '',
        status: 'scheduled',
        notes: '',
        reminders: [],
        created_at: null,
        updated_at: null,
        created_by: ''
      }
    },

    /* ===== data ===== */
    async fetch () {
      this.loading = true
      this.error = ''
      try {
        const skip = (this.page - 1) * this.pageSize
        const params = {
          patient_id: this.f.patient_id || undefined,
          doctor_id: this.f.doctor_id || undefined,
          status: this.f.status || undefined,
          from: this.f.from || undefined,
          to: this.f.to || undefined,
          limit: this.pageSize,
          offset: skip,
          skip
        }
        const res = await AppointmentService.list(params)
        let raw = []; let total = 0; let offset = null
        if (res && Array.isArray(res.rows)) {
          raw = res.rows.map(r => r.doc || r.value || r); total = res.total_rows ?? raw.length; offset = res.offset ?? 0
        } else if (res && Array.isArray(res.data)) { raw = res.data; total = res.total ?? raw.length } else if (Array.isArray(res)) { raw = res; total = raw.length }

        this.items = (raw || []).map(d => this.flattenAppointment(d))
        this.total = total
        this.hasMore = (offset != null)
          ? (offset + this.items.length) < (this.total || 0)
          : (this.page * this.pageSize) < (this.total || 0)

        await this.ensureOptionsLoaded()
      } catch (e) {
        console.error(e)
        this.error = e?.response?.data?.message || e?.message || 'Không tải được dữ liệu'
      } finally { this.loading = false }
    },
    changePageSize () { this.page = 1; this.fetch() },
    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },

    /* ===== combos ===== */
    async ensureOptionsLoaded () {
      if (this.optionsLoaded) return
      try {
        const [dRes, pRes] = await Promise.all([
          DoctorService.list({ limit: 1000 }),
          PatientService.list({ limit: 1000 })
        ])
        const arr = r => (Array.isArray(r?.rows)
          ? r.rows.map(x => x.doc || x.value || x)
          : Array.isArray(r?.data)
            ? r.data
            : Array.isArray(r) ? r : [])
        const dList = arr(dRes); const pList = arr(pRes)
        const key = o => o._id || o.id || o.code || o.username
        const label = o => o.full_name || o.name || o.display_name || o.code || o.username || key(o)

        this.doctorOptions = dList.map(o => ({ value: key(o), label: label(o) }))
        this.patientOptions = pList.map(o => ({ value: key(o), label: label(o) }))

        // Sửa lỗi ESLint: thay reduce với comma operator thành forEach
        this.doctorsMap = {}
        dList.forEach(o => {
          this.doctorsMap[key(o)] = o
        })

        this.patientsMap = {}
        pList.forEach(o => {
          this.patientsMap[key(o)] = o
        })

        this.optionsLoaded = true
      } catch (e) { console.error(e) }
    },

    /* ===== modal ===== */
    openCreate () { this.editingId = null; this.form = this.emptyForm(); this.showModal = true; this.ensureOptionsLoaded() },
    openEdit (row) {
      const f = this.flattenAppointment(row)
      this.editingId = f._id || f.id
      this.form = {
        ...this.emptyForm(),
        ...f,
        scheduled_date: f.scheduled_date ? new Date(f.scheduled_date).toISOString().slice(0, 16) : ''
      }
      this.showModal = true
      this.ensureOptionsLoaded()
    },
    close () { if (!this.saving) this.showModal = false },

    addReminder () { this.form.reminders = [...this.form.reminders, { type: 'sms', sent_at: '', status: 'pending' }] },
    removeReminder (i) { this.form.reminders = this.form.reminders.filter((_, idx) => idx !== i) },

    async save () {
      if (this.saving) return
      this.saving = true
      try {
        const payload = {
          type: 'appointment',
          patient_id: this.form.patient_id || undefined,
          doctor_id: this.form.doctor_id || undefined,
          appointment_info: {
            scheduled_date: this.form.scheduled_date || undefined,
            duration: this.form.duration ?? undefined,
            type: this.form.type || undefined,
            priority: this.form.priority || undefined
          },
          reason: this.form.reason || undefined,
          status: this.form.status || 'scheduled',
          notes: this.form.notes || undefined,
          reminders: (this.form.reminders || []).map(r => ({
            type: r.type || undefined,
            sent_at: r.sent_at || undefined,
            status: r.status || undefined
          }))
        }
        if (this.form._id) payload._id = this.form._id
        if (this.form._rev) payload._rev = this.form._rev

        if (this.editingId) await AppointmentService.update(this.editingId, payload)
        else await AppointmentService.create(payload)

        this.showModal = false
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Lưu thất bại')
      } finally { this.saving = false }
    },

    async remove (row) {
      if (!confirm(`Xóa lịch hẹn "${row._id || row.id}"?`)) return
      try {
        const id = row._id || row.id
        await AppointmentService.remove(id)
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

/* details */
.detail-wrap { border-top: 1px solid #e5e7eb; padding-top: 10px; }
.detail-title { font-weight: 600; margin: 10px 0 6px; }
.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 6px 16px;
}

/* modal */
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.45); display: grid; place-items: center; z-index: 1050; }
.modal-card { width: min(1000px, 96vw); background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 20px 50px rgba(0,0,0,.25); max-height: 92vh; overflow: auto; }
.section-title { font-weight: 600; margin: 14px 0 8px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; }
</style>
