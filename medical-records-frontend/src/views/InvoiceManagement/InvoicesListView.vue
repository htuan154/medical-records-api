<template>
  <div class="invoices-management">
    <!-- Header Section -->
    <header class="header-section">
      <div class="header-content">
        <h1 class="page-title">
          <i class="bi bi-receipt"></i>
          Quản lý hóa đơn
        </h1>
        <div class="header-actions">
          <button class="btn-action btn-action-home" @click="goHome">
            <i class="bi bi-house-door"></i>
          </button>
          <button class="btn-action btn-action-reload" @click="reload" :disabled="loading">
            <i class="bi bi-arrow-clockwise"></i>
          </button>
          <div class="stats-badge">
            <i class="bi bi-receipt-cutoff"></i>
            Tổng: <strong>{{ total }}</strong>
          </div>
          <div class="page-size-selector">
            <select v-model.number="pageSize" @change="changePageSize">
              <option :value="10">10 / trang</option>
              <option :value="25">25 / trang</option>
              <option :value="50">50 / trang</option>
              <option :value="100">100 / trang</option>
            </select>
          </div>
          <button class="btn-action btn-primary" @click="openCreate" :disabled="loading">
            <i class="bi bi-plus-lg"></i>
            Thêm mới
          </button>
        </div>
      </div>
    </header>

    <!-- Search Section -->
    <section class="search-section">
      <div class="search-container">
        <div class="search-input-group">
          <i class="bi bi-search search-icon"></i>
          <input
            v-model.trim="q"
            class="search-input"
            placeholder="Tìm số hóa đơn, bệnh nhân..."
            @keyup.enter="search"
          />
        </div>
        <button class="search-btn" @click="search" :disabled="loading">
          <i class="bi bi-search"></i>
          Tìm kiếm
        </button>
      </div>
    </section>

    <!-- Content Section -->
    <section class="content-section">
      <div v-if="error" class="alert-error">
        <i class="bi bi-exclamation-triangle-fill"></i>
        {{ error }}
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Đang tải dữ liệu...</p>
      </div>

      <div v-else class="table-container">
        <table class="invoices-table">
          <thead>
            <tr>
              <th class="col-number">#</th>
              <th class="col-invoice">Số hóa đơn</th>
              <th class="col-date">Ngày HĐ</th>
              <th class="col-patient">Bệnh nhân</th>
              <th class="col-amount">Tổng tiền</th>
              <th class="col-status">Trạng thái</th>
              <th class="col-actions">Hành động</th>
            </tr>
          </thead>
          <tbody v-for="(inv, idx) in items" :key="rowKey(inv, idx)">
            <tr class="invoice-row" :class="{ expanded: isExpanded(inv) }">
              <td class="cell-number">
                <span class="row-number">{{ idx + 1 + (page - 1) * pageSize }}</span>
              </td>
              <td class="cell-invoice">
                <strong>{{ inv.invoice_number }}</strong>
              </td>
              <td class="cell-date">
                <div class="date-info">
                  <i class="bi bi-calendar-event"></i>
                  {{ fmtDate(inv.invoice_date) }}
                </div>
              </td>
              <td class="cell-patient">
                <span class="patient-name">{{ displayName(patientsMap[inv.patient_id]) || inv.patient_id }}</span>
              </td>
              <td class="cell-amount">
                <span class="amount-badge">{{ n(inv.total_amount) }} VNĐ</span>
              </td>
              <td class="cell-status">
                <span :class="['status-badge', paymentStatusClass(inv.payment_status)]">
                  <i :class="paymentStatusIcon(inv.payment_status)"></i>
                  {{ paymentStatusLabel(inv.payment_status) }}
                </span>
              </td>
              <td class="cell-actions">
                <div class="action-buttons">
                  <button class="action-btn view-btn" @click="toggleRow(inv)" :title="isExpanded(inv) ? 'Thu gọn' : 'Xem chi tiết'">
                    <i :class="isExpanded(inv) ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                  </button>
                  <button class="action-btn print-btn" @click="downloadInvoice(inv)" :disabled="loading" title="In hóa đơn">
                    <i class="bi bi-printer"></i>
                  </button>
                  <button class="action-btn edit-btn" @click="openEdit(inv)" title="Chỉnh sửa">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="action-btn delete-btn" @click="remove(inv)" :disabled="loading" title="Xóa">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>

            <!-- Detail Row -->
            <tr v-if="isExpanded(inv)" class="detail-row">
              <td colspan="7">
                <div class="detail-wrap">
                  <div class="detail-title">Thông tin hóa đơn</div>
                  <div class="detail-grid">
                    <div><b>Số HĐ:</b> {{ inv.invoice_number }}</div>
                    <div><b>Ngày HĐ:</b> {{ fmtDate(inv.invoice_date) }}</div>
                    <div><b>Hạn thanh toán:</b> {{ fmtDate(inv.due_date) }}</div>
                    <div><b>Hồ sơ khám:</b> {{ inv.medical_record_id || '-' }}</div>
                  </div>

                  <div class="detail-title">Dịch vụ</div>
                  <div class="services-table-wrapper">
                    <table class="services-table">
                      <thead>
                        <tr>
                          <th>Loại</th>
                          <th>Mô tả</th>
                          <th class="text-end">SL</th>
                          <th class="text-end">Đơn giá</th>
                          <th class="text-end">Thành tiền</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(s, i) in inv.services" :key="i">
                          <td><span class="service-type-badge">{{ s.service_type }}</span></td>
                          <td>{{ s.description }}</td>
                          <td class="text-end">{{ s.quantity }}</td>
                          <td class="text-end">{{ n(s.unit_price) }} VNĐ</td>
                          <td class="text-end"><strong>{{ n(s.total_price) }} VNĐ</strong></td>
                        </tr>
                        <tr v-if="!inv.services || !inv.services.length">
                          <td colspan="5" class="text-muted">Chưa có dịch vụ</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <div class="detail-title">Thanh toán</div>
                  <div class="detail-grid">
                    <div><b>Tạm tính:</b> {{ n(inv.subtotal) }} VNĐ</div>
                    <div><b>Thuế:</b> {{ inv.tax_rate ?? 0 }}% → {{ n(inv.tax_amount) }} VNĐ</div>
                    <div><b>Tổng cộng:</b> <strong>{{ n(inv.total_amount) }} VNĐ</strong></div>
                    <div><b>Bảo hiểm chi trả:</b> {{ inv.insurance_coverage ?? 0 }}% → {{ n(inv.insurance_amount) }} VNĐ</div>
                    <div><b>BN phải trả:</b> <strong>{{ n(inv.patient_payment) }} VNĐ</strong></div>
                    <div><b>Phương thức TT:</b> {{ inv.payment_method || '-' }}</div>
                    <div><b>Ngày thanh toán:</b> {{ fmtDateTime(inv.paid_date) }}</div>
                  </div>

                  <div class="detail-meta">
                    <span><i class="bi bi-clock-history"></i> Tạo: {{ fmtDateTime(inv.created_at) }}</span>
                    <span><i class="bi bi-pencil-square"></i> Cập nhật: {{ fmtDateTime(inv.updated_at) }}</span>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>

          <tbody v-if="!items.length">
            <tr>
              <td colspan="7" class="text-center text-muted">
                Không có dữ liệu
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination-section">
        <div class="pagination-info-row">
          <i class="bi bi-info-circle"></i>
          Hiển thị {{ items.length > 0 ? (page - 1) * pageSize + 1 : 0 }} - {{ Math.min(page * pageSize, total) }} trong tổng số <strong>{{ total }}</strong> bản ghi
        </div>
        <div class="pagination-controls-center">
          <button class="pagination-btn" @click="goToPage(1)" :disabled="page <= 1 || loading" title="Trang đầu">
            <i class="bi bi-chevron-double-left"></i>
          </button>
          <button class="pagination-btn" @click="prev" :disabled="page <= 1 || loading" title="Trang trước">
            <i class="bi bi-chevron-left"></i>
          </button>
          <div class="page-numbers">
            <button
              v-for="p in visiblePages"
              :key="p"
              :class="['page-number-btn', { active: p === page, ellipsis: p === '...' }]"
              @click="goToPage(p)"
              :disabled="p === '...'"
            >
              {{ p }}
            </button>
          </div>
          <button class="pagination-btn" @click="next" :disabled="!hasMore || loading" title="Trang sau">
            <i class="bi bi-chevron-right"></i>
          </button>
          <button class="pagination-btn" @click="goToPage(Math.ceil(total / pageSize))" :disabled="!hasMore || loading" title="Trang cuối">
            <i class="bi bi-chevron-double-right"></i>
          </button>
        </div>
      </div>
    </section>

    <!-- MODAL: form đầy đủ + combobox -->
    <div v-if="showModal" class="modal-backdrop" @mousedown.self="close">
      <div class="modal-card">
        <div class="modal-header-custom">
          <div class="modal-title-wrapper">
            <i class="bi bi-receipt-cutoff modal-icon"></i>
            <h3 class="modal-title">{{ editingId ? 'Chỉnh sửa hóa đơn' : 'Tạo hóa đơn mới' }}</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="close" :disabled="saving">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <form @submit.prevent="save" class="modal-form">
          <!-- Liên kết -->
          <div class="form-section">
            <div class="section-title-enhanced">
              <i class="bi bi-link-45deg section-icon"></i>
              <span>Liên kết thông tin</span>
            </div>
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
          </div>

          <!-- Thông tin HĐ -->
          <div class="form-section">
            <div class="section-title-enhanced">
              <i class="bi bi-file-earmark-text section-icon"></i>
              <span>Thông tin hóa đơn</span>
            </div>
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
          </div>

          <!-- Dịch vụ -->
          <div class="form-section">
            <div class="section-title-enhanced">
              <i class="bi bi-list-ul section-icon"></i>
              <span>Danh sách dịch vụ</span>
            </div>
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
            <button type="button" class="btn btn-add-service" @click="addService">
              <i class="bi bi-plus-circle"></i>
              Thêm dịch vụ
            </button>
          </div>

          <!-- Thanh toán -->
          <div class="form-section">
            <div class="section-title-enhanced">
              <i class="bi bi-credit-card section-icon"></i>
              <span>Thông tin thanh toán</span>
            </div>
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
              <div class="payment-status-section">
                <div class="status-display-row">
                  <div class="status-label-wrapper">
                    <i class="bi bi-flag-fill status-icon"></i>
                    <span class="status-label">Trạng thái thanh toán:</span>
                  </div>
                  <span :class="['badge', 'fs-6', 'payment-status-badge', statusClass(form.payment_status)]">
                    {{ getPaymentStatusText(form.payment_status) }}
                  </span>
                </div>

                <!-- Quick payment buttons -->
                <div class="quick-action-buttons" v-if="form.payment_status !== 'paid'">
                  <button
                    type="button"
                    class="btn btn-success btn-quick-action"
                    @click="markAsPaid"
                    :disabled="form.total_amount <= 0"
                  >
                    <i class="bi bi-check-circle-fill"></i>
                    Đánh dấu đã thanh toán
                  </button>
                  <button
                    type="button"
                    class="btn btn-warning btn-quick-action"
                    @click="markAsPartial"
                    :disabled="form.total_amount <= 0"
                  >
                    <i class="bi bi-hourglass-split"></i>
                    Thanh toán một phần
                  </button>
                </div>
              </div>
              </div>
            </div>
          </div>

          <div class="modal-footer-custom">
            <button type="button" class="btn btn-cancel" @click="close" :disabled="saving">
              <i class="bi bi-x-circle"></i>
              Hủy bỏ
            </button>
            <button class="btn btn-save" type="submit" :disabled="saving">
              <i class="bi bi-check-circle"></i>
              {{ saving ? 'Đang lưu...' : 'Lưu hóa đơn' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal xem hóa đơn -->
    <div v-if="showInvoicePreview" class="modal-backdrop" @mousedown.self="closeInvoicePreview">
      <div class="invoice-preview-modal">
        <div class="invoice-preview-header">
          <h3>Xem trước hóa đơn</h3>
          <button type="button" class="btn-close-preview" @click="closeInvoicePreview">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="invoice-preview-content">
          <iframe v-if="invoiceHtmlUrl" :src="invoiceHtmlUrl" frameborder="0"></iframe>
        </div>
      </div>
    </div>
  </div>
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
      medicationsLoaded: false,

      // Invoice preview
      showInvoicePreview: false,
      invoiceHtmlUrl: null
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
    },

    visiblePages () {
      const delta = 2
      const pages = []
      const totalPages = Math.ceil(this.total / this.pageSize)

      for (let i = 1; i <= totalPages; i++) {
        if (
          i === 1 ||
          i === totalPages ||
          (i >= this.page - delta && i <= this.page + delta)
        ) {
          pages.push(i)
        } else if (pages[pages.length - 1] !== '...') {
          pages.push('...')
        }
      }

      return pages
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
    /* ===== UI Helpers ===== */
    paymentStatusClass (status) {
      if (status === 'paid') return 'status-paid'
      if (status === 'partial') return 'status-partial'
      if (status === 'void') return 'status-void'
      return 'status-unpaid'
    },

    paymentStatusIcon (status) {
      if (status === 'paid') return 'bi bi-check-circle-fill'
      if (status === 'partial') return 'bi bi-hourglass-split'
      if (status === 'void') return 'bi bi-x-circle-fill'
      return 'bi bi-clock-fill'
    },

    paymentStatusLabel (status) {
      const labels = {
        paid: 'Đã thanh toán',
        partial: 'Thanh toán 1 phần',
        unpaid: 'Chưa thanh toán',
        void: 'Đã hủy'
      }
      return labels[status] || status
    },

    goToPage (p) {
      if (p === '...' || p === this.page) return
      this.page = p
    },

    changePageSize () {
      this.page = 1
      this.fetch()
    },

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
    goHome () { this.$router.push('/') },
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

    // ✅ SUC-06: Open invoice preview in modal
    async downloadInvoice (row) {
      try {
        this.loading = true
        const invoiceId = row._id || row.id

        // ✅ Fetch HTML with authentication via axios
        const response = await InvoiceService.download(invoiceId)

        // ✅ Create blob URL for iframe
        const blob = new Blob([response.data], { type: 'text/html; charset=utf-8' })

        // Clean up old URL if exists
        if (this.invoiceHtmlUrl) {
          window.URL.revokeObjectURL(this.invoiceHtmlUrl)
        }

        this.invoiceHtmlUrl = window.URL.createObjectURL(blob)
        this.showInvoicePreview = true
      } catch (e) {
        console.error('Open invoice error:', e)
        alert(e?.response?.data?.message || e?.message || 'Không thể mở hóa đơn')
      } finally {
        this.loading = false
      }
    },

    // Close invoice preview modal
    closeInvoicePreview () {
      this.showInvoicePreview = false
      if (this.invoiceHtmlUrl) {
        window.URL.revokeObjectURL(this.invoiceHtmlUrl)
        this.invoiceHtmlUrl = null
      }
    }
  }
}
</script>

