-- ============================================================
-- LabManager — Schéma de base de données (Version 1 PFCL)
-- ============================================================

CREATE DATABASE IF NOT EXISTS labmanager
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE labmanager;

-- 1. SALLE (Conteneur principal)
CREATE TABLE salle (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom        VARCHAR(50)  NOT NULL,
  capacite   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  type       ENUM('Standard','Réseau','IA','Serveur') NOT NULL DEFAULT 'Standard'
);

-- 2. EMPLOI DU TEMPS (Composant de Salle - Composition)
CREATE TABLE emploi_du_temps (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  salle_id     INT UNSIGNED NOT NULL,
  jour_semaine ENUM('samedi','dimanche','lundi','mardi','mercredi','jeudi') NOT NULL,
  heure_debut  TIME NOT NULL,
  heure_fin    TIME NOT NULL,
  -- ON DELETE CASCADE : si la salle disparaît, le planning aussi
  CONSTRAINT fk_edt_salle FOREIGN KEY (salle_id) REFERENCES salle(id) ON DELETE CASCADE, 
  UNIQUE KEY uq_salle_creneau (salle_id, jour_semaine, heure_debut)
);

-- 3. UTILISATEUR
CREATE TABLE utilisateur (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom              VARCHAR(50)  NOT NULL,
  prenom           VARCHAR(50)  NOT NULL,
  email            VARCHAR(100) NOT NULL UNIQUE,
  mot_de_passe     VARCHAR(255) NOT NULL, -- Stockage hashé (BNF01)
  role             ENUM('technicien','enseignant','chef departement','service enseignement','vacataire') NOT NULL,
  actif            BOOLEAN DEFAULT TRUE,
  date_expiration  DATE DEFAULT NULL
);

-- 4. EQUIPEMENT (Composant de Salle - Composition)
CREATE TABLE equipement (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  num_inventaire    VARCHAR(50)  NOT NULL UNIQUE, 
  salle_id          INT UNSIGNED NOT NULL,
  poste             VARCHAR(20),
  type              ENUM('PC','switch','serveur','ecran','clavier','souris','onduleur','projecteur') NOT NULL,
  marque            VARCHAR(50),
  modele            VARCHAR(50),
  num_serie         VARCHAR(100),
  date_acquisition  DATE,
  etat              ENUM('fonctionnel','en panne','en reparation','en attente de piece','reforme') NOT NULL DEFAULT 'fonctionnel',
  CONSTRAINT fk_equip_salle FOREIGN KEY (salle_id) REFERENCES salle(id) ON DELETE CASCADE
);

-- 5. CONFIGURATION PC
CREATE TABLE configuration_pc (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  equipement_id      INT UNSIGNED NOT NULL UNIQUE,
  carte_mere         VARCHAR(100),
  cpu                VARCHAR(100),
  gpu                VARCHAR(100),
  ram                VARCHAR(50),
  stockage           VARCHAR(100),
  alimentation       VARCHAR(100),
  systeme_exploitation VARCHAR(100),
  CONSTRAINT fk_config_equip FOREIGN KEY (equipement_id) REFERENCES equipement(id) ON DELETE CASCADE
);

-- 6. PANNE
CREATE TABLE panne (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  equipement_id    INT UNSIGNED NOT NULL,
  description      TEXT NOT NULL,
  gravite          ENUM('mineure','majeure','critique') NOT NULL DEFAULT 'mineure',
  statut           ENUM('ouvert','pris en charge','en attente de piece','resolu') NOT NULL DEFAULT 'ouvert',
  date_signalement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  date_resolution  TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_panne_equip FOREIGN KEY (equipement_id) REFERENCES equipement(id)
);

-- 7. PHOTO PANNE
CREATE TABLE photo_panne (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  panne_id  INT UNSIGNED NOT NULL,
  chemin    VARCHAR(255) NOT NULL,
  CONSTRAINT fk_photo_panne FOREIGN KEY (panne_id) REFERENCES panne(id) ON DELETE CASCADE
);

-- 8. INTERVENTION (Composant de Panne - Composition)
CREATE TABLE intervention (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  panne_id           INT UNSIGNED NOT NULL,
  auteur_id          INT UNSIGNED NOT NULL,
  type_action        ENUM('signalement','pris en charge','en attente de piece','resolution','reforme') NOT NULL,
  description_action TEXT NULL,
  pieces_remplacees  TEXT NULL,
  date_intervention  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_inter_panne FOREIGN KEY (panne_id) REFERENCES panne(id) ON DELETE CASCADE,
  CONSTRAINT fk_inter_auteur FOREIGN KEY (auteur_id) REFERENCES utilisateur(id)
);
--
--

CREATE OR REPLACE VIEW v_historique AS
SELECT
    e.num_inventaire,
    e.id                  AS equipement_id,
    e.type                AS type_equipement,
    p.id                  AS panne_id,
    p.description         AS description_panne,
    p.gravite,
    p.statut              AS statut_ticket,
    i.id                  AS intervention_id,
    i.type_action,
    i.description_action,
    i.pieces_remplacees,
    i.date_intervention,
    u.nom                 AS auteur_nom,
    u.prenom              AS auteur_prenom,
    u.role                AS auteur_role
