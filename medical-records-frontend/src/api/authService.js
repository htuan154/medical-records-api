// src/api/authService.js
import api, { tokenStore } from './axios'

const AuthService = {
  async login (credentials) {
    // đúng route: POST /login
    const { data } = await api.post('/login', credentials)
    // đúng field: snake_case
    if (data?.access_token) tokenStore.access = data.access_token
    if (data?.refresh_token) tokenStore.refresh = data.refresh_token
    if (data?.user) localStorage.setItem('user', JSON.stringify(data.user))
    return data
  },

  async logout () {
    try {
      // đúng route: POST /logout (không cần body)
      await api.post('/logout')
    } catch (e) {
      // ignore
    } finally {
      tokenStore.clear()
    }
  },

  // đúng route: GET /me
  me () {
    return api.get('/me').then(r => r.data)
  },

  async refresh () {
    const { data } = await api.post('/refresh', {
      // đúng field: refresh_token
      refresh_token: tokenStore.refresh
    })
    if (data?.access_token) tokenStore.access = data.access_token
    if (data?.refresh_token) tokenStore.refresh = data.refresh_token
    return data
  }
}

export default AuthService
