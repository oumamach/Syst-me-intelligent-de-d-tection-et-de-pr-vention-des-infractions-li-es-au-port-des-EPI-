<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'

const anomalies = ref([])
const derniere = ref(null)
const selectionnee = ref(null) // anomalie cliquée manuellement dans "Alertes récentes"
let interval = null

const BACKEND_URL = 'http://127.0.0.1:8000'

async function chargerDonnees() {
  try {
    const response = await api.get('/anomalies')
    anomalies.value = response.data.slice(0, 6)

    if (anomalies.value.length > 0) {
      derniere.value = anomalies.value[0]
    }
  } catch (e) {
    console.error("Erreur chargement anomalies:", e)
  }
}

// L'anomalie affichée dans les panneaux du haut :
// celle cliquée manuellement en priorité, sinon la plus récente automatiquement
function anomalieAffichee() {
  return selectionnee.value || derniere.value
}

function selectionnerAlerte(anomalie) {
  selectionnee.value = anomalie
}

function urlComplete(chemin) {
  if (!chemin) return null
  return chemin.startsWith('http') ? chemin : `${BACKEND_URL}${chemin}`
}

function obtenirImage(anomalie) {
  if (!anomalie) return null
  let chemin = null

  if (anomalie.image_url) {
    chemin = anomalie.image_url
  } else if (Array.isArray(anomalie.heatmap) && anomalie.heatmap.length > 0) {
    chemin = anomalie.heatmap[0].chemin || anomalie.heatmap[0].image_url || null
  } else if (anomalie.heatmap && typeof anomalie.heatmap === 'object') {
    chemin = anomalie.heatmap.chemin || anomalie.heatmap.image_url || null
  }

  return urlComplete(chemin)
}

onMounted(() => {
  chargerDonnees()
  interval = setInterval(chargerDonnees, 2000)
})

onUnmounted(() => {
  if (interval) clearInterval(interval)
})
</script>

<template>
  <div>
    <!-- Bandeau d'alerte supérieure -->
    <div class="alert-banner" v-if="anomalieAffichee()">
      <span class="badge">{{ (anomalieAffichee().criticite || 'HAUTE').toUpperCase() }}</span>
      <span class="msg">
        Anomalie détectée — <b>{{ anomalieAffichee().zone }}</b> · {{ (anomalieAffichee().type || '').replaceAll('_', ' ') }}
      </span>
      <span class="time">{{ anomalieAffichee().date_detection }}</span>
      <button v-if="selectionnee" class="clear-btn" @click="selectionnee = null">✕ Voir le direct</button>
    </div>

    <div class="op-grid">
      <!-- Panel 1 : Capture de l'anomalie en direct -->
      <div class="panel">
        <div class="panel-head">
          <span class="t">Flux vidéo — {{ anomalieAffichee()?.zone || 'Caméra principale' }}</span>
          <span class="live-tag"><span class="pulse-dot"></span>{{ selectionnee ? 'HISTORIQUE' : 'CAPTURÉ' }}</span>
        </div>
        <div class="video-feed">
          <img
            v-if="obtenirImage(anomalieAffichee())"
            :src="obtenirImage(anomalieAffichee())"
            style="width:100%;height:100%;object-fit:cover"
            alt="Capture anomalie"
          />
          <div v-else class="empty-media">
            <p>📷 Image en attente de capture pour {{ anomalieAffichee()?.zone || 'la caméra' }}</p>
          </div>

          <div class="scanline"></div>
          <div class="cam-label"><span class="rec"></span>{{ anomalieAffichee()?.zone || 'CAM-01' }}</div>
        </div>
      </div>

      <!-- Panel 2 : Heatmap / Détection IA -->
      <div class="panel">
        <div class="panel-head">
          <span class="t">Heatmap — Détection IA</span>
          <span class="live-tag" style="color:var(--cyan)">IA ACTIVE</span>
        </div>
        <div class="heatmap-wrap">
          <img
            v-if="obtenirImage(anomalieAffichee())"
            :src="obtenirImage(anomalieAffichee())"
            style="width:100%;height:100%;object-fit:cover"
            alt="Heatmap IA"
          />
          <div v-else class="empty-media">
            <span>Aucune anomalie active</span>
          </div>
        </div>
        <div class="vlm-box" v-if="anomalieAffichee()">
          <div class="label">RAPPORT — {{ anomalieAffichee().date_detection }}</div>
          <div class="text">
            {{ anomalieAffichee().rapport_textuel?.contenu || `Anomalie [${anomalieAffichee().criticite}] détectée dans la zone ${anomalieAffichee().zone}.` }}
          </div>
        </div>
      </div>
    </div>

    <!-- Panel 3 : Liste des alertes récentes -->
    <div class="panel" style="margin-top:20px;">
      <div class="panel-head"><span class="t">Alertes récentes</span></div>
      <div class="recent-list">
        <div
          class="recent-item"
          :class="{ selected: selectionnee?.id === a.id }"
          v-for="a in anomalies"
          :key="a.id"
          @click="selectionnerAlerte(a)"
        >
          <span class="sev" :class="a.criticite === 'haute' ? 'c' : a.criticite === 'moyenne' ? 'm' : 'l'"></span>
          <div class="content">
            <div class="zone">{{ a.zone }}</div>
            <div class="desc">{{ (a.type || '').replaceAll('_', ' ') }}</div>
          </div>
          <span class="t">{{ a.date_detection?.slice(11,16) }}</span>
        </div>
        <p v-if="anomalies.length === 0" style="padding:16px; color:var(--text-dim)">Aucune anomalie enregistrée.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.op-grid{ display:grid; grid-template-columns:1.35fr 1fr; gap:20px; align-items:start; }
