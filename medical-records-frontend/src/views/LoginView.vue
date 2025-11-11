<template>
  <section class="auth-page">
    <div class="auth-card">
      <div class="auth-card__header">
        <div class="auth-card__icon">🏥</div>
        <h1 class="auth-title">Hệ thống Quản lý Hồ sơ Bệnh án</h1>
        <p class="auth-subtitle">Vui lòng đăng nhập để tiếp tục</p>
      </div>

      <form @submit.prevent="submit" novalidate class="auth-form">
        <label class="form-label" for="username">Tên đăng nhập</label>
        <div class="input-wrap">
          <input
            id="username"
            v-model.trim="username"
            type="text"
            autocomplete="username"
            placeholder="admin"
            required
          />
        </div>

        <label class="form-label" for="password">Mật khẩu</label>
        <div class="input-wrap password-wrap">
          <input
            id="password"
            v-model.trim="password"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="current-password"
            placeholder="••••••"
            required
          />
          <button
            type="button"
            class="password-toggle"
            @click="togglePassword"
            :aria-label="showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
          >
            <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
          </button>
        </div>

        <div class="row-between">
          <label class="checkbox">
            <input type="checkbox" /> <span>Ghi nhớ đăng nhập</span>
          </label>
          <a class="link" href="javascript:void(0)">Quên mật khẩu?</a>
        </div>

        <button
          class="btn-primary"
          type="submit"
          :disabled="loading"
          :aria-busy="loading"
        >
          <span v-if="loading">Đang đăng nhập…</span>
          <span v-else>Đăng nhập</span>
        </button>

        <p v-if="error" class="error">{{ error }}</p>
      </form>

      <p class="auth-note">© 2025 Hệ thống Quản lý Hồ sơ Bệnh án<br />Bảo mật thông tin bệnh nhân</p>
    </div>
  </section>
</template>

<script>
export default {
  name: 'LoginView',
  data () {
    return {
      username: '',
      password: '',
      loading: false,
      error: '',
      showPassword: false
    }
  },
  async created () {
    // Nếu đã có token thì nạp hồ sơ rồi đá về home
    const isAuthed =
      this.$store.getters?.isAuthenticated ||
      !!localStorage.getItem('access_token') ||
      !!localStorage.getItem('token')

    if (isAuthed) {
      try { await this.$store.dispatch('fetchMe') } catch (e) {}
      this.$router.replace({ name: 'home' })
    }
  },
  methods: {
    togglePassword () {
      this.showPassword = !this.showPassword
    },
    async submit () {
      if (this.loading) return
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
        await this.$store.dispatch('fetchMe')
        const redirect = this.$route.query.redirect || { name: 'home' }
        this.$router.replace(redirect)
      } catch (e) {
        this.error = e?.response?.data?.message || e?.message || 'Đăng nhập thất bại'
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
/* Vùng nền full màn hình, gradient tràn toàn bộ chiều ngang, luôn căn giữa card */
.auth-page {
  min-height: 100vh;                 /* full viewport */
  width: 100%;
  display: grid;
  place-items: center;
  padding: clamp(24px, 6vh, 56px) 16px; /* khoảng đệm chống dính mép trên/dưới */
  background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);
}

/* Thẻ card gọn gàng, không bị cắt, tự co vừa màn hình nhỏ */
.auth-card {
  width: min(440px, 100%);
  background: #fff;
  border-radius: 18px;
  box-shadow:
    0 10px 30px rgba(23, 32, 90, 0.12),
    0 3px 8px rgba(23, 32, 90, 0.08);
  padding: 26px 24px;
}

.auth-card__header {
  text-align: center;
  margin-bottom: 18px;
}

.auth-card__icon {
  width: 56px;
  height: 56px;
  margin: 0 auto 8px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-size: 28px;
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}

.auth-title {
  font-size: 20px;
  font-weight: 700;
  margin: 0 0 4px;
}

.auth-subtitle {
  color: #6b7280;
  font-size: 14px;
  margin: 0;
}

/* Form */
.auth-form {
  display: grid;
  gap: 10px;
  margin-top: 12px;
}

.form-label {
  font-size: 13px;
  color: #374151;
  margin-top: 6px;
}

.input-wrap {
  display: grid;
  position: relative;
}

.password-wrap {
  position: relative;
}

.input-wrap > input {
  height: 42px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 0 12px;
  outline: none;
  transition: box-shadow 0.2s ease, border-color 0.2s ease;
  background: #f9fafb;
}

.password-wrap > input {
  padding-right: 45px; /* Tạo không gian cho nút toggle */
}

.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
  font-size: 16px;
  padding: 4px;
  border-radius: 4px;
  transition: color 0.2s ease;
}

.password-toggle:hover {
  color: #374151;
}

.password-toggle:focus {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

/* Hàng tùy chọn */
.row-between {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 4px 0 6px;
}

.checkbox {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #4b5563;
}

.link {
  font-size: 13px;
  color: #3b82f6;
  text-decoration: none;
}
.link:hover {
  text-decoration: underline;
  color: #1d4ed8;
}

/* Nút đăng nhập */
.btn-primary {
  height: 44px;
  border: 0;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 8px;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: #fff;
  transition: all 0.3s ease;
}
.btn-primary:hover:not([disabled]) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}
.btn-primary[disabled] {
  opacity: 0.75;
  cursor: default;
}

/* Thông báo & note */
.error {
  color: #dc2626;
  margin-top: 10px;
  font-size: 14px;
}
.auth-note {
  margin: 12px 0 0;
  text-align: center;
  color: #6b7280;
  font-size: 12px;
}

/* Responsive nhỏ hơn 360px vẫn đẹp */
@media (max-width: 360px) {
  .auth-card { padding: 22px 18px; border-radius: 14px; }
  .auth-title { font-size: 18px; }
}
</style>
