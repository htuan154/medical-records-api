<template>
  <section class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0">Quản lý Hóa đơn</h1>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" @click="reload" :disabled="loading">Tải lại</button>
        <button class="btn btn-primary" @click="openCreate">+ Thêm mới</button>
      </div>
    </div>

    <!-- Search -->
    <div class="mt-3">
      <div class="input-group" style="max-width:480px">
        <input v-model.trim="q" class="form-control" placeholder="Tìm số hóa đơn / bệnh nhân..." @keyup.enter="search" />
        <button class="btn btn-outline-secondary" @click="search">Tìm</button>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger my-2">{{ error }}</div>

    <!-- Table -->
    <div class="table-responsive mt-3">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Số hóa đơn</th>
            <th>Ngày</th>
            <th>Hạn thanh toán</th>
            <th>Tổng tiền</th>
            <th>Trạng thái</th>
            <th style="width:240px">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="(inv, idx) in items" :key="inv._id">
            <tr>
              <td>{{ idx+1 + (page-1)*pageSize }}</td>
              <td>{{ inv.invoice_info?.invoice_number }}</td>
              <td>{{ fmtDate(inv.invoice_info?.invoice_date) }}</td>
              <td>{{ fmtDate(inv.invoice_info?.due_date) }}</td>
              <td>{{ inv.payment_info?.total_amount?.toLocaleString() }} đ</td>
              <td>
                <span :class="['badge', inv.payment_status==='paid'?'bg-success':'bg-warning']">
                  {{ inv.payment_status }}
                </span>
              </td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary" @click="toggle(inv)">
                    {{ expanded[inv._id]?'Ẩn':'Xem' }}
                  </button>
                  <button class="btn btn-sm btn-outline-primary" @click="openEdit(inv)">Sửa</button>
                  <button class="btn btn-sm btn-outline-danger" @click="remove(inv)">Xóa</button>
                  <button v-if="inv.payment_status!=='paid'" class="btn btn-sm btn-outline-success" @click="pay(inv)">Thanh toán</button>
                  <button class="btn btn-sm btn-outline-dark" @click="download(inv)">PDF</button>
                </div>
              </td>
            </tr>

            <!-- Row detail -->
            <tr v-if="expanded[inv._id]" class="row-detail">
              <td colspan="7">
                <div class="detail-sections">
                  <div class="detail-title">Dịch vụ</div>
                  <ul>
                    <li v-for="(s,i) in inv.services" :key="i">
                      {{ s.description }} (x{{ s.quantity }}) — {{ s.total_price.toLocaleString() }} đ
                    </li>
                  </ul>

                  <div class="detail-title">Thanh toán</div>
                  <p>Thành tiền: {{ inv.payment_info?.subtotal.toLocaleString() }} đ</p>
                  <p>Thuế: {{ inv.payment_info?.tax_amount.toLocaleString() }} đ</p>
                  <p>Bảo hiểm: {{ inv.payment_info?.insurance_amount.toLocaleString() }} đ</p>
                  <p>Bệnh nhân trả: {{ inv.payment_info?.patient_payment.toLocaleString() }} đ</p>
                  <p>Phương thức: {{ inv.payment_method }}</p>
                  <p>Ngày trả: {{ fmtDateTime(inv.paid_date) }}</p>

                  <div class="detail-title">Khác</div>
                  <p>ID: {{ inv._id }} | Rev: {{ inv._rev }}</p>
                  <p>Tạo lúc: {{ fmtDateTime(inv.created_at) }}</p>
                  <p>Cập nhật: {{ fmtDateTime(inv.updated_at) }}</p>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Modal Thêm/Sửa -->
    <div v-if="showModal" class="modal-backdrop" @click.self="close">
      <div class="modal-card">
        <h2 class="h5">{{ editingId?'Sửa':'Thêm' }} hóa đơn</h2>
        <form @submit.prevent="save">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Số hóa đơn</label>
              <input v-model="form.invoice_number" class="form-control" required />
            </div>
            <div class="col-md-3">
              <label class="form-label">Ngày</label>
              <input v-model="form.invoice_date" type="date" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Hạn</label>
              <input v-model="form.due_date" type="date" class="form-control" />
            </div>
          </div>

          <!-- đơn giản: chưa nhập service chi tiết trong modal -->
          <div class="mt-3">
            <label class="form-label">Tổng tiền</label>
            <input v-model.number="form.total_amount" type="number" class="form-control" />
          </div>

          <div class="mt-3">
            <label class="form-label">Trạng thái</label>
            <select v-model="form.payment_status" class="form-select">
              <option value="unpaid">unpaid</option>
              <option value="paid">paid</option>
            </select>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary" @click="close">Hủy</button>
            <button type="submit" class="btn btn-primary">Lưu</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script>
import InvoiceService from '@/api/invoiceService'

export default {
  name: 'InvoicesListView',
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
    toggle (inv) { this.expanded = { ...this.expanded, [inv._id]: !this.expanded[inv._id] } },

    async fetch () {
      this.loading = true
      try {
        const res = await InvoiceService.list({ q: this.q, limit: this.pageSize, skip: (this.page - 1) * this.pageSize })
        this.items = res.rows ? res.rows.map(r => r.doc) : res.data || []
        this.total = res.total_rows || res.total || this.items.length
      } catch (e) { this.error = e.message } finally { this.loading = false }
    },
    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },

    openCreate () { this.editingId = null; this.form = {}; this.showModal = true },
    openEdit (inv) {
      this.editingId = inv._id
      this.form = {
        invoice_number: inv.invoice_info?.invoice_number,
        invoice_date: inv.invoice_info?.invoice_date?.slice(0, 10),
        due_date: inv.invoice_info?.due_date?.slice(0, 10),
        total_amount: inv.payment_info?.total_amount,
        payment_status: inv.payment_status
      }
      this.showModal = true
    },
    close () { this.showModal = false },

    async save () {
      const payload = {
        type: 'invoice',
        invoice_info: {
          invoice_number: this.form.invoice_number,
          invoice_date: this.form.invoice_date,
          due_date: this.form.due_date
        },
        payment_info: { total_amount: this.form.total_amount },
        payment_status: this.form.payment_status
      }
      if (this.editingId) {
        await InvoiceService.update(this.editingId, payload)
      } else {
        await InvoiceService.create(payload)
      }
      this.showModal = false; this.fetch()
    },
    async remove (inv) {
      if (!confirm(`Xóa hóa đơn ${inv.invoice_info?.invoice_number}?`)) return
      await InvoiceService.remove(inv._id)
      this.fetch()
    },
    async pay (inv) {
      await InvoiceService.pay(inv._id)
      this.fetch()
    },
    async download (inv) {
      const res = await InvoiceService.download(inv._id)
      const url = window.URL.createObjectURL(new Blob([res.data]))
      const link = document.createElement('a')
      link.href = url; link.download = `${inv.invoice_info?.invoice_number}.pdf`
      link.click()
    }
  }
}
</script>

<style scoped>
.row-detail td { background:#fafafa }
.detail-sections { padding:10px }
.detail-title { font-weight:bold; margin-top:8px }
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.45);display:grid;place-items:center}
.modal-card{background:#fff;padding:16px;border-radius:8px;width:min(600px,96vw)}
</style>
