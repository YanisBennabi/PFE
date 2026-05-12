<?php
// 1. Initialisation et Sécurité
session_start();

// Vérification du rôle Service Enseignement
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'service enseignement') {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

// Récupération de l'ID de la salle depuis l'URL
$salle_id = isset($_GET['salle_id']) ? intval($_GET['salle_id']) : 0;
if ($salle_id === 0) {
    header("Location: laboratoires.php");
    exit();
}

$page_title = "Gestion du Planning";
$custom_css = "../assets/css/serv/emplois_temps.css";

// Inclusion du header (qui contient la connexion $conn)
include '../includes/header.php';

// 2. Récupérer les informations de la salle
$stmt_salle = $conn->prepare("SELECT nom FROM salle WHERE id = ?");
$stmt_salle->bind_param("i", $salle_id);
$stmt_salle->execute();
$res_salle = $stmt_salle->get_result();
$salle_info = $res_salle->fetch_assoc();
$nom_salle = $salle_info ? $salle_info['nom'] : "Inconnue";

// 3. Récupérer les créneaux déjà occupés dans la base
$occupied = [];
$query_edt = "SELECT jour_semaine, heure_debut FROM emploi_du_temps WHERE salle_id = ?";
$stmt_edt = $conn->prepare($query_edt);
$stmt_edt->bind_param("i", $salle_id);
$stmt_edt->execute();
$res_edt = $stmt_edt->get_result();

while($row = $res_edt->fetch_assoc()) {
    // On stocke sous la forme $occupied['lundi'][] = '08:00:00'
    $occupied[$row['jour_semaine']][] = $row['heure_debut'];
}

// Configuration des jours (doit correspondre à l'ENUM de la DB)
$jours = ['samedi', 'dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi'];

// Configuration des créneaux horaires (Heure début => Label affiché)
$creneaux = [
    '08:00:00' => '08:00 - 09:30',
    '09:40:00' => '09:40 - 11:10',
    '11:20:00' => '11:20 - 12:50',
    '13:00:00' => '13:00 - 14:30',
    '14:40:00' => '14:40 - 16:10'
];
?>

<main class="page-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-navy mb-0">Planning : <?php echo htmlspecialchars($nom_salle); ?></h2>
                <p class="text-muted small">Cliquez sur une case pour basculer entre Libre et Occupé.</p>
            </div>
            <button id="saveBtn" class="btn btn-primary px-4 shadow-sm fw-bold">
                <i class="bi bi-cloud-check me-2"></i>Enregistrer le planning
            </button>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-bordered table-planning mb-0">
                    <thead class="bg-light text-center">
                        <tr>
                            <th style="width: 150px;">Horaire</th>
                            <?php foreach($jours as $j): ?>
                                <th class="text-capitalize"><?php echo $j; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($creneaux as $heure_sql => $label): ?>
                        <tr>
                            <td class="time-column text-center bg-light fw-bold"><?php echo $label; ?></td>
                            <?php foreach($jours as $j): 
                                $is_occ = (isset($occupied[$j]) && in_array($heure_sql, $occupied[$j]));
                                $btn_class = $is_occ ? 'bg-danger text-white' : 'bg-success text-white';
                                $status_text = $is_occ ? 'Occupé' : 'Libre';
                            ?>
                            <td class="p-1">
                                <button class="btn-status-toggle w-100 border-0 py-3 <?php echo $btn_class; ?>" 
                                        data-jour="<?php echo $j; ?>" 
                                        data-heure="<?php echo $heure_sql; ?>"
                                        data-status="<?php echo $is_occ ? 'occupé' : 'libre'; ?>">
                                    <?php echo $status_text; ?>
                                </button>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
// 1. Gestion du clic sur les cases (Changement visuel)
document.querySelectorAll('.btn-status-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        if (this.dataset.status === 'libre') {
            this.dataset.status = 'occupé';
            this.textContent = 'Occupé';
            this.classList.replace('bg-success', 'bg-danger');
        } else {
            this.dataset.status = 'libre';
            this.textContent = 'Libre';
            this.classList.replace('bg-danger', 'bg-success');
        }
    });
});

// 2. Envoi des données au serveur (AJAX)
document.getElementById('saveBtn').addEventListener('click', function() {
    const btnSave = this;
    const planningData = [];
    
    // Récupérer uniquement les créneaux marqués comme "occupé"
    document.querySelectorAll('.btn-status-toggle').forEach(btn => {
        if (btn.dataset.status === 'occupé') {
            planningData.push({
                jour: btn.dataset.jour,
                heure_debut: btn.dataset.heure
            });
        }
    });

    // Désactiver le bouton pendant l'envoi
    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...';

    fetch('save_edt.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            salle_id: <?php echo $salle_id; ?>,
            planning: planningData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Le planning de la salle <?php echo $nom_salle; ?> a été mis à jour avec succès.');
        } else {
            alert('Erreur : ' + (data.error || 'Impossible de sauvegarder.'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur réseau est survenue.');
    })
    .finally(() => {
        btnSave.disabled = false;
        btnSave.innerHTML = '<i class="bi bi-cloud-check me-2"></i>Enregistrer le planning';
    });
});
</script>

<?php 
echo "</div>"; // Fermeture page-layout
include '../includes/footer.php'; 
?>