<?php
session_start();
require_once 'includes/db_connect.php'; // Cette page interagit avec la DB
require_once 'includes/functions.php';

// Rediriger si non connecté ou panier vide
if (!isAuthenticated()) {
    redirect('login.php?redirect=checkout.php');
}

$cart_items = [];
$total_amount = 0;
$message = '';
$can_checkout = false;

try {
    // 1. Récupérer les articles du panier
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

        if (count($cart_items) === 0) {
            $message = "Votre panier est vide. Veuillez ajouter des produits avant de commander.";
        } else {
            foreach ($cart_items as $item) {
                if ($item['quantity'] > $item['stock']) {
                    $message = "Erreur : La quantité de " . htmlspecialchars($item['name']) . " dans votre panier (" . htmlspecialchars($item['quantity']) . ") dépasse le stock disponible (" . htmlspecialchars($item['stock']) . "). Veuillez ajuster votre panier.";
                    $can_checkout = false;
                    break;
                }
                $total_amount += ($item['quantity'] * $item['price']);
                $can_checkout = true;
            }
        }
    } else {
        $message = "Votre panier est vide.";
    }

    // 2. Traitement de la commande (quand le formulaire est soumis)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order']) && $can_checkout) {
        $shipping_address = trim($_POST['shipping_address']);
        $payment_method = trim($_POST['payment_method']);

        if (empty($shipping_address) || empty($payment_method)) {
            $message = "Veuillez remplir l'adresse de livraison et le mode de paiement.";
        } else {
            $conn->beginTransaction(); // Démarre une transaction pour s'assurer que tout réussit ou échoue

            try {
                // Insérer la commande principale
                $stmt_order = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, status) VALUES (:user_id, :total_amount, :shipping_address, :payment_method, 'pending')");
                $stmt_order->execute([
                    ':user_id' => $_SESSION['user_id'],
                    ':total_amount' => $total_amount,
                    ':shipping_address' => $shipping_address,
                    ':payment_method' => $payment_method
                ]);
                $order_id = $conn->lastInsertId();

                // Insérer les articles de la commande et décrémenter le stock
                foreach ($cart_items as $item) {
                    $stmt_order_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (:order_id, :product_id, :quantity, :price_at_purchase)");
                    $stmt_order_item->execute([
                        ':order_id' => $order_id,
                        ':product_id' => $item['product_id'],
                        ':quantity' => $item['quantity'],
                        ':price_at_purchase' => $item['price']
                    ]);

                    // Décrémenter le stock du produit
                    $stmt_update_stock = $conn->prepare("UPDATE products SET stock = stock - :quantity WHERE id = :product_id");
                    $stmt_update_stock->execute([':quantity' => $item['quantity'], ':product_id' => $item['product_id']]);
                }

                // Vider le panier de l'utilisateur
                $stmt_clear_cart = $conn->prepare("DELETE FROM cart_items WHERE cart_id = :cart_id");
                $stmt_clear_cart->execute([':cart_id' => $cart_id]);

                $conn->commit(); // Confirme toutes les opérations si tout s'est bien passé
                redirect('order_confirmation.php?order_id=' . $order_id);

            } catch (PDOException $e) {
                $conn->rollBack(); // Annule toutes les opérations si une erreur survient
                $message = "Erreur lors du traitement de la commande : " . htmlspecialchars($e->getMessage());
            }
        }
    }

} catch (PDOException $e) {
    $message = "Erreur de préparation de la commande : " . htmlspecialchars($e->getMessage());
    $can_checkout = false;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commander - LuxWatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">
    
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        .checkout-container {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            margin-top: 30px;
        }
        .checkout-details {
            flex: 2;
            min-width: 300px;
            background-color: var(--noir-profond);
            padding: 30px;
            border-radius: 10px;
            border: 1px solid var(--gris-moyen);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .checkout-summary-section {
            flex: 1;
            min-width: 250px;
            background-color: var(--noir-profond);
            padding: 30px;
            border-radius: 10px;
            border: 1px solid var(--gris-moyen);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .checkout-details h2, .checkout-summary-section h2 {
            text-align: left;
            margin-bottom: 25px;
            color: var(--blanc-pur);
        }
        .checkout-details form {
            max-width: none; /* Override default form width */
            padding: 0;
            border: none;
            background-color: transparent;
            box-shadow: none;
        }
        .checkout-summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 1.1rem;
            color: var(--gris-clair);
        }
        .checkout-summary-item .item-price {
            font-weight: bold;
            color: var(--accent-or);
        }
        .checkout-summary-total {
            border-top: 1px solid var(--gris-moyen);
            padding-top: 15px;
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--blanc-pur);
        }
        .checkout-summary-total .total-price {
            color: var(--accent-or);
        }
        .checkout-btn {
            width: 100%;
            margin-top: 30px;
            padding: 15px 0;
            font-size: 1.2rem;
            background-color: var(--accent-or);
            color: var(--noir-profond);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-transform: uppercase;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .checkout-btn:hover {
            background-color: var(--blanc-pur);
            color: var(--noir-profond);
        }
        @media (max-width: 768px) {
            .checkout-container {
                flex-direction: column;
            }
            .checkout-details, .checkout-summary-section {
                min-width: unset;
                flex: none;
                width: 100%;
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
        <h1 data-aos="fade-up">Finaliser votre Commande</h1>

        <?php if ($message): ?>
            <p class="message-<?php echo (strpos($message, 'Erreur') !== false || strpos($message, 'vide') !== false || strpos($message, 'ajuster') !== false) ? 'error' : 'success'; ?>" data-aos="fade-up"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if (count($cart_items) > 0 && $can_checkout): ?>
            <div class="checkout-container">
                <div class="checkout-details" data-aos="fade-right" data-aos-duration="1000">
                    <h2>Informations de Livraison et Paiement</h2>
                    <form action="checkout.php" method="POST">
                        <label for="shipping_address">Adresse de Livraison :</label>
                        <textarea id="shipping_address" name="shipping_address" rows="5" required placeholder="Rue, Code Postal, Ville, Pays"></textarea>

                        <label for="payment_method">Méthode de Paiement :</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="">-- Sélectionnez une méthode --</option>
                            <option value="Carte Bancaire">Carte Bancaire (simulation)</option>
                            <option value="Virement Bancaire">Virement Bancaire (simulation)</option>
                        </select>

                        <button type="submit" name="place_order" class="checkout-btn">Confirmer et Payer</button>
                    </form>
                </div>

                <div class="checkout-summary-section" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
                    <h2>Récapitulatif de votre commande</h2>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="checkout-summary-item">
                            <span><?php echo htmlspecialchars($item['name']); ?> (x<?php echo htmlspecialchars($item['quantity']); ?>)</span>
                            <span class="item-price"><?php echo number_format($item['quantity'] * $item['price'], 2, ',', ' '); ?> €</span>
                        </div>
                    <?php endforeach; ?>
                    <div class="checkout-summary-total">
                        <span>Total à payer :</span>
                        <span class="total-price"><?php echo number_format($total_amount, 2, ',', ' '); ?> €</span>
                    </div>
                </div>
            </div>
        <?php elseif (!empty($message)): ?>
            <p data-aos="fade-up" style="text-align: center;"><a href="products.php">Retour aux Collections pour ajouter des produits.</a></p>
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