<?php
require_once '../db_config.php';

$salle_id = isset($_GET['salle_id']) ? intval($_GET['salle_id']) : 0;

if ($salle_id > 0) {
    // On ne récupère que les équipements qui ne sont pas réformés
    $sql = "SELECT id, num_inventaire, type FROM equipement WHERE salle_id = ? AND etat != 'reforme'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $salle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $equipements = [];
    while ($row = $result->fetch_assoc()) {
        $equipements[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($equipements);
}