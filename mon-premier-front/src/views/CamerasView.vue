<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import api from '@/services/api'

const cameras = ref([])
const cameraZoomee = ref(null)
let mediaStream = null
let intervalDetection = null

// Résultats reçus de l'IA Hugging Face via Laravel
const resultatIa = ref({
  rapport: '',
  criticite: ''
})
const analyseEnCours = ref(false)

async function chargerCameras() {
  try {
    const response = await api.get('/cameras')
    cameras.value = response.data
  } catch (e) {
    console.error('Erreur lors du chargement des caméras:', e)
  }
}

async function demarrerWebcam() {
  try {
    mediaStream = await navigator.mediaDevices.getUserMedia({ video: true })
    await nextTick()
    document.querySelectorAll('video.camera-feed').forEach((v) => {
      v.srcObject = mediaStream
    })

    // Lancer la détection automatique toutes les 3 secondes
    intervalDetection = setInterval(capturerEtDetecter, 3000)
  } catch (e) {
    console.error("Impossible d'accéder à la webcam:", e)
  }
}

// Extraction automatique d'une frame compressée et envoi au Dashboard
async function capturerEtDetecter() {
  if (!mediaStream || analyseEnCours.value) return

  const video = document.querySelector('video.camera-feed')
  if (!video || video.readyState !== 4) return

  // 1. Dimensions optimisées
  const canvas = document.createElement('canvas')
  canvas.width = 640
  canvas.height = 360

  const ctx = canvas.getContext('2d')
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height)

  // 2. Compression JPEG à 50% de qualité
  const imageBase64Legere = canvas.toDataURL('image/jpeg', 0.5)

  analyseEnCours.value = true
  try {
    const activeCamId = cameraZoomee.value ? cameraZoomee.value.id : (cameras.value[0]?.id || 1)
    
    // Envoi de la capture à Laravel
    const response = await api.post('/detecter-anomalie', {
      image: imageBase64Legere,
      camera_id: activeCamId
    })

    if (response.data) {
      resultatIa.value = {
        rapport: response.data.rapport_textuel || response.data.description,
        criticite: response.data.criticite || 'HAUTE'
      }
    }
  } catch (e) {
    console.error("Erreur de détection automatique:", e)
  } finally {
    analyseEnCours.value = false
  }
}

function ouvrirZoom(camera) {
  cameraZoomee.value = camera
  nextTick(() => {
    const v = document.querySelector('video.camera-feed-zoom')
    if (v && mediaStream) v.srcObject = mediaStream
  })
}

function fermerZoom() {
  cameraZoomee.value = null
}

onMounted(async () => {
  await chargerCameras()
  await demarrerWebcam()
})

onUnmounted(() => {
  if (intervalDetection) clearInterval(intervalDetection)
  if (mediaStream) {
    mediaStream.getTracks().forEach((track) => track.stop())
  }
})
</script>

<template>
  <div class="page-container">
    <!-- Barre de navigation et statut -->
    <div class="top-nav-bar">
      <router-link to="/dashboard" class="back-link">
        ← Retour au tableau de bord
      </router-link>
      <span class="live-indicator" :class="{ 'active': analyseEnCours }">
        <span class="pulse-dot-indicator"></span>
        {{ analyseEnCours ? 'IA : Analyse en cours...' : 'Surveillance IA Active' }}
      </span>
    </div>

    <!-- En-tête -->
    <div class="header-card">
      <h1 class="page-title">Caméras — Surveillance en direct</h1>
      <p class="page-subtitle">Flux vidéo temps réel connectés aux modèles de détection d'EPI.</p>
    </div>

    <!-- Banner du dernier Rapport VLM généré -->
    <div v-if="resultatIa.rapport" class="vlm-banner" :class="resultatIa.criticite.toLowerCase()">
      <div class="vlm-badge">{{ resultatIa.criticite }}</div>
      <div class="vlm-text"><b>Rapport VLM :</b> {{ resultatIa.rapport }}</div>
    </div>

    <!-- Grille des caméras -->
    <div class="grille-cameras">
      <div
        v-for="camera in cameras"
        :key="camera.id"
        class="tuile-camera"
        @click="ouvrirZoom(camera)"
      >
        <!-- Flux vidéo en direct -->
        <video class="camera-feed" autoplay muted playsinline></video>

        <div class="overlay-info">
          <span class="nom">{{ camera.nom }}</span>
          <span class="zone">{{ camera.emplacement || camera.zone }}</span>
        </div>
        <div class="statut-dot" :class="camera.statut ? camera.statut.toLowerCase() : 'actif'"></div>
      </div>
      <p v-if="cameras.length === 0" class="empty-msg">Aucune caméra enregistrée.</p>
    </div>

    <!-- Vue zoomée en modale -->
    <div v-if="cameraZoomee" class="modal-zoom" @click.self="fermerZoom">
      <div class="contenu-zoom panel">
        <div class="header-zoom panel-head">
          <span class="t">{{ cameraZoomee.nom }} — {{ cameraZoomee.emplacement || cameraZoomee.zone }}</span>
          <button @click="fermerZoom" class="close-btn">✕ Fermer</button>
        </div>
        
        <div class="modal-body">
          <div class="zoom-video-container">
            <video class="camera-feed-zoom" autoplay muted playsinline></video>
          </div>

          <div v-if="resultatIa.rapport" class="vlm-box-modal">
            <strong>Résultat IA Hugging Face :</strong> {{ resultatIa.rapport }}
          </div>
        </div>
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

