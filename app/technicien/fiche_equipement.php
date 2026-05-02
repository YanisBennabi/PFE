<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../db_config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: equipements.php");
    exit();
}

$id = (int)$_GET['id'];

// REQUÊTE SQL : Jointure entre equipement, salle et configuration_pc
$sql = "SELECT e.*, s.nom AS nom_salle, 
               c.cpu, c.ram, c.stockage, c.systeme_exploitation, c.carte_mere, c.gpu, c.alimentation
        FROM equipement e 
        LEFT JOIN salle s ON e.salle_id = s.id 
        LEFT JOIN configuration_pc c ON e.id = c.equipement_id 
        WHERE e.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$eq = $stmt->get_result()->fetch_assoc();

if (!$eq) {
    die("Équipement introuvable.");
}

// Logique des icônes[cite: 4]
$icon = "bi-device-ssd";
$types_icons = [
    'pc' => 'bi-pc-display', 'projecteur' => 'bi-projector', 'switch' => 'bi-hdd-network',
    'ecran' => 'bi-display', 'serveur' => 'bi-server', 'onduleur' => 'bi-lightning-charge',
    'souris' => 'bi-mouse', 'clavier' => 'bi-keyboard'
];
if(isset($types_icons[strtolower($eq['type'])])) $icon = $types_icons[strtolower($eq['type'])];

// Badge état[cite: 4]
$badge_class = (strtolower($eq['etat']) === 'fonctionnel') ? "bg-success-subtle text-success border-success-subtle" : "bg-danger-subtle text-danger border-danger-subtle";

$custom_css = "../assets/css/technicien/fiche_equipement.css";
include '../includes/header.php'; 
?>

<main class="page-content">
    <!-- En-tête (Ariane) -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb">
            <h2 class="fw-bold text-navy mt-1">Détails du Matériel</h2>
        </nav>
        <a href="equipements.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-10">
            <!-- Carte Résumé Rapide -->
            <div class="card border-0 shadow-sm p-4 mb-4">
                <div class="d-flex align-items-center">
                    <div class="detail-icon-circle me-4" style="background: #f0f4f8; padding: 20px; border-radius: 50%;">
                        <i class="bi <?= $icon ?> text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="text-muted small d-block">Marque & Modèle</label>
                                <span class="fw-bold"><?= htmlspecialchars($eq['marque'] . ' ' . $eq['modele']) ?></span>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small d-block">Type</label>
                                <span class="fw-bold"><?= htmlspecialchars(ucfirst($eq['type'])) ?></span>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small d-block">Statut Actuel</label>
                                <span class="badge <?= $badge_class ?> border rounded-pill">
                                    <?= htmlspecialchars(ucfirst($eq['etat'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bloc Informations Techniques (Design PC mixé) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-navy mb-4 d-flex align-items-center">
                        <i class="bi bi-cpu me-2 text-primary"></i> Informations techniques
                    </h6>
                    
                    <div class="row g-4">
                        <div class="col-md-6 border-bottom pb-2 d-flex justify-content-between">
                            <span class="text-muted">N° Série</span>
                            <span class="fw-bold"><?= htmlspecialchars($eq['num_serie'] ?? 'N/A') ?></span>
                        </div>
                        <div class="col-md-6 border-bottom pb-2 d-flex justify-content-between">
                            <span class="text-muted">N° d'inventaire</span>
                            <span class="fw-bold text-primary"><?= htmlspecialchars($eq['num_inventaire']) ?></span>
                        </div>

                        <?php if (strtolower($eq['type']) === 'pc'): ?>
                            <div class="col-md-6 border-bottom pb-2 d-flex justify-content-between">
                                <span class="text-muted">Processeur (CPU)</span>
                                <span class="fw-bold"><?= htmlspecialchars($eq['cpu'] ?? 'N/A') ?></span>
                            </div>
                            <div class="col-md-6 border-bottom pb-2 d-flex justify-content-between">
                                <span class="text-muted">Mémoire RAM</span>
                                <span class="fw-bold"><?= htmlspecialchars($eq['ram'] ?? 'N/A') ?></span>
                            </div>
                            <div class="col-md-6 border-bottom pb-2 d-flex justify-content-between">
                                <span class="text-muted">Stockage</span>
                                <span class="fw-bold"><?= htmlspecialchars($eq['stockage'] ?? 'N/A') ?></span>
                            </div>
                            <div class="col-md-6 border-bottom pb-2 d-flex justify-content-between">
                                <span class="text-muted">OS</span>
                                <span class="fw-bold"><?= htmlspecialchars($eq['systeme_exploitation'] ?? 'N/A') ?></span>
                            </div>
                            <div class="col-md-6 border-bottom pb-2 d-flex justify-content-between">
                                <span class="text-muted">Carte Mère</span>
                                <span class="fw-bold"><?= htmlspecialchars($eq['carte_mere'] ?? 'N/A') ?></span>
                            </div>
                            <div class="col-md-6 border-bottom pb-2 d-flex justify-content-between">
                                <span class="text-muted">GPU</span>
                                <span class="fw-bold"><?= htmlspecialchars($eq['gpu'] ?? 'N/A') ?></span>
                            </div>
                            <div class="col-md-6 d-flex justify-content-between">
                                <span class="text-muted">Alimentation</span>
                                <span class="fw-bold"><?= htmlspecialchars($eq['alimentation'] ?? 'N/A') ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Ligne du bas : Localisation + Acquisition -->
            <div class="row g-4">
                <!-- Localisation -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-navy mb-3">
                                <i class="bi bi-geo-alt me-2 text-primary"></i> Localisation
                            </h6>
                            <div class="d-flex align-items-center gap-4 mt-3">
                                <div class="bg-light rounded-3 p-3 text-center" style="min-width: 120px;">
                                    <h1 class="display-5 fw-bold text-navy mb-0">
                                        <?= htmlspecialchars(explode(' ', $eq['poste'])[1] ?? $eq['poste']) ?>
                                    </h1>
                                    <small class="text-muted">Poste</small>
                                </div>
                                <div>
                                    <p class="mb-1 text-muted">Laboratoire : <strong class="text-dark"><?= htmlspecialchars($eq['nom_salle']) ?></strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acquisition -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-navy mb-3">
                                <i class="bi bi-info-circle me-2 text-primary"></i> Informations d'acquisition
                            </h6>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <label class="text-muted small d-block">Date d'achat</label>
                                    <span class="fw-bold">
                                        <?= $eq['date_acquisition'] ? date('d F Y', strtotime($eq['date_acquisition'])) : 'Non renseignée' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>