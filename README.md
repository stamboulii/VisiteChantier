# 🏗️ Plateforme de Suivi de Chantiers

Une application web simple pour architectes permettant de gérer et suivre l'avancement de leurs chantiers à travers des uploads de photos.

## 📋 Fonctionnalités

### ✨ Version 2.1 (Nouveau !)
- ✅ **Page d'accueil publique** : Galerie de tous les chantiers publics accessible sans connexion
- ✅ **Partage public** : Les admins peuvent rendre un chantier accessible publiquement
- ✅ **Token unique** : Chaque partage génère un lien sécurisé unique
- ✅ **Timeline publique** : Vue timeline accessible sans authentification
- ✅ **Gestion simplifiée** : Toggle on/off dans l'interface admin
- ✅ **Navigation intuitive** : Navigation entre projets publics et détails

### ✨ Version 2.0
- ✅ **Timeline chronologique** : Visualisation du déroulé du chantier par dates
- ✅ **Édition d'images** : Modifier phase, date et commentaires des photos
- ✅ **Suppression d'images** : Admins et architectes peuvent gérer les photos
- ✅ **Date de prise de vue** : Champ éditable distinct de la date d'upload

### 🏗️ Fonctionnalités de base
- ✅ Système d'authentification sécurisé
- ✅ Dashboard avec statistiques
- ✅ Gestion multi-chantiers
- ✅ Upload d'images avec métadonnées (phase, commentaires)
- ✅ Galerie photos par chantier
- ✅ Organisation par phases de construction
- ✅ Suivi chronologique de l'avancement
- ✅ Interface responsive et moderne

## 🛠️ Technologies Utilisées

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Base de données**: MySQL 5.7+
- **Architecture**: MVC simple, sans framework

## 📦 Installation

### Prérequis

- Serveur web (Apache/Nginx) avec PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Extension PHP PDO activée
- Extension PHP GD pour la manipulation d'images

### Étape 1: Configurer le serveur local

Si vous utilisez XAMPP, WAMP ou MAMP:

1. Copiez le dossier `suivi-chantiers` dans le répertoire `htdocs` (XAMPP) ou `www` (WAMP)
2. Démarrez Apache et MySQL

### Étape 2: Créer la base de données

1. Accédez à phpMyAdmin (<http://localhost/phpmyadmin>)
2. Importez le fichier `database.sql` ou exécutez le script SQL fourni
3. Vérifiez que les tables `users`, `chantiers` et `images` ont été créées

### Étape 3: Configuration

1. Ouvrez le fichier `includes/config.php`
2. Modifiez les paramètres de connexion si nécessaire:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Votre utilisateur MySQL
define('DB_PASS', '');              // Votre mot de passe MySQL
define('DB_NAME', 'suivi_chantiers');
```

### Étape 4: Permissions

Assurez-vous que le dossier `uploads/` est accessible en écriture:

```bash
chmod 755 uploads/
```

### Étape 5: Accès à l'application

Ouvrez votre navigateur et accédez à:

```text
http://localhost/suivi-chantiers/
```

## Installation de la base de données

### Prérequis

- MySQL 8.0 ou supérieur
- PHP 7.4 ou supérieur

### Installation

1. Importez le fichier SQL :

```bash
mysql -u root -p < database.sql
```

1. Ou via phpMyAdmin : Importez le fichier `database.sql`

<!-- ## Compte de test

**Username:** admin  
**Email:** admin@example.com  
**Mot de passe:** password123 -->

### Structure

- `users` : Gestion des utilisateurs (admin/architect)
- `chantiers` : Gestion des chantiers
- `images` : Photos des chantiers
- `chantier_assignments` : Affectation des architectes aux chantiers

### 📁 Structure du projet

``` text
suivi-chantiers/
├── css/
│   └── style.css           # Styles CSS
├── js/
│   └── main.js            # JavaScript
├── includes/
│   ├── config.php         # Configuration BDD
│   └── auth.php           # Vérification authentification
├── pages/
│   ├── dashboard.php      # Page principale
│   ├── chantier.php       # Détail d'un chantier
│   ├── nouveau-chantier.php  # Création de chantier
│   └── logout.php         # Déconnexion
├── uploads/               # Dossier des images uploadées
├── index.php              # Page de connexion
├── database.sql           # Script SQL
└── README.md
```

### 🎨 Fonctionnalités détaillées

#### Dashboard

- Vue d'ensemble de tous les chantiers
- Statistiques: total chantiers, chantiers en cours, photos uploadées
- Accès rapide à chaque chantier

#### Gestion des chantiers

- Création de nouveaux chantiers avec informations détaillées
- Suivi du statut (en cours, terminé, en pause)
- Dates de début et de fin prévue

#### Upload de photos

- Upload par chantier
- Catégorisation par phase (fondations, structure, clos & couvert, etc.)
- Ajout de commentaires
- Métadonnées automatiques (date, heure)

#### Galerie

- Affichage chronologique des photos
- Vue détaillée en modal
- Filtrage par phase de construction

### 🔒 Sécurité

- Mots de passe hashés avec `password_hash()` (bcrypt)
- Protection contre les injections SQL via requêtes préparées (PDO)
- Validation des uploads (types, tailles)
- Sessions sécurisées
- Protection XSS avec `htmlspecialchars()`

<!-- ### 🚀 Améliorations futures possibles

- [ ] Export des rapports en PDF
- [ ] Partage de galerie avec clients (liens temporaires)
- [ ] Notifications par email
- [ ] Application mobile
- [ ] Tableau de bord analytique avancé
- [ ] Gestion des équipes et permissions
- [ ] Commentaires collaboratifs
- [ ] Intégration calendrier
- [ ] API REST -->

### 📝 Notes de développement

#### Base de données

La base de données utilise InnoDB pour les contraintes d'intégrité référentielle:

- Suppression en cascade des chantiers → suppression des images associées
- Suppression d'un utilisateur → suppression de ses chantiers

#### Upload d'images

- Taille maximale: 5MB
- Formats acceptés: JPG, JPEG, PNG, GIF
- Nommage unique avec timestamp pour éviter les collisions

### 🤝 Support

Pour toute question ou problème:

1. Vérifiez que PHP et MySQL sont correctement installés
2. Vérifiez les permissions du dossier `uploads/`
3. Consultez les logs d'erreur PHP

### 📄 Licence

Projet libre d'utilisation pour usage personnel ou commercial.

---

**Développé pour les architectes qui souhaitent suivre efficacement l'avancement de leurs chantiers** 🏗️
