<?php
// On inclut la configuration de la base de données
require_once('../db_config.php');

$message = "";

// Vérification de la soumission du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $mdp = $_POST['password'];
    $confirm_mdp = $_POST['confirm_password'];

    // 1. Vérification de la correspondance des mots de passe
    if ($mdp !== $confirm_mdp) {
    $message = "<div class='alert alert-danger' style='font-size: 14px;'>Les mots de passe ne correspondent pas.</div>";
} else {
    // 1. Le hachage est retiré : on utilise directement la variable $mdp
    
    // 2. Préparation de l'insertion
    // Rôle est forcé à 'vacataire' et actif à 0 (Inactif)
    $role = 'vacataire';
    $actif = 0;

    $sql = "INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role, actif) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // On lie directement $mdp au lieu de $hashed_password
        $stmt->bind_param("sssssi", $nom, $prenom, $email, $mdp, $role, $actif);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success' style='font-size: 14px;'>Demande envoyée ! Votre compte est en attente d'activation par le chef.</div>";
        } else {
            $message = "<div class='alert alert-danger' style='font-size: 14px;'>Erreur : Cet email est déjà utilisé.</div>";
        }
        $stmt->close();
    }
}
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LabManager - Demande de compte vacataire</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/auth/demande.css">
</head>
<body>

<div class="request-card">
    <div class="text-center mb-4">
        <div class="app-icon">
            <i class="bi bi-person-badge fs-3"></i>
        </div>
        <h4 class="fw-bold mb-1">Demande de compte</h4>
        <p class="text-muted small">Espace réservé aux enseignants vacataires</p>
    </div>

    <?php echo $message; ?>

    <form action="" method="POST">
        <div class="row">
            <div class="col-md-12">
                <label class="form-label">Nom</label>
                <input type="text" name="nom" class="form-control" placeholder="Ex: Doe" required>
            </div>

            <div class="col-md-12">
                <label class="form-label">Prénom</label>
                <input type="text" name="prenom" class="form-control" placeholder="Ex: John" required>
            </div>
            
            <div class="col-md-12">
                <label class="form-label">Adresse email personnelle</label>
                <input type="email" name="email" class="form-control" placeholder="nom.prenom@ummto.dz" required>
            </div>

            <div class="col-md-12">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="col-md-12">
                <label class="form-label">Confirmer le mot de passe</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit" class="btn btn-request">
            <i class="bi bi-send"></i> Envoyer la demande
        </button>

        <a href="login.php" class="back-link">
            <i class="bi bi-arrow-left"></i> Retour à la page de connexion
        </a>
    </form>
</div>

</body>
</html>