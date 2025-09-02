// src/api/axios.js
import axios from 'axios'

const API_BASE = 'http://localhost:9000/api/v1'

const api = axios.create({
  baseURL: API_BASE,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json'
  },
  timeout: 20000
})

// ---- Helpers lưu token ----
const tokenStore = {
  get access () {
    return localStorage.getItem('access_token')
  },
  set access (val) {
    if (val) localStorage.setItem('access_token', val)
    else localStorage.removeItem('access_token')
  },
  get refresh () {
    return localStorage.getItem('refresh_token')
  },
  set refresh (val) {
    if (val) localStorage.setItem('refresh_token', val)
    else localStorage.removeItem('refresh_token')
  },
  clear () {
    this.access = null
    this.refresh = null
    localStorage.removeItem('user')
    localStorage.removeItem('token')
  }
}

// ---- Request: gắn Authorization ----
api.interceptors.request.use(config => {
  const token = tokenStore.access || localStorage.getItem('token')
  if (token && !config.headers.Authorization) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// ---- Response: tự refresh khi 401 ----
let isRefreshing = false
let pendingQueue = []

const processQueue = (error, token = null) => {
  pendingQueue.forEach(({ resolve, reject, cfg }) => {
    if (error) {
      reject(error)
    } else {
      cfg.headers.Authorization = `Bearer ${token}`
      resolve(api(cfg))
    }
  })
  pendingQueue = []
}

const raw = axios.create({ baseURL: API_BASE })

api.interceptors.response.use(
  res => res,
  async error => {
    const originalConfig = error?.config
    const status = error?.response?.status

    if (status === 401 && !originalConfig?._retry) {
      if (!tokenStore.refresh) {
        tokenStore.clear()
        return Promise.reject(error)
      }

      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          pendingQueue.push({ resolve, reject, cfg: originalConfig })
        })
      }

      originalConfig._retry = true
      isRefreshing = true
      try {
        // đúng route + field snake_case
        const resp = await raw.post('/refresh', {
          refresh_token: tokenStore.refresh
        })
        const newAccess = resp?.data?.access_token
        const newRefresh = resp?.data?.refresh_token

        if (newAccess) tokenStore.access = newAccess
        if (newRefresh) tokenStore.refresh = newRefresh

        processQueue(null, newAccess)
        isRefreshing = false

        originalConfig.headers.Authorization = `Bearer ${newAccess}`
        return api(originalConfig)
      } catch (e) {
        processQueue(e, null)
        isRefreshing = false
        tokenStore.clear()
        return Promise.reject(e)
      }
    }

    return Promise.reject(
      error?.response?.data || { message: error?.message || 'Request error', status }
    )
  }
)
export default api
export { tokenStore }
