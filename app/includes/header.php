<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}

// SÉCURITÉ & ANTI-CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../db_config.php'; 

// Initialisation des données utilisateur
$user_name = "Invité";
$user_role = "Aucun";
$avatar_letter = "U";

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT nom, prenom, role FROM utilisateur WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $user_name = $row['prenom'] . ' ' . $row['nom'];
        $user_role = $row['role']; 
        $avatar_letter = strtoupper(substr($row['prenom'], 0, 1));
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'LabManager') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/includes/header.css">
    <?php 
    // Vérifie si la variable $custom_css existe avant de tenter de l'afficher
    if (isset($custom_css)): ?>
        <link rel="stylesheet" href="<?php echo $custom_css; ?>">
    <?php endif; ?>
    
    
    
</head>
<body>

<header class="main-header">
  <div class="header-container">
    <a href="#">
    <div class="app-logo">
      <span><img src="https://i.postimg.cc/KYqMXX8q/svgviewer-png-output.png" alt="#" height="67"></span>
    </div>
    </a>

    <div class="user-profile-dropdown" id="profileTrigger">
      <div class="profile-info">
        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
        <span class="user-role"><?php echo htmlspecialchars($user_role); ?></span>
      </div>
      <div class="avatar role-a"><?php echo $avatar_letter; ?></div>
      <div class="dropdown-content" id="dropdownMenu">
        <a href="../auth/logout.php" class="logout-link"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
      </div>
    </div>
  </div>
</header>

<div class="page-layout">
  <aside class="sidebar">
    <div class="sidebar-section-label">Menu</div>

    <?php 
    $role_clean = strtolower($user_role);
    
    // --- SIDEBAR POUR CHEF DE DÉPARTEMENT[cite: 13] ---
    if ($role_clean === 'chef departement'): ?>
        <a href="../admin/dashboard.php" class="sidebar-item"><i class="bi bi-speedometer2"></i> <span>Tableau de bord</span></a>
        <div class="sidebar-item sidebar-toggle" id="toggleInventaire">
            <i class="bi bi-box-seam"></i> <span>Inventaire</span> <i class="bi bi-chevron-right sidebar-arrow"></i>
        </div>
        <div class="sidebar-submenu" id="submenuInventaire">
            <a href="#" class="sidebar-subitem"><i class="bi bi-pc-display"></i> Équipements</a>
            <a href="#" class="sidebar-subitem"><i class="bi bi-file-earmark-pdf"></i> Rapport PDF</a>
        </div>
        <div class="sidebar-item sidebar-toggle" id="toggleAdmin">
            <i class="bi bi-building"></i> <span>Administration</span> <i class="bi bi-chevron-right sidebar-arrow"></i>
        </div>
        <div class="sidebar-submenu" id="submenuAdmin">
            <a href="#" class="sidebar-subitem"><i class="bi bi-people"></i> Utilisateurs</a>
            <a href="#" class="sidebar-subitem"><i class="bi bi-person-plus"></i> Comptes vacataires</a>
        </div>

    <?php 
    // --- SIDEBAR POUR ENSEIGNANT[cite: 14] ---
    elseif ($role_clean === 'enseignant'): ?>
        <a href="../enseignant/index.php" class="sidebar-item"><i class="bi bi-house"></i> <span>Accueil</span></a>
        <div class="sidebar-item sidebar-toggle" id="togglePannes">
            <i class="bi bi-wrench-adjustable"></i> <span>Pannes</span> <i class="bi bi-chevron-right sidebar-arrow"></i>
        </div>
        <div class="sidebar-submenu" id="submenuPannes">
            <a href="#" class="sidebar-subitem"><i class="bi bi-exclamation-triangle"></i> Signaler une panne</a>
            <a href="#" class="sidebar-subitem"><i class="bi bi-list-check"></i> Mes signalements</a>
        </div>

    <?php 
    // --- SIDEBAR POUR SERVICE ENSEIGNEMENT[cite: 15] ---
    elseif ($role_clean === 'service enseignement'): ?>
        <a href="../chef/dashboard.php" class="sidebar-item"><i class="bi bi-house"></i> <span>Accueil</span></a>
        <div class="sidebar-item sidebar-toggle" id="togglePlannings">
            <i class="bi bi-calendar3"></i> <span>Plannings</span> <i class="bi bi-chevron-right sidebar-arrow"></i>
        </div>
        <div class="sidebar-submenu" id="submenuPlannings">
            <a href="#" class="sidebar-subitem"><i class="bi bi-calendar3"></i> Emplois du temps</a>
            <a href="#" class="sidebar-subitem"><i class="bi bi-building"></i> Laboratoires</a>
        </div>

    <?php 
    // --- SIDEBAR POUR TECHNICIEN[cite: 16] ---
    elseif ($role_clean === 'technicien'): ?>
        <a href="../technicien/dashboard.php" class="sidebar-item"><i class="bi bi-speedometer2"></i> <span>Tableau de bord</span></a>
        <div class="sidebar-item sidebar-toggle" id="toggleInvTech">
            <i class="bi bi-box-seam"></i> <span>Inventaire</span> <i class="bi bi-chevron-right sidebar-arrow"></i>
        </div>
        <div class="sidebar-submenu" id="submenuInvTech">
            <a href="../technicien/equipements.php" class="sidebar-subitem"><i class="bi bi-pc-display"></i> Équipements</a>
            <a href="../technicien/ajouter_equipement.php" class="sidebar-subitem"><i class="bi bi-plus-circle"></i> Ajouter équipement</a>
        </div>
        <div class="sidebar-item sidebar-toggle" id="togglePanTech">
            <i class="bi bi-wrench-adjustable"></i> <span>Pannes</span> <i class="bi bi-chevron-right sidebar-arrow"></i>
        </div>
        <div class="sidebar-submenu" id="submenuPanTech">
            <a href="../technicien/tickets.php" class="sidebar-subitem"><i class="bi bi-list-ul"></i> Tickets ouverts</a>
            <a href="#" class="sidebar-subitem"><i class="bi bi-clock-history"></i> Historique</a>
        </div>
    <?php endif; ?>
  </aside>

  <main class="content-area">

<script>
  // Gestion unifiée des menus déroulants de la sidebar[cite: 18]
  document.querySelectorAll('.sidebar-toggle').forEach(trigger => {
    trigger.addEventListener('click', function() {
      const submenu = this.nextElementSibling;
      if (submenu && submenu.classList.contains('sidebar-submenu')) {
        submenu.classList.toggle('open');
        this.classList.toggle('open');
      }
    });
  });

  // Gestion du profil utilisateur[cite: 18]
  const profileTrigger = document.getElementById('profileTrigger');
  if (profileTrigger) {
      profileTrigger.addEventListener('click', (e) => {
        e.stopPropagation();
        profileTrigger.classList.toggle('active');
      });
  }
  window.onclick = () => {
      if (profileTrigger) profileTrigger.classList.remove('active');
  };
</script>