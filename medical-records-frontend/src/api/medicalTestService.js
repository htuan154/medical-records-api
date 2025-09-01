// src/api/medicalTestService.js
import api from './axios'
import { buildQuery } from './helpers'

const MedicalTestService = {
  list (params = {}) {
    const qs = buildQuery(params)
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
  remove (id) {
    return api.delete(`/medical-tests/${id}`).then((r) => r.data)
  }
}

export default MedicalTestService
