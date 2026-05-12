<?php 
session_start();

// 1. Sécurité
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'chef departement') {
    header("Location: ../auth/login.php");
    exit();
}

$page_title = "Gestion des Utilisateurs";
$custom_css = "../assets/css/chef/utilisateurs-chef.css";

include_once('../includes/header.php');

// --- LOGIQUE DE RECHERCHE ET FILTRAGE ---
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $conn->real_escape_string($_GET['role']) : '';
$statut_filter = isset($_GET['actif']) ? $_GET['actif'] : '';

$sql = "SELECT id, nom, prenom, email, role, actif FROM utilisateur WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (nom LIKE '%$search%' OR prenom LIKE '%$search%' OR email LIKE '%$search%')";
}
if (!empty($role_filter)) {
    $sql .= " AND role = '$role_filter'";
}
if ($statut_filter !== '') {
    $sql .= " AND statut = '" . (int)$statut_filter . "'";
}

$sql .= " ORDER BY nom ASC";
$result = $conn->query($sql);
?>

  <main class="page-content">
    <div class="container-fluid">
      <div class="mb-4">
        <h2 class="fw-bold mb-1">Gestion des utilisateurs</h2>
        <p class="text-muted small">Tous les comptes inscrits sur la plateforme</p>
      </div>

      <div class="content-card mb-4 p-3 shadow-sm card border-0">
        <form method="GET" action="" class="row g-3">
          <div class="col-lg-5">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" name="search" class="form-control border-start-0" 
                     placeholder="Nom, prénom ou email..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
          </div>
          <div class="col-lg-3">
            <select name="role" class="form-select" onchange="this.form.submit()">
              <option value="">Tous les rôles</option>
              <option value="enseignant" <?php if($role_filter == 'enseignant') echo 'selected'; ?>>Enseignant</option>
              <option value="technicien" <?php if($role_filter == 'technicien') echo 'selected'; ?>>Technicien</option>
              <option value="chef departement" <?php if($role_filter == 'chef departement') echo 'selected'; ?>>Chef de département</option>
            </select>
          </div>
          <div class="col-lg-3">
            <select name="statut" class="form-select" onchange="this.form.submit()">
              <option value="">Tous les états</option>
              <option value="1" <?php if($statut_filter === '1') echo 'selected'; ?>>Actif uniquement</option>
              <option value="0" <?php if($statut_filter === '0') echo 'selected'; ?>>Inactif uniquement</option>
            </select>
          </div>
          <div class="col-lg-1">
            <button type="submit" class="btn btn-navy w-100 text-white"><i class="bi bi-filter"></i></button>
          </div>
        </form>
      </div>
      <a href="ajoute_utilisateur.php" class="btn btn-outline-secondary text-start"><i class="bi bi-person-plus me-2"></i> ajout utilisateur</a>

      <div class="content-card shadow-sm card border-0 p-0">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="bg-light">
              <tr class="text-muted small">
                <th class="ps-4 border-0">UTILISATEUR</th>
                <th class="border-0">EMAIL</th>
                <th class="border-0">RÔLE</th>
                <th class="border-0 text-center">STATUT</th>
                <th class="text-end pe-4 border-0">ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              <?php if($result && $result->num_rows > 0): ?>
                <?php while($user = $result->fetch_assoc()): 
                  $role_class = "bg-blue-soft";
                  if($user['role'] == 'technicien') $role_class = "bg-orange-soft";
                  if($user['role'] == 'chef departement') $role_class = "bg-indigo-soft";
                  
                  $is_active = ($user['actif'] == 1);
                  $initial = strtoupper(substr($user['prenom'], 0, 1));
                ?>
                <tr>
                  <td class="ps-4 border-0">
                    <div class="d-flex align-items-center gap-3">
                      <div class="avatar-sm <?php echo $role_class; ?>"><?php echo $initial; ?></div>
                      <div>
                        <div class="fw-bold text-navy mb-0"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></div>
                        <span class="text-muted extra-small">ID: #<?php echo $user['id']; ?></span>
                      </div>
                    </div>
                  </td>
                  <td class="border-0 text-muted"><?php echo htmlspecialchars($user['email']); ?></td>
                  <td class="border-0">
                    <span class="badge-role <?php echo $role_class; ?>">
                        <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                    </span>
                  </td>
                  <td class="border-0 text-center">
                    <span class="<?php echo $is_active ? 'text-success' : 'text-danger'; ?> small fw-medium">
                        <i class="bi <?php echo $is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?> me-1"></i>
                        <?php echo $is_active ? 'Actif' : 'Inactif'; ?>
                    </span>
                  </td>
                  <td class="text-end pe-4 border-0">
                    <a href="modifier_utilisateur.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                        <i class="bi bi-pencil"></i>
                    </a>

                    <?php if($is_active): ?>
                        <a href="actions/toggle_user.php?id=<?php echo $user['id']; ?>&action=desactiver" 
                           class="btn btn-sm btn-outline-danger" 
                           onclick="return confirm('Désactiver ce compte ?')">
                            <i class="bi bi-x-circle"></i> Désactiver
                        </a>
                    <?php else: ?>
                        <a href="actions/toggle_user.php?id=<?php echo $user['id']; ?>&action=activer" 
                           class="btn btn-sm btn-outline-success" 
                           onclick="return confirm('Activer ce compte ?')">
                            <i class="bi bi-check-circle"></i> Activer
                        </a>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<?php include_once('../includes/footer.php'); ?>