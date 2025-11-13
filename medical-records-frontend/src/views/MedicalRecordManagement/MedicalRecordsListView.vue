<template>
  <div>
    <section class="medical-records-management">
      <!-- Header Section -->
      <div class="header-section">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="bi bi-file-medical"></i>
            Quản lý Hồ sơ khám
          </h1>
          <p class="page-subtitle">Quản lý hồ sơ khám bệnh và điều trị</p>
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
        <div class="search-input-group">
          <i class="bi bi-search search-icon"></i>
          <input
            v-model.trim="q"
            class="search-input"
            placeholder="Tìm theo loại / lý do / bác sĩ / bệnh nhân…"
            @keyup.enter="search"
          />
          <button class="search-btn" @click="search" :disabled="loading">
            <i class="bi bi-search"></i>
            Tìm kiếm
          </button>
        </div>
      </div>
    </div>

    <!-- Content Section -->
    <div class="content-section">
      <div v-if="error" class="alert alert-error">
        <i class="bi bi-exclamation-triangle"></i>
        {{ error }}
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <span>Đang tải danh sách...</span>
      </div>

      <template v-else>
        <div class="table-container">
          <table class="medical-records-table">
            <thead>
              <tr>
                <th class="col-number">#</th>
                <th class="col-date">Ngày khám</th>
                <th class="col-patient">Bệnh nhân</th>
                <th class="col-doctor">Bác sĩ</th>
                <th class="col-type">Loại khám</th>
                <th class="col-status">Trạng thái</th>
                <th class="col-actions">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(r, idx) in filteredItems">
                <tr class="record-row" :class="{ 'expanded': isExpanded(r) }" :key="rowKey(r, idx)">
                  <td class="cell-number">
                    <span class="row-number">{{ idx + 1 + (page - 1) * pageSize }}</span>
                  </td>
                  <td class="cell-date">
                    <div class="date-info">
                      <i class="bi bi-calendar3"></i>
                      <span>{{ fmtDateTime(r.visit_date) }}</span>
                    </div>
                  </td>
                  <td class="cell-patient">
                    <div class="patient-info">
                      <i class="bi bi-person-fill"></i>
                      <strong>{{ displayName(patientsMap[r.patient_id]) || r.patient_id }}</strong>
                    </div>
                  </td>
                  <td class="cell-doctor">
                    <div class="doctor-info">
                      <i class="bi bi-person-badge"></i>
                      <span>{{ displayName(doctorsMap[r.doctor_id]) || r.doctor_id }}</span>
                    </div>
                  </td>
                  <td class="cell-type">
                    <span class="type-text">{{ r.visit_type }}</span>
                  </td>
                  <td class="cell-status">
                    <span class="status-badge" :class="statusClass(r.status)">
                      <i :class="statusIcon(r.status)"></i>
                      {{ r.status || '-' }}
                    </span>
                  </td>
                  <td class="cell-actions">
                    <div class="action-buttons">
                      <button
                        class="action-btn view-btn"
                        @click="toggleRow(r)"
                        :title="isExpanded(r) ? 'Ẩn chi tiết' : 'Xem chi tiết'"
                      >
                        <i :class="isExpanded(r) ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                      </button>
                      <button
                        class="action-btn invoice-btn"
                        @click="createInvoiceFromRecord(r)"
                        :disabled="loading"
                        title="Tạo hóa đơn"
                      >
                        <i class="bi bi-receipt"></i>
                      </button>
                      <button class="action-btn edit-btn" @click="openEdit(r)" title="Sửa">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="action-btn delete-btn" @click="remove(r)" :disabled="loading" title="Xóa">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>

                <!-- DETAILS xổ khi bấm Xem -->
                <tr v-if="isExpanded(r)" class="detail-row" :key="'detail-' + rowKey(r, idx)">
                  <td :colspan="7">
                    <div class="detail-wrap">
                      <div class="detail-title">Thông tin khám</div>
                      <div class="detail-grid">
                        <div><b>Loại:</b> {{ r.visit_type || '-' }}</div>
                        <div><b>Ngày khám:</b> {{ fmtDateTime(r.visit_date) }}</div>
                        <div><b>Lý do:</b> {{ r.chief_complaint || '-' }}</div>
                        <div><b>Lịch hẹn:</b> {{ r.appointment_id || '-' }}</div>
                      </div>

                      <div class="detail-title">Tình trạng</div>
                      <div class="detail-grid">
                        <div><b>Nhiệt độ:</b> {{ r.vital.temperature ?? '-' }} °C</div>
                        <div><b>HA:</b> {{ r.vital.bp_systolic ?? '-' }}/{{ r.vital.bp_diastolic ?? '-' }} mmHg</div>
                        <div><b>Mạch:</b> {{ r.vital.heart_rate ?? '-' }} bpm</div>
                        <div><b>Nhịp thở:</b> {{ r.vital.respiratory_rate ?? '-' }} lần/phút</div>
                        <div><b>Cân nặng:</b> {{ r.vital.weight ?? '-' }} kg</div>
                        <div><b>Chiều cao:</b> {{ r.vital.height ?? '-' }} cm</div>
                      </div>

                      <div class="detail-title">Khám thực thể</div>
                      <div class="detail-grid">
                        <div class="col-span-2"><b>Toàn thân:</b> {{ r.physical.general || '-' }}</div>
                        <div class="col-span-2"><b>Tim mạch:</b> {{ r.physical.cardiovascular || '-' }}</div>
                        <div class="col-span-2"><b>Hô hấp:</b> {{ r.physical.respiratory || '-' }}</div>
                        <div class="col-span-2"><b>Khác:</b> {{ r.physical.other_findings || '-' }}</div>
                      </div>

                      <div class="detail-title">Chẩn đoán</div>
                      <div class="detail-grid">
                    <div><b>Chính:</b> ({{ r.dx_primary.code || '-' }}) {{ r.dx_primary.description || '-' }} <i v-if="r.dx_primary.severity">— {{ r.dx_primary.severity }}</i></div>
                    <div><b>Phụ:</b> {{ (r.dx_secondary || []).join(', ') || '-' }}</div>
                    <div><b>Phân biệt:</b> {{ (r.dx_differential || []).join(', ') || '-' }}</div>
                  </div>

                  <div class="detail-title">
                    Điều trị
                    <button
                      v-if="r.medications && r.medications.length > 0"
                      class="btn btn-sm btn-outline-primary ms-2"
                      @click="viewTreatments(r._id || r.id)"
                      title="Xem phác đồ điều trị đầy đủ"
                    >
                      <i class="bi bi-clipboard2-pulse"></i> Xem Treatment
                    </button>
                  </div>
                  <div class="detail-grid">
                    <div class="col-span-2">
                      <b>Thuốc:</b>
                      <ul class="mb-2">
                        <li v-for="(m, i) in r.medications" :key="i">
                          <b>{{ m.name || 'Không có' }}</b> — {{ m.dosage || 'Không có' }}; {{ m.frequency || 'Không có' }}; {{ m.duration || 'Không có' }}
                          <span v-if="m.instructions"> ({{ m.instructions }})</span>
                        </li>
                        <li v-if="!r.medications || !r.medications.length" class="text-muted">Không có</li>
                      </ul>
                    </div>
                    <div><b>Thủ thuật:</b> {{ (r.procedures || []).join(', ') || '-' }}</div>
                    <div><b>Tư vấn lối sống:</b> {{ (r.lifestyle_advice || []).join(', ') || '-' }}</div>
                    <div><b>Tái khám:</b> {{ r.follow_up?.date || 'Không có' }}<span v-if="r.follow_up?.notes"> — {{ r.follow_up.notes }}</span></div>
                  </div>

                  <div v-if="r.test_requests" class="detail-title">
                    Yêu cầu xét nghiệm
                    <button
                      class="btn btn-sm btn-outline-info ms-2"
                      @click="viewTests(r._id || r.id)"
                      title="Xem kết quả xét nghiệm"
                    >
                      <i class="bi bi-file-medical"></i> Xem Test
                    </button>
                  </div>
                  <div v-if="r.test_requests" class="mb-2" style="white-space: pre-wrap;">{{ r.test_requests }}</div>

                  <div class="detail-title">Đính kèm</div>
                  <ul class="mb-2">
                    <li v-for="(a, i) in r.attachments" :key="i">
                      <b>{{ a.type }}</b> — {{ a.file_name }} <span v-if="a.description">({{ a.description }})</span>
                    </li>
                    <li v-if="!r.attachments || !r.attachments.length" class="text-muted">-</li>
                  </ul>

                      <div class="text-muted small mt-2">
                        Tạo: {{ fmtDateTime(r.created_at) }} | Cập nhật: {{ fmtDateTime(r.updated_at) }}
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>

            <tbody v-if="!filteredItems.length">
              <tr>
                <td colspan="7" class="text-center text-muted">Không có dữ liệu</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Section -->
        <div class="pagination-section">
          <div class="pagination-info-row">
            <span class="page-info">
              <i class="bi bi-file-earmark-text"></i>
              Trang <b>{{ page }} / {{ Math.ceil(total / pageSize) || 1 }}</b>
              <span class="total-info">- Hiển thị {{ filteredItems.length }} trong tổng số {{ total }} hồ sơ</span>
            </span>
          </div>
          <div class="pagination-controls">
            <button class="pagination-btn" @click="prev" :disabled="page <= 1 || loading">
              <i class="bi bi-chevron-left"></i>
              Trước
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
              Sau
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </div>
      </template>
    </div>
  </section>

  <!-- MODAL: form đầy đủ + combobox BN/BS -->
  <div v-if="showModal" class="modal-backdrop" @mousedown.self="close">
    <div class="modal-card">
      <h3 class="h6 mb-3">{{ editingId ? 'Sửa hồ sơ' : 'Thêm hồ sơ' }}</h3>

        <!-- ✅ SUC-08: Display previous medical records for follow-up visits -->
        <div v-if="previousRecords.length > 0" class="alert alert-info mb-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <strong><i class="bi bi-clock-history"></i> Lịch sử khám bệnh ({{ previousRecords.length }} lần)</strong>
            <button type="button" class="btn btn-sm btn-outline-info" @click="togglePreviousRecords">
              {{ showPreviousRecords ? 'Ẩn' : 'Xem' }}
            </button>
          </div>

          <div v-if="showPreviousRecords" class="previous-records-list">
            <div
              v-for="(prev, idx) in previousRecords.slice(0, 5)"
              :key="prev._id || idx"
              class="previous-record-item"
            >
              <div class="d-flex justify-content-between">
                <div>
                  <strong>{{ fmtDateTime(prev.visit_date) }}</strong>
                  <span class="text-muted ms-2">{{ prev.visit_type }}</span>
                </div>
                <span :class="['badge', statusClass(prev.status)]">{{ prev.status }}</span>
              </div>
              <div class="small text-muted mt-1">
                <strong>Chẩn đoán:</strong> {{ prev.diagnosis?.primary_diagnosis?.description || '-' }}
              </div>
              <div class="small text-muted">
                <strong>Thuốc:</strong>
                <span v-if="prev.treatment_plan?.medications?.length > 0">
                  {{ prev.treatment_plan.medications.map(m => m.name).join(', ') }}
                </span>
                <span v-else>-</span>
              </div>
            </div>
            <div v-if="previousRecords.length > 5" class="text-muted small mt-2">
              <i>Hiển thị 5/{{ previousRecords.length }} lần khám gần nhất</i>
            </div>
          </div>
        </div>

        <form @submit.prevent="save">
          <!-- Thông tin chung -->
          <div class="section-title">Thông tin chung</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Mã lịch hẹn</label>
              <select v-model="form.appointment_id" class="form-select" @change="onAppointmentChange">
                <option value="">-- Chọn lịch hẹn --</option>
                <option v-for="a in appointmentOptions" :key="a.value" :value="a.value">{{ a.label }}</option>
              </select>
              <small class="text-muted">Chọn lịch hẹn để tự động điền bệnh nhân và bác sĩ</small>
            </div>
            <div class="col-md-3">
              <label class="form-label">Loại khám</label>
              <select v-model="form.visit_type" class="form-select">
                <option value="">-- Chọn loại --</option>
                <option value="consultation">Tư vấn</option>
                <option value="follow_up">Tái khám</option>
                <option value="checkup">Khám sức khỏe</option>
                <option value="emergency">Cấp cứu</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Trạng thái</label>
              <select v-model="form.status" class="form-select">
                <option value="draft">Nháp</option>
                <option value="in_progress">Đang khám</option>
                <option value="completed">Hoàn thành</option>
                <option value="canceled">Đã hủy</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Bệnh nhân <span class="text-danger">*</span></label>
              <select v-model="form.patient_id" class="form-select" required>
                <option value="">-- Chọn bệnh nhân --</option>
                <option v-for="p in patientOptions" :key="p.value" :value="p.value">{{ p.label }}</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Bác sĩ <span class="text-danger">*</span></label>
              <select v-model="form.doctor_id" class="form-select" required>
                <option value="">-- Chọn bác sĩ --</option>
                <option v-for="d in doctorOptions" :key="d.value" :value="d.value">{{ d.label }}</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Ngày khám <span class="text-danger">*</span></label>
              <input v-model="form.visit_date" type="datetime-local" class="form-control" required />
            </div>

            <div class="col-12">
              <label class="form-label">Lý do khám</label>
              <textarea v-model.trim="form.chief_complaint" class="form-control" rows="2" placeholder="Mô tả lý do đến khám..."></textarea>
            </div>
          </div>

          <!-- Tình trạng -->
          <div class="section-title">Tình trạng</div>
          <div class="row g-3">
            <div class="col-md-2">
              <label class="form-label">Nhiệt độ (°C)</label>
              <input v-model.number="form.vital.temperature" type="number" step="0.1" class="form-control"/>
            </div>
            <div class="col-md-2">
              <label class="form-label">HA tâm thu</label>
              <input v-model.number="form.vital.bp_systolic" type="number" class="form-control"/>
            </div>
            <div class="col-md-2">
              <label class="form-label">HA tâm trương</label>
              <input v-model.number="form.vital.bp_diastolic" type="number" class="form-control"/>
            </div>
            <div class="col-md-2">
              <label class="form-label">Mạch (bpm)</label>
              <input v-model.number="form.vital.heart_rate" type="number" class="form-control"/>
            </div>
            <div class="col-md-2">
              <label class="form-label">Nhịp thở</label>
              <input v-model.number="form.vital.respiratory_rate" type="number" class="form-control"/>
            </div>
            <div class="col-md-2">
              <label class="form-label">Cân nặng (kg)</label>
              <input v-model.number="form.vital.weight" type="number" step="0.1" class="form-control"/>
            </div>
            <div class="col-md-2">
              <label class="form-label">Chiều cao (cm)</label>
              <input v-model.number="form.vital.height" type="number" class="form-control"/>
            </div>
          </div>

          <!-- Khám thực thể -->
          <div class="section-title">Khám thực thể</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Toàn thân</label>
              <textarea v-model.trim="form.physical.general" rows="2" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Tim mạch</label>
              <textarea v-model.trim="form.physical.cardiovascular" rows="2" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Hô hấp</label>
              <textarea v-model.trim="form.physical.respiratory" rows="2" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Khác</label>
              <textarea v-model.trim="form.physical.other_findings" rows="2" class="form-control" />
            </div>
          </div>

          <!-- Chẩn đoán -->
          <div class="section-title">Chẩn đoán</div>
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Mã chính (ICD)</label>
              <input v-model.trim="form.dx_primary.code" class="form-control"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">Mô tả chính</label>
              <input v-model.trim="form.dx_primary.description" class="form-control"/>
            </div>
            <div class="col-md-3">
              <label class="form-label">Mức độ</label>
              <input v-model.trim="form.dx_primary.severity" class="form-control" placeholder="mild/moderate/severe"/>
            </div>

            <div class="col-md-6">
              <label class="form-label">Chẩn đoán phụ (ngăn bởi dấu phẩy)</label>
              <input v-model.trim="form.dx_secondary_text" class="form-control" placeholder="ĐTĐ type 2, RL lipid máu…"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">Chẩn đoán phân biệt (dấu phẩy)</label>
              <input v-model.trim="form.dx_differential_text" class="form-control" placeholder="Bệnh mạch vành, Rối loạn lo âu…"/>
            </div>
          </div>

          <!-- Điều trị -->
          <div class="section-title">Điều trị</div>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th style="width:24%">Tên thuốc</th>
                  <th style="width:14%">Liều</th>
                  <th style="width:18%">Số lần/ngày</th>
                  <th style="width:14%">Thời gian</th>
                  <th style="width:26%">Hướng dẫn</th>
                  <th style="width:4%"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(m, i) in form.medications" :key="i">
                  <td class="position-relative">
                    <input
                      v-model.trim="m.name"
                      class="form-control form-control-sm"
                      placeholder="Nhập tên thuốc (gợi ý tự động)..."
                      @input="m._showSuggestions = filterMedications(m.name, i)"
                      @focus="m._showSuggestions = filterMedications(m.name, i)"
                      @blur="hideSuggestions(i)"
                    />
                    <!-- Autocomplete dropdown -->
                    <div v-if="m._showSuggestions && m._showSuggestions.length" class="autocomplete-dropdown">
                      <div
                        v-for="(med, idx) in m._showSuggestions"
                        :key="idx"
                        class="autocomplete-item"
                        @mousedown.prevent="selectMedication(med, i)"
                      >
                        <strong>{{ med.name }}</strong> {{ med.strength }} <span class="text-muted">({{ med.dosage_form }})</span>
                      </div>
                    </div>
                  </td>
                  <td><input v-model.trim="m.dosage" class="form-control form-control-sm" placeholder="5mg"/></td>
                  <td><input v-model.trim="m.frequency" class="form-control form-control-sm" placeholder="1 lần/ngày"/></td>
                  <td><input v-model.trim="m.duration" class="form-control form-control-sm" placeholder="30 ngày"/></td>
                  <td><input v-model.trim="m.instructions" class="form-control form-control-sm" placeholder="Uống sau ăn sáng"/></td>
                  <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" @click="removeMedication(i)">×</button></td>
                </tr>
                <tr v-if="!form.medications.length">
                  <td colspan="6" class="text-muted small">Chưa có thuốc — bấm “+ Thêm thuốc”</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="d-flex gap-2 flex-wrap mb-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="addMedication">+ Thêm thuốc</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="addProcedure">+ Thêm thủ thuật</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="addLifestyle">+ Thêm tư vấn</button>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Thủ thuật (dấu phẩy)</label>
              <input v-model.trim="form.procedures_text" class="form-control" placeholder="ECG, Siêu âm tim…"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tư vấn lối sống (dấu phẩy)</label>
              <input v-model.trim="form.lifestyle_text" class="form-control" placeholder="Giảm muối, Tập thể dục…"/>
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Ngày tái khám</label>
              <input v-model="form.follow_up.date" type="date" class="form-control" />
            </div>
            <div class="col-md-8">
              <label class="form-label">Ghi chú tái khám</label>
              <input v-model.trim="form.follow_up.notes" class="form-control" />
            </div>
          </div>

          <!-- Yêu cầu xét nghiệm -->
          <div class="section-title">Yêu cầu xét nghiệm</div>
          <div class="mb-3">
            <textarea
              v-model.trim="form.test_requests"
              class="form-control"
              rows="3"
              placeholder="Ví dụ: Xét nghiệm máu: Công thức máu, Đường huyết, Lipid máu&#10;Chẩn đoán hình ảnh: X-quang ngực, Siêu âm bụng"
            ></textarea>
            <small class="text-muted">Liệt kê các xét nghiệm cần làm (mỗi loại một dòng)</small>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary" @click="close">Hủy</button>
            <button class="btn btn-primary" type="submit" :disabled="saving">{{ saving ? 'Đang lưu…' : 'Lưu' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import MedicalRecordService from '@/api/medicalRecordService'
import DoctorService from '@/api/doctorService'
import PatientService from '@/api/patientService'
import AppointmentService from '@/api/appointmentService'
import InvoiceService from '@/api/invoiceService'
import MedicationService from '@/api/medicationService'
import TreatmentService from '@/api/treatmentService'
import MedicalTestService from '@/api/medicalTestService'

export default {
  name: 'MedicalRecordsListView',
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
      // combobox + map để render tên
      doctorOptions: [],
      patientOptions: [],
      appointmentOptions: [],
      doctorsMap: {},
      patientsMap: {},
      appointmentsMap: {},
      optionsLoaded: false,
      filteredItems: [],
      // ✅ Medication autocomplete
      allMedications: [],
      medicationsMap: {},
      // ✅ SUC-08: Previous records for follow-up visits
      previousRecords: [],
      showPreviousRecords: true
    }
  },
  created () { this.fetch() },
  computed: {
    visiblePages () {
      const total = Math.max(1, Math.ceil((this.total || 0) / this.pageSize))
      const current = this.page
      const delta = 2
      const range = []
      const rangeWithDots = []

      for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
        range.push(i)
      }

      if (current - delta > 2) {
        rangeWithDots.push(1, '...')
      } else {
        rangeWithDots.push(1)
      }

      rangeWithDots.push(...range)

      if (current + delta < total - 1) {
        rangeWithDots.push('...', total)
      } else if (total > 1) {
        rangeWithDots.push(total)
      }

      return rangeWithDots
    }
  },
  watch: {
    items () {
      this.applyFilter()
    },
    q () {
      this.applyFilter()
    }
  },
  methods: {
    /* ===== helpers ===== */
    rowKey (r, idx) { return r._id || r.id || `${idx}` },
    isExpanded (row) { return !!this.expanded[this.rowKey(row, 0)] },
    toggleRow (row) {
      const k = this.rowKey(row, 0)
      this.expanded = { ...this.expanded, [k]: !this.expanded[k] }
    },
    fmtDateTime (v) {
      if (!v) return '-'
      try {
        return new Date(v).toLocaleString()
      } catch {
        return v
      }
    },
    statusClass (s) {
      if (s === 'completed') return 'status-completed'
      if (s === 'in_progress') return 'status-in-progress'
      if (s === 'canceled') return 'status-canceled'
      return 'status-draft'
    },
    statusIcon (s) {
      if (s === 'completed') return 'bi bi-check-circle-fill'
      if (s === 'in_progress') return 'bi bi-clock-fill'
      if (s === 'canceled') return 'bi bi-x-circle-fill'
      return 'bi bi-file-earmark-fill'
    },
    displayName (o) { return o?.full_name || o?.name || o?.display_name || o?.code || o?.username },
    changePageSize () {
      this.page = 1
      this.fetch()
    },
    goToPage (p) {
      if (p !== this.page && p !== '...') {
        this.page = p
        this.fetch()
      }
    },

    // Flatten document nested -> flat fields cho list/details/form
    flattenRecord (d = {}) {
      const vi = d.visit_info || {}
      const ex = d.examination || {}
      const vs = ex.vital_signs || {}
      const pe = ex.physical_exam || {}
      const dx = d.diagnosis || {}
      const tp = d.treatment_plan || {}

      return {
        ...d,
        patient_id: d.patient_id || '',
        doctor_id: d.doctor_id || '',
        // visit
        visit_date: vi.visit_date || '',
        visit_type: vi.visit_type || '',
        chief_complaint: vi.chief_complaint || '',
        appointment_id: vi.appointment_id || '',
        // vitals
        vital: {
          temperature: vs.temperature ?? null,
          bp_systolic: vs.blood_pressure?.systolic ?? null,
          bp_diastolic: vs.blood_pressure?.diastolic ?? null,
          heart_rate: vs.heart_rate ?? null,
          respiratory_rate: vs.respiratory_rate ?? null,
          weight: vs.weight ?? null,
          height: vs.height ?? null
        },
        // physical
        physical: {
          general: pe.general || '',
          cardiovascular: pe.cardiovascular || '',
          respiratory: pe.respiratory || '',
          other_findings: pe.other_findings || ''
        },
        // diagnosis
        dx_primary: {
          code: dx.primary?.code || '',
          description: dx.primary?.description || '',
          severity: dx.primary?.severity || ''
        },
        dx_secondary: dx.secondary || [],
        dx_differential: dx.differential || [],
        // treatment
        medications: (tp.medications || []).map(m => ({
          name: m.name || '',
          dosage: m.dosage || '',
          frequency: m.frequency || '',
          duration: m.duration || '',
          instructions: m.instructions || ''
        })),
        procedures: tp.procedures || [],
        lifestyle_advice: tp.lifestyle_advice || [],
        follow_up: {
          date: tp.follow_up?.date || '',
          notes: tp.follow_up?.notes || ''
        },
        test_requests: d.test_requests || '',
        // others
        attachments: d.attachments || [],
        status: d.status || 'draft',
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
        visit_date: '',
        visit_type: '',
        chief_complaint: '',
        appointment_id: '',
        vital: { temperature: null, bp_systolic: null, bp_diastolic: null, heart_rate: null, respiratory_rate: null, weight: null, height: null },
        physical: { general: '', cardiovascular: '', respiratory: '', other_findings: '' },
        dx_primary: { code: '', description: '', severity: '' },
        dx_secondary: [],
        dx_differential: [],
        dx_secondary_text: '',
        dx_differential_text: '',
        medications: [],
        procedures: [],
        procedures_text: '',
        lifestyle_advice: [],
        lifestyle_text: '',
        follow_up: { date: '', notes: '' },
        test_requests: '',
        attachments: [],
        status: 'draft',
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
        const res = await MedicalRecordService.list({ q: this.q || undefined, limit: this.pageSize, offset: skip, skip })
        let raw = []; let total = 0; let offset = null
        if (res && Array.isArray(res.rows)) {
          raw = res.rows.map(r => r.doc || r.value || r)
          total = res.total_rows ?? raw.length
          offset = res.offset ?? 0
        } else if (res && Array.isArray(res.data)) {
          raw = res.data; total = res.total ?? raw.length
        } else if (Array.isArray(res)) { raw = res; total = raw.length }

        this.items = (raw || []).map(d => this.flattenRecord(d))
        this.total = total
        this.hasMore = (offset != null)
          ? (offset + this.items.length) < (this.total || 0)
          : (this.page * this.pageSize) < (this.total || 0)

        // nạp options 1 lần để hiển thị tên trong list
        await this.ensureOptionsLoaded()
      } catch (e) {
        console.error(e)
        this.error = e?.response?.data?.message || e?.message || 'Không tải được dữ liệu'
      } finally { this.loading = false }
    },
    search () {
      this.page = 1
      this.applyFilter()
    },
    applyFilter () {
      const q = (this.q || '').toLowerCase().trim()
      if (!q) {
        this.filteredItems = [...this.items]
        return
      }
      this.filteredItems = this.items.filter(r => {
        const patient = this.displayName(this.patientsMap[r.patient_id]) || r.patient_id || ''
        const doctor = this.displayName(this.doctorsMap[r.doctor_id]) || r.doctor_id || ''
        return (
          (r.visit_type && r.visit_type.toLowerCase().includes(q)) ||
          (r.chief_complaint && r.chief_complaint.toLowerCase().includes(q)) ||
          (doctor && doctor.toLowerCase().includes(q)) ||
          (patient && patient.toLowerCase().includes(q))
        )
      })
    },
    reload () { this.fetch() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },

    /* ===== combobox ===== */
    async ensureOptionsLoaded () {
      if (this.optionsLoaded) return
      try {
        const [dRes, pRes, aRes, mRes] = await Promise.all([
          DoctorService.list({ limit: 1000 }),
          PatientService.list({ limit: 1000 }),
          AppointmentService.list({ limit: 1000 }),
          MedicationService.list({ limit: 500 })
        ])

        const arr = (r) => {
          if (Array.isArray(r?.rows)) {
            return r.rows.map(x => x.doc || x.value || x)
          } else if (Array.isArray(r?.data)) {
            return r.data
          } else if (Array.isArray(r)) {
            return r
          } else {
            return []
          }
        }

        const dList = arr(dRes)
        const pList = arr(pRes)
        const aList = arr(aRes)

        const key = (o) => o._id || o.id || o.code || o.username
        const label = (o) => o?.personal_info?.full_name || o.full_name || o.name || o.display_name || o.code || o.username || key(o)

        this.doctorOptions = dList.map(o => ({
          value: key(o),
          label: label(o)
        }))
        this.patientOptions = pList.map(o => ({
          value: key(o),
          label: label(o)
        }))

        // Create appointment options with patient and doctor names
        this.appointmentOptions = aList.map(apt => {
          const patient = pList.find(p => key(p) === apt.patient_id)
          const doctor = dList.find(d => key(d) === apt.doctor_id)
          const scheduledDate = apt.appointment_info?.scheduled_date || apt.scheduled_date
          const dateStr = scheduledDate ? new Date(scheduledDate).toLocaleString('vi-VN') : ''

          return {
            value: key(apt),
            label: `${dateStr} - ${label(patient)} - ${label(doctor)}`,
            patient_id: apt.patient_id,
            doctor_id: apt.doctor_id
          }
        })

        // map để render tên trong list/details
        this.doctorsMap = {}
        dList.forEach(o => {
          this.doctorsMap[key(o)] = o
        })

        this.patientsMap = {}
        pList.forEach(o => {
          this.patientsMap[key(o)] = o
        })

        this.appointmentsMap = {}
        aList.forEach(o => {
          this.appointmentsMap[key(o)] = o
        })

        // ✅ Load medications for autocomplete
        const mList = arr(mRes)
        this.allMedications = mList.map(med => {
          const info = med.medication_info || {}
          return {
            id: key(med),
            name: info.name || '',
            strength: info.strength || '',
            dosage_form: info.dosage_form || '',
            label: `${info.name} ${info.strength} (${info.dosage_form})`
          }
        })

        this.medicationsMap = {}
        mList.forEach(o => {
          this.medicationsMap[key(o)] = o
        })

        this.optionsLoaded = true
      } catch (e) {
        console.error(e)
        this.doctorOptions = []
        this.patientOptions = []
        this.appointmentOptions = []
        this.allMedications = []
      }
    },

    // Auto-fill patient, doctor, visit_date and chief_complaint from selected appointment
    onAppointmentChange () {
      const aptId = this.form.appointment_id
      if (!aptId) return

      const selectedApt = this.appointmentOptions.find(opt => opt.value === aptId)
      const aptData = this.appointmentsMap[aptId]

      if (selectedApt) {
        this.form.patient_id = selectedApt.patient_id
        this.form.doctor_id = selectedApt.doctor_id
      }

      if (aptData) {
        // Auto-fill visit date from appointment scheduled_date
        const scheduledDate = aptData.appointment_info?.scheduled_date || aptData.scheduled_date
        if (scheduledDate) {
          // Convert to datetime-local format (YYYY-MM-DDTHH:mm)
          this.form.visit_date = new Date(scheduledDate).toISOString().slice(0, 16)
        }

        // Auto-fill chief_complaint from appointment reason
        const reason = aptData.reason || aptData.appointment_info?.reason
        if (reason) {
          this.form.chief_complaint = reason
        }

        // Auto-fill visit_type from appointment type
        const type = aptData.appointment_info?.type || aptData.type
        if (type) {
          this.form.visit_type = type
        }
      }
    },

    /* ===== modal ===== */
    openCreate () {
      this.editingId = null
      this.form = this.emptyForm()
      this.previousRecords = []
      this.showModal = true
      this.ensureOptionsLoaded()
    },
    async openEdit (row) {
      const f = this.flattenRecord(row)
      this.editingId = f._id || f.id
      this.form = {
        ...this.emptyForm(),
        ...f,
        dx_secondary_text: (f.dx_secondary || []).join(', '),
        dx_differential_text: (f.dx_differential || []).join(', '),
        procedures_text: (f.procedures || []).join(', '),
        lifestyle_text: (f.lifestyle_advice || []).join(', '),
        // datetime-local cần kiểu "YYYY-MM-DDTHH:mm"
        visit_date: f.visit_date ? new Date(f.visit_date).toISOString().slice(0, 16) : '',
        // ✅ FIX: Giữ lại test_requests khi edit
        test_requests: f.test_requests || ''
      }
      this.showModal = true
      this.ensureOptionsLoaded()

      // ✅ SUC-08: Load previous records for this patient
      if (f.patient_id) {
        await this.loadPreviousRecords(f.patient_id, f._id || f.id)
      }
    },
    close () {
      if (!this.saving) {
        this.showModal = false
        this.previousRecords = []
      }
    },

    // ✅ SUC-08: Toggle previous records visibility
    togglePreviousRecords () {
      this.showPreviousRecords = !this.showPreviousRecords
    },

    // ✅ SUC-08: Load previous medical records for patient
    async loadPreviousRecords (patientId, currentRecordId) {
      try {
        // Query medical records for this patient
        const response = await MedicalRecordService.list({
          patient_id: patientId,
          limit: 10,
          sort: '-visit_date' // Sort by visit date descending
        })

        // Filter out current record and keep only previous ones
        this.previousRecords = (response.data || [])
          .filter(r => (r._id || r.id) !== currentRecordId)
          .slice(0, 5)
      } catch (e) {
        console.error('Failed to load previous records:', e)
        this.previousRecords = []
      }
    },

    addMedication () { this.form.medications = [...this.form.medications, { name: '', dosage: '', frequency: '', duration: '', instructions: '', medication_id: '' }] },
    removeMedication (i) { this.form.medications = this.form.medications.filter((_, idx) => idx !== i) },

    // ✅ Filter medications for autocomplete
    filterMedications (searchText, medIndex) {
      if (!searchText || searchText.length < 2) return []
      const search = searchText.toLowerCase()
      return this.allMedications
        .filter(m => m.name.toLowerCase().includes(search) || m.label.toLowerCase().includes(search))
        .slice(0, 10)
    },

    // ✅ Select medication from autocomplete
    selectMedication (medication, medIndex) {
      this.form.medications[medIndex].name = medication.name
      this.form.medications[medIndex].dosage = medication.strength
      this.form.medications[medIndex].medication_id = medication.id
      this.form.medications[medIndex]._showSuggestions = null
    },

    // ✅ Hide suggestions with delay
    hideSuggestions (medIndex) {
      setTimeout(() => {
        if (this.form.medications[medIndex]) {
          this.form.medications[medIndex]._showSuggestions = null
        }
      }, 200)
    },

    addProcedure () {
      const s = this.form.procedures_text ? `${this.form.procedures_text}, ` : ''
      this.form.procedures_text = `${s}Thủ thuật`
    },
    addLifestyle () {
      const s = this.form.lifestyle_text ? `${this.form.lifestyle_text}, ` : ''
      this.form.lifestyle_text = `${s}Tư vấn`
    },

    // ✅ Create or Update Treatment record automatically from Medical Record medications
    async createOrUpdateTreatmentFromMedications (medicalRecordId) {
      const validMeds = this.form.medications.filter(m => m.name && m.dosage)
      if (validMeds.length === 0) {
        console.log('⚠️ No valid medications to create/update treatment')
        return
      }

      try {
        // Calculate treatment duration from medications
        let maxDurationDays = 30 // default
        validMeds.forEach(m => {
          if (m.duration) {
            const match = m.duration.match(/(\d+)/)
            if (match) {
              const days = parseInt(match[1])
              if (days > maxDurationDays) maxDurationDays = days
            }
          }
        })

        const startDate = new Date(this.form.visit_date || Date.now())
        const endDate = new Date(startDate)
        endDate.setDate(endDate.getDate() + maxDurationDays)

        const treatmentPayload = {
          type: 'treatment',
          medical_record_id: medicalRecordId,
          patient_id: this.form.patient_id,
          doctor_id: this.form.doctor_id,
          treatment_info: {
            treatment_name: `Điều trị ${this.form.dx_primary.description || 'theo đơn'}`,
            start_date: startDate.toISOString(),
            end_date: endDate.toISOString(),
            duration_days: maxDurationDays,
            treatment_type: 'medication'
          },
          medications: validMeds.map(m => ({
            medication_id: m.medication_id || `med_${m.name.toLowerCase().replace(/\s+/g, '_')}`,
            name: m.name,
            dosage: m.dosage,
            frequency: m.frequency || '1 lần/ngày',
            route: 'oral',
            instructions: m.instructions || '',
            quantity_prescribed: parseInt(m.duration?.match(/(\d+)/)?.[1]) || 30
          })),
          monitoring: {
            parameters: ['vital_signs', 'symptoms'],
            frequency: 'weekly',
            next_check: new Date(startDate.getTime() + 7 * 24 * 60 * 60 * 1000).toISOString()
          },
          status: 'active',
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString()
        }

        console.log('🩺 Treatment payload:', treatmentPayload)

        // ✅ Check if treatment already exists for this medical record
        const existingTreatments = await TreatmentService.list({
          medical_record_id: medicalRecordId,
          limit: 1
        })

        let result
        if (existingTreatments?.data?.length > 0) {
          // Update existing treatment
          const existingTreatment = existingTreatments.data[0]
          treatmentPayload._id = existingTreatment._id
          treatmentPayload._rev = existingTreatment._rev
          result = await TreatmentService.update(existingTreatment._id, treatmentPayload)
          console.log('✅ Updated treatment record:', result)
          console.log(`✅ Đã cập nhật Treatment với ${validMeds.length} loại thuốc!`)
        } else {
          // Create new treatment
          result = await TreatmentService.create(treatmentPayload)
          console.log('✅ Created treatment record:', result)
          console.log(`✅ Đã tạo Treatment tự động với ${validMeds.length} loại thuốc!`)
        }
      } catch (e) {
        console.error('❌ Failed to create/update treatment:', e)
        alert(`⚠️ Lưu Medical Record thành công nhưng không tạo được Treatment: ${e.message}`)
      }
    },

    // ✅ Create or Update Medical Test records automatically from test requests (MỖI DÒNG 1 TEST)
    async createOrUpdateMedicalTestFromRequests (medicalRecordId) {
      const testRequests = (this.form.test_requests || '').trim()
      if (!testRequests) {
        console.log('⚠️ No test requests to create/update medical test')
        return
      }

      try {
        // ✅ Parse TỪNG DÒNG thành 1 test riêng biệt
        const lines = testRequests.split('\n').filter(l => l.trim())

        if (lines.length === 0) {
          console.log('⚠️ No valid test lines found')
          return
        }

        console.log(`🧪 Creating ${lines.length} medical test(s)...`)

        const orderedDate = new Date(this.form.visit_date || Date.now())
        const createdTests = []

        // ✅ Tạo riêng từng test cho mỗi dòng
        for (const testLine of lines) {
          const testName = testLine.trim()
          if (!testName) continue

          // Determine test type from content
          let testType = 'other'
          const lowerText = testName.toLowerCase()
          if (lowerText.includes('máu') || lowerText.includes('blood') || lowerText.includes('công thức máu') || lowerText.includes('đường huyết') || lowerText.includes('lipid')) {
            testType = 'blood_work'
          } else if (lowerText.includes('nước tiểu') || lowerText.includes('urine')) {
            testType = 'urine'
          } else if (lowerText.includes('x-quang') || lowerText.includes('x-ray') || lowerText.includes('xquang')) {
            testType = 'imaging'
          } else if (lowerText.includes('siêu âm') || lowerText.includes('ultrasound')) {
            testType = 'imaging'
          } else if (lowerText.includes('ct') || lowerText.includes('mri')) {
            testType = 'imaging'
          }

          // ✅ Determine test price based on type
          let unitPrice = 100000 // Default
          if (testType === 'blood_work') {
            unitPrice = 150000 // Blood tests
          } else if (testType === 'imaging') {
            if (lowerText.includes('x-quang') || lowerText.includes('x-ray')) {
              unitPrice = 300000 // X-ray
            } else if (lowerText.includes('siêu âm') || lowerText.includes('ultrasound')) {
              unitPrice = 250000 // Ultrasound
            } else if (lowerText.includes('ct') || lowerText.includes('mri')) {
              unitPrice = 800000 // CT/MRI
            } else {
              unitPrice = 250000 // Default imaging
            }
          } else if (testType === 'urine') {
            unitPrice = 80000 // Urine test
          }

          const testPayload = {
            type: 'medical_test',
            medical_record_id: medicalRecordId,
            patient_id: this.form.patient_id,
            doctor_id: this.form.doctor_id,
            test_info: {
              test_type: testType,
              test_name: testName,
              ordered_date: orderedDate.toISOString(),
              sample_collected_date: null,
              result_date: null,
              unit_price: unitPrice
            },
            results: {},
            interpretation: testName, // Store individual test name
            status: 'pending',
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
          }

          console.log(`🧪 Creating Medical Test: "${testName}"`, testPayload)

          try {
            // Create new test (không check existing nữa để tránh duplicate)
            const result = await MedicalTestService.create(testPayload)
            console.log(`✅ Created medical test: "${testName}"`, result)
            createdTests.push(testName)
          } catch (err) {
            console.error(`❌ Failed to create test "${testName}":`, err)
          }
        }

        // Thông báo tổng kết
        if (createdTests.length > 0) {
          alert(`✅ Đã tạo ${createdTests.length} xét nghiệm:\n${createdTests.map((t, i) => `${i + 1}. ${t}`).join('\n')}`)
        } else {
          alert('⚠️ Không tạo được xét nghiệm nào. Kiểm tra Console (F12).')
        }
      } catch (e) {
        console.error('❌ Failed to create medical tests:', e)
        console.error('❌ Error details:', {
          message: e.message,
          response: e.response?.data,
          status: e.response?.status
        })
        const errorMsg = e.response?.data?.message || e.message || 'Unknown error'
        alert(`⚠️ Lưu Medical Record thành công nhưng không tạo được Medical Test:\n\n${errorMsg}\n\nKiểm tra Console (F12) để xem chi tiết.`)
      }
    },

    /* ===== save/remove ===== */
    async save () {
      if (this.saving) return
      this.saving = true
      try {
        const csv = (s) => (s || '').split(',').map(x => x.trim()).filter(Boolean)

        const payload = {
          type: 'medical_record',
          patient_id: this.form.patient_id || undefined,
          doctor_id: this.form.doctor_id || undefined,

          visit_info: {
            visit_date: this.form.visit_date || undefined,
            visit_type: this.form.visit_type || undefined,
            chief_complaint: this.form.chief_complaint || undefined,
            appointment_id: this.form.appointment_id || undefined
          },

          examination: {
            vital_signs: {
              temperature: this.form.vital.temperature ?? undefined,
              blood_pressure: {
                systolic: this.form.vital.bp_systolic ?? undefined,
                diastolic: this.form.vital.bp_diastolic ?? undefined
              },
              heart_rate: this.form.vital.heart_rate ?? undefined,
              respiratory_rate: this.form.vital.respiratory_rate ?? undefined,
              weight: this.form.vital.weight ?? undefined,
              height: this.form.vital.height ?? undefined
            },
            physical_exam: {
              general: this.form.physical.general || undefined,
              cardiovascular: this.form.physical.cardiovascular || undefined,
              respiratory: this.form.physical.respiratory || undefined,
              other_findings: this.form.physical.other_findings || undefined
            }
          },

          diagnosis: {
            primary: {
              code: this.form.dx_primary.code || undefined,
              description: this.form.dx_primary.description || undefined,
              severity: this.form.dx_primary.severity || undefined
            },
            secondary: this.form.dx_secondary?.length ? this.form.dx_secondary : csv(this.form.dx_secondary_text),
            differential: this.form.dx_differential?.length ? this.form.dx_differential : csv(this.form.dx_differential_text)
          },

          treatment_plan: {
            medications: (this.form.medications || []).map(m => ({
              name: m.name || undefined,
              dosage: m.dosage || undefined,
              frequency: m.frequency || undefined,
              duration: m.duration || undefined,
              instructions: m.instructions || undefined
            })),
            procedures: this.form.procedures?.length ? this.form.procedures : csv(this.form.procedures_text),
            lifestyle_advice: this.form.lifestyle_advice?.length ? this.form.lifestyle_advice : csv(this.form.lifestyle_text),
            follow_up: {
              date: this.form.follow_up?.date || undefined,
              notes: this.form.follow_up?.notes || undefined
            }
          },

          test_requests: this.form.test_requests || undefined,

          status: this.form.status || 'draft'
        }

        if (this.form._id) payload._id = this.form._id
        if (this.form._rev) payload._rev = this.form._rev

        let savedRecord
        if (this.editingId) {
          savedRecord = await MedicalRecordService.update(this.editingId, payload)
        } else {
          savedRecord = await MedicalRecordService.create(payload)
        }

        // ✅ Get saved record ID from response
        const recordId = savedRecord?.data?.id ||
                        savedRecord?.data?._id ||
                        savedRecord?.id ||
                        savedRecord?._id ||
                        this.form._id

        console.log('📋 Saved record ID:', recordId)
        console.log('📋 Saved record response:', savedRecord)

        // ✅ CHỈ TẠO điều trị và test KHI TẠO MỚI record (không phải update)
        if (!this.editingId) {
          // Create Treatment record if medications are prescribed
          const hasValidMedications = this.form.medications && this.form.medications.length > 0 &&
                                      this.form.medications.some(m => m.name && m.dosage)

          if (hasValidMedications && recordId) {
            console.log('🩺 Creating treatment for medications...')
            await this.createOrUpdateTreatmentFromMedications(recordId)
          }

          // Create Medical Test record if test requests exist
          const hasTestRequests = this.form.test_requests && this.form.test_requests.trim().length > 0

          if (hasTestRequests && recordId) {
            console.log('🧪 Creating medical test for test requests...')
            console.log('🧪 Test requests:', this.form.test_requests)
            await this.createOrUpdateMedicalTestFromRequests(recordId)
          } else if (!hasTestRequests) {
            console.log('⚠️ No test requests found, skipping Medical Test creation')
          }
        } else {
          console.log('⚠️ Đang cập nhật record, không tạo điều trị/test mới')
        }

        this.showModal = false
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Lưu thất bại')
      } finally { this.saving = false }
    },

    async remove (row) {
      if (!confirm('Xóa hồ sơ này?')) return

      try {
        const id = row._id || row.id
        if (!id) {
          alert('Không tìm thấy ID hồ sơ')
          return
        }

        const rev = row._rev
        if (!rev) {
          alert('Không tìm thấy revision của document')
          return
        }

        // ✅ Truyền cả id và rev
        await MedicalRecordService.remove(id, rev)
        alert('Xóa thành công!')
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Xóa thất bại')
      }
    },

    // ✅ View related treatments
    viewTreatments (recordId) {
      // Navigate to treatments page with filter
      this.$router.push({
        path: '/treatments',
        query: { medical_record_id: recordId }
      })
    },

    // ✅ View related tests
    viewTests (recordId) {
      // Navigate to tests page with filter
      this.$router.push({
        path: '/medical-tests',
        query: { medical_record_id: recordId }
      })
    },

    // ✅ SUC-06: Create invoice from completed medical record
    async createInvoiceFromRecord (record) {
      const recordId = record._id || record.id
      if (!recordId) {
        alert('Không tìm thấy ID bệnh án!')
        return
      }

      try {
        // ✅ Ensure patient data is loaded
        await this.ensureOptionsLoaded()

        // Get patient name
        const patientName = this.displayName(this.patientsMap[record.patient_id]) || 'N/A'

        if (!confirm(`Tạo hóa đơn cho Bệnh án này?\n\nBệnh nhân: ${patientName}`)) {
          return
        }

        this.loading = true

        // Generate invoice number
        const generateInvoiceNumber = () => {
          const now = new Date()
          const year = now.getFullYear()
          const month = (now.getMonth() + 1).toString().padStart(2, '0')
          const day = now.getDate().toString().padStart(2, '0')
          const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0')
          return `INV${year}${month}${day}${random}`
        }

        // Build services array from medical record
        const services = []

        // 1. Add examination fee
        services.push({
          service_type: 'examination',
          description: `Khám ${record.visit_type || 'Tổng quát'}`,
          quantity: 1,
          unit_price: 200000, // Default examination fee
          total_price: 200000
        })

        // 2. ✅ Get medications from Treatment record (not from medical record)
        try {
          const treatments = await TreatmentService.list({
            medical_record_id: recordId,
            limit: 10
          })

          console.log('📋 Found treatments:', treatments)

          if (treatments?.data?.length > 0) {
            // Load medication prices from database
            await this.ensureOptionsLoaded()

            treatments.data.forEach(treatment => {
              if (treatment.medications && treatment.medications.length > 0) {
                treatment.medications.forEach(med => {
                  // Find medication in database to get real price
                  const medData = this.medicationsMap[med.medication_id] || null
                  let unitPrice = 50000 // Default if not found
                  const quantity = med.quantity_prescribed || 1

                  if (medData && medData.medication_info) {
                    // Get price from database
                    unitPrice = medData.medication_info.unit_price || 50000
                  }

                  const totalPrice = unitPrice * quantity

                  services.push({
                    service_type: 'medication',
                    description: `${med.name} - ${med.dosage} - ${med.frequency}`,
                    quantity,
                    unit_price: unitPrice,
                    total_price: totalPrice
                  })
                })
              }
            })
          } else {
            console.warn('⚠️ No treatments found for this medical record')
          }
        } catch (e) {
          console.error('❌ Failed to load treatments:', e)
        }

        // 3. Add procedures if any
        if (record.procedures && record.procedures.length > 0) {
          record.procedures.forEach(proc => {
            services.push({
              service_type: 'procedure',
              description: proc,
              quantity: 1,
              unit_price: 150000, // Default procedure price
              total_price: 150000
            })
          })
        }

        // 4. ✅ Get test prices from Medical Test records (not from text)
        try {
          const tests = await MedicalTestService.list({
            medical_record_id: recordId,
            limit: 10
          })

          console.log('🧪 Found medical tests:', tests)

          if (tests?.data?.length > 0) {
            tests.data.forEach(test => {
              const testInfo = test.test_info || {}
              const testName = testInfo.test_name || 'Xét nghiệm'
              const unitPrice = testInfo.unit_price || 150000 // Get price from test record

              services.push({
                service_type: 'test',
                description: testName,
                quantity: 1,
                unit_price: unitPrice,
                total_price: unitPrice
              })
            })
          } else {
            console.warn('⚠️ No medical tests found for this medical record')
          }
        } catch (e) {
          console.error('❌ Failed to load medical tests:', e)
        }

        // Calculate totals
        const subtotal = services.reduce((sum, s) => sum + (s.total_price || 0), 0)
        const taxRate = 0 // No tax for medical services
        const taxAmount = subtotal * taxRate
        const totalAmount = subtotal + taxAmount

        // ✅ Prepare invoice payload theo đúng cấu trúc backend expect
        const invoicePayload = {
          type: 'invoice',
          patient_id: record.patient_id,
          medical_record_id: record._id || record.id,
          invoice_info: {
            invoice_number: generateInvoiceNumber(),
            invoice_date: new Date().toISOString(),
            due_date: new Date().toISOString()
          },
          services,
          payment_info: {
            subtotal,
            tax_rate: taxRate,
            tax_amount: taxAmount,
            discount_amount: 0,
            insurance_coverage: 0,
            insurance_amount: 0,
            total_amount: totalAmount,
            patient_payment: totalAmount
          },
          payment_status: 'unpaid',
          payment_method: 'cash',
          notes: `Hóa đơn tự động từ Bệnh án ${record._id || record.id}`,
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString()
        }

        console.log('💰 Invoice payload:', JSON.stringify(invoicePayload, null, 2))

        // Create invoice
        const result = await InvoiceService.create(invoicePayload)
        console.log('💰 Invoice created:', result)

        alert(`✅ Đã tạo hóa đơn thành công!\n\nSố HĐ: ${invoicePayload.invoice_number}\nTổng tiền: ${totalAmount.toLocaleString()} VNĐ\n\nVui lòng vào menu "Hóa đơn" để xác nhận thanh toán.`)

        // Optionally navigate to invoices page
        if (confirm('Chuyển đến trang Hóa đơn?')) {
          this.$router.push('/invoices')
        }
      } catch (e) {
        console.error('Create invoice error:', e)
        alert(e?.response?.data?.message || e?.message || 'Không thể tạo hóa đơn')
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
/* Base Styles */
.medical-records-management {
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
}

.page-size-selector select:hover {
  background: rgba(255, 255, 255, 0.25);
}

/* Search Section */
.search-section {
  padding: 2rem 2.5rem;
  background: transparent;
}

.search-container {
  max-width: 1400px;
  margin: 0 auto;
}

.search-input-group {
  background: white;
  border-radius: 12px;
  padding: 1rem 1.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 1rem;
  max-width: 800px;
}

.search-icon {
  color: #9ca3af;
  font-size: 1.25rem;
}

.search-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 1rem;
  color: #374151;
}

.search-input::placeholder {
  color: #9ca3af;
}

.search-btn {
  height: 2.5rem;
  padding: 0 1.5rem;
  border-radius: 8px;
  border: none;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  white-space: nowrap;
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

.medical-records-table {
  width: 100%;
  border-collapse: collapse;
}

.medical-records-table thead {
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
  border-bottom: 2px solid #e2e8f0;
}

.medical-records-table thead th {
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
  width: 70px;
  text-align: center !important;
}

.col-date {
  width: 180px;
}

.col-patient {
  width: 200px;
}

.col-doctor {
  width: 200px;
}

.col-type {
  width: 150px;
}

.col-status {
  width: 140px;
}

.col-actions {
  width: 200px;
  text-align: center !important;
}

.medical-records-table tbody tr {
  border-bottom: 1px solid #f1f5f9;
  transition: all 0.2s ease;
}

.record-row:hover {
  background: #f8fafc;
}

.record-row.expanded {
  background: #f0f9ff;
}

.medical-records-table tbody td {
  padding: 1.25rem 1rem;
  color: #374151;
  font-size: 0.9rem;
}

.cell-number {
  text-align: center;
}

.row-number {
  width: 2.5rem;
  height: 2.5rem;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.95rem;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.date-info, .patient-info, .doctor-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.date-info i {
  color: #3b82f6;
  font-size: 1.1rem;
}

.patient-info i {
  color: #10b981;
  font-size: 1.1rem;
}

.doctor-info i {
  color: #f59e0b;
  font-size: 1.1rem;
}

.type-text {
  color: #64748b;
  font-weight: 500;
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

.status-draft {
  background: #f1f5f9;
  color: #475569;
}

.action-buttons {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.action-btn {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 8px;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 1rem;
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

.invoice-btn {
  background: #d1fae5;
  color: #10b981;
}

.invoice-btn:hover:not(:disabled) {
  background: #10b981;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
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
  display: flex;
  align-items: center;
  gap: 0.5rem;
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

.col-span-2 {
  grid-column: span 2;
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
  align-items: center;
  justify-content: center;
  padding: 0.5rem 0 0.5rem;
  border-top: 1px solid #e5e7eb;
  margin-top: 1.5rem;
  background: transparent;
  gap: 0.5rem;
}

.pagination-info {
  text-align: center;
  color: #64748b;
}

.page-info {
  font-weight: 600;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.95rem;
  margin-bottom: 0.25rem;
}

.page-info b {
  color: #374151;
  font-weight: 700;
}

.total-info {
  font-size: 0.85rem;
  color: #64748b;
}

.pagination-controls {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border-radius: 2rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  border: 1px solid #e5e7eb;
  padding: 0.15rem 0.5rem;
  min-width: 120px;
  max-width: 180px;
  gap: 0;
  height: 2.6rem;
}

.pagination-btn {
  width: 2.4rem;
  height: 2.4rem;
  border: none;
  background: transparent;
  color: #b0b6be;
  border-radius: 50%;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s, color 0.15s;
  cursor: pointer;
  font-size: 1.2rem;
  margin: 0 0.1rem;
}

.pagination-btn:hover:not(:disabled) {
  background: #f3f4f6;
  color: #2563eb;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: transparent;
  color: #e5e7eb;
}

.page-numbers {
  display: flex;
  align-items: center;
  gap: 0;
  margin: 0;
}

.page-number-btn {
  width: 2.4rem;
  height: 2.4rem;
  border: none;
  background: transparent;
  color: #2563eb;
  border-radius: 50%;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s, color 0.15s;
  cursor: pointer;
  font-size: 1.1rem;
  margin: 0 0.1rem;
}

.page-number-btn:hover:not(:disabled):not(.ellipsis) {
  background: #f3f4f6;
  color: #2563eb;
}

.page-number-btn.active {
  background: #2563eb;
  color: #fff;
  border-radius: 50%;
  box-shadow: 0 2px 8px rgba(37,99,235,0.10);
  z-index: 1;
}

.page-number-btn.ellipsis {
  border: none;
  background: transparent;
  cursor: default;
  color: #b0b6be;
  font-weight: 400;
}

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: grid;
  place-items: center;
  z-index: 1050;
  overflow-y: auto;
  padding: 1rem;
}

.modal-card {
  width: min(1000px, 95vw);
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  max-height: 90vh;
  overflow-y: auto;
}

.section-title {
  font-weight: 700;
  font-size: 1.1rem;
  color: #374151;
  margin: 1.5rem 0 1rem 0;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e5e7eb;
}

/* Previous medical records styles */
.previous-records-list {
  max-height: 300px;
  overflow-y: auto;
  margin-top: 0.5rem;
}

.previous-record-item {
  padding: 0.75rem;
  background-color: #f8f9fa;
  border-left: 3px solid #3b82f6;
  border-radius: 0.25rem;
  margin-bottom: 0.5rem;
}

.previous-record-item:hover {
  background-color: #e9ecef;
}

/* Medication autocomplete dropdown */
.autocomplete-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 0.25rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  max-height: 350px;
  overflow-y: auto;
  z-index: 10000;
  margin-top: 2px;
  min-width: 300px;
}

.autocomplete-item {
  padding: 10px 14px;
  cursor: pointer;
  border-bottom: 1px solid #f0f0f0;
  font-size: 0.9rem;
  line-height: 1.5;
}

.autocomplete-item:hover {
  background-color: #e3f2fd;
}

.autocomplete-item:last-child {
  border-bottom: none;
}

.autocomplete-item strong {
  font-size: 0.95rem;
  color: #1976d2;
}
</style>
