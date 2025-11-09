// src/api/appointmentService.js
import api from './axios'
import { buildQuery } from './helpers'

const AppointmentService = {
  list (params = {}) {
    const qs = buildQuery(params)
    return api.get(`/appointments${qs ? `?${qs}` : ''}`).then((r) => r.data)
  },
  get (id) {
    return api.get(`/appointments/${id}`).then((r) => r.data)
  },
  create (payload) {
    return api.post('/appointments', payload).then((r) => r.data)
  },
  update (id, payload) {
    return api.put(`/appointments/${id}`, payload).then((r) => r.data)
  },
  remove (id) {
    return api.delete(`/appointments/${id}`).then((r) => r.data)
  },

  approve (id) {
    return api.post(`/appointments/${id}/approve`).then((r) => r.data)
  },
  cancel (id, reason) {
    return api.post(`/appointments/${id}/cancel`, { reason }).then((r) => r.data)
  },
  complete (id, summary) {
    return api.post(`/appointments/${id}/complete`, { summary }).then((r) => r.data)
  },
  reschedule (id, payload) {
    return api.post(`/appointments/${id}/reschedule`, payload).then((r) => r.data)
  }
}

export default AppointmentService