FROM intervention i
JOIN panne p        ON i.panne_id = p.id
JOIN equipement e   ON p.equipement_id = e.id
JOIN utilisateur u  ON i.auteur_id = u.id
ORDER BY e.id DESC, p.date_signalement DESC, i.date_intervention ASC;

--
--
-- 1. Insertion des Salles
INSERT INTO salle (nom, capacite, type) VALUES
('Laboratoire 1', 25, 'Standard'),
('Laboratoire 2', 30, 'Réseau'),
('Laboratoire 3', 30, 'Standard');

-- 2. Insertion des Utilisateurs
-- Roles disponibles : 'technicien','enseignant','chef departement','service enseignement','vacataire'
INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role) VALUES
('Benali', 'Mohamed', 'm.benali@ummto.dz', 'test', 'technicien'),
('Ziri', 'Amine', 'a.ziri@ummto.dz', 'test', 'enseignant'),
('Mansouri', 'Kamel', 'k.mansouri@ummto.dz', 'test', 'chef departement'),
('Haddad', 'Sara', 's.haddad@ummto.dz', 'test', 'service enseignement');

-- 3. Insertion de l'Emploi du Temps
-- Jours : 'samedi','dimanche','lundi','mardi','mercredi','jeudi'
INSERT INTO emploi_du_temps (salle_id, jour_semaine, heure_debut, heure_fin) VALUES
(1, 'dimanche', '08:30:00', '10:00:00'),
(1, 'lundi', '13:00:00', '14:30:00'),
(2, 'mardi', '10:15:00', '11:45:00'),
(3, 'mercredi', '08:30:00', '11:45:00');

-- 4. Insertion des Équipements
-- Types : 'PC','switch','serveur','ecran','clavier','souris','onduleur','projecteur'
INSERT INTO equipement (num_inventaire, salle_id, poste, type, marque, modele, num_serie, date_acquisition, etat) VALUES
-- PC
('INV-24-PC-005', 1, '04', 'PC', 'Lenovo', 'ThinkCentre M70', 'SN-PC-005', '2024-03-01', 'en attente de piece'),
('INV-24-PC-006', 2, '05', 'PC', 'HP', 'ProDesk 600', 'SN-PC-006', '2024-03-02', 'fonctionnel'),
('INV-24-PC-007', 3, '10', 'PC', 'Dell', 'OptiPlex 3080', 'SN-PC-007', '2024-03-03', 'fonctionnel'),
('INV-24-PC-008', 1, '11', 'PC', 'Asus', 'ExpertCenter', 'SN-PC-008', '2024-03-04', 'fonctionnel'),
('INV-24-PC-009', 2, '12', 'PC', 'Acer', 'Veriton', 'SN-PC-009', '2024-03-05', 'fonctionnel'),

-- Serveurs
('INV-24-SRV-01', 1, NULL, 'serveur', 'Dell', 'PowerEdge R740', 'SN-SRV-01', '2024-01-10', 'fonctionnel'),
('INV-24-SRV-02', 1, NULL, 'serveur', 'HP', 'ProLiant DL380', 'SN-SRV-02', '2024-01-12', 'reforme'),

-- Switches
('INV-24-SW-02', 2, NULL, 'switch', 'TP-Link', 'TL-SG1024', 'SN-SW-02', '2024-02-15', 'fonctionnel'),
('INV-24-SW-03', 3, NULL, 'switch', 'D-Link', 'DGS-1100', 'SN-SW-03', '2024-02-16', 'fonctionnel'),

-- Écrans
('INV-24-ECR-01', 1, '04', 'ecran', 'Samsung', 'Odyssey G3', 'SN-ECR-01', '2024-03-10', 'en panne'),
('INV-24-ECR-02', 1, '05', 'ecran', 'LG', 'UltraGear', 'SN-ECR-02', '2024-03-11', 'fonctionnel'),
('INV-24-ECR-03', 2, '10', 'ecran', 'Dell', 'P2422H', 'SN-ECR-03', '2024-03-12', 'fonctionnel'),
('INV-24-ECR-04', 3, '15', 'ecran', 'ViewSonic', 'VA2432', 'SN-ECR-04', '2024-03-13', 'fonctionnel'),
('INV-24-ECR-05', 2, '18', 'ecran', 'Philips', '273V7', 'SN-ECR-05', '2024-03-15', 'fonctionnel'),

