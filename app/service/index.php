<?php 


session_start();

// 1. Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// 2. Vérifier le rôle (Autoriser Enseignant ET potentiellement Chef de Département)
$role_autorise = 'service enseignement';
if (strtolower($_SESSION['role']) !== $role_autorise) {
    // Redirection vers une page d'erreur ou le profil pour éviter de reconnecter l'utilisateur
    header("Location: ../index.php?error=access_denied");
    exit();

$page_title = "page service";

}


// Inclusion du header qui gère la session, la DB et la sidebar
include_once('../includes/header.php'); 
?>

<div class="container-fluid">
    <!-- Conteneur flexible pour centrer le contenu parfaitement -->
    <div class="d-flex justify-content-center align-items-center" style="min-height: 70vh;">
        <div class="text-center">
            <h1 class="display-1 fw-bold text-navy">Bonjour service enseignemnt</h1>
            <p class="lead text-muted">Bienvenue sur votre espace.</p>
        </div>
    </div>
</div>

<!-- Fermeture des balises ouvertes dans header.php -->
  </main> 
</div> 

<!-- Scripts spécifiques si besoin -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>