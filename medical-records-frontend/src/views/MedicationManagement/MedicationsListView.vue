<template>
  <section class="container py-4">
    <!-- Header + Tools -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 mb-0">Thuốc</h2>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" @click="reload" :disabled="loading">Tải lại</button>
        <button class="btn btn-primary" @click="openCreate" :disabled="loading">+ Thêm mới</button>
      </div>
    </div>

    <div class="d-flex align-items-center mb-3" style="max-width: 520px">
      <input v-model.trim="q" class="form-control me-2" placeholder="Tìm tên thuốc / hoạt chất..." @keyup.enter="search" />
      <button class="btn btn-outline-secondary" @click="search">Tìm</button>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <!-- LIST: chỉ 6 cột như yêu cầu -->
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th style="width:56px">#</th>
            <th>Tên thuốc</th>
            <th>Hoạt chất</th>
            <th>Hàm lượng</th>
            <th>Dạng bào chế</th>
            <th>Tồn kho</th>
            <th>Hạn dùng</th>
            <th style="width:180px">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="(m, idx) in items" :key="rowKey(m, idx)">
            <tr>
              <td>{{ idx + 1 + (page - 1) * pageSize }}</td>
              <td>{{ m.name }}</td>
              <td>{{ m.active_ingredient }}</td>
              <td>{{ m.strength }}</td>
              <td>{{ m.dosage_form }}</td>
              <td>{{ m.quantity }}</td>
              <td>{{ fmtDate(m.expiry_date) }}</td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary" @click="toggleRow(m)">{{ isExpanded(m) ? 'Ẩn' : 'Xem' }}</button>
                  <button class="btn btn-sm btn-outline-primary" @click="openEdit(m)">Sửa</button>
                  <button class="btn btn-sm btn-outline-danger" @click="remove(m)" :disabled="loading">Xóa</button>
                </div>
              </td>
            </tr>

            <!-- ROW DETAILS: hiện đủ phần ẩn khi bấm Xem -->
            <tr v-if="isExpanded(m)">
              <td :colspan="8">
                <div class="detail-wrap">
                  <div class="detail-title">Thông tin thuốc</div>
                  <div class="detail-grid">
                    <div><b>Tên:</b> {{ m.name }}</div>
                    <div><b>Hoạt chất:</b> {{ m.active_ingredient || '-' }}</div>
                    <div><b>Hàm lượng:</b> {{ m.strength || '-' }}</div>
                    <div><b>Dạng:</b> {{ m.dosage_form || '-' }}</div>
                    <div><b>NSX:</b> {{ m.manufacturer || '-' }}</div>
                    <div><b>Barcode:</b> {{ m.barcode || '-' }}</div>
                  </div>

                  <div class="detail-title">Thông tin lâm sàng</div>
                  <div class="detail-grid">
                    <div><b>Nhóm:</b> {{ m.drug_class || '-' }}</div>
                    <div><b>Chỉ định:</b> {{ joinList(m.indications) }}</div>
                    <div><b>Chống chỉ định:</b> {{ joinList(m.contraindications) }}</div>
                    <div><b>Tác dụng phụ:</b> {{ joinList(m.adverse_effects) }}</div>
                    <div><b>Tương tác:</b> {{ joinList(m.interactions) }}</div>
                  </div>

                  <div class="detail-title">Kho</div>
                  <div class="detail-grid">
                    <div><b>Số lượng:</b> {{ m.quantity }}</div>
                    <div><b>Giá nhập:</b> {{ m.purchase_price }}</div>
                    <div><b>Hạn dùng:</b> {{ fmtDate(m.expiry_date) }}</div>
                    <div><b>Nhà cung cấp:</b> {{ m.supplier || '-' }}</div>
                  </div>

                  <div class="detail-title">Khác</div>
                  <div class="detail-grid">
                    <div><b>Trạng thái:</b> {{ m.status || '-' }}</div>
                    <div><b>Tạo:</b> {{ fmtDateTime(m.created_at) }}</div>
                    <div><b>Cập nhật:</b> {{ fmtDateTime(m.updated_at) }}</div>
                  </div>
                </div>
              </td>
            </tr>
          </template>

          <tr v-if="!items.length">
            <td colspan="8" class="text-center text-muted">Không có dữ liệu</td>
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

    <!-- MODAL: form đầy đủ, bảng vẫn gọn -->
    <div v-if="showModal" class="modal-backdrop" @mousedown.self="close">
      <div class="modal-card">
        <h3 class="h6 mb-3">{{ editingId ? 'Sửa thuốc' : 'Thêm thuốc' }}</h3>

        <form @submit.prevent="save">
          <!-- Thông tin thuốc -->
          <div class="section-title">Thông tin thuốc</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Tên <span class="text-danger">*</span></label>
              <input v-model.trim="form.name" type="text" class="form-control" required />
            </div>
            <div class="col-md-6">
              <label class="form-label">Hoạt chất</label>
              <input v-model.trim="form.active_ingredient" type="text" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Hàm lượng</label>
              <input v-model.trim="form.strength" type="text" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Dạng bào chế</label>
              <input v-model.trim="form.dosage_form" type="text" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">NSX</label>
              <input v-model.trim="form.manufacturer" type="text" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Barcode</label>
              <input v-model.trim="form.barcode" type="text" class="form-control" />
            </div>
          </div>

          <!-- Lâm sàng -->
          <div class="section-title">Thông tin lâm sàng</div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nhóm</label>
              <input v-model.trim="form.drug_class" type="text" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Chỉ định (dấu phẩy ,)</label>
              <input v-model.trim="form.indications_text" type="text" class="form-control" placeholder="Hypertension, Angina pectoris" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Chống chỉ định (dấu phẩy)</label>
              <input v-model.trim="form.contraindications_text" type="text" class="form-control" placeholder="Hypersensitivity to amlodipine" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Tác dụng phụ (dấu phẩy)</label>
              <input v-model.trim="form.adverse_effects_text" type="text" class="form-control" placeholder="Ankle edema, Dizziness, Flushing" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Tương tác (dấu phẩy)</label>
              <input v-model.trim="form.interactions_text" type="text" class="form-control" placeholder="Grapefruit juice, Simvastatin" />
            </div>
          </div>

          <!-- Kho -->
          <div class="section-title">Kho</div>
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Số lượng</label>
              <input v-model.number="form.quantity" type="number" min="0" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Giá nhập</label>
              <input v-model.number="form.purchase_price" type="number" min="0" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Hạn dùng</label>
              <input v-model="form.expiry_date" type="date" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Nhà cung cấp</label>
              <input v-model.trim="form.supplier" type="text" class="form-control" />
            </div>
          </div>

          <!-- Khác -->
          <div class="section-title">Khác</div>
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Trạng thái</label>
              <select v-model="form.status" class="form-select">
                <option value="active">active</option>
                <option value="inactive">inactive</option>
              </select>
            </div>
            <div class="col-md-9">
              <div class="text-muted small d-flex gap-3 align-items-center" v-if="form.created_at || form.updated_at">
                <span>Tạo: {{ fmtDateTime(form.created_at) }}</span>
                <span>|</span>
                <span>Cập nhật: {{ fmtDateTime(form.updated_at) }}</span>
              </div>
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
import MedicationService from '@/api/medicationService'