-- Claviers
('INV-24-CL-01', 1, '04', 'clavier', 'Logitech', 'K120', 'SN-CL-01', '2024-04-01', 'en panne'),
('INV-24-CL-02', 1, '05', 'clavier', 'Microsoft', 'Wired 600', 'SN-CL-02', '2024-04-02', 'fonctionnel'),
('INV-24-CL-03', 2, '10', 'clavier', 'HP', 'K2500', 'SN-CL-03', '2024-04-03', 'fonctionnel'),
('INV-24-CL-04', 3, '20', 'clavier', 'Cherry', 'G80', 'SN-CL-04', '2024-04-04', 'fonctionnel'),
('INV-24-CL-05', 1, '24', 'clavier', 'Dell', 'KB216', 'SN-CL-05', '2024-04-10', 'fonctionnel'),

-- Souris
('INV-24-SOU-01', 1, '04', 'souris', 'Logitech', 'M100', 'SN-SOU-01', '2024-04-05', 'fonctionnel'),
('INV-24-SOU-02', 2, '12', 'souris', 'Razer', 'DeathAdder', 'SN-SOU-02', '2024-04-06', 'fonctionnel'),
('INV-24-SOU-03', 3, '15', 'souris', 'SteelSeries', 'Rival 3', 'SN-SOU-03', '2024-04-07', 'fonctionnel'),
('INV-24-SOU-04', 1, '22', 'souris', 'HP', 'M150', 'SN-SOU-04', '2024-04-08', 'fonctionnel'),
('INV-24-SOU-05', 2, '21', 'souris', 'Microsoft', 'Basic Optical', 'SN-SOU-05', '2024-04-11', 'fonctionnel'),

-- Onduleurs
('INV-24-OND-01', 1, '04', 'onduleur', 'APC', 'Back-UPS 700', 'SN-OND-01', '2024-01-20', 'fonctionnel'),
('INV-24-OND-02', 2, '05', 'onduleur', 'Eaton', '5SC', 'SN-OND-02', '2024-01-22', 'fonctionnel'),
('INV-24-OND-03', 3, '10', 'onduleur', 'Legrand', 'Keor SP', 'SN-OND-03', '2024-01-25', 'fonctionnel'),

-- Projecteurs
('INV-24-PRO-02', 1, NULL, 'projecteur', 'BenQ', 'TK700', 'SN-PRO-02', '2024-02-20', 'en panne'),
('INV-24-PRO-03', 2, NULL, 'projecteur', 'Optoma', 'UHD35', 'SN-PRO-03', '2024-02-22', 'en attente de piece'),
('INV-24-PRO-04', 3, NULL, 'projecteur', 'ViewSonic', 'PX701', 'SN-PRO-04', '2024-02-25', 'reforme');

-- 5. Insertion des Configurations PC
-- Lié aux équipements de type 'PC'
INSERT INTO configuration_pc (equipement_id, carte_mere, cpu, gpu, ram, stockage, alimentation, systeme_exploitation) VALUES
(1, 'Lenovo M70 Board', 'Intel Core i5-10400', 'UHD Graphics 630', '16GB DDR4', '512GB NVMe SSD', '260W', 'Windows 11 Pro'),
(2, 'HP Pro 600 G6', 'Intel Core i5-10500', 'Intel UHD 630', '8GB DDR4', '256GB SSD', '310W', 'Windows 10 Pro'),
(3, 'Dell OptiPlex 3080 MB', 'Intel Core i3-10100', 'Intel UHD 630', '8GB DDR4', '1TB HDD', '200W', 'Ubuntu 22.04 LTS'),
(4, 'Asus B560M-E', 'AMD Ryzen 5 5600G', 'Radeon Graphics', '16GB DDR4', '512GB SSD', '450W', 'Windows 11 Pro'),
(5, 'Acer Veriton MB', 'Intel Core i5-11400', 'Intel UHD 730', '16GB DDR4', '1TB NVMe SSD', '300W', 'Windows 10 Pro');

-- 6. Insertion des Pannes
INSERT INTO panne (equipement_id, description, gravite, statut) VALUES
(1, 'L''ordinateur ne démarre plus, écran noir.', 'critique', 'en attente de piece'),
(28, 'L''image du projecteur est jaunie.', 'mineure', 'ouvert'),
(10, 'l''ecan est flou.', 'mineure', 'ouvert'),
(29, 'le projecteur ne s''allume pas', 'critique', 'en attente de piece'),
(15, 'touche manquante', 'mineure', 'ouvert');

-- 7. Insertion des Interventions
INSERT INTO intervention (panne_id, auteur_id, type_action, description_action) VALUES
(1, 2, 'signalement', 'Problème constaté par l''enseignant durant le TP.'),
(1, 1, 'pris en charge', 'Début du diagnostic matériel.'),
(1, 1, 'en attente de piece', 'Alimentation HS, commande d''un nouveau bloc 500W.'),
(2, 2, 'signalement', 'probleme constate durant des test pour un TP.'),
(3, 2, 'signalement', 'probleme constate par le prof durant un test.'),
(4, 2, 'signalement', 'probleme servenu en cours d''un TP.'),
(4, 1, 'pris en charge', 'debut du diagnostic.'),
(4, 1, 'en attente de piece', 'piece commande'),
(5, 1, 'signalement', 'trouve pendant une rond');