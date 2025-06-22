<?php
session_start();
require_once 'includes/db_connect.php'; // Cette page interagit avec la DB
require_once 'includes/functions.php';

$products = [];
try {
    $stmt = $conn->query("SELECT id, name, description, price, image_url FROM products ORDER BY name");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p class='message-error' data-aos='fade-up'>Erreur lors du chargement des produits : " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Collections - LuxWatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">
    
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        .product-card {
            background-color: var(--noir-profond);
            border: 1px solid var(--gris-moyen);
            border-radius: 10px;
            overflow: hidden;
            text-align: center;
            padding-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
        }
        .product-card img {
            max-width: 100%;
            height: auto;
            display: block;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--gris-moyen);
        }
        .product-card h3 {
            color: var(--accent-or);
            font-size: 1.5rem;
            margin-top: 10px;
            margin-bottom: 10px;
            text-transform: none; /* Ne pas mettre en uppercase pour les titres de produits */
            letter-spacing: normal;
        }
        .product-card p {
            color: var(--gris-clair);
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        .product-card .price {
            font-size: 1.6rem;
            font-weight: bold;
            color: var(--blanc-pur);
            margin-bottom: 20px;
        }
        .product-card a.view-detail-btn {
            background-color: var(--accent-or);
            color: var(--noir-profond);
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            text-transform: uppercase;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .product-card a.view-detail-btn:hover {
            background-color: var(--blanc-pur);
            color: var(--noir-profond);
            text-decoration: none;
        }
    </style>
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
        <h1 data-aos="fade-up">Nos Collections Exclusives</h1>
        <p data-aos="fade-up" data-aos-delay="200" style="text-align: center; max-width: 800px; margin: 0 auto 50px;">
            Découvrez notre sélection raffinée de montres de luxe, alliant savoir-faire horloger et design intemporel. Chaque pièce est un chef-d'œuvre.
        </p>

        <?php if (count($products) > 0): ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-aos="zoom-in" data-aos-duration="800">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="price"><?php echo number_format($product['price'], 2, ',', ' '); ?> €</p>
                        <a href="product_detail.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="view-detail-btn">Voir les détails</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p data-aos="fade-up" style="text-align: center;">Aucun produit disponible pour le moment.</p>
        <?php endif; ?>
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