# UVT Campus Fix — Project Context for Claude Code

## Repository

- **GitHub**: https://github.com/izaph/AI-Project_Aether_UVT-Campus-fix
- **Default branch**: `main`
- **Team name**: Project Aether

## What this project is

UVT Campus Fix is an **institutional ticketing system** for the West University of Timișoara (Universitatea de Vest din Timișoara, UVT). Students, teaching staff, and administrative personnel report infrastructure and technical problems around campus — broken projectors, faulty Wi-Fi, broken furniture, missing consumables, lighting/heating issues, etc. Administrators triage and assign these tickets to technicians who resolve them, and confirm resolution.

The distinctive feature is an **AI classifier**: when someone submits a ticket, a machine-learning service reads the free-text description (in Romanian) and automatically suggests the category (IT, Retea, or Administrativ), so the ticket routes to the right department without manual sorting.

This is an **academic team project** for a university course (it is NOT a thesis/licență, and NOT affiliated with any company). Built by a 5-person team. The deadline context is compressed — the team works fast using AI assistance, so treat the timeline as "get to a complete, demonstrable system quickly" rather than a multi-week schedule.

## Core architectural decision (read this first)

We do **NOT** build a ticketing system from scratch. We build **on top of GLPI** — a mature, open-source (GPLv3) IT Service Management platform used by public institutions across Europe. The course coordinator explicitly approved using an existing open-source base, and we **must** acknowledge GLPI openly (it is mentioned in our documentation; this is legitimate "fork & extend", not passing GLPI off as our own work).

GLPI provides the entire ticketing core for free:
- Authentication and role-based access
- Full ticket lifecycle (New → Assigned → Processing → Solved → Closed)
- Asset/inventory management (computers, projectors, network devices, etc.)
- Locations hierarchy (buildings → floors → rooms)
- Email notifications
- Categories, SLAs, statistics

**Our team's contribution** is what makes this project ours:
1. A **custom GLPI plugin** (PHP, directory name `uvtcampusfix`) extending GLPI with UVT-specific features.
2. An **ML classification microservice** (Python + FastAPI) that the plugin calls over HTTP.
3. **GLPI configuration** tailored for UVT (locations, categories, roles, Romanian localization).

### Framing (important for how we describe the work)

This is NOT a "reskin". GLPI vanilla cannot do what our system does. We add four genuinely new capabilities (QR ticket creation, AI classification, custom analytics, QR generation for assets). When describing the project, always frame contributions as **new capabilities GLPI does not have**, built on a mature base — the same way real-world software is built on existing platforms (analogy: building a custom WooCommerce plugin on WordPress, not installing a theme).

When working on this project: always prefer extending GLPI through its plugin API over reinventing functionality GLPI already provides. Never duplicate GLPI's database tables — use GLPI's existing schema and API. Keep the ML service decoupled (REST only, never direct DB access).

## The four contributions we are building (plugin features)

1. **QR code workflow** — generate printable QR codes for rooms/equipment; scanning a code on a phone pre-fills the location and equipment fields when creating a ticket. Reporter opens GLPI on phone → scans QR → location auto-filled → adds description + optional photo → submits.
2. **AI-assisted classification** — on ticket submission, the plugin sends the description to the ML service and displays the suggested category with confidence; the user can accept or override.
3. **Custom analytics dashboard** — a plugin page showing UVT-specific metrics not available in stock GLPI: heatmap/breakdown of reported locations, average resolution time per category, per-technician load, AI accuracy. Based on the prototype dashboards (see below).
4. **UVT configuration** — buildings, rooms, ticket categories (IT/Retea/Administrativ), user roles, Romanian localization.

## The three ticket categories

The ML model classifies into exactly three categories (matching the dataset and GLPI config):
- **IT** — computers, projectors, monitors, printers, audio systems, software, hardware (e.g. "proiectorul nu pornește", "monitorul are ecranul spart")
- **Retea** — Wi-Fi, internet, network connectivity, eduroam (e.g. "wi-fi nu merge", "nu am internet prin cablu")
- **Administrativ** — furniture, doors, windows, lighting, heating, cleaning, plumbing (e.g. "ușa nu se închide", "s-a ars un bec")
- **Necunoscut** — returned by the ML service when confidence is below threshold (not a real category, a fallback)

