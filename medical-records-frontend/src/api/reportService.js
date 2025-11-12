// src/api/reportService.js
import api from './axios'

const buildQuery = (p = {}) =>
  Object.entries(p)
    .filter(([, v]) => v !== undefined && v !== null && v !== '')
    .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
    .join('&')

const ReportService = {
  // Dashboard data
  getDashboardStats (params = {}) {
    const qs = buildQuery(params)
    return api.get(`/reports/dashboard${qs ? `?${qs}` : ''}`).then((r) => r.data)
  },

  // Patient statistics
  getPatientStats (params = {}) {
    const qs = buildQuery(params)
    return api.get(`/reports/patient-stats${qs ? `?${qs}` : ''}`).then((r) => r.data)
  },

  // Doctor records statistics
  getDoctorRecords (params = {}) {
    const qs = buildQuery(params)
    return api.get(`/reports/doctor-records${qs ? `?${qs}` : ''}`).then((r) => r.data)
  },

  // Disease statistics
  getDiseaseStats (params = {}) {
    const qs = buildQuery(params)
    return api.get(`/reports/disease-stats${qs ? `?${qs}` : ''}`).then((r) => r.data)
  },

  // Revenue statistics
  getRevenueStats (params = {}) {
    const qs = buildQuery(params)
    return api.get(`/reports/revenue-stats${qs ? `?${qs}` : ''}`).then((r) => r.data)
  },

  // Appointment statistics
  getAppointmentStats (params = {}) {
    const qs = buildQuery(params)
    return api.get(`/reports/appointment-stats${qs ? `?${qs}` : ''}`).then((r) => r.data)
  },

  // Medication statistics
  getMedicationStats (params = {}) {
    const qs = buildQuery(params)
    return api.get(`/reports/medication-stats${qs ? `?${qs}` : ''}`).then((r) => r.data)
  },

  // Export reports
  exportReport (type, params = {}) {
    const qs = buildQuery(params)
    return api.get(`/reports/export/${type}${qs ? `?${qs}` : ''}`, {
      responseType: 'blob'
    }).then((r) => r.data)
  },

  // Generate custom report
  generateCustomReport (config) {
    return api.post('/reports/custom', config).then((r) => r.data)
  }
}

export default ReportService
