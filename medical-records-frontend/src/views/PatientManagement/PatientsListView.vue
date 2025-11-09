<template>
  <div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0">Quản lý Bệnh nhân</h3>
      <div class="d-flex gap-2">
        <input
          v-model.trim="keyword"
          type="text"
          class="form-control"
          style="width: 340px"
          placeholder="Tìm theo SĐT / tên / email"
          @input="quickFilter"
          @keyup.enter="reload"
        />
        <button class="btn btn-outline-secondary" @click="reload" :disabled="loading">
          <i class="bi bi-search me-1"></i>Tìm
        </button>
        <button class="btn btn-outline-info" @click="refreshPage" :disabled="loading">
          <i class="bi bi-arrow-clockwise me-1"></i>Tải lại
        </button>
        <button class="btn btn-primary" @click="openCreate">
          <i class="bi bi-plus-circle me-1"></i>Thêm mới
        </button>
      </div>
    </div>

    <!-- LIST -->
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="width: 56px">#</th>
            <th>Họ tên</th>
            <th>Điện thoại</th>
            <th>Email</th>
            <th>Giới tính</th>
            <th>Trạng thái</th>
            <th style="width: 220px" class="text-end">Tác vụ</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="(p, idx) in shownItems" :key="getId(p)">
            <!-- Dòng chính -->
            <tr>
              <td>{{ idx + 1 }}</td>
              <td>{{ p.personal_info?.full_name || '-' }}</td>
              <td>{{ p.personal_info?.phone || '-' }}</td>
              <td>{{ p.personal_info?.email || '-' }}</td>
              <td class="text-capitalize">{{ p.personal_info?.gender || '-' }}</td>
              <td>
                <span :class="['badge', p.status === 'active' ? 'bg-success' : 'bg-secondary']">
                  {{ p.status === 'active' ? 'Hoạt động' : 'Không hoạt động' }}
                </span>
              </td>
              <td class="text-end">
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-primary" @click="openEdit(p)">Sửa</button>
                  <button class="btn btn-sm btn-outline-danger" @click="confirmRemove(p)">Xóa</button>
                  <button class="btn btn-sm btn-outline-secondary" @click="toggleExpand(p)">
                    {{ expandedId === getId(p) ? 'Ẩn' : 'Xem' }}
                  </button>
                </div>
              </td>
            </tr>

            <!-- Hàng chi tiết (expand giống User) -->
            <tr v-if="expandedId === getId(p)">
              <td colspan="7" class="bg-body">
                <div class="border-top pt-3">
                  <!-- Header nhỏ như ảnh user -->
                  <div class="small text-muted mb-2">Thông tin tài khoản</div>
                  <div class="row g-3 mb-3">
                    <div class="col-md-3">
                      <div><span class="fw-semibold">Họ tên:</span> {{ p.personal_info?.full_name || '-' }}</div>
                      <div><span class="fw-semibold">Giới tính:</span> {{ p.personal_info?.gender || '-' }}</div>
                      <div><span class="fw-semibold">Ngày sinh:</span> {{ p.personal_info?.birth_date || '-' }}</div>
                    </div>
                    <div class="col-md-3">
                      <div><span class="fw-semibold">Điện thoại:</span> {{ p.personal_info?.phone || '-' }}</div>
                      <div><span class="fw-semibold">Email:</span> {{ p.personal_info?.email || '-' }}</div>
                      <div><span class="fw-semibold">CMND/CCCD:</span> {{ p.personal_info?.id_number || '-' }}</div>
                    </div>
                    <div class="col-md-3">
                      <div><span class="fw-semibold">Trạng thái:</span> {{ p.status || '-' }}</div>
                      <div><span class="fw-semibold">ID:</span> {{ p._id || '-' }}</div>
                      <div><span class="fw-semibold">Rev:</span> {{ p._rev || '-' }}</div>
                    </div>
                    <div class="col-md-3 text-end">
                      <button class="btn btn-sm btn-outline-primary me-2" @click="openEdit(p)">Sửa</button>
                      <button class="btn btn-sm btn-outline-secondary" @click="toggleExpand(p)">Ẩn</button>
                    </div>
                  </div>

                  <div class="row">
                    <!-- Khối địa chỉ -->
                    <div class="col-md-6">
                      <div class="border rounded p-3 h-100">
                        <div class="fw-semibold mb-2">Địa chỉ</div>
                        <div>{{ p.address?.street || '-' }}</div>
                        <div>
                          {{ p.address?.ward || '-' }}
                          <span v-if="p.address?.district">, {{ p.address?.district }}</span>
                        </div>
                        <div>
                          {{ p.address?.city || '-' }}
                          <span v-if="p.address?.postal_code"> ({{ p.address?.postal_code }})</span>
                        </div>
                      </div>
                    </div>

                    <!-- Khối y tế -->
                    <div class="col-md-6">
                      <div class="border rounded p-3 h-100">
                        <div class="fw-semibold mb-2">Thông tin y tế</div>
                        <div><b>Nhóm máu:</b> {{ p.medical_info?.blood_type || '-' }}</div>
                        <div><b>Dị ứng:</b> {{ (p.medical_info?.allergies || []).join(', ') || '-' }}</div>
                        <div><b>Bệnh mạn:</b> {{ (p.medical_info?.chronic_conditions || []).join(', ') || '-' }}</div>
                        <div class="mt-2"><b>Bảo hiểm:</b> {{ p.medical_info?.insurance?.provider || '-' }}</div>
                        <div><b>Số thẻ:</b> {{ p.medical_info?.insurance?.policy_number || '-' }}</div>
                        <div><b>Hết hạn:</b> {{ p.medical_info?.insurance?.valid_until || '-' }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          </template>

          <tr v-if="!loading && !shownItems.length">
            <td colspan="7" class="text-center text-muted py-4">Không có dữ liệu</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODAL: CREATE/EDIT -->
    <div class="modal fade" id="patientModal" tabindex="-1" aria-hidden="true" ref="modalEl">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ isEdit ? 'Chỉnh sửa bệnh nhân' : 'Thêm bệnh nhân mới' }}</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <!-- FORM (model: form) -->
            <div class="vstack gap-3">
              <!-- Cơ bản -->
              <div class="card">
                <div class="card-header fw-semibold">Thông tin cơ bản</div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-lg-6">
                      <label class="form-label">Họ tên *</label>
                      <input v-model="form.hoTen" type="text" class="form-control" required />
                    </div>
                    <div class="col-lg-3">
                      <label class="form-label">Ngày sinh</label>
                      <input v-model="form.ngaySinh" type="date" class="form-control" />
                    </div>
                    <div class="col-lg-3">
                      <label class="form-label">Giới tính</label>
                      <select v-model="form.gioiTinh" class="form-select">
                        <option value="">-- Chọn --</option>
                        <option>Nam</option>
                        <option>Nữ</option>
                        <option>Khác</option>
                      </select>
                    </div>
                    <div class="col-lg-4">
                      <label class="form-label">CMND/CCCD</label>
                      <input v-model="form.cmndCccd" type="text" class="form-control" />
                    </div>
                    <div class="col-lg-4">
                      <label class="form-label">Điện thoại</label>
                      <input v-model="form.dienThoai" type="text" class="form-control" maxlength="10" pattern="\d{10}" @input="onPhoneInput" />
                    </div>
                    <div class="col-lg-4">
                      <label class="form-label">Email</label>
                      <input v-model="form.email" type="email" class="form-control" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Địa chỉ -->
              <div class="card">
                <div class="card-header fw-semibold">Địa chỉ</div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-12">
                      <label class="form-label">Địa chỉ</label>
                      <input v-model="form.diaChi" type="text" class="form-control" />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Phường/Xã</label>
                      <input v-model="form.phuongXa" type="text" class="form-control" />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Quận/Huyện</label>
                      <input v-model="form.quanHuyen" type="text" class="form-control" />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Tỉnh/TP</label>
                      <input v-model="form.tinhTp" type="text" class="form-control" />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Mã bưu điện</label>
                      <input v-model="form.maBuuDien" type="text" class="form-control" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Y tế -->
              <div class="card">
                <div class="card-header fw-semibold">Thông tin y tế</div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-md-3">
                      <label class="form-label">Nhóm máu</label>
                      <select v-model="form.nhomMau" class="form-select">
                        <option value="">-- Chọn nhóm máu --</option>
                        <option>A+</option>
                        <option>A-</option>
                        <option>B+</option>
                        <option>B-</option>
                        <option>AB+</option>
                        <option>AB-</option>
                        <option>O+</option>
                        <option>O-</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Trạng thái</label>
                      <select v-model="form.trangThai" class="form-select">
                        <option>Hoạt động</option>
                        <option>Không hoạt động</option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Nhà cung cấp bảo hiểm</label>
                      <input v-model="form.nhaCungCapBaoHiem" type="text" class="form-control" placeholder="BHYT, Bảo Việt..." />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Số thẻ BH</label>
                      <input v-model="form.soTheBaoHiem" type="text" class="form-control" />
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Hạn bảo hiểm</label>
                      <input v-model="form.hanBaoHiem" type="date" class="form-control" placeholder="dd/mm/yyyy" />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Dị ứng (phẩy hoặc xuống dòng)</label>
                      <textarea v-model="form.diUng" rows="2" class="form-control" placeholder="Seafood, Penicillin"></textarea>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Bệnh mạn tính (phẩy hoặc xuống dòng)</label>
                      <textarea v-model="form.benhNen" rows="2" class="form-control" placeholder="Diabetes, Hypertension"></textarea>
                    </div>
                  </div>
                </div>
              </div>
            </div> <!-- /vstack -->
          </div>
          <div class="modal-footer">
            <button class="btn btn-light" @click="closeModal">Hủy</button>
            <button class="btn btn-primary" :disabled="saving" @click="save">
              <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
              Lưu
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: CONFIRM DELETE -->
    <div class="modal fade" id="confirmDelete" tabindex="-1" ref="confirmEl">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Xác nhận xóa</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="d-flex align-items-center mb-3">
              <i class="bi bi-exclamation-triangle-fill text-warning fs-1 me-3"></i>
              <div>
                <p class="mb-1">Bạn có chắc chắn muốn xóa bệnh nhân:</p>
                <strong class="text-danger">{{ pendingName }}</strong>
              </div>
            </div>
            <div class="alert alert-warning">
              <small>⚠️ Thao tác này không thể hoàn tác!</small>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
            <button class="btn btn-danger" :disabled="deleting" @click="doDelete">
              <span v-if="deleting" class="spinner-border spinner-border-sm me-2"></span>
              <i class="bi bi-trash me-1"></i>
              Xóa
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TOAST NOTIFICATIONS -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080">
      <div class="toast" ref="toastEl" role="alert">
        <div class="toast-header">
          <strong class="me-auto" :class="toastType === 'success' ? 'text-success' : 'text-danger'">
            <i :class="toastType === 'success' ? 'bi bi-check-circle' : 'bi bi-x-circle'" class="me-1"></i>
            {{ toastType === 'success' ? 'Thành công' : 'Lỗi' }}
          </strong>
          <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">{{ toastMessage }}</div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import * as bootstrap from 'bootstrap/dist/js/bootstrap.bundle.min.js'
