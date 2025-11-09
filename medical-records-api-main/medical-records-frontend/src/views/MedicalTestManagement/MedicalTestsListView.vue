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

    <div class="d-flex align-items-center mb-3" style="max-width: 520px">
      <input v-model.trim="q" class="form-control me-2" placeholder="Tìm theo tên xét nghiệm / loại..." @keyup.enter="search" />
      <button class="btn btn-outline-secondary" @click="search">Tìm</button>
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
        <tbody>
          <template v-for="(t, idx) in items" :key="rowKey(t, idx)">
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
        <h3 class="h6 mb-3">{{ editingId ? 'Sửa xét nghiệm' : 'Thêm xét nghiệm' }}</h3>

        <form @submit.prevent="save">
          <!-- Nhóm thông tin -->
          <div class="section-title">Thông tin</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Loại</label>
              <input v-model.trim="form.type" type="text" class="form-control" placeholder="blood_work, imaging, ..." />
            </div>
            <div class="col-md-8">
              <label class="form-label">Tên xét nghiệm</label>
              <input v-model.trim="form.name" type="text" class="form-control" placeholder="Tổng phân tích máu..." />
            </div>

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
              <label class="form-label">KTV</label>
              <input v-model.trim="form.technician_id" type="text" class="form-control" placeholder="Mã KTV / username..." />
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

            <div class="col-md-4">
              <label class="form-label">Trạng thái</label>
              <select v-model="form.status" class="form-select">
                <option value="pending">pending</option>
                <option value="in_progress">in_progress</option>
                <option value="completed">completed</option>
                <option value="canceled">canceled</option>
              </select>
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
      optionsLoaded: false
    }
  },
  created () { this.fetch() },
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

    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },

    /* ===== combobox ===== */
    async ensureOptionsLoaded () {
      if (this.optionsLoaded) return
      try {
        const [dRes, pRes] = await Promise.all([
          DoctorService.list({ limit: 1000 }).catch(() => ({ rows: [] })),
          PatientService.list({ limit: 1000 }).catch(() => ({ rows: [] }))
        ])

        const extractArray = r => {
          if (Array.isArray(r?.rows)) return r.rows.map(x => x.doc || x.value || x)
          if (Array.isArray(r?.data)) return r.data
          if (Array.isArray(r)) return r
          return []
        }

        const makeLabel = (o) => o.fullName || o.full_name || o.name || o.display_name || o.code || o.username || o._id || o.id

        this.doctorOptions = extractArray(dRes).map(o => ({
          value: o._id || o.id || o.code,
          label: makeLabel(o)
        }))

        this.patientOptions = extractArray(pRes).map(o => ({
          value: o._id || o.id || o.code,
          label: makeLabel(o)
        }))

        this.optionsLoaded = true
      } catch (e) {
        console.error('Error loading options:', e)
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
      const f = this.flattenTest(row)
      this.editingId = f._id || f.id
      this.form = { ...this.emptyForm(), ...f }
      this.showModal = true
      this.ensureOptionsLoaded()
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
