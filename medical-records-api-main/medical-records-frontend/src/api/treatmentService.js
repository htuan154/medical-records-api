// src/api/treatmentService.js
import api from './axios'

const buildQuery = (p = {}) =>
  Object.entries(p)
    .filter(([, v]) => v !== undefined && v !== null && v !== '')
    .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
    .join('&')

const TreatmentService = {
  list (params = {}) {
    const qs = buildQuery(params)
    return api.get(`/treatments-public${qs ? `?${qs}` : ''}`).then((r) => r.data)
  },
  get (id) {
    return api.get(`/treatments/${id}`).then((r) => r.data)
  },
  create (payload) {
    return api.post('/treatments', payload).then((r) => r.data)
  },
  update (id, payload) {
    return api.put(`/treatments/${id}`, payload).then((r) => r.data)
  },
  remove (id) {
    return api.delete(`/treatments/${id}`).then((r) => r.data)
  }
}

export default TreatmentService
