<?php
include('../../../inc/includes.php');

Session::checkRight('config', READ);

Html::header('Generator QR', '', 'plugins', 'uvtcampusfix');
?>

<div class="container-fluid">
    <div class="card mt-4">
        <div class="card-header bg-secondary text-white">
            <h3 class="card-title mb-0">
                <i class="fas fa-qrcode me-2"></i>Generare Etichete QR — Inventar UVT
            </h3>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <!-- Form -->
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header"><h5 class="mb-0">Date Localizare Echipament</h5></div>
                        <div class="card-body">
                            <form id="qrForm">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Clădire / Sală</label>
                                    <select id="sala" class="form-select" required>
                                        <option value="">— Alege locația —</option>
                                        <optgroup label="Sediul Central (V. Pârvan 4)">
                                            <option value="FMI-A01">Amfiteatrul A01</option>
                                            <option value="FMI-A02">Amfiteatrul A02</option>
                                            <option value="FMI-A11">Sala A11</option>
                                            <option value="FMI-A12">Sala A12</option>
                                            <option value="FMI-Lab029">Laborator 029</option>
                                            <option value="FMI-Lab030">Laborator 030</option>
                                            <option value="FMI-Lab105">Laborator 105</option>
                                            <option value="FMI-Lab120">Laborator 120</option>
                                            <option value="FMI-Secretariat">Secretariat FMI</option>
                                            <option value="FMI-Biblioteca">Biblioteca FMI</option>
                                        </optgroup>
                                        <optgroup label="Facultatea de Litere">
                                            <option value="Litere-S201">Sala 201</option>
                                            <option value="Litere-S202">Sala 202</option>
                                            <option value="Litere-S204">Sala 204</option>
                                            <option value="Litere-S302">Sala 302</option>
                                        </optgroup>
                                        <optgroup label="FEAA">
                                            <option value="FEAA-C1">Sala C1</option>
                                            <option value="FEAA-C2">Sala C2</option>
                                        </optgroup>
                                        <optgroup label="Cămine">
                                            <option value="Camin-C3">Cămin C3</option>
                                            <option value="Camin-C12">Cămin C12</option>
                                        </optgroup>
                                        <optgroup label="Sport">
                                            <option value="Sport-SalaPrincipala">Sala de Sport Principală</option>
                                            <option value="Sport-Fitness">Sala Fitness</option>
                                        </optgroup>
                                    </select>
                                    <div class="mt-2">
                                        <small class="text-muted">Sau introdu manual:</small>
                                        <input type="text" id="salaCustom" class="form-control form-control-sm mt-1"
                                               placeholder="Ex: Corp D - Etaj 2 - Sala 15">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Denumire Echipament</label>
                                    <input type="text" id="echipament" class="form-control" required
                                           placeholder="Ex: Proiector Principal, PC-04, Router Wi-Fi">
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-qrcode me-2"></i>Generează Cod QR
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Batch generator -->
                    <div class="card mt-3">
                        <div class="card-header"><h5 class="mb-0">Generare Lot (Batch)</h5></div>
                        <div class="card-body">
                            <p class="text-muted small">Generează QR-uri pentru toate PC-urile dintr-o sală.</p>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Sală</label>
                                <input type="text" id="batchSala" class="form-control" placeholder="Ex: FMI-Lab029">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Prefix echipament</label>
                                <input type="text" id="batchPrefix" class="form-control" value="PC" placeholder="PC">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Număr echipamente</label>
                                <input type="number" id="batchCount" class="form-control" value="10" min="1" max="30">
                            </div>
                            <button type="button" id="batchBtn" class="btn btn-outline-primary w-100">
                                <i class="fas fa-layer-group me-2"></i>Generează Lot
                            </button>
                        </div>
                    </div>
                </div>

                <!-- QR preview -->
                <div class="col-lg-7">
                    <div id="qrPlaceholder" class="card h-100 d-flex align-items-center justify-content-center" style="min-height: 400px;">
                        <div class="text-center text-muted">
                            <i class="fas fa-qrcode fa-3x mb-3 opacity-25"></i>
                            <p>Completează formularul pentru a genera codul QR.</p>
                        </div>
                    </div>

                    <div id="qrResult" class="d-none">
                        <div class="card">
                            <div class="card-body text-center">
                                <div id="singleQr" class="d-none">
                                    <div class="border border-2 border-dark p-3 d-inline-block bg-white" id="printArea">
                                        <div class="fw-bold fs-6 text-uppercase border-bottom border-2 border-dark pb-1 mb-2">Campus Fix</div>
                                        <img id="qrImage" src="" alt="QR Code" style="width: 200px; height: 200px;" class="mb-2">
                                        <div class="bg-dark text-white py-1 px-2 small fw-bold text-uppercase" id="qrLabel"></div>
                                        <div class="small text-muted mt-1 fw-bold text-uppercase">Scanează în caz de avarie</div>
                                    </div>
                                    <div class="mt-3">
                                        <button class="btn btn-outline-secondary" onclick="window.print()">
                                            <i class="fas fa-print me-1"></i>Imprimă Etichetă
                                        </button>
                                        <a id="qrTestLink" href="#" target="_blank" class="btn btn-outline-primary ms-2">
                                            <i class="fas fa-external-link-alt me-1"></i>Testează Link-ul
                                        </a>
                                    </div>
                                </div>

                                <div id="batchQrs" class="d-none">
                                    <div class="row g-3" id="batchGrid"></div>
                                    <div class="mt-3">
                                        <button class="btn btn-outline-secondary" onclick="window.print()">
                                            <i class="fas fa-print me-1"></i>Imprimă Toate
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body > *:not(#printArea):not(#batchGrid) { display: none !important; }
    #printArea, #batchGrid { display: block !important; }
}
</style>

