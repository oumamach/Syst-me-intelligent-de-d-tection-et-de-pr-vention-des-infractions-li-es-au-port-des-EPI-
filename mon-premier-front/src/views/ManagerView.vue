<script setup>
import { ref, onMounted, nextTick } from 'vue'
import api from '@/services/api'
import Chart from 'chart.js/auto'

const stats = ref(null)
const anomalies = ref([])

onMounted(async () => {
  const [s, a] = await Promise.all([api.get('/statistiques'), api.get('/anomalies')])
  stats.value = s.data
  anomalies.value = a.data
  await nextTick()
  dessinerGraphiques()
})

function dessinerGraphiques() {
  const gridColor = '#242E3A'
  Chart.defaults.font.family = "'IBM Plex Mono', monospace"
  Chart.defaults.color = '#8A96A3'

  new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
      labels: stats.value.par_zone.map(z => z.zone),
      datasets: [{ data: stats.value.par_zone.map(z => z.total), backgroundColor: '#2FB8D6', borderRadius: 5 }],
    },
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}},
      scales:{ x:{grid:{display:false}}, y:{grid:{color:gridColor}} } },
  })

  new Chart(document.getElementById('doughnutChart'), {
    type: 'doughnut',
    data: {
      labels: stats.value.par_criticite.map(c => c.criticite),
      datasets: [{ data: stats.value.par_criticite.map(c => c.total),
        backgroundColor: stats.value.par_criticite.map(c => c.criticite === 'haute' ? '#E8483C' : c.criticite === 'moyenne' ? '#F0A23D' : '#45B87E'),
        borderColor: '#121821', borderWidth: 3 }],
    },
    options: { responsive:true, maintainAspectRatio:false, cutout:'68%', plugins:{legend:{position:'bottom'}} },
  })
}
</script>

<template>
  <div v-if="stats">
    <div class="kpi-row">
      <div class="kpi-card"><div class="lbl">Total anomalies</div><div class="val">{{ stats.total }}</div></div>
      <div class="kpi-card"><div class="lbl">Zone la plus touchée</div><div class="val" style="font-size:18px">{{ stats.par_zone[0]?.zone || '—' }}</div></div>
      <div class="kpi-card"><div class="lbl">Criticité dominante</div><div class="val" style="font-size:18px">{{ stats.par_criticite[0]?.criticite || '—' }}</div></div>
      <div class="kpi-card"><div class="lbl">Alertes critiques</div><div class="val">{{ stats.par_criticite.find(c=>c.criticite==='haute')?.total || 0 }}</div></div>
    </div>

    <div class="charts-row">
      <div class="chart-card"><div class="t">Anomalies par zone</div><div class="chart-box"><canvas id="barChart"></canvas></div></div>
      <div class="chart-card"><div class="t">Répartition des criticités</div><div class="chart-box"><canvas id="doughnutChart"></canvas></div></div>
    </div>

    <div class="panel">
      <div class="panel-head"><span class="t">Historique détaillé</span></div>
      <table class="history">
        <thead><tr><th>Date / Heure</th><th>Zone</th><th>Type</th><th>Criticité</th></tr></thead>
        <tbody>
          <tr v-for="a in anomalies" :key="a.id">
            <td class="zone-tag">{{ a.date_detection }}</td>
            <td>{{ a.zone }}</td>
            <td>{{ a.type.replaceAll('_',' ') }}</td>
            <td><span class="badge-sev" :class="a.criticite">{{ a.criticite.toUpperCase() }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.charts-row{ display:grid; grid-template-columns:1.5fr 1fr; gap:20px; margin-bottom:20px; }
</style>