.video-feed{ aspect-ratio:16/10; background:#060a0e; position:relative; overflow:hidden; }
.scanline{ position:absolute; left:0; right:0; height:2px; background:rgba(47,184,214,.5); box-shadow:0 0 8px rgba(47,184,214,.7); animation:scan 4s linear infinite; pointer-events:none; }
@keyframes scan{ 0%{top:0;} 100%{top:100%;} }
.cam-label{ position:absolute; top:10px; left:10px; font-family:var(--font-mono); font-size:10px; background:rgba(0,0,0,.55); padding:4px 8px; border-radius:4px; color:var(--text-muted); display:flex; align-items:center; gap:6px; }
.cam-label .rec{ width:6px; height:6px; border-radius:50%; background:var(--critical); }
.heatmap-wrap{ aspect-ratio:16/10; position:relative; background:#060a0e; overflow:hidden; }

.empty-media { display:flex; align-items:center; justify-content:center; width:100%; height:100%; color:#556170; font-size:12px; font-family:var(--font-mono); text-align:center; padding:10px; }

.vlm-box{ margin:14px 16px 16px; padding:13px 14px; background:var(--panel-2); border:1px solid var(--border); border-left:3px solid var(--critical); border-radius:8px; }
.vlm-box .label{ font-family:var(--font-mono); font-size:9.5px; color:var(--critical); letter-spacing:.08em; margin-bottom:6px; font-weight:700; }
.vlm-box .text{ font-size:12.5px; line-height:1.6; font-style:italic; }

.recent-item{ display:flex; align-items:center; gap:12px; padding:11px 16px; border-bottom:1px solid var(--border); cursor:pointer; transition: background 0.15s; }
.recent-item:last-child{ border-bottom:none; }
.recent-item:hover{ background: var(--panel-2); }
.recent-item.selected{ background: var(--panel-2); border-left: 3px solid var(--cyan); }
.recent-item .sev{ width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.recent-item .sev.c{ background:var(--critical); }
.recent-item .sev.m{ background:var(--medium); }
.recent-item .sev.l{ background:var(--low); }
.recent-item .content{ flex:1; min-width:0; }
.recent-item .zone{ font-size:12px; font-weight:500; }
.recent-item .desc{ font-size:11px; color:var(--text-dim); }
.recent-item .t{ font-family:var(--font-mono); font-size:10px; color:var(--text-dim); }

.clear-btn{
  margin-left: 12px;
  background: transparent;
  border: 1px solid rgba(255,255,255,0.2);
  color: var(--text-muted);
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  cursor: pointer;
}
.clear-btn:hover{ background: rgba(255,255,255,0.08); }
</style>