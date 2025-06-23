 Responsabilités & Contributions
J'ai été responsable de la mise en place complète de l'infrastructure de la base de données ainsi que du développement du système d’authentification des utilisateurs. J'ai également conçu la logique backend permettant des interactions simples et efficaces avec les utilisateurs.

 Contributions Techniques
1.  Conception & Création de la Base de Données (MySQL)
J'ai conçu et implémenté la structure complète de la base de données monsite_db, essentielle au fonctionnement du site.

 Tables Principales :

    -users : comptes utilisateurs (clients et admins), mots de passe hachés, rôles.

    -contacts : messages envoyés via le formulaire de contact.

    -appointments : rendez-vous pris par les utilisateurs.

    -products : montres de luxe (nom, prix, description, image, stock).

    -carts, cart_items : gestion des paniers.

    -orders, order_items : commandes et leurs contenus.

    voici un apercu des relation entre les tables : ![mcd_monsite_db](https://github.com/user-attachments/assets/b284372f-d0c0-49b9-a27f-ab13ae2da99d)


  Relations & Intégrité :

    -Mise en place de clés étrangères pour assurer la cohérence des données (ex. un rendez-vous lié à un utilisateur).

  Données Initiales :

    -Insertion des données de base : utilisateur admin par défaut, exemples de montres, etc.

  Maintenance SQL :

    -Requêtes pour mise à jour d’images, réinitialisation de mot de passe, etc.

2.  Système d’Authentification (PHP)
Développement d’un système sécurisé de gestion des comptes utilisateur, avec protections contre les failles classiques (injections SQL, etc.).

   Connexion à la BDD :
     - includes/db_connect.php → Connexion sécurisée via PDO.

    Fonctions Utiles :
     includes/functions.php → Fonctions réutilisables :

        - redirect()

        - isAuthenticated()

        - isAdmin()

    - Inscription / Connexion / Déconnexion :

        - register.php → Inscription + hachage des mots de passe (password_hash()).

        - login.php → Connexion + vérification (password_verify()), gestion des sessions ($_SESSION).

        - logout.php → Déconnexion sécurisée.

   - Gestion des Accès :

        - Redirections selon le statut de l'utilisateur (authentifié / admin).

        - admin.php → Gestion des utilisateurs via interface admin.

    3.  Logique Backend des Interactions Simples :
   
      -Développement des modules pour faciliter les interactions utilisateur.

      -contact.php → Traitement du formulaire de contact (insertion en BDD).

      -appointments.php → Prise de rendez-vous (utilisateur).

      -admin_appointments.php → Gestion des rendez-vous (admin).

 Compétences Mises en Œuvre
  - Conception de bases de données relationnelles (MySQL)

  - Développement backend (PHP natif)

  - Sécurité des applications web (authentification, hachage, PDO, sessions)

  - Intégrité des données (relations entre tables, contraintes)

  - Débogage & gestion des erreurs backend

 Tester le Projet en Local:

  -Lancer XAMPP (Apache + MySQL).

  -Accéder à PHPMyAdmin : http://localhost/phpmyadmin.

  -Créer la base de données monsite_db (ou importer le fichier SQL fourni).

  -Vérifier la configuration de la BDD dans includes/db_connect.php.

  -Utiliser ces identifiants pour se connecter en tant qu’admin :

      -Email : admin@example.com

      -Mot de passe : kiko123
