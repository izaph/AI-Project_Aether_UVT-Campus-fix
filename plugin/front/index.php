<?php
include('../../../inc/includes.php');

Session::checkRight('config', READ);

Html::header('UVT Campus Fix', '', 'plugins', 'uvtcampusfix');
?>

<div class="container-fluid">
    <div class="card mt-4">
        <div class="card-header">
            <h3>UVT Campus Fix — Plugin activ ✓</h3>
        </div>
        <div class="card-body">
            <p>Versiune: <strong>1.0.0</strong></p>
            <p>Echipa: <strong>Team Aether</strong></p>
            <p>Status: <span class="badge bg-success">Activ</span></p>
        </div>
    </div>
</div>

<?php
Html::footer(); 
