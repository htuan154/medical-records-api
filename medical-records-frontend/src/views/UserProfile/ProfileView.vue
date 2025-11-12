<template>
  <div class="profile-container">
    <!-- Header Section -->
    <div class="header-section">
      <div class="header-content">
          <div class="header-left">
            <h2><i class="fas fa-user-circle me-2"></i>Hồ Sơ Cá Nhân</h2>
            <p class="header-subtitle">Quản lý thông tin cá nhân và đổi mật khẩu</p>
          </div>
      </div>
      <div class="back-home-btn">
        <button class="btn btn-light" @click="$router.push({ name: 'home' })">
          <i class="fas fa-arrow-left me-2"></i>Quay về trang chủ
        </button>
      </div>
    </div>

    <div class="content-wrapper">
      <!-- Form duy nhất gộp tất cả thông tin giống trang user -->
      <div class="profile-form-container">
        <div class="card">
          <div class="card-header">
          </div>
          <div class="card-body">
            <form @submit.prevent="updateUser">
              <div class="row">
                <!-- Cột trái: Thông tin cá nhân -->
                <div class="col-md-6">
                  <h6 class="section-title">Thông tin cá nhân</h6>

                  <div class="mb-3">
                    <label class="form-label">ID <span class="text-muted">(readonly)</span></label>
                    <input type="text" class="form-control" :value="currentUser?._id || 'N/A'" readonly>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Tên đăng nhập</label>
                    <input v-model="userForm.username" type="text" class="form-control" :class="{ 'is-invalid': formErrors.username }" placeholder="Nhập tên đăng nhập">
                    <div v-if="formErrors.username" class="invalid-feedback">{{ formErrors.username }}</div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Họ tên</label>
                    <input v-model="userForm.name" type="text" class="form-control" :class="{ 'is-invalid': formErrors.name }" placeholder="Nhập họ tên">
                    <div v-if="formErrors.name" class="invalid-feedback">{{ formErrors.name }}</div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input v-model="userForm.email" type="email" class="form-control" :class="{ 'is-invalid': formErrors.email }" placeholder="Nhập email">
                    <div v-if="formErrors.email" class="invalid-feedback">{{ formErrors.email }}</div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <input v-model="userForm.phone" type="text" class="form-control" :class="{ 'is-invalid': formErrors.phone }" placeholder="Nhập số điện thoại">
                    <div v-if="formErrors.phone" class="invalid-feedback">{{ formErrors.phone }}</div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Địa chỉ</label>
                    <textarea v-model="userForm.address" class="form-control" rows="3" :class="{ 'is-invalid': formErrors.address }" placeholder="Nhập địa chỉ"></textarea>
                    <div v-if="formErrors.address" class="invalid-feedback">{{ formErrors.address }}</div>
                  </div>
                </div>

                <!-- Cột phải: Mật khẩu và thông tin khác -->
                <div class="col-md-6">
                  <h6 class="section-title">Bảo mật & Phân quyền</h6>

                  <div class="mb-3">
                    <label class="form-label">Mật khẩu mới</label>
                    <div class="input-group">
                      <input
                        v-model="userForm.password"
                        :type="showPassword ? 'text' : 'password'"
                        class="form-control"
                        :class="{ 'is-invalid': formErrors.password }"
                        placeholder="Để trống nếu không đổi mật khẩu"
                      >
                      <button type="button" class="btn btn-outline-secondary" @click="showPassword = !showPassword">
                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                      </button>
                    </div>
                    <small class="form-text text-muted">Để trống nếu không muốn đổi mật khẩu</small>
                    <div v-if="formErrors.password" class="invalid-feedback">{{ formErrors.password }}</div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Vai trò</label>
                    <input type="text" class="form-control" :value="currentUser?.role_names?.join(', ') || 'N/A'" readonly>
                    <small class="form-text text-muted">Vai trò chỉ có thể thay đổi bởi admin</small>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Loại tài khoản</label>
                    <input type="text" class="form-control" :value="currentUser?.account_type || 'N/A'" readonly>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Trạng thái</label>
                    <input type="text" class="form-control" :value="currentUser?.status || 'N/A'" readonly>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Ngày tạo</label>
                    <input type="text" class="form-control" :value="formatDate(currentUser?.created_at)" readonly>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Cập nhật cuối</label>
                    <input type="text" class="form-control" :value="formatDate(currentUser?.updated_at)" readonly>
                  </div>
                </div>
              </div>

              <!-- Form Actions -->
              <div class="form-actions mt-4">
                <button type="submit" class="btn btn-primary me-3" :disabled="isSubmitting">
                  <i class="fas fa-save me-2"></i>
                  {{ isSubmitting ? 'Đang lưu...' : 'Lưu Thay Đổi' }}
                </button>
                <button type="button" class="btn btn-secondary" @click="loadUserData">
                  <i class="fas fa-undo me-2"></i>
                  Khôi phục
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import UserService from '@/api/userService'

