<?php
session_start();

// Configuration de la connexion
$host = "localhost";
$user = "root";
$pass = ""; 
$dbname = "labmanager";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Nettoyage des entrées
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Requête sur la table 'utilisateur' (selon votre schema.sql)
    $sql = "SELECT id, mot_de_passe, role FROM utilisateur WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Comparaison du mot de passe
        if ($password === $row['mot_de_passe']) {
            
            // Stockage en session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];

            // Redirection selon le rôle (ENUM: 'Admin','Technicien','Enseignant','Chef')
            $role = strtolower($row['role']);
            
            if ($role === 'technicien') {
                header("Location: ../technicien/dashboard.php");
            } elseif ($role === 'enseignant' || $role === 'vacataire' ) {
                header("Location: ../enseignant/index.php");
            } elseif ($role === 'chef departement') {
                header("Location: ../chef/dashboard.php");
            } elseif ($role === 'service enseignement') {
                header("Location: ../service/index.php");
            } else {
                echo "Erreur : Rôle non reconnu ($role).";
            }
            exit();
        } else {
            header("Location: login.php?error=1"); // Mot de passe faux
        }
    } else {
        header("Location: login.php?error=2"); // Utilisateur non trouvé
    }
}
?>