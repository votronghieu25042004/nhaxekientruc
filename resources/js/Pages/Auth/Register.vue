<template>
  <LoginPage :errors="errors" initial-tab="register" />
</template>

<script setup>
import LoginPage from './Login.vue';

defineProps({
  errors: Object,
  flash: Object,
});
</script>
