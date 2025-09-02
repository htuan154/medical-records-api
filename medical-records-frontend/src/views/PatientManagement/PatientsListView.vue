<template>
  <section class="container py-4">
    <div class="d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0">Quản lý Bệnh nhân</h1>
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

    <div class="mt-3">
      <div class="input-group mb-3" style="max-width:520px">
        <input
          v-model.trim="q"
          class="form-control"
          placeholder="Tìm theo tên / điện thoại / mã..."
          @keyup.enter="search"
        />
        <button class="btn btn-outline-secondary" @click="search">Tìm</button>
      </div>

      <div v-if="error" class="alert alert-danger">{{ error }}</div>
      <div v-if="loading" class="text-muted">Đang tải danh sách…</div>

      <template v-else>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:56px">#</th>
                <th>Mã</th>
                <th>Họ tên</th>
                <th>Giới tính</th>
                <th>Ngày sinh</th>
                <th>SĐT</th>
                <th>Email</th>
                <th>Thành phố</th>
                <th>Trạng thái</th>
                <th style="width:180px">Hành động</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(p, idx) in items" :key="p._id || p.id || idx">
                <tr>
                  <td>{{ idx + 1 + (page - 1) * pageSize }}</td>
                  <td>{{ p.code || '-' }}</td>
                  <td>{{ p.fullName || '-' }}</td>
                  <td>{{ renderGender(p.gender) }}</td>
                  <td>{{ fmtDate(p.birthDate) }}</td>
                  <td>{{ p.phone || '-' }}</td>
                  <td>{{ p.email || '-' }}</td>
                  <td>{{ p.city || '-' }}</td>
                  <td>
                    <span :class="['badge', p.status === 'active' ? 'bg-success' : 'bg-secondary']">
                      {{ p.status || '-' }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group">
                      <button class="btn btn-sm btn-outline-info" @click="toggle(p)">
                        {{ expandedId === (p._id || p.id) ? 'Ẩn' : 'Xem' }}
                      </button>
                      <button class="btn btn-sm btn-outline-primary" @click="openEdit(p)">
                        Sửa
                      </button>
                      <button class="btn btn-sm btn-outline-danger" @click="remove(p)" :disabled="loading">
                        Xóa
                      </button>
                    </div>
                  </td>
                </tr>

                <!-- Hàng chi tiết -->
                <tr v-if="expandedId === (p._id || p.id)">
                  <td :colspan="10">
                    <div class="detail-grid">
                      <div>
                        <h6 class="text-muted">Thông tin cá nhân</h6>
                        <div><strong>CMND/CCCD:</strong> {{ p.idNumber || '-' }}</div>
                        <div><strong>Giới tính:</strong> {{ renderGender(p.gender) }}</div>
                        <div><strong>Ngày sinh:</strong> {{ fmtDate(p.birthDate) }}</div>
                        <div><strong>Email:</strong> {{ p.email || '-' }}</div>
                        <div><strong>Điện thoại:</strong> {{ p.phone || '-' }}</div>
                      </div>
                      <div>
                        <h6 class="text-muted">Địa chỉ</h6>
                        <div>{{ p.street || '-' }}</div>
                        <div>{{ p.ward || '-' }}</div>
                        <div>{{ p.district || '-' }}</div>
                        <div>{{ p.city || '-' }} {{ p.postalCode ? '(' + p.postalCode + ')' : '' }}</div>
                      </div>
                      <div>
                        <h6 class="text-muted">Y tế</h6>
                        <div><strong>Nhóm máu:</strong> {{ p.bloodType || '-' }}</div>
                        <div><strong>Dị ứng:</strong> {{ p.allergies || '-' }}</div>
                        <div><strong>Bệnh mạn:</strong> {{ p.chronicConditions || '-' }}</div>
                        <div><strong>Bảo hiểm:</strong> {{ p.insuranceProvider || '-' }}</div>
                        <div><strong>Số thẻ:</strong> {{ p.insurancePolicy || '-' }}</div>
                        <div><strong>Hết hạn:</strong> {{ fmtDate(p.insuranceValidUntil) }}</div>
                      </div>
                      <div>
                        <h6 class="text-muted">Liên hệ khẩn cấp</h6>
                        <div><strong>Người liên hệ:</strong> {{ p.emergencyName || '-' }}</div>
                        <div><strong>Quan hệ:</strong> {{ p.emergencyRelationship || '-' }}</div>
                        <div><strong>SĐT:</strong> {{ p.emergencyPhone || '-' }}</div>
                      </div>
                      <div>
                        <h6 class="text-muted">Khác</h6>
                        <div><strong>Tạo lúc:</strong> {{ fmtDateTime(p.createdAt) }}</div>
                        <div><strong>Cập nhật:</strong> {{ fmtDateTime(p.updatedAt) }}</div>
                        <div><strong>Người tạo:</strong> {{ p.createdBy || '-' }}</div>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>

              <tr v-if="!items.length">
                <td colspan="10" class="text-center text-muted">Không có dữ liệu</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center">
          <div>Trang {{ page }} / {{ Math.ceil(total / pageSize) || 1 }}</div>
          <div class="btn-group">
            <button class="btn btn-outline-secondary" @click="prev" :disabled="page <= 1">‹ Trước</button>
            <button class="btn btn-outline-secondary" @click="next" :disabled="!hasMore">Sau ›</button>
          </div>
        </div>
      </template>
    </div>

    <!-- Modal CRUD -->
    <div v-if="showModal" class="modal-backdrop" @mousedown.self="close">
      <div class="modal-card">
        <div class="modal-header">
          <h2 class="h5 mb-0">{{ editingId ? 'Sửa thông tin bệnh nhân' : 'Thêm bệnh nhân mới' }}</h2>
          <button class="btn-close" @click="close" :disabled="saving">×</button>
        </div>

        <form @submit.prevent="save" class="modal-body">
          <!-- Thông tin cơ bản -->
          <div class="form-section">
            <h6 class="section-title">Thông tin cơ bản</h6>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Mã bệnh nhân <span class="text-danger">*</span></label>
                <input
                  v-model.trim="form.code"
                  class="form-control"
                  :disabled="!!editingId"
                  placeholder="Tự sinh khi thêm mới"
                  required
                />
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                <input v-model.trim="form.fullName" class="form-control" required />
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Giới tính</label>
                <select v-model="form.gender" class="form-select">
                  <option value="">Chọn...</option>
                  <option value="male">Nam</option>
                  <option value="female">Nữ</option>
                </select>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Ngày sinh</label>
                <input v-model="form.birthDate" type="date" class="form-control" />
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">CMND/CCCD</label>
                <input v-model.trim="form.idNumber" class="form-control" />
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Điện thoại</label>
                <input v-model.trim="form.phone" class="form-control" />
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Email</label>
                <input v-model.trim="form.email" type="email" class="form-control" />
              </div>
            </div>
          </div>

          <!-- Địa chỉ -->
          <div class="form-section">
            <h6 class="section-title">Địa chỉ</h6>
            <div class="row">
              <div class="col-12 mb-3">
                <label class="form-label">Địa chỉ</label>
                <input v-model.trim="form.street" class="form-control" placeholder="Số nhà, tên đường" />
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Phường/Xã</label>
                <input v-model.trim="form.ward" class="form-control" />
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Quận/Huyện</label>
                <input v-model.trim="form.district" class="form-control" />
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Tỉnh/TP</label>
                <input v-model.trim="form.city" class="form-control" />
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Mã bưu điện</label>
                <input v-model.trim="form.postalCode" class="form-control" />
              </div>
            </div>
          </div>

          <!-- Thông tin y tế -->
          <div class="form-section">
            <h6 class="section-title">Thông tin y tế</h6>
            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label">Nhóm máu</label>
                <select v-model="form.bloodType" class="form-select">
                  <option value="">Chọn...</option>
                  <option value="A">A</option>
                  <option value="B">B</option>
                  <option value="AB">AB</option>
                  <option value="O">O</option>
                </select>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Trạng thái</label>
                <select v-model="form.status" class="form-select">
                  <option value="active">Hoạt động</option>
                  <option value="inactive">Không hoạt động</option>
                </select>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Nhà cung cấp bảo hiểm</label>
                <input v-model.trim="form.insuranceProvider" class="form-control" />
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Dị ứng</label>
                <textarea v-model.trim="form.allergies" class="form-control" rows="2" placeholder="Liệt kê các loại dị ứng"></textarea>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Bệnh mãn tính</label>
                <textarea v-model.trim="form.chronicConditions" class="form-control" rows="2" placeholder="Liệt kê các bệnh mãn tính"></textarea>
              </div>
            </div>
          </div>

          <!-- Liên hệ khẩn cấp -->
          <div class="form-section">
            <h6 class="section-title">Liên hệ khẩn cấp</h6>
            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label">Tên người liên hệ</label>
                <input v-model.trim="form.emergencyName" class="form-control" />
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Mối quan hệ</label>
                <input v-model.trim="form.emergencyRelationship" class="form-control" placeholder="Ví dụ: Con, Vợ/Chồng" />
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Số điện thoại</label>
                <input v-model.trim="form.emergencyPhone" class="form-control" />
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" @click="close" :disabled="saving">Hủy</button>
            <button class="btn btn-primary" type="submit" :disabled="saving">
              <span v-if="saving">
                <span class="spinner-border spinner-border-sm me-1"></span>
                Đang lưu...
              </span>
              <span v-else>Lưu</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script>
import PatientService from '@/api/patientService'

// Helper functions
function randomPatientCode () {
  const y = new Date().getFullYear()
  const n = Math.floor(Math.random() * 100000).toString().padStart(5, '0')
  return `BN${y}${n}`
}

export default {
  name: 'PatientsListView',
  data () {
    return {
      items: [],
      total: 0,
      page: 1,
      pageSize: 50,
      q: '',
      loading: false,
      error: '',
      hasMore: false,
      expandedId: null,
      showModal: false,
      editingId: null,
      saving: false,
      form: this.getEmptyForm()
    }
  },
  created () { this.fetch() },
  methods: {
    getEmptyForm () {
      return {
        code: '',
        fullName: '',
        gender: '',
        birthDate: '',
        idNumber: '',
        phone: '',
        email: '',
        street: '',
        ward: '',
        district: '',
        city: '',
        postalCode: '',
        bloodType: '',
        allergies: '',
        chronicConditions: '',
        insuranceProvider: '',
        emergencyName: '',
        emergencyRelationship: '',
        emergencyPhone: '',
        status: 'active'
      }
    },

    // chuẩn hoá CouchDB -> object phẳng + thêm nhiều field
    normalize (res) {
      const payload = (res && typeof res === 'object' && 'data' in res) ? res.data : res
      let rows = []
      if (Array.isArray(payload)) rows = payload
      else if (payload && typeof payload === 'object') {
        if (Array.isArray(payload.rows)) rows = payload.rows.map(r => r.doc || r.value || r)
        else if (Array.isArray(payload.items)) rows = payload.items
        else if (Array.isArray(payload.data)) rows = payload.data
        else if (Array.isArray(payload.results)) rows = payload.results
        else if (Array.isArray(payload.docs)) rows = payload.docs
      }

      const flat = (rows || []).map(d => {
        const pi = d.personal_info || {}
        const addr = d.address || {}
        const mi = d.medical_info || {}
        const ins = mi.insurance || {}
        const ec = pi.emergency_contact || {}
        return {
          ...d,
          _id: d._id || d.id,
          code: d.code || d.patientCode || d.patient_code || d._id || d.id,
          fullName: d.fullName || d.name || d.full_name || pi.full_name,
          gender: d.gender ?? pi.gender,
          birthDate: d.dob || d.birth_date || d.dateOfBirth || pi.birth_date,
          phone: d.phone || d.phoneNumber || pi.phone,
          email: pi.email || d.email,
          idNumber: d.id_number || pi.id_number,
          // địa chỉ
          street: addr.street || d.street,
          ward: addr.ward || d.ward,
          district: addr.district || d.district,
          city: addr.city || d.city,
          postalCode: addr.postal_code || d.postalCode,
          // y tế
          bloodType: mi.blood_type || d.bloodType,
          allergies: Array.isArray(mi.allergies) ? mi.allergies.join(', ') : (mi.allergies || d.allergies || ''),
          chronicConditions: Array.isArray(mi.chronic_conditions) ? mi.chronic_conditions.join(', ') : (mi.chronic_conditions || d.chronicConditions || ''),
          insuranceProvider: ins.provider || d.insuranceProvider,
          insurancePolicy: ins.policy_number || d.insurancePolicy,
          insuranceValidUntil: ins.valid_until || d.insuranceValidUntil,
          // liên hệ khẩn cấp
          emergencyName: ec.name || d.emergencyName,
          emergencyRelationship: ec.relationship || d.emergencyRelationship,
          emergencyPhone: ec.phone || d.emergencyPhone,
          // khác
          status: d.status || 'active',
          createdAt: d.created_at || d.createdAt,
          updatedAt: d.updated_at || d.updatedAt,
          createdBy: d.created_by || d.createdBy
        }
      })

      const total =
        (payload && (payload.total ?? payload.count ?? payload.total_rows ?? payload?.meta?.total ?? payload?.pagination?.total)) ??
        flat.length
      const offset = (payload && (payload.offset ?? payload.skip ?? payload.start)) ?? 0

      return { items: flat, total: Number(total) || 0, offset: Number(offset) || 0 }
    },

    async fetch () {
      this.loading = true
      this.error = ''
      try {
        const skip = (this.page - 1) * this.pageSize
        const res = await PatientService.list({
          q: this.q || undefined,
          limit: this.pageSize,
          skip
        })
        const meta = this.normalize(res)
        this.items = meta.items
        this.total = meta.total
        this.hasMore = (meta.offset + this.items.length) < this.total
      } catch (e) {
        console.error(e)
        this.error = e?.response?.data?.message || e?.message || 'Không tải được danh sách bệnh nhân'
      } finally {
        this.loading = false
      }
    },

    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },
    changePageSize () { this.page = 1; this.fetch() },

    toggle (row) {
      const id = row._id || row.id
      this.expandedId = (this.expandedId === id) ? null : id
    },

    // CRUD functions
    openCreate () {
      this.editingId = null
      this.form = this.getEmptyForm()
      this.form.code = randomPatientCode()
      this.showModal = true
    },

    openEdit (row) {
      this.editingId = row._id || row.id
      this.form = { ...this.getEmptyForm(), ...row }
      this.showModal = true
    },

    close () {
      if (!this.saving) {
        this.showModal = false
        this.editingId = null
        this.form = this.getEmptyForm()
      }
    },

    async save () {
      if (this.saving) return
      this.saving = true
      try {
        const payload = {
          _id: this.editingId || this.form.code,
          _rev: this.form._rev,
          type: 'patient',
          ...this.form
        }

        if (this.editingId) {
          await PatientService.update(this.editingId, payload)
        } else {
          await PatientService.create(payload)
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
      if (!confirm(`Xóa bệnh nhân "${row.fullName || row.code}"?`)) return
      try {
        const id = row._id || row.id
        await PatientService.remove(id)
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Xóa thất bại')
      }
    },

    // Helper methods
    fmtDate (v) { if (!v) return '-'; try { return new Date(v).toLocaleDateString() } catch { return v } },
    fmtDateTime (v) { if (!v) return '-'; try { return new Date(v).toLocaleString() } catch { return v } },
    renderGender (g) {
      const s = String(g ?? '').toLowerCase()
      if (s === 'm' || s === 'male' || s === 'nam' || g === true || g === 1) return 'Nam'
      if (s === 'f' || s === 'female' || s === 'nữ' || g === false || g === 0) return 'Nữ'
      return s || '-'
    }
  }
}
</script>

<style scoped>
.detail-grid{
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  padding: 10px 6px 2px;
  border-top: 1px dashed #e5e7eb;
}
.badge { font-weight: 500; }

/* Modal styles */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.5);
  display: grid;
  place-items: center;
  z-index: 1050;
  overflow-y: auto;
  padding: 20px;
}
.modal-card {
  width: min(900px, 95vw);
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 12px 30px rgba(0,0,0,.18);
  max-height: 90vh;
  overflow-y: auto;
}
.modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.modal-body {
  padding: 24px;
}
.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: end;
  gap: 12px;
}
.btn-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #6b7280;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
}
.btn-close:hover {
  background: #f3f4f6;
  color: #374151;
}

.form-section {
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid #f3f4f6;
}
.form-section:last-child {
  margin-bottom: 0;
  border-bottom: none;
}
.section-title {
  color: #374151;
  font-weight: 600;
  margin-bottom: 16px;
  padding-bottom: 8px;
  border-bottom: 2px solid #e5e7eb;
}
</style>
