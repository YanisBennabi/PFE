<?php 
// 1. Initialisation et Sécurité
session_start();

// 2. Configuration des variables pour le header.php
$page_title = "Accueil - Service Enseignement";
$custom_css = "../assets/css/serv/index.css"; // Chemin vers votre fichier CSS

// Inclusion du header (qui gère déjà la connexion $conn et la session)
include '../includes/header.php'; 


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

?>

<div class="container-fluid">
    <div class="mb-4">
        <h2 class="fw-bold text-navy mb-0">Accueil</h2>
        <p class="text-muted small">Service enseignement — Département Informatique</p>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-5">
            <a href="emplois_temps.php" class="text-decoration-none">
                <div class="quick-link-card shadow-sm border-0 card h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="quick-icon-box bg-light-blue text-primary me-4">
                            <i class="bi bi-calendar3 fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-navy mb-1">Emplois du temps</h5>
                            <p class="text-muted small mb-0">Gérer les créneaux des laboratoires</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-5">
            <a href="laboratoires.php" class="text-decoration-none">
                <div class="quick-link-card shadow-sm border-0 card h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="quick-icon-box bg-light-green text-success me-4">
                            <i class="bi bi-building fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-navy mb-1">Laboratoires</h5>
                            <p class="text-muted small mb-0">Vue d'ensemble des salles</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

</main> </div> <?php 
// 5. Inclusion du footer
include_once('../includes/footer.php'); 
?>