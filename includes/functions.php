<?php
// includes/functions.php

/**
 * Redirige l'utilisateur vers une nouvelle URL et arrête l'exécution du script.
 * @param string $url L'URL vers laquelle rediriger.
 */
function redirect($url) {
    header("Location: $url"); // Envoie l'en-tête HTTP de redirection
    exit(); // Arrête l'exécution du script pour s'assurer que la redirection a lieu
}

/**
 * Vérifie si un utilisateur est actuellement connecté.
 * @return bool Vrai si l'utilisateur est connecté, Faux sinon.
 */
function isAuthenticated() {
    // Vérifie si la variable de session 'user_id' est définie
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur connecté a le rôle d'administrateur.
 * Cette fonction ne doit être appelée qu'après avoir vérifié que l'utilisateur est authentifié.
 * @return bool Vrai si l'utilisateur est administrateur, Faux sinon.
 */
function isAdmin() {
    // Vérifie d'abord si l'utilisateur est connecté ET si son rôle est 'admin'
    return isAuthenticated() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
?>