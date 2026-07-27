<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const email = ref('')
const password = ref('')
const erreur = ref('')
const authStore = useAuthStore()
const router = useRouter()

async function handleLogin() {
  erreur.value = ''
  try {
    await authStore.login(email.value, password.value)
    router.push('/dashboard')
  } catch (e) {
    erreur.value = 'Email ou mot de passe incorrect.'
  }
}
</script>

<template>
  <div class="login-container">
    <h1>Connexion</h1>
    <form @submit.prevent="handleLogin">
      <input v-model="email" type="email" placeholder="Email" required />
      <input v-model="password" type="password" placeholder="Mot de passe" required />
      <button type="submit">Se connecter</button>
      <p v-if="erreur" style="color: red">{{ erreur }}</p>
    </form>
  </div>
</template>

<style scoped>
.login-container {
  max-width: 400px;
  margin: 100px auto;
  text-align: center;
}
form {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
input, button {
  padding: 10px;
  font-size: 16px;
}
button {
  background: #2563eb;
  color: white;
  border: none;
  cursor: pointer;
}
</style>