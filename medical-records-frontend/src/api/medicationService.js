// src/api/medicationService.js
import api from './axios'
import { buildQuery } from './helpers'

const MedicationService = {
  list (params = {}) {
    const qs = buildQuery(params)
    return api.get(`/medications${qs ? `?${qs}` : ''}`).then((r) => r.data)
  },
  get (id) {
    return api.get(`/medications/${id}`).then((r) => r.data)
  },
  create (payload) {
    return api.post('/medications', payload).then((r) => r.data)
  },
  update (id, payload) {
    return api.put(`/medications/${id}`, payload).then((r) => r.data)
  },
  remove (id, rev) {
    if (rev) {
      return api.delete(`/medications/${id}?rev=${encodeURIComponent(rev)}`).then((r) => r.data)
    }
    return api.delete(`/medications/${id}`).then((r) => r.data)
  },

  // ✅ NEW: Search method cho medication lookup
  search (query) {
    const qs = buildQuery({
      q: query,
      limit: 20,
      include_docs: true
    })
    return api.get(`/medications${qs ? `?${qs}` : ''}`).then((r) => r.data)
  }
}

export default MedicationService
