<?php
session_start();

$page_title = "Laboratoires";
$custom_css = "../assets/css/serv/labos.css";
include '../includes/header.php';

// Vérification du rôle Service Enseignement
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'service enseignement') {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

// Récupération des salles
$query = "SELECT * FROM salle ORDER BY nom";
$result = $conn->query($query);
?>

<main class="main-content">
    <div class="content-header">
        <h2 class="page-title">Laboratoires</h2>
        <p class="page-subtitle">Vue d'ensemble des salles informatiques</p>
    </div>

    <div class="labs-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while($salle = $result->fetch_assoc()): ?>
                <div class="lab-card">
                    <h3 class="lab-name"><?php echo htmlspecialchars($salle['nom']); ?></h3>
                    <p class="lab-type"><?php echo htmlspecialchars($salle['type']); ?></p>
                    <p class="lab-capacity"><?php echo htmlspecialchars($salle['capacite']); ?> postes</p>
                    <a href="emplois_temps.php?salle_id=<?php echo $salle['id']; ?>" class="btn-view-planning">
                        Voir planning
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Aucun laboratoire trouvé.</p>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>