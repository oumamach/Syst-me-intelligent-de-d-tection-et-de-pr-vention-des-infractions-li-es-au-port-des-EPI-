<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const operateurs = ref([])
const nouveau = ref({ nom: '', email: '', password: '' })
const erreur = ref('')

async function charger() {
  const response = await api.get('/operateurs')
  operateurs.value = response.data
}

async function ajouter() {
  erreur.value = ''
  try {
    await api.post('/operateurs', nouveau.value)
    nouveau.value = { nom: '', email: '', password: '' }
    charger()
  } catch (e) {
    erreur.value = "Erreur lors de la création (email déjà utilisé ?)"
  }
}

async function supprimer(id) {
  if (confirm('Supprimer cet opérateur ?')) {
    await api.delete(`/operateurs/${id}`)
    charger()
  }
}

onMounted(charger)
</script>

<template>
  <div class="page">
    <router-link to="/dashboard">← Retour au tableau de bord</router-link>
    <h1>Gestion des opérateurs</h1>

    <form @submit.prevent="ajouter" class="form-ajout">
      <input v-model="nouveau.nom" placeholder="Nom" required />
      <input v-model="nouveau.email" type="email" placeholder="Email" required />
      <input v-model="nouveau.password" type="password" placeholder="Mot de passe" required />
      <button type="submit">Ajouter</button>
    </form>
    <p v-if="erreur" style="color:red">{{ erreur }}</p>

    <table>
      <thead><tr><th>Nom</th><th>Email</th><th></th></tr></thead>
      <tbody>
        <tr v-for="op in operateurs" :key="op.id">
          <td>{{ op.nom }}</td>
          <td>{{ op.email }}</td>
          <td><button @click="supprimer(op.id)">Supprimer</button></td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.form-ajout { display: flex; gap: 10px; margin: 20px 0; }
.form-ajout input { padding: 8px; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
</style>