<style scoped>
/* =========================
   Invoices Management
   ========================= */
.invoices-management {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  padding: 2rem 1rem;
}

/* =========================
   Header Section
   ========================= */
.header-section {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  border-radius: 16px;
  padding: 1.5rem 2rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: white;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0;
}

.page-title i {
  font-size: 2rem;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stats-badge {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.page-size-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: white;
  font-size: 0.95rem;
}

.page-size-selector select {
  background: rgba(255, 255, 255, 0.95);
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 8px;
  padding: 0.4rem 2rem 0.4rem 0.75rem;
  font-size: 0.95rem;
  font-weight: 600;
  color: #1e40af;
  cursor: pointer;
  transition: all 0.3s ease;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%231e40af' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.5rem center;
  background-size: 12px;
}

.page-size-selector select:hover {
  background-color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.page-size-selector select:focus {
  outline: none;
  border-color: rgba(255, 255, 255, 0.8);
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.2);
}

.btn-action {
  background: white;
  color: #1d4ed8;
  border: none;
  padding: 0.6rem 1.25rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-action:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  background: #f0f9ff;
}

.btn-action i {
  font-size: 1.1rem;
}

.btn-action-home {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  backdrop-filter: blur(10px);
  padding: 0.6rem 1rem;
}

.btn-action-home:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-2px);
}