export default {
  name: 'ProfileView',

  data () {
    return {
      currentUser: null, // Lưu user data từ API với _id và _rev
      userForm: {
        username: '',
        name: '',
        email: '',
        phone: '',
        address: '',
        password: ''
      },
      formErrors: {},
      isSubmitting: false,
      showPassword: false
    }
  },

  computed: {
    user () {
      return this.$store.state.user
    }
  },

  async created () {
    console.log('created hook - store user:', this.$store.state.user)
    await this.loadUserData()
  },

  methods: {
    async loadUserData () {
      try {
        // Debug Vuex Store
        console.log('loadUserData - Vuex Store user:', this.$store.state.user)

        // Lấy thông tin user từ Vuex Store
        const storeUser = this.$store.state.user
        const userId = storeUser?._id || storeUser?.id
        if (!userId) {
          alert('Không tìm thấy thông tin user! Vui lòng đăng nhập lại.')
          this.$router.push({ name: 'login' }) // Chuyển hướng về trang đăng nhập
          return
        }

        // Gọi API để lấy dữ liệu chi tiết của user
        const userData = await UserService.get(userId)

        // Debug API response
        console.log('API user data:', userData)

        // Gán dữ liệu vào currentUser
        this.currentUser = userData
        console.log('Gán vào currentUser:', this.currentUser)

        // Gán dữ liệu vào form
        this.userForm = {
          username: userData.username || '',
          name: userData.name || '',
          email: userData.email || '',
          phone: userData.phone || '',
          address: userData.address || '',
          password: '' // Luôn để trống password
        }
        console.log('Gán vào userForm:', this.userForm)
      } catch (error) {
        console.error('Lỗi tải dữ liệu user:', error)
        alert('Lỗi khi tải dữ liệu: ' + (error.response?.data?.message || error.message))
      }
    },

    async updateUser () {
      if (!this.currentUser || !this.currentUser._id || !this.currentUser._rev) {
        alert('Không có thông tin user để cập nhật!')
        return
      }

      this.isSubmitting = true
      this.formErrors = {}

      try {
        // Chuẩn bị data update giống trang user
        const updateData = {
          _rev: this.currentUser._rev,
          username: this.userForm.username,
          name: this.userForm.name,
          email: this.userForm.email,
          phone: this.userForm.phone,
          address: this.userForm.address
        }

        // Thêm password nếu người dùng nhập
        if (this.userForm.password && this.userForm.password.trim()) {
          updateData.password = this.userForm.password
        }

        await UserService.update(this.currentUser._id, updateData)

        alert('Cập nhật hồ sơ thành công!')

        // Reload dữ liệu mới để có _rev mới
        await this.loadUserData()

        // Cập nhật store
        await this.$store.dispatch('fetchMe')
      } catch (error) {
        console.error('Lỗi cập nhật user:', error)

        if (error.response?.data?.errors) {
          this.formErrors = error.response.data.errors
        }

        const message = error.response?.data?.message || 'Có lỗi xảy ra khi cập nhật'
        alert('Lỗi: ' + message)
      } finally {
        this.isSubmitting = false
      }
    },
    formatDate (dateString) {
      if (!dateString) return 'N/A'
      try {
        return new Date(dateString).toLocaleString('vi-VN')
      } catch {
        return 'N/A'
      }
    }
  }
}
</script>

