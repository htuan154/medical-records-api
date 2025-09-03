<template>
  <section class="container py-4">
    <div class="d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0">Điều trị</h1>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" @click="reload" :disabled="loading">Tải lại</button>
        <button class="btn btn-primary" @click="openCreate">+ Thêm mới</button>
      </div>
    </div>

    <div class="mt-3" style="max-width:480px">
      <div class="input-group">
        <input v-model.trim="q" class="form-control" placeholder="Tìm theo tên / loại điều trị..." @keyup.enter="search" />
        <button class="btn btn-outline-secondary" @click="search">Tìm</button>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger my-2">{{ error }}</div>

    <div class="table-responsive mt-3">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Tên điều trị</th>
            <th>Loại</th>
            <th>Ngày bắt đầu</th>
            <th>Ngày kết thúc</th>
            <th>Trạng thái</th>
            <th style="width:200px">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="(t, idx) in items" :key="t._id">
            <tr>
              <td>{{ idx+1 + (page-1)*pageSize }}</td>
              <td>{{ t.treatment_info?.treatment_name }}</td>
              <td>{{ t.treatment_info?.treatment_type }}</td>
              <td>{{ fmtDate(t.treatment_info?.start_date) }}</td>
              <td>{{ fmtDate(t.treatment_info?.end_date) }}</td>
              <td>
                <span :class="['badge', t.status==='active'?'bg-success':'bg-secondary']">{{ t.status }}</span>
              </td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary" @click="toggle(t)">
                    {{ expanded[t._id]?'Ẩn':'Xem' }}
                  </button>
                  <button class="btn btn-sm btn-outline-primary" @click="openEdit(t)">Sửa</button>
                  <button class="btn btn-sm btn-outline-danger" @click="remove(t)">Xóa</button>
                </div>
              </td>
            </tr>

            <!-- chi tiết -->
            <tr v-if="expanded[t._id]" class="row-detail">
              <td colspan="7">
                <div class="detail-sections">
                  <div class="detail-title">Thông tin</div>
                  <p>Tên: {{ t.treatment_info?.treatment_name }}</p>
                  <p>Loại: {{ t.treatment_info?.treatment_type }}</p>
                  <p>Thời gian: {{ fmtDate(t.treatment_info?.start_date) }} → {{ fmtDate(t.treatment_info?.end_date) }} ({{ t.treatment_info?.duration_days }} ngày)</p>

                  <div class="detail-title">Thuốc kê đơn</div>
                  <ul>
                    <li v-for="(m,i) in t.medications" :key="i">
                      {{ m.name }} {{ m.dosage }}, {{ m.frequency }} ({{ m.route }}) - SL: {{ m.quantity_prescribed }}. Hướng dẫn: {{ m.instructions }}
                    </li>
                  </ul>

                  <div class="detail-title">Theo dõi</div>
                  <p>Thông số: {{ (t.monitoring?.parameters || []).join(', ') }}</p>
                  <p>Tần suất: {{ t.monitoring?.frequency }}</p>
                  <p>Lần kiểm tra tiếp theo: {{ fmtDate(t.monitoring?.next_check) }}</p>

                  <div class="detail-title">Khác</div>
                  <p>Bệnh nhân: {{ t.patient_id }} | Bác sĩ: {{ t.doctor_id }} | Hồ sơ: {{ t.medical_record_id }}</p>
                  <p>Tạo: {{ fmtDateTime(t.created_at) }} | Cập nhật: {{ fmtDateTime(t.updated_at) }}</p>
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
        <h2 class="h5">{{ editingId?'Sửa':'Thêm' }} điều trị</h2>
        <form @submit.prevent="save">
          <label class="form-label">Tên điều trị</label>
          <input v-model="form.treatment_name" class="form-control" required />
          <label class="form-label">Loại</label>
          <input v-model="form.treatment_type" class="form-control" required />
          <label class="form-label">Ngày bắt đầu</label>
          <input v-model="form.start_date" type="date" class="form-control" required />
          <label class="form-label">Ngày kết thúc</label>
          <input v-model="form.end_date" type="date" class="form-control" required />
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
import TreatmentService from '@/api/treatmentService'

export default {
  name: 'TreatmentsListView',
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
    fmtDate (v) { return v ? new Date(v).toLocaleDateString() : '-' },
    fmtDateTime (v) { return v ? new Date(v).toLocaleString() : '-' },
    toggle (t) { this.expanded = { ...this.expanded, [t._id]: !this.expanded[t._id] } },

    async fetch () {
      this.loading = true
      try {
        const res = await TreatmentService.list({ q: this.q, limit: this.pageSize, skip: (this.page - 1) * this.pageSize })
        this.items = res.rows ? res.rows.map(r => r.doc) : res.data || []
        this.total = res.total_rows || res.total || this.items.length
      } catch (e) { this.error = e.message } finally { this.loading = false }
    },
    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },

    openCreate () { this.editingId = null; this.form = {}; this.showModal = true },
    openEdit (t) {
      this.editingId = t._id
      this.form = {
        treatment_name: t.treatment_info?.treatment_name,
        treatment_type: t.treatment_info?.treatment_type,
        start_date: t.treatment_info?.start_date?.slice(0, 10),
        end_date: t.treatment_info?.end_date?.slice(0, 10)
      }
      this.showModal = true
    },
    close () { this.showModal = false },

    async save () {
      const payload = {
        type: 'treatment',
        treatment_info: {
          treatment_name: this.form.treatment_name,
          treatment_type: this.form.treatment_type,
          start_date: this.form.start_date,
          end_date: this.form.end_date
        }
      }
      if (this.editingId) await TreatmentService.update(this.editingId, payload)
      else await TreatmentService.create(payload)
      this.showModal = false; this.fetch()
    },
    async remove (t) { if (confirm('Xóa điều trị?')) { await TreatmentService.remove(t._id); this.fetch() } }
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
