// src/api/invoiceService.js
import api from './axios'
import { buildQuery } from './helpers'

const InvoiceService = {
  list (params = {}) {
    const qs = buildQuery(params)
    return api.get(`/invoices-public${qs ? `?${qs}` : ''}`).then((r) => r.data)
  },
  get (id) {
    return api.get(`/invoices/${id}`).then((r) => r.data)
  },
  create (payload) {
    return api.post('/invoices', payload).then((r) => r.data)
  },
  update (id, payload) {
    return api.put(`/invoices/${id}`, payload).then((r) => r.data)
  },

  // ✅ FIX: Thêm rev parameter support
  remove (id, rev) {
    if (rev) {
      return api.delete(`/invoices/${id}?rev=${encodeURIComponent(rev)}`).then((r) => r.data)
    }
    return api.delete(`/invoices/${id}`).then((r) => r.data)
  },

  pay (id, payload = {}) {
    return api.post(`/invoices/${id}/pay`, payload).then((r) => r.data)
  },
  void (id, reason) {
    return api.post(`/invoices/${id}/void`, { reason }).then((r) => r.data)
  },
  download (id) {
    return api.get(`/invoices/${id}/download`, { responseType: 'blob' })
  }
}

export default InvoiceService
