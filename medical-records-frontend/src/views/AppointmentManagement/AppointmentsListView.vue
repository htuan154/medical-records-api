<template>
  <div>
    <section class="appointments-management">
      <!-- Header Section -->
      <div class="header-section">
        <div class="header-content">
          <div class="header-left">
            <h1 class="page-title">
              <i class="bi bi-calendar-check"></i>
              Quản lý Lịch hẹn
            </h1>
            <p class="page-subtitle">Quản lý lịch hẹn khám bệnh</p>
          </div>
          <div class="header-actions">
            <button class="btn-action btn-back" @click="$router.push('/')" title="Quay lại Trang chủ">
              <i class="bi bi-arrow-left"></i>
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
              <i class="bi bi-person search-icon"></i>
              <input
                v-model.trim="f.patient_name"
                class="filter-input"
                placeholder="Tên bệnh nhân"
                @keyup.enter="search"
              />
            </div>
            <div class="filter-input-group">
              <i class="bi bi-telephone search-icon"></i>
              <input
                v-model.trim="f.patient_phone"
                class="filter-input"
                placeholder="SĐT bệnh nhân"
                @keyup.enter="search"
              />
            </div>
            <div class="filter-input-group">
              <i class="bi bi-person-badge search-icon"></i>
              <input
                v-model.trim="f.doctor_name"
                class="filter-input"
                placeholder="Tên bác sĩ"
                @keyup.enter="search"
              />
            </div>
            <div class="filter-input-group">
              <i class="bi bi-toggle-on search-icon"></i>
              <select v-model="f.status" class="filter-input">
                <option value="">-- Trạng thái --</option>
                <option value="scheduled">Đã đặt</option>
                <option value="approved">Đã duyệt</option>
                <option value="completed">Hoàn thành</option>
                <option value="canceled">Đã hủy</option>
                <option value="pending">Chờ xử lý</option>
              </select>
            </div>
            <div class="filter-input-group filter-date">
              <i class="bi bi-calendar-event search-icon"></i>
              <input v-model="f.from" type="datetime-local" class="filter-input" placeholder="Từ ngày" />
            </div>
            <div class="filter-input-group filter-date">
              <i class="bi bi-calendar-event search-icon"></i>
              <input v-model="f.to" type="datetime-local" class="filter-input" placeholder="Đến ngày" />
            </div>
            <button class="search-btn" @click="search" :disabled="loading">
              <i class="bi bi-search"></i>
              Tìm
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
            <table class="appointments-table">
              <thead>
                <tr>
                  <th class="col-number">#</th>
                  <th class="col-patient">Bệnh nhân</th>
                  <th class="col-doctor">Bác sĩ</th>
                  <th class="col-time">Thời gian</th>
                  <th class="col-duration">Thời lượng</th>
                  <th class="col-type">Loại khám</th>
                  <th class="col-priority">Ưu tiên</th>
                  <th class="col-status">Trạng thái</th>
                  <th class="col-actions">Hành động</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="(a, idx) in items" :key="rowKey(a, idx)">
                  <tr class="appointment-row" :class="{ 'expanded': isExpanded(a) }">
                    <td class="cell-number">
                      <span class="row-number">{{ idx + 1 + (page - 1) * pageSize }}</span>
                    </td>
                    <td class="cell-patient">
                      <div class="patient-info">
                        <i class="bi bi-person-fill"></i>
                        <strong>{{ displayName(patientsMap[a.patient_id]) || a.patient_id }}</strong>
                      </div>
                    </td>
                    <td class="cell-doctor">
                      <div class="doctor-info">
                        <i class="bi bi-person-badge"></i>
                        <span>{{ displayName(doctorsMap[a.doctor_id]) || a.doctor_id }}</span>
                      </div>
                    </td>
                    <td class="cell-time">
                      <div class="time-info">
                        <i class="bi bi-calendar3"></i>
                        <span>{{ fmtDateTime(a.scheduled_date) }}</span>
                      </div>
                    </td>
                    <td class="cell-duration">{{ a.duration }} phút</td>
                    <td class="cell-type">
                      <span class="type-text">{{ typeLabels[a.type] || a.type }}</span>
                    </td>
                    <td class="cell-priority">
                      <span class="priority-badge" :class="priorityClass(a.priority)">
                        {{ priorityLabels[a.priority] || a.priority }}
                      </span>
                    </td>
                    <td class="cell-status">
                      <span class="status-badge" :class="statusClass(a.status)">
                        <i :class="statusIcon(a.status)"></i>
                        {{ statusLabels[a.status] || a.status }}
                      </span>
                    </td>
                    <td class="cell-actions">
                      <div class="action-buttons">
                        <button class="action-btn view-btn" @click="toggleRow(a)" :title="isExpanded(a) ? 'Ẩn chi tiết' : 'Xem chi tiết'">
                          <i :class="isExpanded(a) ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                        </button>
                        <button
                          class="action-btn record-btn"
                          @click="createRecordFromAppointment(a)"
                          :disabled="loading || !canCreateMedicalRecord(a)"
                          :title="canCreateMedicalRecord(a) ? 'Tạo hồ sơ bệnh án' : 'Chỉ tạo hồ sơ sau khi check-in'">
                          <i class="bi bi-journal-medical"></i>
                        </button>
                        <button v-if="['scheduled', 'approved'].includes(a.status)" class="action-btn checkin-btn" @click="checkIn(a)" :disabled="loading" title="Check-in">
                          <i class="bi bi-check-circle"></i>
                        </button>
                        <button v-if="a.status !== 'completed' && a.status !== 'canceled'" class="action-btn cancel-btn" @click="cancelAppointment(a)" :disabled="loading" title="Hủy">
                          <i class="bi bi-x-circle"></i>
                        </button>
                        <button class="action-btn edit-btn" @click="openEdit(a)" title="Sửa">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button class="action-btn delete-btn" @click="remove(a)" :disabled="loading" title="Xóa">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>

                  <!-- Details -->
                  <tr v-if="isExpanded(a)" class="detail-row">
                    <td :colspan="9">
                      <div class="detail-wrap">
                        <div class="detail-title">Chi tiết lịch hẹn</div>
                        <div class="detail-grid">
                          <div><b>Thời gian:</b> {{ fmtDateTime(a.scheduled_date) }}</div>
                          <div><b>Thời lượng:</b> {{ a.duration }} phút</div>
                          <div><b>Loại khám:</b> {{ typeLabels[a.type] || a.type }}</div>
                          <div><b>Ưu tiên:</b> {{ priorityLabels[a.priority] || a.priority }}</div>
                        </div>
                        <div class="detail-grid">
                          <div><b>Lý do:</b> {{ a.reason || 'Không có' }}</div>
                          <div><b>Ghi chú:</b> {{ a.notes || 'Không có' }}</div>
                        </div>
                        <div class="detail-title">Khác</div>
                        <div class="detail-grid">
                          <div><b>Tạo lúc:</b> {{ fmtDateTime(a.created_at) }}</div>
                          <div><b>Cập nhật:</b> {{ fmtDateTime(a.updated_at) }}</div>
                          <div><b>Người tạo:</b> {{ displayCreator(a.created_by) }}</div>
                        </div>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>

              <tbody v-if="!items.length">
                <tr>
                  <td colspan="9" class="text-center text-muted">Không có dữ liệu</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Section -->
          <div class="pagination-section">
            <div class="pagination-info-row">
              <i class="bi bi-file-earmark-text"></i>
              <span>Trang <strong>{{ page }} / {{ Math.max(1, Math.ceil((total || 0) / pageSize)) }}</strong> - Hiển thị {{ items.length }} trong tổng số {{ total }} lịch hẹn</span>
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
            <i class="bi bi-calendar-plus-fill" v-if="!editingId"></i>
            <i class="bi bi-pencil-square" v-else></i>
            {{ editingId ? 'Sửa lịch hẹn' : 'Thêm lịch hẹn' }}
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
                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-person-fill"></i>
                    Bệnh nhân <span class="text-required">*</span>
                  </label>
                  <select v-model="form.patient_id" class="form-input-custom" required>
                    <option value="">-- Chọn bệnh nhân --</option>
                    <option v-for="p in patientOptions" :key="p.value" :value="p.value">{{ p.label }}</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-person-badge"></i>
                    Bác sĩ <span class="text-required">*</span>
                  </label>
                  <select v-model="form.doctor_id" class="form-input-custom" required @change="loadAvailableSlots">
                    <option value="">-- Chọn bác sĩ --</option>
                    <option v-for="d in doctorOptions" :key="d.value" :value="d.value">{{ d.label }}</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-toggle-on"></i>
                    Trạng thái
                  </label>
                  <select v-model="form.status" class="form-input-custom">
                    <option value="scheduled">Đã đặt</option>
                    <option value="approved">Đã duyệt</option>
                    <option value="completed">Hoàn thành</option>
                    <option value="canceled">Đã hủy</option>
                    <option value="pending">Chờ xử lý</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Thông tin lịch hẹn -->
            <div class="form-section">
              <div class="form-section-title">
                <i class="bi bi-calendar-check"></i>
                Thông tin lịch hẹn
              </div>
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-calendar-event"></i>
                    Ngày khám <span class="text-required">*</span>
                  </label>
                  <input v-model="form.appointment_date" type="date" class="form-input-custom" required @change="loadAvailableSlots" />
                </div>
                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-clock"></i>
                    Thời lượng (phút) <span class="text-required">*</span>
                  </label>
                  <select v-model.number="form.duration" class="form-input-custom" required @change="loadAvailableSlots">
                    <option :value="15">15 phút</option>
                    <option :value="30">30 phút</option>
                    <option :value="45">45 phút</option>
                    <option :value="60">60 phút</option>
                    <option :value="90">90 phút</option>
                    <option :value="120">120 phút</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-alarm"></i>
                    Khung giờ khám <span class="text-required">*</span>
                  </label>
                  <select v-model="form.time_slot" class="form-input-custom" required :disabled="!availableSlots.length || !form.doctor_id || !form.appointment_date">
                    <option value="">-- Chọn giờ --</option>
                    <option v-for="slot in availableSlots" :key="slot.value" :value="slot.value">
                      {{ slot.label }}
                    </option>
                  </select>
                  <small v-if="loadingSlots" class="form-label-hint">Đang tải khung giờ...</small>
                  <small v-else-if="availableSlots.length === 0 && form.appointment_date && form.doctor_id" class="form-label-hint text-warning">
                    Không có khung giờ trống
                  </small>
                </div>
                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-clipboard2-pulse"></i>
                    Loại khám <span class="text-required">*</span>
                  </label>
                  <select v-model="form.type" class="form-input-custom" required>
                    <option value="consultation">Tư vấn</option>
                    <option value="follow_up">Tái khám</option>
                    <option value="checkup">Khám sức khỏe</option>
                    <option value="emergency">Cấp cứu</option>
                    <option value="procedure">Thủ thuật</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label-custom">
                    <i class="bi bi-exclamation-triangle"></i>
                    Ưu tiên
                  </label>
                  <select v-model="form.priority" class="form-input-custom">
                    <option value="normal">Bình thường</option>
                    <option value="high">Cao</option>
                    <option value="urgent">Khẩn cấp</option>
                  </select>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                  <label class="form-label-custom">
                    <i class="bi bi-chat-left-text"></i>
                    Lý do khám
                  </label>
                  <input v-model.trim="form.reason" class="form-input-custom" placeholder="Mô tả lý do đến khám..." />
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                  <label class="form-label-custom">
                    <i class="bi bi-file-text"></i>
                    Ghi chú
                  </label>
                  <textarea v-model.trim="form.notes" class="form-input-custom" rows="2" placeholder="Ghi chú thêm..."></textarea>
                </div>
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

    <!-- Centered confirm modal -->
    <div v-if="confirmModal.visible" class="overlay" @mousedown.self="closeConfirm">
      <div class="dialog">
        <div class="dialog-body" v-html="confirmModal.message"></div>
        <div class="dialog-actions">
          <button class="dialog-btn primary" @click="confirmOk">OK</button>
          <button class="dialog-btn" @click="closeConfirm">Hủy</button>
        </div>
      </div>
    </div>

    <!-- Centered info modal -->
    <div v-if="infoModal.visible" class="overlay" @mousedown.self="closeInfo">
      <div class="dialog">
        <div class="dialog-body" v-html="infoModal.message"></div>
        <div class="dialog-actions">
          <button class="dialog-btn primary" @click="closeInfo">Đóng</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex'
