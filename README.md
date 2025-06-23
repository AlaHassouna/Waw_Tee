# E-commerce Backend API - Laravel

Backend API complet pour une application e-commerce développé avec Laravel et MySQL.

## 🚀 Installation

### Prérequis

- PHP 8.1+
- MySQL 5.7+
- Composer
- Extensions PHP : PDO, OpenSSL, JSON, Tokenizer, Mbstring, XML, Ctype, BCMath

### Étapes d'installation

1. **Installer les dépendances :**
   \`\`\`bash
   cd backendPhp
   composer install
   \`\`\`
2. **Configuration :**
   \`\`\`bash
   cp .env.example .env
   php artisan key:generate
   \`\`\`
3. **Configurer la base de données dans .env :**
   \`\`\`env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ecommerce_db
   DB_USERNAME=root
   DB_PASSWORD=your_password
   \`\`\`
4. **Créer la base de données :**
   \`\`\`sql
   CREATE DATABASE ecommerce_db;
   \`\`\`
5. **Exécuter les migrations et seeders :**
   \`\`\`bash
   php artisan migrate --seed
   \`\`\`
6. **Lancer le serveur :**
   \`\`\`bash
   php artisan serve
   \`\`\`

L'API sera accessible sur `http://localhost:8000`

## 📋 Fonctionnalités

### Authentification

- Inscription/Connexion utilisateur
- Authentification JWT avec Laravel Sanctum
- Réinitialisation de mot de passe
- Gestion des rôles (admin/customer)

### Produits

- CRUD complet des produits
- Gestion des catégories
- Images multiples
- Variantes et options
- Système de stock
- Produits vedettes

### Commandes

- Création de commandes
- Gestion des statuts
- Historique des commandes
- Suivi des livraisons
- Calcul automatique des taxes et frais de port

### Paiements

- Intégration Stripe
- Intégration PayPal
- Gestion des erreurs de paiement
- Webhooks

### Autres

- Liste de souhaits
- Upload d'images (Cloudinary)
- Calcul des frais de port
- Suivi des colis
- Système d'emails
- Dashboard administrateur

## 🔧 Configuration des services

\`\`\`

## 📚 API Endpoints

### Authentification

- `POST /api/auth/register` - Inscription
- `POST /api/auth/login` - Connexion
- `GET /api/auth/me` - Profil utilisateur
- `POST /api/auth/forgot-password` - Mot de passe oublié

### Produits

- `GET /api/products` - Liste des produits
- `GET /api/products/{id}` - Détail produit
- `POST /api/admin/products` - Créer produit (admin)
- `PUT /api/admin/products/{id}` - Modifier produit (admin)

### Commandes

- `POST /api/orders` - Créer commande
- `GET /api/orders/{id}` - Détail commande
- `GET /api/users/orders` - Commandes utilisateur

### Et bien plus...

## 👥 Comptes de test

## 🛠️ Développement

### Commandes utiles :

\`\`\`bash

# Créer une migration

php artisan make:migration create_table_name

# Créer un modèle

php artisan make:model ModelName

# Créer un contrôleur

php artisan make:controller ControllerName

# Vider le cache

php artisan cache:clear
php artisan config:clear
php artisan route:clear
\`\`\`

## ✅ **Étapes pour lancer le backend Laravel :**

1. **Installer les dépendances :**
   \`\`\`bash
   cd backendPhp
   composer install
   \`\`\`
2. **Configuration :**
   \`\`\`bash
   cp .env.example .env
   php artisan key:generate
   \`\`\`
3. **Configurer MySQL dans .env :**
   \`\`\`env
   DB_DATABASE=ecommerce_db
   DB_USERNAME=root
   DB_PASSWORD=your_password
   \`\`\`
4. **Créer la base de données :**
   \`\`\`sql
   CREATE DATABASE ecommerce_db;
   \`\`\`
5. **Migrations et données :**
   \`\`\`bash
   php artisan migrate --seed
   \`\`\`
6. **Lancer le serveur :**
   \`\`\`bash
   php artisan serve
   \`\`\`
