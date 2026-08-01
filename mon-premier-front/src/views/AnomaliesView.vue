<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const anomalies = ref([])
const filtres = ref({ criticite: '', zone: '', date_debut: '', date_fin: '' })

async function chargerAnomalies() {
  const params = {}
  Object.keys(filtres.value).forEach((key) => {
    if (filtres.value[key]) params[key] = filtres.value[key]
  })
  try {
    const response = await api.get('/anomalies', { params })
    anomalies.value = response.data
  } catch (error) {
    console.error("Erreur lors du chargement des anomalies :", error)
  }
}

onMounted(chargerAnomalies)
</script>

<template>
  <div class="page-container">
    <!-- En-tête avec navigation -->
    <div class="top-nav">
      <router-link to="/dashboard" class="back-btn">
        ← Retour au tableau de bord
      </router-link>
    </div>

    <!-- En-tête de la page -->
    <div class="header-card">
      <h1 class="page-title">Historique des Anomalies</h1>
      <p class="page-subtitle">Consultez et filtrez l'ensemble des infractions EPI détectées par le système.</p>
    </div>

    <!-- Panneau des filtres -->
    <div class="panel filters-panel">
      <div class="panel-head">
        <span class="t">Filtres de recherche</span>
      </div>
      <div class="filtres-body">
        <div class="filter-group">
          <select v-model="filtres.criticite" class="input">
            <option value="">Toutes criticités</option>
            <option value="basse">Basse</option>
            <option value="moyenne">Moyenne</option>
            <option value="haute">Haute</option>
          </select>
        </div>

        <div class="filter-group">
          <input v-model="filtres.zone" placeholder="Zone (ex: quai_3)" class="input" />
        </div>

        <div class="filter-group date-group">
          <label class="date-label">Du :</label>
          <input v-model="filtres.date_debut" type="date" class="input" />
        </div>

        <div class="filter-group date-group">
          <label class="date-label">Au :</label>
          <input v-model="filtres.date_fin" type="date" class="input" />
        </div>

        <button @click="chargerAnomalies" class="btn">Filtrer</button>
      </div>
    </div>

    <!-- Tableau de données -->
    <div class="panel table-panel">
      <div class="panel-head">
        <span class="t">Liste des détections ({{ anomalies.length }})</span>
      </div>

      <div class="table-wrapper" v-if="anomalies.length > 0">
        <table class="history">
          <thead>
            <tr>
              <th>Type</th>
              <th>Criticité</th>
              <th>Zone</th>
              <th>Date de détection</th>
              <th>Score</th>
              <th>Statut</th>
              <th class="text-right">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="anomalie in anomalies" :key="anomalie.id">
              <td class="type-cell">{{ anomalie.type.replace('_', ' ') }}</td>
              <td>
                <span class="badge-sev" :class="anomalie.criticite">
                  {{ anomalie.criticite.toUpperCase() }}
                </span>
              </td>
              <td><span class="zone-tag">{{ anomalie.zone }}</span></td>
              <td class="font-mono">{{ anomalie.date_detection }}</td>
              <td class="score-cell font-mono">{{ (anomalie.score_confiance * 100).toFixed(0) }}%</td>
              <td><span class="status-pill">{{ anomalie.statut }}</span></td>
              <td class="text-right">
                <router-link :to="`/anomalies/${anomalie.id}`" class="detail-link">
                  Détails →
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- État vide -->
      <div v-else class="empty-state">
        <p>Aucune anomalie ne correspond à vos critères de recherche.</p>
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

.back-btn {
  display: inline-flex;
  align-items: center;
  color: var(--rose-accent, #D4A373);
  text-decoration: none;
  font-weight: 600;
  font-size: 13px;
  transition: opacity 0.2s;
}

.back-btn:hover {
  opacity: 0.8;
}

/* Carte En-tête */
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

/* Panneau Filtres */
.filters-panel {
  margin-bottom: 24px;
}

.filtres-body {
  display: flex;
  gap: 12px;
  padding: 16px 20px;
  flex-wrap: wrap;
  align-items: center;
}

.filter-group {
  display: flex;
  align-items: center;
}

.date-group {
  gap: 8px;
}

.date-label {
  font-size: 12px;
  color: var(--text-muted, #C4B5AC);
  font-weight: 600;
}

/* Tableau */
.table-panel {
  overflow: hidden;
}

.table-wrapper {
  overflow-x: auto;
}

.type-cell {
  font-weight: 600;
  text-transform: capitalize;
}

.score-cell {
  color: var(--rose-accent, #D4A373);
  font-weight: 600;
}

.status-pill {
  text-transform: capitalize;
  background: var(--panel-2, #382E2A);
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  color: var(--text-muted, #C4B5AC);
}

.detail-link {
  color: var(--cyan, #8FB9B3);
  text-decoration: none;
  font-weight: 600;
  font-size: 12.5px;
  transition: opacity 0.2s;
}

.detail-link:hover {
  opacity: 0.8;
}

.text-right {
  text-align: right;
}

.font-mono {
  font-family: var(--font-mono, monospace);
}

/* État vide */
.empty-state {
  padding: 40px 20px;
  text-align: center;
  color: var(--text-muted, #C4B5AC);
  font-size: 13.5px;
}
</style>