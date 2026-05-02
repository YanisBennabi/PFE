<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../db_config.php';

// 1. Récupération de l'ID de l'équipement depuis l'URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: tickets.php");
    exit();
}

$eq_id = (int)$_GET['id'];

// 2. Traitement de la soumission du formulaire[cite: 11]
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marque = $_POST['marque'] ?? '';
    $modele = $_POST['modele'] ?? '';
    $type = $_POST['type'] ?? '';
    $serie = $_POST['serie'] ?? '';
    $date_acq = $_POST['date_acq'] ?? null;
    $salle_id = (int)$_POST['salle_id'];
    $poste = $_POST['poste'] ?? '';
    $etat = $_POST['etat'] ?? 'fonctionnel';

    // Mise à jour de la table equipement[cite: 11]
    $sql_update = "UPDATE equipement SET 
                    marque = ?, modele = ?, type = ?, num_serie = ?, 
                    date_acquisition = ?, salle_id = ?, poste = ?, etat = ? 
                   WHERE id = ?";
    
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("sssssiisi", $marque, $modele, $type, $serie, $date_acq, $salle_id, $poste, $etat, $eq_id);
    
    if ($stmt->execute()) {
        // Si c'est un PC, on gère la table configuration_pc[cite: 11]
        if ($type === 'PC') {
            $sql_config = "INSERT INTO configuration_pc (equipement_id, carte_mere, cpu, gpu, ram, stockage, alimentation, systeme_exploitation) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE 
                           carte_mere=?, cpu=?, gpu=?, ram=?, stockage=?, alimentation=?, systeme_exploitation=?";
            
            $stmt_pc = $conn->prepare($sql_config);
            $stmt_pc->bind_param("issssssssssssss", 
                $eq_id, $_POST['carte_mere'], $_POST['cpu'], $_POST['gpu'], $_POST['ram'], $_POST['stockage'], $_POST['alimentation'], $_POST['os'],
                $_POST['carte_mere'], $_POST['cpu'], $_POST['gpu'], $_POST['ram'], $_POST['stockage'], $_POST['alimentation'], $_POST['os']
            );
            $stmt_pc->execute();
        }
        header("Location: tickets.php?success=1");
        exit();
    }
}

// 3. Récupération des données pour pré-remplir le formulaire[cite: 11, 12]
$sql_fetch = "SELECT e.*, c.carte_mere, c.cpu, c.gpu, c.ram, c.stockage, c.alimentation, c.systeme_exploitation 
              FROM equipement e 
              LEFT JOIN configuration_pc c ON e.id = c.equipement_id 
              WHERE e.id = ?";
$stmt_fetch = $conn->prepare($sql_fetch);
$stmt_fetch->bind_param("i", $eq_id);
$stmt_fetch->execute();
$data = $stmt_fetch->get_result()->fetch_assoc();

if (!$data) die("Équipement introuvable.");

$custom_css = "../assets/css/technicien/modifier_equipement.css";
include '../includes/header.php'; 
?>

<link rel="stylesheet" href="modifierequipement.css">