export default {
  name: 'MedicationsListView',
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
      expanded: {}
    }
  },
  created () { this.fetch() },
  methods: {
    /* ===== helpers ===== */
    rowKey (m, idx) { return m._id || m.id || m.barcode || `${idx}` },
    isExpanded (row) { return !!this.expanded[this.rowKey(row, 0)] },
    toggleRow (row) { const k = this.rowKey(row, 0); this.expanded = { ...this.expanded, [k]: !this.expanded[k] } },
    fmtDate (v) { if (!v) return '-'; try { return new Date(v).toISOString().slice(0, 10) } catch { return v } },
    fmtDateTime (v) { if (!v) return '-'; try { return new Date(v).toLocaleString() } catch { return v } },
    joinList (v) { return Array.isArray(v) ? v.join(', ') : (v || '-') },

    // chuyển doc CouchDB (lồng) -> object phẳng phục vụ list & details
    flattenMedication (d = {}) {
      const mi = d.medication_info || {}
      const ci = d.clinical_info || {}
      const inv = d.inventory || {}

      return {
        ...d, // giữ id, status, timestamps…

        // các field dùng cho LIST (6 cột)
        name: mi.name ?? d.name ?? 'Chưa có tên',
        active_ingredient: mi.generic_name ?? mi.active_ingredient ?? d.active_ingredient ?? '',
        strength: mi.strength ?? d.strength ?? '',
        dosage_form: mi.dosage_form ?? d.dosage_form ?? '',
        quantity: inv.current_stock ?? d.quantity ?? 0,
        expiry_date: inv.expiry_date ?? d.expiry_date ?? '',

        // phần ẩn khi “Xem”
        manufacturer: mi.manufacturer ?? d.manufacturer ?? '',
        barcode: mi.barcode ?? d.barcode ?? '',
        drug_class: ci.therapeutic_class ?? d.drug_class ?? '',
        indications: ci.indications ?? d.indications ?? [],
        contraindications: ci.contraindications ?? d.contraindications ?? [],
        adverse_effects: ci.side_effects ?? d.adverse_effects ?? [],
        interactions: ci.drug_interactions ?? d.interactions ?? [],
        purchase_price: inv.unit_cost ?? d.purchase_price ?? 0,
        supplier: inv.supplier ?? d.supplier ?? '',

        status: d.status ?? 'active',
        created_at: d.created_at ?? null,
        updated_at: d.updated_at ?? null
      }
    },

    /* ===== data ===== */
    async fetch () {
      this.loading = true
      this.error = ''
      try {
        const skip = (this.page - 1) * this.pageSize
        const res = await MedicationService.list({ q: this.q || undefined, limit: this.pageSize, offset: skip, skip })

        let raw = []; let total = 0; let offset = null
        if (res && Array.isArray(res.rows)) {
          raw = res.rows.map(r => r.doc || r.value || r)
          total = res.total_rows ?? raw.length
          offset = res.offset ?? 0
        } else if (res && res.data && Array.isArray(res.data)) {
          raw = res.data; total = res.total ?? raw.length
        } else if (Array.isArray(res)) { raw = res; total = raw.length }

        // FLATTEN để bảng hiển thị đúng
        this.items = (raw || []).map(d => this.flattenMedication(d))

        this.total = total
        this.hasMore = (offset != null)
          ? (offset + this.items.length) < (this.total || 0)
          : (this.page * this.pageSize) < (this.total || 0)
      } catch (e) {
        console.error(e)
        this.error = e?.response?.data?.message || e?.message || 'Không tải được dữ liệu'
      } finally { this.loading = false }
    },
    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },

    /* ===== modal data ===== */
    emptyForm () {
      return {
        _id: null,
        _rev: null,
        name: '',
        active_ingredient: '',
        strength: '',
        dosage_form: '',
        manufacturer: '',
        barcode: '',
        drug_class: '',
        indications: [],
        contraindications: [],
        adverse_effects: [],
        interactions: [],
        indications_text: '',
        contraindications_text: '',
        adverse_effects_text: '',
        interactions_text: '',
        quantity: 0,
        purchase_price: 0,
        expiry_date: '',
        supplier: '',
        status: 'active',
        created_at: null,
        updated_at: null
      }
    },
    openCreate () {
      this.editingId = null
      this.form = this.emptyForm()
      this.showModal = true
    },
    openEdit (row) {
      const f = this.flattenMedication(row)
      const toCsv = (a) => Array.isArray(a) ? a.join(', ') : (a || '')

      this.editingId = f._id || f.id
      this.form = {
        ...this.emptyForm(),
        ...f,
        indications_text: toCsv(f.indications),
        contraindications_text: toCsv(f.contraindications),
        adverse_effects_text: toCsv(f.adverse_effects),
        interactions_text: toCsv(f.interactions),
        expiry_date: (f.expiry_date || '').toString().slice(0, 10)
      }
      this.showModal = true
    },
    close () { if (!this.saving) this.showModal = false },

    async save () {
      if (this.saving) return
      this.saving = true
      try {
        const csv = (s) => (s || '').split(',').map(x => x.trim()).filter(Boolean)

        // Build payload THEO CẤU TRÚC API (lồng)
        const payload = {
          type: 'medication',
          medication_info: {
            name: this.form.name,
            generic_name: this.form.active_ingredient || undefined,
            strength: this.form.strength || undefined,
            dosage_form: this.form.dosage_form || undefined,
            manufacturer: this.form.manufacturer || undefined,
            barcode: this.form.barcode || undefined
          },
          clinical_info: {
            therapeutic_class: this.form.drug_class || undefined,
            indications: this.form.indications?.length ? this.form.indications : csv(this.form.indications_text),
            contraindications: this.form.contraindications?.length ? this.form.contraindications : csv(this.form.contraindications_text),
            side_effects: this.form.adverse_effects?.length ? this.form.adverse_effects : csv(this.form.adverse_effects_text),
            drug_interactions: this.form.interactions?.length ? this.form.interactions : csv(this.form.interactions_text)
          },
          inventory: {
            current_stock: Number(this.form.quantity || 0),
            unit_cost: Number(this.form.purchase_price || 0),
            expiry_date: this.form.expiry_date || undefined,
            supplier: this.form.supplier || undefined
          },
          status: this.form.status || 'active'
        }

        // CouchDB update cần _id/_rev nếu có
        if (this.form._id) payload._id = this.form._id
        if (this.form._rev) payload._rev = this.form._rev

        if (this.editingId) await MedicationService.update(this.editingId, payload)
        else await MedicationService.create(payload)

        this.showModal = false
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Lưu thất bại')
      } finally { this.saving = false }
    },

    async remove (row) {
      if (!confirm(`Xóa thuốc "${row.name || 'này'}"?`)) return
      try {
        const id = row._id || row.id
        await MedicationService.remove(id)
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Xóa thất bại')
      }
    }
  }
}
</script>

<style scoped>
:deep(table.table) th, :deep(table.table) td { vertical-align: middle; }

/* row details */
.detail-wrap { border-top: 1px solid #e5e7eb; padding-top: 10px; }
.detail-title { font-weight: 600; margin: 10px 0 6px; }
.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 6px 16px;
}

/* modal */
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.45); display: grid; place-items: center; z-index: 1050; }
.modal-card { width: min(980px, 96vw); background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 20px 50px rgba(0,0,0,.25); max-height: 92vh; overflow: auto; }
.section-title { font-weight: 600; margin: 14px 0 8px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; }
</style>
