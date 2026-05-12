<?php
session_start();

// 1. Sécurité
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
$user_id = $_SESSION['user_id']; 
$message = "";

// 2. GESTION DES ACTIONS (Mise à jour Panne + Equipement + Intervention)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $equipement_id = (int)$_POST['equipement_id'];
    $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : "";
    
    $sql_panne = "";
    $sql_equip = "";
    $type_action = ""; 
    $desc_intervention = "";

    switch ($action) {
        case 'prendre_en_charge':
            $sql_panne = "UPDATE panne SET statut = 'pris en charge' WHERE id = ?";
            $sql_equip = "UPDATE equipement SET etat = 'en réparation' WHERE id = ?";
            $type_action = "pris en charge"; 
            $desc_intervention = "Le technicien a débuté l'intervention sur cet équipement.";
            break;
            
        case 'attente_piece':
            $sql_panne = "UPDATE panne SET statut = 'en attente de piece' WHERE id = ?";
            $sql_equip = "UPDATE equipement SET etat = 'en attente de piece' WHERE id = ?";
            $type_action = "en attente de piece";
            $desc_intervention = "Intervention suspendue. Note : " . $commentaire;
            break;
            
        case 'resoudre':
            // Correction : On ne passe plus le commentaire ici, mais en intervention
            $sql_panne = "UPDATE panne SET statut = 'résolu', date_resolution = NOW() WHERE id = ?";
            $sql_equip = "UPDATE equipement SET etat = 'fonctionnel' WHERE id = ?";
            $type_action = "resolution";
            $desc_intervention = "Panne résolue. Note technique : " . $commentaire;
            break;
            
        case 'reformer':
            $sql_panne = "UPDATE panne SET statut = 'réformé', date_resolution = NOW() WHERE id = ?";
            $sql_equip = "UPDATE equipement SET etat = 'réformé' WHERE id = ?";
            $type_action = "reforme";
            $desc_intervention = "Équipement réformé. Motif : " . $commentaire;
            break;
    }

    if ($type_action !== "") {
        $conn->begin_transaction();
        try {
            // A. Update Panne (1 seul paramètre 'i')
            $stmt_p = $conn->prepare($sql_panne);
            $stmt_p->bind_param("i", $ticket_id);
            $stmt_p->execute();

            // B. Update Equipement (1 seul paramètre 'i')
            $stmt_e = $conn->prepare($sql_equip);
            $stmt_e->bind_param("i", $equipement_id);
            $stmt_e->execute();

            // C. Trace dans la table Intervention (Sauvegarde du commentaire)
            $sql_inter = "INSERT INTO intervention (panne_id, auteur_id, type_action, description_action, date_intervention) 
                          VALUES (?, ?, ?, ?, NOW())";
            $stmt_i = $conn->prepare($sql_inter);
            $stmt_i->bind_param("iiss", $ticket_id, $user_id, $type_action, $desc_intervention);
            $stmt_i->execute();

            $conn->commit();
            $message = "<div class='alert alert-success shadow-sm'>L'action a été enregistrée avec succès.</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='alert alert-danger shadow-sm'>Erreur : " . $e->getMessage() . "</div>";
        }
    }
}

// 3. RÉCUPÉRATION DES DONNÉES
$sql = "SELECT p.*, 
               e.id as eid, e.type as type_objet, e.num_inventaire, e.etat as etat_objet, e.marque, e.modele,
               s.nom as nom_salle, 
               u.nom as nom_prof, u.prenom as prenom_prof,
               c.cpu, c.ram, c.stockage, c.systeme_exploitation
        FROM panne p
        JOIN equipement e ON p.equipement_id = e.id
        JOIN salle s ON e.salle_id = s.id
        LEFT JOIN intervention i_sig ON i_sig.panne_id = p.id AND i_sig.type_action = 'signalement'
        LEFT JOIN utilisateur u ON i_sig.auteur_id = u.id
        LEFT JOIN configuration_pc c ON e.id = c.equipement_id
        WHERE p.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) { die("Ticket introuvable."); }

$grav_class = match($data['gravite']) {
    'critique' => 'danger',
    'majeure' => 'warning',
    default => 'info'
};

include '../includes/header.php'; 
?>

