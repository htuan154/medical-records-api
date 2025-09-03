<template>
  <section class="container py-4">
    <div class="d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0">Hồ sơ bệnh án</h1>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" @click="reload" :disabled="loading">Tải lại</button>
        <button class="btn btn-primary" @click="openCreate">+ Thêm mới</button>
      </div>
    </div>

    <div class="mt-3">
      <div class="input-group" style="max-width:480px">
        <input v-model.trim="q" class="form-control" placeholder="Tìm theo mã hồ sơ / bệnh nhân / chẩn đoán..." @keyup.enter="search" />
        <button class="btn btn-outline-secondary" @click="search">Tìm</button>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger my-2">{{ error }}</div>

    <div class="table-responsive mt-3">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Ngày khám</th>
            <th>Loại khám</th>
            <th>Bệnh nhân</th>
            <th>Bác sĩ</th>
            <th>Chẩn đoán chính</th>
            <th>Trạng thái</th>
            <th style="width:200px">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="(rec, idx) in items" :key="rec._id">
            <tr>
              <td>{{ idx+1 + (page-1)*pageSize }}</td>
              <td>{{ fmtDateTime(rec.visit_info?.visit_date) }}</td>
              <td>{{ rec.visit_info?.visit_type }}</td>
              <td>{{ rec.patient_id }}</td>
              <td>{{ rec.doctor_id }}</td>
              <td>{{ rec.diagnosis?.primary?.description }}</td>
              <td>
                <span :class="['badge', rec.status==='completed'?'bg-success':'bg-warning']">{{ rec.status }}</span>
              </td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary" @click="toggle(rec)">
                    {{ expanded[rec._id]?'Ẩn':'Xem' }}
                  </button>
                  <button class="btn btn-sm btn-outline-primary" @click="openEdit(rec)">Sửa</button>
                  <button class="btn btn-sm btn-outline-danger" @click="remove(rec)">Xóa</button>
                </div>
              </td>
            </tr>

            <!-- chi tiết -->
            <tr v-if="expanded[rec._id]" class="row-detail">
              <td colspan="8">
                <div class="detail-sections">
                  <div class="detail-title">Thông tin khám</div>
                  <p>Ngày: {{ fmtDateTime(rec.visit_info?.visit_date) }}</p>
                  <p>Lý do: {{ rec.visit_info?.chief_complaint }}</p>

                  <div class="detail-title">Sinh hiệu</div>
                  <p>Nhiệt độ: {{ rec.examination?.vital_signs?.temperature }}°C</p>
                  <p>Huyết áp: {{ rec.examination?.vital_signs?.blood_pressure?.systolic }}/{{ rec.examination?.vital_signs?.blood_pressure?.diastolic }}</p>
                  <p>Mạch: {{ rec.examination?.vital_signs?.heart_rate }}/phút</p>
                  <p>Nhịp thở: {{ rec.examination?.vital_signs?.respiratory_rate }}/phút</p>
                  <p>Cân nặng: {{ rec.examination?.vital_signs?.weight }}kg</p>
                  <p>Chiều cao: {{ rec.examination?.vital_signs?.height }}cm</p>

                  <div class="detail-title">Khám lâm sàng</div>
                  <p>Toàn thân: {{ rec.examination?.physical_exam?.general }}</p>
                  <p>Tim mạch: {{ rec.examination?.physical_exam?.cardiovascular }}</p>
                  <p>Hô hấp: {{ rec.examination?.physical_exam?.respiratory }}</p>
                  <p>Khác: {{ rec.examination?.physical_exam?.other_findings }}</p>

                  <div class="detail-title">Chẩn đoán</div>
                  <p>Chính: {{ rec.diagnosis?.primary?.description }} ({{ rec.diagnosis?.primary?.code }})</p>
                  <p>Phân biệt: {{ (rec.diagnosis?.differential || []).join(', ') }}</p>

                  <div class="detail-title">Kế hoạch điều trị</div>
                  <ul>
                    <li v-for="(m,i) in rec.treatment_plan?.medications" :key="i">
                      {{ m.name }} {{ m.dosage }}, {{ m.frequency }} x {{ m.duration }} ({{ m.instructions }})
                    </li>
                  </ul>
                  <p>Lời khuyên: {{ (rec.treatment_plan?.lifestyle_advice || []).join('; ') }}</p>
                  <p>Tái khám: {{ rec.treatment_plan?.follow_up?.date }} ({{ rec.treatment_plan?.follow_up?.notes }})</p>

                  <div class="detail-title">Tệp đính kèm</div>
                  <ul>
                    <li v-for="(f,i) in rec.attachments" :key="i">
                      {{ f.file_name }} - {{ f.description }}
                      <button class="btn btn-sm btn-link" @click="download(rec,f)">Tải</button>
                      <button class="btn btn-sm btn-link text-danger" @click="deleteAttachment(rec,f)">Xóa</button>
                    </li>
                  </ul>

                  <div class="detail-title">Khác</div>
                  <p>ID: {{ rec._id }} | Rev: {{ rec._rev }}</p>
                  <p>Tạo lúc: {{ fmtDateTime(rec.created_at) }}</p>
                  <p>Cập nhật: {{ fmtDateTime(rec.updated_at) }}</p>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Modal thêm/sửa -->
    <div v-if="showModal" class="modal-backdrop" @click.self="close">
      <div class="modal-card">
        <h2 class="h5">{{ editingId?'Sửa':'Thêm' }} hồ sơ</h2>
        <form @submit.prevent="save">
          <label class="form-label">Ngày khám</label>
          <input v-model="form.visit_date" type="datetime-local" class="form-control" required />
          <label class="form-label">Loại khám</label>
          <input v-model="form.visit_type" class="form-control" />
          <label class="form-label">Lý do</label>
          <textarea v-model="form.chief_complaint" class="form-control"></textarea>
          <div class="mt-3 d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" @click="close">Hủy</button>
            <button type="submit" class="btn btn-primary">Lưu</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script>
