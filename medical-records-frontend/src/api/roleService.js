// src/api/roleService.js
import api from './axios'

const buildQuery = (p = {}) =>
  Object.entries(p)
    .filter(([, v]) => v !== undefined && v !== null && v !== '')
    .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
    .join('&')

const RoleService = {
  list (params = {}) {
    const qs = buildQuery(params)
    return api.get(`/roles${qs ? `?${qs}` : ''}`).then((r) => r.data)
  },
  get (id) {
    return api.get(`/roles/${id}`).then((r) => r.data)
  },
  create (payload) {
    return api.post('/roles', payload).then((r) => r.data)
  },
  update (id, payload) {
    return api.put(`/roles/${id}`, payload).then((r) => r.data)
  },
  remove (id) {
    return api.delete(`/roles/${id}`).then((r) => r.data)
  }
}

export default RoleService