import PatientService from '@/api/patientService'

/* Helpers */
const toDateInputFormat = (iso) => {
  if (!iso) return ''
  try {
    const d = new Date(iso)
    if (isNaN(d)) return ''
    const yyyy = d.getFullYear()
    const mm = String(d.getMonth() + 1).padStart(2, '0')
    const dd = String(d.getDate()).padStart(2, '0')
    return `${yyyy}-${mm}-${dd}`
  } catch {
    return ''
  }
}
const toMMDDYYYY = (iso) => {
  if (!iso) return ''
  const d = new Date(iso)
  if (isNaN(d)) return ''
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  const yyyy = d.getFullYear()
  return `${mm}/${dd}/${yyyy}`
}
const toYYYYMMDD = (s) => {
  if (!s) return null
  try {
    // Xử lý nếu input là date object
    if (s instanceof Date) {
      const yyyy = s.getFullYear()
      const mm = String(s.getMonth() + 1).padStart(2, '0')
      const dd = String(s.getDate()).padStart(2, '0')
      return `${yyyy}-${mm}-${dd}`
    }

    // Xử lý string với các format khác nhau
    const str = String(s).trim()

    // Format YYYY-MM-DD (ISO)
    if (/^\d{4}-\d{1,2}-\d{1,2}$/.test(str)) {
      const [yyyy, mm, dd] = str.split('-')
      return `${yyyy}-${String(mm).padStart(2, '0')}-${String(dd).padStart(2, '0')}`
    }

    // Format MM/DD/YYYY hoặc DD/MM/YYYY
    const parts = str.split(/[/-]/).map(x => x.trim())
    if (parts.length !== 3) return null

    let mm, dd, yyyy
    // Nếu phần đầu > 12 thì có thể là DD/MM/YYYY
    if (parseInt(parts[0], 10) > 12) {
      dd = parts[0]
      mm = parts[1]
      yyyy = parts[2]
    } else {
      // Ngược lại là MM/DD/YYYY
      mm = parts[0]
      dd = parts[1]
      yyyy = parts[2]
    }

    // Validate values
    const mmNum = parseInt(mm, 10)
    const ddNum = parseInt(dd, 10)
    const yyyyNum = parseInt(yyyy, 10)

    if (mmNum < 1 || mmNum > 12 || ddNum < 1 || ddNum > 31 || yyyyNum < 1900 || yyyyNum > 2100) {
      return null
    }

    return `${String(yyyyNum).padStart(4, '0')}-${String(mmNum).padStart(2, '0')}-${String(ddNum).padStart(2, '0')}`
  } catch (error) {
    console.error('Date conversion error:', error)
    return null
  }
}
const toArray = (v) => (v ?? '').split(/\r?\n|,/).map(s => s.trim()).filter(Boolean)
const fromArray = (arr) => (Array.isArray(arr) ? arr.join(', ') : '')

