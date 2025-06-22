<?php
session_start();
require_once 'includes/db_connect.php'; // Cette page interagit avec la DB
require_once 'includes/functions.php';

// Rediriger si non connecté
if (!isAuthenticated()) {
    redirect('login.php?redirect=cart.php');
}

$cart_items = [];
$total_cart_amount = 0;
$message = '';

try {
    // Récupérer les articles du panier de l'utilisateur
    $stmt_cart = $conn->prepare("SELECT c.id AS cart_id FROM carts c WHERE c.user_id = :user_id");
    $stmt_cart->execute([':user_id' => $_SESSION['user_id']]);
    $user_cart = $stmt_cart->fetch(PDO::FETCH_ASSOC);

    if ($user_cart) {
        $cart_id = $user_cart['cart_id'];
        $stmt_items = $conn->prepare("SELECT ci.id AS item_id, ci.quantity, p.id AS product_id, p.name, p.price, p.image_url, p.stock
                                     FROM cart_items ci
                                     INNER JOIN products p ON ci.product_id = p.id
                                     WHERE ci.cart_id = :cart_id");
        $stmt_items->execute([':cart_id' => $cart_id]);
        $cart_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cart_items as $item) {
            $total_cart_amount += ($item['quantity'] * $item['price']);
        }
    }

    // Logique de mise à jour/suppression du panier
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_quantity']) && isset($_POST['item_id']) && isset($_POST['new_quantity'])) {
            $item_id = (int)$_POST['item_id'];
            $new_quantity = (int)$_POST['new_quantity'];

            // Vérifier que l'item appartient bien au panier de l'utilisateur
            $stmt_check_owner = $conn->prepare("SELECT ci.id, p.stock FROM cart_items ci JOIN products p ON ci.product_id = p.id JOIN carts c ON ci.cart_id = c.id WHERE ci.id = :item_id AND c.user_id = :user_id");
            $stmt_check_owner->execute([':item_id' => $item_id, ':user_id' => $_SESSION['user_id']]);
            $item_info = $stmt_check_owner->fetch(PDO::FETCH_ASSOC);

            if ($item_info) {
                if ($new_quantity <= 0) {
                    // Supprimer si la quantité est 0 ou moins
                    $stmt_delete_item = $conn->prepare("DELETE FROM cart_items WHERE id = :item_id");
                    $stmt_delete_item->execute([':item_id' => $item_id]);
                    $message = "Produit retiré du panier.";
                } elseif ($new_quantity > $item_info['stock']) {
                    $message = "Quantité demandée supérieure au stock disponible (" . $item_info['stock'] . ").";
                } else {
                    // Mettre à jour la quantité
                    $stmt_update_qty = $conn->prepare("UPDATE cart_items SET quantity = :new_quantity WHERE id = :item_id");
                    $stmt_update_qty->execute([':new_quantity' => $new_quantity, ':item_id' => $item_id]);
                    $message = "Quantité mise à jour.";
                }
            } else {
                $message = "Article non trouvé dans votre panier.";
            }
            redirect('cart.php'); // Recharger pour afficher les changements
        } elseif (isset($_POST['remove_item']) && isset($_POST['item_id'])) {
            $item_id = (int)$_POST['item_id'];

            // Vérifier que l'item appartient bien au panier de l'utilisateur
            $stmt_check_owner = $conn->prepare("SELECT ci.id FROM cart_items ci JOIN carts c ON ci.cart_id = c.id WHERE ci.id = :item_id AND c.user_id = :user_id");
            $stmt_check_owner->execute([':item_id' => $item_id, ':user_id' => $_SESSION['user_id']]);

            if ($stmt_check_owner->fetch()) {
                $stmt_delete_item = $conn->prepare("DELETE FROM cart_items WHERE id = :item_id");
                $stmt_delete_item->execute([':item_id' => $item_id]);
                $message = "Produit retiré du panier.";
            } else {
                $message = "Article non trouvé dans votre panier.";
            }
            redirect('cart.php'); // Recharger pour afficher les changements
        }
    }

} catch (PDOException $e) {
    $message = "Erreur du panier : " . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre Panier - LuxWatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">
    
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        .cart-items-container {
            margin-top: 30px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            background-color: var(--noir-profond);
            border: 1px solid var(--gris-moyen);
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-right: 20px;
            border-radius: 5px;
        }
        .cart-item-details {
            flex-grow: 1;
        }
        .cart-item-details h3 {
            text-align: left;
            margin: 0 0 10px 0;
            color: var(--blanc-pur);
            font-size: 1.4rem;
            text-transform: none;
            letter-spacing: normal;
        }
        .cart-item-details p {
            margin: 5px 0;
            color: var(--gris-clair);
        }
        .cart-item-details .price {
            font-weight: bold;
            color: var(--accent-or);
        }
        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .cart-item-actions input[type="number"] {
            width: 60px;
            padding: 8px;
            text-align: center;
            background-color: var(--gris-fonce);
            border: 1px solid var(--gris-moyen);
            color: var(--blanc-pur);
            border-radius: 4px;
        }
        .cart-item-actions button {
            background-color: var(--accent-rouge);
            color: var(--blanc-pur);
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .cart-item-actions button:hover {
            background-color: #990000;
        }
        .cart-summary {
            background-color: var(--gris-fonce);
            border: 1px solid var(--gris-moyen);
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            text-align: right;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .cart-summary h2 {
            text-align: right;
            color: var(--blanc-pur);
            margin-top: 0;
            margin-bottom: 15px;
        }
        .cart-summary .total {
            font-size: 2rem;
            font-weight: bold;
            color: var(--accent-or);
            margin-bottom: 20px;
        }
        .cart-summary button.checkout-btn {
            background-color: var(--accent-or);
            color: var(--noir-profond);
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: bold;
            text-transform: uppercase;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .cart-summary button.checkout-btn:hover {
            background-color: var(--blanc-pur);
            color: var(--noir-profond);
        }
        @media (max-width: 600px) {
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .cart-item img {
                margin-bottom: 15px;
            }
            .cart-item-details h3, .cart-item-details p {
                text-align: center;
            }
            .cart-item-actions {
                width: 100%;
                justify-content: center;
                margin-top: 15px;
            }
            .cart-summary {
                text-align: center;
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
        <h1 data-aos="fade-up">Votre Panier</h1>

        <?php if ($message): ?>
            <p class="message-<?php echo (strpos($message, 'Erreur') !== false || strpos($message, 'non trouvé') !== false || strpos($message, 'supérieure') !== false) ? 'error' : 'success'; ?>" data-aos="fade-up"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if (count($cart_items) > 0): ?>
            <div class="cart-items-container" data-aos="fade-up" data-aos-delay="200">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="cart-item-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p>Prix unitaire : <span class="price"><?php echo number_format($item['price'], 2, ',', ' '); ?> €</span></p>
                            <p>Total : <span class="price"><?php echo number_format($item['quantity'] * $item['price'], 2, ',', ' '); ?> €</span></p>
                        </div>
                        <div class="cart-item-actions">
                            <form action="cart.php" method="POST" style="display: flex; gap: 5px;">
                                <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item['item_id']); ?>">
                                <input type="number" name="new_quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="0" max="<?php echo htmlspecialchars($item['stock']); ?>">
                                <button type="submit" name="update_quantity">Mettre à jour</button>
                            </form>
                            <form action="cart.php" method="POST">
                                <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item['item_id']); ?>">
                                <button type="submit" name="remove_item">Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary" data-aos="fade-up" data-aos-delay="400">
                <h2>Total du panier</h2>
                <p class="total"><?php echo number_format($total_cart_amount, 2, ',', ' '); ?> €</p>
                <form action="checkout.php" method="GET"> <button type="submit" class="checkout-btn">Passer la commande</button>
                </form>
            </div>
        <?php else: ?>
            <p data-aos="fade-up" style="text-align: center;">Votre panier est vide.</p>
            <p data-aos="fade-up" style="text-align: center;"><a href="products.php">Découvrez nos collections !</a></p>
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