// src/api/patientService.js
import api from './axios'

const buildQuery = (p = {}) =>
  Object.entries(p)
    .filter(([, v]) => v !== undefined && v !== null && v !== '')
    .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
    .join('&')

const PatientService = {
  list (params = {}) {
    const qs = buildQuery(params)
    return api.get(`/patients${qs ? `?${qs}` : ''}`).then((r) => r.data)
  },
  get (id) {
    return api.get(`/patients/${id}`).then((r) => r.data)
  },
  create (payload) {
    return api.post('/patients', payload).then((r) => r.data)
  },
  update (id, payload) {
    return api.put(`/patients/${id}`, payload).then((r) => r.data)
  },
  remove (id) {
    return api.delete(`/patients/${id}`).then((r) => r.data)
  },

  // liên quan đến bệnh án & lịch hẹn của bệnh nhân
  records (patientId, params = {}) {
    const qs = buildQuery(params)
    return api
      .get(`/patients/${patientId}/medical-records${qs ? `?${qs}` : ''}`)
      .then((r) => r.data)
  },
  appointments (patientId, params = {}) {
    const qs = buildQuery(params)
    return api
      .get(`/patients/${patientId}/appointments${qs ? `?${qs}` : ''}`)
      .then((r) => r.data)
  }
}

export default PatientService
