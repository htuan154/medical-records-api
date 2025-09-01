<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
      <router-link class="navbar-brand" to="/">MedRecords</router-link>

      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#mainNavbar"
        aria-controls="mainNavbar"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <router-link class="nav-link" to="/">Home</router-link>
          </li>
          <li class="nav-item" v-if="isAuthed">
            <router-link class="nav-link" to="/patients">Bệnh nhân</router-link>
          </li>
        </ul>

        <div class="d-flex align-items-center gap-2">
          <span v-if="isAuthed" class="text-white-50 small">
            Hi, <b>{{ me?.username || me?.name }}</b>
          </span>
          <button
            v-if="isAuthed"
            class="btn btn-outline-light btn-sm"
            @click="onLogout"
          >
            Đăng xuất
          </button>
          <router-link v-else class="btn btn-light btn-sm" to="/login">
            Đăng nhập
          </router-link>
        </div>
      </div>
    </div>
  </nav>

  <main class="py-4">
    <div class="container">
      <router-view />
    </div>
  </main>
</template>

<script>
export default {
  name: 'AppShell',
  computed: {
    isAuthed () { return this.$store.getters.isAuthenticated },
    me () { return this.$store.state.user }
  },
  methods: {
    async onLogout () {
      await this.$store.dispatch('logout')
      this.$router.push({ name: 'login' })
    }
  }
}
</script>
