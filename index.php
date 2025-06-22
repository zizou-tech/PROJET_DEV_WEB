<?php
session_start(); // Démarre la session PHP
require_once 'includes/functions.php'; // Inclut le fichier de fonctions
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Mon Site Web</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">
    
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body>
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
    </header>

    <main>
        <h1 data-aos="fade-up">Bienvenue sur Mon Site Web !</h1>
        <p data-aos="fade-up" data-aos-delay="200">
            <?php if (isAuthenticated()): ?>
                Bonjour, <?php echo htmlspecialchars($_SESSION['username']); ?> !
                Vous êtes connecté(e) en tant que <?php echo htmlspecialchars($_SESSION['user_role']); ?>.
            <?php else: ?>
                Connectez-vous ou inscrivez-vous pour accéder à nos fonctionnalités.
            <?php endif; ?>
        </p>
        <section style="margin-top: 50px; text-align: center;">
            <h2 data-aos="fade-up">Découvrez nos collections</h2>
            <p data-aos="fade-up" data-aos-delay="200" style="max-width: 600px; margin: 0 auto 30px;">
                Explorez notre catalogue de montres de luxe, alliant savoir-faire exceptionnel et design intemporel.
            </p>
            <a href="products.php" class="checkout-btn" data-aos="zoom-in" data-aos-delay="400" style="display: inline-block; text-decoration: none;">Voir les collections</a>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> LuxWatch. Tous droits réservés.</p>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
      AOS.init({
        duration: 1000,
        once: true,
      });
    </script>
</body>
</html>