import AppointmentService from '@/api/appointmentService'
import DoctorService from '@/api/doctorService'
import PatientService from '@/api/patientService'

export default {
  name: 'AppointmentsListView',
  computed: {
    ...mapGetters(['me']),
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
      page: 1,
      pageSize: 50,
      hasMore: false,
      loading: false,
      error: '',
      // filters
      f: { patient_name: '', patient_phone: '', doctor_name: '', status: '', from: '', to: '' },
      // modal
      showModal: false,
      saving: false,
      editingId: null,
      form: this.emptyForm(),
      // expand
      expanded: {},
      // combos + map tên
      doctorOptions: [],
      patientOptions: [],
      doctorsMap: {},
      patientsMap: {},
      optionsLoaded: false,
      // Available slots
      availableSlots: [],
      loadingSlots: false,
      // Labels
      typeLabels: {
        consultation: 'Tư vấn',
        follow_up: 'Tái khám',
        checkup: 'Khám sức khỏe',
        emergency: 'Cấp cứu',
        procedure: 'Thủ thuật'
      },
      priorityLabels: {
        normal: 'Bình thường',
        high: 'Cao',
        urgent: 'Khẩn cấp'
      },
      statusLabels: {
        scheduled: 'Đã đặt',
        approved: 'Đã duyệt',
        completed: 'Hoàn thành',
        canceled: 'Đã hủy',
        pending: 'Chờ xử lý'
      },

      // Simple modals
      confirmModal: { visible: false, message: '', onConfirm: null, onCancel: null },
      infoModal: { visible: false, message: '' }
    }
  },
  created () {
    console.log('🔍 AppointmentsListView created, current user:', this.me)
    this.fetch()
  },
  methods: {
    /* ===== helpers ===== */
    fmtDateTime (v) { if (!v) return 'Không có'; try { return new Date(v).toLocaleString('vi-VN') } catch { return v } },
    rowKey (r, i) { return r._id || r.id || `${i}` },
    isExpanded (r) { return !!this.expanded[this.rowKey(r, 0)] },
    toggleRow (r) { const k = this.rowKey(r, 0); this.expanded = { ...this.expanded, [k]: !this.expanded[k] } },
    toDateTimeLocal (v) { if (!v) return ''; try { return new Date(v).toISOString().slice(0, 16) } catch { return '' } },
    displayName (o) {
      if (!o) return ''
      return o?.personal_info?.full_name || o?.full_name || o?.name || o?.display_name || o?.code || o?.username || ''
    },
    displayCreator (userId) {
      if (!userId) return 'Không có'

      // If creator is current user, show their name
      if (this.me && (userId === this.me.id || userId === this.me._id || userId === this.me.username)) {
        return this.me.username || this.me.name || this.me.full_name || 'Tôi'
      }

      // Extract username from user_xxx_001 format
      // user_receptionist_001 -> receptionist
      // user_admin_001 -> admin
      if (typeof userId === 'string' && userId.startsWith('user_')) {
        const parts = userId.split('_')
        if (parts.length >= 2) {
          // Return the middle part (role/username)
          return parts.slice(1, -1).join('_') || userId
        }
      }

      // Otherwise just show the full ID
      return userId
    },
    statusClass (s) {
      return s === 'scheduled'
        ? 'status-scheduled'
        : s === 'approved'
          ? 'status-approved'
          : s === 'completed'
            ? 'status-completed'
            : s === 'canceled'
              ? 'status-canceled'
              : 'status-pending'
    },
    statusIcon (s) {
      return s === 'scheduled'
        ? 'bi bi-calendar-check'
        : s === 'approved'
          ? 'bi bi-check-circle-fill'
          : s === 'completed'
            ? 'bi bi-check-all'
            : s === 'canceled'
              ? 'bi bi-x-circle-fill'
              : 'bi bi-clock-fill'
    },
    priorityClass (p) {
      return p === 'urgent'
        ? 'priority-urgent'
        : p === 'high'
          ? 'priority-high'
          : 'priority-normal'
    },
    goToPage (p) {
      if (p === '...' || p === this.page) return
      this.page = p
      this.fetch()
    },

    flattenAppointment (d = {}) {
      const ai = d.appointment_info || {}
      return {
        ...d,
        patient_id: d.patient_id || '',
        doctor_id: d.doctor_id || '',
        scheduled_date: ai.scheduled_date || d.scheduled_date || '',
        duration: ai.duration ?? d.duration ?? 30,
        type: ai.type || d.type || 'consultation',
        priority: ai.priority || d.priority || 'normal',
        reason: d.reason || '',
        status: d.status || 'scheduled',
        notes: d.notes || '',
        created_at: d.created_at || null,
        updated_at: d.updated_at || null,
        created_by: d.created_by || '',
        _rev: d._rev
      }
    },

    emptyForm () {
      return {
        _id: null,
        _rev: null,
        patient_id: '',
        doctor_id: '',
        appointment_date: '',
        time_slot: '',
        duration: 30,
        type: 'consultation',
        priority: 'normal',
        reason: '',
        status: 'scheduled',
        notes: ''
      }
    },

    /* ===== Generate time slots ===== */
    generateTimeSlots (duration) {
      const slots = []
      const addSlots = (startHour, endHour) => {
        for (let h = startHour; h < endHour; h++) {
          for (let m = 0; m < 60; m += 15) {
            const slotStart = h * 60 + m
            const slotEnd = slotStart + duration

            // Check if slot fits before end time
            if (slotEnd <= endHour * 60) {
              const hh = String(h).padStart(2, '0')
              const mm = String(m).padStart(2, '0')
              const endH = Math.floor(slotEnd / 60)
              const endM = slotEnd % 60
              const hhEnd = String(endH).padStart(2, '0')
              const mmEnd = String(endM).padStart(2, '0')

              slots.push({
                value: `${hh}:${mm}`,
                label: `${hh}:${mm} - ${hhEnd}:${mmEnd}`,
                startMinutes: slotStart,
                endMinutes: slotEnd
              })
            }
          }
        }
      }

      // Morning: 7:00 - 12:00
      addSlots(7, 12)
      // Afternoon: 13:00 - 17:00
      addSlots(13, 17)

      return slots
    },

    /* ===== Load available slots ===== */
    async loadAvailableSlots () {
      const doctorId = this.form.doctor_id
      const appointmentDate = this.form.appointment_date
      const duration = this.form.duration

      if (!doctorId || !appointmentDate || !duration) {
        this.availableSlots = []
        return
      }

      this.loadingSlots = true
      try {
        // Generate all possible slots
        const allSlots = this.generateTimeSlots(duration)

        // Nếu đang chỉnh sửa, luôn add slot hiện tại để không bị mất hiển thị
        const currentSlot = this.form.time_slot
        const currentOption = currentSlot
          ? {
              value: currentSlot,
              label: allSlots.find(s => s.value === currentSlot)?.label || `${currentSlot} - (hiện tại)`
            }
          : null

        // Get existing appointments for this doctor on this date
        const res = await AppointmentService.list({
          doctor_id: doctorId,
          from: `${appointmentDate}T00:00:00`,
          to: `${appointmentDate}T23:59:59`,
          limit: 1000
        })

        let existingAppointments = []
        if (res && Array.isArray(res.rows)) {
          existingAppointments = res.rows.map(r => r.doc || r.value || r)
        } else if (res && Array.isArray(res.data)) {
          existingAppointments = res.data
        } else if (Array.isArray(res)) {
          existingAppointments = res
        }

        // Filter out editing appointment
        if (this.editingId) {
          existingAppointments = existingAppointments.filter(a => {
            const id = a._id || a.id
            return id !== this.editingId && a.status !== 'canceled'
          })
        } else {
          existingAppointments = existingAppointments.filter(a => a.status !== 'canceled')
        }

        // Convert existing appointments to time ranges
        const busyRanges = existingAppointments.map(apt => {
          const scheduledDate = apt.appointment_info?.scheduled_date || apt.scheduled_date
          const aptDuration = apt.appointment_info?.duration || apt.duration || 30
          const date = new Date(scheduledDate)
          const startMinutes = date.getHours() * 60 + date.getMinutes()
          const endMinutes = startMinutes + aptDuration

          return { startMinutes, endMinutes }
        })

        // Filter available slots
        let slots = allSlots.filter(slot => {
          // Check if slot conflicts with any busy range
          return !busyRanges.some(busy => {
            // Slot is busy if it overlaps with existing appointment
            return !(slot.endMinutes <= busy.startMinutes || slot.startMinutes >= busy.endMinutes)
          })
        })

        // Đảm bảo slot hiện tại xuất hiện trong danh sách
        if (currentOption && !slots.some(s => s.value === currentOption.value)) {
          slots = [currentOption, ...slots]
        }
        this.availableSlots = slots
      } catch (e) {
        console.error('Error loading slots:', e)
        this.availableSlots = []
      } finally {
        this.loadingSlots = false
      }
    },

    /* ===== data ===== */
    async fetch () {
      this.loading = true
      this.error = ''
      try {
        const skip = (this.page - 1) * this.pageSize
        const params = {
          status: this.f.status || undefined,
          from: this.f.from || undefined,
          to: this.f.to || undefined,
          limit: this.pageSize,
          offset: skip,
          skip
        }
        const res = await AppointmentService.list(params)
        let raw = []; let total = 0; let offset = null
        if (res && Array.isArray(res.rows)) {
          raw = res.rows.map(r => r.doc || r.value || r); total = res.total_rows ?? raw.length; offset = res.offset ?? 0
        } else if (res && Array.isArray(res.data)) { raw = res.data; total = res.total ?? raw.length } else if (Array.isArray(res)) { raw = res; total = raw.length }

        // Map appointments and enrich with patient/doctor info
        this.items = (raw || []).map(d => this.flattenAppointment(d))

        // Client-side filtering by patient name/phone or doctor name
        if (this.f.patient_name || this.f.patient_phone || this.f.doctor_name) {
          this.items = this.items.filter(item => {
            const patient = this.patientsMap[item.patient_id]
            const doctor = this.doctorsMap[item.doctor_id]

            let matchPatientName = true
            let matchPatientPhone = true
            let matchDoctorName = true

            if (this.f.patient_name) {
              const patientName = (patient?.personal_info?.full_name || patient?.full_name || patient?.name || '').toLowerCase()
              matchPatientName = patientName.includes(this.f.patient_name.toLowerCase())
            }

            if (this.f.patient_phone) {
              const patientPhone = patient?.personal_info?.phone || patient?.phone || patient?.mobile || ''
              matchPatientPhone = patientPhone.includes(this.f.patient_phone)
            }

            if (this.f.doctor_name) {
              const doctorName = (doctor?.personal_info?.full_name || doctor?.full_name || doctor?.name || '').toLowerCase()
              matchDoctorName = doctorName.includes(this.f.doctor_name.toLowerCase())
            }

            return matchPatientName && matchPatientPhone && matchDoctorName
          })
          total = this.items.length
        }

        this.total = total
        this.hasMore = (offset != null)
          ? (offset + this.items.length) < (this.total || 0)
          : (this.page * this.pageSize) < (this.total || 0)

        await this.ensureOptionsLoaded()
      } catch (e) {
        console.error(e)
        this.error = e?.response?.data?.message || e?.message || 'Không tải được dữ liệu'
      } finally { this.loading = false }
    },
    changePageSize () { this.page = 1; this.fetch() },
    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },

    /* ===== combos ===== */
    async ensureOptionsLoaded () {
      if (this.optionsLoaded) return
      try {
        const [dRes, pRes] = await Promise.all([
          DoctorService.list({ limit: 1000 }),
          PatientService.list({ limit: 1000 })
        ])
        const arr = r => (Array.isArray(r?.rows)
          ? r.rows.map(x => x.doc || x.value || x)
          : Array.isArray(r?.data)
            ? r.data
            : Array.isArray(r) ? r : [])
        const dList = arr(dRes); const pList = arr(pRes)
        const key = o => o._id || o.id || o.code || o.username
        const label = o => o?.personal_info?.full_name || o.full_name || o.name || o.display_name || o.code || o.username || key(o)

        this.doctorOptions = dList.map(o => ({ value: key(o), label: label(o) }))
        this.patientOptions = pList.map(o => ({ value: key(o), label: label(o) }))

        this.doctorsMap = {}
        dList.forEach(o => {
          this.doctorsMap[key(o)] = o
        })

        this.patientsMap = {}
        pList.forEach(o => {
          this.patientsMap[key(o)] = o
        })

        this.optionsLoaded = true
      } catch (e) { console.error(e) }
    },

    /* ===== modal ===== */
    openCreate () {
      this.editingId = null
      this.form = this.emptyForm()
      this.availableSlots = []
      this.showModal = true
      this.ensureOptionsLoaded()
    },
    async openEdit (row) {
      const f = this.flattenAppointment(row)
      this.editingId = f._id || f.id

      // Extract date and time from scheduled_date
      let appointmentDate = ''
      let timeSlot = ''
      if (f.scheduled_date) {
        const dt = new Date(f.scheduled_date)
        appointmentDate = dt.toISOString().split('T')[0]
        timeSlot = `${String(dt.getHours()).padStart(2, '0')}:${String(dt.getMinutes()).padStart(2, '0')}`
      }

      this.form = {
        ...this.emptyForm(),
        ...f,
        appointment_date: appointmentDate,
        time_slot: timeSlot
      }

      // Đảm bảo combobox đã có dữ liệu trước khi hiển thị form
      await this.ensureOptionsLoaded()

      // Nếu doctor/patient đang edit chưa có trong options (do filter/limit), thêm tạm để hiển thị
      if (this.form.doctor_id && !this.doctorOptions.some(o => o.value === this.form.doctor_id)) {
        this.doctorOptions = [
          { value: this.form.doctor_id, label: this.form.doctor_id },
          ...this.doctorOptions
        ]
      }
      if (this.form.patient_id && !this.patientOptions.some(o => o.value === this.form.patient_id)) {
        this.patientOptions = [
          { value: this.form.patient_id, label: this.form.patient_id },
          ...this.patientOptions
        ]
      }

      this.showModal = true

      // Load available slots after form is populated
      if (this.form.doctor_id && this.form.appointment_date && this.form.duration) {
        this.loadAvailableSlots()
      }
    },
    close () { if (!this.saving) this.showModal = false },

    async save () {
      if (this.saving) return

      // Validate required fields
      if (!this.form.patient_id || !this.form.doctor_id || !this.form.appointment_date || !this.form.time_slot) {
        this.showInfo('Vui lòng điền đầy đủ thông tin bắt buộc (Bệnh nhân, Bác sĩ, Ngày khám, Khung giờ)')
        return
      }

      this.saving = true
      try {
        // Combine date and time into scheduled_date
        const scheduledDate = `${this.form.appointment_date}T${this.form.time_slot}:00`

        const payload = {
          type: 'appointment',
          patient_id: this.form.patient_id,
          doctor_id: this.form.doctor_id,
          appointment_info: {
            scheduled_date: scheduledDate,
            duration: this.form.duration,
            type: this.form.type,
            priority: this.form.priority
          },
          reason: this.form.reason || undefined,
          status: this.form.status || 'scheduled',
          notes: this.form.notes || undefined,
          reminders: []
        }

        if (this.form._id) payload._id = this.form._id
        if (this.form._rev) payload._rev = this.form._rev

        if (this.editingId) {
          await AppointmentService.update(this.editingId, payload)
        } else {
          // Set created_by to current user when creating new appointment
          console.log('🔍 Current user (me):', this.me)
          if (this.me) {
            const createdBy = this.me.id || this.me._id || this.me.username
            console.log('🔍 Setting created_by to:', createdBy)
            payload.created_by = createdBy
          } else {
            console.warn('⚠️ No current user found, created_by will be undefined')
          }
          await AppointmentService.create(payload)
        }

        this.showModal = false
        await this.fetch()
        this.showInfo('✅ Lưu lịch hẹn thành công!')
      } catch (e) {
        console.error(e)
        this.showInfo(e?.response?.data?.message || e?.message || 'Lưu thất bại')
      } finally { this.saving = false }
    },

    async remove (row) {
      const msg = `Bạn có chắc muốn xóa lịch hẹn <strong>${row._id || row.id}</strong>?`
      this.showConfirm(msg, async () => {
        try {
          const id = row._id || row.id
          await AppointmentService.remove(id)
          await this.fetch()
          this.showInfo('✅ Đã xóa lịch hẹn')
        } catch (e) {
          console.error(e)
          this.showInfo(e?.response?.data?.message || e?.message || 'Xóa thất bại')
        }
      })
    },

    /* ===== Check-in & Cancel ===== */
    async checkIn (row) {
      const prevStatus = row.status || 'scheduled'
      const msg = `
        <div><strong>Check-in bệnh nhân cho lịch hẹn?</strong></div>
        <div style="margin-top:8px;">Bệnh nhân: ${this.displayName(this.patientsMap[row.patient_id])}</div>
        <div>Bác sĩ: ${this.displayName(this.doctorsMap[row.doctor_id])}</div>
        <div>Thời gian: ${this.fmtDateTime(row.scheduled_date)}</div>
      `

      this.showConfirm(msg, async () => {
        this.loading = true
        try {
          const id = row._id || row.id
          const payload = {
            _id: id,
            _rev: row._rev,
            status: 'completed',
            updated_at: new Date().toISOString(),
            notes: (row.notes || '') + `\n[Check-in lúc ${new Date().toLocaleString('vi-VN')}]`
          }

          const res = await AppointmentService.update(id, payload)
          const newRev = res?.rev || res?._rev || res?.data?.rev || res?.data?._rev

          this.showInfo('✅ Check-in thành công!')
          await this.fetch()

          this.showConfirm('Tạo hồ sơ bệnh án từ lịch hẹn vừa check-in?', async () => {
            this.createRecordFromAppointment({ ...row, status: 'completed', _rev: newRev || row._rev })
          }, async () => {
            // Khôi phục trạng thái lịch hẹn như trước khi check-in
            try {
              await AppointmentService.update(id, {
                _id: id,
                _rev: newRev || row._rev,
                status: prevStatus,
                updated_at: new Date().toISOString(),
                notes: (row.notes || '') + `\n[Hoãn check-in lúc ${new Date().toLocaleString('vi-VN')}]`
              })
              this.showInfo('Đã giữ nguyên trạng thái lịch hẹn, không tạo hồ sơ.')
              await this.fetch()
            } catch (revertErr) {
              console.error('Revert check-in failed:', revertErr)
              this.showInfo('Không thể khôi phục trạng thái lịch hẹn sau khi hủy tạo hồ sơ. Vui lòng kiểm tra lại.')
            }
          })
        } catch (e) {
          console.error(e)
          this.showInfo(e?.response?.data?.message || e?.message || 'Check-in thất bại')
        } finally {
          this.loading = false
        }
      })
    },

    canCreateMedicalRecord (row) {
      return (row?.status || '').toLowerCase() === 'completed'
    },

    createRecordFromAppointment (row) {
      if (!this.canCreateMedicalRecord(row)) {
        this.showInfo('Chỉ được tạo hồ sơ bệnh án sau khi lịch hẹn đã check-in.')
        return
      }

      const scheduled = row.scheduled_date || row.appointment_info?.scheduled_date
      const reason = row.reason || row.appointment_info?.reason || ''
      const type = row.type || row.appointment_info?.type || 'consultation'

      // Thông báo trước khi chuyển trang (modal info)
      this.showInfo('Đang mở form Hồ sơ khám với dữ liệu lịch hẹn đã được điền sẵn.')

      this.$router.push({
        path: '/medical-records',
        query: {
          open_create: '1',
          from_checkin: '1',
          appointment_id: row._id || row.id,
          patient_id: row.patient_id,
          doctor_id: row.doctor_id,
          visit_date: this.toDateTimeLocal(scheduled),
          reason,
          visit_type: type,
          status: 'draft'
        }
      })
    },

    async cancelAppointment (row) {
      const reason = prompt(
        `Hủy lịch hẹn?\n\nBệnh nhân: ${this.displayName(this.patientsMap[row.patient_id])}\nBác sĩ: ${this.displayName(this.doctorsMap[row.doctor_id])}\nThời gian: ${this.fmtDateTime(row.scheduled_date)}\n\n━━━━━━━━━━━━━━━━━━━━━\nVui lòng nhập lý do hủy:`,
        'Bệnh nhân yêu cầu hủy'
      )

      if (!reason || !reason.trim()) return

      this.loading = true
      try {
        const id = row._id || row.id
        const payload = {
          _id: id,
          _rev: row._rev,
          status: 'canceled',
          updated_at: new Date().toISOString(),
          notes: (row.notes || '') + `\n[Hủy lúc ${new Date().toLocaleString('vi-VN')}] Lý do: ${reason.trim()}`
        }

        await AppointmentService.update(id, payload)
        this.showInfo('✅ Đã hủy lịch hẹn!')
        await this.fetch()
      } catch (e) {
        console.error(e)
        this.showInfo(e?.response?.data?.message || e?.message || 'Hủy lịch thất bại')
      } finally {
        this.loading = false
      }
    },

    /* ===== modal helpers ===== */
    showConfirm (message, onConfirm, onCancel) {
      this.confirmModal = { visible: true, message, onConfirm, onCancel }
    },
    closeConfirm () {
      const cancelCb = this.confirmModal.onCancel
      this.confirmModal = { visible: false, message: '', onConfirm: null, onCancel: null }
      if (cancelCb) cancelCb()
    },
    async confirmOk () {
      const handler = this.confirmModal.onConfirm
      this.confirmModal = { visible: false, message: '', onConfirm: null, onCancel: null }
      if (handler) await handler()
    },
    showInfo (message) {
      this.infoModal = { visible: true, message }
    },
    closeInfo () {
      this.infoModal = { visible: false, message: '' }
    }
  }
}
</script>