/* En-tête / Navigation */
.top-nav-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.back-link {
  color: var(--rose-accent, #D4A373);
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  transition: opacity 0.2s;
}

.back-link:hover {
  opacity: 0.8;
}

.live-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: var(--font-mono, monospace);
  font-size: 11px;
  color: var(--low, #81B29A);
  font-weight: 600;
}

.live-indicator.active {
  color: var(--medium, #F2CC8F);
}

.pulse-dot-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: currentColor;
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

/* VLM Banner */
.vlm-banner {
  display: flex;
  align-items: center;
  gap: 14px;
  background: rgba(224, 122, 95, 0.15);
  border: 1px solid rgba(224, 122, 95, 0.3);
  border-left: 5px solid var(--critical, #E07A5F);
  border-radius: 12px;
  padding: 14px 20px;
  margin-bottom: 24px;
}

.vlm-badge {
  background: var(--critical, #E07A5F);
  color: #ffffff;
  font-family: var(--font-mono, monospace);
  font-weight: 700;
  font-size: 10.5px;
  padding: 4px 10px;
  border-radius: 6px;
  letter-spacing: 0.05em;
}

.vlm-text {
  font-size: 13.5px;
  color: var(--text, #F3EBE6);
}

/* Grille Caméras */
.grille-cameras {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.tuile-camera {
  position: relative;
  aspect-ratio: 16 / 9;
  background: #000;
  border-radius: 14px;
  overflow: hidden;
  cursor: pointer;
  border: 1px solid var(--border, #4A3E38);
  box-shadow: var(--shadow-sm, 0 4px 12px rgba(0,0,0,0.25));
  transition: all 0.25s ease;
}

.tuile-camera:hover {
  border-color: var(--rose-accent, #D4A373);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md, 0 8px 24px rgba(0,0,0,0.35));
}

.camera-feed {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.overlay-info {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 10px 14px;
  background: linear-gradient(transparent, rgba(31, 25, 23, 0.95));
  display: flex;
  justify-content: space-between;
  color: var(--text, #F3EBE6);
  font-size: 12px;
}

.overlay-info .nom { 
  font-weight: 700; 
}

.overlay-info .zone {
  font-family: var(--font-mono, monospace);
  color: var(--cyan, #8FB9B3);
}

.statut-dot {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 9px;
  height: 9px;
  border-radius: 50%;
  box-shadow: 0 0 6px rgba(0,0,0,0.5);
}

.statut-dot.actif { background: var(--low, #81B29A); }
.statut-dot.inactif { background: var(--text-dim, #8F7E75); }
.statut-dot.maintenance { background: var(--medium, #F2CC8F); }

.empty-msg {
  color: var(--text-muted, #C4B5AC);
  grid-column: 1 / -1;
  text-align: center;
  padding: 40px;
}

/* Modale Zoom */
.modal-zoom {
  position: fixed;
  inset: 0;
  background: rgba(15, 12, 11, 0.85);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 24px;
}

.contenu-zoom {
  width: 100%;
  max-width: 960px;
  border-radius: 16px;
}

.modal-body {
  padding: 20px;
}

.close-btn {
  background: rgba(224, 122, 95, 0.15);
  color: var(--critical, #E07A5F);
  border: 1px solid rgba(224, 122, 95, 0.3);
  padding: 6px 14px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 700;
  transition: all 0.2s;
}

.close-btn:hover {
  background: var(--critical, #E07A5F);
  color: #ffffff;
}

.zoom-video-container {
  background: #000;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid var(--border, #4A3E38);
}

.camera-feed-zoom {
  width: 100%;
  display: block;
  max-height: 520px;
  object-fit: contain;
}

.vlm-box-modal {
  margin-top: 16px;
  background: var(--panel-2, #382E2A);
  border-left: 4px solid var(--cyan, #8FB9B3);
  padding: 14px 18px;
  border-radius: 8px;
  font-size: 13px;
  color: var(--text, #F3EBE6);
}
</style>