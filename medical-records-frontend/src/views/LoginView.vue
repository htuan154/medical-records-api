<template>
  <div class="row justify-content-center mt-5">
    <div class="col-12 col-sm-10 col-md-6 col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h1 class="h4 mb-3">Đăng nhập</h1>

          <form @submit.prevent="submit" novalidate>
            <div class="mb-3">
              <label class="form-label">Username</label>
              <input
                v-model.trim="username"
                class="form-control"
                autocomplete="username"
                required
                placeholder="Nhập username"
              />
            </div>

            <div class="mb-3">
              <label class="form-label">Password</label>
              <input
                v-model="password"
                type="password"
                class="form-control"
                autocomplete="current-password"
                required
                placeholder="Nhập mật khẩu"
              />
            </div>

            <button class="btn btn-primary w-100" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" />
              {{ loading ? 'Đang đăng nhập...' : 'Đăng nhập' }}
            </button>

            <div v-if="error" class="alert alert-danger mt-3 py-2 mb-0">
              {{ error }}
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LoginView',
  data () {
    return {
      username: '',
      password: '',
      loading: false,
      error: ''
    }
  },
  methods: {
    async submit () {
      this.error = ''
      if (!this.username || !this.password) {
        this.error = 'Vui lòng nhập đầy đủ username và password'
        return
      }
      this.loading = true
      try {
        await this.$store.dispatch('login', {
          username: this.username,
          password: this.password
        })
        const redirect = this.$route.query.redirect || '/'
        this.$router.push(redirect)
      } catch (e) {
        this.error = e?.message || 'Đăng nhập thất bại'
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
