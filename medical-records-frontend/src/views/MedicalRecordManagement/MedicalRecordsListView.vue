<template>
  <section class="container py-4">
    <!-- Header + Tools -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 mb-0">Hồ sơ khám</h2>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" @click="reload" :disabled="loading">Tải lại</button>
        <button class="btn btn-primary" @click="openCreate" :disabled="loading">+ Thêm mới</button>
      </div>
    </div>

    <div class="d-flex align-items-center mb-3" style="max-width: 520px">
      <input v-model.trim="q" class="form-control me-2" placeholder="Tìm theo loại / lý do / bác sĩ / bệnh nhân…" @keyup.enter="search" />
      <button class="btn btn-outline-secondary" @click="search">Tìm</button>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <!-- LIST gọn: Ngày khám / BN / BS / Trạng thái -->
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th style="width:56px">#</th>
            <th>Ngày khám</th>
            <th>Bệnh nhân</th>
            <th>Bác sĩ</th>
            <th>Loại khám</th>
            <th>Trạng thái</th>
            <th style="width:160px">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="(r, idx) in items" :key="rowKey(r, idx)">
            <tr>
              <td>{{ idx + 1 + (page - 1) * pageSize }}</td>
              <td>{{ fmtDateTime(r.visit_date) }}</td>
              <td>{{ displayName(patientsMap[r.patient_id]) || r.patient_id }}</td>
              <td>{{ displayName(doctorsMap[r.doctor_id]) || r.doctor_id }}</td>
              <td>{{ r.visit_type }}</td>
              <td>
                <span :class="['badge', statusClass(r.status)]">{{ r.status || '-' }}</span>
              </td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary" @click="toggleRow(r)">{{ isExpanded(r) ? 'Ẩn' : 'Xem' }}</button>
                  <button class="btn btn-sm btn-outline-primary" @click="openEdit(r)">Sửa</button>
                  <button class="btn btn-sm btn-outline-danger" @click="remove(r)" :disabled="loading">Xóa</button>
                </div>
              </td>
            </tr>

            <!-- DETAILS xổ khi bấm Xem -->
            <tr v-if="isExpanded(r)">
              <td :colspan="7">
                <div class="detail-wrap">
                  <div class="detail-title">Thông tin khám</div>
                  <div class="detail-grid">
                    <div><b>Loại:</b> {{ r.visit_type || '-' }}</div>
                    <div><b>Ngày khám:</b> {{ fmtDateTime(r.visit_date) }}</div>
                    <div><b>Lý do:</b> {{ r.chief_complaint || '-' }}</div>
                    <div><b>Lịch hẹn:</b> {{ r.appointment_id || '-' }}</div>
                  </div>

                  <div class="detail-title">Dấu hiệu sinh tồn</div>
                  <div class="detail-grid">
                    <div><b>Nhiệt độ:</b> {{ r.vital.temperature ?? '-' }} °C</div>
                    <div><b>HA:</b> {{ r.vital.bp_systolic ?? '-' }}/{{ r.vital.bp_diastolic ?? '-' }} mmHg</div>
                    <div><b>Mạch:</b> {{ r.vital.heart_rate ?? '-' }} bpm</div>
                    <div><b>Nhịp thở:</b> {{ r.vital.respiratory_rate ?? '-' }} lần/phút</div>
                    <div><b>Cân nặng:</b> {{ r.vital.weight ?? '-' }} kg</div>
                    <div><b>Chiều cao:</b> {{ r.vital.height ?? '-' }} cm</div>
                  </div>

                  <div class="detail-title">Khám thực thể</div>
                  <div class="detail-grid">
                    <div class="col-span-2"><b>Toàn thân:</b> {{ r.physical.general || '-' }}</div>
                    <div class="col-span-2"><b>Tim mạch:</b> {{ r.physical.cardiovascular || '-' }}</div>
                    <div class="col-span-2"><b>Hô hấp:</b> {{ r.physical.respiratory || '-' }}</div>
                    <div class="col-span-2"><b>Khác:</b> {{ r.physical.other_findings || '-' }}</div>
                  </div>

                  <div class="detail-title">Chẩn đoán</div>
                  <div class="detail-grid">
                    <div><b>Chính:</b> ({{ r.dx_primary.code || '-' }}) {{ r.dx_primary.description || '-' }} <i v-if="r.dx_primary.severity">— {{ r.dx_primary.severity }}</i></div>
                    <div><b>Phụ:</b> {{ (r.dx_secondary || []).join(', ') || '-' }}</div>
                    <div><b>Phân biệt:</b> {{ (r.dx_differential || []).join(', ') || '-' }}</div>
                  </div>

                  <div class="detail-title">Điều trị</div>
                  <div class="detail-grid">
                    <div class="col-span-2">
                      <b>Thuốc:</b>
                      <ul class="mb-2">
                        <li v-for="(m, i) in r.medications" :key="i">
                          <b>{{ m.name }}</b> — {{ m.dosage }}; {{ m.frequency }}; {{ m.duration }}
                          <span v-if="m.instructions"> ({{ m.instructions }})</span>
                        </li>
                        <li v-if="!r.medications || !r.medications.length" class="text-muted">-</li>
                      </ul>
                    </div>
                    <div><b>Thủ thuật:</b> {{ (r.procedures || []).join(', ') || '-' }}</div>
                    <div><b>Tư vấn lối sống:</b> {{ (r.lifestyle_advice || []).join(', ') || '-' }}</div>
                    <div><b>Tái khám:</b> {{ r.follow_up?.date || '-' }}<span v-if="r.follow_up?.notes"> — {{ r.follow_up.notes }}</span></div>
                  </div>

                  <div class="detail-title">Đính kèm</div>
                  <ul class="mb-2">
                    <li v-for="(a, i) in r.attachments" :key="i">
                      <b>{{ a.type }}</b> — {{ a.file_name }} <span v-if="a.description">({{ a.description }})</span>
                    </li>
                    <li v-if="!r.attachments || !r.attachments.length" class="text-muted">-</li>
                  </ul>

                  <div class="text-muted small mt-2">
                    Tạo: {{ fmtDateTime(r.created_at) }} | Cập nhật: {{ fmtDateTime(r.updated_at) }}
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

      <div class="d-flex justify-content-between align-items-center">
        <div>Trang {{ page }} / {{ Math.max(1, Math.ceil((total || 0) / pageSize)) }}</div>
        <div class="btn-group">
          <button class="btn btn-outline-secondary" @click="prev" :disabled="page <= 1 || loading">‹ Trước</button>
          <button class="btn btn-outline-secondary" @click="next" :disabled="!hasMore || loading">Sau ›</button>
        </div>
      </div>
    </div>

    <!-- MODAL: form đầy đủ + combobox BN/BS -->
    <div v-if="showModal" class="modal-backdrop" @mousedown.self="close">
      <div class="modal-card">
        <h3 class="h6 mb-3">{{ editingId ? 'Sửa hồ sơ' : 'Thêm hồ sơ' }}</h3>

        <form @submit.prevent="save">
          <!-- Thông tin chung -->
          <div class="section-title">Thông tin chung</div>
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
              <label class="form-label">Ngày khám</label>
              <input v-model="form.visit_date" type="datetime-local" class="form-control" />
            </div>

            <div class="col-md-4">
              <label class="form-label">Loại khám</label>
              <input v-model.trim="form.visit_type" class="form-control" placeholder="consultation, follow_up…" />
            </div>
            <div class="col-md-8">
              <label class="form-label">Lý do</label>
              <input v-model.trim="form.chief_complaint" class="form-control" placeholder="Đau ngực, khó thở…" />
            </div>

            <div class="col-md-6">
              <label class="form-label">Mã lịch hẹn (nếu có)</label>
              <input v-model.trim="form.appointment_id" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Trạng thái</label>
              <select v-model="form.status" class="form-select">
                <option value="draft">draft</option>
                <option value="in_progress">in_progress</option>
                <option value="completed">completed</option>
                <option value="canceled">canceled</option>
              </select>
            </div>
          </div>

          <!-- Dấu hiệu sinh tồn -->
          <div class="section-title">Dấu hiệu sinh tồn</div>
          <div class="row g-3">
            <div class="col-md-2">
              <label class="form-label">Nhiệt độ (°C)</label>
              <input v-model.number="form.vital.temperature" type="number" step="0.1" class="form-control"/>
            </div>
            <div class="col-md-2">
              <label class="form-label">HA tâm thu</label>
              <input v-model.number="form.vital.bp_systolic" type="number" class="form-control"/>
            </div>
            <div class="col-md-2">
              <label class="form-label">HA tâm trương</label>
              <input v-model.number="form.vital.bp_diastolic" type="number" class="form-control"/>
            </div>
            <div class="col-md-2">
              <label class="form-label">Mạch (bpm)</label>
              <input v-model.number="form.vital.heart_rate" type="number" class="form-control"/>
            </div>
            <div class="col-md-2">
              <label class="form-label">Nhịp thở</label>
              <input v-model.number="form.vital.respiratory_rate" type="number" class="form-control"/>
            </div>
            <div class="col-md-2">
              <label class="form-label">Cân nặng (kg)</label>
              <input v-model.number="form.vital.weight" type="number" step="0.1" class="form-control"/>
            </div>
            <div class="col-md-2">
              <label class="form-label">Chiều cao (cm)</label>
              <input v-model.number="form.vital.height" type="number" class="form-control"/>
            </div>
          </div>

          <!-- Khám thực thể -->
          <div class="section-title">Khám thực thể</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Toàn thân</label>
              <textarea v-model.trim="form.physical.general" rows="2" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Tim mạch</label>
              <textarea v-model.trim="form.physical.cardiovascular" rows="2" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Hô hấp</label>
              <textarea v-model.trim="form.physical.respiratory" rows="2" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Khác</label>
              <textarea v-model.trim="form.physical.other_findings" rows="2" class="form-control" />
            </div>
          </div>

          <!-- Chẩn đoán -->
          <div class="section-title">Chẩn đoán</div>
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Mã chính (ICD)</label>
              <input v-model.trim="form.dx_primary.code" class="form-control"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">Mô tả chính</label>
              <input v-model.trim="form.dx_primary.description" class="form-control"/>
            </div>
            <div class="col-md-3">
              <label class="form-label">Mức độ</label>
              <input v-model.trim="form.dx_primary.severity" class="form-control" placeholder="mild/moderate/severe"/>
            </div>

            <div class="col-md-6">
              <label class="form-label">Chẩn đoán phụ (ngăn bởi dấu phẩy)</label>
              <input v-model.trim="form.dx_secondary_text" class="form-control" placeholder="ĐTĐ type 2, RL lipid máu…"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">Chẩn đoán phân biệt (dấu phẩy)</label>
              <input v-model.trim="form.dx_differential_text" class="form-control" placeholder="Bệnh mạch vành, Rối loạn lo âu…"/>
            </div>
          </div>

          <!-- Điều trị -->
          <div class="section-title">Điều trị</div>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th style="width:24%">Tên thuốc</th>
                  <th style="width:14%">Liều</th>
                  <th style="width:18%">Số lần/ngày</th>
                  <th style="width:14%">Thời gian</th>
                  <th style="width:26%">Hướng dẫn</th>
                  <th style="width:4%"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(m, i) in form.medications" :key="i">
                  <td><input v-model.trim="m.name" class="form-control form-control-sm" placeholder="Amlodipine"/></td>
                  <td><input v-model.trim="m.dosage" class="form-control form-control-sm" placeholder="5mg"/></td>
                  <td><input v-model.trim="m.frequency" class="form-control form-control-sm" placeholder="1 lần/ngày"/></td>
                  <td><input v-model.trim="m.duration" class="form-control form-control-sm" placeholder="30 ngày"/></td>
                  <td><input v-model.trim="m.instructions" class="form-control form-control-sm" placeholder="Uống sau ăn sáng"/></td>
                  <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" @click="removeMedication(i)">×</button></td>
                </tr>
                <tr v-if="!form.medications.length">
                  <td colspan="6" class="text-muted small">Chưa có thuốc — bấm “+ Thêm thuốc”</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="d-flex gap-2 flex-wrap mb-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="addMedication">+ Thêm thuốc</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="addProcedure">+ Thêm thủ thuật</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="addLifestyle">+ Thêm tư vấn</button>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Thủ thuật (dấu phẩy)</label>
              <input v-model.trim="form.procedures_text" class="form-control" placeholder="ECG, Siêu âm tim…"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tư vấn lối sống (dấu phẩy)</label>
              <input v-model.trim="form.lifestyle_text" class="form-control" placeholder="Giảm muối, Tập thể dục…"/>
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Ngày tái khám</label>
              <input v-model="form.follow_up.date" type="date" class="form-control" />
            </div>
            <div class="col-md-8">
              <label class="form-label">Ghi chú tái khám</label>
              <input v-model.trim="form.follow_up.notes" class="form-control" />
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
import MedicalRecordService from '@/api/medicalRecordService'
import DoctorService from '@/api/doctorService'
import PatientService from '@/api/patientService'

