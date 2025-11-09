// src/api/staffService.js
import api from './axios'

// Với baseURL đã có /api/v1, chỉ cần /staffs-public
const prefix = '/staffs-public'

const StaffService = {
  /**
   * params hỗ trợ: { limit, skip, staff_type, department, status, day, q }
   * (Backend sẽ bỏ qua tham số không dùng)
   */
  list (params = {}) {
    return api.get(prefix, { params }).then(r => r.data)
  },

  get (id) {
    return api.get(`${prefix}/${encodeURIComponent(id)}`).then(r => r.data)
  },

  create (payload) {
    return api.post(prefix, payload).then(r => r.data)
  },

  update (id, payload) {
    return api.put(`${prefix}/${encodeURIComponent(id)}`, payload).then(r => r.data)
  },

  remove (id) {
    return api.delete(`${prefix}/${encodeURIComponent(id)}`).then(r => r.data)
  }
}

export default StaffService
