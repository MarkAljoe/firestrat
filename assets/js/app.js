/* FireStrat front-end — talks to api/*.php (JSON) and renders the Leaflet map + panels. */
(function () {
  const MODE = window.FIRESTRAT_MODE || 'admin';
  const VIRAC_CENTER = [13.5824, 124.2350];

  let map, markersLayer;
  let barangays = [], hydrants = [], incidents = [];

  /* ---------------- THEME ---------------- */
  const sunPath = 'M12 4V2M12 22v-2M4.9 4.9L3.5 3.5M20.5 20.5l-1.4-1.4M4 12H2M22 12h-2M4.9 19.1l-1.4 1.4M20.5 3.5l-1.4 1.4M12 17a5 5 0 100-10 5 5 0 000 10z';
  const moonPath = 'M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z';
  function setTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    const icon = document.getElementById('themeIcon');
    if (icon) icon.innerHTML = '<path d="' + (t === 'dark' ? sunPath : moonPath) + '"/>';
    localStorage.setItem('firestrat-theme', t);
  }
  setTheme(localStorage.getItem('firestrat-theme') || 'dark');
  const themeBtn = document.getElementById('themeBtn');
  if (themeBtn) themeBtn.onclick = () => {
    const cur = document.documentElement.getAttribute('data-theme');
    setTheme(cur === 'dark' ? 'light' : 'dark');
  };

  /* ---------------- API HELPERS ---------------- */
  async function api(path, opts) {
    const res = await fetch(path, opts);
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || 'Request failed');
    return data;
  }
  const riskColor = r => r === 'high' ? '#D6472E' : r === 'moderate' ? '#E8963E' : '#3FA34D';

  /* ---------------- MAP ---------------- */
  function initMap() {
    const mapEl = document.getElementById('map');
    if (!mapEl) return;
    const VIRAC_BOUNDS = L.latLngBounds(
      [13.4800, 124.1200],
      [13.6800, 124.3500]
    );

    map = L.map('map', {
      zoomControl: true,
      maxBounds: VIRAC_BOUNDS,
      maxBoundsViscosity: 1.0,
      minZoom: 12
    }).setView(VIRAC_CENTER, 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    markersLayer = L.layerGroup().addTo(map);
  }

  function pinIcon(colorClass, isHydrant) {
    return L.divIcon({
      className: '',
      html: `<div class="map-pin ${colorClass}${isHydrant ? ' hydrant' : ''}" style="width:${isHydrant ? 14 : 18}px;height:${isHydrant ? 14 : 18}px;"></div>`,
      iconSize: [18, 18],
      iconAnchor: [9, 9]
    });
  }

  function renderMapMarkers() {
    if (!markersLayer) return;
    markersLayer.clearLayers();

    barangays.forEach(b => {
      const marker = L.marker([b.lat, b.lng], { icon: pinIcon(b.risk_level, false) }).addTo(markersLayer);
      marker.bindPopup(
        `<div class="popup-title">${b.name}</div>
         <div class="popup-row">Risk level: ${b.risk_level}</div>
         <div class="popup-row">Population: ${b.population ?? '—'}</div>`
      );
      if (MODE === 'admin') {
        marker.on('click', () => {
          const sel = document.getElementById('fBrgy');
          if (sel) { sel.value = b.name; document.getElementById('incidents')?.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        });
      }
    });

    hydrants.forEach(h => {
      const marker = L.marker([h.lat, h.lng], { icon: pinIcon('hydrant', true) }).addTo(markersLayer);
      marker.bindPopup(
        `<div class="popup-title">Hydrant ${h.code}</div>
         <div class="popup-row">Barangay: ${h.barangay_name}</div>
         <div class="popup-row">Status: ${h.status}</div>`
      );
    });
  }

  /* ---------------- LOAD DATA ---------------- */
  async function loadAll() {
    [barangays, hydrants, incidents] = await Promise.all([
      api('api/barangays.php'),
      api('api/hydrants.php'),
      api('api/incidents.php')
    ]);
    renderMapMarkers();
    fillBrgySelects();
    if (MODE === 'admin') {
      renderIncidentList();
      renderHydrantList();
    } else {
      renderCommZones();
    }
  }

  function fillBrgySelects() {
    ['fBrgy', 'cBrgy'].forEach(id => {
      const sel = document.getElementById(id);
      if (!sel) return;
      sel.innerHTML = barangays.map(b => `<option value="${b.id}">${b.name}</option>`).join('');
    });
  }

  /* ---------------- ADMIN: INCIDENT LIST ---------------- */
  function renderIncidentList() {
    const el = document.getElementById('incidentList');
    if (!el) return;
    el.innerHTML = incidents.slice(0, 12).map(i => `
      <div class="list-item">
        <span class="tag ${i.risk_level}">${i.risk_level}</span>
        <div class="li-txt"><div class="n">${i.barangay_name}</div><div class="t">${new Date(i.created_at).toLocaleString()}</div></div>
      </div>`).join('') || '<div class="list-item"><div class="li-txt"><div class="t">No incidents found.</div></div></div>';

    const badge = document.getElementById('incBadge');
if (badge) badge.textContent = incidents.filter(i => i.status === 'active' ).length;  }
// if (badge) badge.textContent = incidents.length;   ||| back up lang kung gustong yung total incidents lang and i show yung total number of incidents regardless of status

/* ---------------- PENDING REPORT ALERT ---------------- */
let lastPendingCount = null;

function playAlarm() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const beep = (time) => {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.value = 880;
      gain.gain.setValueAtTime(0.2, time);
      gain.gain.exponentialRampToValueAtTime(0.001, time + 0.3);
      osc.connect(gain).connect(ctx.destination);
      osc.start(time);
      osc.stop(time + 0.3);
    };
    const now = ctx.currentTime;
    beep(now); beep(now + 0.35); beep(now + 0.7);
  } catch (e) { /* ignore */ }
}

