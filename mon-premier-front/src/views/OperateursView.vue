<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const operateurs = ref([])
const nouveau = ref({ nom: '', email: '', password: '' })
const erreur = ref('')
const loading = ref(false)

async function charger() {
  try {
    const response = await api.get('/operateurs')
    operateurs.value = response.data
  } catch (e) {
    console.error("Erreur de chargement:", e)
    erreur.value = "Impossible de charger la liste des opérateurs."
  }
}

async function ajouter() {
  erreur.value = ''
  loading.value = true
  try {
    const response = await api.post('/operateurs', nouveau.value)
    
    if (response.data) {
      operateurs.value.push(response.data)
    } else {
      await charger()
    }
    
    nouveau.value = { nom: '', email: '', password: '' }
  } catch (e) {
    console.error("Erreur d'ajout:", e)
    if (e.response && e.response.data && e.response.data.message) {
      erreur.value = e.response.data.message
    } else if (e.response && e.response.status === 422) {
      erreur.value = "Email déjà utilisé ou mot de passe trop court."
    } else {
      erreur.value = "Erreur de connexion au serveur."
    }
  } finally {
    loading.value = false
  }
}

async function supprimer(id) {
  if (confirm('Supprimer cet opérateur ?')) {
    try {
      await api.delete(`/operateurs/${id}`)
      operateurs.value = operateurs.value.filter(op => op.id !== id)
    } catch (e) {
      console.error("Erreur de suppression:", e)
    }
  }
}

onMounted(charger)
</script>

<template>
  <div class="page-container">
    <router-link to="/dashboard" class="back-link">← Retour au tableau de bord</router-link>
    <h1 class="page-title">Gestion des opérateurs</h1>

    <!-- Formulaire d'ajout -->
    <form @submit.prevent="ajouter" class="form-ajout">
      <input v-model="nouveau.nom" placeholder="Nom" required />
      <input v-model="nouveau.email" type="email" placeholder="Email" required />
      <input v-model="nouveau.password" type="password" placeholder="Mot de passe" required />
      <button type="submit" :disabled="loading">
        {{ loading ? 'Ajout...' : 'Ajouter' }}
      </button>
    </form>

    <p v-if="erreur" class="error-text">{{ erreur }}</p>

    <!-- Tableau -->
    <table class="data-table">
      <thead>
        <tr>
          <th>Nom</th>
          <th>Email</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="op in operateurs" :key="op.id">
          <td>{{ op.nom }}</td>
          <td>{{ op.email }}</td>
          <td>
            <button @click="supprimer(op.id)" class="btn-delete">Supprimer</button>
          </td>
        </tr>
        <tr v-if="operateurs.length === 0">
          <td colspan="3" class="empty-text">Aucun opérateur trouvé.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.page-container {
  padding: 24px;
  color: #E7EBEF;
}
.back-link {
  color: #2FB8D6;
  text-decoration: none;
  font-size: 13px;
  display: inline-block;
  margin-bottom: 12px;
}
.page-title {
  font-size: 22px;
  font-weight: 600;
  margin-bottom: 20px;
}
.form-ajout {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}
.form-ajout input {
  background: #121821;
  border: 1px solid #242E3A;
  color: #FFF;
  padding: 10px 14px;
  border-radius: 6px;
  font-size: 13px;
}
.form-ajout button {
  background: #2FB8D6;
  color: #08131a;
  border: none;
  padding: 10px 18px;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}
.error-text {
  color: #E8483C;
  font-size: 13px;
  margin-bottom: 16px;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}
.data-table th, .data-table td {
  text-align: left;
  padding: 12px 14px;
  border-bottom: 1px solid #242E3A;
  font-size: 13px;
}
.data-table th {
  color: #8A96A3;
  font-size: 11px;
  text-transform: uppercase;
}
.empty-text {
  text-align: center;
  color: #8A96A3;
  padding: 20px;
}
.btn-delete {
  background: rgba(232, 72, 60, 0.15);
  color: #E8483C;
  border: 1px solid rgba(232, 72, 60, 0.3);
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}
</style>