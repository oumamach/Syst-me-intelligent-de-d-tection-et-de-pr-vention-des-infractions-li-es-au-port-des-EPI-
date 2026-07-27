// src/stores/auth.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const utilisateur = ref(JSON.parse(localStorage.getItem('utilisateur')) || null)
  const token = ref(localStorage.getItem('token') || null)

  async function login(email, password) {
    const response = await api.post('/login', { email, password })
    utilisateur.value = response.data.utilisateur
    token.value = response.data.token

    localStorage.setItem('utilisateur', JSON.stringify(utilisateur.value))
    localStorage.setItem('token', token.value)
  }

  async function logout() {
    await api.post('/logout')
    utilisateur.value = null
    token.value = null
    localStorage.removeItem('utilisateur')
    localStorage.removeItem('token')
  }

  function isAuthenticated() {
    return !!token.value
  }

  return { utilisateur, token, login, logout, isAuthenticated }
})