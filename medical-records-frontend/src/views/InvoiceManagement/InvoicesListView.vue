<template>
  <section class="container py-4">
    <!-- Header + Tools -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 mb-0">Hoá đơn</h2>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" @click="reload" :disabled="loading">Tải lại</button>
        <button class="btn btn-primary" @click="openCreate" :disabled="loading">+ Thêm mới</button>
      </div>
    </div>

    <div class="d-flex align-items-center mb-3" style="max-width: 520px">
      <input v-model.trim="q" class="form-control me-2" placeholder="Tìm số hoá đơn / bệnh nhân…" @keyup.enter="search" />
      <button class="btn btn-outline-secondary" @click="search">Tìm</button>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <!-- LIST: số HĐ / ngày / bệnh nhân / tổng / trạng thái -->
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
        <tr>
          <th style="width:56px">#</th>
          <th>Số hoá đơn</th>
          <th>Ngày HĐ</th>
          <th>Bệnh nhân</th>
          <th>Tổng tiền</th>
          <th>Trạng thái</th>
          <th style="width:180px">Hành động</th>
        </tr>
        </thead>
        <tbody v-for="(inv, idx) in items" :key="rowKey(inv, idx)">
          <tr>
            <td>{{ idx + 1 + (page - 1) * pageSize }}</td>
            <td>{{ inv.invoice_number }}</td>
            <td>{{ fmtDate(inv.invoice_date) }}</td>
            <td>{{ displayName(patientsMap[inv.patient_id]) || inv.patient_id }}</td>
            <td>{{ n(inv.total_amount) }}</td>
            <td><span :class="['badge', statusClass(inv.payment_status)]">{{ inv.payment_status || '-' }}</span></td>
            <td>
              <div class="btn-group">
                <button class="btn btn-sm btn-outline-secondary" @click="toggleRow(inv)" title="Xem chi tiết">
                  <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-success" @click="downloadInvoice(inv)" :disabled="loading" title="In hóa đơn">
                  <i class="bi bi-printer"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary" @click="openEdit(inv)" title="Sửa">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" @click="remove(inv)" :disabled="loading" title="Xóa">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </td>
          </tr>

          <!-- DETAILS xổ khi Xem -->
          <tr v-if="isExpanded(inv)">
            <td :colspan="7">
              <div class="detail-wrap">
                <div class="detail-title">Thông tin</div>
                <div class="detail-grid">
                  <div><b>Số HĐ:</b> {{ inv.invoice_number }}</div>
                  <div><b>Ngày HĐ:</b> {{ fmtDate(inv.invoice_date) }}</div>
                  <div><b>Hạn thanh toán:</b> {{ fmtDate(inv.due_date) }}</div>
                  <div><b>Hồ sơ khám:</b> {{ inv.medical_record_id || '-' }}</div>
                </div>

                <div class="detail-title">Dịch vụ</div>
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead><tr><th>Loại</th><th>Mô tả</th><th class="text-end">SL</th><th class="text-end">Đơn giá</th><th class="text-end">Thành tiền</th></tr></thead>
                    <tbody>
                      <tr v-for="(s, i) in inv.services" :key="i">
                        <td>{{ s.service_type }}</td>
                        <td>{{ s.description }}</td>
                        <td class="text-end">{{ s.quantity }}</td>
                        <td class="text-end">{{ n(s.unit_price) }}</td>
                        <td class="text-end">{{ n(s.total_price) }}</td>
                      </tr>
                      <tr v-if="!inv.services || !inv.services.length">
                        <td colspan="5" class="text-muted">-</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div class="detail-title">Thanh toán</div>
                <div class="detail-grid">
                  <div><b>Tạm tính:</b> {{ n(inv.subtotal) }}</div>
                  <div><b>Thuế:</b> {{ inv.tax_rate ?? 0 }} → {{ n(inv.tax_amount) }}</div>
                  <div><b>Tổng cộng:</b> {{ n(inv.total_amount) }}</div>
                  <div><b>Bảo hiểm chi trả:</b> {{ inv.insurance_coverage ?? 0 }} → {{ n(inv.insurance_amount) }}</div>
                  <div><b>BN phải trả:</b> {{ n(inv.patient_payment) }}</div>
                  <div><b>PTTT:</b> {{ inv.payment_method || '-' }}</div>
                  <div><b>Ngày thanh toán:</b> {{ fmtDateTime(inv.paid_date) }}</div>
                </div>

                <div class="text-muted small mt-2">
                  Tạo: {{ fmtDateTime(inv.created_at) }} | Cập nhật: {{ fmtDateTime(inv.updated_at) }}
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

      <!-- ✅ BETTER: Enhanced pagination với page selector -->
      <div class="d-flex justify-content-between align-items-center">
        <div>
          Trang {{ page }} / {{ Math.max(1, Math.ceil((total || 0) / pageSize)) }}
          <span class="text-muted">({{ total || 0 }} tổng)</span>
        </div>

        <!-- Page selector -->
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-outline-secondary" @click="prev" :disabled="page <= 1 || loading">‹ Trước</button>

          <!-- Quick page jumps -->
          <select v-model.number="page" class="form-select form-select-sm" style="width: auto;" @change="fetch">
            <option v-for="p in Math.min(10, Math.ceil((total || 0) / pageSize))" :key="p" :value="p">{{ p }}</option>
          </select>

          <button class="btn btn-outline-secondary" @click="next" :disabled="!hasMore || loading">Sau ›</button>
        </div>
      </div>
    </div>

    <!-- MODAL: form đầy đủ + combobox -->
    <div v-if="showModal" class="modal-backdrop" @mousedown.self="close">
      <div class="modal-card">
        <h3 class="h6 mb-3">{{ editingId ? 'Sửa hoá đơn' : 'Thêm hoá đơn' }}</h3>

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
              <label class="form-label">Hồ sơ khám</label>
              <select v-model="form.medical_record_id" class="form-select">
                <option value="">-- chọn hồ sơ (theo bệnh nhân) --</option>
                <option v-for="r in recordOptions" :key="r.value" :value="r.value">{{ r.label }}</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Trạng thái thanh toán</label>
              <select v-model="form.payment_status" class="form-select">
                <option value="unpaid">unpaid</option>
                <option value="partial">partial</option>
                <option value="paid">paid</option>
                <option value="void">void</option>
              </select>
            </div>
          </div>

          <!-- Thông tin HĐ -->
          <div class="section-title">Thông tin hoá đơn</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Số hoá đơn</label>
              <input v-model.trim="form.invoice_number" class="form-control" placeholder="INV-2024-001" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Ngày hoá đơn</label>
              <input v-model="form.invoice_date" type="date" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Hạn thanh toán</label>
              <input v-model="form.due_date" type="date" class="form-control" />
            </div>
          </div>

          <!-- Dịch vụ -->
          <div class="section-title">Dịch vụ</div>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
              <tr>
                <th style="width:16%">Loại</th>
                <th style="width:36%">Mô tả / Thuốc</th>
                <th style="width:10%" class="text-end">SL</th>
                <th style="width:16%" class="text-end">Đơn giá</th>
                <th style="width:16%" class="text-end">Thành tiền</th>
                <th style="width:6%"></th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="(s, i) in form.services" :key="i">
                <td>
                  <select v-model="s.service_type" class="form-select form-select-sm" @change="onServiceTypeChange(i)" :disabled="s.medication_id">
                    <option value="consultation">Khám bệnh</option>
                    <option value="medication">Thuốc</option>
                    <option value="test">Xét nghiệm</option>
                    <option value="procedure">Thủ thuật</option>
                  </select>
                </td>
                <td>
                  <!-- ✅ Medication lookup -->
                  <div v-if="s.service_type === 'medication'" class="position-relative">
                    <!-- Select dropdown -->
                    <select
                      v-if="!s.medication_id"
                      v-model="s.selected_medication_temp"
                      class="form-select form-select-sm"
                      @change="onMedicationSelect(i)"
                      @focus="loadMedicationOptions"
                    >
                      <option value="">-- Chọn thuốc --</option>
                      <optgroup v-for="group in groupedMedications" :key="group.label" :label="group.label">
                        <option
                          v-for="med in group.medications"
                          :key="med._id"
                          :value="med._id"
                        >
                          {{ getMedicationName(med) }} ({{ getMedicationStrength(med) }}) - {{ formatPrice(getMedicationPrice(med)) }}đ - Tồn: {{ getMedicationStock(med) }}
                        </option>
                      </optgroup>
                    </select>

                    <!-- Selected medication display -->
                    <div v-if="s.medication_id" class="selected-medication">
                      <div class="fw-bold text-success">✓ {{ s.description }}</div>
                      <div class="small text-muted">
                        Giá: {{ formatPrice(s.unit_price) }}đ | Tồn: {{ s.available_stock || 0 }}
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-secondary ms-2"
                          @click="clearMedication(i)"
                        >
                          Đổi thuốc
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Text input cho các service khác -->
                  <input
                    v-else
                    v-model.trim="s.description"
                    class="form-control form-control-sm"
                    placeholder="Mô tả dịch vụ"
                  />
                </td>
                <td>
                  <!-- ✅ Quantity controls với + - buttons cho medication -->
                  <div v-if="s.service_type === 'medication' && s.medication_id" class="input-group input-group-sm">
                    <button
                      type="button"
                      class="btn btn-outline-secondary"
                      @click="decreaseQuantity(i)"
                      :disabled="s.quantity <= 1"
                    >-</button>
                    <input
                      v-model.number="s.quantity"
                      type="number"
                      min="1"
                      class="form-control text-center"
                      @input="recalcService(i)"
                      readonly
                    />
                    <button
                      type="button"
                      class="btn btn-outline-secondary"
                      @click="increaseQuantity(i)"
                    >+</button>
                  </div>

                  <!-- Normal input cho service khác -->
                  <input
                    v-else
                    v-model.number="s.quantity"
                    type="number"
                    min="0"
                    class="form-control form-control-sm text-end"
                    @input="recalcService(i)"
                  />
                </td>
                <td>
                  <input
                    v-model.number="s.unit_price"
                    type="number"
                    min="0"
                    class="form-control form-control-sm text-end"
                    @input="recalcService(i)"
                    :readonly="s.medication_id"
                  />
                </td>
                <td class="text-end">{{ n(s.total_price) }}</td>
                <td class="text-end">
                  <button type="button" class="btn btn-sm btn-outline-danger" @click="removeService(i)">×</button>
                </td>
              </tr>
              <tr v-if="!form.services.length">
                <td colspan="6" class="text-muted small">Chưa có dịch vụ — bấm "+ Thêm dịch vụ"</td>
              </tr>
              </tbody>
            </table>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm" @click="addService">+ Thêm dịch vụ</button>

          <!-- Thanh toán -->
          <div class="section-title">Thanh toán</div>
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Tạm tính</label>
              <input v-model.number="form.subtotal" type="number" min="0" class="form-control" readonly />
            </div>
            <div class="col-md-3">
              <label class="form-label">Thuế (%)</label>
              <input v-model.number="form.tax_rate" type="number" value="0" class="form-control" readonly />
            </div>
            <div class="col-md-3">
              <label class="form-label">Tiền thuế</label>
              <input v-model.number="form.tax_amount" type="number" value="0" class="form-control" readonly />
            </div>
            <div class="col-md-3">
              <label class="form-label">Tổng cộng</label>
              <input v-model.number="form.total_amount" type="number" min="0" class="form-control" readonly />
            </div>

            <div class="col-md-3">
              <label class="form-label">BH chi trả (%)</label>
              <input v-model.number="form.insurance_coverage" type="number" min="0" max="100" class="form-control" readonly />
            </div>
            <div class="col-md-3">
              <label class="form-label">Tiền BH</label>
              <input v-model.number="form.insurance_amount" type="number" min="0" class="form-control" readonly />
            </div>
            <div class="col-md-3">
              <label class="form-label">BN phải trả</label>
              <input v-model.number="form.patient_payment" type="number" min="0" class="form-control" readonly />
            </div>
            <div class="col-md-3">
              <label class="form-label">PT Thanh toán</label>
              <select v-model="form.payment_method" class="form-select" @change="onPaymentMethodChange">
                <option value="">-- Chọn --</option>
                <option value="cash">Tiền mặt</option>
                <option value="card">Thẻ</option>
                <option value="transfer">Chuyển khoản</option>
                <option value="insurance">Bảo hiểm</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Ngày thanh toán</label>
              <input
                v-model="form.paid_date"
                type="datetime-local"
                class="form-control"
                :readonly="form.payment_status !== 'paid'"
              />
            </div>

            <!-- ✅ NEW: Payment status display -->
            <div class="col-md-12">
              <div class="d-flex align-items-center gap-3">
                <span class="fw-bold">Trạng thái:</span>
                <span :class="['badge', 'fs-6', statusClass(form.payment_status)]">
                  {{ getPaymentStatusText(form.payment_status) }}
                </span>

                <!-- Quick payment buttons -->
                <div class="btn-group btn-group-sm" v-if="form.payment_status !== 'paid'">
                  <button
                    type="button"
                    class="btn btn-outline-success"
                    @click="markAsPaid"
                    :disabled="form.total_amount <= 0"
                  >
                    Đánh dấu đã thanh toán
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-warning"
                    @click="markAsPartial"
                    :disabled="form.total_amount <= 0"
                  >
                    Thanh toán một phần
                  </button>
                </div>
              </div>
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
import InvoiceService from '@/api/invoiceService'
import PatientService from '@/api/patientService'
import MedicationService from '@/api/medicationService' // ✅ Import MedicationService

