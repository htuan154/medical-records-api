// src/api/userService.js
import api from './axios'

const buildQuery = (p = {}) =>
  Object.entries(p)
    .filter(([, v]) => v !== undefined && v !== null && v !== '')
    .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
    .join('&')

const UserService = {
  list (params = {}) {
    const qs = buildQuery(params)
    return api.get(`/users${qs ? `?${qs}` : ''}`).then((r) => r.data)
  },
  get (id) {
    return api.get(`/users/${id}`).then((r) => r.data)
  },
  create (payload) {
    return api.post('/users', payload).then((r) => r.data)
  },
  update (id, payload) {
    return api.put(`/users/${id}`, payload).then((r) => r.data)
  },
  remove (id) {
    return api.delete(`/users/${id}`).then((r) => r.data)
  },

  changePassword (id, payload) {
    return api.post(`/users/${id}/change-password`, payload).then((r) => r.data)
  },
  assignRoles (id, roles = []) {
    return api.post(`/users/${id}/roles`, { roles }).then((r) => r.data)
  }
}

export default UserService
