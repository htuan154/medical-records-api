// src/api/staffService.js
import api from './axios'

const buildQuery = (p = {}) =>
  Object.entries(p)
    .filter(([, v]) => v !== undefined && v !== null && v !== '')
    .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
    .join('&')

const StaffService = {
  list (params = {}) {
    const qs = buildQuery(params)
    return api.get(`/staff${qs ? `?${qs}` : ''}`).then((r) => r.data)
  },
  get (id) {
    return api.get(`/staff/${id}`).then((r) => r.data)
  },
  create (payload) {
    return api.post('/staff', payload).then((r) => r.data)
  },
  update (id, payload) {
    return api.put(`/staff/${id}`, payload).then((r) => r.data)
  },
  remove (id) {
    return api.delete(`/staff/${id}`).then((r) => r.data)
  },

  assignRoles (id, roles = []) {
    return api.post(`/staff/${id}/roles`, { roles }).then((r) => r.data)
  }
}

export default StaffService
