<?php
session_start();
require_once 'includes/db_connect.php'; // Cette page interagit avec la DB
require_once 'includes/functions.php';

// Rediriger si non admin
if (!isAdmin()) {
    redirect('index.php');
}

$message = '';
$orders = [];

try {
    // Récupérer toutes les commandes avec les infos utilisateur
    $stmt = $conn->query("SELECT o.id, o.total_amount, o.status, o.order_date, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gérer la mise à jour du statut de commande
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
        $order_id = (int)$_POST['order_id'];
        $new_status = trim($_POST['new_status']);

        $allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (in_array($new_status, $allowed_statuses)) {
            $stmt_update = $conn->prepare("UPDATE orders SET status = :new_status, updated_at = CURRENT_TIMESTAMP WHERE id = :order_id");
            $stmt_update->execute([':new_status' => $new_status, ':order_id' => $order_id]);
            $message = "Statut de la commande #{$order_id} mis à jour en '" . htmlspecialchars($new_status) . "'.";
        } else {
            $message = "Statut non valide.";
        }
        redirect('admin_orders.php'); // Recharger
    }

    // Gérer la suppression de commande
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order_id'])) {
        $order_id_to_delete = (int)$_POST['delete_order_id'];
        // Suppression cascade par la BDD (FOREIGN KEY ON DELETE CASCADE)
        $stmt_delete = $conn->prepare("DELETE FROM orders WHERE id = :id");
        $stmt_delete->execute([':id' => $order_id_to_delete]);
        $message = "Commande supprimée avec succès.";
        redirect('admin_orders.php'); // Recharger
    }

} catch (PDOException $e) {
    $message = "Erreur : " . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Commandes (Admin) - LuxWatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">
    
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        .order-status-select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid var(--gris-moyen);
            background-color: var(--noir-profond);
            color: var(--blanc-pur);
        }
        .order-status-select option {
            background-color: var(--noir-profond);
            color: var(--blanc-pur);
        }
        .status-update-btn {
            background-color: #28a745; /* Vert */
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 3px;
        }
        .status-update-btn:hover {
            background-color: #218838;
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
        <h1 data-aos="fade-up">Gestion des Commandes</h1>

        <?php if ($message): ?>
            <p class="message-<?php echo (strpos($message, 'Erreur') !== false || strpos($message, 'valide') !== false) ? 'error' : 'success'; ?>" data-aos="fade-up"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <div class="order-list-table" data-aos="fade-up" data-aos-delay="200">
            <table>
                <thead>
                    <tr>
                        <th>ID Commande</th>
                        <th>Utilisateur</th>
                        <th>Email Utilisateur</th>
                        <th>Montant Total</th>
                        <th>Statut</th>
                        <th>Date Commande</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><?php echo htmlspecialchars($order['email']); ?></td>
                                <td><?php echo number_format($order['total_amount'], 2, ',', ' '); ?> €</td>
                                <td>
                                    <form action="admin_orders.php" method="POST" style="display: flex; align-items: center; gap: 5px;">
                                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
                                        <select name="new_status" class="order-status-select">
                                            <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>En attente</option>
                                            <option value="processing" <?php echo ($order['status'] == 'processing') ? 'selected' : ''; ?>>En traitement</option>
                                            <option value="shipped" <?php echo ($order['status'] == 'shipped') ? 'selected' : ''; ?>>Expédiée</option>
                                            <option value="delivered" <?php echo ($order['status'] == 'delivered') ? 'selected' : ''; ?>>Livrée</option>
                                            <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Annulée</option>
                                        </select>
                                        <button type="submit" name="update_order_status" class="status-update-btn">Màj</button>
                                    </form>
                                </td>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($order['order_date']))); ?></td>
                                <td>
                                    <form action="admin_orders.php" method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette commande ?');">
                                        <input type="hidden" name="delete_order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
                                        <button type="submit" class="delete-btn">Supprimer</button>
                                    </form>
                                    </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">Aucune commande trouvée.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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