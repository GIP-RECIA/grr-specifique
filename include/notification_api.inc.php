<?php

// Fonction simple pour charger le fichier .env sans dépendance externe
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $value = trim($value, '"\'');
            
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// Charge le .env situé à la racine du projet
$envPath = __DIR__ . '/../.env'; 
if (!file_exists($envPath)) {
}
loadEnv($envPath);
var_dump($_ENV['KAFKA_ENDPOINT'] ?? 'VARIABLE KAFKA_ENDPOINT NON TROUVÉE');

// Génère un UUID v4 pour identifier l'événement Kafka de manière unique.
function generateUUID() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variante RFC 4122
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Construit un array ServiceEvent standardisé pour le producer Kafka.
function buildServiceEvent($service, $channels, $title, $message, $link, $targetId) {
    $dt = new DateTime('now', new DateTimeZone('Europe/Paris'));
    $createdAt = $dt->format('Y-m-d\TH:i:s.vP');

    return array(
        'header' => array(
            'eventId' => generateUUID(),
            'priority' => "NORMAL",
            'service' => $service ? $service : 'GRR2_netocentre',
            'channels' => $channels ? $channels : array('WEB', 'MAIL', 'PUSH'),
            'createdAt' => $createdAt
        ),
        'content' => array(
            'title' => $title,
            'message' => $message,
            'link' => $link
        ),
        'target' => array(
            'type' => 'EMAIL', 
            'ids' => array($targetId)
        )
    );
}

// Construit un array ServiceEvent standardisé pour le producer Kafka MAIS avec un target de type UID.
function buildServiceEventUID($service, $channels, $title, $message, $link, $targetId) {
    $dt = new DateTime('now', new DateTimeZone('Europe/Paris'));
    $createdAt = $dt->format('Y-m-d\TH:i:s.vP');

    return array(
        'header' => array(
            'eventId' => generateUUID(),
            'priority' => "NORMAL",
            'service' => $service ? $service : 'GRR2_netocentre',
            'channels' => $channels ? $channels : array('WEB', 'MAIL', 'PUSH'),
            'createdAt' => $createdAt
        ),
        'content' => array(
            'title' => $title,
            'message' => $message,
            'link' => $link
        ),
        'target' => array(
            'type' => 'UID', 
            'ids' => array($targetId)
        )
    );
}

// Envoie une notification de demande de modération vers le producer-api Kafka.
function envoyerNotificationModerationKafka($idReservation, $nomReservation, $createur, $targetLogin) {
    $endpoint = $_ENV['API_NOTIFICATION_URL'] ?? '';
    $apiKey   = $_ENV['API_KEY_NOTIFICATION'] ?? '';

    $title = "Demande de modération";
    $message = "Une réservation pour '$nomReservation' est à modérer.";
    $link = "/grr2/grr2_netocentre";
    
    
    $event = buildServiceEventUID('GRR2_netocentre', array('WEB', 'PUSH', 'MAIL'), $title, $message, $link, $targetLogin);
    $body = json_encode($event);

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($body),
        'x-api-key: ' . $apiKey
    ));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
    curl_exec($ch);
    curl_close($ch);
}

// Envoie une notification de rappel de réservation vers le producer-api Kafka 30 minutes avant le début de la réservation.
function envoyerNotificationRappelKafka($idReservation, $nomReservation, $beneficiaire, $start_time) {
    $endpoint = $_ENV['API_NOTIFICATION_URL'] ?? '';
    $apiKey   = $_ENV['API_KEY_NOTIFICATION'] ?? '';

    $heureReelle = date('H:i', $start_time);

    $title = "Rappel de réservation";
    $message = "Votre réservation pour '$nomReservation' commencera à $heureReelle.";
    $link = "/grr2/grr2_netocentre";
    
    
    $event = buildServiceEventUID('GRR2_netocentre', array('WEB', 'PUSH', 'MAIL'), $title, $message, $link, $beneficiaire);
    $body = json_encode($event);

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($body),
        'x-api-key: ' . $apiKey
    ));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("[reservation_reminder] Erreur cURL pour la réservation $idReservation : " . curl_error($ch));
    }
    
    curl_close($ch);
}

?>
