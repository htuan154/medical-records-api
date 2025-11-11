<template>
  <section class="container py-4">
    <!-- Header + Tools -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 mb-0"></h2>
      <div class="d-flex gap-2">
        <select v-model.number="pageSize" class="form-select" style="width:120px" @change="changePageSize" :disabled="loading">
          <option :value="10">10 / trang</option>
          <option :value="25">25 / trang</option>
          <option :value="50">50 / trang</option>
          <option :value="100">100 / trang</option>
        </select>
        <button class="btn btn-outline-secondary" @click="reload" :disabled="loading">Tải lại</button>
        <button class="btn btn-primary" @click="openCreate" :disabled="loading">+ Thêm mới</button>
      </div>
    </div>

    <!-- Filters -->
    <div class="row g-2 mb-3">
      <div class="col-md-2">
        <input v-model.trim="f.patient_name" class="form-control" placeholder="Tên bệnh nhân" @keyup.enter="search" />
      </div>
      <div class="col-md-2">
        <input v-model.trim="f.patient_phone" class="form-control" placeholder="SĐT bệnh nhân" @keyup.enter="search" />
      </div>
      <div class="col-md-2">
        <input v-model.trim="f.doctor_name" class="form-control" placeholder="Tên bác sĩ" @keyup.enter="search" />
      </div>
      <div class="col-md-2">
        <select v-model="f.status" class="form-select">
          <option value="">-- Trạng thái --</option>
          <option value="scheduled">Đã đặt</option>
          <option value="approved">Đã duyệt</option>
          <option value="completed">Hoàn thành</option>
          <option value="canceled">Đã hủy</option>
          <option value="pending">Chờ xử lý</option>
        </select>
      </div>
      <div class="col-md-2"><input v-model="f.from" type="datetime-local" class="form-control" /></div>
      <div class="col-md-2 d-flex gap-2">
        <input v-model="f.to" type="datetime-local" class="form-control" />
        <button class="btn btn-outline-secondary" @click="search">Tìm</button>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <!-- Table -->
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th style="width:56px">#</th>
            <th>Mã</th>
            <th>Bệnh nhân</th>
            <th>Bác sĩ</th>
            <th>Thời gian</th>
            <th>Thời lượng</th>
            <th>Loại khám</th>
            <th>Ưu tiên</th>
            <th>Trạng thái</th>
            <th style="width:180px">Hành động</th>
          </tr>
        </thead>
        <tbody v-for="(a, idx) in items" :key="rowKey(a, idx)">
          <tr>
            <td>{{ idx + 1 + (page - 1) * pageSize }}</td>
            <td>{{ a._id || a.id }}</td>
            <td>{{ displayName(patientsMap[a.patient_id]) || a.patient_id }}</td>
            <td>{{ displayName(doctorsMap[a.doctor_id]) || a.doctor_id }}</td>
            <td>{{ fmtDateTime(a.scheduled_date) }}</td>
            <td>{{ a.duration }} phút</td>
            <td>{{ typeLabels[a.type] || a.type }}</td>
            <td>{{ priorityLabels[a.priority] || a.priority }}</td>
            <td><span :class="['badge', statusClass(a.status)]">{{ statusLabels[a.status] || a.status }}</span></td>
            <td>
              <div class="btn-group">
                <button class="btn btn-sm btn-outline-secondary" @click="toggleRow(a)">{{ isExpanded(a) ? 'Ẩn' : 'Xem' }}</button>
                <button v-if="['scheduled', 'approved'].includes(a.status)" class="btn btn-sm btn-success" @click="checkIn(a)" :disabled="loading" title="Check-in"><i class="bi bi-check-circle"></i></button>
                <button v-if="a.status !== 'completed' && a.status !== 'canceled'" class="btn btn-sm btn-warning" @click="cancelAppointment(a)" :disabled="loading" title="Hủy"><i class="bi bi-x-circle"></i></button>
                <button class="btn btn-sm btn-outline-primary" @click="openEdit(a)">Sửa</button>
                <button class="btn btn-sm btn-outline-danger" @click="remove(a)" :disabled="loading">Xóa</button>
              </div>
            </td>
          </tr>

          <!-- Details -->
          <tr v-if="isExpanded(a)">
            <td :colspan="10">
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
        </tbody>

        <tbody v-if="!items.length">
          <tr>
            <td colspan="10" class="text-center text-muted">Không có dữ liệu</td>
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

    <!-- MODAL -->
    <div v-if="showModal" class="modal-backdrop" @mousedown.self="close">
      <div class="modal-card">
        <h3 class="h6 mb-3">{{ editingId ? 'Sửa lịch hẹn' : 'Thêm lịch hẹn' }}</h3>

        <form @submit.prevent="save">
          <div class="section-title">Liên kết</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Bệnh nhân <span class="text-danger">*</span></label>
              <select v-model="form.patient_id" class="form-select" required>
                <option value="">-- Chọn bệnh nhân --</option>
                <option v-for="p in patientOptions" :key="p.value" :value="p.value">{{ p.label }}</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Bác sĩ <span class="text-danger">*</span></label>
              <select v-model="form.doctor_id" class="form-select" required @change="loadAvailableSlots">
                <option value="">-- Chọn bác sĩ --</option>
                <option v-for="d in doctorOptions" :key="d.value" :value="d.value">{{ d.label }}</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Trạng thái</label>
              <select v-model="form.status" class="form-select">
                <option value="scheduled">Đã đặt</option>
                <option value="approved">Đã duyệt</option>
                <option value="completed">Hoàn thành</option>
                <option value="canceled">Đã hủy</option>
                <option value="pending">Chờ xử lý</option>
              </select>
            </div>
          </div>

          <div class="section-title">Thông tin lịch hẹn</div>
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Ngày khám <span class="text-danger">*</span></label>
              <input v-model="form.appointment_date" type="date" class="form-control" required @change="loadAvailableSlots" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Thời lượng (phút) <span class="text-danger">*</span></label>
              <select v-model.number="form.duration" class="form-select" required @change="loadAvailableSlots">
                <option :value="15">15 phút</option>
                <option :value="30">30 phút</option>
                <option :value="45">45 phút</option>
                <option :value="60">60 phút</option>
                <option :value="90">90 phút</option>
                <option :value="120">120 phút</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Khung giờ khám <span class="text-danger">*</span></label>
              <select v-model="form.time_slot" class="form-select" required :disabled="!availableSlots.length || !form.doctor_id || !form.appointment_date">
                <option value="">-- Chọn giờ --</option>
                <option v-for="slot in availableSlots" :key="slot.value" :value="slot.value">
                  {{ slot.label }}
                </option>
              </select>
              <small v-if="loadingSlots" class="text-info d-block mt-1">Đang tải khung giờ...</small>
              <small v-else-if="availableSlots.length === 0 && form.appointment_date && form.doctor_id" class="text-warning d-block mt-1">
                Không có khung giờ trống
              </small>
            </div>
            <div class="col-md-3">
              <label class="form-label">Loại khám <span class="text-danger">*</span></label>
              <select v-model="form.type" class="form-select" required>
                <option value="consultation">Tư vấn</option>
                <option value="follow_up">Tái khám</option>
                <option value="checkup">Khám sức khỏe</option>
                <option value="emergency">Cấp cứu</option>
                <option value="procedure">Thủ thuật</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Ưu tiên</label>
              <select v-model="form.priority" class="form-select">
                <option value="normal">Bình thường</option>
                <option value="high">Cao</option>
                <option value="urgent">Khẩn cấp</option>
              </select>
            </div>

            <div class="col-md-9">
              <label class="form-label">Lý do khám</label>
              <input v-model.trim="form.reason" class="form-control" placeholder="Mô tả lý do đến khám..." />
            </div>

            <div class="col-12">
              <label class="form-label">Ghi chú</label>
              <textarea v-model.trim="form.notes" class="form-control" rows="2" placeholder="Ghi chú thêm..."></textarea>
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
import { mapGetters } from 'vuex'
import AppointmentService from '@/api/appointmentService'
import DoctorService from '@/api/doctorService'
import PatientService from '@/api/patientService'