<main class="page-content">
    <?= $message ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-navy mb-0"><?= htmlspecialchars($data['num_inventaire']) ?></h2>
            <p class="text-muted small"><?= htmlspecialchars($data['marque'] . ' ' . $data['modele']) ?> — <?= htmlspecialchars($data['nom_salle']) ?></p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle d-flex align-items-center px-3">
                <?= ucfirst($data['etat_objet']) ?>
            </span>
            <a href="modifier_equipement.php?id=<?= $data['eid'] ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i> Modifier</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold text-navy mb-3">Informations Équipement</h6>
                    <div class="info-list">
                        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted small">Type</span><span class="fw-bold small"><?= ucfirst($data['type_objet']) ?></span></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted small">Marque</span><span class="fw-bold small"><?= htmlspecialchars($data['marque']) ?></span></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted small">Modèle</span><span class="fw-bold small"><?= htmlspecialchars($data['modele']) ?></span></div>
                        <div class="d-flex justify-content-between py-2"><span class="text-muted small">Salle</span><span class="fw-bold small"><?= htmlspecialchars($data['nom_salle']) ?></span></div>
                    </div>
                </div>
            </div>

            <?php if (strtolower($data['type_objet']) === 'pc'): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold text-navy mb-3 d-flex align-items-center"><i class="bi bi-cpu me-2"></i> Configuration</h6>
                    <div class="info-list small">
                        <div class="d-flex justify-content-between py-2 border-bottom"><span>CPU</span><span class="fw-bold"><?= htmlspecialchars($data['cpu'] ?? 'N/A') ?></span></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span>RAM</span><span class="fw-bold"><?= htmlspecialchars($data['ram'] ?? 'N/A') ?></span></div>
                        <div class="d-flex justify-content-between py-2"><span>OS</span><span class="fw-bold"><?= htmlspecialchars($data['systeme_exploitation'] ?? 'N/A') ?></span></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-8">
            <div class="card border-<?= $grav_class ?> shadow-sm ticket-active-card">
                <div class="card-header bg-<?= $grav_class ?>-subtle d-flex justify-content-between align-items-center py-3">
                    <span class="text-<?= $grav_class ?> fw-bold">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Ticket PAN-<?= str_pad($data['id'], 3, '0', STR_PAD_LEFT) ?>
                    </span>
                    <span class="badge bg-<?= $grav_class ?>"><?= ucfirst($data['statut']) ?></span>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-4"><label class="text-muted small d-block mb-1">Signalé le</label><span class="fw-bold"><?= date('d/m/Y', strtotime($data['date_signalement'])) ?></span></div>
                        <div class="col-md-4"><label class="text-muted small d-block mb-1">Par</label><span class="fw-bold"><?= htmlspecialchars($data['nom_prof'] . ' ' . $data['prenom_prof']) ?></span></div>
                        <div class="col-md-4 text-md-end"><label class="text-muted small d-block mb-1">Gravité</label><span class="badge bg-<?= $grav_class ?>-subtle text-<?= $grav_class ?> px-3"><?= ucfirst($data['gravite']) ?></span></div>
                    </div>

                    <div class="mb-4">
                        <label class="text-muted small d-block mb-2">Description du problème</label>
                        <div class="p-3 bg-light rounded-3 text-dark italic-desc">"<?= htmlspecialchars($data['description']) ?>"</div>
                    </div>

                    <form action="" method="POST">
                        <input type="hidden" name="equipement_id" value="<?= $data['eid'] ?>">
                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($data['statut'] == 'ouvert'): ?>
                                <button type="submit" name="action" value="prendre_en_charge" class="btn btn-navy px-4">
                                    <i class="bi bi-hand-thumbs-up me-2"></i>Prendre en charge
                                </button>

                            <?php elseif ($data['statut'] == 'pris en charge' || $data['statut'] == 'en attente de piece'): ?>
                                <?php if ($data['statut'] == 'pris en charge'): ?>
                                    <button type="submit" name="action" value="attente_piece" class="btn btn-outline-secondary">
                                        <i class="bi bi-hourglass-split me-2"></i>En attente de pièce
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-outline-success" data-bs-toggle="collapse" data-bs-target="#clotureZone">
                                    <i class="bi bi-check-lg me-2"></i>Résolu
                                </button>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#clotureZone">
                                    <i class="bi bi-trash me-2"></i>Réformer
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="collapse mt-4" id="clotureZone">
                            <div class="card card-body bg-light border-0 shadow-sm">
                                <label class="form-label fw-bold small">Rapport d'intervention (obligatoire)</label>
                                <textarea name="commentaire" class="form-control mb-3" rows="3" placeholder="Décrivez ce qui a été fait..."></textarea>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="action" value="resoudre" class="btn btn-success btn-sm px-3">Confirmer Résolution</button>
                                    <button type="submit" name="action" value="reformer" class="btn btn-danger btn-sm px-3">Confirmer Réforme</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>