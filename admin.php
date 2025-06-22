<?php
session_start();
require_once 'includes/db_connect.php'; // Cette page interagit avec la DB
require_once 'includes/functions.php';

// Rediriger si non admin
if (!isAdmin()) {
    redirect('index.php'); // Ou vers login.php avec un message d'erreur
}

$users = [];
$contacts = [];
$admin_message = '';

try {
    // Récupérer tous les utilisateurs (sauf le mot de passe)
    $stmt_users = $conn->query("SELECT id, username, email, role, created_at FROM users");
    $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer tous les messages de contact
    $stmt_contacts = $conn->query("SELECT id, name, email, subject, message, created_at FROM contacts ORDER BY created_at DESC");
    $contacts = $stmt_contacts->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $admin_message = "Erreur lors de la récupération des données : " . $e->getMessage();
}

// Gestion de la suppression d'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $user_id_to_delete = $_POST['delete_user_id'];
    try {
        $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt_delete->execute([':id' => $user_id_to_delete]);
        $admin_message = "Utilisateur supprimé avec succès.";
        redirect('admin.php'); // Recharger la page pour afficher la mise à jour
    } catch (PDOException $e) {
        $admin_message = "Erreur lors de la suppression de l'utilisateur : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - LuxWatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">
    
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* Styles spécifiques à l'admin pour la lisibilité */
        /* Les styles pour table et boutons sont déjà dans style.css */
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
        <h1 data-aos="fade-up">Panneau d'Administration</h1>

        <?php if ($admin_message): ?>
            <p class="message-<?php echo (strpos($admin_message, 'Erreur') !== false) ? 'error' : 'success'; ?>" data-aos="fade-up"><?php echo htmlspecialchars($admin_message); ?></p>
        <?php endif; ?>

        <h2 data-aos="fade-up" data-aos-delay="100">Gestion des Utilisateurs</h2>
        <div data-aos="fade-up" data-aos-delay="200">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom d'utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Date d'inscription</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($user['created_at']))); ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): // Empêche l'admin de se supprimer lui-même ?>
                                        <form action="admin.php" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                            <input type="hidden" name="delete_user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                            <button type="submit" class="delete-btn">Supprimer</button>
                                        </form>
                                    <?php else: ?>
                                        (Votre compte)
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">Aucun utilisateur trouvé.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h2 data-aos="fade-up" data-aos-delay="300">Messages de Contact</h2>
        <div data-aos="fade-up" data-aos-delay="400">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Sujet</th>
                        <th>Message</th>
                        <th>Date d'envoi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($contacts) > 0): ?>
                        <?php foreach ($contacts as $contact): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($contact['id']); ?></td>
                                <td><?php echo htmlspecialchars($contact['name']); ?></td>
                                <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                <td><?php echo htmlspecialchars($contact['subject']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($contact['message'])); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($contact['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">Aucun message de contact trouvé.</td></tr>
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