async function checkPendingReports() {
  try {
    const data = await api('api/pending_count.php');
    const count = data.count || 0;
    if (count > 0) playAlarm();
    lastPendingCount = count;
    const badge = document.getElementById('reportsBadge');
    if (badge) { badge.textContent = count; badge.style.display = count > 0 ? '' : 'none'; }
  } catch (e) { /* ignore */ }
}

if (MODE !== 'community') {
  checkPendingReports();
  setInterval(checkPendingReports, 2000);
}

  /* ---------------- ADMIN: HYDRANT LIST ---------------- */
  function renderHydrantList() {
    const el = document.getElementById('hydrantList');
    if (!el) return;
    el.innerHTML = hydrants.map(h => `
      <div class="hy-item">
        <div class="li-txt"><div class="n">Hydrant #${h.code}</div><div class="t">${h.barangay_name}</div></div>
        <button class="status-pill ${h.status}" data-id="${h.id}">${h.status === 'active' ? '● Active' : '● Inactive'}</button>
      </div>`).join('');
    el.querySelectorAll('.status-pill').forEach(btn => {
      btn.onclick = async () => {
        const h = hydrants.find(x => String(x.id) === btn.dataset.id);
        const newStatus = h.status === 'active' ? 'inactive' : 'active';
        try {
          await api('api/hydrants.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: h.id, status: newStatus })
          });
          h.status = newStatus;
          renderHydrantList();
          renderMapMarkers();
        } catch (e) { alert(e.message); }
      };
    });
  }

  /* ---------------- SPLIT ANALYTICS VIEW ---------------- */
  function renderBars() {
    const wrap = document.getElementById('barsWrap');
    if (!wrap) return;
    const counts = barangays.map(b => ({ b, count: incidents.filter(i => i.barangay_id === b.id).length }));
    const max = Math.max(1, ...counts.map(c => c.count));
    wrap.innerHTML = counts.map(({ b, count }) => `
      <div class="bar-row">
        <div class="bl"><span>${b.name}</span><span>${count} incident${count === 1 ? '' : 's'}</span></div>
        <div class="bar-track"><div class="bar-fill" style="width:${Math.max(8, Math.round(count / max * 100))}%;background:${riskColor(b.risk_level)}"></div></div>
      </div>`).join('');
  }
  const splitBtn = document.getElementById('splitBtn');
  if (splitBtn) splitBtn.onclick = () => {
    const panel = document.getElementById('analyticsPanel');
    const showing = panel.style.display === 'block';
    panel.style.display = showing ? 'none' : 'block';
    splitBtn.style.color = showing ? '' : 'var(--ember)';
    if (!showing) renderBars();
  };

  /* ---------------- SEARCH ---------------- */
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    let t;
    searchInput.oninput = e => {
      clearTimeout(t);
      t = setTimeout(async () => {
        incidents = await api('api/incidents.php?q=' + encodeURIComponent(e.target.value));
        renderIncidentList();
      }, 250);
    };
  }

  /* ---------------- ADMIN: LOG INCIDENT ---------------- */
  const submitBtn = document.getElementById('submitIncident');
  if (submitBtn) submitBtn.onclick = async () => {
    const barangay_id = document.getElementById('fBrgy').value;
    const risk_level = document.getElementById('fRisk').value;
    const notes = document.getElementById('fNotes').value;
    const msg = document.getElementById('formMsg');
    try {
      await api('api/incidents.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ barangay_id, risk_level, notes, reported_by: 'admin' })
      });
      document.getElementById('fNotes').value = '';
      msg.className = 'form-msg ok'; msg.textContent = 'Incident logged.';
      await loadAll();
    } catch (e) {
      msg.className = 'form-msg err'; msg.textContent = e.message;
    }
  };

  /* ---------------- COMMUNITY VIEW ---------------- */
  function renderCommZones() {
    const el = document.getElementById('commZones');
    if (!el) return;
    el.innerHTML = barangays.map(b => `
      <div class="comm-zone">
        <div class="badge" style="background:${riskColor(b.risk_level)}">${b.risk_level === 'high' ? '!' : b.risk_level === 'moderate' ? '~' : '✓'}</div>
        <div><div class="zn">${b.name}</div><div class="zd">${b.risk_level === 'high' ? 'High risk — stay alert' : b.risk_level === 'moderate' ? 'Moderate risk' : 'Low risk right now'}</div></div>
      </div>`).join('');
  }
  const reportBtn = document.getElementById('commReportBtn');
  if (reportBtn) reportBtn.onclick = () => {
    document.getElementById('commReportSection').style.display = 'block';
    document.getElementById('commReportSection').scrollIntoView({ behavior: 'smooth' });
  };
  const hydrantBtn = document.getElementById('commHydrantBtn');
  if (hydrantBtn) hydrantBtn.onclick = () => document.getElementById('map').scrollIntoView({ behavior: 'smooth' });

  const commSubmit = document.getElementById('commSubmit');
  if (commSubmit) commSubmit.onclick = async () => {
    const barangay_id = document.getElementById('cBrgy').value;
    const reporter_name = document.getElementById('cName').value;
    const reporter_contact = document.getElementById('cPhone').value;
    const notes = document.getElementById('cNotes').value;
    const msg = document.getElementById('commMsg');
    try {
      await api('api/incidents.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
       body: JSON.stringify({ barangay_id, risk_level: 'moderate', notes, reported_by: 'community', reporter_name, reporter_contact })
      });
      document.getElementById('cNotes').value = '';
      msg.className = 'form-msg ok'; msg.textContent = 'Report sent — thank you! BFP Virac has been notified.';
      await loadAll();
    } catch (e) {
      msg.className = 'form-msg err'; msg.textContent = e.message;
    }
  };

  /* ---------------- INIT ---------------- */
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const pageTitle = document.querySelector('.topbar .page-title');
if (mobileMenuBtn && pageTitle) pageTitle.parentNode.insertBefore(mobileMenuBtn, pageTitle);

