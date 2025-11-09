// src/api/medicalTestService.js
import api from './axios'
import { buildQuery } from './helpers'

const MedicalTestService = {
  list (params = {}) {
    const qs = buildQuery(params)
    // Use public endpoint to bypass auth temporarily
    return api
      .get(`/medical-tests${qs ? `?${qs}` : ''}`)
      .then((r) => r.data)
  },
  get (id) {
    return api.get(`/medical-tests/${id}`).then((r) => r.data)
  },
  create (payload) {
    return api.post('/medical-tests', payload).then((r) => r.data)
  },
  update (id, payload) {
    return api.put(`/medical-tests/${id}`, payload).then((r) => r.data)
  },
  remove (id, rev) {
    // Nếu có rev, truyền qua query parameter
    if (rev) {
      return api.delete(`/medical-tests/${id}?rev=${encodeURIComponent(rev)}`).then((r) => r.data)
    }
    // Nếu không có rev, gọi như cũ (backend sẽ tự lấy rev)
    return api.delete(`/medical-tests/${id}`).then((r) => r.data)
  }
}

export default MedicalTestService
