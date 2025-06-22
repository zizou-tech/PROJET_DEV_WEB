<?php
session_start();
require_once 'includes/db_connect.php'; // Cette page interagit avec la DB
require_once 'includes/functions.php';

$product = null;
$message = '';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT id, name, description, price, image_url, stock FROM products WHERE id = :id");
        $stmt->execute([':id' => $product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $message = "Produit non trouvé.";
        }
    } catch (PDOException $e) {
        $message = "Erreur lors du chargement du produit : " . htmlspecialchars($e->getMessage());
    }
} else {
    $message = "ID de produit invalide.";
}

// Logique d'ajout au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart']) && $product) {
    if (!isAuthenticated()) {
        redirect('login.php?redirect=product_detail.php?id=' . $product_id); // Redirige après connexion
    }

    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($quantity < 1) $quantity = 1;

    try {
        // 1. Trouver ou créer le panier de l'utilisateur
        $stmt_cart = $conn->prepare("SELECT id FROM carts WHERE user_id = :user_id");
        $stmt_cart->execute([':user_id' => $_SESSION['user_id']]);
        $cart = $stmt_cart->fetch(PDO::FETCH_ASSOC);

        $cart_id = 0;
        if ($cart) {
            $cart_id = $cart['id'];
        } else {
            $stmt_new_cart = $conn->prepare("INSERT INTO carts (user_id) VALUES (:user_id)");
            $stmt_new_cart->execute([':user_id' => $_SESSION['user_id']]);
            $cart_id = $conn->lastInsertId();
        }

        // 2. Vérifier si l'article est déjà dans le panier
        $stmt_item = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id");
        $stmt_item->execute([':cart_id' => $cart_id, ':product_id' => $product['id']]);
        $cart_item = $stmt_item->fetch(PDO::FETCH_ASSOC);

        if ($cart_item) {
            // Mettre à jour la quantité si l'article existe déjà
            $new_quantity = $cart_item['quantity'] + $quantity;
            if ($new_quantity > $product['stock']) {
                $message = "Quantité demandée supérieure au stock disponible (" . $product['stock'] . ").";
            } else {
                $stmt_update_item = $conn->prepare("UPDATE cart_items SET quantity = :quantity WHERE id = :id");
                $stmt_update_item->execute([':quantity' => $new_quantity, ':id' => $cart_item['id']]);
                $message = "Quantité mise à jour dans le panier !";
            }
        } else {
            // Ajouter un nouvel article si ce n'est pas déjà dans le panier
            if ($quantity > $product['stock']) {
                 $message = "Quantité demandée supérieure au stock disponible (" . $product['stock'] . ").";
            } else {
                $stmt_add_item = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (:cart_id, :product_id, :quantity)");
                $stmt_add_item->execute([':cart_id' => $cart_id, ':product_id' => $product['id'], ':quantity' => $quantity]);
                $message = "Article ajouté au panier !";
            }
        }
    } catch (PDOException $e) {
        $message = "Erreur lors de l'ajout au panier : " . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? htmlspecialchars($product['name']) : 'Détail Produit'; ?> - LuxWatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">
    
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        .product-detail-container {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            margin-top: 40px;
            align-items: flex-start;
        }
        .product-detail-image {
            flex: 1 1 40%; /* Prend 40% de la largeur ou 100% sur petit écran */
            min-width: 300px;
            text-align: center;
        }
        .product-detail-image img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        .product-detail-info {
            flex: 1 1 50%; /* Prend 50% de la largeur ou 100% sur petit écran */
            min-width: 300px;
        }
        .product-detail-info h1 {
            text-align: left;
            color: var(--blanc-pur);
            font-size: 2.8rem;
            margin-bottom: 20px;
        }
        .product-detail-info .price {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--accent-or);
            margin-bottom: 25px;
        }
        .product-detail-info p {
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 20px;
        }
        .product-detail-info .stock {
            font-size: 1rem;
            color: var(--gris-clair);
            margin-bottom: 20px;
        }
        .add-to-cart-form {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 30px;
        }
        .add-to-cart-form input[type="number"] {
            width: 80px;
            padding: 10px;
            text-align: center;
            background-color: var(--noir-profond);
            color: var(--blanc-pur);
            border: 1px solid var(--gris-moyen);
            border-radius: 5px;
        }
        .add-to-cart-form button {
            padding: 12px 25px;
            background-color: var(--accent-or);
            color: var(--noir-profond);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-transform: uppercase;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .add-to-cart-form button:hover {
            background-color: var(--blanc-pur);
            color: var(--noir-profond);
        }
        .cta-buttons {
            margin-top: 40px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .cta-buttons a {
            flex: 1;
            min-width: 200px;
            text-align: center;
            padding: 15px 25px;
            border-radius: 5px;
            font-weight: bold;
            text-transform: uppercase;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        .cta-buttons .appointment-btn {
            background-color: transparent;
            border: 2px solid var(--accent-or);
            color: var(--accent-or);
        }
        .cta-buttons .appointment-btn:hover {
            background-color: var(--accent-or);
            color: var(--noir-profond);
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .product-detail-container {
                flex-direction: column;
            }
            .product-detail-info h1 {
                text-align: center;
            }
            .add-to-cart-form {
                justify-content: center;
            }
            .cta-buttons {
                justify-content: center;
            }
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
        <?php if ($message): ?>
            <p class="message-<?php echo (strpos($message, 'Erreur') !== false || strpos($message, 'invalide') !== false || strpos($message, 'supérieure') !== false) ? 'error' : 'success'; ?>" data-aos="fade-up"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if ($product): ?>
            <div class="product-detail-container">
                <div class="product-detail-image" data-aos="fade-right" data-aos-duration="1000">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                <div class="product-detail-info" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    <p class="price"><?php echo number_format($product['price'], 2, ',', ' '); ?> €</p>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    <p class="stock">Stock disponible : <?php echo htmlspecialchars($product['stock']); ?> pièces</p>

                    <?php if ($product['stock'] > 0): ?>
                        <form action="product_detail.php?id=<?php echo $product_id; ?>" method="POST" class="add-to-cart-form">
                            <label for="quantity">Quantité :</label>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo htmlspecialchars($product['stock']); ?>">
                            <button type="submit" name="add_to_cart">Ajouter au panier</button>
                        </form>
                    <?php else: ?>
                        <p class="message-error">Ce produit est actuellement en rupture de stock.</p>
                    <?php endif; ?>

                    <div class="cta-buttons">
                        <a href="appointments.php" class="appointment-btn">Prendre Rendez-vous</a>
                        <a href="products.php" class="appointment-btn">Retour aux Collections</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <p data-aos="fade-up" style="text-align: center;">Le produit demandé n'est pas disponible ou l'ID est invalide.</p>
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