<?php
session_start();
include '../db_config.php';

// Sécurité : On vérifie aussi ici si c'est bien un technicien qui envoie les données
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'technicien') {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

if (isset($_POST['valider_ajout'])) {
    $conn->begin_transaction();

    if(isset($_POST['valider_ajout'])) {
    // Ceci va arrêter le script et afficher TOUTES les données reçues.
    // Si tu ne vois rien s'afficher, c'est que le formulaire n'envoie rien vers ce fichier.
    var_dump($_POST); die(); 
}

    try {
        $salle_id = intval($_POST['id_labo']);
        $num_poste = !empty($_POST['num_poste']) ? $_POST['num_poste'] : NULL;

        $sqlEquip = "INSERT INTO equipement (num_inventaire, salle_id, poste, type, marque, modele, num_serie, date_acquisition, etat) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'fonctionnel')";
        
        $stmtEquip = $conn->prepare($sqlEquip);
        $stmtEquip->bind_param("sissssss", 
            $_POST['num_inventaire'], 
            $salle_id, 
            $num_poste, 
            $_POST['type_equipement'], 
            $_POST['marque'], 
            $_POST['modele'], 
            $_POST['num_serie'], 
            $_POST['date_acquisition']
        );

        if (!$stmtEquip->execute()) {
            throw new Exception("Erreur insertion équipement : " . $stmtEquip->error);
        }

        $equipement_id = $conn->insert_id;

        if ($_POST['type_equipement'] === 'PC') {
            $sqlConfig = "INSERT INTO configuration_pc (equipement_id, cpu, ram, stockage, gpu) 
                          VALUES (?, ?, ?, ?, ?)";
            
            $stmtConfig = $conn->prepare($sqlConfig);
            $stmtConfig->bind_param("issss", 
                $equipement_id, 
                $_POST['cpu'], 
                $_POST['ram'], 
                $_POST['stockage'], 
                $_POST['gpu']
            );
            $stmtConfig->execute();
        }

        $conn->commit();
        
        // REDIRECTION FINALE : C'est ici que la magie opère
        // On quitte cette page pour aller vers la liste. 
        // Le formulaire d'origine sera forcément vide si on y retourne.
        header("Location: equipements.php?success=1");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        // En cas d'erreur, on peut rediriger vers le formulaire avec l'erreur
        header("Location: ajouter_equipement.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si on arrive ici sans POST, on renvoie au formulaire
    header("Location: ajouter_equipement.php");
    exit();
}