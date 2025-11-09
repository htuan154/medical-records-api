import { createApp } from 'vue'
import App from './App.vue'
import './registerServiceWorker'
import router from './router'
import store from './store'

// Bootstrap CSS & Icons (tuỳ chọn)
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap-icons/font/bootstrap-icons.css'

// Bootstrap JS bundle (đã gồm Popper)
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import { initAuth } from './api/axios'

// ✅ Init auth BEFORE mounting app
initAuth()

// ✅ Restore user from localStorage if exists
const savedUser = localStorage.getItem('user')
if (savedUser) {
  try {
    const user = JSON.parse(savedUser)
    store.commit('SET_USER', user)
  } catch (e) {
    console.error('Failed to parse saved user:', e)
    localStorage.removeItem('user')
  }
}

createApp(App).use(store).use(router).mount('#app')
