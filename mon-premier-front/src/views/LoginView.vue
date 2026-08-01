<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const email = ref('')
const password = ref('')
const erreur = ref('')
const loading = ref(false)

const authStore = useAuthStore()
const router = useRouter()

async function handleLogin() {
  erreur.value = ''
  loading.value = true
  try {
    await authStore.login(email.value, password.value)
    router.push('/dashboard')
  } catch (e) {
    erreur.value = 'Email ou mot de passe incorrect.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-wrapper">
    <div class="login-card panel">
      <!-- En-tête de connexion -->
      <div class="brand-header">
        <div class="brand-badge">TERMINAL ALLIANCE</div>
        <h1 class="login-title">Surveillance IA</h1>
        <p class="login-subtitle">Connectez-vous pour accéder au tableau de bord</p>
      </div>

      <!-- Formulaire -->
      <form @submit.prevent="handleLogin" class="login-form">
        <div class="field-group">
          <label class="field-label">Adresse email</label>
          <input 
            v-model="email" 
            type="email" 
            placeholder="nom@exemple.com" 
            class="input"
            required 
          />
        </div>

        <div class="field-group">
          <label class="field-label">Mot de passe</label>
          <input 
            v-model="password" 
            type="password" 
            placeholder="••••••••" 
            class="input"
            required 
          />
        </div>

        <button type="submit" class="btn submit-btn" :disabled="loading">
          {{ loading ? 'Connexion en cours...' : 'Se connecter' }}
        </button>

        <p v-if="erreur" class="error-msg">{{ erreur }}</p>
      </form>
    </div>
  </div>
</template>

<style scoped>
.login-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: var(--bg, #1F1917);
}

.login-card {
  width: 100%;
  max-width: 420px;
  padding: 36px 32px;
  border-radius: 18px;
  box-shadow: var(--shadow-md, 0 8px 24px rgba(0, 0, 0, 0.35));
}

.brand-header {
  text-align: center;
  margin-bottom: 28px;
}

.brand-badge {
  display: inline-block;
  font-family: var(--font-mono, monospace);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.12em;
  color: var(--cyan, #8FB9B3);
  background: rgba(143, 185, 179, 0.12);
  border: 1px solid rgba(143, 185, 179, 0.25);
  padding: 4px 12px;
  border-radius: 20px;
  margin-bottom: 12px;
}

.login-title {
  font-size: 24px;
  font-weight: 700;
  color: var(--text, #F3EBE6);
}

.login-subtitle {
  font-size: 13px;
  color: var(--text-muted, #C4B5AC);
  margin-top: 6px;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  text-align: left;
}

.field-label {
  font-size: 11.5px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted, #C4B5AC);
}

.login-form .input {
  width: 100%;
  padding: 11px 14px;
}

.submit-btn {
  width: 100%;
  padding: 12px;
  font-size: 13px;
  margin-top: 6px;
}

.error-msg {
  color: var(--critical, #E07A5F);
  font-size: 12.5px;
  font-weight: 600;
  text-align: center;
  background: rgba(224, 122, 95, 0.12);
  border: 1px solid rgba(224, 122, 95, 0.25);
  padding: 10px;
  border-radius: 8px;
  margin-top: 4px;
}
</style>