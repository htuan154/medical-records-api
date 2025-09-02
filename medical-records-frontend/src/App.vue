<!-- App.vue -->
<template>
  <div>
    <!-- giữ nguyên navbar/header của bạn -->

    <main class="app-main">
      <router-view v-slot="{ Component, route }">
        <!-- Nếu route yêu cầu fullWidth (như Login) thì không bọc container -->
        <component :is="Component" v-if="route.meta && route.meta.fullWidth" />
        <!-- Mặc định: các trang còn lại bọc trong container như cũ -->
        <div v-else class="container py-4">
          <component :is="Component" />
        </div>
      </router-view>
    </main>
  </div>
</template>

<script>
export default { name: 'App' }
</script>

<style>
/* chỉnh chiều cao tối thiểu để nội dung không bị dính đáy khi có navbar */
.app-main{ min-height: calc(100vh - 56px); } /* 56px ~ chiều cao navbar Bootstrap */
</style>
