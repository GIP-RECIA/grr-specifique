<?php

/**
 * reservation_reminder.php
 * permet l'envoie de notifications vers les utilisateurs 30 minutes avant le début de leur réservation
 * Ce script fait partie de l'application GRR
 * */

// Vérification que le script est exécuté en ligne de commande
if (php_sapi_name() !== 'cli') {
    die("Accès refusé. Ce script doit être exécuté en ligne de commande.");
}

chdir(__DIR__);


include "include/connect.inc.php";
include "include/config.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php";
include "include/misc.inc.php";

include "include/notification_api.inc.php";


$now = time();
$minutes_before = 30;

$window_start = $now + ($minutes_before * 60);
$window_end = $now + (($minutes_before + 2) * 60);

$sql = "SELECT id, name, beneficiaire, start_time, create_by FROM " .TABLE_PREFIX . "_entry WHERE start_time > $window_start AND start_time <= $window_end";

echo "[" . date('Y-m-d H:i:s') . "] Début du cron\n";
echo "[" . date('Y-m-d H:i:s') . "] DEBUG now=$now window_start=$window_start (" . date('Y-m-d H:i:s', $window_start) . ") window_end=$window_end (" . date('Y-m-d H:i:s', $window_end) . ")\n";
echo "[" . date('Y-m-d H:i:s') . "] DEBUG SQL: $sql\n";


$res = grr_sql_query($sql);

if ($res === false) {
    echo "[" . date('Y-m-d H:i:s') . "] DEBUG ERREUR SQL : " . grr_sql_error() . "\n";
}

if ($res)
{
    for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
    {
        $idReservation = $row[0];
        $nomReservation = $row[1];
        $beneficiaire  = $row[2];
        $start_time    = $row[3];
        $create_by     = $row[4];
        
        // LOG DEBUG : contenu brut de la ligne avant filtrage
        echo "[" . date('Y-m-d H:i:s') . "] DEBUG ligne trouvée : id=$idReservation beneficiaire='$beneficiaire' start_time=$start_time (" . date('Y-m-d H:i:s', $start_time) . ") create_by=$create_by\n";

        if (!empty($beneficiaire) && preg_match('/^[fF][a-zA-Z0-9]{7}$/', $beneficiaire)) {
            envoyerNotificationRappelKafka($idReservation, $nomReservation, $beneficiaire, $start_time);
            
            echo "[" . date('Y-m-d H:i:s') . "] Notification envoyée pour la réservation : $idReservation de l'utilisateur $beneficiaire\n";
        } else {
            echo "[" . date('Y-m-d H:i:s') . "] Utilisateur invalide pour la réservation : $idReservation. Notification non envoyée.\n";
        }
    }
} else { 
    // LOG DEBUG : $res est false ou 0 lignes selon l'implémentation de grr_sql_query
    echo "[" . date('Y-m-d H:i:s') . "] DEBUG \$res est vide/false, aucune réservation à traiter.\n";
}
?>
