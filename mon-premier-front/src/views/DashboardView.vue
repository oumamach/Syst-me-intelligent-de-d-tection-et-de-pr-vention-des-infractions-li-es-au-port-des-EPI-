<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'

const anomalies = ref([])
const derniere = ref(null)
let interval = null
async function chargerDonnees() {
  try {
    const response = await api.get('/anomalies')
    anomalies.value = response.data.slice(0, 6)
    
    if (anomalies.value.length > 0) {
      derniere.value = anomalies.value[0]
      // LIGNE DE DÉBOGAGE À AJOUTER :
      console.log("🔍 Données reçues pour la dernière anomalie :", derniere.value)
    }
  } catch (e) {
    console.error("Erreur chargement anomalies:", e)
  }
}

// Fonction d'extraction d'image universelle (Objets, Tableaux, Champs multiples)
function obtenirImage(anomalie) {
  if (!anomalie) return null

  // 1. Si l'image est directement sur l'objet anomalie
  if (anomalie.image_url) return anomalie.image_url

  // 2. Si heatmap est un tableau d'éléments (Relation HasMany)
  if (Array.isArray(anomalie.heatmap) && anomalie.heatmap.length > 0) {
    return anomalie.heatmap[0].chemin || anomalie.heatmap[0].image_url || null
  }

  // 3. Si heatmap est un objet individuel (Relation HasOne / BelongsTo)
  if (anomalie.heatmap && typeof anomalie.heatmap === 'object') {
    return anomalie.heatmap.chemin || anomalie.heatmap.image_url || null
  }

  return null
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
    <div class="alert-banner" v-if="derniere">
      <span class="badge">{{ (derniere.criticite || 'HAUTE').toUpperCase() }}</span>
      <span class="msg">
        Anomalie détectée — <b>{{ derniere.zone }}</b> · {{ (derniere.type || '').replaceAll('_', ' ') }}
      </span>
      <span class="time">{{ derniere.date_detection }}</span>
    </div>

    <div class="op-grid">
      <!-- Panel 1 : Capture de l'anomalie en direct -->
      <div class="panel">
        <div class="panel-head">
          <span class="t">Flux vidéo — {{ derniere?.zone || 'Caméra principale' }}</span>
          <span class="live-tag"><span class="pulse-dot"></span>CAPTURÉ</span>
        </div>
        <div class="video-feed">
          <img 
            v-if="obtenirImage(derniere)" 
            :src="obtenirImage(derniere)" 
            style="width:100%;height:100%;object-fit:cover" 
            alt="Capture anomalie"
          />
          <div v-else class="empty-media">
            <p>📷 Image en attente de capture pour {{ derniere?.zone || 'la caméra' }}</p>
          </div>

          <div class="scanline"></div>
          <div class="cam-label"><span class="rec"></span>{{ derniere?.zone || 'CAM-01' }}</div>
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
            v-if="obtenirImage(derniere)" 
            :src="obtenirImage(derniere)" 
            style="width:100%;height:100%;object-fit:cover" 
            alt="Heatmap IA"
          />
          <div v-else class="empty-media">
            <span>Aucune anomalie active</span>
          </div>
        </div>
        <div class="vlm-box" v-if="derniere">
          <div class="label">RAPPORT — {{ derniere.date_detection }}</div>
          <div class="text">
            {{ derniere.rapport_textuel?.contenu || `Anomalie [${derniere.criticite}] détectée dans la zone ${derniere.zone}.` }}
          </div>
        </div>
      </div>
    </div>

    <!-- Panel 3 : Liste des alertes récentes -->
    <div class="panel" style="margin-top:20px;">
      <div class="panel-head"><span class="t">Alertes récentes</span></div>
      <div class="recent-list">
        <div class="recent-item" v-for="a in anomalies" :key="a.id">
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
.recent-item{ display:flex; align-items:center; gap:12px; padding:11px 16px; border-bottom:1px solid var(--border); }
.recent-item:last-child{ border-bottom:none; }
.recent-item .sev{ width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.recent-item .sev.c{ background:var(--critical); }
.recent-item .sev.m{ background:var(--medium); }
.recent-item .sev.l{ background:var(--low); }
.recent-item .content{ flex:1; min-width:0; }
.recent-item .zone{ font-size:12px; font-weight:500; }
.recent-item .desc{ font-size:11px; color:var(--text-dim); }
.recent-item .t{ font-family:var(--font-mono); font-size:10px; color:var(--text-dim); }
</style>