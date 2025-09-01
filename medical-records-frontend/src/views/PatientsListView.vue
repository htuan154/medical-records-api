<template>
  <div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h2 class="h4 mb-0">Bệnh nhân</h2>
      <button class="btn btn-primary btn-sm" @click="load">Tải lại</button>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" role="status" />
      <div class="mt-2 text-muted">Đang tải dữ liệu...</div>
    </div>

    <div v-else class="table-responsive">
      <table class="table table-striped table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:64px">#</th>
            <th>Tên</th>
            <th>Ngày sinh</th>
            <th>SĐT</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(p, idx) in items" :key="p.id || idx">
            <td>{{ idx + 1 }}</td>
            <td>{{ p.fullName || p.name }}</td>
            <td>{{ p.birthDate || '-' }}</td>
            <td>{{ p.phone || '-' }}</td>
          </tr>
          <tr v-if="!items.length">
            <td colspan="4" class="text-center text-muted py-4">Không có dữ liệu</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import PatientService from '@/api/patientService'

export default {
  name: 'PatientsListView',
  data () {
    return {
      items: [],
      loading: false,
      q: '',
      page: 1,
      pageSize: 20,
      total: 0
    }
  },
  methods: {
    async load () {
      this.loading = true
      try {
        const data = await PatientService.list({
          q: this.q,
          page: this.page,
          pageSize: this.pageSize
        })
        this.items = Array.isArray(data?.items) ? data.items : (Array.isArray(data) ? data : [])
        this.total = Number.isFinite(data?.total) ? data.total : this.items.length
      } catch (e) {
        alert(e?.message || 'Tải danh sách thất bại')
      } finally {
        this.loading = false
      }
    },
    go (p) {
      if (p < 1) return
      this.page = p
      this.load()
    }
  },
  mounted () {
    this.load()
  }
}
</script>
