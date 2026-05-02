<?php
// 1. Démarrage de la session et vérification de sécurité
session_start();

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'technicien') {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

// 2. Configuration et variables de page
require_once '../db_config.php'; 
$page_title = "Inventaire des Équipements - LabManager";
$custom_css = "../assets/css/technicien/equipements.css"; 

// ---------------------------------------------------------------
// CONSTRUCTION DE LA REQUÊTE AVEC FILTRES
// ---------------------------------------------------------------
$conditions  = [];
$bind_params = [];
$bind_types  = '';

if (!empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $conditions[] = "(e.num_inventaire LIKE ? OR e.modele LIKE ?)";
    $bind_params[] = $search;
    $bind_params[] = $search;
    $bind_types   .= 'ss';
}

if (!empty($_GET['salle'])) {
    $conditions[] = "e.salle_id = ?";
    $bind_params[] = (int) $_GET['salle'];
    $bind_types   .= 'i';
}

if (!empty($_GET['type'])) {
    $conditions[] = "e.type = ?";
    $bind_params[] = $_GET['type'];
    $bind_types   .= 's';
}

$sql = "SELECT e.*, s.nom AS nom_salle
        FROM equipement e
        JOIN salle s ON e.salle_id = s.id";

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY e.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($bind_params)) {
    $stmt->bind_param($bind_types, ...$bind_params);
}
$stmt->execute();
$query_result = $stmt->get_result();

// ---------------------------------------------------------------
// STOCKER TOUS LES RÉSULTATS EN MÉMOIRE avant tout autre query
// (évite que la requête des salles du formulaire écrase $result)
// ---------------------------------------------------------------
$equipements = [];
while ($row = $query_result->fetch_assoc()) {
    $equipements[] = $row;
}
$total = count($equipements);

// Récupérer les salles pour le select du filtre
$salles_result = $conn->query("SELECT id, nom FROM salle ORDER BY nom");
$salles_list = [];
while ($s = $salles_result->fetch_assoc()) {
    $salles_list[] = $s;
}

// 3. Appel du Header
include '../includes/header.php'; 
?>

<main class="page-content">
  <div class="container-fluid">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold mb-1" style="color: #1a3a4a;">Inventaire des Équipements</h2>
        <p class="text-muted small">Consultez et gérez l'ensemble du matériel des laboratoires</p>
      </div>
      <a href="ajouter_equipement.php" class="btn btn-primary d-flex align-items-center gap-2" style="background-color: #295971; border: none;">
        <i class="bi bi-plus-lg"></i> Ajouter
      </a>
    </div>

    <!-- Barre de Recherche et Filtres -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-3">
        <form action="" method="GET" class="row g-3">

          <div class="col-lg-4 col-md-6">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0 text-muted">
                <i class="bi bi-search"></i>
              </span>
              <input
                type="text"
                name="search"
                class="form-control border-start-0 ps-0"
                placeholder="Rechercher par n° d'inventaire ou modèle..."
                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
          </div>
          
          <div class="col-lg-3 col-md-6">
            <select name="salle" class="form-select">
              <option value="">Tous les laboratoires</option>
              <?php foreach ($salles_list as $s): ?>
                <option value="<?= $s['id'] ?>" <?= (isset($_GET['salle']) && $_GET['salle'] == $s['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($s['nom']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-lg-3 col-md-6">
            <select name="type" class="form-select">
              <option value="">Tous les types</option>
              <?php
              // Valeurs exactes de l'ENUM dans la BDD
              $types_enum = ['PC', 'switch', 'serveur', 'ecran', 'clavier', 'souris', 'onduleur', 'projecteur'];
              foreach ($types_enum as $t): ?>
                <option value="<?= $t ?>" <?= (isset($_GET['type']) && $_GET['type'] === $t) ? 'selected' : '' ?>>
                  <?= ucfirst($t) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-lg-2 col-md-6 d-flex gap-2">
            <button type="submit" class="btn btn-outline-secondary w-100">
              <i class="bi bi-filter"></i> Filtrer
            </button>
            <?php if (!empty($_GET['search']) || !empty($_GET['salle']) || !empty($_GET['type'])): ?>
              <a href="equipements.php" class="btn btn-outline-danger" title="Réinitialiser">
                <i class="bi bi-x-lg"></i>
              </a>
            <?php endif; ?>
          </div>

        </form>
      </div>
    </div>

    <!-- Compteur -->
    <p class="text-muted small mb-3">
      <?= $total ?> équipement<?= $total > 1 ? 's' : '' ?> trouvé<?= $total > 1 ? 's' : '' ?>
    </p>

    <!-- Grille d'équipements -->
    <div class="row g-4">

      <?php if ($total > 0): ?>
        <?php foreach ($equipements as $row): ?>

          <?php
          // Icône selon le type
          switch (strtolower($row['type'])) {
              case 'pc':         $icon = "bi-pc-display";       break;
              case 'projecteur': $icon = "bi-projector";        break;
              case 'switch':     $icon = "bi-hdd-network";      break;
              case 'ecran':      $icon = "bi-display";          break;
              case 'serveur':    $icon = "bi-server";           break;
              case 'onduleur':   $icon = "bi-lightning-charge"; break;
              case 'souris':     $icon = "bi-mouse";            break;
              case 'clavier':    $icon = "bi-keyboard";         break;
              default:           $icon = "bi-device-ssd";       break;
          }

          // Badge état
          switch (strtolower($row['etat'])) {
              case 'fonctionnel':
                  $badge_class = "bg-success-subtle text-success border-success-subtle"; break;
              case 'en panne':
                  $badge_class = "bg-danger-subtle text-danger border-danger-subtle"; break;
              case 'en reparation':
              case 'en attente de piece':
                  $badge_class = "bg-warning-subtle text-warning border-warning-subtle"; break;
              default:
                  $badge_class = "bg-secondary-subtle text-secondary border-secondary-subtle"; break;
          }
          ?>

          <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm item-card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <span class="badge <?= $badge_class ?> border rounded-pill">
                    <?= htmlspecialchars(ucfirst($row['etat'])) ?>
                  </span>
                </div>
                <div class="text-center mb-3">
                  <i class="bi <?= $icon ?>" style="font-size: 2.5rem; color: #295971;"></i>
                </div>
                <h6 class="fw-bold mb-1"><?= htmlspecialchars($row['num_inventaire']) ?></h6>
                <p class="text-muted small mb-0"><?= htmlspecialchars($row['marque'] . ' ' . $row['modele']) ?></p>
                <p class="text-primary small mb-3">
                  <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($row['nom_salle']) ?>
                </p>
                <hr class="opacity-25">
              </div>
              <div class="card-footer bg-white border-0 p-3 pt-0">
                <a href="fiche_equipement.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary w-100">Détails</a>
              </div>
            </div>
          </div>

        <?php endforeach; ?>

      <?php else: ?>
        <div class="col-12 text-center py-5">
          <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
          <p class="text-muted mt-2">Aucun équipement trouvé avec ces critères.</p>
        </div>
      <?php endif; ?>

    </div><!-- fin row -->
  </div><!-- fin container-fluid -->
</main>

<?php include '../includes/footer.php'; ?>