const sidebarEl = document.getElementById('sidebar');
const backdrop = document.getElementById('sidebarBackdrop');
if (mobileMenuBtn && sidebarEl && backdrop) {
  mobileMenuBtn.onclick = () => { sidebarEl.classList.add('open'); backdrop.classList.add('open'); };
  backdrop.onclick = () => { sidebarEl.classList.remove('open'); backdrop.classList.remove('open'); };
}
  initMap();
  setTimeout(() => { if (map) map.invalidateSize(); }, 100);
  loadAll().catch(e => console.error('FireStrat load error:', e));

window.setIncidentStatus = async function(id, status) {
  try {
    await api('api/incident_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, status })
    });
    location.reload();
  } catch (e) {
    alert('Could not update status: ' + e.message);
  }
};

  window.setBarangayRisk = async function(id, level) {
  try {
    await api('api/barangays.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, risk_level: level })
    });
    location.reload();
  } catch (e) {
    alert('Could not update risk level: ' + e.message);
  }
};

  window.approveReport = async function(id) {
  if (!confirm('Approve this report? It will become an official incident.')) return;
  try {
    await api('api/report_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, action: 'approve' })
    });
    location.reload();
  } catch (e) {
    alert('Could not approve: ' + e.message);
  }
};

window.rejectReport = async function(id) {
  if (!confirm('Reject this report? It will be dismissed.')) return;
  try {
    await api('api/report_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, action: 'reject' })
    });
    location.reload();
  } catch (e) {
    alert('Could not reject: ' + e.message);
  }
};

