<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../db_config.php';

// Récupération des filtres depuis l'URL
$filter_gravite = $_GET['gravite'] ?? '';
$filter_statut = $_GET['statut'] ?? '';

// 1. Calcul des statistiques dynamiques
$stats_query = "SELECT 
    SUM(CASE WHEN gravite = 'critique' THEN 1 ELSE 0 END) as critiques,
    SUM(CASE WHEN gravite = 'majeure' THEN 1 ELSE 0 END) as majeures,
    SUM(CASE WHEN gravite = 'mineure' THEN 1 ELSE 0 END) as mineures,
    SUM(CASE WHEN statut = 'en attente de piece' THEN 1 ELSE 0 END) as attente
    FROM panne WHERE statut != 'resolu'";
$stats = $conn->query($stats_query)->fetch_assoc();

// 2. Construction de la requête principale avec filtres[cite: 5, 6]
$sql = "SELECT p.*, e.num_inventaire, s.nom as nom_salle, u.nom as nom_prof, u.prenom as prenom_prof 
        FROM panne p
        JOIN equipement e ON p.equipement_id = e.id
        JOIN salle s ON e.salle_id = s.id
        JOIN intervention i ON i.panne_id = p.id AND i.type_action = 'signalement'
        JOIN utilisateur u ON i.auteur_id = u.id
        WHERE 1=1";

if ($filter_gravite) $sql .= " AND p.gravite = '" . $conn->real_escape_string($filter_gravite) . "'";
if ($filter_statut) $sql .= " AND p.statut = '" . $conn->real_escape_string($filter_statut) . "'";

$sql .= " GROUP BY p.id ORDER BY p.date_signalement DESC";
$tickets = $conn->query($sql);

$custom_css = "../assets/css/technicien/tickers.css";
include '../includes/header.php'; 
?>

<main class="page-content">
    <div class="mb-4">
        <h2 class="fw-bold text-navy mb-1">Tickets de panne</h2>
    </div>

    <!-- Cartes de statistiques dynamiques -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-danger-subtle text-danger"><i class="bi bi-fire"></i></div>
                    <div><h3 class="fw-bold mb-0"><?= $stats['critiques'] ?? 0 ?></h3><small class="text-muted">Critiques</small></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i></div>
                    <div><h3 class="fw-bold mb-0"><?= $stats['majeures'] ?? 0 ?></h3><small class="text-muted">Majeures</small></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-info-circle"></i></div>
                    <div><h3 class="fw-bold mb-0"><?= $stats['mineures'] ?? 0 ?></h3><small class="text-muted">Mineures</small></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-hourglass-split"></i></div>
                    <div><h3 class="fw-bold mb-0"><?= $stats['attente'] ?? 0 ?></h3><small class="text-muted">En attente pièce</small></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barre de recherche et Filtres[cite: 6] -->
    <div class="card border-0 shadow-sm mb-4 p-3">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-0" placeholder="Rechercher un ticket...">
                </div>
            </div>
            <div class="col-md-2">
                <select name="gravite" class="form-select border-0 bg-light" onchange="this.form.submit()">
                    <option value="">Toutes gravités</option>
                    <option value="critique" <?= $filter_gravite == 'critique' ? 'selected' : '' ?>>Critique</option>
                    <option value="majeure" <?= $filter_gravite == 'majeure' ? 'selected' : '' ?>>Majeure</option>
                    <option value="mineure" <?= $filter_gravite == 'mineure' ? 'selected' : '' ?>>Mineure</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="statut" class="form-select border-0 bg-light" onchange="this.form.submit()">
                    <option value="">Tous statuts</option>
                    <option value="ouvert" <?= $filter_statut == 'ouvert' ? 'selected' : '' ?>>Ouvert</option>
                    <option value="pris en charge" <?= $filter_statut == 'pris en charge' ? 'selected' : '' ?>>Pris en charge</option>
                    <option value="en attente de piece" <?= $filter_statut == 'en attente de piece' ? 'selected' : '' ?>>En attente</option>
                </select>
            </div>
            <div class="col-md-3 text-end">
                <a href="nouveau_ticket.php" class="btn btn-navy w-100 py-2"><i class="bi bi-plus-lg me-2"></i>Nouveau ticket</a>
            </div>
        </form>
    </div>

    <!-- Tableau des tickets -->
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">TICKET</th>
                        <th>ÉQUIPEMENT</th>
                        <th>SALLE</th>
                        <th>SIGNALÉ PAR</th>
                        <th>DATE</th>
                        <th>GRAVITÉ</th>
                        <th>STATUT</th>
                        <th class="pe-4">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $tickets->fetch_assoc()): 
                        // Style des badges de gravité[cite: 5]
                        $grav_class = match($row['gravite']) {
                            'critique' => 'bg-danger-subtle text-danger',
                            'majeure' => 'bg-warning-subtle text-warning',
                            default => 'bg-info-subtle text-info'
                        };
                        
                        // Style du texte de statut
                        $statut_class = match($row['statut']) {
                            'ouvert' => 'text-danger',
                            'pris en charge' => 'text-warning',
                            'en attente de piece' => 'text-primary',
                            default => 'text-success'
                        };
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold">PAN-<?= str_pad($row['id'], 3, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars($row['num_inventaire']) ?></td>
                        <td><?= htmlspecialchars($row['nom_salle']) ?></td>
                        <td><?= htmlspecialchars($row['nom_prof'] . ' ' . $row['prenom_prof']) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['date_signalement'])) ?></td>
                        <td><span class="badge <?= $grav_class ?>"><?= ucfirst($row['gravite']) ?></span></td>
                        <td><span class="<?= $statut_class ?> fw-bold"><?= ucfirst($row['statut']) ?></span></td>
                        <td class="pe-4">
                            <!-- Logique des boutons d'actions demandée -->
                            <a href="detail_ticket.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-light me-1">Voir</a>
                            
                            <?php if($row['statut'] == 'ouvert'): ?>
                                <a href="actions/prendre_charge.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-navy">Prendre en charge</a>
                            
                            <?php elseif($row['statut'] == 'pris en charge'): ?>
                                <a href="actions/resoudre.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-success-subtle">Résolu</a>
                                <a href="actions/reformer.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger border-0">Réformé</a>
                            
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>