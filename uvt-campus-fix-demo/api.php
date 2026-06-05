<?php
// Setăm formatul de răspuns
header('Content-Type: application/json');

// Fișierul unde salvăm tichetele
$fisier = 'tichete.json';

// Dacă fișierul nu există, îl creăm gol
if (!file_exists($fisier)) {
    file_put_contents($fisier, '[]');
}

// Citim tichetele existente
$tichete = json_decode(file_get_contents($fisier), true);

// DACĂ PRIMIM DATE NOI (Cineva a dat click pe "Trimite" în demo.html)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dateNoi = json_decode(file_get_contents('php://input'), true);
    
    // Adăugăm ora și un ID generat
    $dateNoi['id'] = 'TKT-' . rand(1000, 9999);
    $dateNoi['data'] = date('Y-m-d H:i');
    
    // Salvăm noul tichet în listă
    array_push($tichete, $dateNoi);
    file_put_contents($fisier, json_encode($tichete, JSON_PRETTY_PRINT));
    
    echo json_encode(['success' => true]);
    exit;
}

// DACĂ DOAR CEREM DATELE (Dashboard-ul vrea să afișeze statistici)
echo json_encode($tichete);
?>