<main class="page-content">
    <form method="POST">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-navy mb-1">Modifier l'équipement</h2>
                <p class="text-muted small"><?= htmlspecialchars($data['num_inventaire']) ?> — <?= htmlspecialchars($data['marque'] . ' ' . $data['modele']) ?></p>
            </div>
            <a href="actions/reformer.php?id=<?= $eq_id ?>" class="btn btn-outline-danger btn-sm px-3">
                <i class="bi bi-archive me-2"></i>Réformer cet équipement
            </a>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Informations Générales[cite: 10] -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-navy mb-4 border-bottom pb-2">Informations générales</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">N° d'inventaire</label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data['num_inventaire']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Type d'équipement</label>
                                <select class="form-select" name="type" id="typeSelect">
                                    <?php 
                                    $types = ['PC','switch','serveur','ecran','clavier','souris','onduleur','projecteur'];
                                    foreach($types as $t) {
                                        $selected = ($data['type'] == $t) ? 'selected' : '';
                                        echo "<option value='$t' $selected>".ucfirst($t)."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Marque</label>
                                <input type="text" class="form-control" name="marque" value="<?= htmlspecialchars($data['marque']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Modèle</label>
                                <input type="text" class="form-control" name="modele" value="<?= htmlspecialchars($data['modele']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Numéro de série</label>
                                <input type="text" class="form-control" name="serie" value="<?= htmlspecialchars($data['num_serie']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Date d'acquisition</label>
                                <input type="date" class="form-control" name="date_acq" value="<?= $data['date_acquisition'] ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Localisation[cite: 10, 11] -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-navy mb-4 border-bottom pb-2">Localisation</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Laboratoire</label>
                                <select class="form-select" name="salle_id">
                                    <?php 
                                    $salles = $conn->query("SELECT id, nom FROM salle");
                                    while($s = $salles->fetch_assoc()) {
                                        $selected = ($data['salle_id'] == $s['id']) ? 'selected' : '';
                                        echo "<option value='".$s['id']."' $selected>".htmlspecialchars($s['nom'])."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Numéro de poste</label>
                                <input type="text" class="form-control" name="poste" value="<?= htmlspecialchars($data['poste']) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuration PC (Dynamique)[cite: 10, 11] -->
                <div class="card border-0 shadow-sm" id="configPCSection">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-navy mb-4 border-bottom pb-2"><i class="bi bi-cpu me-2"></i>Configuration PC</h6>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label small fw-bold">Carte mère</label><input type="text" class="form-control" name="carte_mere" value="<?= htmlspecialchars($data['carte_mere'] ?? '') ?>"></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">CPU</label><input type="text" class="form-control" name="cpu" value="<?= htmlspecialchars($data['cpu'] ?? '') ?>"></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">GPU</label><input type="text" class="form-control" name="gpu" value="<?= htmlspecialchars($data['gpu'] ?? '') ?>"></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">RAM</label><input type="text" class="form-control" name="ram" value="<?= htmlspecialchars($data['ram'] ?? '') ?>"></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Stockage</label><input type="text" class="form-control" name="stockage" value="<?= htmlspecialchars($data['stockage'] ?? '') ?>"></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Alimentation</label><input type="text" class="form-control" name="alimentation" value="<?= htmlspecialchars($data['alimentation'] ?? '') ?>"></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">OS</label><input type="text" class="form-control" name="os" value="<?= htmlspecialchars($data['systeme_exploitation'] ?? '') ?>"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne DROITE : État[cite: 10] -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-navy mb-3">État</h6>
                        <select class="form-select border-primary-subtle" name="etat">
                            <?php 
                            $etats = ['fonctionnel', 'en panne', 'en reparation', 'en attente de piece', 'reforme'];
                            foreach($etats as $e) {
                                $selected = ($data['etat'] == $e) ? 'selected' : '';
                                echo "<option value='$e' $selected>".ucfirst($e)."</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="sticky-top" style="top: 20px;">
                    <button type="submit" class="btn btn-navy w-100 py-3 fw-bold mb-2 shadow-sm">
                        <i class="bi bi-download me-2"></i>Enregistrer
                    </button>
                    <a href="tickets.php" class="btn btn-white border w-100 py-3 text-muted">Annuler</a>
                </div>
            </div>
        </div>
    </form>
</main>

<script>
  const typeSelect = document.getElementById('typeSelect');
  const configPCSection = document.getElementById('configPCSection');

  function checkPC() {
    configPCSection.style.display = (typeSelect.value === 'PC') ? 'block' : 'none';
  }
  
  typeSelect.addEventListener('change', checkPC);
  checkPC(); // Exécution au chargement
</script>

<?php include '../includes/footer.php'; ?>