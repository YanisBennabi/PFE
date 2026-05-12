<?php 
session_start();
require_once('../db_config.php');

// --- 1. SÉCURITÉ : Vérification du rôle ---
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'chef departement') {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";

// --- 2. RÉCUPÉRATION DE L'UTILISATEUR ---
if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    $sql = "SELECT * FROM utilisateur WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) { die("Utilisateur non trouvé."); }
} else {
    header("Location: utilisateurs.php");
    exit();
}

// --- 3. TRAITEMENT DE LA MISE À JOUR ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $statut = isset($_POST['actif']) ? 1 : 0;
    
    $new_mdp = $_POST['new_password'];
    $conf_mdp = $_POST['confirm_password'];

    $error = false;

    // Logique de mise à jour du mot de passe
    if (!empty($new_mdp)) {
        if ($new_mdp !== $conf_mdp) {
            $message = "<div class='alert alert-danger'>Les nouveaux mots de passe ne correspondent pas.</div>";
            $error = true;
        } else {
            // Hachage du mot de passe
            $hashed_password = password_hash($new_mdp, PASSWORD_DEFAULT);
            $sql_update = "UPDATE utilisateur SET nom=?, prenom=?, email=?, role=?, actif=?, mot_de_passe=? WHERE id=?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ssssisi", $nom, $prenom, $email, $role, $statut, $hashed_password, $user_id);
        }
    } else {
        // Mise à jour sans toucher au mot de passe
        $sql_update = "UPDATE utilisateur SET nom=?, prenom=?, email=?, role=?, actif=? WHERE id=?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssssii", $nom, $prenom, $email, $role, $statut, $user_id);
    }

    if (!$error) {
        if ($stmt_update->execute()) {
            $message = "<div class='alert alert-success'>Profil mis à jour avec succès !</div>";
            // Actualisation des données pour l'affichage
            $user['nom'] = $nom; $user['prenom'] = $prenom; $user['email'] = $email; $user['role'] = $role; $user['actif'] = $statut;
        } else {
            $message = "<div class='alert alert-danger'>Erreur lors de la mise à jour (Email peut-être déjà utilisé).</div>";
        }
    }
}

$page_title = "Modifier l'utilisateur";
$custom_css = "../assets/css/chef/modifier-compte.css";
include_once('../includes/header.php');
?>

<div class="page-layout">
  <main class="page-content">
    <div class="container">
      <div class="mb-4">
        <a href="utilisateurs.php" class="text-muted small text-decoration-none">
          <i class="bi bi-arrow-left"></i> Retour à la liste
        </a>
        <h2 class="fw-bold mt-2">Modifier le compte</h2>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-8">
          <?php echo $message; ?>

          <form action="" method="POST" class="content-card p-4 shadow-sm border-0 rounded-4 bg-white">
            <div class="d-flex align-items-center gap-4 mb-5 pb-4 border-bottom">
              <div class="avatar-lg bg-indigo-soft text-indigo fw-bold">
                <?php echo strtoupper(substr($user['prenom'], 0, 1)); ?>
              </div>
              <div>
                <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h5>
                <span class="badge bg-light text-dark border">ID: #<?php echo $user['id']; ?></span>
              </div>
            </div>

            <div class="row g-4">
              <div class="col-md-6">
                <label class="custom-label">Nom</label>
                <input type="text" name="nom" class="form-control custom-input" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
              </div>
              <div class="col-md-6">
                <label class="custom-label">Prénom</label>
                <input type="text" name="prenom" class="form-control custom-input" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
              </div>
              <div class="col-md-6">
                <label class="custom-label">Email</label>
                <input type="email" name="email" class="form-control custom-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
              </div>
              <div class="col-md-6">
                <label class="custom-label">Rôle</label>
                <select name="role" class="form-select custom-input" required>
                  <option value="enseignant" <?php if($user['role'] == 'enseignant') echo 'selected'; ?>>Enseignant</option>
                  <option value="technicien" <?php if($user['role'] == 'technicien') echo 'selected'; ?>>Technicien</option>
                  <option value="chef departement" <?php if($user['role'] == 'chef departement') echo 'selected'; ?>>Chef de département</option>
                </select>
              </div>

              <div class="col-12">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="statut" id="st" <?php echo ($user['actif'] == 1) ? 'checked' : ''; ?>>
                  <label class="form-check-label small fw-bold" for="st">Compte Actif</label>
                </div>
              </div>

              <div class="col-12 mt-4">
                <div class="p-3 rounded-3 bg-light border">
                  <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock me-2"></i>Sécurité du compte</h6>
                  <p class="text-muted small mb-3">Laissez ces champs vides si vous ne souhaitez pas modifier le mot de passe actuel.</p>
                  
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="custom-label">Nouveau mot de passe</label>
                      <input type="password" name="new_password" class="form-control custom-input" placeholder="••••••••">
                    </div>
                    <div class="col-md-6">
                      <label class="custom-label">Confirmer le mot de passe</label>
                      <input type="password" name="confirm_password" class="form-control custom-input" placeholder="••••••••">
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-12 mt-5 d-flex gap-3 justify-content-end">
                <a href="utilisateurs.php" class="btn btn-light border px-4">Annuler</a>
                <button type="submit" class="btn btn-teal px-5 text-white" style="background-color: #295971;">
                  Enregistrer
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