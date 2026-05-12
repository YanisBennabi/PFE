<?php
session_start();
require_once '../db_config.php';

$data = json_decode(file_get_contents('php://input'), true);
$salle_id = $data['salle_id'];

if ($salle_id > 0) {
    $conn->begin_transaction();
    try {
        // 1. On vide le planning actuel (Table correcte : emploi_du_temps)
        $conn->query("DELETE FROM emploi_du_temps WHERE salle_id = $salle_id");

        // 2. On insère les nouveaux créneaux occupés
        $stmt = $conn->prepare("INSERT INTO emploi_du_temps (salle_id, jour_semaine, heure_debut, heure_fin) VALUES (?, ?, ?, ?)");
        
        foreach ($data['planning'] as $item) {
            $h_debut = $item['heure_debut']; // Format 08:00:00
            
            // Calcul automatique de l'heure de fin (+90 minutes)
            $h_fin = date('H:i:s', strtotime($h_debut . ' +90 minutes'));
            
            $stmt->bind_param("isss", $salle_id, $item['jour'], $h_debut, $h_fin);
            $stmt->execute();
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}