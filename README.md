# FireStrat — Hazard Mapping System for BFP Virac, Catanduanes

A PHP + MySQL + Leaflet.js rewrite of the FireStrat dashboard: a GIS hazard map, incident
logging, hydrant status tracking, and a separate community-facing reporting view.

## Stack
- **Backend:** PHP 8+ (PDO/MySQL, no framework)
- **Database:** MySQL / MariaDB
- **Map:** Leaflet.js + OpenStreetMap tiles (free, no API key required)
- **Frontend:** vanilla HTML/CSS/JS (`assets/`), fetches JSON from `api/*.php`

## Requirements
- PHP 8.0+ with the `pdo_mysql` extension
- MySQL 5.7+/MariaDB 10.3+
- A local server stack: XAMPP, WAMP, MAMP, Laragon, or `php -S` + a MySQL server

## Setup

1. **Create the database.**
   Import `database/firestrat.sql` — this creates the `firestrat` database, all tables,
   and seed data (4 barangays, 3 hydrants, 4 incidents, 1 admin account).

   Using phpMyAdmin: open phpMyAdmin → *Import* → choose `database/firestrat.sql` → Go.

   Using the CLI:
   ```bash
   mysql -u root -p --default-character-set=utf8mb4 < database/firestrat.sql
   ```
   (`--default-character-set=utf8mb4` matters — without it the é/ñ/— characters in
   sample notes can come through corrupted.)

2. **Set your DB credentials.**
   Edit `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'firestrat');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

3. **Run it.**
   - *XAMPP/WAMP:* copy this whole folder into `htdocs/` (or `www/`), then visit
     `http://localhost/firestrat-php/`.
   - *PHP's built-in server:* from the project folder, run
     ```bash
     php -S localhost:8000
     ```
     then visit `http://localhost:8000/`.

4. **Log in.**
   `index.php` requires a session. Default seed account:
   - Username: `fmarshal`
   - Password: `firestrat123`

   The community view needs no login: `http://localhost:8000/community.php`.

## Project structure
```
firestrat-php/
├── config/database.php      PDO connection (edit your DB credentials here)
├── database/firestrat.sql   Schema + seed data
├── includes/
│   ├── auth.php             Session helpers (isLoggedIn, requireLogin)
│   └── sidebar.php          Shared admin nav partial
├── api/
│   ├── barangays.php        GET  — list barangays (for map + selects)
│   ├── hydrants.php         GET  — list hydrants | PATCH — toggle status (auth required)
│   └── incidents.php        GET  — list/search incidents | POST — log new incident
├── assets/
│   ├── css/style.css        Shared design tokens (dark/light theme, cards, map, forms)
│   └── js/app.js            Leaflet map + all dashboard/community interactivity
├── index.php                 Admin dashboard (protected, requireLogin())
├── community.php             Public community view (no login)
├── login.php / logout.php    Session auth
└── README.md
```

## What's implemented
- **Real GIS map** (Leaflet + OpenStreetMap) centered on Virac, plotting barangay
  risk markers and hydrant markers from the database, not a static image.
- **Dark mode toggle**, remembered via `localStorage`.
- **Admin dashboard**: live incident count, high-risk zone count, hydrant uptime %,
  recent incidents list, hydrant status list with a one-click active/inactive toggle
  (writes to the DB, requires login).
- **Split analytics view**: toggle button reveals an incidents-by-barangay bar panel
  next to the map.
- **Incident logging**: admin form and community form both POST to the same
  `api/incidents.php`, which also syncs the barangay's displayed risk level to the
  incident just filed.
- **Community view**: a separate, jargon-free page — report a hazard, see risk by
  barangay, find hydrants — with no admin chrome or login wall.
- **Session-based auth** with bcrypt password hashing (`password_hash` /
  `password_verify`); hydrant/incident write endpoints that should be staff-only are
  gated server-side, not just hidden in the UI.

## Known placeholders — decide before going live
- **Barangay/hydrant coordinates are approximate**, centered near Virac's town
  proper. Replace `lat`/`lng` in `database/firestrat.sql` (or via a future admin
  form) with surveyed GPS points from BFP/the LGU's GIS office.
- **"Pin removed only once a fire is fully extinguished"** — the schema supports
  this (`incidents.status` = active/contained/resolved), but there's no UI yet to
  transition an incident's status after it's logged. Add a status-update control on
  the incident list to close this out.
- **Functional org chart / structures / reports / barangays / users pages** are
  still nav-only placeholders — this build focused on the map, incidents, and
  hydrants modules called out in the compliance sheet.
- **Pending reports** stat on the dashboard is a placeholder value; wire it to a
  real `reports` table if/when that workflow exists.
- No CSRF tokens or rate limiting yet on the POST endpoints — fine for a defense
  demo on localhost, but add both before deploying anywhere public.
