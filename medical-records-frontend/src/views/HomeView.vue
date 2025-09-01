<template>
  <div class="py-5 text-center">
    <h1 class="display-6 mb-3">Hệ thống quản lý bệnh án</h1>
    <p class="text-muted">
      Quản lý bệnh nhân, hồ sơ khám chữa bệnh, đơn thuốc và lịch hẹn.
    </p>

    <div class="mt-3">
      <router-link
        v-if="!isAuthed"
        class="btn btn-primary me-2"
        to="/login"
      >
        Đăng nhập
      </router-link>

      <router-link
        v-else
        class="btn btn-primary me-2"
        to="/patients"
      >
        Quản lý bệnh nhân
      </router-link>
    </div>

    <div v-if="isAuthed" class="mt-4 text-muted">
      Đang đăng nhập: <b>{{ me?.username || me?.name }}</b>
    </div>
  </div>
</template>

<script>
export default {
  name: 'HomeView',
  computed: {
    isAuthed () { return this.$store.getters.isAuthenticated },
    me () { return this.$store.state.user }
  }
}
</script>
