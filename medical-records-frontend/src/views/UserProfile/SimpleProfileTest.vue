<template>
  <div class="simple-profile-test">
    <h2>Test Profile Component</h2>

    <div class="current-user" v-if="user">
      <h3>Current User:</h3>
      <pre>{{ JSON.stringify(user, null, 2) }}</pre>
    </div>

    <div class="actions">
      <button @click="testGetProfile" class="btn btn-primary me-2">
        Get Profile (/me)
      </button>
      <button @click="testUpdateProfile" class="btn btn-success me-2">
        Test Update Profile
      </button>
      <button @click="testChangePassword" class="btn btn-warning">
        Test Change Password
      </button>
    </div>

    <div class="result mt-3" v-if="result">
      <h4>Result:</h4>
      <pre :class="{'text-danger': isError, 'text-success': !isError}">{{ result }}</pre>
    </div>
  </div>
</template>

<script>
import { mapState } from 'vuex'
import { UserService } from '@/api'

export default {
  name: 'SimpleProfileTest',
  data () {
    return {
      result: '',
      isError: false
    }
  },
  computed: {
    ...mapState(['user'])
  },
  methods: {
    async testGetProfile () {
      try {
        this.result = 'Loading...'
        this.isError = false

        const response = await UserService.getProfile()
        this.result = JSON.stringify(response, null, 2)
        this.isError = false
      } catch (error) {
        console.error('Error getting profile:', error)
        this.result = `Error: ${error.response?.data?.message || error.message}`
        this.isError = true
      }
    },

    async testUpdateProfile () {
      try {
        this.result = 'Updating...'
        this.isError = false

        const testData = {
          name: 'Test User Updated',
          email: 'test.updated@example.com',
          phone: '0987654321',
          address: 'Test Address Updated'
        }

        const response = await UserService.updateProfile(testData)
        this.result = JSON.stringify(response, null, 2)
        this.isError = false

        // Refresh user data
        await this.$store.dispatch('fetchMe')
      } catch (error) {
        console.error('Error updating profile:', error)
        this.result = `Error: ${error.response?.data?.message || error.message}`
        this.isError = true
      }
    },

    async testChangePassword () {
      try {
        this.result = 'Changing password...'
        this.isError = false

        const passwordData = {
          current_password: 'password123',
          new_password: 'newpassword123',
          new_password_confirmation: 'newpassword123'
        }

        const response = await UserService.changeMyPassword(passwordData)
        this.result = JSON.stringify(response, null, 2)
        this.isError = false
      } catch (error) {
        console.error('Error changing password:', error)
        this.result = `Error: ${error.response?.data?.message || error.message}`
        this.isError = true
      }
    }
  }
}
</script>

<style scoped>
.simple-profile-test {
  padding: 20px;
  max-width: 800px;
  margin: 0 auto;
}

.current-user {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.actions {
  margin-bottom: 20px;
}

.result {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 8px;
  border-left: 4px solid #007bff;
}

.text-danger {
  color: #dc3545 !important;
}

.text-success {
  color: #28a745 !important;
}

pre {
  white-space: pre-wrap;
  word-break: break-word;
}
</style>
