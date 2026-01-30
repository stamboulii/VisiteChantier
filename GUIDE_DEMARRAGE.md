# 🚀 Guide de Démarrage Rapide

## Installation en 5 minutes

### 1️⃣ Configuration du serveur local

**Si vous utilisez XAMPP:**
- Téléchargez XAMPP depuis https://www.apachefriends.org/
- Installez et lancez Apache + MySQL
- Copiez le dossier `suivi-chantiers` dans `C:\xampp\htdocs\`

**Si vous utilisez WAMP:**
- Téléchargez WAMP depuis https://www.wampserver.com/
- Installez et lancez tous les services
- Copiez le dossier `suivi-chantiers` dans `C:\wamp64\www\`

### 2️⃣ Création de la base de données

1. Ouvrez votre navigateur
2. Allez sur http://localhost/phpmyadmin
3. Cliquez sur "Nouveau" dans la barre latérale
4. Nom de la base: `suivi_chantiers`
5. Interclassement: `utf8mb4_unicode_ci`
6. Cliquez sur "Créer"
7. Sélectionnez la base `suivi_chantiers`
8. Cliquez sur l'onglet "SQL"
9. Copiez-collez le contenu du fichier `database.sql`
10. Cliquez sur "Exécuter"

### 3️⃣ Première connexion

1. Ouvrez http://localhost/suivi-chantiers/
2. Connectez-vous avec:
   - **Username**: architect
   - **Password**: architect123

### 4️⃣ Créer votre premier chantier

1. Sur le dashboard, cliquez sur "Nouveau Chantier"
2. Remplissez les informations:
   - Nom: ex. "Villa Moderne - Nice"
   - Adresse: ex. "15 Promenade des Anglais, 06000 Nice"
   - Description: ex. "Construction villa 200m²"
   - Date de début: choisissez une date
3. Cliquez sur "Créer le chantier"

### 5️⃣ Uploader des photos

1. Cliquez sur le chantier que vous venez de créer
2. Dans la section "Ajouter une photo":
   - Sélectionnez une image de votre ordinateur
   - Choisissez la phase (Fondations, Structure, etc.)
   - Ajoutez un commentaire (optionnel)
3. Cliquez sur "Uploader la photo"

## 📱 Utilisation quotidienne

### Dashboard
Le dashboard vous montre:
- Le nombre total de chantiers
- Les chantiers en cours
- Le total de photos uploadées
- La liste complète de vos chantiers

### Page Chantier
Pour chaque chantier, vous pouvez:
- Voir toutes les informations du projet
- Uploader de nouvelles photos
- Consulter la galerie chronologique
- Organiser les photos par phase

### Phases disponibles
- **Fondations**: Terrassement, fondations, dalle
- **Structure**: Murs, poteaux, planchers
- **Clos & Couvert**: Toiture, menuiseries extérieures
- **Second Œuvre**: Électricité, plomberie, isolation
- **Finitions**: Peinture, revêtements, aménagements
- **Autres**: Tout ce qui ne rentre pas dans les catégories précédentes

## 🎯 Bonnes pratiques

### Organisation des photos
- Uploadez régulièrement (hebdomadaire recommandé)
- Utilisez les bonnes phases pour faciliter le tri
- Ajoutez des commentaires détaillés
- Prenez des photos depuis les mêmes angles pour voir l'évolution

### Gestion des chantiers
- Mettez à jour le statut régulièrement
- Utilisez des noms de chantiers clairs et uniques
- Indiquez toujours l'adresse complète

### Sécurité
- Changez le mot de passe par défaut
- Ne partagez pas vos identifiants
- Faites des sauvegardes régulières de la base de données

## ⚠️ Résolution de problèmes

### "Erreur de connexion à la base de données"
- Vérifiez que MySQL est démarré
- Vérifiez les paramètres dans `includes/config.php`
- Assurez-vous que la base `suivi_chantiers` existe

### "Erreur lors de l'upload"
- Vérifiez que le dossier `uploads/` existe
- Sur Linux/Mac: `chmod 755 uploads/`
- Vérifiez la taille de votre image (max 5MB)

### Les images ne s'affichent pas
- Vérifiez les permissions du dossier `uploads/`
- Vérifiez que les images sont bien dans `uploads/`
- Essayez de vider le cache du navigateur (Ctrl+F5)

### Page blanche
- Activez l'affichage des erreurs PHP
- Vérifiez les logs Apache/PHP
- Assurez-vous que toutes les extensions PHP requises sont activées

## 📞 Besoin d'aide?

1. Consultez le fichier README.md
2. Vérifiez les logs d'erreur de votre serveur
3. Assurez-vous d'avoir PHP 7.4 minimum

## 🎉 Félicitations!

Vous êtes prêt à utiliser votre plateforme de suivi de chantiers!

Commencez dès maintenant à documenter l'avancement de vos projets de construction.
