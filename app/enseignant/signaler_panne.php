<?php
session_start();
// Chemins à adapter selon ton arborescence
$custom_css = "../assets/css/enseignant/signaler_panne.css"; 
$page_title = "Signaler une panne";

include '../includes/header.php'; 

if (!isset($_SESSION['role']) || (strtolower($_SESSION['role']) !== 'enseignant' && strtolower($_SESSION['role']) !== 'vacataire')) {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

// Récupération des salles pour le premier menu
$query_salles = "SELECT id, nom FROM salle ORDER BY nom";
$result_salles = $conn->query($query_salles);
?>

<main class="page-content">
    <div class="container-fluid">
        <form action="traitement_panne.php" method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <h2 class="fw-bold text-navy mb-1">Signaler une panne</h2>
                <p class="text-muted small">Détaillez le problème rencontré pour une prise en charge rapide.</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-navy mb-4 border-bottom pb-2">Localisation</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Salle / Laboratoire</label>
                                    <select class="form-select" name="salle_id" id="selectSalle" required>
                                        <option value="" selected disabled>Choisir une salle</option>
                                        <?php while($salle = $result_salles->fetch_assoc()): ?>
                                            <option value="<?= $salle['id'] ?>"><?= htmlspecialchars($salle['nom']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Équipement</label>
                                    <select class="form-select" name="equipement_id" id="selectEquipement" required disabled>
                                        <option value="" selected disabled>Choisir la salle d'abord</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-navy mb-4 border-bottom pb-2">Détails de l'incident</h6>
                            
                            <label class="form-label small fw-bold mb-3">Gravité</label>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="gravite" id="mineure" value="mineure" checked>
                                    <label class="btn btn-outline-light gravity-card w-100 p-3" for="mineure">
                                        <i class="bi bi-info-circle fs-3 d-block mb-1 text-info"></i>
                                        <span class="fw-bold text-dark">Mineure</span>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="gravite" id="majeure" value="majeure">
                                    <label class="btn btn-outline-light gravity-card w-100 p-3" for="majeure">
                                        <i class="bi bi-exclamation-triangle fs-3 d-block mb-1 text-warning"></i>
                                        <span class="fw-bold text-dark">Majeure</span>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="gravite" id="critique" value="critique">
                                    <label class="btn btn-outline-light gravity-card w-100 p-3" for="critique">
                                        <i class="bi bi-fire fs-3 d-block mb-1 text-danger"></i>
                                        <span class="fw-bold text-dark">Critique</span>
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Description du problème</label>
                                <textarea name="description" class="form-control" rows="4" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-navy">Ajouter une photo (Preuve du défaut)</label>
                                <input type="file" name="photo_panne" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
                        <div class="card-body p-4">
                            <button type="submit" class="btn btn-warning w-100 py-3 fw-bold text-white shadow-sm mb-2">
                                <i class="bi bi-send me-2"></i>Envoyer le signalement
                            </button>
                            <a href="index.php" class="btn btn-light w-100 py-3">Annuler</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
// AJAX pour charger les équipements selon la salle
document.getElementById('selectSalle').addEventListener('change', function() {
    const salleId = this.value;
    const eqSelect = document.getElementById('selectEquipement');
    
    eqSelect.disabled = true;
    eqSelect.innerHTML = '<option>Chargement...</option>';

    fetch(`get_equipements.php?salle_id=${salleId}`)
        .then(response => response.json())
        .then(data => {
            eqSelect.innerHTML = '<option value="" selected disabled>Choisir l\'équipement</option>';
            data.forEach(eq => {
                eqSelect.innerHTML += `<option value="${eq.id}">${eq.num_inventaire} (${eq.type})</option>`;
            });
            eqSelect.disabled = false;
        });
});
</script>

<?php include '../includes/footer.php'; ?>