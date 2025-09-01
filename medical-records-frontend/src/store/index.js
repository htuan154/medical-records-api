// src/store/index.js
import { createStore } from 'vuex'
import AuthService from '@/api/authService'
import { tokenStore } from '@/api/axios'

export default createStore({
  state: {
    user: JSON.parse(localStorage.getItem('user') || 'null'),
    loading: false
  },

  getters: {
    isAuthenticated: () => !!tokenStore.access,
    roles: state => state.user?.role_names || state.user?.roles || [],
    me: state => state.user
  },

  mutations: {
    SET_LOADING (state, v) {
      state.loading = v
    },
    SET_USER (state, u) {
      state.user = u
      if (u) localStorage.setItem('user', JSON.stringify(u))
      else localStorage.removeItem('user')
    },
    CLEAR_AUTH (state) {
      state.user = null
      localStorage.removeItem('user')
    }
  },

  actions: {
    async login ({ commit }, { username, password }) {
      commit('SET_LOADING', true)
      try {
        const data = await AuthService.login({ username, password })
        commit('SET_USER', data?.user || null)
        return data
      } finally {
        commit('SET_LOADING', false)
      }
    },

    async fetchMe ({ commit }) {
      if (!tokenStore.access) return null
      try {
        const me = await AuthService.me()
        commit('SET_USER', me)
        return me
      } catch (e) {
        await this.dispatch('logout')
        return null
      }
    },

    async logout ({ commit }) {
      try {
        await AuthService.logout()
      } catch (e) {
        // ignore
      }
      commit('CLEAR_AUTH')
    }
  }
})
