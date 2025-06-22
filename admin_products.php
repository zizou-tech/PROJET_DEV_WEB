<?php
session_start();
require_once 'includes/db_connect.php'; // Cette page interagit avec la DB
require_once 'includes/functions.php';

// Rediriger si non admin
if (!isAdmin()) {
    redirect('index.php');
}

$message = '';
$products = [];
$edit_product = null;

try {
    // Récupérer tous les produits
    $stmt = $conn->query("SELECT id, name, description, price, image_url, category, stock FROM products ORDER BY name");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gérer l'ajout/édition de produit
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_product']) || isset($_POST['edit_product']))) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price'];
        $image_url = trim($_POST['image_url']);
        $category = trim($_POST['category']);
        $stock = (int)$_POST['stock'];

        if (empty($name) || empty($price) || empty($image_url) || empty($category)) {
            $message = "Nom, Prix, URL Image et Catégorie sont requis.";
        } elseif ($price <= 0) {
            $message = "Le prix doit être un nombre positif.";
        } elseif ($stock < 0) {
            $message = "Le stock ne peut pas être négatif.";
        } else {
            if (isset($_POST['add_product'])) {
                $stmt_insert = $conn->prepare("INSERT INTO products (name, description, price, image_url, category, stock) VALUES (:name, :description, :price, :image_url, :category, :stock)");
                $stmt_insert->execute([':name' => $name, ':description' => $description, ':price' => $price, ':image_url' => $image_url, ':category' => $category, ':stock' => $stock]);
                $message = "Produit ajouté avec succès !";
            } elseif (isset($_POST['edit_product']) && isset($_POST['product_id'])) {
                $product_id = (int)$_POST['product_id'];
                $stmt_update = $conn->prepare("UPDATE products SET name = :name, description = :description, price = :price, image_url = :image_url, category = :category, stock = :stock, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
                $stmt_update->execute([':name' => $name, ':description' => $description, ':price' => $price, ':image_url' => $image_url, ':category' => $category, ':stock' => $stock, ':id' => $product_id]);
                $message = "Produit mis à jour avec succès !";
            }
            redirect('admin_products.php'); // Recharger la page pour rafraîchir la liste
        }
    }

    // Gérer la suppression de produit
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product_id'])) {
        $product_id_to_delete = (int)$_POST['delete_product_id'];
        $stmt_delete = $conn->prepare("DELETE FROM products WHERE id = :id");
        $stmt_delete->execute([':id' => $product_id_to_delete]);
        $message = "Produit supprimé avec succès.";
        redirect('admin_products.php'); // Recharger la page pour rafraîchir la liste
    }

    // Gérer l'affichage du formulaire d'édition
    if (isset($_GET['edit_id'])) {
        $edit_id = (int)$_GET['edit_id'];
        $stmt_edit = $conn->prepare("SELECT id, name, description, price, image_url, category, stock FROM products WHERE id = :id");
        $stmt_edit->execute([':id' => $edit_id]);
        $edit_product = $stmt_edit->fetch(PDO::FETCH_ASSOC);
        if (!$edit_product) {
            $message = "Produit à éditer non trouvé.";
        }
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
    <title>Gestion des Produits (Admin) - LuxWatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">
    
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        .product-form-section {
            background-color: var(--noir-profond);
            border: 1px solid var(--gris-moyen);
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .product-form-section form {
            max-width: none;
            margin: 0;
            padding: 0;
            background-color: transparent;
            border: none;
            box-shadow: none;
        }
        .product-list-table img {
            width: 80px;
            height: auto;
            border-radius: 5px;
        }
        .edit-btn {
            background-color: #007bff; /* Bleu */
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 3px;
            margin-right: 5px;
        }
        .edit-btn:hover {
            background-color: #0056b3;
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
        <h1 data-aos="fade-up">Gestion des Produits</h1>

        <?php if ($message): ?>
            <p class="message-<?php echo (strpos($message, 'Erreur') !== false || strpos($message, 'requis') !== false || strpos($message, 'négatif') !== false) ? 'error' : 'success'; ?>" data-aos="fade-up"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <div class="product-form-section" data-aos="fade-up" data-aos-delay="200">
            <h2><?php echo $edit_product ? 'Modifier un Produit' : 'Ajouter un Nouveau Produit'; ?></h2>
            <form action="admin_products.php" method="POST">
                <?php if ($edit_product): ?>
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($edit_product['id']); ?>">
                <?php endif; ?>

                <label for="name">Nom du Produit :</label>
                <input type="text" id="name" name="name" value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" required>

                <label for="description">Description :</label>
                <textarea id="description" name="description" rows="4"><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>

                <label for="price">Prix :</label>
                <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $edit_product ? htmlspecialchars($edit_product['price']) : ''; ?>" required>

                <label for="image_url">URL de l'Image :</label>
                <input type="url" id="image_url" name="image_url" value="<?php echo $edit_product ? htmlspecialchars($edit_product['image_url']) : ''; ?>" required>

                <label for="category">Catégorie :</label>
                <input type="text" id="category" name="category" value="<?php echo $edit_product ? htmlspecialchars($edit_product['category']) : ''; ?>" required>

                <label for="stock">Stock :</label>
                <input type="number" id="stock" name="stock" min="0" value="<?php echo $edit_product ? htmlspecialchars($edit_product['stock']) : ''; ?>" required>

                <button type="submit" name="<?php echo $edit_product ? 'edit_product' : 'add_product'; ?>">
                    <?php echo $edit_product ? 'Modifier le Produit' : 'Ajouter le Produit'; ?>
                </button>
            </form>
        </div>

        <h2 data-aos="fade-up" data-aos-delay="300">Liste des Produits</h2>
        <div class="product-list-table" data-aos="fade-up" data-aos-delay="400">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Nom</th>
                        <th>Prix</th>
                        <th>Catégorie</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['id']); ?></td>
                                <td><img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo number_format($product['price'], 2, ',', ' '); ?> €</td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td><?php echo htmlspecialchars($product['stock']); ?></td>
                                <td>
                                    <a href="admin_products.php?edit_id=<?php echo htmlspecialchars($product['id']); ?>" class="edit-btn">Éditer</a>
                                    <form action="admin_products.php" method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">
                                        <input type="hidden" name="delete_product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                        <button type="submit" class="delete-btn">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">Aucun produit trouvé.</td></tr>
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