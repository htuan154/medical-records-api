// src/api/medicalRecordService.js
import api from './axios'
import { buildQuery, toFormData } from './helpers'

const MedicalRecordService = {
  list (params = {}) {
    const qs = buildQuery(params)
    return api
      .get(`/medical-records${qs ? `?${qs}` : ''}`)
      .then((r) => r.data)
  },
  get (id) {
    return api.get(`/medical-records/${id}`).then((r) => r.data)
  },
  create (payload) {
    return api.post('/medical-records', payload).then((r) => r.data)
  },
  update (id, payload) {
    return api.put(`/medical-records/${id}`, payload).then((r) => r.data)
  },

  // ✅ FIX: Thêm rev parameter support
  remove (id, rev) {
    // Nếu có rev, truyền qua query parameter
    if (rev) {
      return api.delete(`/medical-records/${id}?rev=${encodeURIComponent(rev)}`).then((r) => r.data)
    }
    // Nếu không có rev, gọi như cũ (backend sẽ tự lấy rev)
    return api.delete(`/medical-records/${id}`).then((r) => r.data)
  },

  // liên quan bệnh nhân
  forPatient (patientId, params = {}) {
    const qs = buildQuery(params)
    return api
      .get(`/patients/${patientId}/medical-records${qs ? `?${qs}` : ''}`)
      .then((r) => r.data)
  },

  // tệp đính kèm
  uploadAttachment (id, file) {
    return api
      .post(`/medical-records/${id}/attachments`, toFormData({ file }), {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      .then((r) => r.data)
  },
  deleteAttachment (id, attachmentId) {
    return api
      .delete(`/medical-records/${id}/attachments/${attachmentId}`)
      .then((r) => r.data)
  },
  downloadAttachment (id, attachmentId) {
    return api.get(
      `/medical-records/${id}/attachments/${attachmentId}`,
      { responseType: 'blob' }
    )
  }
}

export default MedicalRecordService
