<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const anomalie = ref(null)

onMounted(async () => {
  const response = await api.get(`/anomalies/${route.params.id}`)
  anomalie.value = response.data
})
</script>

<template>
  <div class="page" v-if="anomalie">
    <router-link to="/anomalies">← Retour à la liste</router-link>
    <h1>Anomalie #{{ anomalie.id }} — {{ anomalie.type }}</h1>

    <p><strong>Criticité :</strong> {{ anomalie.criticite }}</p>
    <p><strong>Zone :</strong> {{ anomalie.zone }}</p>
    <p><strong>Date de détection :</strong> {{ anomalie.date_detection }}</p>
    <p><strong>Score de confiance :</strong> {{ anomalie.score_confiance }}</p>
    <p><strong>Statut :</strong> {{ anomalie.statut }}</p>

    <div v-if="anomalie.heatmap">
      <h3>Heatmap (XAI)</h3>
      <img :src="anomalie.heatmap.image_url" alt="Heatmap" style="max-width: 500px; border: 1px solid #ccc;" />
    </div>
    <p v-else>Aucune heatmap disponible pour cette anomalie.</p>

    <div v-if="anomalie.rapport_textuel">
      <h3>Rapport généré (VLM)</h3>
      <p>{{ anomalie.rapport_textuel.contenu }}</p>
    </div>
  </div>
</template>