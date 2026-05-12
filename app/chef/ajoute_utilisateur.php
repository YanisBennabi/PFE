<?php 
session_start();
require_once('../db_config.php'); // Ton fichier de connexion

// --- 1. SÉCURITÉ : Vérification du rôle ---
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'chef departement') {
    header("Location: ../auth/login.php");
    exit();
}

$page_title = "Ajouter un utilisateur";
$custom_css = "../assets/css/chef/ajoute_utilisateur.css";
include_once('../includes/header.php');

// --- 2. TRAITEMENT DU FORMULAIRE ---
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $mdp = $_POST['password'];
    $confirm_mdp = $_POST['confirm_password'];

    // Vérification basique
    if ($mdp !== $confirm_mdp) {
    $message = "<div class='alert alert-danger'>Les mots de passe ne correspondent pas.</div>";
    } else {
    // Le hachage a été retiré : on utilise directement la variable $mdp
    $sql = "INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role, actif) VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $conn->prepare($sql);
    
    // On lie directement $mdp au lieu de $hashed_password
    $stmt->bind_param("sssss", $nom, $prenom, $email, $mdp, $role);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Utilisateur créé avec succès !</div>";
        } else {
         $message = "<div class='alert alert-danger'>Erreur : L'email existe peut-être déjà.</div>";
        }
    }
}
?>

<div class="page-layout">
  <main class="page-content">
    <div class="container">
      <div class="mb-4">
        <a href="utilisateurs.php" class="text-muted small text-decoration-none">
          <i class="bi bi-arrow-left"></i> Retour à la gestion des utilisateurs
        </a>
        <h2 class="fw-bold mt-2">Ajouter un nouvel utilisateur</h2>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-8">
          
          <?php echo $message; ?>

          <form action="" method="POST" class="content-card p-4 shadow-sm border-0 rounded-4 bg-white">
            
            <div class="d-flex align-items-center gap-3 mb-5 pb-4 border-bottom">
              <div class="icon-box-add bg-teal-soft text-teal">
                <i class="bi bi-person-plus-fill fs-3"></i>
              </div>
              <div>
                <h5 class="mb-1 fw-bold">Informations du compte</h5>
                <p class="text-muted small mb-0">Remplissez les informations pour créer un nouvel accès au LabManager.</p>
              </div>
            </div>

            <div class="row g-4">
              <div class="col-md-6">
                <label class="form-label fw-bold small">Nom</label>
                <input type="text" class="form-control" name="nom" placeholder="Ex: Doe" required>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small">Prénom</label>
                <input type="text" class="form-control" name="prenom" placeholder="Ex: John" required>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small">Adresse email (@ummto.dz)</label>
                <input type="email" class="form-control" name="email" placeholder="nom.prenom@ummto.dz" required>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small">Attribuer un rôle</label>
                <select class="form-select" name="role" required>
                  <option value="" selected disabled>Choisir un rôle...</option>
                  <option value="technicien">Technicien</option>
                  <option value="enseignant">Enseignant</option>
                  <option value="chef departement">Chef de département</option>
                  <option value="service enseignement">Service enseignement</option>
                  <option value="vacataire">Vacataire</option>
                </select>
              </div>

              <hr class="my-4 opacity-50">

              <div class="col-md-6">
                <label class="form-label fw-bold small">Mot de passe</label>
                <input type="password" class="form-control" name="password" placeholder="••••••••" required>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small">Confirmer le mot de passe</label>
                <input type="password" class="form-control" name="confirm_password" placeholder="••••••••" required>
              </div>

              <div class="col-12 mt-5 d-flex gap-3 justify-content-end">
                <button type="reset" class="btn btn-light px-4 border">Réinitialiser</button>
                <button type="submit" class="btn btn-teal px-5 shadow-sm text-white" style="background-color: #295971;">
                  Créer le compte
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>

<?php include_once('../includes/footer.php'); ?>