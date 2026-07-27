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

  // 1. Dimensions optimisées pour éviter l'erreur 500 dans Laravel
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
    
    // Envoi de la capture légère à Laravel
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
  <div class="page">
    <div class="header-bar">
      <router-link to="/dashboard" class="back-link">← Retour au tableau de bord</router-link>
      <span class="live-indicator" :class="{ 'active': analyseEnCours }">
        ● {{ analyseEnCours ? 'IA : Analyse en cours...' : 'Surveillance IA Active' }}
      </span>
    </div>

    <h1>Caméras — Surveillance en direct</h1>

    <!-- Banner du dernier Rapport VLM généré -->
    <div v-if="resultatIa.rapport" class="vlm-banner" :class="resultatIa.criticite.toLowerCase()">
      <div class="vlm-badge">{{ resultatIa.criticite }}</div>
      <div class="vlm-text"><b>Rapport VLM :</b> {{ resultatIa.rapport }}</div>
    </div>

    <div class="grille-cameras">
      <div
        v-for="camera in cameras"
        :key="camera.id"
        class="tuile-camera"
        @click="ouvrirZoom(camera)"
      >
        <!-- Flux vidéo net en direct -->
        <video class="camera-feed" autoplay muted playsinline></video>

        <div class="overlay-info">
          <span class="nom">{{ camera.nom }}</span>
          <span class="zone">{{ camera.emplacement || camera.zone }}</span>
        </div>
        <div class="statut-dot" :class="camera.statut ? camera.statut.toLowerCase() : 'actif'"></div>
      </div>
      <p v-if="cameras.length === 0" class="empty-msg">Aucune caméra enregistrée.</p>
    </div>

    <!-- Vue zoomée en plein écran -->
    <div v-if="cameraZoomee" class="modal-zoom" @click.self="fermerZoom">
      <div class="contenu-zoom">
        <div class="header-zoom">
          <span>{{ cameraZoomee.nom }} — {{ cameraZoomee.emplacement || cameraZoomee.zone }}</span>
          <button @click="fermerZoom">✕ Fermer</button>
        </div>
        
        <div class="zoom-video-container">
          <video class="camera-feed-zoom" autoplay muted playsinline></video>
        </div>

        <div v-if="resultatIa.rapport" class="vlm-box-modal">
          <strong>Résultat IA Hugging Face :</strong> {{ resultatIa.rapport }}
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page {
  padding: 24px;
  color: #E7EBEF;
  background: #0A0E13;
  min-height: 100vh;
}
.header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.back-link {
  color: #2FB8D6;
  text-decoration: none;
  font-size: 13px;
}
.live-indicator {
  font-size: 11px;
  color: #45B87E;
  font-weight: 600;
  letter-spacing: 0.05em;
}
.live-indicator.active {
  color: #F0A23D;
}

/* VLM Banner */
.vlm-banner {
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgba(232, 72, 60, 0.12);
  border: 1px solid rgba(232, 72, 60, 0.4);
  border-left: 4px solid #E8483C;
  border-radius: 8px;
  padding: 12px 16px;
  margin: 16px 0;
}
.vlm-badge {
  background: #E8483C;
  color: #fff;
  font-weight: bold;
  font-size: 10px;
  padding: 4px 8px;
  border-radius: 4px;
}
.vlm-text {
  font-size: 13px;
  color: #E7EBEF;
}

.grille-cameras {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
  margin-top: 20px;
}
.tuile-camera {
  position: relative;
  aspect-ratio: 16 / 9;
  background: #000;
  border-radius: 10px;
  overflow: hidden;
  cursor: pointer;
  border: 2px solid #242E3A;
  transition: border-color 0.2s;
}
.tuile-camera:hover {
  border-color: #2FB8D6;
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
  padding: 8px 10px;
  background: linear-gradient(transparent, rgba(0, 0, 0, 0.85));
  display: flex;
  justify-content: space-between;
  color: white;
  font-size: 12px;
}
.overlay-info .nom { font-weight: 600; }
.statut-dot {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 9px;
  height: 9px;
  border-radius: 50%;
}
.statut-dot.actif { background: #45B87E; }
.statut-dot.inactif { background: #6b7280; }
.statut-dot.maintenance { background: #F0A23D; }

.empty-msg {
  color: #8A96A3;
  grid-column: 1 / -1;
}

/* Modal Zoom */
.modal-zoom {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.85);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.contenu-zoom {
  width: 85%;
  max-width: 1000px;
}
.header-zoom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
  margin-bottom: 10px;
  font-weight: 600;
}
.header-zoom button {
  background: #E8483C;
  color: white;
  border: none;
  padding: 8px 14px;
  border-radius: 6px;
  cursor: pointer;
}
.zoom-video-container {
  position: relative;
  width: 100%;
  background: #000;
  border-radius: 10px;
  overflow: hidden;
}
.camera-feed-zoom {
  width: 100%;
  display: block;
}
.vlm-box-modal {
  margin-top: 12px;
  background: #171F29;
  border-left: 3px solid #2FB8D6;
  padding: 12px;
  border-radius: 6px;
  font-size: 13px;
}
</style>