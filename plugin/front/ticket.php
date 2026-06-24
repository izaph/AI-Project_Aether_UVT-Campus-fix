<?php
include('../../../inc/includes.php');
require_once('../inc/mlservice.class.php');

$qr_id = $_GET['qr_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $input = json_decode(file_get_contents('php://input'), true);
    $descriere  = trim($input['descriere'] ?? '');
    $categorie  = trim($input['categorie'] ?? '');
    $locatie    = trim($input['locatie'] ?? '');
    $titlu      = trim($input['titlu'] ?? '');
    $ai_cat     = trim($input['ai_category'] ?? '');
    $ai_conf    = (float)($input['ai_confidence'] ?? 0);

    if (empty($descriere)) {
        http_response_code(400);
        echo json_encode(['error' => 'Descrierea este obligatorie']);
        exit;
    }

    $ai_result = null;
    if (empty($categorie) || $categorie === 'auto') {
        $ai_result = PluginUvtcampusfixMlservice::classify($descriere);
        $categorie = $ai_result['category'];
        $ai_cat  = $ai_result['category'];
        $ai_conf = $ai_result['confidence'];
    }

    $category_map = [
        'IT'            => 1,
        'Retea'         => 2,
        'Administrativ' => 3,
    ];

    $ticket = new Ticket();
    $ticket_data = [
        'name'              => !empty($titlu) ? $titlu : mb_substr($descriere, 0, 80),
        'content'           => htmlspecialchars($descriere, ENT_QUOTES, 'UTF-8'),
        'itilcategories_id' => $category_map[$categorie] ?? 0,
        'urgency'           => 3,
        'type'              => Ticket::INCIDENT_TYPE,
        '_disablenotif'     => true,
    ];

    if (!empty($locatie)) {
        $ticket_data['locations_id'] = (int)$locatie;
    }

    $ticket_id = $ticket->add($ticket_data);

    if ($ticket_id) {
        if (!empty($ai_cat)) {
            PluginUvtcampusfixMlservice::sendFeedback($descriere, $ai_cat, $categorie, $ai_conf, (int)$ticket_id);
        }
        echo json_encode([
            'success'     => true,
            'ticket_id'   => (int)$ticket_id,
            'ai_category' => $ai_cat,
            'ai_confidence' => $ai_conf,
            'final_category' => $categorie,
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Eroare la crearea tichetului']);
    }
    exit;
}

Html::header('Raportare Incident', '', 'plugins', 'uvtcampusfix');
?>

<div class="container-fluid" style="max-width: 700px; margin: 0 auto;">
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Raportează un incident
            </h3>
            <?php if ($qr_id): ?>
                <small class="text-white-50">
                    <i class="fas fa-qrcode me-1"></i>
                    Scanat: <?php echo htmlspecialchars($qr_id); ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <!-- Success message (hidden initially) -->
            <div id="successMsg" class="alert alert-success d-none">
                <h5><i class="fas fa-check-circle me-2"></i>Tichet creat cu succes!</h5>
                <p>Tichetul <strong>#<span id="ticketNum"></span></strong> a fost înregistrat.</p>
                <p id="aiInfo" class="mb-0 d-none">
                    <i class="fas fa-robot me-1"></i>
                    Categorie sugerată de AI: <strong><span id="aiCatResult"></span></strong>
                    (încredere: <span id="aiConfResult"></span>%)
                </p>
                <hr>
                <a href="ticket.php" class="btn btn-outline-success">
                    <i class="fas fa-plus me-1"></i>Raportează altă problemă
                </a>
                <a id="viewTicketLink" href="#" class="btn btn-outline-primary ms-2">
                    <i class="fas fa-eye me-1"></i>Vezi tichetul în GLPI
                </a>
            </div>

            <!-- Error message -->
            <div id="errorMsg" class="alert alert-danger d-none">
                <i class="fas fa-exclamation-circle me-1"></i>
                <span id="errorText"></span>
            </div>

            <!-- Form -->
            <form id="ticketForm">
                <!-- Locație -->
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-map-marker-alt me-1"></i>Locație / Echipament
                    </label>
                    <?php if ($qr_id): ?>
                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($qr_id); ?>" readonly>
                        <input type="hidden" name="locatie" id="locatie" value="<?php echo htmlspecialchars($qr_id); ?>">
                        <small class="text-muted">Pre-completat din codul QR scanat.</small>
                    <?php else: ?>
                        <input type="text" name="locatie" id="locatie" class="form-control" placeholder="Ex: Sala A11, Laborator C2">
                    <?php endif; ?>
                </div>

                <!-- Titlu -->
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-heading me-1"></i>Titlu (opțional)
                    </label>
                    <input type="text" name="titlu" id="titlu" class="form-control" placeholder="Ex: Proiectorul nu funcționează" maxlength="100">
                </div>

                <!-- Descriere -->
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-align-left me-1"></i>Descrie problema <span class="text-danger">*</span>
                    </label>
                    <textarea name="descriere" id="descriere" rows="4" required class="form-control"
                              placeholder="Ex: Proiectorul din sala A11 nu pornește, deși este conectat la priză..."></textarea>
                </div>

                <!-- Categorie -->
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-tag me-1"></i>Categorie
                    </label>
                    <select name="categorie" id="categorie" class="form-select">
                        <option value="auto" selected>🤖 Lasă AI-ul să decidă</option>
                        <option value="IT">Echipament IT (PC, Proiector, Monitor)</option>
                        <option value="Retea">Rețea (Wi-Fi, Internet)</option>
                        <option value="Administrativ">Administrativ (Mobilier, Iluminat)</option>
                    </select>
                </div>

                <!-- AI suggestion preview -->
                <div id="aiSuggestion" class="alert alert-info d-none mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-robot fa-lg me-2"></i>
                        <div>
                            <strong>Sugestie AI:</strong>
                            <span id="aiCategory">—</span>
                            <span class="badge ms-2" id="aiConfidenceBadge">—</span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="acceptAi">
                            <i class="fas fa-check me-1"></i>Acceptă sugestia
                        </button>
                    </div>
                </div>

                <input type="hidden" id="aiCatHidden" value="">
                <input type="hidden" id="aiConfHidden" value="0">

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                        <i class="fas fa-paper-plane me-2"></i>Trimite Tichetul
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const mlUrl = window.location.protocol + '//' + window.location.hostname + ':8000/classify';
    const catLabels = {
        'IT': 'Echipament IT (PC, Proiector, Monitor)',
        'Retea': 'Rețea (Wi-Fi, Internet, Conectivitate)',
        'Administrativ': 'Administrativ (Mobilier, Iluminat, Încălzire)',
        'Necunoscut': 'Necunoscut — alege manual'
    };

    const csrfMeta = document.querySelector('meta[property="glpi:csrf_token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    let typingTimer;
    const descriere  = document.getElementById('descriere');
    const categorie  = document.getElementById('categorie');
    const suggestion = document.getElementById('aiSuggestion');
    const aiCat      = document.getElementById('aiCategory');
    const aiBadge    = document.getElementById('aiConfidenceBadge');
    const acceptBtn  = document.getElementById('acceptAi');

    // Live AI classification
    descriere.addEventListener('input', function() {
        clearTimeout(typingTimer);
        if (categorie.value !== 'auto') return;
        const text = this.value.trim();
        if (text.length < 10) {
            suggestion.classList.add('d-none');
            return;
        }
        typingTimer = setTimeout(() => classifyText(text), 800);
    });

    async function classifyText(text) {
        try {
            const resp = await fetch(mlUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({description: text})
            });
            if (!resp.ok) return;
            const data = await resp.json();

            data.category_label = catLabels[data.category] || data.category;
            data.confidence_pct = Math.round(data.confidence * 100);
            data.confidence_level = data.confidence >= 0.70 ? 'high' : data.confidence >= 0.50 ? 'medium' : 'low';

            document.getElementById('aiCatHidden').value = data.category;
            document.getElementById('aiConfHidden').value = data.confidence;

            if (!data.ai_available || data.category === 'Necunoscut') {
                suggestion.className = 'alert alert-warning mb-3';
                aiCat.textContent = 'Nu pot determina categoria — alege manual.';
                aiBadge.textContent = '';
                aiBadge.className = 'badge';
                acceptBtn.classList.add('d-none');
            } else {
                suggestion.className = 'alert alert-info mb-3';
                aiCat.textContent = data.category_label;
                const pct = data.confidence_pct + '%';
                aiBadge.textContent = pct;
                aiBadge.className = data.confidence_level === 'high' ? 'badge bg-success ms-2'
                    : data.confidence_level === 'medium' ? 'badge bg-warning text-dark ms-2'
                    : 'badge bg-secondary ms-2';

                acceptBtn.classList.remove('d-none');
                acceptBtn.onclick = function() {
                    categorie.value = data.category;
                    suggestion.className = 'alert alert-success mb-3';
                    aiCat.innerHTML = '<i class="fas fa-check me-1"></i>' + data.category_label + ' — acceptată';
                    acceptBtn.classList.add('d-none');
                };
            }
            suggestion.classList.remove('d-none');
        } catch (e) {}
    }

    // Form submit via AJAX
    document.getElementById('ticketForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const desc = descriere.value.trim();
        if (!desc) return;

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Se creează tichetul...';

        const payload = {
            descriere: desc,
            categorie: categorie.value,
            locatie: document.getElementById('locatie') ? document.getElementById('locatie').value : '',
            titlu: document.getElementById('titlu').value,
            ai_category: document.getElementById('aiCatHidden').value,
            ai_confidence: parseFloat(document.getElementById('aiConfHidden').value) || 0,
        };

        try {
            const resp = await fetch(window.location.pathname + window.location.search, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Glpi-Csrf-Token': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            const text = await resp.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseErr) {
                document.getElementById('errorText').textContent = 'Server a returnat non-JSON (HTTP ' + resp.status + '): ' + text.substring(0, 200);
                document.getElementById('errorMsg').classList.remove('d-none');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Trimite Tichetul';
                return;
            }

            if (data.success) {
                document.getElementById('ticketForm').classList.add('d-none');
                document.getElementById('errorMsg').classList.add('d-none');
                document.getElementById('ticketNum').textContent = data.ticket_id;
                document.getElementById('viewTicketLink').href = '/front/ticket.form.php?id=' + data.ticket_id;

                if (data.ai_category) {
                    document.getElementById('aiCatResult').textContent = data.ai_category;
                    document.getElementById('aiConfResult').textContent = Math.round(data.ai_confidence * 100);
                    document.getElementById('aiInfo').classList.remove('d-none');
                }

                document.getElementById('successMsg').classList.remove('d-none');
            } else {
                document.getElementById('errorText').textContent = data.error || 'Eroare necunoscută';
                document.getElementById('errorMsg').classList.remove('d-none');
            }
        } catch (err) {
            document.getElementById('errorText').textContent = 'Eroare de conexiune. Încearcă din nou.';
            document.getElementById('errorMsg').classList.remove('d-none');
        }

        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Trimite Tichetul';
    });
})();
</script>

<?php
Html::footer();
