<template>
  <section class="container py-4">
    <!-- Header + Tools -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 mb-0">Xét nghiệm</h2>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" @click="reload" :disabled="loading">Tải lại</button>
        <button class="btn btn-primary" @click="openCreate" :disabled="loading">+ Thêm mới</button>
      </div>
    </div>

    <div class="d-flex align-items-center gap-2 mb-3">
      <input v-model.trim="q" class="form-control" style="max-width: 350px" placeholder="Tìm theo tên xét nghiệm / loại..." @keyup.enter="search" />
      <select v-model="filterRecordId" class="form-select" style="max-width: 300px" @change="applyFilter">
        <option value="">-- Tất cả hồ sơ khám --</option>
        <option v-for="opt in recordOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
      <button class="btn btn-outline-secondary" @click="search">Tìm</button>
      <button v-if="filterRecordId" class="btn btn-outline-danger" @click="clearFilter" title="Xóa bộ lọc">✕</button>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <!-- LIST: 5 cột theo ảnh -->
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th style="width:56px">#</th>
            <th>Loại</th>
            <th>Tên xét nghiệm</th>
            <th>Ngày chỉ định</th>
            <th>Ngày có kết quả</th>
            <th>Trạng thái</th>
            <th style="width:160px">Hành động</th>
          </tr>
        </thead>
        <tbody v-for="(t, idx) in filteredItems" :key="rowKey(t, idx)">
          <tr>
            <td>{{ idx + 1 + (page - 1) * pageSize }}</td>
              <td>{{ t.type }}</td>
              <td>{{ t.name }}</td>
              <td>{{ fmtDateTime(t.ordered_at) }}</td>
              <td>{{ fmtDateTime(t.result_at) }}</td>
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

          <!-- ROW DETAILS -->
          <tr v-if="isExpanded(t)">
            <td :colspan="7">
                <div class="detail-wrap">
                  <div class="detail-title">Thông tin</div>
                  <div class="detail-grid">
                    <div><b>Loại:</b> {{ t.type || '-' }}</div>
                    <div><b>Tên:</b> {{ t.name || '-' }}</div>
                    <div><b>Ngày lấy mẫu:</b> {{ fmtDateTime(t.collected_at) }}</div>
                  </div>

                  <div class="detail-title">Kết quả</div>
                  <ul class="mb-2">
                    <li v-for="(r, i) in t.results_items" :key="i">
                      <b>{{ r.metric }}</b>: {{ r.value }}
                      <span v-if="r.range"> (chuẩn: {{ r.range }})</span>
                      <span v-if="r.note"> - {{ r.note }}</span>
                    </li>
                  </ul>

                  <div class="detail-title">Diễn giải</div>
                  <div class="mb-2">{{ t.interpretation || '-' }}</div>

                  <div class="detail-title">Khác</div>
                  <div class="detail-grid">
                    <div><b>Bệnh nhân:</b> {{ t.patient_id || '-' }}</div>
                    <div><b>Bác sĩ:</b> {{ t.doctor_id || '-' }}</div>
                    <div><b>KTV:</b> {{ t.technician_id || '-' }}</div>
                  </div>

                  <div class="text-muted small mt-2">
                    Tạo lúc: {{ fmtDateTime(t.created_at) }} | Cập nhật: {{ fmtDateTime(t.updated_at) }}
                  </div>
              </div>
            </td>
          </tr>
        </tbody>

        <tbody v-if="!filteredItems.length">
          <tr>
            <td colspan="7" class="text-center text-muted">{{ filterRecordId ? 'Không tìm thấy xét nghiệm cho hồ sơ này' : 'Không có dữ liệu' }}</td>
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
        <h3 class="h6 mb-3">{{ editingId ? 'Sửa xét nghiệm' : 'Thêm xét nghiệm' }}</h3>

        <form @submit.prevent="save">
          <!-- Liên kết -->
          <div class="section-title">Liên kết</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Hồ sơ khám <span class="text-danger">*</span></label>
              <select v-model="form.medical_record_id" class="form-select" required @change="onRecordChange">
                <option value="">-- Chọn hồ sơ khám --</option>
                <option v-for="r in recordOptions" :key="r.value" :value="r.value">{{ r.label }}</option>
              </select>
              <small class="text-muted">Chọn hồ sơ khám để tự động điền bệnh nhân và bác sĩ</small>
            </div>
            <div class="col-md-3">
              <label class="form-label">Loại xét nghiệm <span class="text-danger">*</span></label>
              <select v-model="form.type" class="form-select" required>
                <option value="">-- Chọn loại --</option>
                <option value="blood_work">Xét nghiệm máu</option>
                <option value="urine_analysis">Xét nghiệm nước tiểu</option>
                <option value="imaging">Chẩn đoán hình ảnh</option>
                <option value="biopsy">Sinh thiết</option>
                <option value="culture">Cấy mẫu</option>
                <option value="pathology">Giải phẫu bệnh</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Trạng thái</label>
              <select v-model="form.status" class="form-select">
                <option value="pending">Chờ xử lý</option>
                <option value="in_progress">Đang thực hiện</option>
                <option value="completed">Hoàn thành</option>
                <option value="canceled">Đã hủy</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Bệnh nhân</label>
              <input v-model="form.patient_name" class="form-control" readonly placeholder="Tự động từ hồ sơ" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Bác sĩ chỉ định</label>
              <input v-model="form.doctor_name" class="form-control" readonly placeholder="Tự động từ hồ sơ" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Ngày khám</label>
              <input v-model="form.visit_date" class="form-control" readonly placeholder="Tự động từ hồ sơ" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Kỹ thuật viên</label>
              <input v-model.trim="form.technician_id" type="text" class="form-control" placeholder="Mã KTV..." />
            </div>
          </div>

          <!-- Thông tin xét nghiệm -->
          <div class="section-title">Thông tin xét nghiệm</div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Tên xét nghiệm <span class="text-danger">*</span></label>
              <input v-model.trim="form.name" type="text" class="form-control" required placeholder="Tổng phân tích máu, X-quang phổi..." />
            </div>

            <div class="col-md-4">
              <label class="form-label">Ngày chỉ định</label>
              <input v-model="form.ordered_at" type="datetime-local" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Ngày lấy mẫu</label>
              <input v-model="form.collected_at" type="datetime-local" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Ngày có kết quả</label>
              <input v-model="form.result_at" type="datetime-local" class="form-control" />
            </div>
          </div>

          <!-- Kết quả -->
          <div class="section-title">Kết quả</div>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th style="width:26%">Chỉ số</th>
                  <th style="width:22%">Giá trị</th>
                  <th style="width:32%">Khoảng chuẩn</th>
                  <th style="width:16%">Đánh giá</th>
                  <th style="width:4%"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(r, i) in form.results_items" :key="i">
                  <td><input v-model.trim="r.metric" class="form-control form-control-sm" placeholder="WBC, RBC, ..." /></td>
                  <td><input v-model.trim="r.value" class="form-control form-control-sm" placeholder="7.2 10^9/L" /></td>
                  <td><input v-model.trim="r.range" class="form-control form-control-sm" placeholder="4.0–10.0" /></td>
                  <td><input v-model.trim="r.note" class="form-control form-control-sm" placeholder="normal / high / low" /></td>
                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="removeResult(i)">×</button>
                  </td>
                </tr>
                <tr v-if="!form.results_items.length">
                  <td colspan="5" class="text-muted small">Chưa có chỉ số — bấm “+ Thêm chỉ số” bên dưới</td>
                </tr>
              </tbody>
            </table>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm" @click="addResult">+ Thêm chỉ số</button>

          <div class="mt-3">
            <label class="form-label">Diễn giải</label>
            <textarea v-model.trim="form.interpretation" class="form-control" rows="3"
                      placeholder="Các chỉ số xét nghiệm trong giới hạn bình thường..."></textarea>
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
import MedicalTestService from '@/api/medicalTestService'
import DoctorService from '@/api/doctorService'
import PatientService from '@/api/patientService'
import MedicalRecordService from '@/api/medicalRecordService'

