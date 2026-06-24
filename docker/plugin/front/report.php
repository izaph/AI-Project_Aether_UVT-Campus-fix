<?php
// 1. Includem nucleul GLPI
include('../../../inc/includes.php');

// 2. OCOLIRE CONFIG/READ: Forțăm doar verificarea că ești logat în GLPI (valabil pentru orice Super-Admin)
if (!Session::getLoginUserID()) {
    Html::redirect($CFG_GLPI["root_doc"] . "/index.php");
    die();
}

// 3. Încărcăm tema și meniul GLPI
Html::header('Analytics Dashboard', '', 'plugins', 'uvtcampusfix');
?>

<div class="container-fluid" style="margin-top: 20px;">
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-chart-bar"></i> UVT Campus Fix — Dashboard Analytics (Team Aether)</h4>
        </div>
        <div class="card-body">

            <div class="row text-center">
                <div class="col-md-6 mb-3">
                    <div class="p-4 bg-light border rounded shadow-sm">
                        <h5><i class="fas fa-list"></i> Tichete după Categorie</h5>
                        <hr>
                        <p style="font-size: 16px; color: #555;">[Graficele și statisticile tale din prototip vor apărea aici]</p>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="p-4 bg-light border rounded shadow-sm">
                        <h5><i class="fas fa-map-marker-alt"></i> Top Locații UVT afectate</h5>
                        <hr>
                        <p style="font-size: 16px; color: #555;">[Harta sau listele de clădiri configurate]</p>
                    </div>
                </div>
            </div>
            </div>
    </div>
</div>

<?php
// 4. Închidem cu footer-ul GLPI
Html::footer();
