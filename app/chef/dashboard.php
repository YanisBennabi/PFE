<?php 
session_start();

// 1. Vérification de sécurité
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'chef departement') {
    header("Location: ../auth/login.php");
    exit();
}

$page_title = "Tableau de Bord - Chef de Département";
$custom_css = "../assets/css/chef/dashboard.css";

include_once('../includes/header.php'); 

// --- RÉCUPÉRATION DES DONNÉES ---

// 1. Statistiques du haut
$total_equipements = $conn->query("SELECT COUNT(*) as nb FROM equipement WHERE etat != 'réformé'")->fetch_assoc()['nb'] ?? 0;
$total_pannes = $conn->query("SELECT COUNT(*) as nb FROM panne WHERE statut != 'résolu'")->fetch_assoc()['nb'] ?? 0;
$total_critiques = $conn->query("SELECT COUNT(*) as nb FROM panne WHERE gravite = 'critique' AND statut != 'résolu'")->fetch_assoc()['nb'] ?? 0;

$resDispo = $conn->query("SELECT 
    (SELECT COUNT(*) FROM equipement WHERE etat = 'fonctionnel') / 
    (SELECT COUNT(*) FROM equipement WHERE etat != 'reforme') * 100");
$tauxDispo = round($resDispo->fetch_row()[0] ?? 0, 1);

// 2. Données du tableau (Disponibilité par laboratoire)
// On calcule le nombre d'équipements dont l'état n'est pas 'réformé' (Total) 
// et ceux qui n'ont pas de panne critique en cours (Fonctionnels)
$salles_query = $conn->query("
    SELECT s.nom, 
           (SELECT COUNT(*) FROM equipement e WHERE e.salle_id = s.id AND e.etat != 'réformé') as total,
           (SELECT COUNT(*) FROM equipement e 
            WHERE e.salle_id = s.id AND e.etat != 'réformé' 
            AND e.id NOT IN (SELECT equipement_id FROM panne WHERE gravite = 'critique' AND statut != 'résolu')
           ) as fonctionnels
    FROM salle s 
    ORDER BY s.nom ASC
");

// 3. Pannes critiques récentes (avec calcul du nombre de jours)
$pannes_critiques_list = $conn->query("
    SELECT p.*, e.num_inventaire, DATEDIFF(NOW(), p.date_signalement) as jours
    FROM panne p 
    JOIN equipement e ON p.equipement_id = e.id 
    WHERE p.gravite = 'critique' AND p.statut != 'résolu'
    ORDER BY p.date_signalement DESC LIMIT 5
");
?>

<div class="container-fluid">
  <div class="mb-4">
    <h2 class="fw-bold mb-1">Tableau de bord</h2>
    <p class="text-muted small">État général du parc informatique — <?php echo date('d/m/Y'); ?></p>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-md-3">
      <div class="stat-card">
        <div class="stat-icon bg-light-blue text-primary">
          <i class="bi bi-pc-display"></i>
        </div>
        <div>
          <div class="text-muted small">Équipements</div>
          <div class="fw-bold h4 mb-0"><?php echo $total_equipements; ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card">
        <div class="stat-icon bg-light-yellow text-warning">
          <i class="bi bi-exclamation-triangle"></i>
        </div>
        <div>
          <div class="text-muted small">Pannes Actives</div>
          <div class="fw-bold h4 mb-0"><?php echo $total_pannes; ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card">
        <div class="stat-icon bg-light-red text-danger">
          <i class="bi bi-shield-exclamation"></i>
        </div>
        <div>
          <div class="text-muted small">Pannes Critiques</div>
          <div class="fw-bold h4 mb-0"><?php echo $total_critiques; ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
          <div class="stat-card">
            <div class="stat-icon bg-light-green text-success">
              <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div>
              <div class="stat-label">Disponibilité globale</div>
              <div class="stat-value"><?php echo $tauxDispo; ?>%</div>
            </div>
          </div>
        </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="content-card">
        <div class="card-header-custom mb-4">
          <span>Disponibilité par laboratoire</span>
          <i class="bi bi-bar-chart-line text-muted"></i>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle border-0">
            <thead>
              <tr>
                <th class="text-muted small fw-normal border-0">LABORATOIRE</th>
                <th class="text-muted small fw-normal border-0 text-center">TOTAL EQUIP.</th>
                <th class="text-muted small fw-normal border-0 text-center">FONCTIONNELS</th>
                <th class="text-muted small fw-normal border-0">TAUX DE DISPONIBILITÉ</th>
              </tr>
            </thead>
            <tbody>
              <?php while($salle = $salles_query->fetch_assoc()): 
                $taux = ($salle['total'] > 0) ? round(($salle['fonctionnels'] / $salle['total']) * 100) : 100;
                $bar_color = ($taux > 80) ? 'bg-teal' : (($taux > 50) ? 'bg-warning' : 'bg-danger');
              ?>
              <tr>
                <td class="fw-bold text-navy border-0"><?php echo htmlspecialchars($salle['nom']); ?></td>
                <td class="text-center border-0"><?php echo $salle['total']; ?></td>
                <td class="text-center border-0"><span class="text-success fw-bold"><?php echo $salle['fonctionnels']; ?></span></td>
                <td class="border-0">
                  <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height: 6px;">
                      <div class="progress-bar <?php echo $bar_color; ?>" style="width: <?php echo $taux; ?>%"></div>
                    </div>
                    <span class="small fw-bold"><?php echo $taux; ?>%</span>
                  </div>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="content-card mb-4 border-start border-danger border-1">
        <div class="card-header-custom mb-3">Pannes critiques récentes</div>
        <div class="panic-list">
            <?php if($pannes_critiques_list->num_rows > 0): ?>
                <?php while($p = $pannes_critiques_list->fetch_assoc()): ?>
                <div class="panic-item d-flex justify-content-between align-items-center p-2 border-bottom">
                    <div>
                        <div class="fw-bold small"><?php echo htmlspecialchars($p['num_inventaire']); ?></div>
                        <div class="text-muted extra-small text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($p['description']); ?></div>
                    </div>
                    <span class="badge bg-danger-soft text-danger"><?php echo $p['jours']; ?> jours</span>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-muted small text-center">Aucune panne critique active.</p>
            <?php endif; ?>
        </div>
      </div>

      <div class="content-card">
        <div class="card-header-custom mb-3">Actions rapides</div>
        <div class="d-grid gap-2">
          <a href="rapports.php" class="btn btn-teal text-white"><i class="bi bi-file-earmark-pdf me-2"></i> Générer le rapport PDF</a>
          <a href="inventaire.php" class="btn btn-outline-secondary text-start"><i class="bi bi-pc-display me-2"></i> Consulter l'inventaire</a>
          <a href="utilisateurs.php?type=vacataire" class="btn btn-outline-secondary text-start"><i class="bi bi-person-plus me-2"></i> Gérer les comptes vacataires</a>
          <a href="utilisateurs.php?action=add" class="btn btn-outline-secondary text-start"><i class="bi bi-person-plus-fill me-2"></i> Ajouter un compte permanent</a>
        </div>
      </div>
    </div>
  </div>
</div>

</main> 
</div> 

<?php include_once('../includes/footer.php'); ?>