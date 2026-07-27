<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import api from '@/services/api'

const cameras = ref([])
const cameraZoomee = ref(null)
let mediaStream = null

async function chargerCameras() {
  const response = await api.get('/cameras')
  cameras.value = response.data
}

async function demarrerWebcam() {
  try {
    mediaStream = await navigator.mediaDevices.getUserMedia({ video: true })
    await nextTick()
    document.querySelectorAll('video.camera-feed').forEach((v) => {
      v.srcObject = mediaStream
    })
  } catch (e) {
    console.error('Impossible d\'accéder à la webcam:', e)
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
  if (mediaStream) {
    mediaStream.getTracks().forEach((track) => track.stop())
  }
})
</script>

<template>
  <div class="page">
    <router-link to="/dashboard">← Retour au tableau de bord</router-link>
    <h1>Caméras — Surveillance en direct</h1>

    <div class="grille-cameras">
      <div
        v-for="camera in cameras"
        :key="camera.id"
        class="tuile-camera"
        @click="ouvrirZoom(camera)"
      >
        <video class="camera-feed" autoplay muted playsinline></video>
        <div class="overlay-info">
          <span class="nom">{{ camera.nom }}</span>
          <span class="zone">{{ camera.zone }}</span>
        </div>
        <div class="statut-dot" :class="camera.statut"></div>
      </div>
      <p v-if="cameras.length === 0">Aucune caméra enregistrée.</p>
    </div>

    <!-- Vue zoomée en plein écran -->
    <div v-if="cameraZoomee" class="modal-zoom" @click.self="fermerZoom">
      <div class="contenu-zoom">
        <div class="header-zoom">
          <span>{{ cameraZoomee.nom }} — {{ cameraZoomee.zone }}</span>
          <button @click="fermerZoom">✕ Fermer</button>
        </div>
        <video class="camera-feed-zoom" autoplay muted playsinline></video>
      </div>
    </div>
  </div>
</template>

<style scoped>
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
  border: 2px solid transparent;
  transition: border-color 0.2s;
}
.tuile-camera:hover {
  border-color: #2563eb;
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
  background: linear-gradient(transparent, rgba(0, 0, 0, 0.75));
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
.statut-dot.actif { background: #22c55e; }
.statut-dot.inactif { background: #6b7280; }
.statut-dot.maintenance { background: #f59e0b; }

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
  background: #ef4444;
  color: white;
  border: none;
  padding: 8px 14px;
  border-radius: 6px;
  cursor: pointer;
}
.camera-feed-zoom {
  width: 100%;
  border-radius: 10px;
  background: #000;
}
</style>