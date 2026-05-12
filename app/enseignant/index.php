<?php
// 1. Initialisation et Sécurité
session_start();

// On définit le CSS et le titre avant d'inclure le header
$custom_css = "../assets/css/enseignant/index.css"; 
$page_title = "Tableau de Bord - Enseignant";

// Inclusion du header (qui gère déjà la connexion $conn et la session)
include '../includes/header.php'; 

// Vérification du rôle (Sécurité)
if (!isset($_SESSION['role']) || (strtolower($_SESSION['role']) !== 'enseignant' && strtolower($_SESSION['role']) !== 'vacataire')) {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Récupération des signalements RÉCENTS avec les informations de la salle
$sql_recents = "SELECT p.*, e.num_inventaire, s.nom as nom_salle 
                FROM panne p
                JOIN equipement e ON p.equipement_id = e.id
                JOIN salle s ON e.salle_id = s.id
                JOIN intervention i ON i.panne_id = p.id
                WHERE i.auteur_id = ? AND i.type_action = 'signalement'
                ORDER BY p.date_signalement DESC 
                LIMIT 5";

$stmt = $conn->prepare($sql_recents);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_recents = $stmt->get_result();
?>

<main class="page-content">
  <div class="welcome-section mb-4">
    <h2 class="fw-bold text-navy">Bonjour, <?php echo htmlspecialchars($user_name); ?></h2>
    <p class="text-muted">Bienvenue sur LabManager — Espace Enseignant</p>
  </div>

  <div class="row g-4 mb-5">
    <div class="col-md-6">
      <a href="signaler_panne.php" class="action-card action-report">
        <div class="action-icon icon-danger">
          <i class="bi bi-exclamation-triangle"></i>
        </div>
        <div class="action-details">
          <h4>Signaler une panne</h4>
          <p>Un problème avec un PC ou un projecteur ? Informez les techniciens.</p>
        </div>
      </a>
    </div>
    <div class="col-md-6">
      <a href="mes_signalements.php" class="action-card action-status">
        <div class="action-icon icon-primary">
          <i class="bi bi-search"></i>
        </div>
        <div class="action-details">
          <h4>Suivre mes signalements</h4>
          <p>Consultez l'avancement des réparations de vos tickets.</p>
        </div>
      </a>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
      <h5 class="mb-0 fw-bold text-navy"><i class="bi bi-clock-history me-2"></i>Mes signalements récents</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-4">ID</th>
              <th>Équipement</th>
              <th>Salle</th> <th>Date</th>
              <th>Gravité</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result_recents->num_rows > 0): ?>
                <?php while($row = $result_recents->fetch_assoc()): ?>
                <tr>
                  <td class="ps-4 text-muted">#<?php echo $row['id']; ?></td>
                  <td>
                      <span class="fw-bold"><?php echo htmlspecialchars($row['num_inventaire']); ?></span>
                  </td>
                  <td>
                      <span class="badge bg-light text-navy border">
                        <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($row['nom_salle']); ?>
                      </span>
                  </td>
                  <td><?php echo date('d/m/Y', strtotime($row['date_signalement'])); ?></td>
                  <td>
                    <span class="badge badge-<?php echo strtolower($row['gravite']); ?>">
                        <?php echo ucfirst($row['gravite']); ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge badge-<?php echo str_replace(' ', '-', strtolower($row['statut'])); ?>">
                        <?php echo ucfirst($row['statut']); ?>
                    </span>
                  </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Aucun signalement effectué pour le moment.</td>
                </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?php 
echo "</div>"; 
include '../includes/footer.php'; 
?>