## Tech stack

- **Base platform**: GLPI 10.x / 11.x (PHP + MariaDB), run via the `diouxx/glpi:latest` Docker image
- **Plugin**: PHP, following the official GLPI plugin API conventions (PSR-12 style)
- **ML service**: Python 3.11 + FastAPI + scikit-learn (TF-IDF + Logistic Regression, serialized with joblib)
- **Database**: MariaDB 10.11 (this is GLPI's database — the plugin uses it, does NOT create its own)
- **Infrastructure**: Docker Compose orchestrates GLPI + MariaDB + ML service
- **Charts** (dashboard/prototypes): Chart.js + Tailwind CSS (via CDN)
- **Email**: SMTP via GLPI's built-in notifications (not yet configured)

## Repository structure

```
AI-Project_Aether_UVT-Campus-fix/
├── CLAUDE.md                   # this file
├── docker/
│   └── docker-compose.yml      # orchestrates all 3 services
├── plugin/                     # the custom GLPI plugin (PHP) — Horia + Herlo
│   ├── setup.php               # plugin metadata, init, install/uninstall hooks, menu hook
│   ├── hook.php                # GLPI hooks (currently minimal)
│   ├── front/
│   │   └── index.php           # plugin's main page (currently a status page)
│   └── inc/
│       └── menu.class.php      # PluginUvtcampusfixMenu — menu integration class
├── ml-service/                 # ML classification microservice (Python) — Denis
│   ├── main.py                 # FastAPI server (POST /classify, GET /health)
│   ├── train.py                # trains the model from the CSV, saves model.joblib
│   ├── dataset_tichete.csv     # 100 annotated training examples (text,categorie)
│   ├── Dockerfile              # builds the service, runs train.py at build time
│   └── requirements.txt        # fastapi, uvicorn, pydantic, scikit-learn, pandas, joblib
├── glpi-config/                # GLPI config notes + UI prototypes — Izabella
│   ├── dataset_tichete.csv     # master copy of training data
│   ├── prototype_index.php     # QR ticket submission form — reference for plugin feature 1
│   ├── prototype_dashboard_v1.html  # admin dashboard mockup (simpler, fetches from api.php)
│   ├── prototype_dashboard_v2.html  # admin dashboard mockup (nicest, Tailwind + Chart.js)
│   ├── prototype_dashboard_v3.html  # admin dashboard mockup (GLPI-plugin styled)
│   ├── prototype_generator.html     # QR generator mockup — reference for QR feature
│   ├── prototype_registru.html      # ticket registry mockup
│   ├── prototype_demo.html
│   ├── prototype_api.php            # simple PHP API that stored tickets in JSON (early prototype)
│   └── prototype_tichete.json       # sample ticket data from the early prototype
└── docs/                       # annexes, diagrams, documentation
```

### About the prototypes (use as design reference, not production code)

The `glpi-config/prototype_*` files are early standalone prototypes Izabella built before we moved to GLPI. They are NOT the final implementation — they are visual/behavioral references for building the real plugin features:
- `prototype_index.php` — shows the intended QR-based ticket submission flow (reads `?qr_id=` from URL to simulate a scanned code, pre-fills the equipment/location field, has a category dropdown with "Lasă AI-ul să decidă" option). Use this as the blueprint for plugin feature 1 (QR workflow + AI classification in the ticket form).
- `prototype_dashboard_v2.html` — the best-looking admin dashboard mockup. Tailwind + Chart.js. Shows: open tickets count, AI classification rate, average resolution time, current critical zone; a bar chart of incidents per faculty; a doughnut chart of ticket categories. Use this as the blueprint for plugin feature 3 (analytics dashboard). NOTE: it currently uses hardcoded/demo data — the real version must read live data from GLPI's database.

## How to run the project

From the `docker/` folder:

```
docker-compose up
```

This starts:
- **GLPI** at `http://localhost:8080` (login: `glpi` / `glpi` for Super-Admin; default users are `glpi`/`glpi`, `tech`/`tech`, `normal`/`normal`, `post-only`/`postonly`)
- **ML service** at `http://localhost:8000` (interactive docs at `/docs`, health at `/health`)
- **MariaDB** internally at `db:3306`

GLPI database connection settings (entered during GLPI's web setup wizard):
- SQL server: `db`
- SQL user: `glpi`
- SQL password: `glpi`
- Database: `glpi`

The plugin page is reachable at: `http://localhost:8080/plugins/uvtcampusfix/front/index.php`

### Critical Docker notes (we hit all of these — avoid repeating)

- Use `docker-compose stop` / `docker-compose start` to preserve data. **NEVER use `docker-compose down`** unless you intend to wipe everything — it removes the volumes and forces a full GLPI reinstall (you then have to re-run the GLPI setup wizard). This bit us several times.
- The `diouxx/glpi` image reinstalls GLPI if it doesn't find an existing install in the volume. A `glpi-data` volume is mounted to persist the installation across restarts.
- The plugin is mounted via volume: `../plugin:/var/www/html/glpi/plugins/uvtcampusfix`. The path is relative to the `docker/` folder where docker-compose runs, so it MUST be `../plugin`, not `./plugin`. Getting this wrong means the plugin folder shows up empty inside the container.
- The ML service builds from `../ml-service` with an explicit `context` and `dockerfile`. It runs `train.py` at build time so `model.joblib` exists when the server starts.
- After changing the dataset or training code, rebuild with `docker-compose up --build` (plain `up` reuses the cached image and won't retrain).
- After editing plugin PHP files, changes are live (mounted volume); just refresh GLPI. If GLPI caches something, restart only the glpi container: `docker-compose restart glpi` (but ensure `db` is already up, or GLPI fails to connect to SQL).
- The `version:` key in docker-compose.yml is obsolete and prints a harmless warning; it can be removed.

## The ML service contract

The plugin communicates with the ML service over HTTP/JSON.

**POST /classify**
Request body:
```json
{ "description": "wi-fi nu merge in sala A11" }
```
Response:
```json
{ "category": "Retea", "confidence": 0.62, "ai_available": true }
```

Details:
- Categories returned: `IT`, `Retea`, `Administrativ`, or `Necunoscut` (when confidence < threshold).
- Confidence threshold is currently `0.35` — below this, returns `Necunoscut` rather than guessing. (We lowered it from 0.60 because the small dataset produces modest confidence.)
- Trained on `dataset_tichete.csv` (currently 100 examples, 3 categories). Accuracy is ~60% because the dataset is small; expanding the dataset (toward ~200+ balanced examples) raises accuracy. Target stated in our documentation is ≥75%.
- From inside the Docker network the plugin reaches it at `http://ml-service:8000/classify` (Docker resolves the service name). From the host browser it's `http://localhost:8000`.

**GET /health**
Returns `{ "status": "ok", "model_loaded": true }`.

### Graceful degradation (mandatory)

When integrating the plugin with the ML service, ALWAYS implement graceful degradation: if the ML service is down, errors, or times out (use a short timeout, ~3 seconds), the ticket form must still work — the user just picks the category manually. Never let an ML failure block ticket creation. Also apply a confidence gate in the UI: if the suggestion is low-confidence/`Necunoscut`, don't push a guess on the user.

## Team and ownership

- **Raian Paunchici** (Team Leader) — raian.paunchici06@e-uvt.ro — DevOps, Docker, GLPI deployment, plugin↔ML integration, coordination
- **Horia Bociat** — horia.bociat05@e-uvt.ro — GLPI plugin (PHP), paired with Herlo
- **Andrei Herlo** — andrei.herlo05@e-uvt.ro — GLPI plugin (PHP), paired with Horia
- **Denis Molete** — denis.molete05@e-uvt.ro — ML classification service (Python)
- **Izabella Hanos** — izabella.hanos04@e-uvt.ro — GLPI configuration, UI prototypes, training dataset, testing. (Repo owner: `izaph`.)

## Current status (what already works)

- GLPI deployed and running locally via Docker Compose
- ML service running with a real trained model; classifies correctly (verified: "wi-fi nu merge" → Retea 0.62; "proiectorul nu porneste" → IT 0.50)
- Custom plugin `uvtcampusfix` installed and activated in GLPI; page loads at `/plugins/uvtcampusfix/front/index.php` showing "UVT Campus Fix — Plugin activ ✓"
- Training dataset of 100 annotated Romanian examples (IT / Retea / Administrativ)
- UI prototypes for the ticket form and dashboards (in glpi-config/)
- Annex 2 (Analysis/Design) and Annex 3 (Beta) documents completed

Known minor issue: the plugin does not yet appear in GLPI's left navigation menu (menu hook needs adjustment for GLPI 11). The page is reachable directly by URL. Low priority.

## What remains to be built

1. **Plugin → ML integration**: the plugin calls `POST /classify` on ticket submission and displays the suggested category + confidence; user accepts or overrides. Graceful degradation + confidence gate required.
2. **QR workflow**: a plugin page that generates QR codes for rooms/equipment (PHP `endroid/qr-code` or frontend `qrcode.js`), plus PDF export for printing stickers; scanning a code pre-fills the ticket form (see `prototype_index.php`).
3. **Analytics dashboard**: port `prototype_dashboard_v2.html` into a real plugin page that reads live data from GLPI's database (tickets, categories, locations) and renders Chart.js charts. Include AI accuracy if feedback tracking is added.
4. **Feedback tracking** (optional, strengthens the project academically): an ML endpoint `/feedback` (or plugin-side log) recording AI suggestion vs the user's final choice, so the dashboard can show real-world accuracy ("AI accuracy: N% over M tickets").
5. **GLPI UVT configuration**: locations (UVT buildings + real rooms like A01, A11, C2, labs), categories (IT/Retea/Administrativ), user roles for the team, Romanian localization, SMTP.
6. **Acceptance tests**: at least 8 documented scenarios (authentication, ticket submission, QR workflow, AI classification, assignment, status update, dashboard, notifications).
7. **Final Docker Compose cleanup** (clean one-command startup with GLPI pre-configured) and documentation (Annex 4).

## Coding conventions

- **PHP** (plugin): PSR-12, comments in English, follow GLPI plugin API patterns. Reference official examples at github.com/pluginsGLPI for structure (setup.php hooks, front/ pages, inc/ classes).
- **Python** (ML service): PEP8, comments in English.
- **Git**: feature branches (`feature/qr-workflow`, `feature/ml-integration`), clear English commit messages, PRs reviewed by Raian before merge to `main`. Commit often, not one giant commit.
- **Languages**: Romanian for all user-facing text (the system is for UVT, localized in Romanian); English for code, comments, and commit messages.

## Important guardrails

- Built on GLPI — do NOT rewrite GLPI functionality. Extend it through the plugin API.
- The plugin must NOT create its own database tables unless strictly necessary; use GLPI's schema and API. (A small custom table is acceptable only for feedback tracking if there's no clean GLPI-native way.)
- ALWAYS implement graceful degradation for the ML dependency; never block ticket creation on ML availability.
- Keep the ML service decoupled — REST API only, never direct database access from the ML service.
- Preserve Docker data: never run `docker-compose down` casually; use `stop`/`start`.
- Don't reintroduce the early JSON-file prototype (`prototype_api.php` / `prototype_tichete.json`) into the real system — tickets live in GLPI's MariaDB, not a JSON file. Those files are historical reference only.
- When unsure about a GLPI plugin API detail, check the official GLPI developer documentation rather than guessing — the API differs between GLPI 10 and 11.