<style scoped>
/* Container chính */
.profile-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem 0;
}

/* Header section */
.header-section {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  margin-bottom: 2rem;
  padding: 1.5rem 0;
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left h2 {
  color: white;
  margin-bottom: 0.5rem;
  font-weight: 600;
}

.header-subtitle {
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 0;
}

.user-avatar {
  width: 60px;
  height: 60px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
}

.back-home-btn {
  max-width: 1200px;
  margin: 1rem auto 0;
  padding: 0 1rem;
}

/* Content wrapper */
.content-wrapper {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* Form container */
 .profile-form-container {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(102,126,234,0.18);
  padding: 3rem 2.5rem;
  max-width: 100%;
  width: 100%;
  margin: 0;
  overflow: hidden;
}

.card {
  border: none;
  box-shadow: none;
}

.card-body {
  padding: 0;
}

.row {
  display: flex;
  gap: 2.5rem;
}

.col-md-6 {
  flex: 1;
  min-width: 0;
}

/* Section titles */
.section-title {
  color: #4facfe;
  font-weight: 800;
  margin-bottom: 1.5rem;
  font-size: 1.15rem;
  letter-spacing: 0.5px;
}

/* Form controls */
.form-label {
  color: #333;
  font-weight: 600;
  margin-bottom: 0.4rem;
  font-size: 1.05rem;
}

.form-control {
  border: 1px solid #e1e5e9;
  border-radius: 12px;
  padding: 0.8rem 1.1rem;
  font-size: 1rem;
  background: #f8f9fa;
  transition: all 0.3s ease;
}

.form-control:focus {
  border-color: #4facfe;
  box-shadow: 0 0 0 0.15rem rgba(79, 172, 254, 0.15);
}

.form-control:read-only {
  background-color: #e9ecef;
  border-color: #e9ecef;
}

/* Input group for password */
.input-group .btn-outline-secondary {
  border-left: none;
  border-color: #e1e5e9;
}

.input-group .btn-outline-secondary:hover {
  background-color: #f8f9fa;
  border-color: #4facfe;
}

/* Form actions */
.form-actions {
  padding-top: 2.2rem;
  border-top: 1px solid #e9ecef;
  display: flex;
  gap: 1.5rem;
  justify-content: flex-end;
}

.btn-primary {
  background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 0.9rem 2rem;
  font-weight: 700;
  font-size: 1.05rem;
  box-shadow: 0 2px 8px rgba(79,172,254,0.14);
  transition: all 0.3s ease;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(79, 172, 254, 0.22);
  background: linear-gradient(90deg, #00f2fe 0%, #4facfe 100%);
}

.btn-secondary {
  background-color: #e1e5e9;
  color: #333;
  border: none;
  border-radius: 10px;
  padding: 0.9rem 2rem;
  font-weight: 700;
  font-size: 1.05rem;
}

.btn-secondary:hover {
  background-color: #d1d5db;
}

.btn-light {
  border-radius: 10px;
  padding: 0.7rem 1.3rem;
  font-weight: 700;
  background: #fff;
  color: #4facfe;
  border: 1px solid #4facfe;
  box-shadow: 0 2px 8px rgba(79,172,254,0.10);
}

/* Error states */
.is-invalid {
  border-color: #dc3545;
  background: #fff0f0;
}

.invalid-feedback {
  display: block;
  color: #dc3545;
  font-size: 0.95rem;
  margin-top: 0.2rem;
}

/* Responsive */
@media (max-width: 900px) {
  .profile-form-container {
    padding: 1.5rem 0.7rem;
    border-radius: 8px;
  }
  .row {
    flex-direction: column;
    gap: 1.2rem;
  }
  .form-actions {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
}
</style>
