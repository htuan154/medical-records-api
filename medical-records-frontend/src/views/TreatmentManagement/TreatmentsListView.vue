<template>
  <section class="container py-4">
    <!-- Header + Tools -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 mb-0">Phác đồ điều trị</h2>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" @click="reload" :disabled="loading">Tải lại</button>
        <button class="btn btn-primary" @click="openCreate" :disabled="loading">+ Thêm mới</button>
      </div>
    </div>

    <div class="d-flex align-items-center mb-3" style="max-width: 520px">
      <input v-model.trim="q" class="form-control me-2" placeholder="Tìm theo tên điều trị / loại…" @keyup.enter="search" />
      <button class="btn btn-outline-secondary" @click="search">Tìm</button>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <!-- LIST: Tên / bắt đầu / kết thúc / loại / trạng thái -->
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th style="width:56px">#</th>
            <th>Tên điều trị</th>
            <th>Bắt đầu</th>
            <th>Kết thúc</th>
            <th>Loại</th>
            <th>Trạng thái</th>
            <th style="width:180px">Hành động</th>
          </tr>
        </thead>
        <tbody v-for="(t, idx) in items" :key="rowKey(t, idx)">
          <tr>
            <td>{{ idx + 1 + (page - 1) * pageSize }}</td>
              <td>{{ t.treatment_name }}</td>
              <td>{{ fmtDate(t.start_date) }}</td>
              <td>{{ fmtDate(t.end_date) }}</td>
              <td>{{ t.treatment_type }}</td>
              <td>
                <span :class="['badge', statusClass(t.status)]">{{ t.status || '-' }}</span>
              </td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary" @click="toggleRow(t)">{{ isExpanded(t) ? 'Ẩn' : 'Xem' }}</button>
                  <button class="btn btn-sm btn-outline-primary" @click="openEdit(t)">Sửa</button>
                  <button class="btn btn-sm btn-outline-danger" @click="remove(t)" :disabled="loading">Xóa</button>
                </div>
              </td>
          </tr>

          <!-- DETAILS xổ khi Xem -->
          <tr v-if="isExpanded(t)">
            <td :colspan="7">
                <div class="detail-wrap">
                  <div class="detail-title">Liên kết</div>
                  <div class="detail-grid">
                    <div><b>Bệnh nhân:</b> {{ displayName(patientsMap[t.patient_id]) || t.patient_id }}</div>
                    <div><b>Bác sĩ:</b> {{ displayName(doctorsMap[t.doctor_id]) || t.doctor_id }}</div>
                    <div><b>Hồ sơ khám:</b> {{ t.medical_record_id || '-' }}</div>
                  </div>

                  <div class="detail-title">Thông tin điều trị</div>
                  <div class="detail-grid">
                    <div><b>Tên:</b> {{ t.treatment_name }}</div>
                    <div><b>Loại:</b> {{ t.treatment_type || '-' }}</div>
                    <div><b>Bắt đầu:</b> {{ fmtDate(t.start_date) }}</div>
                    <div><b>Kết thúc:</b> {{ fmtDate(t.end_date) }}</div>
                    <div><b>Số ngày:</b> {{ t.duration_days ?? '-' }}</div>
                  </div>

                  <div class="detail-title">Thuốc</div>
                  <ul class="mb-2">
                    <li v-for="(m, i) in t.medications" :key="i">
                      <b>{{ m.name }}</b>
                      <span v-if="m.dosage"> — {{ m.dosage }}</span>
                      <span v-if="m.frequency">; {{ m.frequency }}</span>
                      <span v-if="m.route">; {{ m.route }}</span>
                      <span v-if="m.instructions">; {{ m.instructions }}</span>
                      <span v-if="m.quantity_prescribed">; SL: {{ m.quantity_prescribed }}</span>
                      <span class="text-muted" v-if="m.medication_id"> ({{ m.medication_id }})</span>
                    </li>
                    <li v-if="!t.medications || !t.medications.length" class="text-muted">-</li>
                  </ul>

                  <div class="detail-title">Theo dõi</div>
                  <div class="detail-grid">
                    <div><b>Tham số:</b> {{ (t.monitor_params || []).join(', ') || '-' }}</div>
                    <div><b>Tần suất:</b> {{ t.monitor_frequency || '-' }}</div>
                    <div><b>Lịch kiểm tra tiếp:</b> {{ fmtDate(t.monitor_next_check) }}</div>
                  </div>

                  <div class="text-muted small mt-2">
                    Tạo: {{ fmtDateTime(t.created_at) }} | Cập nhật: {{ fmtDateTime(t.updated_at) }}
                  </div>
              </div>
            </td>
          </tr>
        </tbody>

        <tbody v-if="!items.length">
          <tr>
            <td colspan="7" class="text-center text-muted">Không có dữ liệu</td>
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

    <!-- MODAL: form đầy đủ + combobox BN/BS/HS -->
    <div v-if="showModal" class="modal-backdrop" @mousedown.self="close">
      <div class="modal-card">
        <h3 class="h6 mb-3">{{ editingId ? 'Sửa điều trị' : 'Thêm điều trị' }}</h3>

        <form @submit.prevent="save">
          <!-- Liên kết -->
          <div class="section-title">Liên kết</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Bệnh nhân</label>
              <select v-model="form.patient_id" class="form-select" @change="onPatientChange">
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
              <label class="form-label">Hồ sơ khám</label>
              <select v-model="form.medical_record_id" class="form-select">
                <option value="">-- chọn hồ sơ (theo bệnh nhân) --</option>
                <option v-for="r in recordOptions" :key="r.value" :value="r.value">{{ r.label }}</option>
              </select>
            </div>
          </div>

          <!-- Thông tin điều trị -->
          <div class="section-title">Thông tin điều trị</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Tên điều trị</label>
              <input v-model.trim="form.treatment_name" class="form-control" placeholder="Điều trị tăng huyết áp" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Bắt đầu</label>
              <input v-model="form.start_date" type="date" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Kết thúc</label>
              <input v-model="form.end_date" type="date" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Số ngày</label>
              <input v-model.number="form.duration_days" type="number" min="0" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Loại điều trị</label>
              <input v-model.trim="form.treatment_type" class="form-control" placeholder="medication / procedure / ..." />
            </div>
            <div class="col-md-3">
              <label class="form-label">Trạng thái</label>
              <select v-model="form.status" class="form-select">
                <option value="active">active</option>
                <option value="paused">paused</option>
                <option value="completed">completed</option>
                <option value="canceled">canceled</option>
              </select>
            </div>
          </div>

          <!-- Thuốc -->
          <div class="section-title">Thuốc</div>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th style="width:18%">Mã thuốc</th>
                  <th style="width:18%">Tên</th>
                  <th style="width:12%">Liều</th>
                  <th style="width:16%">Số lần/ngày</th>
                  <th style="width:10%">Đường dùng</th>
                  <th style="width:20%">Hướng dẫn</th>
                  <th style="width:10%">Số lượng</th>
                  <th style="width:6%"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(m, i) in form.medications" :key="i">
                  <td><input v-model.trim="m.medication_id" class="form-control form-control-sm" placeholder="med_amlodipine_5mg" /></td>
                  <td><input v-model.trim="m.name" class="form-control form-control-sm" placeholder="Amlodipine" /></td>
                  <td><input v-model.trim="m.dosage" class="form-control form-control-sm" placeholder="5mg" /></td>
                  <td><input v-model.trim="m.frequency" class="form-control form-control-sm" placeholder="1 lần/ngày" /></td>
                  <td><input v-model.trim="m.route" class="form-control form-control-sm" placeholder="oral" /></td>
                  <td><input v-model.trim="m.instructions" class="form-control form-control-sm" placeholder="Uống sau ăn sáng" /></td>
                  <td><input v-model.number="m.quantity_prescribed" type="number" min="0" class="form-control form-control-sm" /></td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="removeMedication(i)">×</button>
                  </td>
                </tr>
                <tr v-if="!form.medications.length">
                  <td colspan="8" class="text-muted small">Chưa có thuốc — bấm “+ Thêm thuốc”</td>
                </tr>
              </tbody>
            </table>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm" @click="addMedication">+ Thêm thuốc</button>

          <!-- Theo dõi -->
          <div class="section-title">Theo dõi</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Tham số theo dõi (dấu phẩy)</label>
              <input v-model.trim="form.monitor_params_text" class="form-control" placeholder="blood_pressure, heart_rate" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Tần suất</label>
              <input v-model.trim="form.monitor_frequency" class="form-control" placeholder="daily / weekly / ..." />
            </div>
            <div class="col-md-3">
              <label class="form-label">Kiểm tra tiếp</label>
              <input v-model="form.monitor_next_check" type="date" class="form-control" />
            </div>
          </div>

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
import TreatmentService from '@/api/treatmentService'
import DoctorService from '@/api/doctorService'
import PatientService from '@/api/patientService'
import MedicalRecordService from '@/api/medicalRecordService'

