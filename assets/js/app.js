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

    // Bottom-right legend
    const legend = L.control({ position: 'bottomright' });
    legend.onAdd = function () {
      const div = L.DomUtil.create('div');
      div.innerHTML = `
        <div style="
          background:rgba(20,20,30,0.92);
          border:1px solid rgba(255,255,255,0.12);
          border-radius:10px;
          padding:10px 14px;
          font-family:Inter,sans-serif;
          font-size:12px;
          color:#f0f0f0;
          min-width:130px;
          box-shadow:0 2px 8px rgba(0,0,0,0.5);
          margin-bottom:6px;
        ">
          <div style="font-weight:600;margin-bottom:8px;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;opacity:0.7;">LEGEND</div>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;"><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#D6472E;flex-shrink:0;"></span>High risk</div>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;"><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#E8963E;flex-shrink:0;"></span>Moderate</div>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;"><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#3FA34D;flex-shrink:0;"></span>Low risk</div>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;"><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#2980b9;flex-shrink:0;"></span>Hydrant (active)</div>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;"><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#7f8c8d;flex-shrink:0;"></span>Hydrant (inactive)</div>
          <div style="display:flex;align-items:center;gap:8px;"><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:white;border:2px solid #cc2200;box-sizing:border-box;flex-shrink:0;"></span>Fire Station</div>
        </div>`;
      return div;
    };
    legend.addTo(map);
  }

  /* ---------------- MAP ICONS ---------------- */
  function barangayIcon(riskLevel) {
    const color = riskColor(riskLevel);
    return L.divIcon({
      className: '',
      html: `
        <div style="position:relative;width:32px;height:38px;filter:drop-shadow(0 2px 5px rgba(0,0,0,0.6));">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 38" width="32" height="38">
            <path d="M16 1 C8 1 2 7 2 15 C2 24 16 37 16 37 C16 37 30 24 30 15 C30 7 24 1 16 1Z"
                  fill="${color}" stroke="white" stroke-width="1.5"/>
            <path d="M16 5 C16 5 21 10 21 14 C21 15.5 20.5 16.5 20 17
                     C21 15 20 12 18.5 11 C18.5 13 17.5 14.5 16 15.5
                     C14.5 14.5 13.5 13 13.5 11 C12 12 11 15 12 17
                     C11.5 16.5 11 15.5 11 14 C11 10 16 5 16 5Z"
                  fill="white"/>
            <path d="M16 10 C16 10 19.5 13.5 19.5 16.5
                     C19.5 18.9 17.9 20.5 16 20.5
                     C14.1 20.5 12.5 18.9 12.5 16.5
                     C12.5 13.5 16 10 16 10Z"
                  fill="white"/>
            <path d="M16 13 C16 13 18 15.5 18 17
                     C18 18.1 17.1 19 16 19
                     C14.9 19 14 18.1 14 17
                     C14 15.5 16 13 16 13Z"
                  fill="${color}"/>
          </svg>
        </div>`,
      iconSize: [32, 38],
      iconAnchor: [16, 37],
      popupAnchor: [0, -38]
    });
  }

  function hydrantIcon(status) {
    const isActive = status === 'active';
    const bodyColor = isActive ? '#2980b9' : '#7f8c8d';
    const darkColor = isActive ? '#1a6fcc' : '#606060';
    const midColor  = isActive ? '#2471a3' : '#6b6b6b';
    return L.divIcon({
      className: '',
      html: `
        <div style="filter:drop-shadow(0 2px 4px rgba(0,0,0,0.5));">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 36" width="28" height="36">
            <rect x="8"  y="10" width="12" height="14" rx="3"   fill="${bodyColor}" stroke="white" stroke-width="1.2"/>
            <rect x="10" y="6"  width="8"  height="4"  rx="1.5" fill="${darkColor}" stroke="white" stroke-width="1"/>
            <rect x="12" y="3"  width="4"  height="3"  rx="1"   fill="${darkColor}" stroke="white" stroke-width="0.8"/>
            <rect x="3"  y="14" width="5"  height="3"  rx="1.5" fill="${midColor}"  stroke="white" stroke-width="1"/>
            <rect x="20" y="14" width="5"  height="3"  rx="1.5" fill="${midColor}"  stroke="white" stroke-width="1"/>
            <rect x="6"  y="24" width="16" height="3"  rx="1.5" fill="${darkColor}" stroke="white" stroke-width="1"/>
            <circle cx="14" cy="17" r="2" fill="white" opacity="0.3"/>
          </svg>
        </div>`,
      iconSize: [28, 36],
      iconAnchor: [14, 36],
      popupAnchor: [0, -36]
    });
  }

  function fireStationIcon() {
    return L.divIcon({
      className: '',
      html: `
        <div style="filter:drop-shadow(0 2px 5px rgba(0,0,0,0.6));">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 38 38" width="38" height="38">
            <circle cx="19" cy="19" r="18" fill="white" stroke="#cc2200" stroke-width="2"/>
            <rect x="7"  y="17" width="24" height="13" rx="1" fill="#cc2200"/>
            <rect x="5"  y="14" width="28" height="4"  rx="1" fill="#cc2200"/>
            <path d="M11 14 C11 9 27 9 27 14Z" fill="#cc2200"/>
            <circle cx="19" cy="11" r="4.5" fill="white" stroke="#cc2200" stroke-width="1"/>
            <path d="M19 8 C19 8 21.2 10.2 21.2 11.8 C21.2 13 20.2 14 19 14
                     C17.8 14 16.8 13 16.8 11.8 C16.8 10.2 19 8 19 8Z" fill="#cc2200"/>
            <rect x="8"  y="21" width="9" height="8" rx="1" fill="white" opacity="0.9"/>
            <rect x="21" y="21" width="9" height="8" rx="1" fill="white" opacity="0.9"/>
            <rect x="9.5"  y="23" width="6" height="4" rx="0.5" fill="#cc2200" opacity="0.6"/>
            <rect x="22.5" y="23" width="6" height="4" rx="0.5" fill="#cc2200" opacity="0.6"/>
          </svg>
        </div>`,
      iconSize: [38, 38],
      iconAnchor: [19, 19],
      popupAnchor: [0, -22]
    });
  }

  /* ---------------- BARANGAY INFO MODAL ---------------- */
  window.openBarangayInfo = async function(barangayId) {
    const modal = document.getElementById('brgy-info-modal');
    const body  = document.getElementById('brgy-info-body');
    if (!modal || !body) return;
    body.innerHTML = '<p style="color:var(--ink-dim,#888);font-size:13px;padding:20px;">Loading…</p>';
    modal.style.display = 'flex';

    try {
      const res = await fetch('api/barangay_info.php?id=' + barangayId);
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const d = await res.json();
      const rc = d.risk_level === 'high' ? '#D6472E' : d.risk_level === 'moderate' ? '#E8963E' : '#3FA34D';
      const riskLabel = d.risk_level === 'high' ? 'High Risk' : d.risk_level === 'moderate' ? 'Moderate Risk' : 'Low Risk';

      let latestHtml = '';
      if (d.latest_incident) {
        const inc = d.latest_incident;
        const date = new Date(inc.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
        const statusColor = inc.status === 'active' ? '#D6472E' : '#3FA34D';
        const statusLabel = inc.status === 'fire_out' ? 'Fire Out' : inc.status.charAt(0).toUpperCase() + inc.status.slice(1);
        const ticket = inc.ticket_number || ('TKT-' + String(inc.id).padStart(5, '0'));
        latestHtml = `
          <div style="background:var(--panel,#111);border:1px solid var(--line,#2a2a2a);border-radius:8px;padding:12px 14px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
              <span style="font-size:11px;font-family:'IBM Plex Mono',monospace;color:var(--ember,#e03e1a);">${ticket}</span>
              <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:4px;background:${statusColor}22;color:${statusColor};">${statusLabel}</span>
            </div>
            <div style="font-size:13px;color:var(--ink,#ddd);line-height:1.5;margin-bottom:4px;">${inc.notes || 'No details provided.'}</div>
            <div style="font-size:11px;color:var(--ink-dim,#888);">${date}${inc.reporter_name ? ' · ' + inc.reporter_name : ''}</div>
          </div>`;
      } else {
        latestHtml = `<div style="font-size:13px;color:var(--ink-dim,#888);padding:10px 0;">No incidents recorded in this barangay.</div>`;
      }

      const adminActions = MODE === 'admin' ? `
        <div style="border-top:1px solid var(--line,#2a2a2a);padding-top:14px;margin-top:4px;">
          <div style="font-size:11px;font-weight:600;color:var(--ink-dim,#888);letter-spacing:0.04em;text-transform:uppercase;margin-bottom:10px;">Quick Actions</div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:6px;flex:1;min-width:140px;">
              <label style="font-size:12px;color:var(--ink-dim,#888);white-space:nowrap;">Set Risk:</label>
              <select onchange="window.setBarangayRisk(${d.id}, this.value)"
                style="flex:1;padding:6px 8px;border-radius:6px;border:1px solid var(--line,#2a2a2a);
                       background:var(--panel,#111);color:var(--ink,#fff);font-size:12px;">
                <option value="low"      ${d.risk_level==='low'?'selected':''}>Low</option>
                <option value="moderate" ${d.risk_level==='moderate'?'selected':''}>Moderate</option>
                <option value="high"     ${d.risk_level==='high'?'selected':''}>High</option>
              </select>
            </div>
            <a href="incident_log.php" style="
              display:inline-flex;align-items:center;gap:5px;
              padding:7px 14px;border-radius:6px;
              background:var(--ember,#e03e1a);color:#fff;
              font-size:12px;font-weight:600;text-decoration:none;
            ">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><path d="M12 5v14M5 12h14"/></svg>
              Log Incident
            </a>
          </div>
        </div>` : '';

      body.innerHTML = `
        <!-- Header -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px;">
          <div>
            <div style="font-family:'Oswald',sans-serif;font-size:20px;font-weight:600;color:var(--ink,#fff);line-height:1.2;">${d.name}</div>
            ${d.ever_high_risk ? '<div style="font-size:11px;color:#e09a1a;margin-top:3px;">⚠ Has had high-risk incidents</div>' : ''}
          </div>
          <span style="
            display:inline-block;padding:4px 12px;border-radius:6px;
            background:${rc};color:#fff;
            font-size:12px;font-weight:700;white-space:nowrap;flex-shrink:0;
          ">${riskLabel}</span>
        </div>

        <!-- Stats row -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px;">
          <div style="background:var(--panel,#111);border:1px solid var(--line,#2a2a2a);border-radius:8px;padding:10px 12px;text-align:center;">
            <div style="font-size:22px;font-weight:700;color:var(--ink,#fff);font-family:'Oswald',sans-serif;">${d.incident_count}</div>
            <div style="font-size:11px;color:var(--ink-dim,#888);margin-top:2px;">Total Incidents</div>
          </div>
          <div style="background:var(--panel,#111);border:1px solid var(--line,#2a2a2a);border-radius:8px;padding:10px 12px;text-align:center;">
            <div style="font-size:22px;font-weight:700;color:var(--ink,#fff);font-family:'Oswald',sans-serif;">${d.active_hydrants}<span style="font-size:14px;color:var(--ink-dim,#888);">/${d.total_hydrants}</span></div>
            <div style="font-size:11px;color:var(--ink-dim,#888);margin-top:2px;">Active Hydrants</div>
          </div>
          <div style="background:var(--panel,#111);border:1px solid var(--line,#2a2a2a);border-radius:8px;padding:10px 12px;text-align:center;">
            <div style="font-size:22px;font-weight:700;color:var(--ink,#fff);font-family:'Oswald',sans-serif;">${d.population ?? '—'}</div>
            <div style="font-size:11px;color:var(--ink-dim,#888);margin-top:2px;">Population</div>
          </div>
        </div>

        <!-- Status breakdown -->
        ${d.incident_count > 0 ? `
        <div style="display:flex;gap:8px;margin-bottom:16px;">
          <div style="flex:1;background:rgba(214,71,46,0.1);border:1px solid rgba(214,71,46,0.3);border-radius:7px;padding:8px 10px;text-align:center;">
            <div style="font-size:16px;font-weight:700;color:#D6472E;">${d.active_count}</div>
            <div style="font-size:11px;color:var(--ink-dim,#888);">Active</div>
          </div>
          <div style="flex:1;background:rgba(63,163,77,0.1);border:1px solid rgba(63,163,77,0.3);border-radius:7px;padding:8px 10px;text-align:center;">
            <div style="font-size:16px;font-weight:700;color:#3FA34D;">${d.fireout_count}</div>
            <div style="font-size:11px;color:var(--ink-dim,#888);">Fire Out</div>
          </div>
        </div>` : ''}

        <!-- Latest incident -->
        <div style="margin-bottom:16px;">
          <div style="font-size:11px;font-weight:600;color:var(--ink-dim,#888);letter-spacing:0.04em;text-transform:uppercase;margin-bottom:8px;">Latest Incident</div>
          ${latestHtml}
        </div>

        <!-- Coordinates -->
        <div style="margin-bottom:16px;">
          <div style="font-size:11px;font-weight:600;color:var(--ink-dim,#888);letter-spacing:0.04em;text-transform:uppercase;margin-bottom:6px;">Coordinates</div>
          <div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:var(--ink-dim,#aaa);background:var(--panel,#111);padding:8px 12px;border-radius:6px;border:1px solid var(--line,#2a2a2a);">
            ${d.lat ? parseFloat(d.lat).toFixed(6) : '—'}, ${d.lng ? parseFloat(d.lng).toFixed(6) : '—'}
          </div>
        </div>

        ${adminActions}
      `;
    } catch(e) {
      body.innerHTML = '<p style="color:red;padding:20px;">Failed to load barangay info.</p>';
    }
  };

  document.addEventListener('click', e => {
    const modal = document.getElementById('brgy-info-modal');
    if (modal && e.target === modal) modal.style.display = 'none';
  });

  /* ---------------- RENDER MAP MARKERS ---------------- */
  function renderMapMarkers() {
    if (!markersLayer) return;
    markersLayer.clearLayers();

    barangays.forEach(b => {
      const marker = L.marker([b.lat, b.lng], { icon: barangayIcon(b.risk_level) }).addTo(markersLayer);
      const rc = riskColor(b.risk_level);
      const rLabel = b.risk_level === 'high' ? 'High Risk' : b.risk_level === 'moderate' ? 'Moderate Risk' : 'Low Risk';
      const popup = L.popup({ className: 'firestrat-popup' }).setContent(`
  <div style="min-width:160px;">
    <div style="font-family:'Oswald',sans-serif;font-size:15px;font-weight:600;color:#fff;margin-bottom:6px;">${b.name}</div>
    <span style="display:inline-block;padding:3px 10px;border-radius:5px;background:${rc};color:#fff;font-size:11px;font-weight:700;margin-bottom:10px;">${rLabel}</span>
    <div>
      <button class="brgy-more-info-btn" data-id="${b.id}"
        style="width:100%;padding:7px 0;background:#e03e1a;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;">
        More Info
      </button>
    </div>
  </div>`);
marker.bindPopup(popup);
marker.on('popupopen', function() {
  document.querySelectorAll('.brgy-more-info-btn').forEach(btn => {
    btn.onclick = function() {
      window.openBarangayInfo(parseInt(this.dataset.id));
      marker.closePopup();
    };
  });
});
      if (MODE === 'admin') {
        marker.on('click', () => {
          const sel = document.getElementById('fBrgy');
          if (sel) { sel.value = b.name; document.getElementById('incidents')?.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        });
      }
    });

    hydrants.forEach(h => {
      const marker = L.marker([h.lat, h.lng], { icon: hydrantIcon(h.status) }).addTo(markersLayer);
      marker.bindPopup(
        `<div class="popup-title">Hydrant ${h.code}</div>
         <div class="popup-row">Barangay: ${h.barangay_name}</div>
         <div class="popup-row">Status: ${h.status}</div>`
      );
    });

    // Virac Fire Station
    const fireStation = L.marker([13.5785, 124.2172], { icon: fireStationIcon() }).addTo(markersLayer);
    fireStation.bindPopup(
      `<div class="popup-title">🚒 Virac Fire Station</div>
       <div class="popup-row">BFP Virac, Catanduanes</div>
       <div class="popup-row">Main fire response station</div>`
    );
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
        <div class="li-txt">
          <div class="n">${i.barangay_name}</div>
          <div class="t">${new Date(i.created_at).toLocaleString()}</div>
        </div>
      </div>`).join('') || '<div class="list-item"><div class="li-txt"><div class="t">No incidents found.</div></div></div>';

    const badge = document.getElementById('incBadge');
    if (badge) badge.textContent = incidents.filter(i => i.status === 'active').length;
  }

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
        <div class="li-txt">
          <div class="n">Hydrant #${h.code}</div>
          <div class="t">${h.barangay_name}</div>
        </div>
        <button class="status-pill ${h.status}" data-id="${h.id}">
          ${h.status === 'active' ? '● Active' : '● Inactive'}
        </button>
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
        <div class="bl">
          <span>${b.name}</span>
          <span>${count} incident${count === 1 ? '' : 's'}</span>
        </div>
        <div class="bar-track">
          <div class="bar-fill" style="width:${Math.max(8, Math.round(count / max * 100))}%;background:${riskColor(b.risk_level)}"></div>
        </div>
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
    const risk_level  = document.getElementById('fRisk').value;
    const notes       = document.getElementById('fNotes').value;
    const msg         = document.getElementById('formMsg');
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
        <div class="badge" style="background:${riskColor(b.risk_level)}">
          ${b.risk_level === 'high' ? '!' : b.risk_level === 'moderate' ? '~' : '✓'}
        </div>
        <div>
          <div class="zn">${b.name}</div>
          <div class="zd">${b.risk_level === 'high' ? 'High risk — stay alert' : b.risk_level === 'moderate' ? 'Moderate risk' : 'Low risk right now'}</div>
        </div>
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
    const consent = document.getElementById('privacyConsent');
    if (consent && !consent.checked) {
      const msg = document.getElementById('commMsg');
      msg.className = 'form-msg err';
      msg.textContent = 'Please read and accept the Privacy Notice before submitting.';
      return;
    }
    const barangay_id      = document.getElementById('cBrgy').value;
    const reporter_name    = document.getElementById('cName').value;
    const reporter_contact = document.getElementById('cPhone').value;
    const notes            = document.getElementById('cNotes').value;
    const msg              = document.getElementById('commMsg');
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
  const backdrop  = document.getElementById('sidebarBackdrop');
  if (mobileMenuBtn && sidebarEl && backdrop) {
    mobileMenuBtn.onclick = () => { sidebarEl.classList.add('open');    backdrop.classList.add('open');    };
    backdrop.onclick      = () => { sidebarEl.classList.remove('open'); backdrop.classList.remove('open'); };
  }

  if (MODE !== 'hazardmap') {
    initMap();
    setTimeout(() => { if (map) map.invalidateSize(); }, 100);
    loadAll().catch(e => console.error('FireStrat load error:', e));
  }

  /* ---------------- GLOBAL HANDLERS ---------------- */
  window.setIncidentStatus = async function(id, status) {
    try {
      await api('api/incident_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
      });
      location.reload();
    } catch (e) { alert('Could not update status: ' + e.message); }
  };

  window.setBarangayRisk = async function(id, level) {
    try {
      await api('api/barangays.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, risk_level: level })
      });
      location.reload();
    } catch (e) { alert('Could not update risk level: ' + e.message); }
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
    } catch (e) { alert('Could not approve: ' + e.message); }
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
    } catch (e) { alert('Could not reject: ' + e.message); }
  };

  window.toggleOnspotForm = function(id) {
    const el = document.getElementById('onspot-form-' + id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
  };

  window.submitOnspotReport = async function(id) {
    const val = (suffix) => document.getElementById(suffix + '_' + id)?.value || null;
    const payload = {
      incident_id:           id,
      investigator_name:     val('inv_name'),
      report_time:           val('inv_time'),
      report_date:           val('inv_date'),
      location:              val('inv_location'),
      position:              val('inv_position'),
      risk_level:            val('inv_risk'),
      estimated_cost_damage: val('inv_cost'),
      building_type:         val('inv_building'),
      personnel_deployed:    val('inv_deployed'),
      personnel_injured:     val('inv_inj_pers'),
      civilians_injured:     val('inv_inj_civ'),
      deaths:                val('inv_deaths'),
      witness_name:          val('inv_witness'),
      witness_address:       val('inv_wit_addr'),
      testimony:             val('inv_testimony'),
      conclusion_of_origin:  val('inv_conclusion')
    };
    try {
      await api('api/onspot_report.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      alert('On-spot report saved.');
      location.reload();
    } catch (e) { alert('Could not save report: ' + e.message); }
  };

  window.togglePostForm = function(id) {
    const el = document.getElementById('post-form-' + id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
  };

  window.submitPostReport = async function(id) {
    const val = (suffix) => document.getElementById(suffix + '_' + id)?.value || null;
    const fileInput = document.getElementById('post_photo_' + id);
    const formData = new FormData();
    formData.append('incident_id',                id);
    formData.append('investigator_name',          val('post_name'));
    formData.append('location',                   val('post_location'));
    formData.append('report_time',                val('post_time'));
    formData.append('report_date',                val('post_date'));
    formData.append('position',                   val('post_position'));
    formData.append('risk_level',                 val('post_risk'));
    formData.append('estimated_final_cost_damage',val('post_cost'));
    formData.append('building_type',              val('post_building'));
    formData.append('incident_scale',             val('post_scale'));
    formData.append('personnel_deployed',         val('post_deployed'));
    formData.append('personnel_injured',          val('post_inj_pers'));
    formData.append('civilians_injured',          val('post_inj_civ'));
    formData.append('deaths',                     val('post_deaths'));
    formData.append('descriptive_narration',      val('post_narration'));
    if (fileInput && fileInput.files[0]) formData.append('zone_photo', fileInput.files[0]);
    try {
      const res = await fetch('api/post_incident_report.php', { method: 'POST', body: formData });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(data.error || 'Request failed');
      alert('Post incident report saved.');
      location.reload();
    } catch (e) { alert('Could not save report: ' + e.message); }
  };

  window.toggleViewReports = function(id) {
    const el = document.getElementById('view-reports-' + id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
  };

  window.createUser = async function() {
    const fullName = document.getElementById('newFullName').value.trim();
    const username = document.getElementById('newUsername').value.trim();
    const password = document.getElementById('newPassword').value;
    const role     = document.getElementById('newRole').value;
    const msg      = document.getElementById('userMsg');
    if (!fullName || !username || !password) {
      msg.className = 'form-msg err';
      msg.textContent = 'Full name, username, and password are all required.';
      msg.style.display = 'block'; return;
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
    } catch (e) { alert('Could not delete: ' + e.message); }
  };

})();