<script>
(function() {
    const GLPI_BASE = window.location.origin;
    const TICKET_URL = GLPI_BASE + '/plugins/uvtcampusfix/front/ticket.php';

    function buildQrId(sala, echipament) {
        return sala.replace(/\s+/g, '-') + '--' + echipament.replace(/\s+/g, '-');
    }

    function buildQrImageUrl(qrId) {
        const ticketUrl = TICKET_URL + '?qr_id=' + encodeURIComponent(qrId);
        return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data='
            + encodeURIComponent(ticketUrl) + '&color=111827&margin=10';
    }

    // Single QR
    document.getElementById('qrForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const salaSelect = document.getElementById('sala').value;
        const salaCustom = document.getElementById('salaCustom').value.trim();
        const sala = salaCustom || salaSelect;
        const echipament = document.getElementById('echipament').value.trim();
        if (!sala || !echipament) return;

        const qrId = buildQrId(sala, echipament);
        const label = sala + ' — ' + echipament;

        document.getElementById('qrImage').src = buildQrImageUrl(qrId);
        document.getElementById('qrLabel').textContent = label;
        document.getElementById('qrTestLink').href = TICKET_URL + '?qr_id=' + encodeURIComponent(qrId);

        document.getElementById('qrPlaceholder').classList.add('d-none');
        document.getElementById('qrResult').classList.remove('d-none');
        document.getElementById('singleQr').classList.remove('d-none');
        document.getElementById('batchQrs').classList.add('d-none');
    });

    // Batch QR
    document.getElementById('batchBtn').addEventListener('click', function() {
        const sala = document.getElementById('batchSala').value.trim();
        const prefix = document.getElementById('batchPrefix').value.trim() || 'PC';
        const count = parseInt(document.getElementById('batchCount').value) || 10;
        if (!sala) return;

        const grid = document.getElementById('batchGrid');
        grid.innerHTML = '';

        for (let i = 1; i <= count; i++) {
            const echipament = prefix + '-' + String(i).padStart(2, '0');
            const qrId = buildQrId(sala, echipament);
            const label = sala + ' — ' + echipament;

            const col = document.createElement('div');
            col.className = 'col-md-4 col-lg-3';
            col.innerHTML =
                '<div class="border border-2 border-dark p-2 bg-white text-center">' +
                    '<div class="fw-bold small text-uppercase border-bottom border-dark pb-1 mb-1">Campus Fix</div>' +
                    '<img src="' + buildQrImageUrl(qrId) + '" alt="QR" style="width:120px;height:120px;" class="mb-1">' +
                    '<div class="bg-dark text-white py-1 px-1" style="font-size:10px;">' + label + '</div>' +
                    '<div style="font-size:9px;" class="text-muted mt-1 fw-bold">Scanează în caz de avarie</div>' +
                '</div>';
            grid.appendChild(col);
        }

        document.getElementById('qrPlaceholder').classList.add('d-none');
        document.getElementById('qrResult').classList.remove('d-none');
        document.getElementById('singleQr').classList.add('d-none');
        document.getElementById('batchQrs').classList.remove('d-none');
    });
})();
</script>

<?php
Html::footer();
