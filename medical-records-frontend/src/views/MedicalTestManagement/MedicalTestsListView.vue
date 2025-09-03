<template>
  <section class="container py-4">
    <div class="d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0">Xét nghiệm</h1>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" @click="reload" :disabled="loading">Tải lại</button>
        <button class="btn btn-primary" @click="openCreate">+ Thêm mới</button>
      </div>
    </div>

    <div class="mt-3" style="max-width:480px">
      <div class="input-group">
        <input v-model.trim="q" class="form-control" placeholder="Tìm theo tên xét nghiệm / loại..." @keyup.enter="search" />
        <button class="btn btn-outline-secondary" @click="search">Tìm</button>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger my-2">{{ error }}</div>

    <div class="table-responsive mt-3">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Loại</th>
            <th>Tên xét nghiệm</th>
            <th>Ngày chỉ định</th>
            <th>Ngày có kết quả</th>
            <th>Trạng thái</th>
            <th style="width:200px">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="(t, idx) in items" :key="t._id">
            <tr>
              <td>{{ idx+1 + (page-1)*pageSize }}</td>
              <td>{{ t.test_info?.test_type }}</td>
              <td>{{ t.test_info?.test_name }}</td>
              <td>{{ fmtDateTime(t.test_info?.ordered_date) }}</td>
              <td>{{ fmtDateTime(t.test_info?.result_date) }}</td>
              <td>
                <span :class="['badge', t.status==='completed'?'bg-success':'bg-warning']">{{ t.status }}</span>
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
                  <p>Loại: {{ t.test_info?.test_type }}</p>
                  <p>Tên: {{ t.test_info?.test_name }}</p>
                  <p>Ngày lấy mẫu: {{ fmtDateTime(t.test_info?.sample_collected_date) }}</p>

                  <div class="detail-title">Kết quả</div>
                  <ul>
                    <li v-for="(r,k) in t.results" :key="k">
                      {{ k.toUpperCase() }}: {{ r.value }} {{ r.unit }}
                      (chuẩn: {{ r.reference_range }}) - {{ r.status }}
                    </li>
                  </ul>

                  <div class="detail-title">Diễn giải</div>
                  <p>{{ t.interpretation }}</p>

                  <div class="detail-title">Khác</div>
                  <p>Bệnh nhân: {{ t.patient_id }} | Bác sĩ: {{ t.doctor_id }}</p>
                  <p>KTV: {{ t.lab_technician }}</p>
                  <p>Tạo lúc: {{ fmtDateTime(t.created_at) }} | Cập nhật: {{ fmtDateTime(t.updated_at) }}</p>
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
        <h2 class="h5">{{ editingId?'Sửa':'Thêm' }} xét nghiệm</h2>
        <form @submit.prevent="save">
          <label class="form-label">Loại</label>
          <input v-model="form.test_type" class="form-control" required />
          <label class="form-label">Tên xét nghiệm</label>
          <input v-model="form.test_name" class="form-control" required />
          <label class="form-label">Ngày chỉ định</label>
          <input v-model="form.ordered_date" type="datetime-local" class="form-control" required />
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
import MedicalTestService from '@/api/medicalTestService'

export default {
  name: 'MedicalTestsListView',
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
    toggle (t) { this.expanded = { ...this.expanded, [t._id]: !this.expanded[t._id] } },

    async fetch () {
      this.loading = true
      try {
        const res = await MedicalTestService.list({ q: this.q, limit: this.pageSize, skip: (this.page - 1) * this.pageSize })
        this.items = res.rows ? res.rows.map(r => r.doc) : res.data || []
        this.total = res.total_rows || res.total || this.items.length
      } catch (e) { this.error = e.message } finally { this.loading = false }
    },
    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },

    openCreate () { this.editingId = null; this.form = {}; this.showModal = true },
    openEdit (t) {
      this.editingId = t._id
      this.form = { test_type: t.test_info?.test_type, test_name: t.test_info?.test_name, ordered_date: t.test_info?.ordered_date?.slice(0, 16) }
      this.showModal = true
    },
    close () { this.showModal = false },

    async save () {
      const payload = {
        type: 'medical_test',
        test_info: {
          test_type: this.form.test_type,
          test_name: this.form.test_name,
          ordered_date: this.form.ordered_date
        }
      }
      if (this.editingId) await MedicalTestService.update(this.editingId, payload)
      else await MedicalTestService.create(payload)
      this.showModal = false; this.fetch()
    },
    async remove (t) { if (confirm('Xóa xét nghiệm?')) { await MedicalTestService.remove(t._id); this.fetch() } }
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
