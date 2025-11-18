<template>
  <div>
    <section class="medical-tests-management">
      <!-- Header Section -->
      <div class="header-section">
        <div class="header-content">
          <div class="header-left">
            <h1 class="page-title">
              <i class="bi bi-file-medical"></i>
              Quản lý Xét nghiệm
            </h1>
            <p class="page-subtitle">Quản lý xét nghiệm và kết quả xét nghiệm</p>
          </div>
          <div class="header-actions">
            <button class="btn-action btn-back" @click="$router.push('/')" title="Quay lại Trang chủ">
              <i class="bi bi-arrow-left"></i>
              Trang chủ
            </button>
            <div class="stats-badge">
              <i class="bi bi-bar-chart-fill"></i>
              <span>Tổng: <strong>{{ total }}</strong></span>
            </div>
            <div class="page-size-selector">
              <select v-model.number="pageSize" @change="changePageSize" :disabled="loading">
                <option :value="10">10 / trang</option>
                <option :value="25">25 / trang</option>
                <option :value="50">50 / trang</option>
                <option :value="100">100 / trang</option>
              </select>
            </div>
            <button class="btn-action btn-refresh" @click="reload" :disabled="loading" title="Tải lại">
              <i class="bi bi-arrow-clockwise"></i>
            </button>
            <button class="btn-action btn-primary" @click="openCreate" :disabled="loading">
              <i class="bi bi-plus-circle"></i>
              Thêm mới
            </button>
          </div>
        </div>
      </div>

      <!-- Search and Filter Section -->
      <div class="search-section">
        <div class="search-container">
          <div class="filter-row">
            <div class="filter-input-group">
              <i class="bi bi-search search-icon"></i>
              <input
                v-model.trim="q"
                class="filter-input"
                placeholder="Tìm theo tên xét nghiệm / loại..."
                @keyup.enter="search"
              />
            </div>
            <div class="filter-input-group filter-select">
              <i class="bi bi-file-medical search-icon"></i>
              <select v-model="filterRecordId" class="filter-input" @change="applyFilter">
                <option value="">-- Tất cả hồ sơ khám --</option>
                <option v-for="opt in recordOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
            </div>
            <button class="search-btn" @click="search" :disabled="loading">
              <i class="bi bi-search"></i>
              Tìm
            </button>
            <button v-if="filterRecordId" class="clear-filter-btn" @click="clearFilter" title="Xóa bộ lọc">
              <i class="bi bi-x-circle"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Content Section -->
      <div class="content-section">
        <div v-if="error" class="alert-error">
          <i class="bi bi-exclamation-triangle"></i>
          {{ error }}
        </div>

        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <span>Đang tải danh sách...</span>
        </div>

        <template v-else>
          <div class="table-container">
            <table class="tests-table">
              <thead>
                <tr>
                  <th class="col-number">#</th>
                  <th class="col-type">Loại</th>
                  <th class="col-name">Tên xét nghiệm</th>
                  <th class="col-ordered">Ngày chỉ định</th>
                  <th class="col-result">Ngày có kết quả</th>
                  <th class="col-status">Trạng thái</th>
                  <th class="col-actions">Hành động</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(t, idx) in filteredItems" :key="rowKey(t, idx)">
                  <tr class="test-row" :class="{ 'expanded': isExpanded(t) }">
                    <td class="cell-number">
                      <span class="row-number">{{ idx + 1 + (page - 1) * pageSize }}</span>
                    </td>
                    <td class="cell-type">
                      <span class="type-badge" :class="typeClass(t.type)">
                        <i :class="typeIcon(t.type)"></i>
                        {{ typeLabel(t.type) }}
                      </span>
                    </td>
                    <td class="cell-name">
                      <strong>{{ t.name }}</strong>
                    </td>
                    <td class="cell-ordered">
                      <div class="date-info">
                        <i class="bi bi-calendar-check"></i>
                        <span>{{ fmtDateTime(t.ordered_at) }}</span>
                      </div>
                    </td>
                    <td class="cell-result">
                      <div class="date-info">
                        <i class="bi bi-calendar-event"></i>
                        <span>{{ fmtDateTime(t.result_at) }}</span>
                      </div>
                    </td>
                    <td class="cell-status">
                      <span class="status-badge" :class="statusClass(t.status)">
                        <i :class="statusIcon(t.status)"></i>
                        {{ statusLabel(t.status) }}
                      </span>
                    </td>
                    <td class="cell-actions">
                      <div class="action-buttons">
                        <button class="action-btn view-btn" @click="toggleRow(t)" :title="isExpanded(t) ? 'Ẩn chi tiết' : 'Xem chi tiết'">
                          <i :class="isExpanded(t) ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                        </button>
                        <button class="action-btn edit-btn" @click="openEdit(t)" title="Sửa">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button class="action-btn delete-btn" @click="remove(t)" :disabled="loading" title="Xóa">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>

                  <!-- ROW DETAILS -->
                  <tr v-if="isExpanded(t)" class="detail-row">
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
            </tbody>

            <tbody v-if="!filteredItems.length">
              <tr>
                <td colspan="7" class="text-center text-muted">{{ filterRecordId ? 'Không tìm thấy xét nghiệm cho hồ sơ này' : 'Không có dữ liệu' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Section -->
        <div class="pagination-section">
          <div class="pagination-info-row">
            <i class="bi bi-file-earmark-text"></i>
            <span>Trang <strong>{{ page }} / {{ Math.max(1, Math.ceil((total || 0) / pageSize)) }}</strong> - Hiển thị {{ filteredItems.length }} trong tổng số {{ total }} xét nghiệm</span>
          </div>
          <div class="pagination-controls-center">
            <button class="pagination-btn" @click="prev" :disabled="page <= 1 || loading">
              <i class="bi bi-chevron-left"></i>
            </button>

            <div class="page-numbers">
              <button
                v-for="p in visiblePages"
                :key="p"
                class="page-number-btn"
                :class="{ 'active': p === page, 'ellipsis': p === '...' }"
                @click="goToPage(p)"
                :disabled="p === '...' || loading"
              >
                {{ p }}
              </button>
            </div>

            <button class="pagination-btn" @click="next" :disabled="!hasMore || loading">
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </div>
      </template>
    </div>
  </section>

    <!-- MODAL -->
    <div v-if="showModal" class="modal-overlay" @mousedown.self="close">
      <div class="modal-container">
        <div class="modal-header-custom">
          <h3 class="modal-title-custom">
            <i class="bi bi-file-medical-fill" v-if="!editingId"></i>
            <i class="bi bi-pencil-square" v-else></i>
            {{ editingId ? 'Sửa xét nghiệm' : 'Thêm xét nghiệm' }}
          </h3>
          <button type="button" class="modal-close-btn" @click="close">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <div class="modal-body-custom">

          <form @submit.prevent="save">
            <!-- Liên kết -->
            <div class="form-section">
              <div class="form-section-title">
                <i class="bi bi-link-45deg"></i>
                Liên kết
              </div>
              <div class="form-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                  <label class="form-label-custom">
                    <i class="bi bi-file-medical"></i>
                    Hồ sơ khám <span class="text-required">*</span>
                  </label>
                  <select v-model="form.medical_record_id" class="form-input-custom" required @change="onRecordChange">
                    <option value="">-- Chọn hồ sơ khám --</option>
                    <option v-for="r in recordOptions" :key="r.value" :value="r.value">{{ r.label }}</option>
                  </select>
                  <small class="form-label-hint">Chọn hồ sơ khám để tự động điền bệnh nhân và bác sĩ</small>
                </div>
                <div class="form-group">
              <label class="form-label-custom">
                <i class="bi bi-clipboard-pulse"></i>
                Loại xét nghiệm <span class="text-required">*</span>
              </label>
              <select v-model="form.type" class="form-input-custom" required>
                <option value="">-- Chọn loại --</option>
                <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>
            </div>
                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-toggle-on"></i>
                    Trạng thái
                  </label>
                  <select v-model="form.status" class="form-input-custom">
                    <option value="pending">Chờ xử lý</option>
                    <option value="in_progress">Đang thực hiện</option>
                    <option value="completed">Hoàn thành</option>
                    <option value="canceled">Đã hủy</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-person-fill"></i>
                    Bệnh nhân
                  </label>
                  <input v-model="form.patient_name" class="form-input-custom" readonly placeholder="Tự động từ hồ sơ" />
                </div>
                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-person-badge"></i>
                    Bác sĩ chỉ định
                  </label>
                  <input v-model="form.doctor_name" class="form-input-custom" readonly placeholder="Tự động từ hồ sơ" />
                </div>
                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-calendar-event"></i>
                    Ngày khám
                  </label>
                  <input v-model="form.visit_date" class="form-input-custom" readonly placeholder="Tự động từ hồ sơ" />
                </div>
                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-person-gear"></i>
                    Kỹ thuật viên
                  </label>
                  <input v-model.trim="form.technician_id" type="text" class="form-input-custom" placeholder="Mã KTV..." />
                </div>
              </div>
            </div>

            <!-- Thông tin xét nghiệm -->
            <div class="form-section">
              <div class="form-section-title">
                <i class="bi bi-clipboard2-check"></i>
                Thông tin xét nghiệm
              </div>
              <div class="form-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                  <label class="form-label-custom">
                    <i class="bi bi-pencil"></i>
                    Tên xét nghiệm <span class="text-required">*</span>
                  </label>
                  <input v-model.trim="form.name" type="text" class="form-input-custom" required placeholder="Tổng phân tích máu, X-quang phổi..." />
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-calendar-check"></i>
                    Ngày chỉ định
                  </label>
                  <input v-model="form.ordered_at" type="datetime-local" class="form-input-custom" />
                </div>
                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-droplet"></i>
                    Ngày lấy mẫu
                  </label>
                  <input v-model="form.collected_at" type="datetime-local" class="form-input-custom" />
                </div>
                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-clipboard-check"></i>
                    Ngày có kết quả
                  </label>
                  <input v-model="form.result_at" type="datetime-local" class="form-input-custom" />
                </div>
              </div>
            </div>

            <!-- Kết quả -->
            <div class="form-section">
              <div class="form-section-title">
                <i class="bi bi-clipboard-data"></i>
                Kết quả
              </div>
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

              <div class="form-group" style="margin-top: 1.5rem;">
                <label class="form-label-custom">
                  <i class="bi bi-file-text"></i>
                  Diễn giải
                </label>
                <textarea v-model.trim="form.interpretation" class="form-input-custom" rows="3"
                          placeholder="Các chỉ số xét nghiệm trong giới hạn bình thường..."></textarea>
              </div>
            </div>
          </form>
        </div>

        <div class="modal-footer-custom">
          <button type="button" class="btn-modal-cancel" @click="close">
            <i class="bi bi-x-circle"></i>
            Hủy
          </button>
          <button class="btn-modal-save" type="submit" @click="save" :disabled="saving">
            <i class="bi bi-check-circle"></i>
            {{ saving ? 'Đang lưu…' : 'Lưu' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import MedicalTestService from '@/api/medicalTestService'
import DoctorService from '@/api/doctorService'
import PatientService from '@/api/patientService'
import MedicalRecordService from '@/api/medicalRecordService'
import TreatmentService from '@/api/treatmentService'

export default {
  name: 'MedicalTestsListView',
  computed: {
    typeOptions () {
      const base = this.typeOptionsBase || []
      const map = {}
      base.forEach(o => { if (o?.value) map[o.value] = o.label || o.value })
      ;(this.dynamicTypes || []).forEach(t => {
        if (t) map[t] = map[t] || t
      })
      return Object.entries(map).map(([value, label]) => ({ value, label }))
    },
    visiblePages () {
      const totalPages = Math.max(1, Math.ceil((this.total || 0) / this.pageSize))
      const current = this.page
      const delta = 2
      const range = []
      const rangeWithDots = []

      for (let i = Math.max(2, current - delta); i <= Math.min(totalPages - 1, current + delta); i++) {
        range.push(i)
      }

      if (current - delta > 2) {
        rangeWithDots.push(1, '...')
      } else {
        rangeWithDots.push(1)
      }

      rangeWithDots.push(...range)

      if (current + delta < totalPages - 1) {
        rangeWithDots.push('...', totalPages)
      } else if (totalPages > 1) {
        rangeWithDots.push(totalPages)
      }

      return rangeWithDots
    }
  },
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
      filteredItems: [],
      typeOptionsBase: [
        { value: 'blood_work', label: 'Xét nghiệm máu' },
        { value: 'urine_analysis', label: 'Xét nghiệm nước tiểu' },
        { value: 'imaging', label: 'Chẩn đoán hình ảnh' },
        { value: 'biopsy', label: 'Sinh thiết' },
        { value: 'culture', label: 'Cấy mẫu' },
        { value: 'pathology', label: 'Giải phẫu bệnh' }
      ],
      dynamicTypes: []
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
        ? 'status-completed'
        : s === 'in_progress'
          ? 'status-in-progress'
          : s === 'canceled'
            ? 'status-canceled'
            : 'status-pending'
    },
    statusIcon (s) {
      return s === 'completed'
        ? 'bi bi-check-circle-fill'
        : s === 'in_progress'
          ? 'bi bi-hourglass-split'
          : s === 'canceled'
            ? 'bi bi-x-circle-fill'
            : 'bi bi-clock-fill'
    },
    statusLabel (s) {
      return s === 'completed'
        ? 'Hoàn thành'
        : s === 'in_progress'
          ? 'Đang thực hiện'
          : s === 'canceled'
            ? 'Đã hủy'
            : 'Chờ xử lý'
    },
    typeClass (t) {
      return t === 'blood_work'
        ? 'type-blood'
        : t === 'urine_analysis'
          ? 'type-urine'
          : t === 'imaging'
            ? 'type-imaging'
            : 'type-other'
    },
    typeIcon (t) {
      return t === 'blood_work'
        ? 'bi bi-droplet-fill'
        : t === 'urine_analysis'
          ? 'bi bi-droplet-half'
          : t === 'imaging'
            ? 'bi bi-x-ray'
            : 'bi bi-clipboard-pulse'
    },
    typeLabel (t) {
      return t === 'blood_work'
        ? 'Xét nghiệm máu'
        : t === 'urine_analysis'
          ? 'Xét nghiệm nước tiểu'
          : t === 'imaging'
            ? 'Chẩn đoán hình ảnh'
            : t === 'biopsy'
              ? 'Sinh thiết'
              : t === 'culture'
                ? 'Cấy mẫu'
                : t === 'pathology'
                  ? 'Giải phẫu bệnh'
                  : t
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
          medical_record_id: this.filterRecordId || undefined, // ✅ fetch trực tiếp theo hồ sơ
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
        // Cập nhật dynamic test types từ dữ liệu nhận được
        const dyn = new Set(this.dynamicTypes || [])
        this.items.forEach(t => { if (t.type) dyn.add(t.type) })
        this.dynamicTypes = Array.from(dyn)
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
      const q = (this.q || '').toLowerCase().trim()
      this.filteredItems = this.items.filter(t => {
        const matchRecord = !this.filterRecordId || t.medical_record_id === this.filterRecordId
        const matchQuery = !q || (t.name?.toLowerCase().includes(q) || t.type?.toLowerCase().includes(q))
        return matchRecord && matchQuery
      })
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
      // Bổ sung option nếu type hiện tại chưa có trong danh sách
      if (this.form.type && !this.typeOptions.some(o => o.value === this.form.type)) {
        this.dynamicTypes = Array.from(new Set([...(this.dynamicTypes || []), this.form.type]))
      }

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

        // ✅ Auto-update medical record status if this test is completed
        if (payload.status === 'completed' && payload.medical_record_id) {
          await this.checkAndUpdateRecordStatus(payload.medical_record_id)
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
    },

    // ✅ Check and update medical record status when all treatments and tests are completed
    async checkAndUpdateRecordStatus (recordId) {
      try {
        // Get all treatments for this record
        const treatmentsRes = await TreatmentService.list({
          medical_record_id: recordId,
          limit: 1000
        })
        const treatments = treatmentsRes?.data || []

        // Get all tests for this record
        const testsRes = await MedicalTestService.list({
          medical_record_id: recordId,
          limit: 1000
        })
        const arr = (r) => {
          if (Array.isArray(r?.rows)) return r.rows.map(x => x.doc || x.value || x)
          if (Array.isArray(r?.data)) return r.data
          if (Array.isArray(r)) return r
          return []
        }
        const tests = arr(testsRes).filter(t => t.medical_record_id === recordId)

        // Check if all are completed
        const allTreatmentsCompleted = treatments.length === 0 || treatments.every(t => t.status === 'completed')
        const allTestsCompleted = tests.length === 0 || tests.every(t => t.status === 'completed')

        if (allTreatmentsCompleted && allTestsCompleted) {
          const recordRes = await MedicalRecordService.get(recordId)
          const recordData = recordRes?.data || recordRes

          if (recordData && recordData.status !== 'completed') {
            console.log('✅ All treatments and tests completed, updating record status')
            await MedicalRecordService.update(recordId, {
              ...recordData,
              status: 'completed',
              updated_at: new Date().toISOString()
            })
          }
        }
      } catch (e) {
        console.error('❌ Failed to check/update record status:', e)
      }
    }
  }
}
</script>

<style scoped>
/* Base Styles */
.medical-tests-management {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
  padding-bottom: 2rem;
}

/* Header Section */
.header-section {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  padding: 2rem 2.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.header-left {
  flex: 1;
}

.page-title {
  color: white;
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 0.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-title i {
  font-size: 2.5rem;
}

.page-subtitle {
  color: rgba(255, 255, 255, 0.95);
  font-size: 1rem;
  margin: 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn-action {
  height: 2.75rem;
  padding: 0 1.25rem;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  white-space: nowrap;
}

.btn-back {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.btn-back:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.25);
  border-color: rgba(255, 255, 255, 0.5);
}

.btn-refresh {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.2);
  width: 2.75rem;
  padding: 0;
  justify-content: center;
}

.btn-refresh:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
  transform: rotate(90deg);
}

.btn-primary {
  background: white;
  color: #3b82f6;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

.btn-action:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.stats-badge {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  border: 2px solid rgba(255, 255, 255, 0.2);
}

.stats-badge strong {
  font-weight: 700;
  font-size: 1.1rem;
}

.page-size-selector select {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.3);
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  font-size: 0.9rem;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  padding-right: 2rem;
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 0.5rem center;
  background-size: 1.2em;
}

.page-size-selector select:hover {
  background-color: rgba(255, 255, 255, 0.25);
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
}

.page-size-selector select option {
  background: #1e293b;
  color: white;
  padding: 0.5rem;
}

/* Search Section */
.search-section {
  padding: 1.5rem 2.5rem;
  background: transparent;
}

.search-container {
  max-width: 1400px;
  margin: 0 auto;
}

.filter-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: nowrap;
}

.filter-input-group {
  background: white;
  border-radius: 10px;
  padding: 0.65rem 0.85rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex: 1;
  min-width: 0;
}

.filter-input-group.filter-select {
  flex: 1.5;
}

.search-icon {
  color: #9ca3af;
  font-size: 1rem;
  flex-shrink: 0;
}

.filter-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 0.875rem;
  color: #374151;
  background: transparent;
  min-width: 0;
}

.filter-input::placeholder {
  color: #9ca3af;
}

.search-btn {
  height: 2.6rem;
  padding: 0 1.5rem;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  white-space: nowrap;
  flex-shrink: 0;
  font-size: 0.9rem;
}

.search-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.search-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.clear-filter-btn {
  height: 2.6rem;
  width: 2.6rem;
  border-radius: 10px;
  border: 2px solid #ef4444;
  background: white;
  color: #ef4444;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 1.1rem;
}

.clear-filter-btn:hover {
  background: #ef4444;
  color: white;
  transform: translateY(-1px);
}

/* Content Section */
.content-section {
  padding: 0 2.5rem 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.alert-error {
  background: #fee2e2;
  border-left: 4px solid #ef4444;
  color: #991b1b;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.loading-state {
  text-align: center;
  padding: 3rem 0;
  color: #64748b;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.spinner {
  width: 3rem;
  height: 3rem;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Table Styles */
.table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.tests-table {
  width: 100%;
  border-collapse: collapse;
}

.tests-table thead {
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
  border-bottom: 2px solid #e2e8f0;
}

.tests-table thead th {
  padding: 1.25rem 1rem;
  text-align: left;
  font-weight: 700;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #64748b;
  white-space: nowrap;
}

.col-number {
  width: 60px;
  text-align: center !important;
}

.col-type {
  width: 160px;
}

.col-name {
  width: auto;
  min-width: 200px;
}

.col-ordered {
  width: 150px;
}

.col-result {
  width: 150px;
}

.col-status {
  width: 130px;
  text-align: center !important;
}

.col-actions {
  width: 140px;
  text-align: center !important;
}

.tests-table tbody tr {
  border-bottom: 1px solid #f1f5f9;
  transition: all 0.2s ease;
}

.test-row:hover {
  background: #f8fafc;
}

.test-row.expanded {
  background: #f0f9ff;
}

.tests-table tbody td {
  padding: 1.25rem 1rem;
  color: #374151;
  font-size: 0.9rem;
}

.cell-number {
  text-align: center;
}

.row-number {
  width: 2.2rem;
  height: 2.2rem;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.type-badge {
  padding: 0.4rem 1rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.type-blood {
  background: #fee2e2;
  color: #991b1b;
}

.type-urine {
  background: #fef3c7;
  color: #92400e;
}

.type-imaging {
  background: #dbeafe;
  color: #1e40af;
}

.type-other {
  background: #f1f5f9;
  color: #475569;
}

.cell-name strong {
  color: #1e293b;
  font-weight: 600;
}

.date-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.date-info i {
  color: #3b82f6;
  font-size: 1rem;
}

.cell-status {
  text-align: center;
}

.status-badge {
  padding: 0.4rem 1rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.status-completed {
  background: #d1fae5;
  color: #065f46;
}

.status-in-progress {
  background: #fef3c7;
  color: #92400e;
}

.status-canceled {
  background: #fee2e2;
  color: #991b1b;
}

.status-pending {
  background: #f1f5f9;
  color: #475569;
}

.action-buttons {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
}

.action-btn {
  width: 2.2rem;
  height: 2.2rem;
  border-radius: 6px;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.95rem;
  flex-shrink: 0;
}

.view-btn {
  background: #dbeafe;
  color: #3b82f6;
}

.view-btn:hover:not(:disabled) {
  background: #3b82f6;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.edit-btn {
  background: #fef3c7;
  color: #f59e0b;
}

.edit-btn:hover:not(:disabled) {
  background: #f59e0b;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.delete-btn {
  background: #fee2e2;
  color: #ef4444;
}

.delete-btn:hover:not(:disabled) {
  background: #ef4444;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Detail Row */
.detail-row td {
  background: #f0f9ff !important;
  padding: 0 !important;
}

.detail-wrap {
  padding: 2rem;
  background: white;
  margin: 1rem;
  border-radius: 8px;
  border-left: 4px solid #3b82f6;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.detail-title {
  font-weight: 700;
  font-size: 1rem;
  color: #374151;
  margin: 1.5rem 0 1rem 0;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e5e7eb;
}

.detail-title:first-child {
  margin-top: 0;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1rem 1.5rem;
  color: #374151;
  font-size: 0.9rem;
}

.detail-grid b {
  color: #64748b;
  font-weight: 600;
}

/* Pagination Section */
.pagination-section {
  margin-top: 1.5rem;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.pagination-info-row {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 8px;
  margin-bottom: 1rem;
  font-size: 0.875rem;
  color: #334155;
}

.pagination-info-row i {
  color: #3b82f6;
  font-size: 1rem;
}

.pagination-info-row strong {
  color: #1e40af;
  font-weight: 600;
}

.pagination-controls-center {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
}

.pagination-btn {
  width: 36px;
  height: 36px;
  border: 2px solid #e2e8f0;
  background: white;
  color: #64748b;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  font-size: 16px;
}

.pagination-btn:hover:not(:disabled) {
  border-color: #3b82f6;
  color: #3b82f6;
  transform: translateY(-1px);
}

.pagination-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 6px;
}

.page-number-btn {
  min-width: 36px;
  height: 36px;
  padding: 0 12px;
  border: 2px solid #e2e8f0;
  background: white;
  color: #64748b;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.page-number-btn:hover:not(:disabled):not(.active) {
  border-color: #3b82f6;
  color: #3b82f6;
}

.page-number-btn.active {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border-color: transparent;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.page-number-btn.ellipsis {
  border: none;
  background: transparent;
  cursor: default;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  overflow-y: auto;
  padding: 1rem;
}

.modal-container {
  width: min(1100px, 95vw);
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-header-custom {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  padding: 24px 32px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 16px 16px 0 0;
}

.modal-title-custom {
  color: white;
  font-size: 24px;
  font-weight: 700;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.modal-close-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.modal-close-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

.modal-body-custom {
  padding: 32px;
  overflow-y: auto;
  flex: 1;
}

.form-section {
  margin-bottom: 32px;
}

.form-section:last-child {
  margin-bottom: 0;
}

.form-section-title {
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 20px 0;
  padding-bottom: 12px;
  border-bottom: 2px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.form-section-title i {
  color: #3b82f6;
  font-size: 20px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label-custom {
  font-size: 14px;
  font-weight: 600;
  color: #475569;
  display: flex;
  align-items: center;
  gap: 6px;
}

.form-label-custom i {
  color: #3b82f6;
  font-size: 16px;
}

.text-required {
  color: #dc2626;
}

.form-label-hint {
  font-size: 12px;
  font-weight: 400;
  color: #94a3b8;
  font-style: italic;
}

.form-input-custom {
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  color: #1e293b;
  transition: all 0.3s ease;
  background: white;
}

.form-input-custom:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input-custom:disabled {
  background: #f1f5f9;
  cursor: not-allowed;
}

.form-input-custom::placeholder {
  color: #94a3b8;
}

.modal-footer-custom {
  padding: 20px 32px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  border-radius: 0 0 16px 16px;
}

.btn-modal-cancel {
  padding: 12px 24px;
  background: white;
  color: #64748b;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
}

.btn-modal-cancel:hover:not(:disabled) {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.btn-modal-save {
  padding: 12px 24px;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
}

.btn-modal-save:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(59, 130, 246, 0.4);
}

.btn-modal-cancel:disabled,
.btn-modal-save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.text-center {
  text-align: center;
}

.text-muted {
  color: #9ca3af;
}

.text-end {
  text-align: right;
}
</style>
