<?php
session_start();
require_once 'includes/db_connect.php'; // Cette page interagit avec la DB
require_once 'includes/functions.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "Tous les champs sont requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse email invalide.";
    } elseif ($password !== $confirm_password) {
        $message = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 6) {
        $message = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        try {
            // Vérifier si l'email ou le nom d'utilisateur existe déjà
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1");
            $stmt->execute([':email' => $email, ':username' => $username]);
            if ($stmt->fetch()) {
                $message = "Cet email ou nom d'utilisateur est déjà utilisé.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hachage du mot de passe

                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
                $stmt->execute([':username' => $username, ':email' => $email, ':password' => $hashed_password]);

                $message = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                // Optionnel: Rediriger vers la page de connexion après inscription
                // redirect('login.php');
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de l'inscription : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - LuxWatch</title>
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
        <h1 data-aos="fade-up">Inscription</h1>
        <?php if ($message): ?>
            <p class="message-<?php echo (strpos($message, 'requis') !== false || strpos($message, 'invalide') !== false || strpos($message, 'correspondent') !== false || strpos($message, 'utilisé') !== false || strpos($message, 'Erreur') !== false) ? 'error' : 'success'; ?>" data-aos="fade-up"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form action="register.php" method="POST" data-aos="zoom-in" data-aos-delay="200">
            <label for="username">Nom d'utilisateur :</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Email :</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Confirmer mot de passe :</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit">S'inscrire</button>
        </form>
        <p data-aos="fade-up" data-aos-delay="300" style="text-align: center;">Déjà un compte ? <a href="login.php">Connectez-vous ici</a>.</p>
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