/* Mapping */
function toFormFn (doc) {
  const p = doc || {}
  const pi = p.personal_info || {}
  const ad = p.address || {}
  const mi = p.medical_info || {}
  const ins = mi.insurance || {}
  return {
    _id: p._id || null,
    _rev: p._rev || null,
    hoTen: pi.full_name || '',
    ngaySinh: toDateInputFormat(pi.birth_date),
    gioiTinh: pi.gender === 'male' ? 'Nam' : (pi.gender === 'female' ? 'Nữ' : ''),
    cmndCccd: pi.id_number || '',
    dienThoai: pi.phone || '',
    email: pi.email || '',
    diaChi: ad.street || '',
    phuongXa: ad.ward || '',
    quanHuyen: ad.district || '',
    tinhTp: ad.city || '',
    maBuuDien: ad.postal_code || '',
    nhomMau: mi.blood_type || '',
    trangThai: p.status === 'active' ? 'Hoạt động' : 'Không hoạt động',
    nhaCungCapBaoHiem: ins.provider || '',
    soTheBaoHiem: ins.policy_number || '',
    hanBaoHiem: toMMDDYYYY(ins.valid_until),
    diUng: fromArray(mi.allergies),
    benhNen: fromArray(mi.chronic_conditions)
  }
}
function toPayloadFn (f) {
  const g = (f.gioiTinh || '').toLowerCase()
  const gender = g.includes('nam') ? 'male' : (g.includes('nữ') || g.includes('nu') ? 'female' : 'other')

  // Xử lý ngày sinh - nếu từ date input thì đã là YYYY-MM-DD
  let birthDate = null
  if (f.ngaySinh) {
    // Nếu đã là format YYYY-MM-DD (từ date input)
    if (/^\d{4}-\d{2}-\d{2}$/.test(f.ngaySinh)) {
      birthDate = f.ngaySinh
    } else {
      // Nếu là format khác thì convert
      birthDate = toYYYYMMDD(f.ngaySinh)
    }
  }

  return {
    type: 'patient',
    personal_info: {
      full_name: f.hoTen?.trim(),
      birth_date: birthDate,
      gender,
      id_number: f.cmndCccd?.trim(),
      phone: f.dienThoai?.trim(),
      email: f.email?.trim()
    },
    address: {
      street: f.diaChi?.trim(),
      ward: f.phuongXa?.trim(),
      district: f.quanHuyen?.trim(),
      city: f.tinhTp?.trim(),
      postal_code: f.maBuuDien?.trim()
    },
    medical_info: {
      blood_type: f.nhomMau?.trim() || null,
      allergies: toArray(f.diUng),
      chronic_conditions: toArray(f.benhNen),
      insurance: (f.nhaCungCapBaoHiem || f.soTheBaoHiem || f.hanBaoHiem)
        ? {
            provider: f.nhaCungCapBaoHiem?.trim(),
            policy_number: f.soTheBaoHiem?.trim(),
            valid_until: toYYYYMMDD(f.hanBaoHiem)
          }
        : undefined
    },
    status: (f.trangThai || '').toLowerCase().includes('hoạt') ? 'active' : 'inactive'
  }
}/* State */
const items = ref([])
const filtered = ref([]) // filter tạm thời client-side khi nhập số ĐT
const loading = ref(false)
const keyword = ref('')
const saving = ref(false)
const isEdit = ref(false)
const expandedId = ref(null)

