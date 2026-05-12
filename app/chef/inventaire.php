<?php 
session_start();

// 1. Vérification de sécurité (Chef de Département uniquement)
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'chef departement') {
    header("Location: ../auth/login.php");
    exit();
}

// Le titre de la page et le CSS spécifique
$page_title = "Inventaire du Parc - Chef de Département";
$custom_css = "../assets/css/chef/inventaire.css";

// Inclusion du header (qui contient la connexion $conn et la sidebar)
include_once('../includes/header.php'); 

// --- RÉCUPÉRATION DES DONNÉES DU PARC ---

// 1. Statistiques des badges (Haut de page)
$total_equipements = $conn->query("SELECT COUNT(*) as nb FROM equipement WHERE etat != 'réformé'")->fetch_assoc()['nb'] ?? 0;
$total_pannes = $conn->query("SELECT COUNT(*) as nb FROM equipement WHERE etat != 'fonctionel' AND etat != 'reforme'")->fetch_assoc()['nb'] ?? 0;
$total_reforme = $conn->query("SELECT COUNT(*) as nb FROM equipement WHERE etat = 'reforme'")->fetch_assoc()['nb'] ?? 0;

// 2. Liste complète des équipements avec jointure sur la salle
$sql_inventaire = "
    SELECT e.*, s.nom as nom_salle 
    FROM equipement e 
    JOIN salle s ON e.salle_id = s.id 
    ORDER BY s.nom ASC, e.num_inventaire ASC";
$result_inventaire = $conn->query($sql_inventaire);
?>
  <main class="page-content">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="fw-bold mb-1">Parc informatique</h2>
          <p class="text-muted small">Consultation de l'inventaire complet</p>
        </div>
        <button class="btn btn-teal">
          <i class="bi bi-file-earmark-pdf"></i> Rapport PDF
        </button>
      </div>

      <div class="row g-4 mb-4">
    <div class="col-md-4">
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
    <div class="col-md-4">
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
    <div class="col-md-4">
      <div class="stat-card">
        <div class="stat-icon bg-light-orange text-warning">
          <i class="bi bi-archive"></i>
        </div>
        <div>
          <div class="text-muted small">reforme</div>
          <div class="fw-bold h4 mb-0"><?php echo $total_reforme; ?></div>
        </div>
      </div>
    </div>
    
  </div>

      <div class="content-card shadow-sm border-0 card p-4 bg-white rounded">
        <div class="table-responsive">
          <table class="table align-middle border-0">
            <thead>
              <tr class="text-muted small">
                <th class="border-0">CODE INVENTAIRE</th>
                <th class="border-0">TYPE</th>
                <th class="border-0">MODÈLE / NOM</th>
                <th class="border-0">EMPLACEMENT</th>
                <th class="border-0">ÉTAT</th>
              </tr>
            </thead>
            <tbody>
              <?php if($result_inventaire && $result_inventaire->num_rows > 0): ?>
                <?php while($item = $result_inventaire->fetch_assoc()): 
                  // Gestion dynamique des classes de badge selon l'état
                  $status_class = "bg-secondary-soft text-muted"; // Par défaut
                  if($item['etat'] == 'opérationnel') $status_class = "bg-success-soft text-success";
                  if($item['etat'] == 'en panne') $status_class = "bg-danger-soft text-danger";
                  if($item['etat'] == 'en maintenance') $status_class = "bg-warning-soft text-warning";
                ?>
                <tr>
                  <td class="fw-bold text-navy border-0"><?php echo htmlspecialchars($item['num_inventaire']); ?></td>
                  <td class="border-0"><?php echo htmlspecialchars($item['type_equipement'] ?? 'PC'); ?></td>
                  <td class="border-0"><?php echo htmlspecialchars($item['nom_modele'] ?? $item['marque']); ?></td>
                  <td class="border-0"><?php echo htmlspecialchars($item['nom_salle']); ?></td>
                  <td class="border-0">
                    <span class="badge-status <?php echo $status_class; ?>">
                        <?php echo ucfirst(htmlspecialchars($item['etat'])); ?>
                    </span>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">Aucun équipement trouvé dans l'inventaire.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<?php include_once('../includes/footer.php'); ?>