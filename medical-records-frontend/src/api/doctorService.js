// src/api/doctorService.js
import api from './axios'
import { buildQuery } from './helpers'

const DoctorService = {
  list (params = {}) {
    const qs = buildQuery(params)
    return api.get(`/doctors${qs ? `?${qs}` : ''}`).then((r) => r.data)
  },
  get (id) {
    return api.get(`/doctors/${id}`).then((r) => r.data)
  },
  create (payload) {
    return api.post('/doctors', payload).then((r) => r.data)
  },
  update (id, payload) {
    return api.put(`/doctors/${id}`, payload).then((r) => r.data)
  },
  remove (id) {
    return api.delete(`/doctors/${id}`).then((r) => r.data)
  },

  schedule (doctorId, params = {}) {
    const qs = buildQuery(params)
    return api
      .get(`/doctors/${doctorId}/schedule${qs ? `?${qs}` : ''}`)
      .then((r) => r.data)
  },
  updateSchedule (doctorId, payload) {
    return api.put(`/doctors/${doctorId}/schedule`, payload).then((r) => r.data)
  }
}

export default DoctorService
