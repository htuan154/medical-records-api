<template>
  <section class="patients-management">
    <!-- Header Section -->
    <div class="header-section">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="bi bi-people-fill"></i>
            Quản lý Bệnh nhân
          </h1>
          <p class="page-subtitle">Quản lý thông tin bệnh nhân và hồ sơ y tế</p>
        </div>
        <div class="header-actions">
          <button class="btn-action btn-back" @click="goHome" title="Quay lại Trang chủ">
            <i class="bi bi-arrow-left"></i>
          </button>
          <button class="btn-action btn-refresh" @click="refreshPage" :disabled="loading" title="Tải lại">
            <i class="bi bi-arrow-clockwise"></i>
          </button>
          <div class="stats-badge">
            <i class="bi bi-file-earmark-text"></i>
            <span>Tổng: <strong>{{ items.length }}</strong></span>
          </div>
          <select v-model.number="pageSize" class="page-size-select" title="Số bản ghi mỗi trang">
            <option v-for="size in [10, 25, 50, 100]" :key="size" :value="size">{{ size }} / trang</option>
          </select>
          <button class="btn-action btn-add-new" @click="openCreate" :disabled="loading">
            <i class="bi bi-plus-lg"></i>
            Thêm mới
          </button>
        </div>
      </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="search-section">
      <div class="search-container">
        <div class="search-input-group">
          <i class="bi bi-search search-icon"></i>
          <input
            v-model.trim="keyword"
            class="search-input"
            placeholder="Tìm theo SĐT / tên / email..."
            @input="quickFilter"
            @keyup.enter="reload"
          />
          <button class="search-btn" @click="reload" :disabled="loading">
            <i class="bi bi-search"></i>
            Tìm kiếm
          </button>
        </div>
      </div>
    </div>

    <!-- Content Section -->
    <div class="content-section">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <span>Đang tải danh sách...</span>
      </div>

      <template v-else>
        <div class="table-container">
          <table class="patients-table">
            <thead>
              <tr>
                <th class="col-number">#</th>
                <th class="col-name">Họ tên</th>
                <th class="col-phone">Điện thoại</th>
                <th class="col-email">Email</th>
                <th class="col-gender">Giới tính</th>
                <th class="col-status">Trạng thái</th>
                <th class="col-actions">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(p, idx) in shownItems" :key="getId(p)">
                <tr class="patient-row" :class="{ 'expanded': expandedId === getId(p) }">
                  <td class="cell-number">
                    <span class="row-number">{{ idx + 1 + (page - 1) * pageSize }}</span>
                  </td>
                  <td class="cell-name">
                    <div class="patient-name">
                      <strong>{{ p.personal_info?.full_name || '-' }}</strong>
                    </div>
                  </td>
                  <td class="cell-phone">{{ p.personal_info?.phone || '-' }}</td>
                  <td class="cell-email">{{ p.personal_info?.email || '-' }}</td>
                  <td class="cell-gender">{{ p.personal_info?.gender === 'male' ? 'Nam' : (p.personal_info?.gender === 'female' ? 'Nữ' : '-') }}</td>
                  <td class="cell-status">
                    <span class="status-badge" :class="p.status === 'active' ? 'status-active' : 'status-inactive'">
                      <i :class="p.status === 'active' ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill'"></i>
                      {{ p.status === 'active' ? 'Hoạt động' : 'Không hoạt động' }}
                    </span>
                  </td>
                  <td class="cell-actions">
                    <div class="action-buttons">
                      <button class="action-btn view-btn" @click="toggleExpand(p)" :title="expandedId === getId(p) ? 'Thu gọn' : 'Xem chi tiết'">
                        <i :class="expandedId === getId(p) ? 'bi bi-chevron-up' : 'bi bi-eye'" />
                      </button>
                      <button class="action-btn edit-btn" @click="openEdit(p)"><i class="bi bi-pencil-square"></i></button>
                      <button class="action-btn delete-btn" @click="confirmRemove(p)" :disabled="deleting"><i class="bi bi-trash"></i></button>
                    </div>
                  </td>
                </tr>

                <!-- Detail Row (expanded view) -->
                <tr v-if="expandedId === getId(p)" class="detail-row">
                  <td colspan="7" class="detail-cell">
                    <div class="patient-detail-card">
                      <div class="detail-header">
                        <h4 class="detail-title">
                          <i class="bi bi-info-circle"></i>
                          Chi tiết bệnh nhân: {{ p.personal_info?.full_name }}
                        </h4>
                      </div>

                      <div class="detail-content">
                        <div class="detail-section">
                          <h5 class="section-title">
                            <i class="bi bi-info-square"></i>
                            Thông tin cơ bản
                          </h5>
                          <div class="info-grid">
                            <div class="info-item">
                              <label>Họ tên:</label>
                              <span class="info-value">{{ p.personal_info?.full_name || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Giới tính:</label>
                              <span class="info-value">{{ p.personal_info?.gender === 'male' ? 'Nam' : (p.personal_info?.gender === 'female' ? 'Nữ' : '-') }}</span>
                            </div>
                            <div class="info-item">
                              <label>Ngày sinh:</label>
                              <span class="info-value">{{ p.personal_info?.birth_date || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Điện thoại:</label>
                              <span class="info-value">{{ p.personal_info?.phone || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Email:</label>
                              <span class="info-value">{{ p.personal_info?.email || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>CMND/CCCD:</label>
                              <span class="info-value">{{ p.personal_info?.id_number || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Trạng thái:</label>
                              <span class="status-badge" :class="p.status === 'active' ? 'status-active' : 'status-inactive'">
                                <i :class="p.status === 'active' ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill'"></i>
                                {{ p.status === 'active' ? 'Hoạt động' : 'Không hoạt động' }}
                              </span>
                            </div>
                          </div>
                        </div>

                        <div class="detail-section">
                          <h5 class="section-title">
                            <i class="bi bi-geo-alt"></i>
                            Địa chỉ
                          </h5>
                          <div class="info-grid">
                            <div class="info-item full-width">
                              <label>Địa chỉ:</label>
                              <span class="info-value">{{ p.address?.street || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Phường/Xã:</label>
                              <span class="info-value">{{ p.address?.ward || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Quận/Huyện:</label>
                              <span class="info-value">{{ p.address?.district || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Tỉnh/TP:</label>
                              <span class="info-value">{{ p.address?.city || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Mã bưu điện:</label>
                              <span class="info-value">{{ p.address?.postal_code || '-' }}</span>
                            </div>
                          </div>
                        </div>

                        <div class="detail-section">
                          <h5 class="section-title">
                            <i class="bi bi-heart-pulse"></i>
                            Thông tin y tế
                          </h5>
                          <div class="info-grid">
                            <div class="info-item">
                              <label>Nhóm máu:</label>
                              <span class="info-value">{{ p.medical_info?.blood_type || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Nhà cung cấp BH:</label>
                              <span class="info-value">{{ p.medical_info?.insurance?.provider || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Số thẻ BH:</label>
                              <span class="info-value">{{ p.medical_info?.insurance?.policy_number || '-' }}</span>
                            </div>
                            <div class="info-item">
                              <label>Hạn BH:</label>
                              <span class="info-value">{{ p.medical_info?.insurance?.valid_until || '-' }}</span>
                            </div>
                            <div class="info-item full-width">
                              <label>Dị ứng:</label>
                              <span class="info-value">{{ (p.medical_info?.allergies || []).join(', ') || '-' }}</span>
                            </div>
                            <div class="info-item full-width">
                              <label>Bệnh mạn tính:</label>
                              <span class="info-value">{{ (p.medical_info?.chronic_conditions || []).join(', ') || '-' }}</span>
                            </div>
                          </div>
                        </div>

                        <div class="detail-section">
                          <h5 class="section-title">
                            <i class="bi bi-clock"></i>
                            Thời gian
                          </h5>
                          <div class="info-grid">
                            <div class="info-item">
                              <label>ID:</label>
                              <span class="info-value"><code>{{ p._id || '-' }}</code></span>
                            </div>
                            <div class="info-item">
                              <label>Rev:</label>
                              <span class="info-value"><code>{{ p._rev || '-' }}</code></span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>

              <tr v-if="!loading && !shownItems.length" class="empty-row">
                <td colspan="7" class="empty-cell">
                  <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h3>Không có dữ liệu</h3>
                    <p>Chưa có bệnh nhân nào hoặc không tìm thấy kết quả phù hợp</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Section (Material style) -->
        <div class="pagination-section">
          <div class="pagination-info-row material-pagination-info">
            <i class="bi bi-info-circle"></i>
            <span>
              Hiển thị {{ (page - 1) * pageSize + 1 }} - {{ Math.min(page * pageSize, filtered.length ? filtered.length : items.length) }} trong tổng số <b>{{ filtered.length ? filtered.length : items.length }}</b> bản ghi
            </span>
          </div>
          <div class="material-pagination-controls">
            <button class="material-pagination-btn" :disabled="page === 1" @click="goToPage(1)">
              <i class="bi bi-chevron-double-left"></i>
            </button>
            <button class="material-pagination-btn" :disabled="page === 1" @click="prevPage">
              <i class="bi bi-chevron-left"></i>
            </button>
            <button v-for="num in getPageNumbers()" :key="num"
              class="material-pagination-btn"
              :class="{ active: num === page, ellipsis: num === '...' }"
              :disabled="num === '...'"
              @click="goToPage(num)"
            >
              {{ num }}
            </button>
            <button class="material-pagination-btn" :disabled="page === totalPages" @click="nextPage">
              <i class="bi bi-chevron-right"></i>
            </button>
            <button class="material-pagination-btn" :disabled="page === totalPages" @click="goToPage(totalPages)">
              <i class="bi bi-chevron-double-right"></i>
            </button>
          </div>
        </div>
      </template>
    </div>
    <!-- End Content Section -->

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

  </section>
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

// Pagination
const page = ref(1)
const pageSize = ref(25)

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

/* Computed: danh sách hiển thị với phân trang */
const shownItems = computed(() => {
  const source = filtered.value.length ? filtered.value : items.value
  const start = (page.value - 1) * pageSize.value
  const end = start + pageSize.value
  return source.slice(start, end)
})

const totalPages = computed(() => {
  const source = filtered.value.length ? filtered.value : items.value
  return Math.ceil(source.length / pageSize.value) || 1
})

/* Utils */
const getId = (p) => p._id || p.id || p?.doc?._id

/* Navigate home */
function goHome () {
  window.location.href = '/#/home'
}

/* Pagination helpers */
function getPageNumbers () {
  const totalPagesValue = totalPages.value
  const current = page.value
  const pages = []

  if (totalPagesValue <= 7) {
    for (let i = 1; i <= totalPagesValue; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 4) {
      pages.push(1, 2, 3, 4, 5, '...', totalPagesValue)
    } else if (current >= totalPagesValue - 3) {
      pages.push(1, '...', totalPagesValue - 4, totalPagesValue - 3, totalPagesValue - 2, totalPagesValue - 1, totalPagesValue)
    } else {
      pages.push(1, '...', current - 1, current, current + 1, '...', totalPagesValue)
    }
  }
  return pages
}

function goToPage (pageNum) {
  if (pageNum !== '...' && pageNum !== page.value) {
    page.value = pageNum
  }
}

function nextPage () {
  if (page.value < totalPages.value) {
    page.value++
  }
}

function prevPage () {
  if (page.value > 1) {
    page.value--
  }
}

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
  page.value = 1 // Reset về trang đầu khi filter
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
@import 'bootstrap-icons/font/bootstrap-icons.css';

/* Main Container */
.patients-management {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 0;
  margin: 0;
  font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Header Section */
.header-section {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  padding: 2rem 3rem;
  box-shadow: 0 4px 20px rgba(59, 130, 246, 0.15);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1400px;
  margin: 0 auto;
}

.header-left .page-title {
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.header-left .page-title i {
  font-size: 1.75rem;
  color: #dbeafe;
}

.header-left .page-subtitle {
  font-size: 1rem;
  color: #bfdbfe;
  margin: 0;
  opacity: 0.9;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stats-badge {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  padding: 0.75rem 1rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.stats-badge i {
  font-size: 1.1rem;
  color: #dbeafe;
}

.btn-action {
  padding: 0.75rem 1.25rem;
  border-radius: 10px;
  border: none;
  font-weight: 600;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  cursor: pointer;
  text-decoration: none;
}

.btn-refresh {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 0.75rem;
}

.btn-refresh:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.05);
}

/* Add New Button Custom Style */
/* Đảm bảo nút Thêm mới có chiều rộng bằng với select */
/* Đảm bảo nút Thêm mới có chiều rộng đúng bằng select */
/* Đảm bảo chữ Thêm mới không bị xuống dòng */
.btn-add-new {
  background: #fff;
  color: #2563eb;
  border: 2px solid #2563eb;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.9rem;
  padding: 0.75rem 2.5rem 0.75rem 1rem;
  min-width: 120px;
  width: 120px;
  min-height: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  box-shadow: none;
  transition: all 0.2s;
  white-space: nowrap;
}
.btn-add-new i {
  font-size: 1.3rem;
  color: #2563eb;
  transition: color 0.2s;
}
.btn-add-new:hover:not(:disabled) {
  background: #2563eb;
  color: #fff;
  box-shadow: 0 2px 8px rgba(37,99,235,0.10);
  transform: translateY(-1px) scale(1.04);
}
.btn-add-new:hover:not(:disabled) i {
  color: #fff;
}

.btn-back {
  background: linear-gradient(135deg, #64748b 0%, #3b82f6 100%);
  color: #fff;
  border: none;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.12);
  font-weight: 600;
  transition: all 0.2s;
}

.btn-back:hover:not(:disabled) {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: #fff;
  transform: translateY(-1px);
  box-shadow: 0 4px 16px rgba(59, 130, 246, 0.18);
}

.page-size-select {
  padding: 0.75rem 2.5rem 0.75rem 1rem;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: rgba(255, 255, 255, 0.95);
  color: #2563eb;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  outline: none;
  transition: all 0.3s ease;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%232563eb' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  min-width: 120px;
}

.page-size-select:hover {
  background: white;
  border-color: rgba(255, 255, 255, 0.3);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.page-size-select:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Search Section */
.search-section {
  background: white;
  padding: 2rem 3rem;
  border-bottom: 1px solid #e5e7eb;
}

.search-container {
  max-width: 1400px;
  margin: 0 auto;
}

.search-input-group {
  max-width: 600px;
  position: relative;
  display: flex;
  align-items: center;
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.search-input-group:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  background: white;
}

.search-icon {
  color: #64748b;
  font-size: 1.1rem;
  margin: 0 1rem;
}

.search-input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 1rem 0.5rem;
  font-size: 1rem;
  color: #1e293b;
  outline: none;
}

.search-input::placeholder {
  color: #64748b;
}

.search-btn {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border: none;
  padding: 1rem 1.5rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  cursor: pointer;
}

.search-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
}

/* Content Section */
.content-section {
  padding: 2rem 3rem;
  max-width: 1400px;
  margin: 0 auto;
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 3rem;
  color: #64748b;
  font-size: 1.1rem;
}

.spinner {
  width: 2rem;
  height: 2rem;
  border: 3px solid #e2e8f0;
  border-top: 3px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Table Container */
.table-container {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
}

.patients-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
}

.patients-table thead {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.patients-table th {
  padding: 1.25rem 1rem;
  text-align: left;
  font-weight: 700;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.col-number { width: 80px; text-align: center; }
.col-name { width: 200px; }
.col-phone { width: 140px; }
.col-email { width: 200px; }
.col-gender { width: 100px; }
.col-status { width: 150px; text-align: center; }
.col-actions { width: 140px; text-align: center; }

.patient-row {
  transition: all 0.3s ease;
  border-bottom: 1px solid #f1f5f9;
}

.patient-row:hover {
  background: #f8fafc;
}

.patient-row.expanded {
  background: #eff6ff;
}

.patients-table td {
  padding: 1.25rem 1rem;
  vertical-align: middle;
  color: #374151;
}

.cell-number {
  text-align: center;
}

.row-number {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.85rem;
  display: inline-block;
  min-width: 2.5rem;
}

.patient-name strong {
  color: #1e293b;
  font-weight: 600;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.85rem;
  border: 1px solid;
  display: inline-flex;
}

.status-active {
  background: #f0fdf4;
  color: #166534;
  border-color: #bbf7d0;
}

.status-inactive {
  background: #f8fafc;
  color: #64748b;
  border-color: #e2e8f0;
}

.action-buttons {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
}

.action-btn {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 8px;
  border: 1px solid;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  cursor: pointer;
  font-size: 1rem;
}

.view-btn {
  color: #3b82f6;
  border-color: #bfdbfe;
}

.view-btn:hover:not(:disabled) {
  background: #eff6ff;
  color: #1d4ed8;
  transform: scale(1.1);
}

.edit-btn {
  color: #f59e0b;
  border-color: #fed7aa;
}

.edit-btn:hover:not(:disabled) {
  background: #fffbeb;
  color: #d97706;
  transform: scale(1.1);
}

.delete-btn {
  color: #ef4444;
  border-color: #fecaca;
}

.delete-btn:hover:not(:disabled) {
  background: #fef2f2;
  color: #dc2626;
  transform: scale(1.1);
}

.delete-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Detail Row */
.detail-row td {
  padding: 0;
  background: #eff6ff;
  border-top: 2px solid #bfdbfe;
}

.detail-cell {
  padding: 0 !important;
}

.patient-detail-card {
  margin: 2rem;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
}

.detail-header {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  padding: 1.5rem 2rem;
}

.detail-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.detail-content {
  padding: 2rem;
}

.detail-section {
  margin-bottom: 2rem;
}

.detail-section:last-child {
  margin-bottom: 0;
}

.section-title {
  color: #374151;
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e5e7eb;
}

.section-title i {
  color: #3b82f6;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-item.full-width {
  grid-column: 1 / -1;
}

.info-item label {
  color: #64748b;
  font-weight: 600;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-value {
  color: #1e293b;
  font-weight: 500;
}

.info-value code {
  background: #eff6ff;
  color: #1d4ed8;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  font-weight: 600;
}

/* Empty State */
.empty-row td {
  padding: 4rem 2rem;
  text-align: center;
}

.empty-state {
  color: #64748b;
}

.empty-state i {
  font-size: 4rem;
  color: #cbd5e1;
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.empty-state p {
  font-size: 1rem;
  margin: 0;
}

/* Material style pagination */
.material-pagination-info {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  font-size: 1.08rem;
  color: #64748b;
  font-weight: 500;
  gap: 0.5rem;
  margin-bottom: 1.2rem;
}
.material-pagination-info i {
  color: #3b82f6;
  font-size: 1.3rem;
}
.material-pagination-controls {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}
/* Material pagination buttons: square style */
.material-pagination-btn {
  width: 44px;
  height: 44px;
  border: none;
  background: #fff;
  color: #2563eb;
  border-radius: 12px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.18rem;
  margin: 0 0.1rem;
  box-shadow: 0 2px 12px rgba(37,99,235,0.08);
  transition: background 0.18s, color 0.18s, box-shadow 0.18s;
  cursor: pointer;
  outline: none;
  position: relative;
}
.material-pagination-btn.active {
  background: #2563eb;
  color: #fff;
  box-shadow: 0 4px 16px rgba(37,99,235,0.18);
  z-index: 1;
}

.material-pagination-btn.ellipsis {
  background: transparent;
  color: #b0b6be;
  cursor: default;
  box-shadow: none;
}
.material-pagination-btn:disabled {
  background: #f3f4f6;
  color: #b0b6be;
  cursor: not-allowed;
  box-shadow: none;
  opacity: 0.7;
}
.material-pagination-btn:hover:not(:disabled):not(.active):not(.ellipsis) {
  background: #e0e7ff;
  color: #2563eb;
  box-shadow: 0 2px 12px rgba(37,99,235,0.13);
}

/* Responsive Design */
@media (max-width: 1200px) {
  .header-content {
    flex-direction: column;
    gap: 1.5rem;
    align-items: flex-start;
  }

  .header-actions {
    align-self: stretch;
    justify-content: space-between;
    flex-wrap: wrap;
  }
}

@media (max-width: 768px) {
  .header-section {
    padding: 1.5rem 1rem;
  }

  .search-section {
    padding: 1.5rem 1rem;
  }

  .content-section {
    padding: 1.5rem 1rem;
  }

  .patients-table {
    font-size: 0.85rem;
  }

  .patients-table th,
  .patients-table td {
    padding: 1rem 0.5rem;
  }

  .action-buttons {
    flex-direction: column;
    gap: 0.25rem;
  }
}
</style>
