<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const clock = ref('')

function tick() {
  const now = new Date()
  const d = String(now.getDate()).padStart(2,'0') + '/' + String(now.getMonth()+1).padStart(2,'0') + '/' + now.getFullYear()
  clock.value = `${d} · ${now.toTimeString().slice(0,8)}`
}
let interval
onMounted(() => { tick(); interval = setInterval(tick, 1000) })
onUnmounted(() => clearInterval(interval))

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}

const initiales = () => {
  const nom = authStore.utilisateur?.nom || ''
  return nom.split(' ').map(n => n[0]).join('').toUpperCase().slice(0,2)
}
</script>

<template>
  <div class="app">
    <div class="sidebar">
      <div class="mark">TA</div>
      <router-link to="/dashboard" class="nav-item" :class="{active: route.path==='/dashboard'}" title="Surveillance">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/></svg>
      </router-link>
      <router-link to="/cameras" class="nav-item" :class="{active: route.path==='/cameras'}" title="Caméras">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="6" width="14" height="12" rx="2"/><path d="M16 10l6-3v10l-6-3"/></svg>
      </router-link>
      <router-link v-if="authStore.utilisateur?.role==='manager'" to="/manager" class="nav-item" :class="{active: route.path==='/manager'}" title="Analytics">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 20V10M12 20V4M20 20v-7"/></svg>
      </router-link>
      <router-link to="/anomalies" class="nav-item" :class="{active: route.path.startsWith('/anomalies')}" title="Historique">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/><path d="M12 7v5l3 3"/></svg>
      </router-link>
      <router-link v-if="authStore.utilisateur?.role==='manager'" to="/operateurs" class="nav-item" :class="{active: route.path==='/operateurs'}" title="Administration">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.2"/><path d="M5 20c0-3.9 3.1-7 7-7s7 3.1 7 7"/></svg>
      </router-link>
      <div class="spacer"></div>
      <div class="avatar" @click="handleLogout" title="Déconnexion" style="cursor:pointer">{{ initiales() }}</div>
    </div>

    <div class="main">
      <div class="topbar">
        <div class="brand">
          <div class="name">TERMINAL ALLIANCE — SURVEILLANCE IA</div>
          <div class="sub">
            {{ route.path==='/manager' ? 'MODULE MANAGER · BUSINESS INTELLIGENCE' : 'MODULE OPÉRATEUR · SURVEILLANCE TEMPS RÉEL' }}
          </div>
        </div>
        <div class="right">
          <div class="live-clock-wrap"><span class="pulse-dot"></span><span>{{ clock }}</span></div>
          <div class="user-chip">
            <div class="dot">{{ initiales() }}</div>
            <div class="info">
              <span class="n">{{ authStore.utilisateur?.nom }}</span>
              <span class="r">{{ authStore.utilisateur?.role === 'manager' ? 'Manager sécurité' : 'Opérateur sécurité' }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="view">
        <slot />
      </div>
    </div>
  </div>
</template>

<style scoped>
.app{ display:flex; min-height:100vh; }

.sidebar{
  width:76px; background:var(--panel); border-right:1px solid var(--border);
  display:flex; flex-direction:column; align-items:center; padding:20px 0; gap:8px; flex-shrink:0;
}
.mark{
  width:36px; height:36px; border-radius:8px;
  background:linear-gradient(135deg, var(--cyan), #1a7a91);
  display:flex; align-items:center; justify-content:center;
  font-family:var(--font-mono); font-weight:700; font-size:13px; color:#08131a; margin-bottom:28px;
}
.nav-item{
  width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center;
  color:var(--text-dim); cursor:pointer; position:relative; text-decoration:none;
}
.nav-item svg{ width:20px; height:20px; }
.nav-item.active{ background:var(--panel-2); color:var(--cyan); }
.nav-item:hover{ color:var(--text); }
.spacer{ flex:1; }
.avatar{
  width:34px; height:34px; border-radius:8px; background:var(--panel-2); border:1px solid var(--border);
  display:flex; align-items:center; justify-content:center; font-family:var(--font-mono); font-size:11px; color:var(--text-muted);
}

.main{ flex:1; display:flex; flex-direction:column; min-width:0; }
.topbar{
  height:64px; border-bottom:1px solid var(--border); display:flex; align-items:center;
  justify-content:space-between; padding:0 28px; flex-shrink:0; background:var(--bg);
}
.brand .name{ font-weight:600; font-size:14px; letter-spacing:.04em; }
.brand .sub{ font-family:var(--font-mono); font-size:10.5px; color:var(--text-dim); letter-spacing:.08em; }
.right{ display:flex; align-items:center; gap:22px; }
.live-clock-wrap{ font-family:var(--font-mono); font-size:12.5px; color:var(--text-muted); display:flex; align-items:center; gap:8px; }
.user-chip{ display:flex; align-items:center; gap:9px; padding:6px 12px 6px 6px; background:var(--panel-2); border:1px solid var(--border); border-radius:24px; }
.user-chip .dot{ width:26px; height:26px; border-radius:50%; background:#2A3746; display:flex; align-items:center; justify-content:center; font-size:10px; font-family:var(--font-mono); color:var(--text-muted); }
.user-chip .info{ display:flex; flex-direction:column; line-height:1.2; }
.user-chip .info .n{ font-size:12px; font-weight:500; }
.user-chip .info .r{ font-size:10px; color:var(--text-dim); }

.view{ flex:1; overflow-y:auto; padding:24px 28px 40px; }
</style>