<?php
// 1. Initialisation et Sécurité
session_start();

// On définit le CSS et le titre avant d'inclure le header
$custom_css = "../assets/css/enseignant/mes_signalements.css"; 
$page_title = "Mes Signalements";

// Inclusion du header (qui gère déjà la connexion $conn et la session)
include '../includes/header.php'; 

// Vérification du rôle
if (!isset($_SESSION['role']) || (strtolower($_SESSION['role']) !== 'enseignant' && strtolower($_SESSION['role']) !== 'vacataire')) {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Récupération de TOUS les signalements de cet enseignant
// On utilise une jointure pour avoir les infos de l'équipement et de la salle
$sql = "SELECT p.*, e.num_inventaire, e.type as eq_type, s.nom as nom_salle 
        FROM panne p
        JOIN equipement e ON p.equipement_id = e.id
        JOIN salle s ON e.salle_id = s.id
        JOIN intervention i ON i.panne_id = p.id
        WHERE i.auteur_id = ? AND i.type_action = 'signalement'
        ORDER BY p.date_signalement DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<main class="page-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-navy mb-0">Mes signalements</h2>
            <a href="signaler_panne.php" class="btn btn-warning text-white fw-bold px-4 py-2 shadow-sm">
                <i class="bi bi-plus-lg me-2"></i>Signaler une panne
            </a>
        </div>

        <div class="row g-3">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="col-12">
                        <div class="report-card shadow-sm border-0 card">
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    <div class="col-md-3 border-end">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-light p-3 me-3 text-primary">
                                                <i class="bi bi-pc-display fs-4"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted small">Équipement</div>
                                                <div class="fw-bold text-navy"><?php echo htmlspecialchars($row['num_inventaire']); ?></div>
                                                <div class="badge bg-secondary-subtle text-secondary sm-badge"><?php echo htmlspecialchars($row['eq_type']); ?></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3 border-end ps-md-4">
                                        <div class="mb-2">
                                            <i class="bi bi-geo-alt me-2 text-muted"></i>
                                            <span class="text-dark"><?php echo htmlspecialchars($row['nom_salle']); ?></span>
                                        </div>
                                        <div>
                                            <i class="bi bi-calendar3 me-2 text-muted"></i>
                                            <span class="text-dark"><?php echo date('d/m/Y H:i', strtotime($row['date_signalement'])); ?></span>
                                        </div>
                                    </div>

                                    <div class="col-md-3 border-end ps-md-4">
                                        <div class="mb-2">
                                            <span class="text-muted small d-block">Gravité :</span>
                                            <span class="badge badge-<?php echo strtolower($row['gravite']); ?>">
                                                <?php echo ucfirst($row['gravite']); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-muted small d-block">Statut :</span>
                                            <span class="badge badge-<?php echo str_replace(' ', '-', strtolower($row['statut'])); ?>">
                                                <?php echo ucfirst($row['statut']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-md-3 ps-md-4 text-end">
                                        <div class="text-start mb-2">
                                            <div class="text-muted small">Description :</div>
                                            <div class="fw-bold text-truncate" style="max-width: 200px;">
                                                <?php echo htmlspecialchars($row['description']); ?>
                                            </div>
                                        </div>
                                        <a href="detail_ticket.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                            Détails <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="text-muted mb-3">
                        <i class="bi bi-clipboard-x display-1"></i>
                    </div>
                    <h4>Aucun signalement trouvé</h4>
                    <p>Vous n'avez pas encore déclaré de panne sur le système.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php 
echo "</div>"; // Fermeture page-layout
include '../includes/footer.php'; 
?>