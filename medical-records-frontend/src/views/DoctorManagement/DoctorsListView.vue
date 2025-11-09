<template>
  <section class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0">Quản lý Bác sĩ</h1>
      <div class="d-flex gap-2 align-items-center">
        <span class="text-muted me-2">Tổng: {{ total }}</span>
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

    <!-- Tools -->
    <div class="mt-3 d-flex justify-content-between align-items-center">
      <div class="input-group" style="max-width: 520px">
        <input v-model.trim="q" class="form-control" placeholder="Tìm theo tên / chuyên khoa / điện thoại / email..." @keyup.enter="search" />
        <button class="btn btn-outline-secondary" @click="search">Tìm</button>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error" class="alert alert-danger my-3">{{ error }}</div>

    <!-- Table -->
    <div class="table-responsive mt-3">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th style="width:56px">#</th>
            <th>Họ tên</th>
            <th>Chuyên khoa</th>
            <th>Phân chuyên khoa</th>
            <th>KN (năm)</th>
            <th>Điện thoại</th>
            <th>Email</th>
            <th>Trạng thái</th>
            <th style="width:200px">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="(d, idx) in items" :key="d._id || d.id || idx">
            <tr>
              <td>{{ idx + 1 + (page - 1) * pageSize }}</td>
              <td>{{ d.personal_info?.full_name || '-' }}</td>
              <td>{{ d.professional_info?.specialty || '-' }}</td>
              <td>{{ joinArr(d.professional_info?.sub_specialties) }}</td>
              <td>{{ d.professional_info?.experience_years ?? '-' }}</td>
              <td>{{ d.personal_info?.phone || '-' }}</td>
              <td>{{ d.personal_info?.email || '-' }}</td>
              <td>
                <span :class="['badge', d.status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary']">
                  {{ d.status || '-' }}
                </span>
              </td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary" @click="toggleRow(d)">{{ isExpanded(d) ? 'Ẩn' : 'Xem' }}</button>
                  <button class="btn btn-sm btn-outline-primary" @click="openEdit(d)">Sửa</button>
                  <button class="btn btn-sm btn-outline-danger" @click="remove(d)" :disabled="loading">Xóa</button>
                </div>
              </td>
            </tr>

            <!-- Row details -->
            <tr v-if="isExpanded(d)" class="row-detail">
              <td :colspan="9">
                <div class="detail-sections">
                  <div class="detail-title">Thông tin cá nhân</div>
                  <div class="detail-grid">
                    <div class="detail-item">
                      <span class="detail-label">Họ tên:</span> <span class="detail-value">{{ d.personal_info?.full_name || '-' }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Giới tính:</span> <span class="detail-value">{{ renderGender(d.personal_info?.gender) }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Ngày sinh:</span> <span class="detail-value">{{ fmtDate(d.personal_info?.birth_date) }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Điện thoại:</span> <span class="detail-value">{{ d.personal_info?.phone || '-' }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Email:</span> <span class="detail-value">{{ d.personal_info?.email || '-' }}</span>
                    </div>
                  </div>

                  <div class="detail-title">Nghề nghiệp</div>
                  <div class="detail-grid">
                    <div class="detail-item">
                      <span class="detail-label">Số giấy phép:</span> <span class="detail-value">{{ d.professional_info?.license_number || '-' }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Chuyên khoa:</span> <span class="detail-value">{{ d.professional_info?.specialty || '-' }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Phân chuyên khoa:</span> <span class="detail-value">{{ joinArr(d.professional_info?.sub_specialties) }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Kinh nghiệm:</span> <span class="detail-value">{{ d.professional_info?.experience_years ?? '-' }} năm</span>
                    </div>
                  </div>

                  <div class="detail-title">Học vấn</div>
                  <div class="detail-grid">
                    <div class="detail-item" v-for="(e, ei) in (d.professional_info?.education || [])" :key="ei">
                      <span class="detail-label">•</span>
                      <span class="detail-value">
                        {{ e.degree || '-' }} — {{ e.university || e.institution || '-' }}
                        <template v-if="e.year"> ({{ e.year }})</template>
                      </span>
                    </div>
                  </div>

                  <div class="detail-title">Chứng chỉ</div>
                  <div class="detail-grid">
                    <div class="detail-item" v-for="(c, ci) in (d.professional_info?.certifications || [])" :key="ci">
                      <span class="detail-label">•</span>
                      <span class="detail-value">
                        {{ c.name || '-' }} — {{ c.issuer || '-' }}
                        <template v-if="c.valid_until"> (Hết hạn: {{ fmtDate(c.valid_until) }})</template>
                      </span>
                    </div>
                  </div>

                  <div class="detail-title">Lịch làm việc</div>
                  <div class="detail-grid">
                    <div class="detail-item">
                      <span class="detail-label">Ngày làm:</span>
                      <span class="detail-value">{{ renderDays(d.schedule?.working_days) }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Giờ làm:</span>
                      <span class="detail-value">{{ renderHours(d.schedule?.working_hours) }}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Giờ nghỉ:</span>
                      <span class="detail-value">{{ renderHours(d.schedule?.break_time) }}</span>
                    </div>
                  </div>

                  <div class="detail-title">Khác</div>
                  <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">ID:</span> <span class="detail-value">{{ d._id || d.id || '-' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Rev:</span> <span class="detail-value">{{ d._rev || '-' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Tạo lúc:</span> <span class="detail-value">{{ fmtDateTime(d.created_at) }}</span></div>
                    <div class="detail-item"><span class="detail-label">Cập nhật:</span> <span class="detail-value">{{ fmtDateTime(d.updated_at) }}</span></div>
                  </div>
                </div>
              </td>
            </tr>
          </template>

          <tr v-if="!items.length">
            <td colspan="9" class="text-center text-muted">Không có dữ liệu</td>
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

    <!-- Modal Thêm/Sửa -->
    <div v-if="showModal" class="modal-backdrop" @mousedown.self="close">
      <div class="modal-card">
        <h2 class="h5 mb-3">{{ editingId ? 'Sửa thông tin bác sĩ' : 'Thêm bác sĩ' }}</h2>

        <form @submit.prevent="save">
          <!-- Personal -->
          <div class="section-title">Thông tin cá nhân</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Họ tên <span class="text-danger">*</span></label>
              <input v-model.trim="form.full_name" type="text" class="form-control" required />
            </div>
            <div class="col-md-3">
              <label class="form-label">Ngày sinh</label>
              <input v-model="form.birth_date" type="date" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Giới tính</label>
              <select v-model="form.gender" class="form-select">
                <option value="">-- chọn --</option>
                <option value="male">Nam</option>
                <option value="female">Nữ</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Điện thoại</label>
              <input v-model.trim="form.phone" class="form-control" maxlength="10" pattern="\d{10}" @input="onPhoneInput" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input v-model.trim="form.email" type="email" class="form-control" />
            </div>
          </div>

          <!-- Professional -->
          <div class="section-title">Nghề nghiệp</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Số giấy phép</label>
              <input v-model.trim="form.license_number" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Chuyên khoa</label>
              <input v-model.trim="form.specialty" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Phân chuyên khoa (phân tách dấu phẩy)</label>
              <input v-model.trim="form.sub_specialties" class="form-control" placeholder="vd: interventional_cardiology, ..." />
            </div>
            <div class="col-md-3">
              <label class="form-label">Kinh nghiệm (năm)</label>
              <input v-model.number="form.experience_years" type="number" min="0" class="form-control" />
            </div>

            <div class="col-12">
              <label class="form-label">Học vấn (mỗi dòng: degree;institution;year)</label>
              <textarea v-model="form.education_text" rows="3" class="form-control" placeholder="Doctor of Medicine;Đại học Y Dược TP.HCM;2005&#10;Specialist Level 2;Bệnh viện Chợ Rẫy;2010"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Chứng chỉ (mỗi dòng: name;issuer;valid_until YYYY-MM-DD)</label>
              <textarea v-model="form.certifications_text" rows="3" class="form-control" placeholder="Cardiac Intervention Certification;Vietnam Heart Association;2025-12-31"></textarea>
            </div>
          </div>

          <!-- Schedule -->
          <div class="section-title">Lịch làm việc (Cố định)</div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Ngày làm việc</label>
              <div class="alert alert-info py-2">
                <strong>Cố định:</strong> Thứ 2, Thứ 3, Thứ 4, Thứ 5, Thứ 6
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Giờ bắt đầu</label>
              <input type="text" class="form-control" value="08:00" readonly />
              <small class="text-muted">Cố định: 08:00 (24 giờ)</small>
            </div>
            <div class="col-md-3">
              <label class="form-label">Giờ kết thúc</label>
              <input type="text" class="form-control" value="17:00" readonly />
              <small class="text-muted">Cố định: 17:00 (24 giờ)</small>
            </div>
            <div class="col-md-3">
              <label class="form-label">Nghỉ trưa từ</label>
              <input type="text" class="form-control" value="12:00" readonly />
              <small class="text-muted">Cố định: 12:00 (24 giờ)</small>
            </div>
            <div class="col-md-3">
              <label class="form-label">Nghỉ trưa đến</label>
              <input type="text" class="form-control" value="13:00" readonly />
              <small class="text-muted">Cố định: 13:00 (24 giờ)</small>
            </div>
          </div>

          <!-- Status -->
          <div class="section-title">Khác</div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Trạng thái</label>
              <select v-model="form.status" class="form-select">
                <option value="active">Hoạt động</option>
                <option value="inactive">Không hoạt động</option>
              </select>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary" @click="close">Huỷ</button>
            <button class="btn btn-primary" type="submit" :disabled="saving">{{ saving ? 'Đang lưu…' : 'Lưu' }}</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script>
import DoctorService from '@/api/doctorService'

const dayLabels = {
  monday: 'Thứ 2', tuesday: 'Thứ 3', wednesday: 'Thứ 4', thursday: 'Thứ 5', friday: 'Thứ 6', saturday: 'Thứ 7', sunday: 'Chủ nhật'
}

export default {
  name: 'DoctorsListView',
  data () {
    return {
      items: [],
      allItems: [],
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
      // day options
      dayOptions: [
        { value: 'monday', label: 'Thứ 2' },
        { value: 'tuesday', label: 'Thứ 3' },
        { value: 'wednesday', label: 'Thứ 4' },
        { value: 'thursday', label: 'Thứ 5' },
        { value: 'friday', label: 'Thứ 6' },
        { value: 'saturday', label: 'Thứ 7' },
        { value: 'sunday', label: 'Chủ nhật' }
      ]
    }
  },
  created () { this.fetch() },
  methods: {
    onPhoneInput (e) {
      // Chỉ cho phép nhập số và tối đa 10 ký tự
      const val = e.target.value.replace(/\D/g, '').slice(0, 10)
      this.form.phone = val
    },
    emptyForm () {
      return {
        _id: null,
        _rev: null,
        // personal
        full_name: '',
        birth_date: '',
        gender: '',
        phone: '',
        email: '',
        // professional
        license_number: '',
        specialty: '',
        sub_specialties: '',
        experience_years: null,
        education_text: '',
        certifications_text: '',
        // schedule
        working_days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], // Cố định thứ 2-6
        working_start: '08:00', // Cố định 8:00
        working_end: '17:00', // Cố định 17:00
        break_start: '12:00', // Cố định nghỉ trưa
        break_end: '13:00', // Cố định nghỉ trưa
        // status
        status: 'active'
      }
    },

    // --- UI helpers ---
    joinArr (a) { return Array.isArray(a) ? a.join(', ') : (a || '-') },
    renderGender (g) {
      const s = String(g ?? '').toLowerCase()
      if (['m', 'male', 'nam', '1', 'true'].includes(s)) return 'Nam'
      if (['f', 'female', 'nữ', '0', 'false'].includes(s)) return 'Nữ'
      return s || '-'
    },
    renderDays (days) {
      if (!Array.isArray(days) || !days.length) return '-'
      return days.map(d => dayLabels[d] || d).join(', ')
    },
    renderHours (obj) {
      if (!obj || (!obj.start && !obj.end)) return '-'
      if (obj.start === '12:00' && obj.end === '13:00') return '12g đến 13g'
      return `${obj.start || '--:--'} - ${obj.end || '--:--'}`
    },
    fmtDate (v) { if (!v) return '-'; try { return new Date(v).toLocaleDateString() } catch { return v } },
    fmtDateTime (v) { if (!v) return '-'; try { return new Date(v).toLocaleString() } catch { return v } },

    rowId (row) { return row._id || row.id || row.personal_info?.full_name },
    isExpanded (row) { return !!this.expanded[this.rowId(row)] },
    toggleRow (row) {
      const id = this.rowId(row)
      this.expanded = { ...this.expanded, [id]: !this.expanded[id] }
    },

    // --- Data ---
    async fetch () {
      this.loading = true
      this.error = ''
      try {
        // Always fetch all data for robust search
        const res = await DoctorService.list({})
        let items = []
        if (res && Array.isArray(res.rows)) {
          items = (res.rows || []).map(r => r.doc || r.value || r)
        } else if (res && res.data && Array.isArray(res.data)) {
          items = res.data
        } else if (Array.isArray(res)) { items = res }
        // đảm bảo có cấu trúc tối thiểu và ràng buộc giờ nghỉ trưa 12:00-13:00
        this.allItems = items.map(d => {
          const schedule = d.schedule || {}
          schedule.break_time = { start: '12:00', end: '13:00' }
          return {
            personal_info: d.personal_info || {},
            professional_info: d.professional_info || {},
            schedule,
            status: d.status || 'active',
            _id: d._id || d.id,
            _rev: d._rev,
            created_at: d.created_at,
            updated_at: d.updated_at
          }
        })
        this.items = [...this.allItems]
        this.total = this.items.length
        this.hasMore = false
      } catch (e) {
        console.error(e)
        this.error = e?.response?.data?.message || e?.message || 'Không tải được dữ liệu'
      } finally {
        this.loading = false
      }
    },
    async search () {
      this.page = 1
      if (!this.allItems.length) await this.fetch()
      const kw = (this.q || '').trim().toLowerCase()
      if (!kw) {
        this.items = [...this.allItems]
        this.total = this.items.length
        return
      }
      this.items = this.allItems.filter(d => {
        const pi = d.personal_info || {}
        const pro = d.professional_info || {}
        let subSpecialties = ''
        if (Array.isArray(pro.sub_specialties)) {
          subSpecialties = pro.sub_specialties.join(', ')
        } else if (typeof pro.sub_specialties === 'string') {
          subSpecialties = pro.sub_specialties
        }
        const fields = [
          pi.full_name,
          pro.specialty,
          subSpecialties,
          pi.phone,
          pi.email
        ].map(f => (f || '').toString().toLowerCase())
        return fields.some(field => field.includes(kw))
      })
      this.total = this.items.length
    },
    reload () { this.fetch() },
    changePageSize () { this.page = 1; this.fetch() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },

    // --- CRUD ---
    openCreate () {
      this.editingId = null
      this.form = this.emptyForm()
      // Force set giá trị cố định
      this.form.working_days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
      this.form.working_start = '08:00'
      this.form.working_end = '17:00'
      this.form.break_start = '12:00'
      this.form.break_end = '13:00'
      this.showModal = true
    },
    openEdit (row) {
      this.editingId = row._id || row.id || row.personal_info?.full_name
      const pi = row.personal_info || {}
      const pro = row.professional_info || {}

      // education -> text lines
      const eduLines = Array.isArray(pro.education)
        ? pro.education.map(e => {
          const inst = e.university || e.institution || ''
          return [e.degree || '', inst, e.year || ''].filter(x => x !== null && x !== undefined).join(';')
        })
        : []
      // certs -> text lines
      const certLines = Array.isArray(pro.certifications)
        ? pro.certifications.map(c =>
          [c.name || '', c.issuer || '', c.valid_until || ''].join(';')
        )
        : []

      this.form = {
        _id: row._id,
        _rev: row._rev,
        // personal
        full_name: pi.full_name || '',
        birth_date: pi.birth_date || '',
        gender: pi.gender || '',
        phone: pi.phone || '',
        email: pi.email || '',
        // professional
        license_number: pro.license_number || '',
        specialty: pro.specialty || '',
        sub_specialties: Array.isArray(pro.sub_specialties) ? pro.sub_specialties.join(', ') : (pro.sub_specialties || ''),
        experience_years: pro.experience_years ?? null,
        education_text: eduLines.join('\n'),
        certifications_text: certLines.join('\n'),
        // schedule - Force set giá trị cố định
        working_days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        working_start: '08:00',
        working_end: '17:00',
        break_start: '12:00', // Luôn cố định nghỉ trưa
        break_end: '13:00', // Luôn cố định nghỉ trưa
        // status
        status: row.status || 'active'
      }
      this.showModal = true
    },
    close () { if (!this.saving) this.showModal = false },

    parseEducation (text) {
      // mỗi dòng: degree;institution;year
      return (text || '')
        .split('\n')
        .map(s => s.trim())
        .filter(Boolean)
        .map(line => {
          const [degree, institution, year] = line.split(';').map(x => (x || '').trim())
          const obj = { degree: degree || undefined }
          if (institution) obj.institution = institution
          // chấp nhận cả "university" nếu trước đó đã dùng trường này
          if (institution && !obj.university) obj.university = institution
          if (year) obj.year = isNaN(+year) ? year : +year
          return obj
        })
    },
    parseCerts (text) {
      // mỗi dòng: name;issuer;valid_until
      return (text || '')
        .split('\n')
        .map(s => s.trim())
        .filter(Boolean)
        .map(line => {
          const [name, issuer, valid] = line.split(';').map(x => (x || '').trim())
          const obj = { name: name || undefined, issuer: issuer || undefined }
          if (valid) obj.valid_until = valid
          return obj
        })
    },

    async save () {
      if (this.saving) return
      this.saving = true
      try {
        // Ràng buộc giờ nghỉ trưa 12:00-13:00 cho tất cả bác sĩ
        const breakStart = '12:00'
        const breakEnd = '13:00'
        const payload = {
          type: 'doctor',
          personal_info: {
            full_name: this.form.full_name,
            birth_date: this.form.birth_date || undefined,
            gender: this.form.gender || undefined,
            phone: this.form.phone || undefined,
            email: this.form.email || undefined
          },
          professional_info: {
            license_number: this.form.license_number || undefined,
            specialty: this.form.specialty || undefined,
            sub_specialties: this.form.sub_specialties
              ? this.form.sub_specialties.split(',').map(s => s.trim()).filter(Boolean)
              : [],
            experience_years: (this.form.experience_years ?? '') === '' ? undefined : Number(this.form.experience_years),
            education: this.parseEducation(this.form.education_text),
            certifications: this.parseCerts(this.form.certifications_text)
          },
          schedule: {
            working_days: Array.isArray(this.form.working_days) ? this.form.working_days : [],
            working_hours: {
              start: this.form.working_start || undefined,
              end: this.form.working_end || undefined
            },
            break_time: {
              start: breakStart,
              end: breakEnd
            }
          },
          status: this.form.status
        }
        if (this.form._id) payload._id = this.form._id
        if (this.form._rev) payload._rev = this.form._rev

        if (this.editingId) {
          await DoctorService.update(this.editingId, payload)
        } else {
          await DoctorService.create(payload)
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
      if (!confirm(`Xóa bác sĩ "${row.personal_info?.full_name || row._id}"?`)) return
      try {
        const id = row._id || row.id
        // Always fetch latest _rev before delete
        const fresh = await DoctorService.get(id)
        const rev = fresh?._rev
        if (!rev) throw new Error('Không lấy được revision của document')
        const result = await DoctorService.remove(id, rev)
        if (result && (result.ok === true || result.status === 'success')) {
          await this.fetch()
        } else {
          throw new Error(result?.message || 'Xóa thất bại')
        }
      } catch (e) {
        // Retry if conflict
        if (e?.message?.includes('conflict') || e?.message?.includes('409')) {
          try {
            const newer = await DoctorService.get(row._id || row.id)
            const newerRev = newer?._rev
            if (newerRev) {
              const retryResult = await DoctorService.remove(row._id || row.id, newerRev)
              if (retryResult && (retryResult.ok === true || retryResult.status === 'success')) {
                await this.fetch()
                return
              }
            }
            alert('Xóa thất bại: Document đã được thay đổi, vui lòng tải lại trang')
          } catch (retryError) {
            alert('Xóa thất bại sau khi thử lại')
          }
        } else {
          alert(e?.response?.data?.message || e?.message || 'Xóa thất bại')
        }
      }
    }
  }
}
</script>

<style scoped>
/* Remove red border for all timepicker input states */
.vue__time-picker input,
.vue__time-picker input:focus,
.vue__time-picker input:active,
.vue__time-picker input:invalid {
  border: 1px solid #ced4da !important;
  box-shadow: none !important;
  outline: none !important;
}
/* Ensure VueTimepicker is always clickable and editable */
.vue__time-picker {
  cursor: pointer !important;
  pointer-events: auto !important;
  user-select: auto !important;
}
.vue__time-picker input {
  cursor: text !important;
  pointer-events: auto !important;
  user-select: auto !important;
  background-color: #fff !important;
}
/* Remove red border for VueTimepicker error/invalid state */
.vue__time-picker,
.vue__time-picker.vue__time-picker--error,
.vue__time-picker:invalid {
  border-color: #ced4da !important;
  box-shadow: none !important;
}
/* Icon wrap for timepicker */
.input-icon-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.input-icon-wrap .icon-clock {
  position: absolute;
  left: 10px;
  z-index: 2;
  color: #6c757d;
}
.input-icon-wrap .vue__time-picker {
  padding-left: 32px !important;
}
/* Fix style for VueTimepicker to look like Bootstrap input, remove red border */
.vue__time-picker {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #ced4da !important;
  border-radius: 0.375rem;
  padding: 0.375rem 0.75rem;
  font-size: 1rem;
  line-height: 1.5;
  color: #212529;
  background-color: #fff;
  transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
  outline: none !important;
}
.vue__time-picker:focus {
  border-color: #86b7fe !important;
  box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
}
/* Fix style for VueTimepicker to look like Bootstrap input */
.vue__time-picker {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #ced4da;
  border-radius: 0.375rem;
  padding: 0.375rem 0.75rem;
  font-size: 1rem;
  line-height: 1.5;
  color: #212529;
  background-color: #fff;
  transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
}
/* Time input styling */
.time-input {
  border: 1px solid #ced4da !important;
  box-shadow: none !important;
}
.time-input:focus {
  border-color: #86b7fe !important;
  box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25) !important;
}
.time-input:invalid {
  border: 1px solid #ced4da !important;
  box-shadow: none !important;
}
/* table */
:deep(table.table) th, :deep(table.table) td { vertical-align: middle; }

/* row detail style (giống mẫu bệnh nhân) */
.row-detail td { background: #fff; }
.detail-sections { border-top: 1px solid #e5e7eb; padding: 12px 10px 6px; }
.detail-title { font-weight: 700; color: #111827; margin: 8px 0; }
.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 8px 16px;
}
.detail-item { line-height: 1.6; }
.detail-label { font-weight: 700; }
.detail-value { margin-left: 6px; }

/* Modal */
.modal-backdrop{ position: fixed; inset: 0; background: rgba(0,0,0,.45); display: grid; place-items: center; z-index: 1050; }
.modal-card{ width: min(980px, 96vw); background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 20px 50px rgba(0,0,0,.25); max-height: 92vh; overflow: auto; }
.section-title{ font-weight: 600; margin: 14px 0 8px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; }
</style>