// Modal refs
const modalEl = ref(null)
const confirmEl = ref(null)
const toastEl = ref(null)
let modal
let confirmModal
let toast

// Delete confirmation state
const pendingRow = ref(null)
const pendingName = ref('')
const deleting = ref(false)

// Toast state
const toastMessage = ref('')
const toastType = ref('success') // 'success' | 'error'

const form = reactive(toFormFn({}))

/* Computed: danh sách hiển thị (ưu tiên filter client-side nếu đang gõ số) */
const shownItems = computed(() => filtered.value.length ? filtered.value : items.value)

/* Utils */
const getId = (p) => p._id || p.id || p?.doc?._id

/* Toast notifications */
function showToast (message, type = 'success') {
  toastMessage.value = message
  toastType.value = type
  toast.show()
}

/* API */
async function reload () {
  loading.value = true
  try {
    const kw = keyword.value.trim().toLowerCase()
    let res
    if (!kw) {
      // Không có từ khóa, lấy toàn bộ
      res = await PatientService.list()
    } else if (/^\d{3,}$/.test(kw.replace(/\D/g, ''))) {
      // Tìm theo số điện thoại
      res = await PatientService.list({ phone: kw })
    } else if (kw.includes('@')) {
      // Tìm theo email
      res = await PatientService.list({ email: kw })
    } else {
      // Tìm theo tên (không phân biệt hoa thường, có thể tìm một phần)
      res = await PatientService.list({ q: kw })
      // Nếu backend chỉ trả về kết quả chính xác, lọc lại trên client
      if (Array.isArray(res?.data)) {
        res.data = res.data.filter(p => (p.personal_info?.full_name || '').toLowerCase().includes(kw))
      } else if (Array.isArray(res?.rows)) {
        res.rows = res.rows.filter(r => ((r.doc?.personal_info?.full_name || r.value?.personal_info?.full_name || '').toLowerCase().includes(kw)))
      }
    }
    // Nếu là email, lọc lại trên client nếu backend chưa hỗ trợ
    if (kw.includes('@')) {
      if (Array.isArray(res?.data)) {
        res.data = res.data.filter(p => (p.personal_info?.email || '').toLowerCase().includes(kw))
      } else if (Array.isArray(res?.rows)) {
        res.rows = res.rows.filter(r => ((r.doc?.personal_info?.email || r.value?.personal_info?.email || '').toLowerCase().includes(kw)))
      }
    }
    const arr = Array.isArray(res?.data)
      ? res.data
      : Array.isArray(res?.rows) ? res.rows.map(r => r.doc || r) : []
    items.value = arr
    filtered.value = [] // reset filter client
    expandedId.value = null
  } catch (error) {
    console.error('Search error:', error)
    showToast('Lỗi tìm kiếm: ' + (error?.message || 'Không thể kết nối với server'), 'error')
  } finally {
    loading.value = false
  }
}

