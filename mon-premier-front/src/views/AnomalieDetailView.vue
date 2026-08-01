<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const anomalie = ref(null)

onMounted(async () => {
  try {
    const response = await api.get(`/anomalies/${route.params.id}`)
    anomalie.value = response.data
  } catch (error) {
    console.error("Erreur de chargement de l'anomalie :", error)
  }
})
</script>

<template>
  <div class="detail-container" v-if="anomalie">
    <!-- En-tête avec navigation -->
    <div class="top-nav">
      <router-link to="/anomalies" class="back-btn">
        ← Retour à la liste
      </router-link>
    </div>

    <!-- Carte Titre Principale -->
    <div class="header-card">
      <div class="title-row">
        <div>
          <span class="anomalie-id">ANOMALIE #{{ anomalie.id }}</span>
          <h1 class="type-title">{{ anomalie.type.replace('_', ' ') }}</h1>
        </div>
        <span :class="['badge-sev', anomalie.criticite]">
          {{ anomalie.criticite.toUpperCase() }}
        </span>
      </div>
    </div>

    <div class="content-grid">
      <!-- Colonne Gauche : Métadonnées et Informations -->
      <div class="panel meta-panel">
        <div class="panel-head">
          <span class="t">Informations générales</span>
        </div>
        <div class="meta-body">
          <div class="info-row">
            <span class="label">Zone</span>
            <span class="val zone-tag">{{ anomalie.zone }}</span>
          </div>

          <div class="info-row">
            <span class="label">Date de détection</span>
            <span class="val font-mono">{{ anomalie.date_detection }}</span>
          </div>

          <div class="info-row">
            <span class="label">Score de confiance</span>
            <span class="val score-val">{{ (anomalie.score_confiance * 100).toFixed(0) }}%</span>
          </div>

          <div class="info-row">
            <span class="label">Statut</span>
            <span class="val status-pill">{{ anomalie.statut }}</span>
          </div>
        </div>
      </div>

      <!-- Colonne Droite : Visualisation Heatmap & Rapport -->
      <div class="panel media-panel">
        <div class="panel-head">
          <span class="t">Visualisation IA & Rapport (XAI/VLM)</span>
        </div>
        
        <div class="media-body">
          <!-- Image Heatmap / Capture -->
          <div v-if="anomalie.heatmap || anomalie.image_url" class="image-wrapper">
            <img 
              :src="anomalie.heatmap?.image_url || anomalie.heatmap?.chemin || anomalie.image_url" 
              alt="Analyse IA" 
              class="heatmap-img"
            />
          </div>
          <div v-else class="empty-state">
            <p>Aucune heatmap disponible pour cette anomalie.</p>
          </div>

          <!-- Rapport Textuel VLM -->
          <div v-if="anomalie.rapport_textuel || anomalie.rapportTextuel" class="report-box">
            <h4>Rapport généré</h4>
            <p>{{ anomalie.rapport_textuel?.contenu || anomalie.rapportTextuel?.contenu }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.detail-container {
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

.title-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.anomalie-id {
  font-family: var(--font-mono, monospace);
  font-size: 11px;
  letter-spacing: 0.08em;
  color: var(--text-muted, #C4B5AC);
  text-transform: uppercase;
}

.type-title {
  font-size: 22px;
  font-weight: 700;
  color: var(--text, #F3EBE6);
  margin-top: 4px;
  text-transform: capitalize;
}

/* Grille de contenu */
.content-grid {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 24px;
  align-items: start;
}

.meta-body {
  padding: 16px 20px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid var(--border, #4A3E38);
}

.info-row:last-child {
  border-bottom: none;
}

.info-row .label {
  font-size: 12px;
  color: var(--text-muted, #C4B5AC);
}

.info-row .val {
  font-size: 13px;
  font-weight: 600;
  color: var(--text, #F3EBE6);
}

.score-val {
  font-family: var(--font-mono, monospace);
  color: var(--rose-accent, #D4A373);
}

.status-pill {
  text-transform: capitalize;
  background: var(--panel-2, #382E2A);
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11.5px;
}

/* Panneau Médias & Visualisation */
.media-body {
  padding: 20px;
}

.image-wrapper {
  background: #000;
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid var(--border, #4A3E38);
  margin-bottom: 20px;
  display: flex;
  justify-content: center;
}

.heatmap-img {
  width: 100%;
  max-height: 480px;
  object-fit: contain;
}

.empty-state {
  background: var(--panel-2, #382E2A);
  padding: 30px;
  border-radius: 10px;
  text-align: center;
  color: var(--text-muted, #C4B5AC);
  font-size: 13px;
  margin-bottom: 20px;
}

.report-box {
  background: var(--panel-2, #382E2A);
  border: 1px solid var(--border, #4A3E38);
  border-left: 4px solid var(--cyan, #8FB9B3);
  padding: 16px 20px;
  border-radius: 8px;
}

.report-box h4 {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--cyan, #8FB9B3);
  margin-bottom: 6px;
}

.report-box p {
  font-size: 13.5px;
  line-height: 1.5;
  color: var(--text, #F3EBE6);
}

.font-mono {
  font-family: var(--font-mono, monospace);
}
</style>