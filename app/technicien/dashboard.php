<?php
// 1. Initialisation et Sécurité
session_start();

// On définit le CSS avant d'inclure le header pour qu'il soit chargé dans le <head>[cite: 6]
$custom_css = "../assets/css/technicien/dashboard.css"; 
$page_title = "Tableau de Bord - Technicien";

// Inclusion du header (qui contient la barre latérale et la connexion DB)[cite: 3, 5]
include '../includes/header.php'; 

// Vérification du rôle[cite: 3, 5]
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'technicien') {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

// 2. Récupération des données depuis la base[cite: 1, 4]
require_once '../db_config.php'; 

// Statistiques
$totalEquip = $conn->query("SELECT COUNT(*) FROM equipement WHERE etat != 'reforme'")->fetch_row()[0];
$totalPannes = $conn->query("SELECT COUNT(*) FROM panne WHERE statut = 'ouvert'")->fetch_row()[0];
$totalAttente = $conn->query("SELECT COUNT(*) FROM panne WHERE statut = 'en attente de piece'")->fetch_row()[0];
// Taux de disponibilité
$resDispo = $conn->query("SELECT 
    (SELECT COUNT(*) FROM equipement WHERE etat = 'fonctionnel') / 
    (SELECT COUNT(*) FROM equipement WHERE etat != 'reforme') * 100");
$tauxDispo = round($resDispo->fetch_row()[0] ?? 0, 1);
?>

<!-- Structure Flexbox pour occuper tout l'écran -->
<div class="page-layout">
    <!-- Note: La sidebar est déjà incluse dans ton header.php selon tes sources[cite: 3] -->

    <main class="page-content">
        <div class="container-fluid p-4"> <!-- container-fluid pour le plein écran[cite: 5] -->
            
            <div class="dashboard-header mb-4">
                <h2 class="fw-bold">Tableau de bord</h2>
            </div>

            <!-- Cartes Statistiques[cite: 6] -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon icon-blue"><i class="bi bi-pc-display"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $totalEquip; ?></div>
                            <div class="stat-label">Équipements actifs</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon icon-red"><i class="bi bi-exclamation-circle"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $totalPannes; ?></div>
                            <div class="stat-label">Pannes ouvertes</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon icon-orange"><i class="bi bi-clock"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $totalAttente; ?></div>
                            <div class="stat-label">En attente de pièce</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon icon-green"><i class="bi bi-check2-circle"></i></div>
                        <div>
                            <div class="stat-value"><?php echo $tauxDispo; ?>%</div>
                            <div class="stat-label">Disponibilité</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau des Tickets Ouverts -->
            <div class="card border-0 shadow-sm w-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-ticket-perforated me-2 text-primary"></i> Tickets ouverts</h5>
                    <a href="tickets.php" class="btn btn-outline-primary btn-sm">Voir tout</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>ÉQUIPEMENT</th>
                                    <th>SALLE</th>
                                    <th>GRAVITÉ</th>
                                    <th>STATUT</th>
                                    <th>DATE</th>
                                    <th class="text-end pe-4">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT p.*, e.num_inventaire, s.nom as nom_salle 
                                        FROM panne p
                                        JOIN equipement e ON p.equipement_id = e.id
                                        JOIN salle s ON e.salle_id = s.id
                                        WHERE p.statut != 'resolu'
                                        ORDER BY p.date_signalement DESC LIMIT 5";
                                $result = $conn->query($sql);

                                while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 text-muted">PAN-<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($row['num_inventaire']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nom_salle']); ?></td>
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
                                    <td><?php echo date('d/m/Y', strtotime($row['date_signalement'])); ?></td>
                                    <td class="text-end pe-4">
                                        <a href="detail_ticket.php?id=<?php echo $row['id']; ?>" class="btn btn-light btn-sm">Voir</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php 
// Fermeture des balises div ouvertes dans le header (si nécessaire)[cite: 3]
echo "</div></div>"; 
include '../includes/footer.php'; 
?>