export default {
  name: 'TreatmentsListView',
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
      expanded: {},
      // combobox + map tên
      doctorOptions: [],
      patientOptions: [],
      recordOptions: [],
      doctorsMap: {},
      patientsMap: {},
      optionsLoaded: false
    }
  },
  created () { this.fetch() },
  methods: {
    /* ===== helpers ===== */
    rowKey (t, idx) { return t._id || t.id || `${idx}` },
    isExpanded (row) { return !!this.expanded[this.rowKey(row, 0)] },
    toggleRow (row) { const k = this.rowKey(row, 0); this.expanded = { ...this.expanded, [k]: !this.expanded[k] } },
    fmtDate (v) { if (!v) return '-'; try { return new Date(v).toISOString().slice(0, 10) } catch { return v } },
    fmtDateTime (v) { if (!v) return '-'; try { return new Date(v).toLocaleString() } catch { return v } },
    statusClass (s) {
      return s === 'active'
        ? 'bg-success-subtle text-success'
        : s === 'paused'
          ? 'bg-warning-subtle text-warning'
          : s === 'completed'
            ? 'bg-primary-subtle text-primary'
            : s === 'canceled'
              ? 'bg-danger-subtle text-danger'
              : 'bg-secondary-subtle text-secondary'
    },
    displayName (o) { return o?.full_name || o?.name || o?.display_name || o?.code || o?.username },

    // flatten doc nested -> flat fields cho list/details/form
    flattenTreatment (d = {}) {
      const ti = d.treatment_info || {}
      const mon = d.monitoring || {}
      const meds = d.medications || []
      return {
        ...d,
        patient_id: d.patient_id || '',
        doctor_id: d.doctor_id || '',
        medical_record_id: d.medical_record_id || '',
        treatment_name: ti.treatment_name || d.treatment_name || '',
        start_date: ti.start_date || d.start_date || '',
        end_date: ti.end_date || d.end_date || '',
        duration_days: ti.duration_days ?? d.duration_days ?? null,
        treatment_type: ti.treatment_type || d.treatment_type || '',
        medications: meds.map(m => ({
          medication_id: m.medication_id || '',
          name: m.name || '',
          dosage: m.dosage || '',
          frequency: m.frequency || '',
          route: m.route || '',
          instructions: m.instructions || '',
          quantity_prescribed: m.quantity_prescribed ?? null
        })),
        monitor_params: mon.parameters || [],
        monitor_frequency: mon.frequency || '',
        monitor_next_check: mon.next_check || '',
        status: d.status || 'active',
        created_at: d.created_at || null,
        updated_at: d.updated_at || null
      }
    },

    emptyForm () {
      return {
        _id: null,
        _rev: null,
        patient_id: '',
        doctor_id: '',
        medical_record_id: '',
        treatment_name: '',
        start_date: '',
        end_date: '',
        duration_days: null,
        treatment_type: '',
        medications: [],
        monitor_params: [],
        monitor_params_text: '',
        monitor_frequency: '',
        monitor_next_check: '',
        status: 'active',
        created_at: null,
        updated_at: null
      }
    },

    /* ===== data ===== */
    async fetch () {
      this.loading = true
      this.error = ''
      try {
        const skip = (this.page - 1) * this.pageSize
        const res = await TreatmentService.list({ q: this.q || undefined, limit: this.pageSize, offset: skip, skip })
        let raw = []; let total = 0; let offset = null
        if (res && Array.isArray(res.rows)) {
          raw = res.rows.map(r => r.doc || r.value || r)
          total = res.total_rows ?? raw.length
          offset = res.offset ?? 0
        } else if (res && Array.isArray(res.data)) {
          raw = res.data; total = res.total ?? raw.length
        } else if (Array.isArray(res)) { raw = res; total = raw.length }

        this.items = (raw || []).map(d => this.flattenTreatment(d))
        this.total = total
        this.hasMore = (offset != null)
          ? (offset + this.items.length) < (this.total || 0)
          : (this.page * this.pageSize) < (this.total || 0)

        // nạp options 1 lần để hiện tên
        await this.ensureOptionsLoaded()
      } catch (e) {
        console.error(e)
        this.error = e?.response?.data?.message || e?.message || 'Không tải được dữ liệu'
      } finally { this.loading = false }
    },
    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },

    /* ===== combobox ===== */
    async ensureOptionsLoaded () {
      if (this.optionsLoaded) return
      try {
        const [dRes, pRes] = await Promise.all([
          DoctorService.list({ limit: 1000 }), // BS
          PatientService.list({ limit: 1000 }) // BN
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
      } catch (e) {
        console.error(e)
        this.doctorOptions = []
        this.patientOptions = []
      }
    },
    async onPatientChange () {
      this.form.medical_record_id = ''
      await this.loadRecordsForPatient(this.form.patient_id)
    },
    async loadRecordsForPatient (patientId) {
      try {
        if (!patientId) { this.recordOptions = []; return }
        const r = await MedicalRecordService.forPatient(patientId, { limit: 1000 })
        const arr = Array.isArray(r?.rows)
          ? r.rows.map(x => x.doc || x.value || x)
          : Array.isArray(r?.data)
            ? r.data
            : Array.isArray(r) ? r : []
        this.recordOptions = arr.map(rec => ({
          value: rec._id || rec.id,
          label: `${(rec.visit_info?.visit_date || rec.visit_date || '').toString().slice(0, 10)} — ${(rec.visit_info?.visit_type || rec.visit_type || 'khám')}`
        }))
      } catch (e) {
        console.error(e)
        this.recordOptions = []
      }
    },

    /* ===== modal ===== */
    openCreate () {
      this.editingId = null
      this.form = this.emptyForm()
      this.showModal = true
      this.ensureOptionsLoaded()
      this.recordOptions = []
    },
    openEdit (row) {
      const f = this.flattenTreatment(row)
      this.editingId = f._id || f.id
      this.form = {
        ...this.emptyForm(),
        ...f,
        monitor_params_text: (f.monitor_params || []).join(', '),
        // date inputs want yyyy-mm-dd
        start_date: (f.start_date || '').toString().slice(0, 10),
        end_date: (f.end_date || '').toString().slice(0, 10),
        monitor_next_check: (f.monitor_next_check || '').toString().slice(0, 10)
      }
      this.showModal = true
      this.ensureOptionsLoaded()
      this.loadRecordsForPatient(this.form.patient_id)
    },
    close () { if (!this.saving) this.showModal = false },

    addMedication () {
      this.form.medications = [...this.form.medications, {
        medication_id: '', name: '', dosage: '', frequency: '', route: '', instructions: '', quantity_prescribed: null
      }]
    },
    removeMedication (i) { this.form.medications = this.form.medications.filter((_, idx) => idx !== i) },

    /* ===== save/remove ===== */
    async save () {
      if (this.saving) return
      this.saving = true
      try {
        const csv = (s) => (s || '').split(',').map(x => x.trim()).filter(Boolean)
        const payload = {
          type: 'treatment',
          patient_id: this.form.patient_id || undefined,
          doctor_id: this.form.doctor_id || undefined,
          medical_record_id: this.form.medical_record_id || undefined,
          treatment_info: {
            treatment_name: this.form.treatment_name || undefined,
            start_date: this.form.start_date || undefined,
            end_date: this.form.end_date || undefined,
            duration_days: this.form.duration_days ?? undefined,
            treatment_type: this.form.treatment_type || undefined
          },
          medications: (this.form.medications || []).map(m => ({
            medication_id: m.medication_id || undefined,
            name: m.name || undefined,
            dosage: m.dosage || undefined,
            frequency: m.frequency || undefined,
            route: m.route || undefined,
            instructions: m.instructions || undefined,
            quantity_prescribed: m.quantity_prescribed ?? undefined
          })),
          monitoring: {
            parameters: this.form.monitor_params?.length ? this.form.monitor_params : csv(this.form.monitor_params_text),
            frequency: this.form.monitor_frequency || undefined,
            next_check: this.form.monitor_next_check || undefined
          },
          status: this.form.status || 'active'
        }

        if (this.form._id) payload._id = this.form._id
        if (this.form._rev) payload._rev = this.form._rev

        if (this.editingId) await TreatmentService.update(this.editingId, payload)
        else await TreatmentService.create(payload)

        this.showModal = false
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Lưu thất bại')
      } finally { this.saving = false }
    },

    async remove (row) {
      if (!confirm(`Xóa điều trị "${row.treatment_name || 'này'}"?`)) return
      try {
        const id = row._id || row.id
        await TreatmentService.remove(id)
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

/* row details */
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