export default {
  name: 'MedicalTestsListView',
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
      // combobox
      doctorOptions: [],
      patientOptions: [],
      recordOptions: [],
      doctorsMap: {},
      patientsMap: {},
      optionsLoaded: false,
      // ✅ Filter
      filterRecordId: '',
      filteredItems: []
    }
  },
  created () {
    // ✅ Check if medical_record_id from query parameter
    if (this.$route.query.medical_record_id) {
      this.filterRecordId = this.$route.query.medical_record_id
    }
    this.fetch()
  },
  watch: {
    items () {
      this.applyFilter()
    }
  },
  methods: {
    /* ===== helpers ===== */
    rowKey (t, idx) { return t._id || t.id || `${idx}` },
    isExpanded (row) { return !!this.expanded[this.rowKey(row, 0)] },
    toggleRow (row) { const k = this.rowKey(row, 0); this.expanded = { ...this.expanded, [k]: !this.expanded[k] } },
    fmtDateTime (v) {
      if (!v) return '-'
      try {
        return new Date(v).toLocaleString('vi-VN')
      } catch {
        return v
      }
    },
    statusClass (s) {
      return s === 'completed'
        ? 'bg-success-subtle text-success'
        : s === 'in_progress'
          ? 'bg-warning-subtle text-warning'
          : s === 'canceled'
            ? 'bg-danger-subtle text-danger'
            : 'bg-secondary-subtle text-secondary'
    },

    // Flatten doc từ cấu trúc API response
    flattenTest (d = {}) {
      console.log('Flattening test:', d) // Debug log

      // Lấy thông tin từ test_info
      const testInfo = d.test_info || {}

      // Xử lý results - chuyển từ object thành array
      const results = d.results || {}
      const resultsItems = []

      // Convert results object thành array items
      Object.keys(results).forEach(key => {
        if (typeof results[key] === 'object' && results[key] !== null) {
          const item = results[key]
          resultsItems.push({
            metric: key.toUpperCase(), // wbc -> WBC, rbc -> RBC
            value: `${item.value || ''} ${item.unit || ''}`.trim(),
            range: item.reference_range || '',
            note: item.status || ''
          })
        }
      })

      const flattened = {
        ...d,
        _id: d._id || d.id,
        _rev: d._rev,
        type: testInfo.test_type || d.test_type || d.type || '',
        name: testInfo.test_name || d.test_name || d.name || '',

        // Timeline fields
        ordered_at: testInfo.ordered_date || d.ordered_date || d.ordered_at || '',
        collected_at: testInfo.sample_collected_date || d.sample_collected_date || d.collected_at || '',
        result_at: testInfo.result_date || d.result_date || d.result_at || '',

        status: d.status || 'pending',
        results_items: resultsItems,
        interpretation: d.interpretation || '',

        // Links
        patient_id: d.patient_id || '',
        doctor_id: d.doctor_id || '',
        technician_id: d.lab_technician || d.technician_id || '',
        medical_record_id: d.medical_record_id || '',

        created_at: d.created_at || null,
        updated_at: d.updated_at || null
      }

      console.log('Flattened result:', flattened) // Debug log
      return flattened
    },

    emptyForm () {
      return {
        _id: null,
        _rev: null,
        type: '',
        name: '',
        ordered_at: '',
        collected_at: '',
        result_at: '',
        status: 'pending',
        results_items: [],
        interpretation: '',
        patient_id: '',
        doctor_id: '',
        patient_name: '',
        doctor_name: '',
        visit_date: '',
        technician_id: '',
        medical_record_id: '',
        created_at: null,
        updated_at: null
      }
    },

    /* ===== data ===== */
    async fetch () {
      this.loading = true
      this.error = ''

      // Debug logging
      const token = localStorage.getItem('access_token')
      console.log('🔍 DEBUG: Access token exists:', !!token)
      console.log('🔍 DEBUG: API call starting to medical-tests endpoint')

      try {
        const skip = (this.page - 1) * this.pageSize
        const res = await MedicalTestService.list({
          q: this.q || undefined,
          limit: this.pageSize,
          offset: skip,
          skip
        })

        console.log('🔍 DEBUG: API Response received:', res) // Debug log

        let raw = []
        let total = 0
        let offset = null

        // Xử lý response từ CouchDB
        if (res && Array.isArray(res.rows)) {
          console.log('🔍 DEBUG: Using res.rows format')
          raw = res.rows.map(r => r.doc || r.value || r)
          total = res.total_rows ?? raw.length
          offset = res.offset ?? 0
        } else if (res && Array.isArray(res.data)) {
          console.log('🔍 DEBUG: Using res.data format')
          raw = res.data
          total = res.total ?? raw.length
        } else if (Array.isArray(res)) {
          console.log('🔍 DEBUG: Using direct array format')
          raw = res
          total = raw.length
        } else {
          console.log('🔍 DEBUG: Unknown response format:', res)
        }

        console.log('🔍 DEBUG: Raw data before flatten:', raw) // Debug log

        // Flatten các test records
        this.items = (raw || []).map(d => this.flattenTest(d))
        this.total = total
        this.hasMore = (offset != null)
          ? (offset + this.items.length) < (this.total || 0)
          : (this.page * this.pageSize) < (this.total || 0)

        console.log('Final items:', this.items) // Debug log
      } catch (e) {
        console.error('Fetch error:', e)
        this.error = e?.response?.data?.message || e?.message || 'Không tải được dữ liệu'
        this.items = []
        this.total = 0
      } finally {
        this.loading = false
      }
    },

    // ✅ Apply filter by medical record
    applyFilter () {
      if (!this.filterRecordId) {
        this.filteredItems = [...this.items]
      } else {
        this.filteredItems = this.items.filter(t => t.medical_record_id === this.filterRecordId)
      }
    },

    clearFilter () {
      this.filterRecordId = ''
      this.applyFilter()
    },

    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },

    /* ===== combobox ===== */
    async ensureOptionsLoaded () {
      if (this.optionsLoaded) return
      try {
        const [dRes, pRes, rRes] = await Promise.all([
          DoctorService.list({ limit: 1000 }).catch(() => ({ rows: [] })),
          PatientService.list({ limit: 1000 }).catch(() => ({ rows: [] })),
          MedicalRecordService.list({ limit: 1000 }).catch(() => ({ rows: [] }))
        ])

        const extractArray = r => {
          if (Array.isArray(r?.rows)) return r.rows.map(x => x.doc || x.value || x)
          if (Array.isArray(r?.data)) return r.data
          if (Array.isArray(r)) return r
          return []
        }

        const dList = extractArray(dRes)
        const pList = extractArray(pRes)
        const rList = extractArray(rRes)

        const key = o => o._id || o.id || o.code
        const makeLabel = (o) => o?.personal_info?.full_name || o.fullName || o.full_name || o.name || o.display_name || o.code || o.username || key(o)

        this.doctorOptions = dList.map(o => ({
          value: key(o),
          label: makeLabel(o)
        }))

        this.patientOptions = pList.map(o => ({
          value: key(o),
          label: makeLabel(o)
        }))

        // Create medical record options
        this.recordOptions = rList.map(rec => {
          const patient = pList.find(p => key(p) === rec.patient_id)
          const doctor = dList.find(d => key(d) === rec.doctor_id)
          const visitDate = rec.visit_info?.visit_date || rec.visit_date
          const dateStr = visitDate ? new Date(visitDate).toLocaleDateString('vi-VN') : ''
          const visitType = rec.visit_info?.visit_type || rec.visit_type || 'khám'

          return {
            value: key(rec),
            label: `${dateStr} - ${makeLabel(patient)} - ${visitType}`,
            patient_id: rec.patient_id,
            doctor_id: rec.doctor_id,
            patient_name: makeLabel(patient),
            doctor_name: makeLabel(doctor),
            visit_date: dateStr
          }
        })

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
        console.error('Error loading options:', e)
        this.doctorOptions = []
        this.patientOptions = []
        this.recordOptions = []
      }
    },

    // Auto-fill from medical record
    onRecordChange () {
      const recordId = this.form.medical_record_id
      if (!recordId) return

      const selectedRecord = this.recordOptions.find(opt => opt.value === recordId)
      if (selectedRecord) {
        this.form.patient_id = selectedRecord.patient_id
        this.form.doctor_id = selectedRecord.doctor_id
        this.form.patient_name = selectedRecord.patient_name
        this.form.doctor_name = selectedRecord.doctor_name
        this.form.visit_date = selectedRecord.visit_date

        // ✅ TỰ ĐỘNG điền "Ngày chỉ định" = Ngày khám từ hồ sơ
        if (selectedRecord.visit_date) {
          try {
            // Chuyển visit_date sang datetime-local format
            const visitDate = new Date(selectedRecord.visit_date)
            this.form.ordered_at = visitDate.toISOString().slice(0, 16)
          } catch (e) {
            console.error('Error parsing visit_date:', e)
          }
        }
      }
    },

    /* ===== modal ===== */
    openCreate () {
      this.editingId = null
      this.form = this.emptyForm()
      this.showModal = true
      this.ensureOptionsLoaded()
    },

    async openEdit (row) {
      const f = this.flattenTest(row)
      this.editingId = f._id || f.id
      this.form = { ...this.emptyForm(), ...f }
      this.showModal = true
      await this.ensureOptionsLoaded()

      // ✅ TỰ ĐỘNG điền thông tin từ hồ sơ khi edit
      if (this.form.medical_record_id) {
        this.onRecordChange()
      }
    },

    close () {
      if (!this.saving) {
        this.showModal = false
        this.editingId = null
        this.form = this.emptyForm()
      }
    },

    addResult () {
      this.form.results_items = [...this.form.results_items, {
        metric: '',
        value: '',
        range: '',
        note: ''
      }]
    },

    removeResult (i) {
      this.form.results_items = this.form.results_items.filter((_, idx) => idx !== i)
    },

    /* ===== save/remove ===== */
    async save () {
      if (this.saving) return
      this.saving = true
      try {
        // Convert results_items array back to object format for API
        const resultsObject = {}
        this.form.results_items.forEach(item => {
          if (item.metric && item.value) {
            const [value, unit] = item.value.split(' ')
            resultsObject[item.metric.toLowerCase()] = {
              value: parseFloat(value) || value,
              unit: unit || '',
              reference_range: item.range || '',
              status: item.note || 'normal'
            }
          }
        })

        // Build payload theo cấu trúc API expects
        const payload = {
          type: 'medical_test',
          test_info: {
            test_type: this.form.type || 'blood_work',
            test_name: this.form.name || '',
            ordered_date: this.form.ordered_at || undefined,
            sample_collected_date: this.form.collected_at || undefined,
            result_date: this.form.result_at || undefined
          },
          results: resultsObject,
          interpretation: this.form.interpretation || '',
          status: this.form.status || 'pending',
          patient_id: this.form.patient_id || undefined,
          doctor_id: this.form.doctor_id || undefined,
          lab_technician: this.form.technician_id || undefined,
          medical_record_id: this.form.medical_record_id || undefined
        }

        if (this.form._id) payload._id = this.form._id
        if (this.form._rev) payload._rev = this.form._rev

        console.log('Saving payload:', payload) // Debug log

        if (this.editingId) {
          await MedicalTestService.update(this.editingId, payload)
        } else {
          await MedicalTestService.create(payload)
        }

        this.showModal = false
        await this.fetch()
      } catch (e) {
        console.error('Save error:', e)
        alert(e?.response?.data?.message || e?.message || 'Lưu thất bại')
      } finally {
        this.saving = false
      }
    },

    // ✅ FIX: Remove với rev parameter
    async remove (row) {
      if (!confirm(`Xóa xét nghiệm "${row.name || 'này'}"?`)) return

      try {
        const id = row._id || row.id
        if (!id) {
          alert('Không tìm thấy ID xét nghiệm')
          return
        }

        const rev = row._rev
        if (!rev) {
          alert('Không tìm thấy revision của document')
          return
        }

        // ✅ Truyền cả id và rev
        await MedicalTestService.remove(id, rev)
        alert('Xóa thành công!')
        await this.fetch()
      } catch (e) {
        console.error('Remove error:', e)
        alert(e?.response?.data?.message || e?.message || 'Xóa thất bại')
      }
    }
  }
}
</script>

<style scoped>
:deep(table.table) th, :deep(table.table) td { vertical-align: middle; }

/* row details */
.detail-wrap { border-top: 1px solid #e5e7eb; padding: 10px 6px 0; }
.detail-title { font-weight: 700; margin: 10px 0 6px; }
.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 6px 16px;
}

/* modal */
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.45); display: grid; place-items: center; z-index: 1050; }
.modal-card { width: min(980px, 96vw); background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 20px 50px rgba(0,0,0,.25); max-height: 92vh; overflow: auto; }
.section-title { font-weight: 600; margin: 14px 0 8px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; }
</style>
