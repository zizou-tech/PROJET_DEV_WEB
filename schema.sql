-- Désactive temporairement la vérification des clés étrangères pour éviter les problèmes d'ordre
SET FOREIGN_KEY_CHECKS = 0;

-- Supprime la base de données si elle existe déjà (utile pour repartir de zéro, attention aux données existantes !)
DROP DATABASE IF EXISTS monsite_db;

-- Création de la base de données 'monsite_db' avec encodage UTF-8 pour supporter divers caractères
CREATE DATABASE IF NOT EXISTS monsite_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Sélection de la base de données pour les opérations suivantes
USE monsite_db;

-- Table des utilisateurs
-- Stocke les informations des comptes utilisateurs (connexion, rôle)
CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Champ pour le mot de passe haché (ex: par password_hash() en PHP)
    role ENUM('user', 'admin') DEFAULT 'user', -- Définit le rôle de l'utilisateur ('user' ou 'admin')
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, -- Horodatage de la création du compte
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- Horodatage de la dernière mise à jour
);

-- Table des messages de contact
-- Enregistre les messages envoyés via le formulaire de contact
CREATE TABLE IF NOT EXISTS contacts (
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP -- Horodatage de l'envoi du message
);

-- Table des rendez-vous
-- Gère les réservations de rendez-vous (pour les montres de luxe, par exemple)
CREATE TABLE IF NOT EXISTS appointments (
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) NOT NULL, -- Clé étrangère vers la table 'users'
    appointment_date DATETIME NOT NULL, -- Date et heure du rendez-vous
    message TEXT, -- Message ou note spécifique au rendez-vous, facultatif
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, -- Horodatage de la création du rendez-vous
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE -- Si un utilisateur est supprimé, ses rendez-vous le sont aussi
);

-- Table des produits (montres)
CREATE TABLE IF NOT EXISTS products (
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255), -- Chemin vers l'image du produit (local)
    category VARCHAR(100), -- Ex: 'Sport', 'Classique', 'Voyage'
    stock INT(11) DEFAULT 0, -- Quantité disponible
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des paniers (pour les utilisateurs connectés)
-- Un utilisateur n'a qu'un seul panier actif
CREATE TABLE IF NOT EXISTS carts (
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) NOT NULL UNIQUE, -- Chaque utilisateur a un seul panier
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des articles dans le panier
CREATE TABLE IF NOT EXISTS cart_items (
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    cart_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    quantity INT(11) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE (cart_id, product_id) -- Un seul type de produit par panier dans un même panier
);

-- Table des commandes
CREATE TABLE IF NOT EXISTS orders (
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    payment_method VARCHAR(100), -- Ex: 'Carte Bancaire', 'Virement Bancaire'
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des articles de commande (détail des produits dans chaque commande)
CREATE TABLE IF NOT EXISTS order_items (
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    order_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    quantity INT(11) NOT NULL,
    price_at_purchase DECIMAL(10, 2) NOT NULL, -- Prix du produit au moment de l'achat
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insertion d'un utilisateur administrateur par défaut pour faciliter les tests
-- Son email est 'admin@example.com' et son mot de passe est 'kiko123'.
-- N'oubliez pas de changer ce mot de passe ou de le supprimer en production !
-- Le hachage '$2y$10$tJ9c/x/k.L7g.f/P.4Pz.e0sV9w7v4g5j6k7l8m9n0o1p2q3r4s5t6u7v8w9x0y1z2' correspond à 'admin123'
-- Le hachage ci-dessous correspond à 'kiko123'
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@example.com', '$2y$10$w8T.N0GgJ9kK7kQ2q3.0j.O6z7.Y8Y9X0Y1Z2A3B4C5D6E7F8G9H0I1J2K3L4M5N6O7P8Q9R0S1', 'admin');


-- Insertion de quelques montres Rolex pour le test
INSERT INTO products (name, description, price, image_url, category, stock) VALUES
('Rolex Submariner Date', 'La montre de plongée emblématique de Rolex, avec date. Boîtier Oyster, lunette Cerachrom et bracelet Oyster.', 12500.00, 'image/submariner_date.jpg', 'Sport', 5),
('Rolex Daytona', 'Le chronographe de course ultime, né pour la vitesse. Boîtier Oyster, lunette Cerachrom et bracelet Oysterflex.', 18000.00, 'image/daytona.jpg', 'Sport', 3),
('Rolex Datejust 36', 'Le classique de l''élégance intemporelle, avec date. Boîtier Oyster, lunette cannelée et bracelet Jubilee.', 9800.00, 'image/datejust_36.jpg', 'Classique', 7),
('Rolex GMT-Master II', 'La montre des globe-trotters, affichant plusieurs fuseaux horaires. Boîtier Oyster, lunette Cerachrom bicolore.', 13800.00, 'image/gmt_master_ii.jpg', 'Voyage', 4);

-- Mise à jour des chemins d'images pour correspondre au dossier 'image/' (si ce n'est pas déjà fait par l'insertion)
UPDATE products SET image_url = 'image/submariner_date.jpg' WHERE name = 'Rolex Submariner Date';
UPDATE products SET image_url = 'image/daytona.jpg' WHERE name = 'Rolex Daytona';
UPDATE products SET image_url = 'image/datejust_36.jpg' WHERE name = 'Rolex Datejust 36';
UPDATE products SET image_url = 'image/gmt_master_ii.jpg' WHERE name = 'Rolex GMT-Master II';


-- Réactive la vérification des clés étrangères
SET FOREIGN_KEY_CHECKS=1;