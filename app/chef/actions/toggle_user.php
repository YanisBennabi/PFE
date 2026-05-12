<?php
session_start();
require_once '../../db_config.php'; // Ajuste le chemin selon ta structure

// Vérification de sécurité (seul le chef peut faire ça)
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'chef departement') {
    exit("Accès refusé");
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $user_id = (int)$_GET['id'];
    $action = $_GET['action'];

    // Déterminer la nouvelle valeur du statut
    // 1 pour activer, 0 pour désactiver
    $nouveau_statut = ($action === 'activer') ? 1 : 0;

    // Mise à jour en base de données
    $sql = "UPDATE utilisateur SET actif = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $nouveau_statut, $user_id);

    if ($stmt->execute()) {
        // Redirection vers la page précédente avec un message de succès
        header("Location: ../utilisateurs.php?msg=success");
    } else {
        header("Location: ../utilisateurs.php?msg=error");
    }
    exit();
} else {
    header("Location: ../utilisateurs.php");
    exit();
}