<?php
include('../../../inc/includes.php');
require_once('../inc/mlservice.class.php');

Session::checkRight('config', READ);

global $DB;

// Total open tickets (status: New=1, Processing=2, Planned=3)
$openResult = $DB->request([
    'COUNT' => 'total',
    'FROM'  => 'glpi_tickets',
    'WHERE' => ['status' => [1, 2, 3], 'is_deleted' => 0],
]);
$openTickets = 0;
foreach ($openResult as $row) { $openTickets = (int)$row['total']; }

// Total tickets
$totalResult = $DB->request([
    'COUNT' => 'total',
    'FROM'  => 'glpi_tickets',
    'WHERE' => ['is_deleted' => 0],
]);
$totalTickets = 0;
foreach ($totalResult as $row) { $totalTickets = (int)$row['total']; }

// Tickets per category
$catData = ['IT' => 0, 'Retea' => 0, 'Administrativ' => 0, 'Altele' => 0];
$catResult = $DB->request([
    'SELECT' => ['glpi_itilcategories.name AS cat_name', 'COUNT' => 'glpi_tickets.id AS cnt'],
    'FROM'   => 'glpi_tickets',
    'LEFT JOIN' => ['glpi_itilcategories' => [
        'FKEY' => ['glpi_tickets' => 'itilcategories_id', 'glpi_itilcategories' => 'id']
    ]],
    'WHERE'  => ['glpi_tickets.is_deleted' => 0],
    'GROUPBY' => 'glpi_itilcategories.name',
]);
foreach ($catResult as $row) {
    $name = $row['cat_name'] ?? '';
    if (isset($catData[$name])) {
        $catData[$name] = (int)$row['cnt'];
    } else {
        $catData['Altele'] += (int)$row['cnt'];
    }
}

// Tickets per location (top 5)
$locLabels = [];
$locValues = [];
$locResult = $DB->request([
    'SELECT' => ['glpi_locations.name AS loc_name', 'COUNT' => 'glpi_tickets.id AS cnt'],
    'FROM'   => 'glpi_tickets',
    'LEFT JOIN' => ['glpi_locations' => [
        'FKEY' => ['glpi_tickets' => 'locations_id', 'glpi_locations' => 'id']
    ]],
    'WHERE'  => ['glpi_tickets.is_deleted' => 0, 'NOT' => ['glpi_tickets.locations_id' => 0]],
    'GROUPBY' => 'glpi_locations.name',
    'ORDERBY' => 'cnt DESC',
    'LIMIT'   => 5,
]);
foreach ($locResult as $row) {
    $locLabels[] = $row['loc_name'] ?: 'Nespecificat';
    $locValues[] = (int)$row['cnt'];
}

// Solved tickets count
$solvedResult = $DB->request([
    'COUNT' => 'total',
    'FROM'  => 'glpi_tickets',
    'WHERE' => ['status' => [5, 6], 'is_deleted' => 0],
]);
$solvedTickets = 0;
foreach ($solvedResult as $row) { $solvedTickets = (int)$row['total']; }

// ML service status
$mlHealth = PluginUvtcampusfixMlservice::health();
$mlUp = ($mlHealth['status'] ?? '') === 'ok';

// AI feedback stats (if table exists)
$aiFeedbackTotal = 0;
$aiFeedbackCorrect = 0;
if ($DB->tableExists('glpi_plugin_uvtcampusfix_feedback')) {
    $fbResult = $DB->request([
        'COUNT' => 'total',
        'FROM'  => 'glpi_plugin_uvtcampusfix_feedback',
    ]);
    foreach ($fbResult as $row) { $aiFeedbackTotal = (int)$row['total']; }

    $fbCorrect = $DB->request([
        'COUNT' => 'total',
        'FROM'  => 'glpi_plugin_uvtcampusfix_feedback',
        'WHERE' => ['ai_category = user_category'],
    ]);
    foreach ($fbCorrect as $row) { $aiFeedbackCorrect = (int)$row['total']; }
}
$aiAccuracy = $aiFeedbackTotal > 0 ? round(($aiFeedbackCorrect / $aiFeedbackTotal) * 100) : null;

