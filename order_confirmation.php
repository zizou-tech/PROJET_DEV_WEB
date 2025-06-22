<?php
session_start();
require_once 'includes/db_connect.php'; // Cette page interagit avec la DB
require_once 'includes/functions.php';

// Rediriger si non connecté ou si pas d'ID de commande
if (!isAuthenticated() || !isset($_GET['order_id'])) {
    redirect('index.php');
}

$order_id = (int)$_GET['order_id'];
$order = null;
$order_items = [];
$message = '';

try {
    // Récupérer les détails de la commande
    $stmt_order = $conn->prepare("SELECT id, total_amount, status, shipping_address, payment_method, order_date FROM orders WHERE id = :id AND user_id = :user_id");
    $stmt_order->execute([':id' => $order_id, ':user_id' => $_SESSION['user_id']]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        // Récupérer les articles de la commande
        $stmt_items = $conn->prepare("SELECT oi.quantity, oi.price_at_purchase, p.name, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :order_id");
        $stmt_items->execute([':order_id' => $order_id]);
        $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $message = "Commande non trouvée ou vous n'avez pas les droits d'accès.";
    }

} catch (PDOException $e) {
    $message = "Erreur lors du chargement de la confirmation : " . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de Commande - LuxWatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">
    
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        .confirmation-box {
            background-color: var(--noir-profond);
            border: 1px solid var(--gris-moyen);
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            margin: 50px auto;
        }
        .confirmation-box h1 {
            color: var(--accent-or);
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        .confirmation-box p {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: var(--gris-clair);
        }
        .order-details-summary {
            text-align: left;
            margin-top: 30px;
            border-top: 1px solid var(--gris-moyen);
            padding-top: 20px;
        }
        .order-details-summary h2 {
            text-align: left;
            color: var(--blanc-pur);
            margin-bottom: 20px;
        }
        .order-item-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .order-item-list li {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed var(--gris-moyen);
            color: var(--gris-clair);
        }
        .order-item-list li:last-child {
            border-bottom: none;
        }
        .order-item-list li .item-name {
            font-weight: bold;
            color: var(--blanc-pur);
        }
        .order-item-list li .item-price {
            color: var(--accent-or);
            font-weight: bold;
        }
        .total-amount-display {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--accent-or);
            text-align: right;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid var(--accent-or);
        }
        .return-home-btn {
            background-color: var(--accent-or);
            color: var(--noir-profond);
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 40px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .return-home-btn:hover {
            background-color: var(--blanc-pur);
            color: var(--noir-profond);
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
            <p class="message-error" data-aos="fade-up"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if ($order): ?>
            <div class="confirmation-box" data-aos="zoom-in" data-aos-duration="1000">
                <h1>Commande Confirmée !</h1>
                <p>Merci pour votre achat, **<?php echo htmlspecialchars($_SESSION['username']); ?>** !</p>
                <p>Votre commande **#<?php echo htmlspecialchars($order['id']); ?>** a été placée avec succès.</p>
                <p>Statut actuel : **<?php echo htmlspecialchars(ucfirst($order['status'])); ?>**</p>
                <p>Date de commande : **<?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($order['order_date']))); ?>**</p>

                <div class="order-details-summary">
                    <h2>Détails de la Commande</h2>
                    <ul class="order-item-list">
                        <?php foreach ($order_items as $item): ?>
                            <li>
                                <span class="item-name"><?php echo htmlspecialchars($item['name']); ?> (x<?php echo htmlspecialchars($item['quantity']); ?>)</span>
                                <span class="item-price"><?php echo number_format($item['quantity'] * $item['price_at_purchase'], 2, ',', ' '); ?> €</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="total-amount-display">
                        <span>Total Final :</span>
                        <span class="total-price"><?php echo number_format($order['total_amount'], 2, ',', ' '); ?> €</span>
                    </div>
                    <p style="margin-top: 20px;">Votre commande sera expédiée à :<br>
                    **<?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>**</p>
                    <p>Méthode de paiement : **<?php echo htmlspecialchars($order['payment_method']); ?>**</p>
                </div>
                <a href="index.php" class="return-home-btn" data-aos="fade-up" data-aos-delay="600">Retour à l'Accueil</a>
            </div>
        <?php else: ?>
            <div class="confirmation-box" data-aos="fade-up">
                <h1>Erreur de Confirmation</h1>
                <p>Impossible de charger les détails de la commande.</p>
                <a href="index.php" class="return-home-btn">Retour à l'Accueil</a>
            </div>
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