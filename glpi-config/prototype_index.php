<?php
$success = false;
$ticket_id = "";

// Simulăm procesarea formularului
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Aici va veni integrarea voastră viitoare cu API-ul GLPI și serviciul de AI al lui Denis
    $echipament_id = $_POST['echipament_id'] ?? 'N/A';
    $categorie = $_POST['categorie'] ?? '';
    $descriere = $_POST['descriere'] ?? '';
    
    // Simulăm un ID de tichet generat cu succes
    $ticket_id = "UVT-TKT-" . rand(1000, 9999);
    $success = true;
}

// Simulăm datele venite din scanarea codului QR (ex: ?qr_id=PRJ-032)
$qr_id = $_GET['qr_id'] ?? 'Sala 029 - Proiector Principal';
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UVT Campus Fix - Raportare Avarie</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-900">

    <div class="max-w-md mx-auto bg-white min-h-screen shadow-lg">
        <div class="bg-blue-800 text-white p-6 rounded-b-3xl shadow-md">
            <h1 class="text-2xl font-bold">UVT Campus Fix</h1>
            <p class="text-blue-200 text-sm mt-1">Sistem rapid de ticketing</p>
        </div>

        <div class="p-6">
            <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm" role="alert">
                    <p class="font-bold">Problemă raportată!</p>
                    <p>Tichetul <strong><?php echo $ticket_id; ?></strong> a fost trimis spre sistemul AI pentru clasificare automată și alocare.</p>
                </div>
                <a href="index.php" class="w-full block text-center bg-blue-800 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-900 transition duration-200">Raportează altă problemă</a>
            <?php else: ?>
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Raportează un incident</h2>
                
                <form action="index.php?qr_id=<?php echo urlencode($qr_id); ?>" method="POST" class="space-y-4">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Echipament / Locație identificată</label>
                        <input type="text" name="echipament_id" value="<?php echo htmlspecialchars($qr_id); ?>" readonly 
                               class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-gray-600">
                        <p class="text-xs text-gray-500 mt-1">Pre-completat din scanarea codului QR.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Categoria (opțional - pentru AI)</label>
                        <select name="categorie" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Lasă AI-ul să decidă</option>
                            <option value="it">Echipament IT (PC, Proiector)</option>
                            <option value="admin">Administrativ (Mobilier, Geamuri)</option>
                            <option value="curatenie">Curățenie / Igienă</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Descrie problema pe scurt *</label>
                        <textarea name="descriere" rows="4" required placeholder="Ex: Proiectorul nu se aprinde, deși este în priză..."
                                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-blue-800 hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                        Trimite Tichetul
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>