/* Refresh page - Reset all filters and reload */
async function refreshPage () {
  loading.value = true
  try {
    // Reset tất cả bộ lọc và trạng thái
    keyword.value = ''
    filtered.value = []
    expandedId.value = null

    // Hiển thị loading toast
    showToast('Đang tải lại dữ liệu...', 'success')

    // Lấy toàn bộ dữ liệu từ server
    const res = await PatientService.list()
    const arr = Array.isArray(res?.data)
      ? res.data
      : Array.isArray(res?.rows) ? res.rows.map(r => r.doc || r) : []

    items.value = arr

    showToast(`Đã tải lại ${arr.length} bệnh nhân`, 'success')
  } catch (error) {
    console.error('Refresh error:', error)
    showToast('Lỗi tải lại: ' + (error?.message || 'Không thể kết nối với server'), 'error')
  } finally {
    loading.value = false
  }
}

/* Quick filter: nếu keyword toàn số (>=3 ký tự) thì lọc local theo phone */
function quickFilter () {
  const kw = keyword.value.trim()
  const isPhone = /^\d{3,}$/.test(kw.replace(/\D/g, ''))
  if (!kw || !isPhone) { filtered.value = []; return }
  const norm = kw.replace(/\D/g, '')
  filtered.value = items.value.filter(p => (p.personal_info?.phone || '').replace(/\D/g, '').includes(norm))
}

