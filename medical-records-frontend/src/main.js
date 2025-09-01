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

createApp(App).use(store).use(router).mount('#app')