// Helper function tạo số hóa đơn random
function generateInvoiceNumber () {
  const now = new Date()
  const year = now.getFullYear()
  const month = (now.getMonth() + 1).toString().padStart(2, '0')
  const day = now.getDate().toString().padStart(2, '0')
  const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0')

  return `INV${year}${month}${day}${random}`
}

export default {
  name: 'InvoicesListView',
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
      // combos
      patientOptions: [],
      recordOptions: [],
      patientsMap: {},
      optionsLoaded: false,
      // ✅ NEW: Medication lookup data
      medicationOptions: {},
      showMedicationOptions: {},
      medicationSearchTimers: {},
      medicationBlurTimers: {}, // Prevent dropdown close when clicking option

      // ✅ UPDATED: Simpler medication state
      allMedications: [],
      groupedMedications: [],
      medicationsLoaded: false
    }
  },
  created () { this.fetch() },
  computed: {
    // ✅ UPDATED: Enhanced auto payment status
    autoPaymentStatus () {
      const total = Number(this.form.total_amount || 0)
      const patientPayment = Number(this.form.patient_payment || 0)

      if (total === 0) return 'unpaid'
      if (patientPayment >= total) return 'paid'
      if (patientPayment > 0) return 'partial'
      return 'unpaid'
    }
  },
  watch: {
    // ✅ UPDATED: Auto sync payment status và method
    'form.patient_payment' () {
      const newStatus = this.autoPaymentStatus
      if (this.form.payment_status !== newStatus) {
        this.form.payment_status = newStatus
        this.updatePaymentDateTime()
      }
    },
    'form.total_amount' () {
      const newStatus = this.autoPaymentStatus
      if (this.form.payment_status !== newStatus) {
        this.form.payment_status = newStatus
        this.updatePaymentDateTime()
      }
    }
  },
  methods: {
    /* ===== helpers ===== */
    rowKey (r, idx) { return r._id || r.id || `${idx}` },
    isExpanded (row) { return !!this.expanded[this.rowKey(row, 0)] },
    toggleRow (row) {
      const k = this.rowKey(row, 0)
      this.expanded = { ...this.expanded, [k]: !this.expanded[k] }
    },
    fmtDate (v) {
      if (!v) return '-'
      try {
        return new Date(v).toISOString().slice(0, 10)
      } catch {
        return v
      }
    },
    fmtDateTime (v) {
      if (!v) return '-'
      try {
        return new Date(v).toLocaleString()
      } catch {
        return v
      }
    },
    // ✅ NEW: Format price method thay cho filter
    formatPrice (value) {
      return Number(value || 0).toLocaleString()
    },
    n (v) {
      return this.formatPrice(v)
    },
    displayName (o) {
      return o?.full_name || o?.name || o?.display_name || o?.code || o?.username
    },
    statusClass (s) {
      if (s === 'paid') {
        return 'bg-success-subtle text-success'
      } else if (s === 'partial') {
        return 'bg-warning-subtle text-warning'
      } else if (s === 'void') {
        return 'bg-danger-subtle text-danger'
      } else {
        return 'bg-secondary-subtle text-secondary'
      }
    },

    // chuyển doc lồng -> phẳng
    flattenInvoice (d = {}) {
      const info = d.invoice_info || {}
      const pay = d.payment_info || {}
      return {
        ...d,
        patient_id: d.patient_id || '',
        medical_record_id: d.medical_record_id || '',
        invoice_number: info.invoice_number || d.invoice_number || '',
        invoice_date: info.invoice_date || d.invoice_date || '',
        due_date: info.due_date || d.due_date || '',
        services: (d.services || []).map(s => ({
          service_type: s.service_type || '',
          description: s.description || '',
          quantity: s.quantity ?? 0,
          unit_price: s.unit_price ?? 0,
          total_price: s.total_price ?? (Number(s.quantity || 0) * Number(s.unit_price || 0))
        })),
        subtotal: pay.subtotal ?? 0,
        tax_rate: pay.tax_rate ?? 0,
        tax_amount: pay.tax_amount ?? 0,
        total_amount: pay.total_amount ?? 0,
        insurance_coverage: pay.insurance_coverage ?? 0,
        insurance_amount: pay.insurance_amount ?? 0,
        patient_payment: pay.patient_payment ?? 0,
        payment_status: d.payment_status || 'unpaid',
        payment_method: d.payment_method || '',
        paid_date: d.paid_date || '',
        created_at: d.created_at || null,
        updated_at: d.updated_at || null
      }
    },

    /* ===== data ===== */
    async fetch () {
      this.loading = true
      this.error = ''
      try {
        const skip = (this.page - 1) * this.pageSize
        const res = await InvoiceService.list({
          q: this.q || undefined,
          limit: this.pageSize,
          offset: skip,
          skip
        })

        let raw = []
        let total = 0
        let offset = null

        if (res && Array.isArray(res.rows)) {
          raw = res.rows.map(r => r.doc || r.value || r)
          total = res.total_rows ?? raw.length
          offset = res.offset ?? 0
        } else if (res && Array.isArray(res.data)) {
          raw = res.data
          total = res.total ?? raw.length
        } else if (Array.isArray(res)) {
          raw = res
          total = res.length
        }

        this.items = (raw || []).map(d => this.flattenInvoice(d))
        this.total = total
        this.hasMore = (offset != null)
          ? (offset + this.items.length) < (this.total || 0)
          : (this.page * this.pageSize) < (this.total || 0)

        await this.ensureOptionsLoaded()
      } catch (e) {
        console.error(e)
        this.error = e?.response?.data?.message || e?.message || 'Không tải được dữ liệu'
      } finally {
        this.loading = false
      }
    },

    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },

    /* ===== combos ===== */
    async ensureOptionsLoaded () {
      if (this.optionsLoaded) return
      try {
        const r = await PatientService.list({ limit: 1000 })
        const arr = Array.isArray(r?.rows)
          ? r.rows.map(x => x.doc || x.value || x)
          : (Array.isArray(r?.data) ? r.data : (Array.isArray(r) ? r : []))

        const key = o => o._id || o.id || o.code || o.username
        const label = o => o.full_name || o.name || o.display_name || o.code || o.username || key(o)

        this.patientOptions = arr.map(o => ({
          value: key(o),
          label: label(o)
        }))

        // Sửa lỗi ESLint: thay reduce với comma operator thành forEach
        this.patientsMap = {}
        arr.forEach(o => {
          this.patientsMap[key(o)] = o
        })

        this.optionsLoaded = true
      } catch (e) {
        console.error(e)
        this.patientOptions = []
      }
    },

    async onPatientChange () {
      this.form.medical_record_id = ''
      await this.loadRecordsForPatient(this.form.patient_id)
      await this.loadPatientInsurance(this.form.patient_id)
    },

    async loadRecordsForPatient (patientId) {
      try {
        if (!patientId) {
          this.recordOptions = []
          return
        }

        const r = await PatientService.records(patientId, { limit: 1000 })
        const arr = Array.isArray(r?.rows)
          ? r.rows.map(x => x.doc || x.value || x)
          : (Array.isArray(r?.data) ? r.data : (Array.isArray(r) ? r : []))

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
    emptyForm () {
      const now = new Date()
      return {
        _id: null,
        _rev: null,
        patient_id: '',
        medical_record_id: '',
        invoice_number: generateInvoiceNumber(),
        invoice_date: now.toISOString().slice(0, 10), // Today
        due_date: now.toISOString().slice(0, 10), // Same day payment
        services: [],
        subtotal: 0,
        tax_rate: 0, // ✅ 0% tax
        tax_amount: 0,
        total_amount: 0,
        insurance_coverage: 0,
        insurance_amount: 0,
        patient_payment: 0,
        payment_status: 'unpaid',
        payment_method: '',
        paid_date: now.toISOString().slice(0, 16), // ✅ Current datetime
        created_at: null,
        updated_at: null
      }
    },

    openCreate () {
      this.editingId = null
      this.form = this.emptyForm()

      // ✅ Set payment date to current time
      const now = new Date()
      this.form.paid_date = now.toISOString().slice(0, 16)

      // ✅ Default payment method
      this.form.payment_method = 'cash'

      this.showModal = true
      this.ensureOptionsLoaded()
      this.loadMedicationOptions()
      this.recordOptions = []
    },

    // Thêm method để regenerate số HĐ manually
    regenerateInvoiceNumber () {
      this.form.invoice_number = generateInvoiceNumber()
    },

    openEdit (row) {
      const f = this.flattenInvoice(row)
      this.editingId = f._id || f.id
      this.form = {
        ...this.emptyForm(),
        ...f,
        // date inputs (yyyy-mm-dd / yyyy-mm-ddTHH:mm)
        invoice_date: (f.invoice_date || '').toString().slice(0, 10),
        due_date: (f.due_date || '').toString().slice(0, 10),
        paid_date: f.paid_date ? new Date(f.paid_date).toISOString().slice(0, 16) : ''
      }
      this.showModal = true
      this.ensureOptionsLoaded()
      this.loadMedicationOptions() // Load medications
      this.loadRecordsForPatient(this.form.patient_id)
      this.recalcAllServices()
      this.recalcTotals()
    },

    close () {
      if (!this.saving) this.showModal = false
    },

    // ✅ NEW: Service type change handler
    onServiceTypeChange (serviceIndex) {
      const service = this.form.services[serviceIndex]

      if (service.service_type === 'medication') {
        // Reset medication fields
        this.clearMedication(serviceIndex)
      } else {
        // Reset to manual input
        this.clearMedication(serviceIndex)
      }

      this.recalcService(serviceIndex)
    },

    // ✅ UPDATED: Enhanced search với better error handling
    selectMedication (serviceIndex, medication) {
      console.log('Selecting medication:', medication) // Debug

      if (medication.error) {
        return // Don't select error items
      }

      const service = this.form.services[serviceIndex]

      // Clear timers
      if (this.medicationBlurTimers[serviceIndex]) {
        clearTimeout(this.medicationBlurTimers[serviceIndex])
      }

      // Set medication data
      service.medication_id = medication._id || medication.id
      service.medication_query = this.getMedicationName(medication)
      service.description = `${this.getMedicationName(medication)} (${this.getMedicationStrength(medication)})`
      service.unit_price = Number(this.getMedicationPrice(medication))
      service.available_stock = Number(this.getMedicationStock(medication))

      // Auto set quantity = 1 if not set
      if (!service.quantity || service.quantity <= 0) {
        service.quantity = 1
      }

      // Hide dropdown
      this.showMedicationOptions[serviceIndex] = false
      this.medicationOptions[serviceIndex] = []

      console.log('Updated service:', service) // Debug

      this.recalcService(serviceIndex)
    },

    // ✅ UPDATED: Clear medication selection
    clearMedication (serviceIndex) {
      const service = this.form.services[serviceIndex]

      service.medication_id = null
      service.medication_query = ''
      service.description = ''
      service.unit_price = 0
      service.available_stock = undefined
      service.selected_medication_temp = ''

      this.medicationOptions[serviceIndex] = []
      this.showMedicationOptions[serviceIndex] = false

      this.recalcService(serviceIndex)
    },

    addService () {
      this.form.services = [...this.form.services, {
        service_type: 'consultation',
        description: '',
        quantity: 1, // ✅ Default quantity = 1
        unit_price: 0,
        total_price: 0,
        // medication fields
        medication_id: null,
        selected_medication_temp: '',
        available_stock: undefined
      }]
      this.recalcTotals()
    },

    removeService (i) {
      // Clear all timers
      if (this.medicationSearchTimers[i]) {
        clearTimeout(this.medicationSearchTimers[i])
        delete this.medicationSearchTimers[i]
      }

      if (this.medicationBlurTimers[i]) {
        clearTimeout(this.medicationBlurTimers[i])
        delete this.medicationBlurTimers[i]
      }

      // Remove from options
      delete this.medicationOptions[i]
      delete this.showMedicationOptions[i]

      // Remove service
      this.form.services = this.form.services.filter((_, idx) => idx !== i)
      this.recalcTotals()
    },

    recalcService (i) {
      const s = this.form.services[i]
      const qty = Number(s.quantity || 0)
      const up = Number(s.unit_price || 0)
      s.total_price = qty * up
      this.recalcTotals()
    },

    recalcAllServices () {
      (this.form.services || []).forEach((_, i) => this.recalcService(i))
    },

    recalcTotals () {
      // Tính subtotal từ services
      const subtotal = (this.form.services || []).reduce((sum, s) => sum + Number(s.total_price || 0), 0)
      this.form.subtotal = subtotal

      // ✅ Thuế = 0%
      this.form.tax_rate = 0
      this.form.tax_amount = 0

      // Total = subtotal + tax (= subtotal vì tax = 0)
      this.form.total_amount = subtotal

      // ✅ Tính bảo hiểm theo %
      const insuranceCoverage = Number(this.form.insurance_coverage || 0) / 100
      this.form.insurance_amount = this.form.total_amount * insuranceCoverage

      // BN phải trả = total - insurance
      this.form.patient_payment = this.form.total_amount - this.form.insurance_amount

      // ✅ Auto update payment status (sẽ trigger watcher)
      // Watcher sẽ tự động update payment_status
    },

    async save () {
      if (this.saving) return
      this.saving = true
      try {
        // ✅ Validation: medication chỉ validate tồn tại, không validate stock
        for (const service of this.form.services) {
          if (service.service_type === 'medication' && service.medication_id) {
            if (service.quantity < 1) {
              alert(`Số lượng thuốc "${service.description}" phải ít nhất là 1!`)
              return
            }
          }
        }

        const payload = {
          type: 'invoice',
          patient_id: this.form.patient_id || undefined,
          medical_record_id: this.form.medical_record_id || undefined,
          invoice_info: {
            invoice_number: this.form.invoice_number || undefined,
            invoice_date: this.form.invoice_date || undefined,
            due_date: this.form.due_date || undefined
          },
          services: (this.form.services || []).map(s => ({
            service_type: s.service_type || undefined,
            description: s.description || undefined,
            quantity: Number(s.quantity || 0),
            unit_price: Number(s.unit_price || 0),
            total_price: Number(s.total_price || 0),
            medication_id: s.medication_id || undefined
          })),
          payment_info: {
            subtotal: Number(this.form.subtotal || 0),
            tax_rate: 0, // ✅ Always 0%
            tax_amount: 0,
            total_amount: Number(this.form.total_amount || 0),
            insurance_coverage: Number(this.form.insurance_coverage || 0),
            insurance_amount: Number(this.form.insurance_amount || 0),
            patient_payment: Number(this.form.patient_payment || 0)
          },
          payment_status: this.form.payment_status || 'unpaid',
          payment_method: this.form.payment_method || undefined,
          paid_date: this.form.paid_date || undefined
        }

        if (this.form._id) payload._id = this.form._id
        if (this.form._rev) payload._rev = this.form._rev

        if (this.editingId) {
          await InvoiceService.update(this.editingId, payload)
        } else {
          await InvoiceService.create(payload)
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
      if (!confirm(`Xóa hóa đơn "${row.invoice_number || 'này'}"?`)) return

      try {
        const id = row._id || row.id
        if (!id) {
          alert('Không tìm thấy ID hóa đơn')
          return
        }

        const rev = row._rev
        if (!rev) {
          alert('Không tìm thấy revision của document')
          return
        }

        // ✅ Truyền cả id và rev
        await InvoiceService.remove(id, rev)
        alert('Xóa thành công!')
        await this.fetch()
      } catch (e) {
        console.error('Remove error:', e)
        alert(e?.response?.data?.message || e?.message || 'Xóa thất bại')
      }
    },

    async loadPatientInsurance (patientId) {
      try {
        if (!patientId) {
          this.form.insurance_coverage = 0
          this.recalcTotals()
          return
        }

        const patient = await PatientService.get(patientId)
        const insurance = patient?.medical_info?.insurance || patient?.insurance

        if (insurance && insurance.provider && insurance.valid_until) {
          // Check if insurance is still valid
          const validUntil = new Date(insurance.valid_until)
          const now = new Date()
          if (validUntil > now) {
            // Set insurance coverage based on provider
            let coverage = 0
            const provider = (insurance.provider || '').toLowerCase()
            if (provider.includes('bhyt') || provider.includes('bảo hiểm y tế')) {
              coverage = 80 // BHYT covers 80%
            } else if (provider.includes('bảo việt') || provider.includes('pvi')) {
              coverage = 70 // Private insurance 70%
            } else {
              coverage = 50 // Default coverage 50%
            }
            this.form.insurance_coverage = coverage
          } else {
            this.form.insurance_coverage = 0 // Expired insurance
          }
        } else {
          this.form.insurance_coverage = 0 // No insurance
        }
        this.recalcTotals()
      } catch (e) {
        console.error('Load insurance error:', e)
        this.form.insurance_coverage = 0
        this.recalcTotals()
      }
    },

    // ✅ NEW: Helper methods để extract medication data
    getMedicationName (med) {
      return med.medication_info?.name ||
             med.name ||
             med.medication_name ||
             'Tên thuốc không xác định'
    },

    getMedicationStrength (med) {
      return med.medication_info?.strength ||
             med.strength ||
             med.dosage ||
             'Không xác định'
    },

    getMedicationPrice (med) {
      return med.inventory?.unit_cost ||
             med.unit_cost ||
             med.price ||
             0
    },

    getMedicationStock (med) {
      return med.inventory?.current_stock ||
             med.current_stock ||
             med.stock ||
             0
    },

    // ✅ NEW: Quantity controls cho medication
    increaseQuantity (serviceIndex) {
      const service = this.form.services[serviceIndex]
      service.quantity = (service.quantity || 0) + 1
      this.recalcService(serviceIndex)
    },

    decreaseQuantity (serviceIndex) {
      const service = this.form.services[serviceIndex]
      if (service.quantity > 1) {
        service.quantity = service.quantity - 1
        this.recalcService(serviceIndex)
      }
    },

    // ✅ SIMPLIFIED: Load all medications once
    async loadMedicationOptions () {
      if (this.medicationsLoaded) return

      try {
        console.log('Loading all medications...')
        const response = await MedicationService.list({ limit: 1000 })

        let medications = []
        if (Array.isArray(response.rows)) {
          medications = response.rows.map(r => r.doc || r.value || r)
        } else if (Array.isArray(response.data)) {
          medications = response.data
        } else if (Array.isArray(response)) {
          medications = response
        }

        // Filter active medications with stock
        this.allMedications = medications.filter(med => {
          const isActive = (med.status || '').toLowerCase() === 'active'
          const hasStock = this.getMedicationStock(med) > 0
          return isActive && hasStock
        })

        // Group by medication type
        this.groupMedications()
        this.medicationsLoaded = true

        console.log('Loaded medications:', this.allMedications.length)
      } catch (e) {
        console.error('Load medications error:', e)
        this.allMedications = []
      }
    },

    // ✅ NEW: Group medications by type/category
    groupMedications () {
      const groups = {}

      this.allMedications.forEach(med => {
        const type = med.medication_info?.type ||
                    med.type ||
                    med.category ||
                    'Khác'

        if (!groups[type]) {
          groups[type] = []
        }
        groups[type].push(med)
      })

      this.groupedMedications = Object.keys(groups)
        .sort()
        .map(type => ({
          label: type,
          medications: groups[type].sort((a, b) =>
            this.getMedicationName(a).localeCompare(this.getMedicationName(b))
          )
        }))
    },

    // ✅ NEW: Missing onMedicationSelect method
    onMedicationSelect (serviceIndex) {
      const service = this.form.services[serviceIndex]
      const medicationId = service.selected_medication_temp

      if (!medicationId) {
        this.clearMedication(serviceIndex)
        return
      }

      const medication = this.allMedications.find(m => m._id === medicationId || m.id === medicationId)
      if (!medication) {
        alert('Không tìm thấy thông tin thuốc')
        return
      }

      console.log('Selected medication:', medication)

      // Set medication data
      service.medication_id = medication._id || medication.id
      service.description = `${this.getMedicationName(medication)} (${this.getMedicationStrength(medication)})`
      service.unit_price = Number(this.getMedicationPrice(medication))
      service.available_stock = Number(this.getMedicationStock(medication))

      // Auto set quantity = 1
      if (!service.quantity || service.quantity <= 0) {
        service.quantity = 1
      }

      // Clear temp selection
      service.selected_medication_temp = ''

      this.recalcService(serviceIndex)
    },

    // ✅ NEW: Payment status text
    getPaymentStatusText (status) {
      const statusMap = {
        unpaid: 'Chưa thanh toán',
        partial: 'Thanh toán một phần',
        paid: 'Đã thanh toán',
        void: 'Đã hủy'
      }
      return statusMap[status] || status
    },

    // ✅ NEW: Payment method change handler
    onPaymentMethodChange () {
      // Auto set payment status if method is selected and amount > 0
      if (this.form.payment_method && this.form.total_amount > 0) {
        if (this.form.payment_status === 'unpaid') {
          this.markAsPaid()
        }
      }
    },

    // ✅ NEW: Mark as paid
    markAsPaid () {
      this.form.payment_status = 'paid'
      this.form.patient_payment = this.form.total_amount
      this.updatePaymentDateTime()

      // Auto set payment method if not set
      if (!this.form.payment_method) {
        this.form.payment_method = 'cash'
      }
    },

    // ✅ NEW: Mark as partial
    markAsPartial () {
      this.form.payment_status = 'partial'
      // Don't auto-change patient_payment, let user input
      this.updatePaymentDateTime()
    },

    // ✅ NEW: Update payment datetime
    updatePaymentDateTime () {
      if (this.form.payment_status === 'paid' || this.form.payment_status === 'partial') {
        if (!this.form.paid_date) {
          const now = new Date()
          this.form.paid_date = now.toISOString().slice(0, 16)
        }
      } else {
        // Clear payment date if unpaid
        this.form.paid_date = ''
      }
    },

    // ✅ SUC-06: Open invoice print page in new tab with authentication
    async downloadInvoice (row) {
      try {
        this.loading = true
        const invoiceId = row._id || row.id

        // ✅ Fetch HTML with authentication via axios
        const response = await InvoiceService.download(invoiceId)

        // ✅ Create blob and open in new window
        const blob = new Blob([response.data], { type: 'text/html; charset=utf-8' })
        const url = window.URL.createObjectURL(blob)

        // Open in new tab
        const printWindow = window.open(url, '_blank')

        // Clean up after window loads
        if (printWindow) {
          printWindow.addEventListener('load', () => {
            window.URL.revokeObjectURL(url)
          })
        } else {
          alert('Vui lòng cho phép popup để xem hóa đơn')
          window.URL.revokeObjectURL(url)
        }
      } catch (e) {
        console.error('Open invoice error:', e)
        alert(e?.response?.data?.message || e?.message || 'Không thể mở hóa đơn')
      } finally {
        this.loading = false
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

/* ✅ UPDATED: Selected medication styles */
.selected-medication {
  padding: 0.5rem;
  background-color: #f8fffe;
  border: 1px solid #20c997;
  border-radius: 0.375rem;
}

.selected-medication .fw-bold {
  color: #198754;
}

/* Combobox styles */
.form-select option {
  padding: 0.5rem;
}

optgroup {
  font-weight: 600;
  color: #495057;
}

/* ✅ NEW: Payment status styles */
.badge.fs-6 {
  font-size: 0.875rem !important;
  padding: 0.5rem 0.75rem;
}

/* Payment button styles */
.btn-group-sm .btn {
  font-size: 0.875rem;
  padding: 0.25rem 0.5rem;
}

/* Readonly payment date when not paid */
input[readonly] {
  background-color: #f8f9fa;
  border-color: #dee2e6;
}
</style>
