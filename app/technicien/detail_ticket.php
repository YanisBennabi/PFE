<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../db_config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: tickets.php");
    exit();
}

$ticket_id = (int)$_GET['id'];

// REQUÊTE : On récupère tout (Panne, Équipement, Salle, Utilisateur, Config)
$sql = "SELECT p.*, e.*, s.nom as nom_salle, u.nom as nom_prof, u.prenom as prenom_prof, u.role as role_prof,
               c.cpu, c.ram, c.stockage, c.systeme_exploitation, c.carte_mere, c.gpu, c.alimentation
        FROM panne p
        JOIN equipement e ON p.equipement_id = e.id
        JOIN salle s ON e.salle_id = s.id
        LEFT JOIN intervention i ON i.panne_id = p.id AND i.type_action = 'signalement'
        LEFT JOIN utilisateur u ON i.auteur_id = u.id
        LEFT JOIN configuration_pc c ON e.id = c.equipement_id
        WHERE p.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Ticket introuvable.");
}

// Couleurs selon la gravité
$grav_class = match($data['gravite']) {
    'critique' => 'danger',
    'majeure' => 'warning',
    default => 'info'
};

$custom_css = "../assets/css/technicien/detail_ticket.css";
include '../includes/header.php'; 
?>

<main class="page-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-navy mb-0"><?= htmlspecialchars($data['num_inventaire']) ?></h2>
            <p class="text-muted small"><?= htmlspecialchars($data['marque'] . ' ' . $data['modele']) ?> — <?= htmlspecialchars($data['nom_salle']) ?>, Poste <?= htmlspecialchars($data['poste'] ?? 'N/A') ?></p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle d-flex align-items-center px-3">En panne</span>
            <a href="modifier_equipement.php?id=<?= $data['equipement_id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i> Modifier</a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Colonne GAUCHE : Infos Équipement[cite: 8] -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold text-navy mb-3">Informations</h6>
                    <div class="info-list">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted small">N° inventaire</span>
                            <span class="fw-bold small"><?= htmlspecialchars($data['num_inventaire']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted small">Type</span>
                            <span class="fw-bold small"><?= ucfirst($data['type']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted small">Marque</span>
                            <span class="fw-bold small"><?= htmlspecialchars($data['marque']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted small">Modèle</span>
                            <span class="fw-bold small"><?= htmlspecialchars($data['modele']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted small">Date d'acquisition</span>
                            <span class="fw-bold small"><?= $data['date_acquisition'] ? date('d/m/Y', strtotime($data['date_acquisition'])) : 'N/A' ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted small">Salle</span>
                            <span class="fw-bold small"><?= htmlspecialchars($data['nom_salle']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bloc Configuration PC (uniquement si type PC)[cite: 5, 8] -->
            <?php if (strtolower($data['type']) === 'pc'): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold text-navy mb-3 d-flex align-items-center"><i class="bi bi-cpu me-2"></i> Configuration PC</h6>
                    <div class="info-list">
                        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted small">CPU</span><span class="fw-bold small"><?= htmlspecialchars($data['cpu'] ?? 'N/A') ?></span></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted small">RAM</span><span class="fw-bold small"><?= htmlspecialchars($data['ram'] ?? 'N/A') ?></span></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted small">Stockage</span><span class="fw-bold small"><?= htmlspecialchars($data['stockage'] ?? 'N/A') ?></span></div>
                        <div class="d-flex justify-content-between py-2"><span class="text-muted small">OS</span><span class="fw-bold small"><?= htmlspecialchars($data['systeme_exploitation'] ?? 'N/A') ?></span></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Colonne DROITE : Détails du Ticket[cite: 7, 8] -->
        <div class="col-lg-8">
            <div class="card border-<?= $grav_class ?> shadow-sm ticket-active-card">
                <div class="card-header bg-<?= $grav_class ?>-subtle d-flex justify-content-between align-items-center py-3">
                    <span class="text-<?= $grav_class ?> fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Ticket actif — PAN-<?= str_pad($data['id'], 3, '0', STR_PAD_LEFT) ?></span>
                    <span class="text-<?= $grav_class ?> fw-bold small"><?= ucfirst($data['statut']) ?></span>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="text-muted small d-block mb-1">Signalé le</label>
                            <span class="fw-bold"><?= date('d/m/Y', strtotime($data['date_signalement'])) ?></span>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small d-block mb-1">Par</label>
                            <span class="fw-bold"><?= htmlspecialchars($data['nom_prof'] . ' ' . $data['prenom_prof']) ?> (<?= ucfirst($data['role_prof']) ?>)</span>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <label class="text-muted small d-block mb-1">Gravité</label>
                            <span class="badge bg-<?= $grav_class ?>-subtle text-<?= $grav_class ?> px-3"><?= ucfirst($data['gravite']) ?></span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="text-muted small d-block mb-2">Description</label>
                        <div class="p-3 bg-light rounded-3 text-dark italic-desc">
                            "<?= htmlspecialchars($data['description']) ?>"
                        </div>
                    </div>

                    <!-- BOUTONS D'ACTIONS DYNAMIQUES[cite: 7] -->
                    <div class="d-flex gap-2">
                        <?php if ($data['statut'] == 'ouvert'): ?>
                            <a href="actions/prendre_charge.php?id=<?= $data['id'] ?>" class="btn btn-navy px-4"><i class="bi bi-hand-thumbs-up me-2"></i>Prendre en charge</a>
                            <a href="actions/attente_piece.php?id=<?= $data['id'] ?>" class="btn btn-outline-secondary"><i class="bi bi-hourglass-split me-2"></i>En attente de pièce</a>
                            <a href="actions/resoudre.php?id=<?= $data['id'] ?>" class="btn btn-outline-success"><i class="bi bi-check-lg me-2"></i>Clôturer</a>

                        <?php elseif ($data['statut'] == 'pris en charge' || $data['statut'] == 'en attente de piece'): ?>
                            <a href="actions/resoudre.php?id=<?= $data['id'] ?>" class="btn btn-success px-4"><i class="bi bi-check-all me-2"></i>Résolu</a>
                            <a href="actions/reformer.php?id=<?= $data['id'] ?>" class="btn btn-outline-danger"><i class="bi bi-trash me-2"></i>Réformer l'équipement</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>