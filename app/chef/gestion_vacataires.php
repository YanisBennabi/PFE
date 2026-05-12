<?php
session_start();
require_once('../db_config.php');

// --- 1. SÉCURITÉ : Vérification du rôle ---
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'chef departement') {
    header("Location: ../auth/login.php");
    exit();
}

// --- 2. CONFIGURATION DU HEADER ---
$page_title = "Gestion Vacataires - LabManager";
$custom_css = "../assets/css/chef/gestion-vac.css"; // Assure-toi que le chemin est correct

include_once('../includes/header.php'); // Appel du header comme dans dashboard.php

$message = "";

// --- 3. TRAITEMENT DES ACTIONS (Valider / Refuser) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Action Valider : Activer le compte avec une date d'expiration
    if (isset($_POST['action_valider'])) {
        $id = (int)$_POST['user_id'];
        $date_exp = $_POST['date_expiration'];
        
        $sql = "UPDATE utilisateur SET actif = 1, date_expiration = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $date_exp, $id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success mx-4 mt-3'>Compte activé avec succès jusqu'au $date_exp.</div>";
        }
    }

    // Action Refuser : Supprimer définitivement le compte
    if (isset($_POST['action_refuser'])) {
        $id = (int)$_POST['user_id'];
        $sql = "DELETE FROM utilisateur WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-warning mx-4 mt-3'>La demande d'inscription a été supprimée.</div>";
        }
    }
}

// --- 4. RÉCUPÉRATION DES DONNÉES ---
// Demandes en attente : role vacataire, actif=0, pas de date d'expiration
$sql_demandes = "SELECT * FROM utilisateur WHERE role = 'vacataire' AND actif = 0 AND date_expiration IS NULL";
$result_demandes = $conn->query($sql_demandes);

// Comptes actifs ou historiques : tous les vacataires avec une date d'expiration
$sql_actifs = "SELECT * FROM utilisateur WHERE role = 'vacataire' AND date_expiration IS NOT NULL ORDER BY date_expiration DESC";
$result_actifs = $conn->query($sql_actifs);
?>

<main class="page-content">
  <div class="container-fluid">
    <div class="mb-4">
      <h2 class="fw-bold mb-1">Comptes vacataires</h2>
      <p class="text-muted small">Validation et gestion des accès temporaires</p>
    </div>

    <?php echo $message; ?>

    <div class="alert-custom-orange mb-5">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="fw-bold small"><i class="bi bi-clock-history"></i> Demandes en attente de validation</span>
        <span class="badge bg-orange text-white"><?php echo $result_demandes->num_rows; ?> nouvelles</span>
      </div>
      
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr class="text-muted extra-small">
              <th>UTILISATEUR</th>
              <th>EMAIL</th>
              <th style="width: 250px;">DATE D'EXPIRATION</th>
              <th>ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <?php if($result_demandes->num_rows > 0): ?>
              <?php while($row = $result_demandes->fetch_assoc()): ?>
              <tr>
                <td class="fw-bold small"><?php echo htmlspecialchars($row['nom'].' '.$row['prenom']); ?></td>
                <td class="text-muted small"><?php echo htmlspecialchars($row['email']); ?></td>
                <td>
                  <form action="" method="POST" class="d-flex gap-2">
                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                    <input type="date" name="date_expiration" class="form-control form-control-sm" required>
                </td>
                <td>
                    <button type="submit" name="action_valider" class="btn btn-sm btn-success-light"><i class="bi bi-check2"></i> Valider</button>
                    <button type="submit" name="action_refuser" class="btn btn-sm btn-danger-light" onclick="return confirm('Supprimer définitivement cette demande ?')"><i class="bi bi-x-lg"></i> Refuser</button>
                  </form>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="4" class="text-center p-3 text-muted small">Aucune demande en attente.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="content-card shadow-sm p-4 bg-white rounded-4">
      <div class="card-header-custom mb-3 fw-bold"><i class="bi bi-people"></i> Historique et comptes actifs</div>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr class="text-muted extra-small">
              <th>UTILISATEUR</th>
              <th>EMAIL</th>
              <th>EXPIRE LE</th>
              <th>STATUT</th>
            </tr>
          </thead>
          <tbody>
            <?php while($user = $result_actifs->fetch_assoc()): 
                $today = date('Y-m-d');
                $is_expired = ($user['date_expiration'] < $today); // Vérification automatique de l'expiration
            ?>
            <tr>
              <td class="fw-bold small"><?php echo htmlspecialchars($user['nom'].' '.$user['prenom']); ?></td>
              <td class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></td>
              <td class="text-muted small"><?php echo date('d/m/Y', strtotime($user['date_expiration'])); ?></td>
              <td>
                <?php if ($is_expired): ?>
                  <span class="badge-status bg-secondary-soft text-muted">Expiré</span>
                <?php else: ?>
                  <span class="badge-status bg-success-soft">Actif</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?php include_once('../includes/footer.php'); ?>