<style scoped>
/* Base Styles */
.appointments-management {
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

.filter-input-group.filter-date {
  flex: 1.2;
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

.appointments-table {
  width: 100%;
  border-collapse: collapse;
}

.appointments-table thead {
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
  border-bottom: 2px solid #e2e8f0;
}

.appointments-table thead th {
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

.col-patient {
  width: 180px;
}

.col-doctor {
  width: 180px;
}

.col-time {
  width: 160px;
}

.col-duration {
  width: 90px;
  text-align: center !important;
}

.col-type {
  width: 130px;
}

.col-priority {
  width: 110px;
  text-align: center !important;
}

.col-status {
  width: 120px;
  text-align: center !important;
}

.col-actions {
  width: 200px;
  text-align: center !important;
}

.appointments-table tbody tr {
  border-bottom: 1px solid #f1f5f9;
  transition: all 0.2s ease;
}

.appointment-row:hover {
  background: #f8fafc;
}

.appointment-row.expanded {
  background: #f0f9ff;
}

.appointments-table tbody td {
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

.cell-duration {
  text-align: center;
  font-weight: 600;
  color: #64748b;
}

.cell-priority, .cell-status {
  text-align: center;
}

.patient-info, .doctor-info, .time-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.patient-info i {
  color: #10b981;
  font-size: 1.1rem;
}

.doctor-info i {
  color: #f59e0b;
  font-size: 1.1rem;
}

.time-info i {
  color: #3b82f6;
  font-size: 1.1rem;
}

.type-text {
  color: #64748b;
  font-weight: 500;
}

.priority-badge {
  padding: 0.4rem 1rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.priority-normal {
  background: #f1f5f9;
  color: #475569;
}

.priority-high {
  background: #fef3c7;
  color: #92400e;
}

.priority-urgent {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge {
  padding: 0.4rem 1rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  text-transform: capitalize;
}

.status-scheduled {
  background: #dbeafe;
  color: #1e40af;
}

.status-approved {
  background: #e0e7ff;
  color: #4338ca;
}

.status-completed {
  background: #d1fae5;
  color: #065f46;
}

.status-canceled {
  background: #fee2e2;
  color: #991b1b;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
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

.checkin-btn {
  background: #d1fae5;
  color: #10b981;
}

.checkin-btn:hover:not(:disabled) {
  background: #10b981;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.record-btn {
  background: #e0e7ff;
  color: #4338ca;
}

.record-btn:hover:not(:disabled) {
  background: #4338ca;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(67, 56, 202, 0.3);
}

.cancel-btn {
  background: #fef3c7;
  color: #f59e0b;
}

.cancel-btn:hover:not(:disabled) {
  background: #f59e0b;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
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

.text-warning {
  color: #f59e0b;
}

/* Simple centered dialogs */
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}
.dialog {
  background: white;
  border-radius: 12px;
  padding: 16px 20px;
  max-width: 420px;
  width: 90%;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
}
.dialog-body {
  font-size: 14px;
  color: #1f2937;
}
.dialog-actions {
  margin-top: 14px;
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}
.dialog-btn {
  padding: 8px 14px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  background: #e5e7eb;
  color: #374151;
}
.dialog-btn.primary {
  background: #3b82f6;
  color: white;
}
.dialog-btn:hover {
  filter: brightness(0.95);
}
</style>
