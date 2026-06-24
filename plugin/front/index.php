<?php
include('../../../inc/includes.php');
require_once('../inc/mlservice.class.php');

Session::checkRight('config', READ);

$mlHealth = PluginUvtcampusfixMlservice::health();
$mlUp = ($mlHealth['status'] ?? '') === 'ok';

Html::header('UVT Campus Fix', '', 'plugins', 'uvtcampusfix');
?>

<div class="container-fluid">
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0">
                <i class="fas fa-tools me-2"></i>UVT Campus Fix — Panou Principal
            </h3>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <!-- Status plugin -->
                <div class="col-md-4">
                    <div class="card h-100 border-success">
                        <div class="card-body text-center">
                            <i class="fas fa-plug fa-2x text-success mb-2"></i>
                            <h5>Plugin</h5>
                            <span class="badge bg-success">Activ — v1.0.0</span>
                            <p class="text-muted mt-2 mb-0">Team Aether</p>
                        </div>
                    </div>
                </div>

                <!-- Status ML -->
                <div class="col-md-4">
                    <div class="card h-100 border-<?php echo $mlUp ? 'success' : 'danger'; ?>">
                        <div class="card-body text-center">
                            <i class="fas fa-robot fa-2x text-<?php echo $mlUp ? 'success' : 'danger'; ?> mb-2"></i>
                            <h5>Serviciu AI</h5>
                            <?php if ($mlUp): ?>
                                <span class="badge bg-success">Online</span>
                                <p class="text-muted mt-2 mb-0">
                                    Model: <?php echo $mlHealth['model_loaded'] ? 'Încărcat' : 'Lipsă'; ?>
                                </p>
                            <?php else: ?>
                                <span class="badge bg-danger">Offline</span>
                                <p class="text-muted mt-2 mb-0">Clasificarea manuală rămâne disponibilă.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick links -->
                <div class="col-md-4">
                    <div class="card h-100 border-primary">
                        <div class="card-body text-center">
                            <i class="fas fa-link fa-2x text-primary mb-2"></i>
                            <h5>Acces Rapid</h5>
                            <div class="d-grid gap-2">
                                <a href="ticket.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i>Raportează Incident
                                </a>
                                <a href="dashboard.php" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-chart-bar me-1"></i>Dashboard Analytics
                                </a>
                                <a href="qr.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-qrcode me-1"></i>Generator QR
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test AI classification -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-flask me-2"></i>Test Clasificare AI</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <textarea id="testDesc" class="form-control" rows="2"
                                      placeholder="Scrie o descriere de test (ex: &quot;wi-fi nu merge în sala A11&quot;)"></textarea>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button id="testBtn" class="btn btn-primary w-100" <?php echo $mlUp ? '' : 'disabled'; ?>>
                                <i class="fas fa-brain me-1"></i>Clasifică
                            </button>
                        </div>
                    </div>
                    <div id="testResult" class="mt-3 d-none">
                        <div class="alert mb-0" id="testAlert">
                            <strong>Categorie:</strong> <span id="resCat">—</span><br>
                            <strong>Încredere:</strong> <span id="resConf">—</span>
                            <span class="badge ms-2" id="resLevel">—</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const btn    = document.getElementById('testBtn');
    const desc   = document.getElementById('testDesc');
    const result = document.getElementById('testResult');
    const alert  = document.getElementById('testAlert');
    const resCat = document.getElementById('resCat');
    const resConf= document.getElementById('resConf');
    const resLvl = document.getElementById('resLevel');

    btn.addEventListener('click', async function() {
        const text = desc.value.trim();
        if (!text) return;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Se clasifică...';

        try {
            const mlUrl = window.location.protocol + '//' + window.location.hostname + ':8000/classify';
            const resp = await fetch(mlUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({description: text})
            });
            const data = await resp.json();

            const catLabels = {
                'IT': 'Echipament IT (PC, Proiector, Monitor)',
                'Retea': 'Rețea (Wi-Fi, Internet, Conectivitate)',
                'Administrativ': 'Administrativ (Mobilier, Iluminat, Încălzire)',
                'Necunoscut': 'Necunoscut — alege manual'
            };
            data.category_label = catLabels[data.category] || data.category;
            data.confidence_pct = Math.round(data.confidence * 100);
            data.confidence_level = data.confidence >= 0.70 ? 'high' : data.confidence >= 0.50 ? 'medium' : 'low';

            resCat.textContent = data.category_label;
            resConf.textContent = data.confidence_pct + '%';

            if (data.confidence_level === 'high') {
                alert.className = 'alert alert-success mb-0';
                resLvl.className = 'badge bg-success ms-2';
                resLvl.textContent = 'Încredere ridicată';
            } else if (data.confidence_level === 'medium') {
                alert.className = 'alert alert-info mb-0';
                resLvl.className = 'badge bg-warning text-dark ms-2';
                resLvl.textContent = 'Încredere medie';
            } else {
                alert.className = 'alert alert-warning mb-0';
                resLvl.className = 'badge bg-secondary ms-2';
                resLvl.textContent = 'Încredere scăzută';
            }

            result.classList.remove('d-none');
        } catch (e) {
            alert.className = 'alert alert-danger mb-0';
            resCat.textContent = 'Serviciul AI nu este disponibil';
            resConf.textContent = '—';
            resLvl.textContent = '';
            result.classList.remove('d-none');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-brain me-1"></i>Clasifică';
    });
})();
</script>

<?php
Html::footer();