.btn-action-reload {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  backdrop-filter: blur(10px);
  padding: 0.6rem 1rem;
}

.btn-action-reload:hover {
  background: rgba(255, 255, 255, 0.3);
  color: white;
}

/* =========================
   Search Section
   ========================= */
.search-section {
  background: white;
  border-radius: 12px;
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.search-container {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.search-input-group {
  position: relative;
  flex: 1;
  max-width: 500px;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  font-size: 1.1rem;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.3s ease;
}

.search-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-btn {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
}

.search-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

/* =========================
   Content Section
   ========================= */
.content-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
  margin-bottom: 1.5rem;
}

.alert-error {
  background: #fef2f2;
  border-left: 4px solid #ef4444;
  color: #991b1b;
  padding: 1rem 1.25rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.alert-error i {
  font-size: 1.25rem;
  color: #ef4444;
}

.loading-state {
  text-align: center;
  padding: 3rem 1rem;
  color: #6b7280;
}

.loading-state .spinner {
  width: 3rem;
  height: 3rem;
  margin: 0 auto 1rem;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* =========================
   Table
   ========================= */
.table-container {
  overflow-x: auto;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.invoices-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.invoices-table thead {
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
}

.invoices-table thead th {
  padding: 1rem 0.75rem;
  text-align: left;
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
  border-bottom: 2px solid #cbd5e1;
}

.invoices-table tbody td {
  padding: 0.875rem 0.75rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.invoices-table tbody tr {
  transition: background-color 0.2s ease;
}

.invoices-table tbody tr:hover {
  background-color: #f8fafc;
}

/* Column Widths */
.col-number {
  width: 60px;
  text-align: center;
}

.col-invoice {
  min-width: 140px;
}

.col-date {
  width: 120px;
}

.col-patient {
  min-width: 180px;
}

.col-amount {
  width: 140px;
}

.col-status {
  width: 140px;
}

.col-actions {
  width: 180px;
  text-align: center;
}

/* Cell Content */
.row-number {
  display: inline-block;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  padding: 0.25rem 0.65rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.85rem;
  min-width: 32px;
  text-align: center;
}

.date-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  font-size: 0.875rem;
}

.date-info i {
  color: #3b82f6;
}

.patient-name {
  color: #1e293b;
  font-weight: 500;
}

.amount-badge {
  display: inline-block;
  background: #dbeafe;
  color: #1e40af;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.875rem;
}

/* Status Badges */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.85rem;
}

.status-badge i {
  font-size: 0.95rem;
}

.status-paid {
  background: #d1fae5;
  color: #065f46;
}

.status-partial {
  background: #fef3c7;
  color: #92400e;
}

.status-unpaid {
  background: #fee2e2;
  color: #991b1b;
}

.status-void {
  background: #f3f4f6;
  color: #6b7280;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  align-items: center;
}

.action-btn {
  width: 2.2rem;
  height: 2.2rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.action-btn i {
  font-size: 1rem;
}

.view-btn {
  background: #dbeafe;
  color: #1e40af;
}

.view-btn:hover {
  background: #3b82f6;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
}

.print-btn {
  background: #d1fae5;
  color: #065f46;
}

.print-btn:hover {
  background: #10b981;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
}

.edit-btn {
  background: #fef3c7;
  color: #92400e;
}

.edit-btn:hover {
  background: #f59e0b;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(245, 158, 11, 0.3);
}

.delete-btn {
  background: #fee2e2;
  color: #991b1b;
}

.delete-btn:hover {
  background: #ef4444;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
}

/* =========================
   Detail Row
   ========================= */
.detail-row td {
  background: #f8fafc;
  padding: 0 !important;
}

.detail-wrap {
  padding: 1.5rem;
  border-top: 2px solid #e2e8f0;
}

.detail-title {
  font-weight: 600;
  font-size: 1rem;
  color: #1e293b;
  margin-bottom: 0.75rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e2e8f0;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 0.75rem 1.5rem;
  margin-bottom: 1rem;
}

.detail-grid div {
  display: flex;
  gap: 0.5rem;
}

.detail-grid b {
  color: #475569;
  min-width: 140px;
  flex-shrink: 0;
}

.services-table-wrapper {
  margin-bottom: 1rem;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.services-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.services-table thead {
  background: #f8fafc;
}

.services-table thead th {
  padding: 0.75rem;
  text-align: left;
  font-weight: 600;
  color: #475569;
  border-bottom: 2px solid #e2e8f0;
}

.services-table tbody td {
  padding: 0.75rem;
  border-bottom: 1px solid #f1f5f9;
}

.service-type-badge {
  display: inline-block;
  background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
  color: #4338ca;
  padding: 0.4rem 0.85rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 700;
  text-transform: capitalize;
  border: 1px solid #c7d2fe;
  box-shadow: 0 2px 4px rgba(67, 56, 202, 0.15);
}

.detail-meta {
  display: flex;
  gap: 2rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
  font-size: 0.85rem;
  color: #64748b;
}

.detail-meta i {
  margin-right: 0.35rem;
}

/* =========================
   Pagination Section
   ========================= */
.pagination-section {
  background: white;
  border-radius: 12px;
  padding: 1.25rem 1.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.pagination-info-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  font-size: 0.95rem;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e5e7eb;
}

.pagination-info-row i {
  color: #3b82f6;
  font-size: 1.1rem;
}

.pagination-controls-center {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
}

.pagination-btn {
  width: 2.5rem;
  height: 2.5rem;
  border: 2px solid #e5e7eb;
  background: white;
  color: #64748b;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.pagination-btn:hover:not(:disabled) {
  border-color: #3b82f6;
  color: #3b82f6;
  background: #eff6ff;
  transform: translateY(-2px);
}

.pagination-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 0.35rem;
  margin: 0 0.5rem;
}

.page-number-btn {
  min-width: 2.5rem;
  height: 2.5rem;
  padding: 0 0.5rem;
  border: 2px solid #e5e7eb;
  background: white;
  color: #64748b;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 600;
  font-size: 0.95rem;
}

.page-number-btn:hover:not(.active):not(.ellipsis) {
  border-color: #3b82f6;
  color: #3b82f6;
  background: #eff6ff;
  transform: translateY(-2px);
}

.page-number-btn.active {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border-color: #1d4ed8;
  box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
}

.page-number-btn.ellipsis {
  border: none;
  cursor: default;
  background: transparent;
}

/* =========================
   Modal
   ========================= */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: grid;
  place-items: center;
  z-index: 1050;
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-card {
  width: min(1100px, 96vw);
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
  max-height: 92vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Modal Header */
.modal-header-custom {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  padding: 1.5rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 3px solid #1e40af;
}

.modal-title-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.modal-icon {
  font-size: 2rem;
  color: white;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: white;
  margin: 0;
  letter-spacing: 0.3px;
}

.btn-close-modal {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.25rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-close-modal:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

.btn-close-modal:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modal Form */
.modal-form {
  padding: 2rem;
  overflow-y: auto;
  flex: 1;
}

/* Form Sections */
.form-section {
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.section-title-enhanced {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.15rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 3px solid #3b82f6;
  letter-spacing: 0.3px;
}

.section-icon {
  font-size: 1.5rem;
  color: #3b82f6;
}

/* Legacy section title */
.section-title {
  font-weight: 600;
  margin: 14px 0 8px;
  padding-bottom: 8px;
  border-bottom: 2px solid #e5e7eb;
}

/* Add Service Button */
.btn-add-service {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 1rem;
  box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
}

.btn-add-service:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.btn-add-service i {
  font-size: 1.1rem;
}

/* Modal Footer */
.modal-footer-custom {
  background: #f8fafc;
  padding: 1.5rem 2rem;
  border-top: 2px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.btn-cancel {
  background: white;
  color: #64748b;
  border: 2px solid #cbd5e1;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-cancel:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
  transform: translateY(-2px);
}

.btn-cancel:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-save {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border: none;
  padding: 0.75rem 2rem;
  border-radius: 8px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-save:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}

.btn-save:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-cancel i,
.btn-save i {
  font-size: 1.1rem;
}

/* Selected medication styles */
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

/* Enhanced service type select in table */
.table tbody td .form-select.form-select-sm {
  font-weight: 600;
  border: 2px solid #e5e7eb;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  color: #1e293b;
  padding: 0.5rem 2rem 0.5rem 0.75rem;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.table tbody td .form-select.form-select-sm:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  background: white;
}

.table tbody td .form-select.form-select-sm:disabled {
  background: #f3f4f6;
  color: #9ca3af;
  cursor: not-allowed;
}

/* Payment status section */
.payment-status-section {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.status-display-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.status-label-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-icon {
  font-size: 1.25rem;
  color: #3b82f6;
}

.status-label {
  font-size: 1.05rem;
  font-weight: 700;
  color: #1e293b;
  letter-spacing: 0.3px;
}

.payment-status-badge {
  font-size: 1rem !important;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 700;
  border: 2px solid transparent;
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.payment-status-badge::before {
  content: '●';
  font-size: 1.3rem;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.quick-action-buttons {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  padding-top: 0.75rem;
  border-top: 2px dashed #e2e8f0;
}

.btn-quick-action {
  padding: 0.65rem 1.25rem;
  font-weight: 600;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.btn-quick-action i {
  font-size: 1.1rem;
}

.btn-quick-action:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* Legacy badge support */
.badge.fs-6 {
  font-size: 0.95rem !important;
  padding: 0.65rem 1.25rem;
  border-radius: 8px;
  font-weight: 700;
  border: 2px solid transparent;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.badge.fs-6::before {
  content: '●';
  font-size: 1.2rem;
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

/* Utilities */
.text-center {
  text-align: center;
}

.text-muted {
  color: #94a3b8;
}

.text-end {
  text-align: right;
}

/* =========================
   Invoice Preview Modal
   ========================= */
.invoice-preview-modal {
  width: 95vw;
  max-width: 1400px;
  height: 90vh;
  background: white;
  border-radius: 16px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
  animation: slideUp 0.3s ease-out;
}

.invoice-preview-header {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  padding: 1.25rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 3px solid #1d4ed8;
  flex-shrink: 0;
}

.invoice-preview-header h3 {
  font-size: 1.35rem;
  font-weight: 700;
  color: white;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.invoice-preview-header h3::before {
  content: '📄';
  font-size: 1.5rem;
}

.btn-close-preview {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.25rem;
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.btn-close-preview:hover {
  background: rgba(255, 255, 255, 0.35);
  transform: rotate(90deg) scale(1.1);
}

.invoice-preview-content {
  flex: 1;
  overflow: hidden;
  background: #e5e7eb;
  position: relative;
  min-height: 0;
}

.invoice-preview-content iframe {
  width: 100%;
  height: 100%;
  border: none;
  display: block;
  background: white;
}
</style>
