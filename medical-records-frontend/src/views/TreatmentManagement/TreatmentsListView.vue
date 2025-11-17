<template>
  <div class="treatments-management">
    <!-- Header Section -->
    <header class="header-section">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="bi bi-capsule"></i>
            Phác đồ điều trị
          </h1>
          <p class="page-subtitle">Quản lý phác đồ điều trị và theo dõi tiến trình</p>
        </div>
        <div class="header-actions">
          <button class="btn-action btn-back" @click="$router.go(-1)">
            <i class="bi bi-arrow-left"></i>
          </button>
          <button class="btn-action btn-refresh" @click="reload" :disabled="loading">
            <i class="bi bi-arrow-clockwise"></i>
          </button>
          <div class="stats-badge">
            <i class="bi bi-list-check"></i>
            Tổng: <strong>{{ total }}</strong>
          </div>
          <div class="page-size-selector custom-dropdown" @click="togglePageSizeDropdown" :tabindex="0" @blur="closePageSizeDropdown" style="position:relative;display:inline-block;min-width:140px;">
            <div class="dropdown-selected" :class="{ open: pageSizeDropdownOpen }">
              {{ pageSize }} / trang
              <span class="dropdown-arrow" :class="{ open: pageSizeDropdownOpen }">&#9662;</span>
            </div>
            <div v-if="pageSizeDropdownOpen" class="dropdown-list">
              <div v-for="size in [10,25,50,100]" :key="size" class="dropdown-item" :class="{ selected: pageSize === size }" @click.stop="selectPageSize(size)">
                {{ size }} / trang
              </div>
            </div>
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
        <div class="filter-row">
          <div class="filter-input-group">
            <i class="bi bi-search search-icon"></i>
            <input
              v-model.trim="q"
              class="filter-input"
              placeholder="Tìm theo tên điều trị, loại..."
              @keyup.enter="search"
            />
          </div>
          <div class="filter-input-group filter-select">
            <i class="bi bi-folder2-open search-icon"></i>
            <select v-model="filterRecordId" class="filter-input" @change="applyFilter">
              <option value="">-- Tất cả hồ sơ khám --</option>
              <option v-for="opt in recordOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <button class="search-btn" @click="search" :disabled="loading">
            <i class="bi bi-search"></i>
            Tìm kiếm
          </button>
          <button v-if="filterRecordId" class="clear-filter-btn" @click="clearFilter" title="Xóa bộ lọc">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
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
        <table class="treatments-table">
          <thead>
            <tr>
              <th class="col-number">#</th>
              <th class="col-name">Tên điều trị</th>
              <th class="col-start">Bắt đầu</th>
              <th class="col-end">Kết thúc</th>
              <th class="col-type">Loại</th>
              <th class="col-status">Trạng thái</th>
              <th class="col-actions">Hành động</th>
            </tr>
          </thead>
          <tbody v-for="(t, idx) in filteredItems" :key="rowKey(t, idx)">
            <tr class="treatment-row" :class="{ expanded: isExpanded(t) }">
              <td class="cell-number">
                <span class="row-number">{{ idx + 1 + (page - 1) * pageSize }}</span>
              </td>
              <td class="cell-name">
                <strong>{{ t.treatment_name || 'Chưa đặt tên' }}</strong>
              </td>
              <td class="cell-start">
                <div class="date-info" v-if="t.start_date">
                  <i class="bi bi-calendar-check"></i>
                  {{ fmtDate(t.start_date) }}
                </div>
                <span v-else class="text-muted">-</span>
              </td>
              <td class="cell-end">
                <div class="date-info" v-if="t.end_date">
                  <i class="bi bi-calendar-x"></i>
                  {{ fmtDate(t.end_date) }}
                </div>
                <span v-else class="text-muted">-</span>
              </td>
              <td class="cell-type">
                <span :class="['type-badge', typeClass(t.treatment_type)]">
                  <i :class="typeIcon(t.treatment_type)"></i>
                  {{ typeLabel(t.treatment_type) }}
                </span>
              </td>
              <td class="cell-status">
                <span :class="['status-badge', statusClass(t.status)]">
                  <i :class="statusIcon(t.status)"></i>
                  {{ statusLabel(t.status) }}
                </span>
              </td>
              <td class="cell-actions">
                <div class="action-buttons">
                  <button class="action-btn view-btn" @click="toggleRow(t)" :title="isExpanded(t) ? 'Thu gọn' : 'Xem chi tiết'">
                    <i :class="isExpanded(t) ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                  </button>
                  <button class="action-btn edit-btn" @click="openEdit(t)" title="Chỉnh sửa">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="action-btn delete-btn" @click="remove(t)" :disabled="loading" title="Xóa">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>

            <!-- Detail Row -->
            <tr v-if="isExpanded(t)" class="detail-row">
              <td colspan="7">
                <div class="detail-wrap">
                  <div class="detail-title">Liên kết</div>
                  <div class="detail-grid">
                    <div><b>Bệnh nhân:</b> {{ displayName(patientsMap[t.patient_id]) || t.patient_id || '-' }}</div>
                    <div><b>Bác sĩ:</b> {{ displayName(doctorsMap[t.doctor_id]) || t.doctor_id || '-' }}</div>
                    <div><b>Hồ sơ khám:</b> {{ t.medical_record_id || '-' }}</div>
                  </div>

                  <div class="detail-title">Thông tin điều trị</div>
                  <div class="detail-grid">
                    <div><b>Tên:</b> {{ t.treatment_name || '-' }}</div>
                    <div><b>Loại:</b> {{ typeLabel(t.treatment_type) }}</div>
                    <div><b>Bắt đầu:</b> {{ fmtDate(t.start_date) }}</div>
                    <div><b>Kết thúc:</b> {{ fmtDate(t.end_date) }}</div>
                    <div><b>Số ngày:</b> {{ t.duration_days ?? '-' }}</div>
                  </div>

                  <div class="detail-title">Thuốc</div>
                  <ul class="medication-list">
                    <li v-for="(m, i) in t.medications" :key="i">
                      <b>{{ m.name || 'Không có tên' }}</b>
                      <span v-if="m.dosage"> — {{ m.dosage }}</span>
                      <span v-if="m.frequency">; {{ m.frequency }}</span>
                      <span v-if="m.route">; {{ m.route }}</span>
                      <span v-if="m.instructions">; {{ m.instructions }}</span>
                      <span v-if="m.quantity_prescribed">; SL: {{ m.quantity_prescribed }}</span>
                      <span class="text-muted" v-if="m.medication_id"> ({{ m.medication_id }})</span>
                    </li>
                    <li v-if="!t.medications || !t.medications.length" class="text-muted">Không có thuốc</li>
                  </ul>

                  <div class="detail-title">Theo dõi</div>
                  <div class="detail-grid">
                    <div><b>Tham số:</b> {{ (t.monitor_params || []).join(', ') || '-' }}</div>
                    <div><b>Tần suất:</b> {{ t.monitor_frequency || '-' }}</div>
                    <div><b>Lịch kiểm tra tiếp:</b> {{ fmtDate(t.monitor_next_check) }}</div>
                  </div>

                  <div class="detail-meta">
                    <span><i class="bi bi-clock-history"></i> Tạo: {{ fmtDateTime(t.created_at) }}</span>
                    <span><i class="bi bi-pencil-square"></i> Cập nhật: {{ fmtDateTime(t.updated_at) }}</span>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>

          <tbody v-if="!filteredItems.length">
            <tr>
              <td colspan="7" class="text-center text-muted">
                {{ filterRecordId ? 'Không tìm thấy điều trị cho hồ sơ này' : 'Không có dữ liệu' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination-section">
        <div class="pagination-info-row">
          <i class="bi bi-info-circle"></i>
          Hiển thị {{ filteredItems.length > 0 ? (page - 1) * pageSize + 1 : 0 }} - {{ Math.min(page * pageSize, total) }} trong tổng số <strong>{{ total }}</strong> bản ghi
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
            {{ editingId ? 'Sửa điều trị' : 'Thêm điều trị mới' }}
          </h3>
          <button type="button" class="modal-close-btn" @click="close">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <div class="modal-body-custom">
          <form @submit.prevent="save">
            <!-- Liên kết -->
            <div class="form-section">
              <h4 class="form-section-title">
                <i class="bi bi-link-45deg"></i>
                Liên kết
              </h4>
              <div class="form-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                  <label class="form-label-custom">
                    <i class="bi bi-folder2-open"></i>
                    Hồ sơ khám <span class="text-required">*</span>
                  </label>
                  <select v-model="form.medical_record_id" class="form-input-custom" required @change="onRecordChange">
                    <option value="">-- Chọn hồ sơ khám --</option>
                    <option v-for="r in recordOptions" :key="r.value" :value="r.value">{{ r.label }}</option>
                  </select>
                  <span class="form-label-hint">Chọn hồ sơ khám để tự động điền bệnh nhân và bác sĩ</span>
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-person"></i>
                    Bệnh nhân
                  </label>
                  <input v-model="form.patient_name" class="form-input-custom" readonly placeholder="Tự động từ hồ sơ" />
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-person-badge"></i>
                    Bác sĩ
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
              </div>
            </div>

            <!-- Thông tin điều trị -->
            <div class="form-section">
              <h4 class="form-section-title">
                <i class="bi bi-capsule"></i>
                Thông tin điều trị
              </h4>
              <div class="form-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                  <label class="form-label-custom">
                    <i class="bi bi-file-text"></i>
                    Tên điều trị
                  </label>
                  <input v-model.trim="form.treatment_name" class="form-input-custom" placeholder="Điều trị tăng huyết áp" />
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-tag"></i>
                    Loại điều trị <span class="text-required">*</span>
                  </label>
                  <select v-model="form.treatment_type" class="form-input-custom" required>
                    <option value="">-- Chọn loại --</option>
                    <option value="medication">Điều trị bằng thuốc</option>
                    <option value="procedure">Thủ thuật</option>
                    <option value="surgery">Phẫu thuật</option>
                    <option value="therapy">Vật lý trị liệu</option>
                    <option value="rehabilitation">Phục hồi chức năng</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-toggle-on"></i>
                    Trạng thái
                  </label>
                  <select v-model="form.status" class="form-input-custom">
                    <option value="active">Đang điều trị</option>
                    <option value="paused">Tạm dừng</option>
                    <option value="completed">Hoàn thành</option>
                    <option value="discontinued">Ngừng</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-calendar-check"></i>
                    Ngày bắt đầu
                  </label>
                  <input v-model="form.start_date" type="date" class="form-input-custom" />
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-calendar-x"></i>
                    Ngày kết thúc
                  </label>
                  <input v-model="form.end_date" type="date" class="form-input-custom" />
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-clock"></i>
                    Số ngày điều trị
                  </label>
                  <input v-model.number="form.duration_days" type="number" min="0" class="form-input-custom" />
                </div>
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
            <div class="form-section">
              <h4 class="form-section-title">
                <i class="bi bi-heart-pulse"></i>
                Theo dõi và giám sát
              </h4>
              <div class="form-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                  <label class="form-label-custom">
                    <i class="bi bi-clipboard-data"></i>
                    Tham số theo dõi
                  </label>
                  <input v-model.trim="form.monitor_params_text" class="form-input-custom" placeholder="blood_pressure, heart_rate, temperature" />
                  <span class="form-label-hint">Nhập các tham số cách nhau bằng dấu phẩy</span>
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-arrow-repeat"></i>
                    Tần suất theo dõi
                  </label>
                  <input v-model.trim="form.monitor_frequency" class="form-input-custom" placeholder="daily / weekly / monthly" />
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-calendar-check"></i>
                    Lịch kiểm tra tiếp theo
                  </label>
                  <input v-model="form.monitor_next_check" type="date" class="form-input-custom" />
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
      optionsLoaded: false,
      // ✅ Filter
      filterRecordId: '',
      filteredItems: [],
      // Custom dropdown
      pageSizeDropdownOpen: false
    }
  },
  created () {
    // ✅ Check if medical_record_id from query parameter
    if (this.$route.query.medical_record_id) {
      this.filterRecordId = this.$route.query.medical_record_id
    }
    this.fetch()
  },
  computed: {
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
    fmtDate (v) { if (!v) return '-'; try { return new Date(v).toISOString().slice(0, 10) } catch { return v } },
    fmtDateTime (v) { if (!v) return '-'; try { return new Date(v).toLocaleString('vi-VN') } catch { return v } },
    statusClass (s) {
      return s === 'active'
        ? 'status-active'
        : s === 'paused'
          ? 'status-paused'
          : s === 'completed'
            ? 'status-completed'
            : s === 'discontinued'
              ? 'status-discontinued'
              : 'status-pending'
    },
    statusIcon (s) {
      return s === 'active'
        ? 'bi bi-play-circle-fill'
        : s === 'paused'
          ? 'bi bi-pause-circle-fill'
          : s === 'completed'
            ? 'bi bi-check-circle-fill'
            : s === 'discontinued'
              ? 'bi bi-x-circle-fill'
              : 'bi bi-clock-fill'
    },
    statusLabel (s) {
      return s === 'active'
        ? 'Đang điều trị'
        : s === 'paused'
          ? 'Tạm dừng'
          : s === 'completed'
            ? 'Hoàn thành'
            : s === 'discontinued'
              ? 'Ngừng'
              : 'Chờ xử lý'
    },
    typeClass (t) {
      return t === 'medication'
        ? 'type-medication'
        : t === 'procedure'
          ? 'type-procedure'
          : t === 'surgery'
            ? 'type-surgery'
            : t === 'therapy'
              ? 'type-therapy'
              : t === 'rehabilitation'
                ? 'type-rehabilitation'
                : 'type-other'
    },
    typeIcon (t) {
      return t === 'medication'
        ? 'bi bi-capsule'
        : t === 'procedure'
          ? 'bi bi-scissors'
          : t === 'surgery'
            ? 'bi bi-heart-pulse'
            : t === 'therapy'
              ? 'bi bi-activity'
              : t === 'rehabilitation'
                ? 'bi bi-hospital'
                : 'bi bi-clipboard-pulse'
    },
    typeLabel (t) {
      return t === 'medication'
        ? 'Điều trị bằng thuốc'
        : t === 'procedure'
          ? 'Thủ thuật'
          : t === 'surgery'
            ? 'Phẫu thuật'
            : t === 'therapy'
              ? 'Vật lý trị liệu'
              : t === 'rehabilitation'
                ? 'Phục hồi chức năng'
                : t || '-'
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
    togglePageSizeDropdown () {
      this.pageSizeDropdownOpen = !this.pageSizeDropdownOpen
    },
    closePageSizeDropdown () {
      this.pageSizeDropdownOpen = false
    },
    selectPageSize (size) {
      if (this.pageSize !== size) {
        this.pageSize = size
        this.changePageSize()
      }
      this.pageSizeDropdownOpen = false
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
        patient_name: '',
        doctor_name: '',
        visit_date: '',
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
          DoctorService.list({ limit: 1000 }),
          PatientService.list({ limit: 1000 }),
          MedicalRecordService.list({ limit: 1000 })
        ])
        const arr = r => (Array.isArray(r?.rows)
          ? r.rows.map(x => x.doc || x.value || x)
          : Array.isArray(r?.data)
            ? r.data
            : Array.isArray(r) ? r : [])
        const dList = arr(dRes)
        const pList = arr(pRes)
        const rList = arr(rRes)
        const key = o => o._id || o.id || o.code || o.username
        const label = o => o?.personal_info?.full_name || o.full_name || o.name || o.display_name || o.code || o.username || key(o)

        this.doctorOptions = dList.map(o => ({ value: key(o), label: label(o) }))
        this.patientOptions = pList.map(o => ({ value: key(o), label: label(o) }))

        // Create medical record options
        this.recordOptions = rList.map(rec => {
          const patient = pList.find(p => key(p) === rec.patient_id)
          const doctor = dList.find(d => key(d) === rec.doctor_id)
          const visitDate = rec.visit_info?.visit_date || rec.visit_date
          const dateStr = visitDate ? new Date(visitDate).toLocaleDateString('vi-VN') : ''
          const visitType = rec.visit_info?.visit_type || rec.visit_type || 'khám'

          return {
            value: key(rec),
            label: `${dateStr} - ${label(patient)} - ${visitType}`,
            patient_id: rec.patient_id,
            doctor_id: rec.doctor_id,
            patient_name: label(patient),
            doctor_name: label(doctor),
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
        console.error(e)
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
      this.ensureOptionsLoaded().then(() => {
        // After options loaded, populate display names
        this.onRecordChange()
      })
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
/* Base Styles */
.treatments-management {
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

.custom-dropdown {
  position: relative;
  min-width: 140px;
  user-select: none;
}
.dropdown-selected {
  background: rgba(255,255,255,0.15);
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.3);
  padding: 0.7rem 1.5rem 0.7rem 1rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 1.1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.dropdown-selected:hover {
  background: rgba(255,255,255,0.25);
  border-color: rgba(255, 255, 255, 0.5);
}
.dropdown-selected.open {
  background: rgba(255,255,255,0.25);
  border-color: rgba(255, 255, 255, 0.5);
}
.dropdown-arrow {
  margin-left: 1rem;
  font-size: 1.2em;
  color: white;
  transition: transform 0.2s;
}
.dropdown-arrow.open {
  transform: rotate(180deg);
}
.dropdown-list {
  position: absolute;
  top: 110%;
  left: 0;
  width: 100%;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(59,130,246,0.25);
  z-index: 10;
  border: 2px solid #3b82f6;
  padding: 0.5rem 0;
  animation: dropdownFadeIn 0.18s;
}
@keyframes dropdownFadeIn {
  from { opacity: 0; transform: translateY(-8px); }
  to { opacity: 1; transform: translateY(0); }
}
.dropdown-item {
  padding: 0.75rem 1rem;
  font-size: 1rem;
  color: #3b82f6;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
  font-weight: 500;
}
.dropdown-item.selected {
  background: #94a3b8;
  color: white;
}
.dropdown-item:hover {
  background: #dbeafe;
  color: #1d4ed8;
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

.treatments-table {
  width: 100%;
  border-collapse: collapse;
}

.treatments-table thead {
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
  border-bottom: 2px solid #e2e8f0;
}

.treatments-table thead th {
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

.col-name {
  width: auto;
  min-width: 200px;
}

.col-start {
  width: 150px;
}

.col-end {
  width: 150px;
}

.col-type {
  width: 180px;
}

.col-status {
  width: 150px;
  text-align: center !important;
}

.col-actions {
  width: 140px;
  text-align: center !important;
}

.treatments-table tbody tr {
  border-bottom: 1px solid #f1f5f9;
  transition: all 0.2s ease;
}

.treatment-row:hover {
  background: #f8fafc;
}

.treatment-row.expanded {
  background: #f0f9ff;
}

.treatments-table tbody td {
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

.type-badge {
  padding: 0.4rem 1rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.type-medication {
  background: #dbeafe;
  color: #1e40af;
}

.type-procedure {
  background: #fef3c7;
  color: #92400e;
}

.type-surgery {
  background: #fee2e2;
  color: #991b1b;
}

.type-therapy {
  background: #d1fae5;
  color: #065f46;
}

.type-rehabilitation {
  background: #e9d5ff;
  color: #6b21a8;
}

.type-other {
  background: #f1f5f9;
  color: #475569;
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

.status-active {
  background: #d1fae5;
  color: #065f46;
}

.status-paused {
  background: #fef3c7;
  color: #92400e;
}

.status-completed {
  background: #dbeafe;
  color: #1e40af;
}

.status-discontinued {
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

.medication-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.medication-list li {
  padding: 0.5rem 0;
  border-bottom: 1px solid #f1f5f9;
}

.medication-list li:last-child {
  border-bottom: none;
}

.detail-meta {
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 2rem;
  font-size: 0.85rem;
  color: #64748b;
}

.detail-meta i {
  margin-right: 0.5rem;
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

/* Medications Table */
.medications-table-wrapper {
  overflow-x: auto;
  margin-bottom: 16px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.medications-table {
  width: 100%;
  border-collapse: collapse;
}

.medications-table thead {
  background: #f8fafc;
}

.medications-table th {
  padding: 12px;
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
  text-align: left;
  border-bottom: 2px solid #e2e8f0;
}

.medications-table td {
  padding: 8px;
  border-bottom: 1px solid #f1f5f9;
}

.medications-table tbody tr:last-child td {
  border-bottom: none;
}

.medications-table .form-input-custom {
  padding: 8px 12px;
  font-size: 13px;
}

.btn-remove-med {
  width: 28px;
  height: 28px;
  background: #fee2e2;
  color: #ef4444;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.btn-remove-med:hover {
  background: #ef4444;
  color: white;
  transform: scale(1.1);
}

.btn-add-medication {
  padding: 10px 20px;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
  font-size: 14px;
}

.btn-add-medication:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
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
