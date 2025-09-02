<template>
  <section class="container py-4">
    <div class="d-flex justify-content-between align-items-center">
      <h1 class="h4 mb-0">{{ title }}</h1>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" @click="reload" :disabled="loading">Tải lại</button>
        <button class="btn btn-primary" @click="openCreate" :disabled="loading">+ Thêm mới</button>
      </div>
    </div>

    <div class="mt-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="input-group" style="max-width: 440px">
          <input v-model.trim="q" class="form-control" placeholder="Tìm kiếm..." @keyup.enter="search" />
          <button class="btn btn-outline-secondary" @click="search">Tìm</button>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="text-muted">Tổng: {{ total }}</span>
          <select v-model.number="pageSize" class="form-select" style="width:120px" @change="changePageSize" :disabled="loading">
            <option :value="10">10 / trang</option>
            <option :value="25">25 / trang</option>
            <option :value="50">50 / trang</option>
            <option :value="100">100 / trang</option>
          </select>
        </div>
      </div>

      <div v-if="error" class="alert alert-danger">{{ error }}</div>
      <div v-if="loading" class="text-muted">Đang tải dữ liệu…</div>

      <table v-else class="table table-hover align-middle">
        <thead>
          <tr>
            <th style="width:60px">#</th>
            <th v-for="c in columns" :key="c.key">{{ c.label }}</th>
            <th style="width:140px">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, idx) in items" :key="row._id || row.id || row.code || idx">
            <td>{{ idx + 1 + (page - 1) * pageSize }}</td>
            <td v-for="c in columns" :key="c.key">
              <span v-if="c.render">{{ c.render(row[c.key], row) }}</span>
              <span v-else>{{ row[c.key] ?? '-' }}</span>
            </td>
            <td>
              <div class="btn-group">
                <button class="btn btn-sm btn-outline-primary" @click="openEdit(row)">Sửa</button>
                <button class="btn btn-sm btn-outline-danger" @click="remove(row)" :disabled="loading">Xoá</button>
              </div>
            </td>
          </tr>
          <tr v-if="!items.length">
            <td :colspan="columns.length + 2" class="text-center text-muted">Không có dữ liệu</td>
          </tr>
        </tbody>
      </table>

      <div class="d-flex justify-content-between align-items-center">
        <div>Trang {{ page }}</div>
        <div class="btn-group">
          <button class="btn btn-outline-secondary" @click="prev" :disabled="page <= 1 || loading">‹ Trước</button>
          <button class="btn btn-outline-secondary" @click="next" :disabled="!hasMore || loading">Sau ›</button>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal-backdrop" @mousedown.self="close">
      <div class="modal-card">
        <h2 class="h5 mb-3">{{ editingId ? 'Sửa' : 'Thêm mới' }}</h2>
        <form @submit.prevent="save">
          <div v-for="f in formSchema" :key="f.key" class="mb-3">
            <label class="form-label">{{ f.label }}</label>

            <input
              v-if="f.type === 'text' || f.type === 'date' || !f.type"
              class="form-control"
              :type="f.type || 'text'"
              v-model.trim="form[f.key]"
              :placeholder="f.placeholder || ''"
              :required="!!f.required"
              :disabled="!!(editingId && f.disabledOnEdit)"
            />

            <select v-else-if="f.type === 'select'" class="form-select" v-model="form[f.key]" :required="!!f.required">
              <option v-if="f.placeholder" disabled value="">{{ f.placeholder }}</option>
              <option v-for="opt in f.options || []" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>

            <div v-else class="text-muted">Không hỗ trợ type: {{ f.type }}</div>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" @click="close">Huỷ</button>
            <button class="btn btn-primary" type="submit" :disabled="saving">{{ saving ? 'Đang lưu…' : 'Lưu' }}</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: 'SimpleCrud',
  props: {
    title: { type: String, required: true },
    columns: { type: Array, required: true },
    formSchema: { type: Array, required: true },
    listFn: { type: Function, required: true },
    createFn: { type: Function, required: true },
    updateFn: { type: Function, required: true },
    removeFn: { type: Function, required: true },
    normalizeFn: { type: Function, required: true },
    toFormFn: { type: Function, required: true },
    toPayloadFn: { type: Function, required: true }
  },
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
      showModal: false,
      form: {},
      editingId: null,
      saving: false
    }
  },
  created () { this.fetch() },
  methods: {
    async fetch () {
      this.loading = true
      this.error = ''
      try {
        const skip = (this.page - 1) * this.pageSize
        const res = await this.listFn({
          q: this.q || undefined,
          page: this.page,
          pageSize: this.pageSize,
          limit: this.pageSize,
          offset: skip,
          skip,
          per_page: this.pageSize,
          take: this.pageSize
        })
        const meta = this.normalizeFn(res)
        this.items = meta.items
        this.total = meta.total ?? (Array.isArray(meta.items) ? meta.items.length : 0)
        // CouchDB: còn trang nếu offset + items.length < total
        this.hasMore = (meta.offset != null)
          ? (meta.offset + this.items.length) < (this.total || 0)
          : (this.page * this.pageSize) < (this.total || 0)
      } catch (e) {
        console.error(e)
        this.error = e?.response?.data?.message || e?.message || 'Không tải được dữ liệu'
      } finally {
        this.loading = false
      }
    },
    search () { this.page = 1; this.fetch() },
    reload () { this.fetch() },
    changePageSize () { this.page = 1; this.fetch() },
    next () { if (this.hasMore) { this.page++; this.fetch() } },
    prev () { if (this.page > 1) { this.page--; this.fetch() } },

    openCreate () { this.editingId = null; this.form = this.toFormFn(null); this.showModal = true },
    openEdit (row) { this.editingId = row._id || row.id || row.code; this.form = this.toFormFn(row); this.showModal = true },
    close () { if (!this.saving) this.showModal = false },

    async save () {
      if (this.saving) return
      this.saving = true
      try {
        const payload = this.toPayloadFn(this.form, this.editingId)
        if (this.editingId) await this.updateFn(this.editingId, payload)
        else await this.createFn(payload)
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
      if (!confirm('Xoá bản ghi này?')) return
      try {
        const id = row._id || row.id || row.code
        await this.removeFn(id)
        await this.fetch()
      } catch (e) {
        console.error(e)
        alert(e?.response?.data?.message || e?.message || 'Xoá thất bại')
      }
    }
  }
}
</script>

<style scoped>
.modal-backdrop{ position: fixed; inset: 0; background: rgba(0,0,0,.35); display: grid; place-items: center; z-index: 1050; }
.modal-card{ width: min(560px, 94vw); background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 12px 30px rgba(0,0,0,.18); }
</style>
