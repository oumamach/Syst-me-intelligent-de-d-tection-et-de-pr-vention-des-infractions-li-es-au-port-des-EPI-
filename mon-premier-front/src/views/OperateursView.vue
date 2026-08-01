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
    <!-- Navigation -->
    <div class="top-nav">
      <router-link to="/dashboard" class="back-link">
        ← Retour au tableau de bord
      </router-link>
    </div>

    <!-- En-tête de page -->
    <div class="header-card">
      <h1 class="page-title">Gestion des opérateurs</h1>
      <p class="page-subtitle">Gérez les comptes d'accès à la plateforme de surveillance.</p>
    </div>

    <!-- Formulaire d'ajout d'opérateur -->
    <div class="panel form-panel">
      <div class="panel-head">
        <span class="t">Ajouter un nouvel opérateur</span>
      </div>
      <div class="form-body">
        <form @submit.prevent="ajouter" class="form-ajout">
          <input 
            v-model="nouveau.nom" 
            placeholder="Nom complet" 
            class="input"
            required 
          />
          <input 
            v-model="nouveau.email" 
            type="email" 
            placeholder="Adresse email" 
            class="input"
            required 
          />
          <input 
            v-model="nouveau.password" 
            type="password" 
            placeholder="Mot de passe" 
            class="input"
            required 
          />
          <button type="submit" class="btn" :disabled="loading">
            {{ loading ? 'Ajout en cours...' : 'Ajouter' }}
          </button>
        </form>

        <p v-if="erreur" class="error-text">{{ erreur }}</p>
      </div>
    </div>

    <!-- Tableau des opérateurs -->
    <div class="panel table-panel">
      <div class="panel-head">
        <span class="t">Liste des comptes actifs ({{ operateurs.length }})</span>
      </div>

      <div class="table-wrapper" v-if="operateurs.length > 0">
        <table class="history">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Email</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="op in operateurs" :key="op.id">
              <td class="name-cell">{{ op.nom }}</td>
              <td class="font-mono text-muted">{{ op.email }}</td>
              <td class="text-right">
                <button @click="supprimer(op.id)" class="btn-delete">
                  Supprimer
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="empty-state">
        <p>Aucun opérateur trouvé.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
}

/* Bouton Retour */
.top-nav {
  margin-bottom: 16px;
}

.back-link {
  display: inline-flex;
  align-items: center;
  color: var(--rose-accent, #D4A373);
  text-decoration: none;
  font-weight: 600;
  font-size: 13px;
  transition: opacity 0.2s;
}

.back-link:hover {
  opacity: 0.8;
}

/* En-tête */
.header-card {
  background: var(--panel, #2C2421);
  border: 1px solid var(--border, #4A3E38);
  border-radius: 14px;
  padding: 20px 24px;
  margin-bottom: 24px;
  box-shadow: var(--shadow-sm, 0 4px 12px rgba(0,0,0,0.25));
}

.page-title {
  font-size: 22px;
  font-weight: 700;
  color: var(--text, #F3EBE6);
}

.page-subtitle {
  font-size: 13px;
  color: var(--text-muted, #C4B5AC);
  margin-top: 4px;
}

/* Formulaire */
.form-panel {
  margin-bottom: 24px;
}

.form-body {
  padding: 20px;
}

.form-ajout {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.form-ajout .input {
  flex: 1;
  min-width: 200px;
}

.error-text {
  color: var(--critical, #E07A5F);
  font-size: 13px;
  margin-top: 14px;
  font-weight: 600;
}

/* Tableau */
.table-panel {
  overflow: hidden;
}

.table-wrapper {
  overflow-x: auto;
}

.name-cell {
  font-weight: 600;
  color: var(--text, #F3EBE6);
}

.text-muted {
  color: var(--text-muted, #C4B5AC);
}

.text-right {
  text-align: right;
}

.font-mono {
  font-family: var(--font-mono, monospace);
}

/* Bouton de suppression corail */
.btn-delete {
  background: rgba(224, 122, 95, 0.15);
  color: var(--critical, #E07A5F);
  border: 1px solid rgba(224, 122, 95, 0.3);
  padding: 6px 14px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
  transition: all 0.2s ease;
}

.btn-delete:hover {
  background: var(--critical, #E07A5F);
  color: #FFFFFF;
}

/* État vide */
.empty-state {
  padding: 40px 20px;
  text-align: center;
  color: var(--text-muted, #C4B5AC);
  font-size: 13.5px;
}
</style>