export default {
  name: 'MedicalRecordsListView',
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
      // combobox + map để render tên
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
    rowKey (r, idx) { return r._id || r.id || `${idx}` },
    isExpanded (row) { return !!this.expanded[this.rowKey(row, 0)] },
    toggleRow (row) {
      const k = this.rowKey(row, 0)
      this.expanded = { ...this.expanded, [k]: !this.expanded[k] }
    },
    fmtDateTime (v) {
      if (!v) return '-'
      try {
        return new Date(v).toLocaleString()
      } catch {
        return v
      }
    },
    statusClass (s) {
      if (s === 'completed') {
        return 'bg-success-subtle text-success'
      } else if (s === 'in_progress') {
        return 'bg-warning-subtle text-warning'
      } else if (s === 'canceled') {
        return 'bg-danger-subtle text-danger'
      } else {
        return 'bg-secondary-subtle text-secondary'
      }
    },
    displayName (o) { return o?.full_name || o?.name || o?.display_name || o?.code || o?.username },

    // Flatten document nested -> flat fields cho list/details/form
    flattenRecord (d = {}) {
      const vi = d.visit_info || {}
      const ex = d.examination || {}
      const vs = ex.vital_signs || {}
      const pe = ex.physical_exam || {}
      const dx = d.diagnosis || {}
      const tp = d.treatment_plan || {}

      return {
        ...d,
        patient_id: d.patient_id || '',
        doctor_id: d.doctor_id || '',
        // visit
        visit_date: vi.visit_date || '',
        visit_type: vi.visit_type || '',
        chief_complaint: vi.chief_complaint || '',
        appointment_id: vi.appointment_id || '',
        // vitals
        vital: {
          temperature: vs.temperature ?? null,
          bp_systolic: vs.blood_pressure?.systolic ?? null,
          bp_diastolic: vs.blood_pressure?.diastolic ?? null,
          heart_rate: vs.heart_rate ?? null,
          respiratory_rate: vs.respiratory_rate ?? null,
          weight: vs.weight ?? null,
          height: vs.height ?? null
        },
        // physical
        physical: {
          general: pe.general || '',
          cardiovascular: pe.cardiovascular || '',
          respiratory: pe.respiratory || '',
          other_findings: pe.other_findings || ''
        },
        // diagnosis
        dx_primary: {
          code: dx.primary?.code || '',
          description: dx.primary?.description || '',
          severity: dx.primary?.severity || ''
        },
        dx_secondary: dx.secondary || [],
        dx_differential: dx.differential || [],
        // treatment
        medications: (tp.medications || []).map(m => ({
          name: m.name || '',
          dosage: m.dosage || '',
          frequency: m.frequency || '',
          duration: m.duration || '',
          instructions: m.instructions || ''
        })),
        procedures: tp.procedures || [],
        lifestyle_advice: tp.lifestyle_advice || [],
        follow_up: {
          date: tp.follow_up?.date || '',
          notes: tp.follow_up?.notes || ''
        },
        // others
        attachments: d.attachments || [],
        status: d.status || 'draft',
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
        visit_date: '',
        visit_type: '',
        chief_complaint: '',
        appointment_id: '',
        vital: { temperature: null, bp_systolic: null, bp_diastolic: null, heart_rate: null, respiratory_rate: null, weight: null, height: null },
        physical: { general: '', cardiovascular: '', respiratory: '', other_findings: '' },
        dx_primary: { code: '', description: '', severity: '' },
        dx_secondary: [],
        dx_differential: [],
        dx_secondary_text: '',
        dx_differential_text: '',
        medications: [],
        procedures: [],
        procedures_text: '',
        lifestyle_advice: [],
        lifestyle_text: '',
        follow_up: { date: '', notes: '' },
        attachments: [],
        status: 'draft',
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
        const res = await MedicalRecordService.list({ q: this.q || undefined, limit: this.pageSize, offset: skip, skip })
        let raw = []; let total = 0; let offset = null
        if (res && Array.isArray(res.rows)) {
          raw = res.rows.map(r => r.doc || r.value || r)
          total = res.total_rows ?? raw.length
          offset = res.offset ?? 0
        } else if (res && Array.isArray(res.data)) {
          raw = res.data; total = res.total ?? raw.length
        } else if (Array.isArray(res)) { raw = res; total = raw.length }

        this.items = (raw || []).map(d => this.flattenRecord(d))
        this.total = total
        this.hasMore = (offset != null)
          ? (offset + this.items.length) < (this.total || 0)
          : (this.page * this.pageSize) < (this.total || 0)

        // nạp options 1 lần để hiển thị tên trong list
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
          DoctorService.list({ limit: 1000 }),
          PatientService.list({ limit: 1000 })
        ])

        const arr = (r) => {
          if (Array.isArray(r?.rows)) {
            return r.rows.map(x => x.doc || x.value || x)
          } else if (Array.isArray(r?.data)) {
            return r.data
          } else if (Array.isArray(r)) {
            return r
          } else {
            return []
          }
        }

        const dList = arr(dRes)
        const pList = arr(pRes)

        const key = (o) => o._id || o.id || o.code || o.username
        const label = (o) => o.full_name || o.name || o.display_name || o.code || o.username || key(o)

        this.doctorOptions = dList.map(o => ({
          value: key(o),
          label: label(o)
        }))
        this.patientOptions = pList.map(o => ({
          value: key(o),
          label: label(o)
        }))

        // map để render tên trong list/details
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

    /* ===== modal ===== */
    openCreate () {
      this.editingId = null
      this.form = this.emptyForm()
      this.showModal = true
      this.ensureOptionsLoaded()
    },
    openEdit (row) {
      const f = this.flattenRecord(row)
      this.editingId = f._id || f.id
      this.form = {
        ...this.emptyForm(),
        ...f,
        dx_secondary_text: (f.dx_secondary || []).join(', '),
        dx_differential_text: (f.dx_differential || []).join(', '),
        procedures_text: (f.procedures || []).join(', '),
        lifestyle_text: (f.lifestyle_advice || []).join(', '),
        // datetime-local cần kiểu "YYYY-MM-DDTHH:mm"
        visit_date: f.visit_date ? new Date(f.visit_date).toISOString().slice(0, 16) : ''
      }
      this.showModal = true
      this.ensureOptionsLoaded()
    },
    close () { if (!this.saving) this.showModal = false },

    addMedication () { this.form.medications = [...this.form.medications, { name: '', dosage: '', frequency: '', duration: '', instructions: '' }] },
    removeMedication (i) { this.form.medications = this.form.medications.filter((_, idx) => idx !== i) },
    addProcedure () {
      const s = this.form.procedures_text ? `${this.form.procedures_text}, ` : ''
      this.form.procedures_text = `${s}Thủ thuật`
    },
    addLifestyle () {
      const s = this.form.lifestyle_text ? `${this.form.lifestyle_text}, ` : ''
      this.form.lifestyle_text = `${s}Tư vấn`
    },

    /* ===== save/remove ===== */
    async save () {
      if (this.saving) return
      this.saving = true
      try {
        const csv = (s) => (s || '').split(',').map(x => x.trim()).filter(Boolean)

        const payload = {
          type: 'medical_record',
          patient_id: this.form.patient_id || undefined,
          doctor_id: this.form.doctor_id || undefined,

          visit_info: {
            visit_date: this.form.visit_date || undefined,
            visit_type: this.form.visit_type || undefined,
            chief_complaint: this.form.chief_complaint || undefined,
            appointment_id: this.form.appointment_id || undefined
          },

          examination: {
            vital_signs: {
              temperature: this.form.vital.temperature ?? undefined,
              blood_pressure: {
                systolic: this.form.vital.bp_systolic ?? undefined,
                diastolic: this.form.vital.bp_diastolic ?? undefined
              },
              heart_rate: this.form.vital.heart_rate ?? undefined,
              respiratory_rate: this.form.vital.respiratory_rate ?? undefined,
              weight: this.form.vital.weight ?? undefined,
              height: this.form.vital.height ?? undefined
            },
            physical_exam: {
              general: this.form.physical.general || undefined,
              cardiovascular: this.form.physical.cardiovascular || undefined,
              respiratory: this.form.physical.respiratory || undefined,
              other_findings: this.form.physical.other_findings || undefined
            }
          },

          diagnosis: {
            primary: {
              code: this.form.dx_primary.code || undefined,
              description: this.form.dx_primary.description || undefined,
              severity: this.form.dx_primary.severity || undefined
            },
            secondary: this.form.dx_secondary?.length ? this.form.dx_secondary : csv(this.form.dx_secondary_text),
            differential: this.form.dx_differential?.length ? this.form.dx_differential : csv(this.form.dx_differential_text)
          },

          treatment_plan: {
            medications: (this.form.medications || []).map(m => ({
              name: m.name || undefined,
              dosage: m.dosage || undefined,
              frequency: m.frequency || undefined,
              duration: m.duration || undefined,
              instructions: m.instructions || undefined
            })),
            procedures: this.form.procedures?.length ? this.form.procedures : csv(this.form.procedures_text),
            lifestyle_advice: this.form.lifestyle_advice?.length ? this.form.lifestyle_advice : csv(this.form.lifestyle_text),
            follow_up: {
              date: this.form.follow_up?.date || undefined,
              notes: this.form.follow_up?.notes || undefined
            }
          },

          status: this.form.status || 'draft'
        }

        if (this.form._id) payload._id = this.form._id
        if (this.form._rev) payload._rev = this.form._rev

        if (this.editingId) await MedicalRecordService.update(this.editingId, payload)
        else await MedicalRecordService.create(payload)

        this.showModal = false
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Lưu thất bại')
      } finally { this.saving = false }
    },

    async remove (row) {
      if (!confirm('Xóa hồ sơ này?')) return
      try {
        const id = row._id || row.id
        await MedicalRecordService.remove(id)
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
.detail-wrap { border-top: 1px solid #e5e7eb; padding: 10px 6px 0; }
.detail-title { font-weight: 700; margin: 10px 0 6px; }
.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 6px 16px;
}
.col-span-2 { grid-column: span 2; }

/* modal */
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.45); display: grid; place-items: center; z-index: 1050; }
.modal-card { width: min(1000px, 96vw); background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 20px 50px rgba(0,0,0,.25); max-height: 92vh; overflow: auto; }
.section-title { font-weight: 600; margin: 14px 0 8px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; }
</style>
