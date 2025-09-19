// src/api/axios.js
import axios from 'axios'

// Đồng bộ đúng host bạn đang login (127.0.0.1):
const API_BASE = 'http://127.0.0.1:9000/api/v1'
// Nếu BE là /api/v1/auth/refresh thì đổi thành '/auth/refresh'
const REFRESH_PATH = '/refresh'

const api = axios.create({
  baseURL: API_BASE,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  timeout: 20000
  // withCredentials: true, // bật nếu dùng cookie-session
})

// ===== Token store: chỉ dùng 2 key chuẩn =====
export const tokenStore = {
  get access () { return localStorage.getItem('access_token') },
  set access (v) { v ? localStorage.setItem('access_token', v) : localStorage.removeItem('access_token') },
  get refresh () { return localStorage.getItem('refresh_token') },
  set refresh (v) { v ? localStorage.setItem('refresh_token', v) : localStorage.removeItem('refresh_token') },
  clear () {
    localStorage.removeItem('access_token')
    localStorage.removeItem('refresh_token')
    localStorage.removeItem('user')
    delete api.defaults.headers.common.Authorization
  }
}

// ===== Gắn Bearer token cho mọi request =====
api.interceptors.request.use((cfg) => {
  const t = tokenStore.access
  if (t) cfg.headers.Authorization = `Bearer ${t}`
  return cfg
})

// ===== Cho phép app khởi động đọc token & set header ngay (tránh race 401) =====
export function initAuth () {
  const t = tokenStore.access
  if (t) api.defaults.headers.common.Authorization = `Bearer ${t}`
}

let isRefreshing = false
let queue = []

function flushQueue (error, newToken) {
  queue.forEach(({ resolve, reject, cfg }) => {
    if (error) reject(error)
    else {
      cfg.headers.Authorization = `Bearer ${newToken}`
      resolve(api(cfg))
    }
  })
  queue = []
}

const raw = axios.create({ baseURL: API_BASE }) // gọi refresh tách biệt

api.interceptors.response.use(
  (res) => res,
  async (err) => {
    const status = err?.response?.status
    const cfg = err?.config
    if (status === 401 && cfg && !cfg._retry) {
      if (!tokenStore.refresh) {
        tokenStore.clear()
        return Promise.reject(err)
      }

      if (isRefreshing) {
        return new Promise((resolve, reject) => queue.push({ resolve, reject, cfg }))
      }

      cfg._retry = true
      isRefreshing = true
      try {
        const r = await raw.post(REFRESH_PATH, { refresh_token: tokenStore.refresh })
        const newAccess = r?.data?.access_token
        const newRefresh = r?.data?.refresh_token
        if (!newAccess) throw new Error('No access_token from refresh')

        tokenStore.access = newAccess
        if (newRefresh) tokenStore.refresh = newRefresh
        api.defaults.headers.common.Authorization = `Bearer ${newAccess}`

        flushQueue(null, newAccess)
        isRefreshing = false

        cfg.headers.Authorization = `Bearer ${newAccess}`
        return api(cfg)
      } catch (e) {
        flushQueue(e, null)
        isRefreshing = false
        tokenStore.clear()
        return Promise.reject(e)
      }
    }
    return Promise.reject(err?.response?.data || err)
  }
)

export default api
