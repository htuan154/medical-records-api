// src/api/authService.js
import api, { tokenStore } from './axios'

const AuthService = {
  async login (credentials) {
    const { data } = await api.post('/login', credentials)
    if (data?.access_token) {
      tokenStore.access = data.access_token
      api.defaults.headers.common.Authorization = `Bearer ${data.access_token}` // set ngay
    }
    if (data?.refresh_token) tokenStore.refresh = data.refresh_token
    if (data?.user) localStorage.setItem('user', JSON.stringify(data.user))
    return data
  },

  async me () {
    const { data } = await api.get('/me')
    return data
  },

  async logout () {
    try { await api.post('/logout') } catch (_) {}
    tokenStore.clear()
  },

  async refresh () {
    const { data } = await api.post('/refresh', { refresh_token: tokenStore.refresh })
    if (data?.access_token) {
      tokenStore.access = data.access_token
      api.defaults.headers.common.Authorization = `Bearer ${data.access_token}`
    }
    if (data?.refresh_token) tokenStore.refresh = data.refresh_token
    return data
  }
}

export default AuthService
