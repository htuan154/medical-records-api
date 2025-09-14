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
        <tbody>
        <template v-for="(inv, idx) in items" :key="rowKey(inv, idx)">
          <tr>
            <td>{{ idx + 1 + (page - 1) * pageSize }}</td>
            <td>{{ inv.invoice_number }}</td>
            <td>{{ fmtDate(inv.invoice_date) }}</td>
            <td>{{ displayName(patientsMap[inv.patient_id]) || inv.patient_id }}</td>
            <td>{{ n(inv.total_amount) }}</td>
            <td><span :class="['badge', statusClass(inv.payment_status)]">{{ inv.payment_status || '-' }}</span></td>
            <td>
              <div class="btn-group">
                <button class="btn btn-sm btn-outline-secondary" @click="toggleRow(inv)">{{ isExpanded(inv) ? 'Ẩn' : 'Xem' }}</button>
                <button class="btn btn-sm btn-outline-primary" @click="openEdit(inv)">Sửa</button>
                <button class="btn btn-sm btn-outline-danger" @click="remove(inv)" :disabled="loading">Xoá</button>
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
                <th style="width:40%">Mô tả</th>
                <th style="width:10%" class="text-end">SL</th>
                <th style="width:16%" class="text-end">Đơn giá</th>
                <th style="width:16%" class="text-end">Thành tiền</th>
                <th style="width:2%"></th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="(s, i) in form.services" :key="i">
                <td><input v-model.trim="s.service_type" class="form-control form-control-sm" placeholder="consultation / medication / ..."/></td>
                <td><input v-model.trim="s.description" class="form-control form-control-sm" placeholder="Mô tả dịch vụ"/></td>
                <td><input v-model.number="s.quantity" type="number" min="0" class="form-control form-control-sm text-end" @input="recalcService(i)"/></td>
                <td><input v-model.number="s.unit_price" type="number" min="0" class="form-control form-control-sm text-end" @input="recalcService(i)"/></td>
                <td class="text-end">{{ n(s.total_price) }}</td>
                <td class="text-end">
                  <button type="button" class="btn btn-sm btn-outline-danger" @click="removeService(i)">×</button>
                </td>
              </tr>
              <tr v-if="!form.services.length">
                <td colspan="6" class="text-muted small">Chưa có dịch vụ — bấm “+ Thêm dịch vụ”</td>
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
              <input v-model.number="form.subtotal" type="number" min="0" class="form-control" @input="recalcTotals"/>
            </div>
            <div class="col-md-3">
              <label class="form-label">Thuế (%)</label>
              <input v-model.number="form.tax_rate" type="number" min="0" step="0.01" class="form-control" @input="recalcTotals"/>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tiền thuế</label>
              <input v-model.number="form.tax_amount" type="number" min="0" class="form-control" @input="recalcTotals"/>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tổng cộng</label>
              <input v-model.number="form.total_amount" type="number" min="0" class="form-control" @input="recalcTotals"/>
            </div>

            <div class="col-md-3">
              <label class="form-label">BH chi trả (%)</label>
              <input v-model.number="form.insurance_coverage" type="number" min="0" max="1" step="0.01" class="form-control" @input="recalcTotals"/>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tiền BH</label>
              <input v-model.number="form.insurance_amount" type="number" min="0" class="form-control" @input="recalcTotals"/>
            </div>
            <div class="col-md-3">
              <label class="form-label">BN phải trả</label>
              <input v-model.number="form.patient_payment" type="number" min="0" class="form-control"/>
            </div>
            <div class="col-md-3">
              <label class="form-label">PT Thanh toán</label>
              <input v-model.trim="form.payment_method" class="form-control" placeholder="cash / card / transfer"/>
            </div>

            <div class="col-md-4">
              <label class="form-label">Ngày thanh toán</label>
              <input v-model="form.paid_date" type="datetime-local" class="form-control"/>
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
    n (v) {
      try {
        return Number(v || 0).toLocaleString()
      } catch {
        return v
      }
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
      return {
        _id: null,
        _rev: null,
        patient_id: '',
        medical_record_id: '',
        invoice_number: generateInvoiceNumber(), // Tự động tạo số HĐ
        invoice_date: new Date().toISOString().slice(0, 10), // Ngày hiện tại
        due_date: '',
        services: [],
        subtotal: 0,
        tax_rate: 0,
        tax_amount: 0,
        total_amount: 0,
        insurance_coverage: 0,
        insurance_amount: 0,
        patient_payment: 0,
        payment_status: 'unpaid',
        payment_method: '',
        paid_date: '',
        created_at: null,
        updated_at: null
      }
    },

    openCreate () {
      this.editingId = null
      this.form = this.emptyForm()
      // Tạo số HĐ mới mỗi lần mở modal
      this.form.invoice_number = generateInvoiceNumber()
      this.showModal = true
      this.ensureOptionsLoaded()
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
      this.loadRecordsForPatient(this.form.patient_id)
      this.recalcAllServices()
      this.recalcTotals()
    },

    close () {
      if (!this.saving) this.showModal = false
    },

    addService () {
      this.form.services = [...this.form.services, {
        service_type: '',
        description: '',
        quantity: 0,
        unit_price: 0,
        total_price: 0
      }]
      this.recalcTotals()
    },

    removeService (i) {
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
      const subtotal = (this.form.services || []).reduce((sum, s) => sum + Number(s.total_price || 0), 0)
      const taxAmount = this.form.tax_amount || (Number(this.form.tax_rate || 0) * subtotal)
      const totalAmount = this.form.total_amount || (subtotal + Number(taxAmount || 0))
      const insuranceAmount = this.form.insurance_amount || (Number(this.form.insurance_coverage || 0) * totalAmount)
      const patientPayment = totalAmount - Number(insuranceAmount || 0)

      this.form.subtotal = subtotal
      this.form.tax_amount = taxAmount
      this.form.total_amount = totalAmount
      this.form.insurance_amount = insuranceAmount
      this.form.patient_payment = patientPayment
    },

    async save () {
      if (this.saving) return
      this.saving = true
      try {
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
            total_price: Number(s.total_price || 0)
          })),
          payment_info: {
            subtotal: Number(this.form.subtotal || 0),
            tax_rate: Number(this.form.tax_rate || 0),
            tax_amount: Number(this.form.tax_amount || 0),
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
      if (!confirm(`Xoá hoá đơn "${row.invoice_number || 'này'}"?`)) return
      try {
        const id = row._id || row.id
        await InvoiceService.remove(id)
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Xoá thất bại')
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
