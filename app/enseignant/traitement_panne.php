<?php
session_start();
require_once '../db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipement_id = $_POST['equipement_id'];
    $description = $_POST['description'];
    $gravite = $_POST['gravite'];
    $auteur_id = $_SESSION['user_id'];

    // DÉBUT DE LA TRANSACTION
    $conn->begin_transaction();

    try {
        // 1. Créer la panne
        $stmt1 = $conn->prepare("INSERT INTO panne (equipement_id, description, gravite, statut) VALUES (?, ?, ?, 'ouvert')");
        $stmt1->bind_param("iss", $equipement_id, $description, $gravite);
        $stmt1->execute();
        $panne_id = $conn->insert_id;

        // 2. Créer l'intervention de type 'signalement'
        $stmt2 = $conn->prepare("INSERT INTO intervention (panne_id, auteur_id, type_action, description_action) VALUES (?, ?, 'signalement', ?)");
        $stmt2->bind_param("iis", $panne_id, $auteur_id, $description);
        $stmt2->execute();

        // 3. Changer l'état de l'équipement
        $stmt3 = $conn->prepare("UPDATE equipement SET etat = 'en panne' WHERE id = ?");
        $stmt3->bind_param("i", $equipement_id);
        $stmt3->execute();

        // 4. Gérer l'upload de la photo
        if (isset($_FILES['photo_panne']) && $_FILES['photo_panne']['error'] === 0) {
            $folder = "../uploads/pannes/";
            if (!is_dir($folder)) mkdir($folder, 0777, true);

            $ext = pathinfo($_FILES['photo_panne']['name'], PATHINFO_EXTENSION);
            $filename = "P_" . $panne_id . "_" . time() . "." . $ext;
            $path = $folder . $filename;

            if (move_uploaded_file($_FILES['photo_panne']['tmp_name'], $path)) {
                $stmt4 = $conn->prepare("INSERT INTO photo_panne (panne_id, chemin) VALUES (?, ?)");
                $stmt4->bind_param("is", $panne_id, $path);
                $stmt4->execute();
            }
        }

        $conn->commit();
        header("Location: index.php?status=success");

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: signaler_panne.php?status=error");
    }
}