/* Expand row */
function toggleExpand (p) {
  const id = getId(p)
  expandedId.value = expandedId.value === id ? null : id
}

/* Create / Edit */
function openCreate () {
  isEdit.value = false
  Object.assign(form, toFormFn({}))
  form.trangThai = 'Hoạt động'
  modal.show()
}

function openEdit (row) {
  // Sử dụng requestAnimationFrame để tách thao tác nặng khỏi click handler
  requestAnimationFrame(async () => {
    try {
      isEdit.value = true
      const id = getId(row)
      const fresh = await PatientService.get(id)
      Object.assign(form, toFormFn(fresh))
      modal.show()
    } catch (e) {
      console.error('Edit error:', e)
      showToast('Không thể tải thông tin bệnh nhân', 'error')
    }
  })
}

function closeModal () { modal.hide() }

async function save () {
  // Validation cơ bản
  if (!form.hoTen?.trim()) {
    showToast('Vui lòng nhập họ tên', 'error')
    return
  }

  // Validate ngày sinh
  if (form.ngaySinh) {
    // Kiểm tra format date input (YYYY-MM-DD)
    if (!/^\d{4}-\d{2}-\d{2}$/.test(form.ngaySinh)) {
      showToast('Ngày sinh không hợp lệ', 'error')
      return
    }

    // Kiểm tra ngày có hợp lệ không
    const date = new Date(form.ngaySinh)
    if (isNaN(date) || date.getFullYear() < 1900 || date.getFullYear() > new Date().getFullYear()) {
      showToast('Ngày sinh không hợp lệ', 'error')
      return
    }
  }

  // Validate email nếu có
  if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    showToast('Email không hợp lệ', 'error')
    return
  }

  // Validate số điện thoại nếu có
  if (form.dienThoai && !/^\d{10}$/.test(form.dienThoai.replace(/\D/g, ''))) {
    showToast('Số điện thoại phải có 10 chữ số', 'error')
    return
  }

  saving.value = true
  try {
    const payload = toPayloadFn(form)
    console.log('Saving payload:', payload) // Debug log

    if (isEdit.value && form._id) {
      await PatientService.update(form._id, payload)
      showToast('Cập nhật bệnh nhân thành công!')
    } else {
      await PatientService.create(payload)
      showToast('Thêm bệnh nhân thành công!')
    }
    await reload()
    closeModal()
  } catch (e) {
    console.error('Save error:', e)
    let errorMessage = 'Lưu thất bại'

    if (e?.response?.data?.details) {
      const details = e.response.data.details
      const errors = []
      for (const [field, messages] of Object.entries(details)) {
        if (Array.isArray(messages)) {
          errors.push(`${field}: ${messages.join(', ')}`)
        }
      }
      if (errors.length > 0) {
        errorMessage = errors.join('\n')
      }
    } else if (e?.response?.data?.message) {
      errorMessage = e.response.data.message
    } else if (e?.message) {
      errorMessage = e.message
    }

    showToast(errorMessage, 'error')
  } finally {
    saving.value = false
  }
}

