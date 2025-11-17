<template>
  <div class="medications-management">
    <!-- Header Section -->
    <header class="header-section">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="bi bi-capsule"></i>
            Quản lý thuốc
          </h1>
          <p class="page-subtitle">Quản lý danh mục thuốc và vật tư y tế</p>
        </div>
        <div class="header-actions">
          <button class="btn-action btn-back" @click="$router.go(-1)">
            <i class="bi bi-arrow-left"></i>
          </button>
          <button class="btn-action btn-refresh" @click="reload" :disabled="loading">
            <i class="bi bi-arrow-clockwise"></i>
          </button>
          <div class="stats-badge">
            <i class="bi bi-box-seam"></i>
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
            placeholder="Tìm kiếm tên thuốc, hoạt chất, dạng bào chế..."
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
        <table class="medications-table">
          <thead>
            <tr>
              <th class="col-number">#</th>
              <th class="col-name">Tên thuốc</th>
              <th class="col-ingredient">Hoạt chất</th>
              <th class="col-strength">Hàm lượng</th>
              <th class="col-form">Dạng bào chế</th>
              <th class="col-stock">Tồn kho</th>
              <th class="col-expiry">Hạn dùng</th>
              <th class="col-actions">Hành động</th>
            </tr>
          </thead>
          <tbody v-for="(m, idx) in items" :key="rowKey(m, idx)">
            <tr class="medication-row" :class="{ expanded: isExpanded(m) }">
              <td class="cell-number">
                <span class="row-number">{{ idx + 1 + (page - 1) * pageSize }}</span>
              </td>
              <td class="cell-name">
                <strong>{{ m.name }}</strong>
              </td>
              <td class="cell-ingredient">
                <span class="ingredient-text">{{ m.active_ingredient || '-' }}</span>
              </td>
              <td class="cell-strength">
                <span class="strength-badge">{{ m.strength || '-' }}</span>
              </td>
              <td class="cell-form">
                <span class="form-text">{{ m.dosage_form || '-' }}</span>
              </td>
              <td class="cell-stock">
                <span :class="['stock-badge', stockClass(m.quantity)]">
                  <i :class="stockIcon(m.quantity)"></i>
                  {{ m.quantity }}
                </span>
              </td>
              <td class="cell-expiry">
                <div class="date-info" v-if="m.expiry_date">
                  <i class="bi bi-calendar-x"></i>
                  {{ fmtDate(m.expiry_date) }}
                </div>
                <span v-else class="text-muted">-</span>
              </td>
              <td class="cell-actions">
                <div class="action-buttons">
                  <button class="action-btn view-btn" @click="toggleRow(m)" :title="isExpanded(m) ? 'Thu gọn' : 'Xem chi tiết'">
                    <i :class="isExpanded(m) ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                  </button>
                  <button class="action-btn edit-btn" @click="openEdit(m)" title="Chỉnh sửa">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="action-btn delete-btn" @click="remove(m)" :disabled="loading" title="Xóa">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>

            <!-- Detail Row -->
            <tr v-if="isExpanded(m)" class="detail-row">
              <td colspan="8">
                <div class="detail-wrap">
                  <div class="detail-title">Thông tin thuốc</div>
                  <div class="detail-grid">
                    <div><b>Tên:</b> {{ m.name }}</div>
                    <div><b>Hoạt chất:</b> {{ m.active_ingredient || '-' }}</div>
                    <div><b>Hàm lượng:</b> {{ m.strength || '-' }}</div>
                    <div><b>Dạng bào chế:</b> {{ m.dosage_form || '-' }}</div>
                    <div><b>Nhà sản xuất:</b> {{ m.manufacturer || '-' }}</div>
                    <div><b>Barcode:</b> {{ m.barcode || '-' }}</div>
                  </div>

                  <div class="detail-title">Thông tin lâm sàng</div>
                  <div class="detail-grid">
                    <div><b>Nhóm thuốc:</b> {{ m.drug_class || '-' }}</div>
                    <div><b>Chỉ định:</b> {{ joinList(m.indications) }}</div>
                    <div><b>Chống chỉ định:</b> {{ joinList(m.contraindications) }}</div>
                    <div><b>Tác dụng phụ:</b> {{ joinList(m.adverse_effects) }}</div>
                    <div><b>Tương tác:</b> {{ joinList(m.interactions) }}</div>
                  </div>

                  <div class="detail-title">Thông tin kho</div>
                  <div class="detail-grid">
                    <div><b>Số lượng tồn:</b> {{ m.quantity }}</div>
                    <div><b>Giá nhập:</b> {{ m.purchase_price ? m.purchase_price.toLocaleString('vi-VN') + ' VNĐ' : '-' }}</div>
                    <div><b>Hạn sử dụng:</b> {{ fmtDate(m.expiry_date) }}</div>
                    <div><b>Nhà cung cấp:</b> {{ m.supplier || '-' }}</div>
                  </div>

                  <div class="detail-meta">
                    <span><i class="bi bi-toggle-on"></i> Trạng thái: {{ m.status || 'active' }}</span>
                    <span><i class="bi bi-clock-history"></i> Tạo: {{ fmtDateTime(m.created_at) }}</span>
                    <span><i class="bi bi-pencil-square"></i> Cập nhật: {{ fmtDateTime(m.updated_at) }}</span>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>

          <tbody v-if="!items.length">
            <tr>
              <td colspan="8" class="text-center text-muted">
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

    <!-- Modal -->
    <div v-if="showModal" class="modal-overlay" @mousedown.self="close">
      <div class="modal-container">
        <div class="modal-header-custom">
          <h3 class="modal-title-custom">
            <i :class="editingId ? 'bi bi-pencil-square' : 'bi bi-plus-circle'"></i>
            {{ editingId ? 'Sửa thông tin thuốc' : 'Thêm thuốc mới' }}
          </h3>
          <button type="button" class="modal-close-btn" @click="close">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <div class="modal-body-custom">
          <form @submit.prevent="save">
            <!-- Thông tin thuốc -->
            <div class="form-section">
              <h4 class="form-section-title">
                <i class="bi bi-capsule"></i>
                Thông tin thuốc
              </h4>
              <div class="form-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                  <label class="form-label-custom">
                    <i class="bi bi-file-text"></i>
                    Tên thuốc <span class="text-required">*</span>
                  </label>
                  <input v-model.trim="form.name" type="text" class="form-input-custom" required placeholder="Ví dụ: Paracetamol" />
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-clipboard-pulse"></i>
                    Hoạt chất
                  </label>
                  <input v-model.trim="form.active_ingredient" type="text" class="form-input-custom" placeholder="Ví dụ: Acetaminophen" />
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-prescription2"></i>
                    Hàm lượng
                  </label>
                  <input v-model.trim="form.strength" type="text" class="form-input-custom" placeholder="Ví dụ: 500mg" />
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-capsule-pill"></i>
                    Dạng bào chế
                  </label>
                  <input v-model.trim="form.dosage_form" type="text" class="form-input-custom" placeholder="Ví dụ: Viên nén" />
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-building"></i>
                    Nhà sản xuất
                  </label>
                  <input v-model.trim="form.manufacturer" type="text" class="form-input-custom" placeholder="Ví dụ: Sanofi" />
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-upc-scan"></i>
                    Mã vạch (Barcode)
                  </label>
                  <input v-model.trim="form.barcode" type="text" class="form-input-custom" placeholder="Ví dụ: 8934564001231" />
                </div>
              </div>
            </div>

            <!-- Thông tin lâm sàng -->
            <div class="form-section">
              <h4 class="form-section-title">
                <i class="bi bi-heart-pulse"></i>
                Thông tin lâm sàng
              </h4>
              <div class="form-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                  <label class="form-label-custom">
                    <i class="bi bi-tag"></i>
                    Nhóm thuốc
                  </label>
                  <input v-model.trim="form.drug_class" type="text" class="form-input-custom" placeholder="Ví dụ: Analgesics" />
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                  <label class="form-label-custom">
                    <i class="bi bi-check2-circle"></i>
                    Chỉ định
                  </label>
                  <input v-model.trim="form.indications_text" type="text" class="form-input-custom" placeholder="Nhập các chỉ định, cách nhau bằng dấu phẩy" />
                  <span class="form-label-hint">Ví dụ: Hypertension, Angina pectoris</span>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                  <label class="form-label-custom">
                    <i class="bi bi-x-circle"></i>
                    Chống chỉ định
                  </label>
                  <input v-model.trim="form.contraindications_text" type="text" class="form-input-custom" placeholder="Nhập các chống chỉ định, cách nhau bằng dấu phẩy" />
                  <span class="form-label-hint">Ví dụ: Hypersensitivity to amlodipine</span>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                  <label class="form-label-custom">
                    <i class="bi bi-exclamation-triangle"></i>
                    Tác dụng phụ
                  </label>
                  <input v-model.trim="form.adverse_effects_text" type="text" class="form-input-custom" placeholder="Nhập các tác dụng phụ, cách nhau bằng dấu phẩy" />
                  <span class="form-label-hint">Ví dụ: Ankle edema, Dizziness, Flushing</span>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                  <label class="form-label-custom">
                    <i class="bi bi-arrow-left-right"></i>
                    Tương tác thuốc
                  </label>
                  <input v-model.trim="form.interactions_text" type="text" class="form-input-custom" placeholder="Nhập các tương tác, cách nhau bằng dấu phẩy" />
                  <span class="form-label-hint">Ví dụ: Grapefruit juice, Simvastatin</span>
                </div>
              </div>
            </div>

            <!-- Thông tin kho -->
            <div class="form-section">
              <h4 class="form-section-title">
                <i class="bi bi-box-seam"></i>
                Quản lý kho
              </h4>
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-boxes"></i>
                    Số lượng tồn kho
                  </label>
                  <input v-model.number="form.quantity" type="number" min="0" class="form-input-custom" placeholder="0" />
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-cash"></i>
                    Giá nhập (VNĐ)
                  </label>
                  <input v-model.number="form.purchase_price" type="number" min="0" class="form-input-custom" placeholder="0" />
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-calendar-x"></i>
                    Hạn sử dụng
                  </label>
                  <input v-model="form.expiry_date" type="date" class="form-input-custom" />
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-truck"></i>
                    Nhà cung cấp
                  </label>
                  <input v-model.trim="form.supplier" type="text" class="form-input-custom" placeholder="Ví dụ: Zuellig Pharma" />
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-toggle-on"></i>
                    Trạng thái
                  </label>
                  <select v-model="form.status" class="form-input-custom">
                    <option value="active">Đang hoạt động</option>
                    <option value="inactive">Ngừng kinh doanh</option>
                  </select>
                </div>
              </div>
            </div>
          </form>
        </div>

        <div class="modal-footer-custom">
          <button type="button" class="btn-modal-cancel" @click="close" :disabled="saving">
            <i class="bi bi-x-circle"></i>
            Hủy
          </button>
          <button type="submit" class="btn-modal-save" @click="save" :disabled="saving">
            <i :class="saving ? 'bi bi-hourglass-split' : 'bi bi-check-circle'"></i>
            {{ saving ? 'Đang lưu...' : 'Lưu' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import MedicationService from '@/api/medicationService'

export default {
  name: 'MedicationsListView',
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
      expanded: {}
    }
  },
  created () { this.fetch() },

  computed: {
    visiblePages () {
      const totalPages = Math.max(1, Math.ceil(this.total / this.pageSize))
      const current = this.page
      const pages = []
      if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) pages.push(i)
      } else {
        if (current <= 3) {
          pages.push(1, 2, 3, 4, '...', totalPages)
        } else if (current >= totalPages - 2) {
          pages.push(1, '...', totalPages - 3, totalPages - 2, totalPages - 1, totalPages)
        } else {
          pages.push(1, '...', current - 1, current, current + 1, '...', totalPages)
        }
      }
      return pages
    }
  },

  methods: {
    /* ===== UI Helpers ===== */
    stockClass (quantity) {
      if (quantity > 100) return 'stock-high'
      if (quantity >= 50) return 'stock-medium'
      if (quantity >= 10) return 'stock-low'
      return 'stock-out'
    },

    stockIcon (quantity) {
      if (quantity > 100) return 'bi bi-box-seam-fill'
      if (quantity >= 50) return 'bi bi-box-seam'
      if (quantity >= 10) return 'bi bi-exclamation-triangle-fill'
      return 'bi bi-x-circle-fill'
    },

    goToPage (p) {
      if (p === '...' || p === this.page) return
      this.page = p
      this.fetch()
    },

    changePageSize () {
      this.page = 1
      this.fetch()
    },

    /* ===== helpers ===== */
    rowKey (m, idx) { return m._id || m.id || m.barcode || `${idx}` },
    isExpanded (row) { return !!this.expanded[this.rowKey(row, 0)] },
    toggleRow (row) { const k = this.rowKey(row, 0); this.expanded = { ...this.expanded, [k]: !this.expanded[k] } },
    fmtDate (v) { if (!v) return '-'; try { return new Date(v).toISOString().slice(0, 10) } catch { return v } },
    fmtDateTime (v) { if (!v) return '-'; try { return new Date(v).toLocaleString() } catch { return v } },
    joinList (v) { return Array.isArray(v) ? v.join(', ') : (v || '-') },

    // chuyển doc CouchDB (lồng) -> object phẳng phục vụ list & details
    flattenMedication (d = {}) {
      const mi = d.medication_info || {}
      const ci = d.clinical_info || {}
      const inv = d.inventory || {}

      return {
        ...d, // giữ id, status, timestamps…

        // các field dùng cho LIST (6 cột)
        name: mi.name ?? d.name ?? 'Chưa có tên',
        active_ingredient: mi.generic_name ?? mi.active_ingredient ?? d.active_ingredient ?? '',
        strength: mi.strength ?? d.strength ?? '',
        dosage_form: mi.dosage_form ?? d.dosage_form ?? '',
        quantity: inv.current_stock ?? d.quantity ?? 0,
        expiry_date: inv.expiry_date ?? d.expiry_date ?? '',

        // phần ẩn khi “Xem”
        manufacturer: mi.manufacturer ?? d.manufacturer ?? '',
        barcode: mi.barcode ?? d.barcode ?? '',
        drug_class: ci.therapeutic_class ?? d.drug_class ?? '',
        indications: ci.indications ?? d.indications ?? [],
        contraindications: ci.contraindications ?? d.contraindications ?? [],
        adverse_effects: ci.side_effects ?? d.adverse_effects ?? [],
        interactions: ci.drug_interactions ?? d.interactions ?? [],
        purchase_price: inv.unit_cost ?? d.purchase_price ?? 0,
        supplier: inv.supplier ?? d.supplier ?? '',

        status: d.status ?? 'active',
        created_at: d.created_at ?? null,
        updated_at: d.updated_at ?? null
      }
    },

    /* ===== data ===== */
    async fetch () {
      this.loading = true
      this.error = ''
      try {
        const skip = (this.page - 1) * this.pageSize
        const res = await MedicationService.list({ q: this.q || undefined, limit: this.pageSize, offset: skip, skip })

        console.log('🔍 MedicationService.list response:', res)
        console.log('🔍 Response type:', typeof res)
        console.log('🔍 Response keys:', res ? Object.keys(res) : 'null')
        console.log('🔍 Is Array?', Array.isArray(res))
        console.log('🔍 Has rows?', res?.rows)
        console.log('🔍 Has data?', res?.data)

        // 🔥 Let's see what those 2 keys actually are!
        if (res && typeof res === 'object' && !Array.isArray(res)) {
          const keys = Object.keys(res)
          console.log('🔥 ACTUAL KEYS:', keys)
          keys.forEach(key => {
            console.log(`🔥 res['${key}']:`, res[key])
          })
        }

        let raw = []; let total = 0; let offset = null
        if (res && Array.isArray(res.rows)) {
          console.log('✅ Using rows format, count:', res.rows.length)
          raw = res.rows.map(r => r.doc || r.value || r)
          total = res.total_rows ?? raw.length
          offset = res.offset ?? 0
        } else if (res && res.data && Array.isArray(res.data)) {
          console.log('✅ Using data format, count:', res.data.length)
          raw = res.data; total = res.total ?? raw.length
        } else if (Array.isArray(res)) {
          console.log('✅ Using direct array format, count:', res.length)
          raw = res; total = raw.length
        } else {
          console.error('❌ Unknown response format!')
        }

        // FLATTEN để bảng hiển thị đúng
        this.items = (raw || []).map(d => this.flattenMedication(d))

        this.total = total
        this.hasMore = (offset != null)
          ? (offset + this.items.length) < (this.total || 0)
          : (this.page * this.pageSize) < (this.total || 0)
      } catch (e) {
        console.error(e)
        this.error = e?.response?.data?.message || e?.message || 'Không tải được dữ liệu'
      } finally { this.loading = false }
    },
    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },

    /* ===== modal data ===== */
    emptyForm () {
      return {
        _id: null,
        _rev: null,
        name: '',
        active_ingredient: '',
        strength: '',
        dosage_form: '',
        manufacturer: '',
        barcode: '',
        drug_class: '',
        indications: [],
        contraindications: [],
        adverse_effects: [],
        interactions: [],
        indications_text: '',
        contraindications_text: '',
        adverse_effects_text: '',
        interactions_text: '',
        quantity: 0,
        purchase_price: 0,
        expiry_date: '',
        supplier: '',
        status: 'active',
        created_at: null,
        updated_at: null
      }
    },
    openCreate () {
      this.editingId = null
      this.form = this.emptyForm()
      this.showModal = true
    },
    openEdit (row) {
      const f = this.flattenMedication(row)
      const toCsv = (a) => Array.isArray(a) ? a.join(', ') : (a || '')

      this.editingId = f._id || f.id
      this.form = {
        ...this.emptyForm(),
        ...f,
        indications_text: toCsv(f.indications),
        contraindications_text: toCsv(f.contraindications),
        adverse_effects_text: toCsv(f.adverse_effects),
        interactions_text: toCsv(f.interactions),
        expiry_date: (f.expiry_date || '').toString().slice(0, 10)
      }
      this.showModal = true
    },
    close () { if (!this.saving) this.showModal = false },

    async save () {
      if (this.saving) return
      this.saving = true
      try {
        const csv = (s) => (s || '').split(',').map(x => x.trim()).filter(Boolean)

        // Build payload THEO CẤU TRÚC API (lồng)
        const payload = {
          type: 'medication',
          medication_info: {
            name: this.form.name,
            generic_name: this.form.active_ingredient || undefined,
            strength: this.form.strength || undefined,
            dosage_form: this.form.dosage_form || undefined,
            manufacturer: this.form.manufacturer || undefined,
            barcode: this.form.barcode || undefined
          },
          clinical_info: {
            therapeutic_class: this.form.drug_class || undefined,
            indications: this.form.indications?.length ? this.form.indications : csv(this.form.indications_text),
            contraindications: this.form.contraindications?.length ? this.form.contraindications : csv(this.form.contraindications_text),
            side_effects: this.form.adverse_effects?.length ? this.form.adverse_effects : csv(this.form.adverse_effects_text),
            drug_interactions: this.form.interactions?.length ? this.form.interactions : csv(this.form.interactions_text)
          },
          inventory: {
            current_stock: Number(this.form.quantity || 0),
            unit_cost: Number(this.form.purchase_price || 0),
            expiry_date: this.form.expiry_date || undefined,
            supplier: this.form.supplier || undefined
          },
          status: this.form.status || 'active'
        }

        // CouchDB update cần _id/_rev nếu có
        if (this.form._id) payload._id = this.form._id
        if (this.form._rev) payload._rev = this.form._rev

        if (this.editingId) await MedicationService.update(this.editingId, payload)
        else await MedicationService.create(payload)

        this.showModal = false
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Lưu thất bại')
      } finally { this.saving = false }
    },

    async remove (row) {
      if (!confirm(`Xóa thuốc "${row.name || 'này'}"?`)) return

      try {
        const id = row._id || row.id
        if (!id) {
          alert('Không tìm thấy ID thuốc')
          return
        }

        const rev = row._rev
        if (!rev) {
          alert('Không tìm thấy revision của document')
          return
        }

        // ✅ Truyền cả id và rev
        await MedicationService.remove(id, rev)
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
/* =========================
   Medications Management
   ========================= */
.medications-management {
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

.loading-state i {
  font-size: 2.5rem;
  color: #3b82f6;
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

.medications-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.medications-table thead {
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
}

.medications-table thead th {
  padding: 1rem 0.75rem;
  text-align: left;
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
  border-bottom: 2px solid #cbd5e1;
}

.medications-table tbody td {
  padding: 0.875rem 0.75rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.medications-table tbody tr {
  transition: background-color 0.2s ease;
}

.medications-table tbody tr:hover {
  background-color: #f8fafc;
}

/* Column Widths */
.col-number {
  width: 60px;
  text-align: center;
}

.col-name {
  min-width: 180px;
}

.col-ingredient {
  min-width: 150px;
}

.col-strength {
  width: 120px;
}

.col-form {
  width: 120px;
}

.col-stock {
  width: 100px;
  text-align: center;
}

.col-expiry {
  width: 110px;
}

.col-actions {
  width: 140px;
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

.ingredient-text {
  color: #64748b;
  font-size: 0.875rem;
}

.strength-badge {
  display: inline-block;
  background: #dbeafe;
  color: #1e40af;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.85rem;
}

.form-text {
  color: #64748b;
  font-size: 0.875rem;
}

/* Stock Badges */
.stock-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.85rem;
}

.stock-badge i {
  font-size: 0.95rem;
}

.stock-high {
  background: #d1fae5;
  color: #065f46;
}

.stock-medium {
  background: #fef3c7;
  color: #92400e;
}

.stock-low {
  background: #fed7aa;
  color: #9a3412;
}

.stock-out {
  background: #fee2e2;
  color: #991b1b;
}

.date-info {
  color: #64748b;
  font-size: 0.875rem;
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

.detail-grid p {
  margin: 0;
  display: flex;
  gap: 0.5rem;
}

.detail-grid strong {
  color: #475569;
  min-width: 140px;
  flex-shrink: 0;
}

.detail-grid span {
  color: #64748b;
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
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
}

.modal-container {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header-custom {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  padding: 1.5rem 2rem;
  border-radius: 16px 16px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title-custom {
  color: white;
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.modal-title-custom i {
  font-size: 1.75rem;
}

.modal-close-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  backdrop-filter: blur(10px);
}

.modal-close-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

.modal-close-btn i {
  font-size: 1.25rem;
}

.modal-body-custom {
  padding: 2rem;
  overflow-y: auto;
  flex: 1;
}

/* Form Sections */
.form-section {
  margin-bottom: 2rem;
}

.form-section-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 1.25rem 0;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.form-section-title i {
  font-size: 1.25rem;
  color: #3b82f6;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label-custom {
  font-weight: 600;
  color: #475569;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-label-custom i {
  color: #3b82f6;
  font-size: 1rem;
}

.text-required {
  color: #ef4444;
}

.form-input-custom {
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  font-family: inherit;
}

.form-input-custom:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-label-hint {
  font-size: 0.8rem;
  color: #94a3b8;
  font-style: italic;
}

.modal-footer-custom {
  padding: 1.25rem 2rem;
  border-top: 2px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  background: #f8fafc;
  border-radius: 0 0 16px 16px;
}

.btn-modal-cancel {
  background: white;
  color: #64748b;
  border: 2px solid #e5e7eb;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-modal-cancel:hover {
  border-color: #94a3b8;
  color: #475569;
  transform: translateY(-2px);
}

.btn-modal-save {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
}

.btn-modal-save:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
}

.btn-modal-save:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Utilities */
.text-center {
  text-align: center;
}

.text-muted {
  color: #94a3b8;
}
</style>
