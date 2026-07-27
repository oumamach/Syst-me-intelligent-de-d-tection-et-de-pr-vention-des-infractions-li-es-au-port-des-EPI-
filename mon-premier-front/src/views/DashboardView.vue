<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const anomalies = ref([])
const derniere = ref(null)

onMounted(async () => {
  const response = await api.get('/anomalies')
  anomalies.value = response.data.slice(0, 6)
  derniere.value = anomalies.value.find(a => a.criticite === 'haute' && a.statut === 'nouvelle')
})
</script>

<template>
  <div>
    <div class="alert-banner" v-if="derniere">
      <span class="badge">{{ derniere.criticite.toUpperCase() }}</span>
      <span class="msg">Anomalie détectée — <b>{{ derniere.zone }}</b> · {{ derniere.type.replaceAll('_', ' ') }}</span>
      <span class="time">{{ derniere.date_detection }}</span>
    </div>

    <div class="op-grid">
      <div class="panel">
        <div class="panel-head">
          <span class="t">Flux vidéo — {{ derniere?.zone || 'Caméra principale' }}</span>
          <span class="live-tag"><span class="pulse-dot"></span>LIVE</span>
        </div>
        <div class="video-feed">
          <svg viewBox="0 0 400 250" preserveAspectRatio="xMidYMid slice">
            <rect width="400" height="250" fill="#0B1016"/>
            <rect y="170" width="400" height="80" fill="#12181f"/>
            <rect x="30" y="60" width="46" height="110" fill="#1c2530"/>
            <rect x="90" y="40" width="46" height="130" fill="#1c2530"/>
            <rect x="150" y="70" width="46" height="100" fill="#232d39"/>
          </svg>
          <div class="scanline"></div>
          <div class="cam-label"><span class="rec"></span>{{ derniere?.zone || 'CAM-01' }}</div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head">
          <span class="t">Heatmap — Détection IA</span>
          <span class="live-tag" style="color:var(--cyan)">IA ACTIVE</span>
        </div>
        <div class="heatmap-wrap">
          <img v-if="derniere?.heatmap" :src="derniere.heatmap.image_url" style="width:100%;height:100%;object-fit:cover" />
          <svg v-else viewBox="0 0 400 250" preserveAspectRatio="xMidYMid slice">
            <rect width="400" height="250" fill="#0B1016"/>
            <text x="130" y="130" fill="#556170" font-family="IBM Plex Mono" font-size="12">Aucune anomalie active</text>
          </svg>
        </div>
        <div class="vlm-box" v-if="derniere">
          <div class="label">RAPPORT — {{ derniere.date_detection }}</div>
          <div class="text">
            {{ derniere.rapport_textuel?.contenu || `Anomalie [${derniere.criticite}] détectée dans la zone ${derniere.zone}.` }}
          </div>
        </div>
      </div>
    </div>

    <div class="panel" style="margin-top:20px;">
      <div class="panel-head"><span class="t">Alertes récentes</span></div>
      <div class="recent-list">
        <div class="recent-item" v-for="a in anomalies" :key="a.id">
          <span class="sev" :class="a.criticite === 'haute' ? 'c' : a.criticite === 'moyenne' ? 'm' : 'l'"></span>
          <div class="content">
            <div class="zone">{{ a.zone }}</div>
            <div class="desc">{{ a.type.replaceAll('_', ' ') }}</div>
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
.video-feed svg{ width:100%; height:100%; display:block; }
.scanline{ position:absolute; left:0; right:0; height:2px; background:rgba(47,184,214,.5); box-shadow:0 0 8px rgba(47,184,214,.7); animation:scan 4s linear infinite; }
@keyframes scan{ 0%{top:0;} 100%{top:100%;} }
.cam-label{ position:absolute; top:10px; left:10px; font-family:var(--font-mono); font-size:10px; background:rgba(0,0,0,.55); padding:4px 8px; border-radius:4px; color:var(--text-muted); display:flex; align-items:center; gap:6px; }
.cam-label .rec{ width:6px; height:6px; border-radius:50%; background:var(--critical); }
.heatmap-wrap{ aspect-ratio:16/10; position:relative; background:#060a0e; overflow:hidden; }
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