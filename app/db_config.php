<?php
// db_config.php
$host = "localhost";
$user = "root";
$pass = ""; 
$dbname = "labmanager";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}
?>