// src/store/index.js
import { createStore } from 'vuex'
import AuthService from '@/api/authService'
import { tokenStore } from '@/api/axios'

export default createStore({
  state: {
    user: (() => {
      const raw = localStorage.getItem('user')
      console.log('🔍 Raw localStorage user:', raw)
      if (!raw || raw === 'null') return null

      try {
        let parsed = JSON.parse(raw)
        console.log('🔍 Parsed object:', parsed)

        // Extract user from nested structure
        // Backend returns: {ok, token: {iss, aud, ..., u: {user_data}}}
        if (parsed.token && parsed.token.u) {
          console.log('🔍 User is nested in token.u field')
          parsed = parsed.token.u
        } else if (parsed.u) {
          console.log('🔍 User is nested in .u field')
          parsed = parsed.u
        }

        console.log('🔍 Final user:', parsed)
        console.log('🔍 Final role_names:', parsed.role_names)
        return parsed
      } catch (e) {
        console.error('❌ Failed to parse user:', e)
        return null
      }
    })(),
    loading: false
  },
  getters: {
    isAuthenticated: () => !!tokenStore.access,
    roles: state => {
      if (!state.user) {
        console.log('🔍 No user in state')
        return []
      }

      // Direct access to role_names
      console.log('🔍 Raw user:', state.user)
      console.log('🔍 Type:', typeof state.user.role_names)
      console.log('🔍 Value:', state.user.role_names)

      const roleNames = state.user.role_names || state.user.roles || []
      console.log('🔍 Final roles:', roleNames)

      // Ensure we return an array
      if (Array.isArray(roleNames)) {
        return roleNames
      }

      if (typeof roleNames === 'string') {
        return [roleNames]
      }

      return []
    },
    me: state => state.user
  },

  mutations: {
    SET_LOADING (state, v) {
      state.loading = v
    },
    SET_USER (state, u) {
      console.log('🔍 SET_USER called with:', u)

      // Extract user from nested structure if needed
      let user = u
      if (u && u.token && u.token.u) {
        console.log('🔍 Extracting user from token.u field')
        user = u.token.u
      } else if (u && u.u) {
        console.log('🔍 Extracting user from .u field')
        user = u.u
      }

      console.log('🔍 Storing user:', user)
      console.log('🔍 User role_names:', user?.role_names)

      state.user = user
      if (user) localStorage.setItem('user', JSON.stringify(user))
      else localStorage.removeItem('user')
    },
    CLEAR_AUTH (state) {
      state.user = null
      localStorage.removeItem('user')
      localStorage.removeItem('access_token')
      localStorage.removeItem('refresh_token')
    }
  },

  actions: {
    async login ({ commit }, { username, password }) {
      commit('SET_LOADING', true)
      try {
        const data = await AuthService.login({ username, password })
        console.log('🔍 Login response:', data)
        console.log('🔍 User data:', data?.user)
        console.log('🔍 User role_names:', data?.user?.role_names)

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
