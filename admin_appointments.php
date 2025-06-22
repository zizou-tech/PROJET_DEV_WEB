<?php
session_start();
require_once 'includes/db_connect.php'; 
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est administrateur
if (!isAdmin()) {
    redirect('index.php');
}

$message = '';
$appointments = [];

try {
    // Vérifier si l'utilisateur est authentifié
    $stmt = $conn->prepare("SELECT appointments.id, appointments.appointment_date, appointments.message, appointments.created_at, users.username, users.email
                           FROM appointments
                           INNER JOIN users ON appointments.user_id = users.id
                           ORDER BY appointments.appointment_date");
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gérer la suppression d'un rendez-vous
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_appointment_id'])) {
        $appointment_id_to_delete = $_POST['delete_appointment_id'];
        $stmt_delete = $conn->prepare("DELETE FROM appointments WHERE id = :id");
        $stmt_delete->execute([':id' => $appointment_id_to_delete]);
        $message = "Rendez-vous supprimé avec succès.";
        redirect('admin_appointments.php'); 
    }

} catch (PDOException $e) {
    $message = "Erreur : " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Rendez-vous (Admin) - LuxWatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">
    
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* Styles spécifiques pour la page d'administration des rendez-vous */
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
        <h1 data-aos="fade-up">Gestion des Rendez-vous (Admin)</h1>

        <?php if ($message): ?>
            <p class="message-<?php echo (strpos($message, 'Erreur') !== false) ? 'error' : 'success'; ?>" data-aos="fade-up"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <div data-aos="fade-up" data-aos-delay="200">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilisateur</th>
                        <th>Email Utilisateur</th>
                        <th>Date et heure</th>
                        <th>Message</th>
                        <th>Créé le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($appointments) > 0): ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['id']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['username']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['email']); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($appointment['appointment_date']))); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($appointment['message'])); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($appointment['created_at']))); ?></td>
                                <td>
                                    <form action="admin_appointments.php" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce rendez-vous ?');">
                                        <input type="hidden" name="delete_appointment_id" value="<?php echo htmlspecialchars($appointment['id']); ?>">
                                        <button type="submit" class="delete-btn">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">Aucun rendez-vous trouvé.</td></tr>
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