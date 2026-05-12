<?php
session_start(); // On récupère la session actuelle

// 1. On vide toutes les variables de session
$_SESSION = array();

// 2. On détruit le cookie de session dans le navigateur (très important)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. On détruit la session sur le serveur
session_destroy();

// 4. On redirige vers la page de connexion
header("Location: login.php");
exit();
?>