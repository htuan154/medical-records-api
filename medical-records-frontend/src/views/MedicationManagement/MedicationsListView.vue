<template>
  <section class="container py-4">
    <div class="d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0">Thuốc</h1>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" @click="reload" :disabled="loading">Tải lại</button>
        <button class="btn btn-primary" @click="openCreate">+ Thêm mới</button>
      </div>
    </div>

    <div class="mt-3" style="max-width:480px">
      <div class="input-group">
        <input v-model.trim="q" class="form-control" placeholder="Tìm tên thuốc / hoạt chất..." @keyup.enter="search" />
        <button class="btn btn-outline-secondary" @click="search">Tìm</button>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger my-2">{{ error }}</div>

    <div class="table-responsive mt-3">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Tên thuốc</th>
            <th>Hoạt chất</th>
            <th>Hàm lượng</th>
            <th>Dạng bào chế</th>
            <th>Tồn kho</th>
            <th>Hạn dùng</th>
            <th style="width:200px">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="(m, idx) in items" :key="m._id">
            <tr>
              <td>{{ idx+1 + (page-1)*pageSize }}</td>
              <td>{{ m.medication_info?.name }}</td>
              <td>{{ m.medication_info?.generic_name }}</td>
              <td>{{ m.medication_info?.strength }}</td>
              <td>{{ m.medication_info?.dosage_form }}</td>
              <td>{{ m.inventory?.current_stock }}</td>
              <td>{{ m.inventory?.expiry_date }}</td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary" @click="toggle(m)">
                    {{ expanded[m._id]?'Ẩn':'Xem' }}
                  </button>
                  <button class="btn btn-sm btn-outline-primary" @click="openEdit(m)">Sửa</button>
                  <button class="btn btn-sm btn-outline-danger" @click="remove(m)">Xóa</button>
                </div>
              </td>
            </tr>

            <!-- chi tiết -->
            <tr v-if="expanded[m._id]" class="row-detail">
              <td colspan="8">
                <div class="detail-sections">
                  <div class="detail-title">Thông tin thuốc</div>
                  <p>Tên: {{ m.medication_info?.name }}</p>
                  <p>Hoạt chất: {{ m.medication_info?.generic_name }}</p>
                  <p>Hàm lượng: {{ m.medication_info?.strength }}</p>
                  <p>Dạng: {{ m.medication_info?.dosage_form }}</p>
                  <p>NSX: {{ m.medication_info?.manufacturer }}</p>
                  <p>Barcode: {{ m.medication_info?.barcode }}</p>

                  <div class="detail-title">Thông tin lâm sàng</div>
                  <p>Nhóm: {{ m.clinical_info?.therapeutic_class }}</p>
                  <p>Chỉ định: {{ (m.clinical_info?.indications || []).join(', ') }}</p>
                  <p>Chống chỉ định: {{ (m.clinical_info?.contraindications || []).join(', ') }}</p>
                  <p>Tác dụng phụ: {{ (m.clinical_info?.side_effects || []).join(', ') }}</p>
                  <p>Tương tác: {{ (m.clinical_info?.drug_interactions || []).join(', ') }}</p>

                  <div class="detail-title">Kho</div>
                  <p>Số lượng: {{ m.inventory?.current_stock }}</p>
                  <p>Giá nhập: {{ m.inventory?.unit_cost }}</p>
                  <p>Hạn dùng: {{ m.inventory?.expiry_date }}</p>
                  <p>Nhà cung cấp: {{ m.inventory?.supplier }}</p>

                  <div class="detail-title">Khác</div>
                  <p>Trạng thái: {{ m.status }}</p>
                  <p>Tạo: {{ fmtDateTime(m.created_at) }} | Cập nhật: {{ fmtDateTime(m.updated_at) }}</p>
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
        <h2 class="h5">{{ editingId?'Sửa':'Thêm' }} thuốc</h2>
        <form @submit.prevent="save">
          <label class="form-label">Tên</label>
          <input v-model="form.name" class="form-control" required />
          <label class="form-label">Hàm lượng</label>
          <input v-model="form.strength" class="form-control" />
          <label class="form-label">Dạng bào chế</label>
          <input v-model="form.dosage_form" class="form-control" />
          <label class="form-label">Tồn kho</label>
          <input v-model.number="form.current_stock" type="number" class="form-control" />
          <label class="form-label">Hạn dùng</label>
          <input v-model="form.expiry_date" type="date" class="form-control" />
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
import MedicationService from '@/api/medicationService'

export default {
  name: 'MedicationsListView',
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
    toggle (m) { this.expanded = { ...this.expanded, [m._id]: !this.expanded[m._id] } },

    async fetch () {
      this.loading = true
      try {
        const res = await MedicationService.list({ q: this.q, limit: this.pageSize, skip: (this.page - 1) * this.pageSize })
        this.items = res.rows ? res.rows.map(r => r.doc) : res.data || []
        this.total = res.total_rows || res.total || this.items.length
      } catch (e) { this.error = e.message } finally { this.loading = false }
    },
    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },

    openCreate () { this.editingId = null; this.form = {}; this.showModal = true },
    openEdit (m) {
      this.editingId = m._id
      this.form = {
        name: m.medication_info?.name,
        strength: m.medication_info?.strength,
        dosage_form: m.medication_info?.dosage_form,
        current_stock: m.inventory?.current_stock,
        expiry_date: m.inventory?.expiry_date
      }
      this.showModal = true
    },
    close () { this.showModal = false },

    async save () {
      const payload = {
        type: 'medication',
        medication_info: {
          name: this.form.name,
          strength: this.form.strength,
          dosage_form: this.form.dosage_form
        },
        inventory: {
          current_stock: this.form.current_stock,
          expiry_date: this.form.expiry_date
        }
      }
      if (this.editingId) await MedicationService.update(this.editingId, payload)
      else await MedicationService.create(payload)
      this.showModal = false; this.fetch()
    },
    async remove (m) { if (confirm('Xóa thuốc?')) { await MedicationService.remove(m._id); this.fetch() } }
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
