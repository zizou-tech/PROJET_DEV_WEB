<?php
// includes/db_connect.php

// Paramètres de connexion à la base de données
$servername = "localhost"; // L'adresse de ton serveur MySQL (XAMPP par défaut)
$username = "root";        // Le nom d'utilisateur par défaut de MySQL sous XAMPP
$password = "";            // Le mot de passe par défaut de MySQL sous XAMPP est vide
$dbname = "monsite_db";    // Le nom de la base de données que tu as créée

try {
    // Crée une nouvelle instance de PDO (PHP Data Objects) pour la connexion
    // 'mysql:host=' indique le type de base de données et son adresse
    // 'dbname=' spécifie la base de données à utiliser
    // 'charset=utf8mb4' assure le bon encodage des caractères (supporte les emojis, etc.)
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);

    // Configure PDO pour qu'il lance des exceptions en cas d'erreurs SQL
    // Cela rend le débogage plus facile et est une bonne pratique de sécurité
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Désactive l'émulation des requêtes préparées pour une meilleure sécurité et performance
    // Les requêtes préparées protègent contre les injections SQL
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch(PDOException $e) {
    // En cas d'erreur de connexion, arrête le script et affiche un message d'erreur
    // 'die()' arrête l'exécution du script
    // '$e->getMessage()' récupère le message d'erreur de PDO
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>