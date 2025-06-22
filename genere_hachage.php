<header>
    <div class="logo"><a href="index.php">LuxWatch</a></div>
    <nav>
        <ul>
            <li><a href="index.php">Accueil</a></li>
            <li><a href="products.php">Collections</a></li>
            <?php if (isAuthenticated()): ?>
                <li><a href="dashboard.php">Tableau de bord</a></li>
                <li><a href="cart.php">Panier</a></li>
                <li><a href="appointments.php">Rendez-vous</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="admin.php">Administration</a></li>
                    <li><a href="admin_products.php">Gérer Produits</a></li>
                    <li><a href="admin_orders.php">Gérer Commandes</a></li>
                <?php endif; ?>
            <?php else: ?>
                <li><a href="login.php">Connexion</a></li>
                <li><a href="register.php">Inscription</a></li>
            <?php endif; ?>
            <li><a href="contact.php">Contact</a></li>
        </ul>
    </nav>
</header> <?php
$mot_de_passe = 'kiko123'; // C'est le mot de passe que tu veux
$hachage_securise = password_hash($mot_de_passe, PASSWORD_DEFAULT);
echo "Le code haché pour 'kiko123' est : <br><strong>" . $hachage_securise . "</strong>";
?>