export default {
  name: 'AppointmentsListView',
  computed: {
    ...mapGetters(['me'])
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
      }
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
        ? 'bg-info-subtle text-info'
        : s === 'approved'
          ? 'bg-primary-subtle text-primary'
          : s === 'completed'
            ? 'bg-success-subtle text-success'
            : s === 'canceled'
              ? 'bg-danger-subtle text-danger'
              : 'bg-secondary-subtle text-secondary'
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
        this.availableSlots = allSlots.filter(slot => {
          // Check if slot conflicts with any busy range
          return !busyRanges.some(busy => {
            // Slot is busy if it overlaps with existing appointment
            return !(slot.endMinutes <= busy.startMinutes || slot.startMinutes >= busy.endMinutes)
          })
        })
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
    openEdit (row) {
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

      this.showModal = true
      this.ensureOptionsLoaded()

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
        alert('Vui lòng điền đầy đủ thông tin bắt buộc (Bệnh nhân, Bác sĩ, Ngày khám, Khung giờ)')
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
        alert('✅ Lưu lịch hẹn thành công!')
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Lưu thất bại')
      } finally { this.saving = false }
    },

    async remove (row) {
      if (!confirm(`Xóa lịch hẹn "${row._id || row.id}"?`)) return
      try {
        const id = row._id || row.id
        await AppointmentService.remove(id)
        await this.fetch()
        alert('✅ Đã xóa lịch hẹn')
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Xóa thất bại')
      }
    },

    /* ===== Check-in & Cancel ===== */
    async checkIn (row) {
      if (!confirm(`Check-in bệnh nhân cho lịch hẹn?\n\nBệnh nhân: ${this.displayName(this.patientsMap[row.patient_id])}\nBác sĩ: ${this.displayName(this.doctorsMap[row.doctor_id])}\nThời gian: ${this.fmtDateTime(row.scheduled_date)}`)) {
        return
      }

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

        await AppointmentService.update(id, payload)
        alert('✅ Check-in thành công!')
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Check-in thất bại')
      } finally {
        this.loading = false
      }
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
        alert('✅ Đã hủy lịch hẹn!')
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Hủy lịch thất bại')
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
</style>
