<?php
include('../../../inc/includes.php');
require_once('../inc/mlservice.class.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$description = $input['description'] ?? '';

if (empty(trim($description))) {
    http_response_code(400);
    echo json_encode(['error' => 'Descrierea este obligatorie']);
    exit;
}

$result = PluginUvtcampusfixMlservice::classify($description);

echo json_encode([
    'category'         => $result['category'],
    'category_label'   => PluginUvtcampusfixMlservice::getCategoryLabel($result['category']),
    'confidence'       => $result['confidence'],
    'confidence_pct'   => round($result['confidence'] * 100),
    'confidence_level' => PluginUvtcampusfixMlservice::getConfidenceLevel($result['confidence']),
    'ai_available'     => $result['ai_available'],
]);
