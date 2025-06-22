<?php
session_start();
require_once 'includes/db_connect.php'; // Cette page interagit avec la DB
require_once 'includes/functions.php';

$message_status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $msg_content = trim($_POST['message']); // Renommé pour éviter conflit avec variable globale

    if (empty($name) || empty($email) || empty($msg_content)) {
        $message_status = "Tous les champs obligatoires (Nom, Email, Message) doivent être remplis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message_status = "Adresse email invalide.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':subject' => $subject,
                ':message' => $msg_content
            ]);
            $message_status = "Votre message a été envoyé avec succès !";
        } catch (PDOException $e) {
            $message_status = "Erreur lors de l'envoi du message : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - LuxWatch</title>
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
        <h1 data-aos="fade-up">Contactez-nous</h1>
        <?php if ($message_status): ?>
            <p class="message-<?php echo (strpos($message_status, 'Erreur') !== false || strpos($message_status, 'invalide') !== false || strpos($message_status, 'remplis') !== false) ? 'error' : 'success'; ?>" data-aos="fade-up"><?php echo htmlspecialchars($message_status); ?></p>
        <?php endif; ?>
        <form action="contact.php" method="POST" data-aos="zoom-in" data-aos-delay="200">
            <label for="name">Votre Nom :</label>
            <input type="text" id="name" name="name" required>

            <label for="email">Votre Email :</label>
            <input type="email" id="email" name="email" required>

            <label for="subject">Sujet (optionnel) :</label>
            <input type="text" id="subject" name="subject">

            <label for="message">Votre Message :</label>
            <textarea id="message" name="message" rows="5" required></textarea>

            <button type="submit">Envoyer le message</button>
        </form>
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