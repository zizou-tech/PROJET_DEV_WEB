<?php
session_start();
require_once 'includes/db_connect.php'; // Cette page interagit avec la DB
require_once 'includes/functions.php';

// Rediriger si non connecté
if (!isAuthenticated()) {
    redirect('login.php');
}

$message = '';
$appointments = [];

try {
    // Récupérer les rendez-vous de l'utilisateur connecté
    $stmt = $conn->prepare("SELECT id, appointment_date, message, created_at FROM appointments WHERE user_id = :user_id ORDER BY appointment_date");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gérer la création de rendez-vous
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_date']) && !isset($_POST['delete_appointment_id'])) {
        $appointment_date = $_POST['appointment_date'];
        $message_content = isset($_POST['message']) ? trim($_POST['message']) : null; // Message optionnel

        if (empty($appointment_date)) {
            $message = "La date et l'heure du rendez-vous sont requises.";
        } else {
            // Vérifier si la date est valide et future
            $current_time = new DateTime();
            $selected_time = new DateTime($appointment_date);
            if ($selected_time < $current_time) {
                $message = "La date et l'heure du rendez-vous doivent être dans le futur.";
            } else {
                $stmt = $conn->prepare("INSERT INTO appointments (user_id, appointment_date, message) VALUES (:user_id, :appointment_date, :message)");
                $stmt->execute([
                    ':user_id' => $_SESSION['user_id'],
                    ':appointment_date' => $appointment_date,
                    ':message' => $message_content
                ]);
                $message = "Rendez-vous créé avec succès !";
                redirect('appointments.php'); // Recharger pour afficher la mise à jour
            }
        }
    }

    // Gérer la suppression de rendez-vous
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_appointment_id'])) {
        $appointment_id_to_delete = $_POST['delete_appointment_id'];
        $stmt_delete = $conn->prepare("DELETE FROM appointments WHERE id = :id AND user_id = :user_id");
        $stmt_delete->execute([':id' => $appointment_id_to_delete, ':user_id' => $_SESSION['user_id']]);
        $message = "Rendez-vous supprimé avec succès.";
        redirect('appointments.php'); // Recharger pour afficher la mise à jour
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
    <title>Mes Rendez-vous - LuxWatch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">
    
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* Styles spécifiques pour la page des rendez-vous */
        .appointment-form {
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid var(--gris-moyen);
            border-radius: 8px;
            background-color: var(--noir-profond);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .appointment-list {
            margin-top: 40px;
        }

        .appointment-item {
            background-color: var(--noir-profond);
            border: 1px solid var(--gris-moyen);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .appointment-item p {
            margin-bottom: 8px;
        }
        .appointment-item p strong {
            color: var(--blanc-pur);
        }
        .delete-appointment-btn {
            background-color: var(--accent-rouge);
            color: var(--blanc-pur);
            border: none;
            padding: 8px 15px;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 10px;
            transition: background-color 0.3s ease;
        }
        .delete-appointment-btn:hover {
            background-color: #990000;
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
        <h1 data-aos="fade-up">Mes Rendez-vous</h1>

        <?php if ($message): ?>
            <p class="message-<?php echo (strpos($message, 'Erreur') !== false || strpos($message, 'requises') !== false || strpos($message, 'futur') !== false) ? 'error' : 'success'; ?>" data-aos="fade-up"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <div class="appointment-form" data-aos="zoom-in" data-aos-delay="200">
            <h2>Prendre un rendez-vous</h2>
            <form action="appointments.php" method="POST">
                <label for="appointment_date">Date et heure du rendez-vous :</label>
                <input type="datetime-local" id="appointment_date" name="appointment_date" required>

                <label for="message">Message (optionnel) :</label>
                <textarea id="message" name="message" rows="3"></textarea>

                <button type="submit">Réserver</button>
            </form>
        </div>

        <div class="appointment-list" data-aos="fade-up" data-aos-delay="300">
            <h2>Mes Rendez-vous Prévus</h2>
            <?php if (count($appointments) > 0): ?>
                <?php foreach ($appointments as $appointment): ?>
                    <div class="appointment-item">
                        <p><strong>Date et heure :</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($appointment['appointment_date']))); ?></p>
                        <?php if ($appointment['message']): ?>
                            <p><strong>Message :</strong> <?php echo nl2br(htmlspecialchars($appointment['message'])); ?></p>
                        <?php endif; ?>
                        <p><strong>Créé le :</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($appointment['created_at']))); ?></p>
                         <form action="appointments.php" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce rendez-vous ?');">
                            <input type="hidden" name="delete_appointment_id" value="<?php echo htmlspecialchars($appointment['id']); ?>">
                            <button type="submit" class="delete-appointment-btn">Supprimer</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Vous n'avez aucun rendez-vous prévu.</p>
            <?php endif; ?>
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