Html::header('Dashboard Analytics', '', 'plugins', 'uvtcampusfix');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid">
    <div class="card mt-4">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                <i class="fas fa-chart-bar me-2"></i>Analize și Performanță Campus
            </h3>
            <span class="badge <?php echo $mlUp ? 'bg-success' : 'bg-danger'; ?>">
                <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                AI: <?php echo $mlUp ? 'Online' : 'Offline'; ?>
            </span>
        </div>
        <div class="card-body">
            <!-- KPI Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card border-start border-primary border-4 h-100">
                        <div class="card-body">
                            <div class="text-muted small fw-bold text-uppercase">Tichete Deschise</div>
                            <div class="fs-2 fw-bold text-primary mt-1"><?php echo $openTickets; ?></div>
                            <div class="text-muted small mt-1">din <?php echo $totalTickets; ?> total</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-start border-success border-4 h-100">
                        <div class="card-body">
                            <div class="text-muted small fw-bold text-uppercase">Tichete Rezolvate</div>
                            <div class="fs-2 fw-bold text-success mt-1"><?php echo $solvedTickets; ?></div>
                            <div class="text-muted small mt-1">
                                <?php echo $totalTickets > 0 ? round(($solvedTickets / $totalTickets) * 100) . '% rată rezolvare' : '—'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-start border-warning border-4 h-100">
                        <div class="card-body">
                            <div class="text-muted small fw-bold text-uppercase">Acuratețe AI</div>
                            <div class="fs-2 fw-bold mt-1">
                                <?php echo $aiAccuracy !== null ? $aiAccuracy . '%' : '—'; ?>
                            </div>
                            <div class="text-muted small mt-1">
                                <?php echo $aiFeedbackTotal > 0 ? "din $aiFeedbackTotal clasificări" : 'Nu există feedback încă'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-start border-danger border-4 h-100">
                        <div class="card-body">
                            <div class="text-muted small fw-bold text-uppercase">Serviciu ML</div>
                            <div class="fs-4 fw-bold mt-1">
                                <?php if ($mlUp): ?>
                                    <span class="text-success"><i class="fas fa-check-circle"></i> Activ</span>
                                <?php else: ?>
                                    <span class="text-danger"><i class="fas fa-times-circle"></i> Oprit</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted small mt-1">
                                Model: <?php echo ($mlHealth['model_loaded'] ?? false) ? 'Încărcat' : 'Lipsă'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Distribuție Tichete pe Locații (Top 5)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($locLabels)): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-map-marker-alt fa-2x mb-2 opacity-25"></i>
                                    <p>Nu există tichete cu locație specificată.</p>
                                </div>
                            <?php else: ?>
                                <div style="height: 300px;">
                                    <canvas id="locChart"></canvas>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Clasificare pe Categorii</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($totalTickets === 0): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-tag fa-2x mb-2 opacity-25"></i>
                                    <p>Nu există tichete încă.</p>
                                </div>
                            <?php else: ?>
                                <div style="height: 300px;">
                                    <canvas id="catChart"></canvas>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent tickets table -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Ultimele Tichete</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Titlu</th>
                                    <th>Categorie</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $statusLabels = [
                                    1 => ['Nou', 'primary'],
                                    2 => ['În lucru', 'info'],
                                    3 => ['Planificat', 'secondary'],
                                    4 => ['Așteptare', 'warning'],
                                    5 => ['Rezolvat', 'success'],
                                    6 => ['Închis', 'dark'],
                                ];
                                $recent = $DB->request([
                                    'SELECT' => ['glpi_tickets.id', 'glpi_tickets.name', 'glpi_tickets.status',
                                                 'glpi_tickets.date', 'glpi_itilcategories.name AS cat_name'],
                                    'FROM'   => 'glpi_tickets',
                                    'LEFT JOIN' => ['glpi_itilcategories' => [
                                        'FKEY' => ['glpi_tickets' => 'itilcategories_id', 'glpi_itilcategories' => 'id']
                                    ]],
                                    'WHERE'  => ['glpi_tickets.is_deleted' => 0],
                                    'ORDERBY' => 'glpi_tickets.date DESC',
                                    'LIMIT'   => 10,
                                ]);
                                $hasRows = false;
                                foreach ($recent as $row):
                                    $hasRows = true;
                                    $st = $statusLabels[$row['status']] ?? ['Necunoscut', 'secondary'];
                                ?>
                                <tr>
                                    <td><?php echo (int)$row['id']; ?></td>
                                    <td>
                                        <a href="/front/ticket.form.php?id=<?php echo (int)$row['id']; ?>">
                                            <?php echo htmlspecialchars(mb_substr($row['name'], 0, 60)); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['cat_name'] ?: '—'); ?></td>
                                    <td><span class="badge bg-<?php echo $st[1]; ?>"><?php echo $st[0]; ?></span></td>
                                    <td class="text-muted small"><?php echo htmlspecialchars($row['date']); ?></td>
                                </tr>
                                <?php endforeach;
                                if (!$hasRows): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Nu există tichete.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
<?php if (!empty($locLabels)): ?>
new Chart(document.getElementById('locChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($locLabels); ?>,
        datasets: [{
            label: 'Tichete',
            data: <?php echo json_encode($locValues); ?>,
            backgroundColor: 'rgba(37, 99, 235, 0.8)',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
<?php endif; ?>

<?php if ($totalTickets > 0): ?>
new Chart(document.getElementById('catChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_keys($catData)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_values($catData)); ?>,
            backgroundColor: [
                'rgba(59, 130, 246, 0.9)',
                'rgba(16, 185, 129, 0.9)',
                'rgba(139, 92, 246, 0.9)',
                'rgba(148, 163, 184, 0.9)',
            ],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    }
});
<?php endif; ?>
</script>

<?php
Html::footer();
