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
  const response = await api.get('/anomalies', { params })
  anomalies.value = response.data
}

onMounted(chargerAnomalies)
</script>

<template>
  <div class="page">
    <router-link to="/dashboard">← Retour au tableau de bord</router-link>
    <h1>Liste des anomalies</h1>

    <div class="filtres">
      <select v-model="filtres.criticite">
        <option value="">Toutes criticités</option>
        <option value="basse">Basse</option>
        <option value="moyenne">Moyenne</option>
        <option value="haute">Haute</option>
      </select>

      <input v-model="filtres.zone" placeholder="Zone (ex: quai_3)" />

      <label>Du : <input v-model="filtres.date_debut" type="date" /></label>
      <label>Au : <input v-model="filtres.date_fin" type="date" /></label>

      <button @click="chargerAnomalies">Filtrer</button>
    </div>

    <table>
      <thead>
        <tr>
          <th>Type</th><th>Criticité</th><th>Zone</th><th>Date</th><th>Score</th><th>Statut</th><th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="anomalie in anomalies" :key="anomalie.id">
          <td>{{ anomalie.type }}</td>
          <td>
            <span class="badge" :class="anomalie.criticite">{{ anomalie.criticite }}</span>
          </td>
          <td>{{ anomalie.zone }}</td>
          <td>{{ anomalie.date_detection }}</td>
          <td>{{ anomalie.score_confiance }}</td>
          <td>{{ anomalie.statut }}</td>
          <td>
            <router-link :to="`/anomalies/${anomalie.id}`">Détails</router-link>
          </td>
        </tr>
      </tbody>
    </table>
    <p v-if="anomalies.length === 0">Aucune anomalie trouvée.</p>
  </div>
</template>

<style scoped>
.filtres { display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap; align-items: center; }
.filtres input, .filtres select { padding: 8px; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
.badge { padding: 4px 10px; border-radius: 12px; font-size: 13px; color: white; }
.badge.haute { background: #dc2626; }
.badge.moyenne { background: #f59e0b; }
.badge.basse { background: #16a34a; }
</style>