/* Delete confirmation - bất đồng bộ */
function confirmRemove (row) {
  pendingRow.value = row
  pendingName.value = row?.personal_info?.full_name || row?.doc?.personal_info?.full_name || 'Không rõ tên'
  // Mở modal xác nhận (không block main thread)
  confirmModal.show()
}

async function doDelete () {
  if (!pendingRow.value) return

  const id = getId(pendingRow.value)
  if (!id) {
    showToast('Không xác định được ID bệnh nhân', 'error')
    return
  }

  deleting.value = true
  let fresh = null // Declare fresh outside try block

  try {
    console.log('Attempting to delete patient:', {
      id,
      pendingRow: pendingRow.value
    })

    // Lấy document mới nhất để có _rev chính xác
    fresh = await PatientService.get(id)
    console.log('Fresh patient data:', fresh)

    const rev = fresh?._rev
    if (!rev) {
      throw new Error('Không lấy được revision của document')
    }

    console.log('Deleting with rev:', rev)

    // Gọi remove với error handling
    const deleteResult = await PatientService.remove(id, rev)
    console.log('Delete result:', deleteResult)

    // Kiểm tra kết quả từ backend
    if (deleteResult && (deleteResult.ok === true || deleteResult.status === 'success')) {
      showToast(`Đã xóa bệnh nhân "${pendingName.value}" thành công!`)
      await reload()
    } else {
      throw new Error(deleteResult?.message || 'Delete operation failed')
    }
  } catch (error) {
    console.error('Delete error details:', error)

    // Handle specific error types
    let errorMessage = 'Xóa thất bại'

    if (error?.message) {
      errorMessage = error.message
    } else if (typeof error === 'string') {
      errorMessage = error
    } else if (error?.response?.data?.message) {
      errorMessage = error.response.data.message
    } else {
      // Fallback cho [object Object] errors
      errorMessage = 'Có lỗi xảy ra khi xóa bệnh nhân'
      console.error('Unhandled error object:', error)
    }

    // Retry logic cho conflict errors
    if (errorMessage.includes('modified') || errorMessage.includes('conflict') || errorMessage.includes('409')) {
      try {
        console.log('Retrying delete due to conflict...')
        const newer = await PatientService.get(id)
        const newerRev = newer?._rev

        if (newerRev && newerRev !== fresh?._rev) {
          const retryResult = await PatientService.remove(id, newerRev)

          if (retryResult && (retryResult.ok === true || retryResult.status === 'success')) {
            showToast(`Đã xóa bệnh nhân "${pendingName.value}" thành công!`)
            await reload()
            return // Success on retry
          }
        }

        // If retry failed
        showToast('Xóa thất bại: Document đã được thay đổi, vui lòng tải lại trang', 'error')
      } catch (retryError) {
        console.error('Retry delete failed:', retryError)
        showToast('Xóa thất bại sau khi thử lại', 'error')
      }
    } else {
      // Regular error handling
      showToast(errorMessage, 'error')
    }
  } finally {
    deleting.value = false
    confirmModal.hide()
    pendingRow.value = null
    pendingName.value = ''
  }
}

/* lifecycle */
onMounted(() => {
  modal = new bootstrap.Modal(modalEl.value)
  confirmModal = new bootstrap.Modal(confirmEl.value)
  toast = new bootstrap.Toast(toastEl.value)
  reload()
})

function onPhoneInput (e) {
  // Chỉ cho phép nhập số và tối đa 10 ký tự
  const val = e.target.value.replace(/\D/g, '').slice(0, 10)
  form.dienThoai = val
}
</script>

<style scoped>
.table td, .table th { vertical-align: middle; }
.card-header { background: #f8f9fa; }
.bg-body { background-color: #fff; }
</style>
