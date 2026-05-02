<?php
// 1. Toujours inclure la config et démarrer la session en premier
include '../db_config.php'; 
session_start();

// 2. Traitement des données AVANT d'afficher quoi que ce soit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider_ajout'])) {
    
    $conn->begin_transaction();

    try {
        // Récupération et nettoyage des données
        $salle_id = intval($_POST['id_labo']);
        $num_poste = !empty($_POST['num_poste']) ? $_POST['num_poste'] : NULL;
        $num_inventaire = trim($_POST['num_inventaire']);

        // Préparation de la requête d'insertion
        $sqlEquip = "INSERT INTO equipement (num_inventaire, salle_id, poste, type, marque, modele, num_serie, date_acquisition, etat) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'fonctionnel')";
        
        $stmtEquip = $conn->prepare($sqlEquip);
        $stmtEquip->bind_param("sissssss", 
            $num_inventaire, 
            $salle_id, 
            $num_poste, 
            $_POST['type_equipement'], 
            $_POST['marque'], 
            $_POST['modele'], 
            $_POST['num_serie'], 
            $_POST['date_acquisition']
        );

        if (!$stmtEquip->execute()) {
            throw new Exception("Erreur base de données : " . $stmtEquip->error);
        }

        $equipement_id = $conn->insert_id;

        // Gestion de la partie PC
        if ($_POST['type_equipement'] === 'PC') {
            $sqlConfig = "INSERT INTO configuration_pc (equipement_id, carte_mere, cpu, gpu, ram, stockage, alimentation, systeme_exploitation) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmtConfig = $conn->prepare($sqlConfig);
            $stmtConfig->bind_param("isssssss", 
                $equipement_id, 
                $_POST['carte_mere'], 
                $_POST['cpu'], 
                $_POST['gpu'], 
                $_POST['ram'], 
                $_POST['stockage'], 
                $_POST['alimentation'], 
                $_POST['systeme_exploitation']
            );
            $stmtConfig->execute();
        }

        $conn->commit();
        
        // REDIRECTION : C'est ici que le formulaire se vide car on change de page
        header("Location: equipements.php?success=1");
        exit(); // STOP l'exécution ici pour forcer la redirection

    } catch (Exception $e) {
        $conn->rollback();
        // Si ça échoue, on stocke l'erreur pour l'afficher plus bas
        $msg_erreur = "L'ajout a échoué : " . $e->getMessage();
    }
}

// 3. Début de l'affichage HTML
$page_title = 'Ajouter un équipement';
$custom_css = "../assets/css/technicien/ajouter_equipement.css";
include '../includes/header.php'; 
?>

<main class="page-content">
    <div class="mb-4">
        <h2 class="fw-bold text-navy">Ajouter un équipement</h2>
        <p class="text-muted small">Enregistrer un nouvel équipement dans l'inventaire</p>
    </div>

    <?php if (isset($msg_erreur)): ?>
        <div class="alert alert-danger"><?= $msg_erreur ?></div>
    <?php endif; ?>

    

    <form action="ajouter_equipement.php" method="POST">
        <div class="row">
            <div class="col-lg-8">
                
                <!-- Informations Générales -->
                <div class="card p-4 mb-4 shadow-sm">
                    <h6 class="fw-bold mb-4 text-navy">Informations générales</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">N° d'inventaire *</label>
                            <input type="text" name="num_inventaire" class="form-control" placeholder="ex: LABO2-PC-015" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type d'équipement *</label>
                            <select name="type_equipement" class="form-select" id="typeEquipement" required>
                                <option value="" selected disabled>-- Choisir --</option>
                                <option value="PC">PC</option>
                                <option value="projecteur">Vidéoprojecteur</option>
                                <option value="switch">Switch réseau</option>
                                <option value="ecran">Écran</option>
                                <option value="clavier">Clavier</option>
                                <option value="souris">Souris</option>
                                <option value="onduleur">Onduleur</option>
                                <option value="serveur">Serveur</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Marque *</label>
                            <input type="text" name="marque" class="form-control" placeholder="ex: Dell, HP..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Modèle *</label>
                            <input type="text" name="modele" class="form-control" placeholder="ex: OptiPlex 7050" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Numéro de série</label>
                            <input type="text" name="num_serie" class="form-control" placeholder="ex: DLL7050X12456">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date d'acquisition</label>
                            <input type="date" name="date_acquisition" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Localisation -->
                <div class="card p-4 mb-4 shadow-sm">
                    <h6 class="fw-bold mb-4 text-navy">Localisation</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Laboratoire *</label>
                            <select name="id_labo" class="form-select" required>
                                <option value="" selected disabled>-- Choisir --</option>
                                <option value="1">Laboratoire 1</option>
                                <option value="2">Laboratoire 2</option>
                                <option value="3">Laboratoire 3 (IA)</option>
                                <option value="4">Laboratoire 4</option>
                                <option value="5">Laboratoire 5</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Numéro de poste</label>
                            <input type="number" name="num_poste" class="form-control" placeholder="ex: 14" min="1">
                        </div>
                    </div>
                </div>

                <!-- Configuration PC -->
                <div class="card p-4 mb-4 shadow-sm" id="configPCSection" style="display: none;">
                    <div class="d-flex align-items-center mb-4">
                        <div class="icon-circle me-3"><i class="bi bi-pc-display text-primary"></i></div>
                        <h6 class="fw-bold mb-0 text-navy">Configuration matérielle (PC)</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Carte mère</label>
                            <input type="text" name="carte_mere" class="form-control" placeholder="ex: ASUS PRIME">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Processeur (CPU)</label>
                            <input type="text" name="cpu" class="form-control" placeholder="ex: Intel i5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mémoire RAM</label>
                            <input type="text" name="ram" class="form-control" placeholder="ex: 8 Go DDR4">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stockage</label>
                            <input type="text" name="stockage" class="form-control" placeholder="ex: 256 Go SSD">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Carte graphique (GPU)</label>
                            <input type="text" name="gpu" class="form-control" placeholder="ex: Intel HD 630">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Système d'exploitation</label>
                            <input type="text" name="systeme_exploitation" class="form-control" placeholder="ex: Windows 10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alimentation</label>
                            <input type="text" name="alimentation" class="form-control" placeholder="ex: 400W">
                        </div>
                    </div>
                </div>
            </div>

            <!-- État et Actions -->
            <div class="col-lg-4">
                <div class="card p-4 mb-4 shadow-sm">
                    <h6 class="fw-bold mb-3 text-navy">État initial</h6>
                    <div class="alert alert-success py-2 small border-0" style="background:#e6f7ef; color:#166534;">
                        <i class="bi bi-check-circle-fill me-2"></i> État : <strong>Fonctionnel</strong>
                    </div>
                    <label class="form-label mt-2">Notes</label>
                    <textarea name="notes" class="form-control" rows="4" placeholder="Observations..."></textarea>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" name="valider_ajout" class="btn btn-navy py-2 fw-bold">
                        <i class="bi bi-save me-2"></i>Enregistrer
                    </button>
                    <a href="equipements.php" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </div>
        </div>
    </form>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('typeEquipement');
        const configSection = document.getElementById('configPCSection');
        
        if (typeSelect) {
            typeSelect.addEventListener('change', function() {
                configSection.style.display = (this.value === 'PC') ? 'block' : 'none';
            });
        }
    });
</script>

<?php 
echo "</main></div>"; 
include '../includes/footer.php'; 
?>