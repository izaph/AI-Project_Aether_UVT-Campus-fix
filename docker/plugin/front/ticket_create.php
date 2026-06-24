<?php
include('../../../inc/includes.php');

// Ne asigurăm că utilizatorul este autentificat în GLPI
Session::checkLoginUser();

// Validarea securității standard din GLPI 10
Html::checkNonce($_POST);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    
    $ticket = new Ticket();

    // Maparea informațiilor colectate pe structura nativă GLPI
    $input = [
        'name'            => $_POST['name'], 
        'content'         => "<strong>Locație:</strong> " . htmlspecialchars($_POST['location']) . "<br><br>" . 
                             "<strong>Detalii problemă:</strong><br>" . nl2br(htmlspecialchars($_POST['content'])),
        'status'          => Ticket::INCOMING, 
        'urgency'         => 3,                
        'requesttypes_id' => 1,                
    ];

    // Inserarea tichetului în sistem
    $tickets_id = $ticket->add($input);

    if ($tickets_id) {
        Session::addMessageAfterRedirect("Tichetul a fost înregistrat cu succes!", true, MessageAfterRedirect::INFO);
        Html::redirect("index.php");
    } else {
        Session::addMessageAfterRedirect("Eroare la crearea tichetului.", true, MessageAfterRedirect::ERROR);
        Html::redirect("report.php");
    }
} else {
    Html::redirect("index.php");
}