import MedicalRecordService from '@/api/medicalRecordService'

export default {
  name: 'MedicalRecordsListView',
  data () {
    return {
      q: '',
      page: 1,
      pageSize: 50,
      items: [],
      total: 0,
      loading: false,
      error: '',
      expanded: {},
      showModal: false,
      editingId: null,
      form: {}
    }
  },
  created () { this.fetch() },
  methods: {
    fmtDateTime (v) { return v ? new Date(v).toLocaleString() : '-' },
    toggle (r) { this.expanded = { ...this.expanded, [r._id]: !this.expanded[r._id] } },

    async fetch () {
      this.loading = true
      try {
        const res = await MedicalRecordService.list({ q: this.q, limit: this.pageSize, skip: (this.page - 1) * this.pageSize })
        this.items = res.rows ? res.rows.map(r => r.doc) : res.data || []
        this.total = res.total_rows || res.total || this.items.length
      } catch (e) { this.error = e.message } finally { this.loading = false }
    },
    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },

    openCreate () { this.editingId = null; this.form = {}; this.showModal = true },
    openEdit (r) {
      this.editingId = r._id
      this.form = { visit_date: r.visit_info?.visit_date?.slice(0, 16), visit_type: r.visit_info?.visit_type, chief_complaint: r.visit_info?.chief_complaint }
      this.showModal = true
    },
    close () { this.showModal = false },

    async save () {
      const payload = {
        type: 'medical_record',
        visit_info: {
          visit_date: this.form.visit_date,
          visit_type: this.form.visit_type,
          chief_complaint: this.form.chief_complaint
        }
      }
      if (this.editingId) await MedicalRecordService.update(this.editingId, payload)
      else await MedicalRecordService.create(payload)
      this.showModal = false; this.fetch()
    },
    async remove (r) { if (confirm('Xóa hồ sơ?')) { await MedicalRecordService.remove(r._id); this.fetch() } },

    async download (rec, f) {
      const res = await MedicalRecordService.downloadAttachment(rec._id, f.file_name)
      const url = window.URL.createObjectURL(new Blob([res.data]))
      const link = document.createElement('a'); link.href = url; link.download = f.file_name; link.click()
    },
    async deleteAttachment (rec, f) {
      if (confirm('Xóa file?')) { await MedicalRecordService.deleteAttachment(rec._id, f.file_name); this.fetch() }
    }
  }
}
</script>

<style scoped>
.row-detail td{background:#fafafa}
.detail-sections{padding:10px}
.detail-title{font-weight:bold;margin-top:8px}
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.45);display:grid;place-items:center}
.modal-card{background:#fff;padding:16px;border-radius:8px;width:min(600px,96vw)}
</style>
