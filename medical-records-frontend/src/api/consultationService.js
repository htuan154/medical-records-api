// src/api/consultationService.js
import api from './axios'

const ConsultationService = {
  // ========== Consultations ==========
  async getConsultations (params = {}) {
    const { data } = await api.get('/consultations', { params })
    return data
  },

  async getConsultation (id) {
    const { data } = await api.get(`/consultations/${id}`)
    return data
  },

  async createConsultation (payload) {
    const { data } = await api.post('/consultations', payload)
    return data
  },

  async updateConsultation (id, payload) {
    const { data } = await api.put(`/consultations/${id}`, payload)
    return data
  },

  async deleteConsultation (id, rev) {
    const { data } = await api.delete(`/consultations/${id}`, { data: { _rev: rev } })
    return data
  },

  async assignStaff (consultationId, payload) {
    const { data } = await api.post(`/consultations/${consultationId}/assign`, payload)
    return data
  },

  async closeConsultation (consultationId) {
    const { data } = await api.post(`/consultations/${consultationId}/close`)
    return data
  },

  // ========== Messages ==========
  async getMessages (params = {}) {
    const { data } = await api.get('/messages', { params })
    return data
  },

  async getMessage (id) {
    const { data } = await api.get(`/messages/${id}`)
    return data
  },

  async sendMessage (payload) {
    const { data } = await api.post('/messages', payload)
    return data
  },

  async updateMessage (id, payload) {
    const { data } = await api.put(`/messages/${id}`, payload)
    return data
  },

  async deleteMessage (id, rev) {
    const { data } = await api.delete(`/messages/${id}`, { data: { _rev: rev } })
    return data
  },

  async markAsRead (messageIds) {
    const { data } = await api.post('/messages/mark-read', { message_ids: messageIds })
    return data
  },

  async getMessagesByConsultation (consultationId, params = {}) {
    const { data } = await api.get(`/consultations/${consultationId}/messages`, { params })
    return data
  }
}

export default ConsultationService