window.toggleOnspotForm = function(id) {
  const el = document.getElementById('onspot-form-' + id);
  if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
};

window.submitOnspotReport = async function(id) {
  const val = (suffix) => document.getElementById(suffix + '_' + id)?.value || null;

  const payload = {
    incident_id: id,
    investigator_name: val('inv_name'),
    report_time: val('inv_time'),
    report_date: val('inv_date'),
    location: val('inv_location'),
    position: val('inv_position'),
    risk_level: val('inv_risk'),
    estimated_cost_damage: val('inv_cost'),
    building_type: val('inv_building'),
    personnel_deployed: val('inv_deployed'),
    personnel_injured: val('inv_inj_pers'),
    civilians_injured: val('inv_inj_civ'),
    deaths: val('inv_deaths'),
    witness_name: val('inv_witness'),
    witness_address: val('inv_wit_addr'),
    testimony: val('inv_testimony'),
    conclusion_of_origin: val('inv_conclusion')
  };

  try {
    await api('api/onspot_report.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    alert('On-spot report saved.');
    location.reload();
  } catch (e) {
    alert('Could not save report: ' + e.message);
  }
};

window.togglePostForm = function(id) {
  const el = document.getElementById('post-form-' + id);
  if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
};

window.submitPostReport = async function(id) {
  const val = (suffix) => document.getElementById(suffix + '_' + id)?.value || null;
  const fileInput = document.getElementById('post_photo_' + id);

  const formData = new FormData();
  formData.append('incident_id', id);
  formData.append('investigator_name', val('post_name'));
  formData.append('location', val('post_location'));
  formData.append('report_time', val('post_time'));
  formData.append('report_date', val('post_date'));
  formData.append('position', val('post_position'));
  formData.append('risk_level', val('post_risk'));
  formData.append('estimated_final_cost_damage', val('post_cost'));
  formData.append('building_type', val('post_building'));
  formData.append('incident_scale', val('post_scale'));
  formData.append('personnel_deployed', val('post_deployed'));
  formData.append('personnel_injured', val('post_inj_pers'));
  formData.append('civilians_injured', val('post_inj_civ'));
  formData.append('deaths', val('post_deaths'));
  formData.append('descriptive_narration', val('post_narration'));
  if (fileInput && fileInput.files[0]) {
    formData.append('zone_photo', fileInput.files[0]);
  }

  try {
    const res = await fetch('api/post_incident_report.php', { method: 'POST', body: formData });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || 'Request failed');
    alert('Post incident report saved.');
    location.reload();
  } catch (e) {
    alert('Could not save report: ' + e.message);
  }
};

window.toggleViewReports = function(id) {
  const el = document.getElementById('view-reports-' + id);
  if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
};

window.createUser = async function() {
  const fullName = document.getElementById('newFullName').value.trim();
  const username = document.getElementById('newUsername').value.trim();
  const password = document.getElementById('newPassword').value;
  const role = document.getElementById('newRole').value;
  const msg = document.getElementById('userMsg');

  if (!fullName || !username || !password) {
    msg.className = 'form-msg err';
    msg.textContent = 'Full name, username, and password are all required.';
    msg.style.display = 'block';
    return;
  }

  try {
    await api('api/users.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ full_name: fullName, username, password, role })
    });
    msg.className = 'form-msg ok';
    msg.textContent = 'Account created.';
    msg.style.display = 'block';
    setTimeout(() => location.reload(), 800);
  } catch (e) {
    msg.className = 'form-msg err';
    msg.textContent = e.message;
    msg.style.display = 'block';
  }
};

window.deleteUser = async function(id) {
  if (!confirm('Delete this officer account? This cannot be undone.')) return;
  try {
    await api('api/users.php', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    location.reload();
  } catch (e) {
    alert('Could not delete: ' + e.message);
  }
};

})();
