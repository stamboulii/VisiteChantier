<?php
/**
 * Migration - Ajout du système de rôles
 * 
 * Cette migration ajoute :
 * - Colonne 'role' dans la table users
 * - Table 'chantier_assignments' pour assigner les chantiers aux architectes
 * - Convertit le compte 'architect' en admin
 */

require_once 'includes/config.php';

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Migration - Système de Rôles</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        h1 { color: #2c3e50; }
        .success { color: #27ae60; padding: 10px; background: #d4edda; border-radius: 5px; margin: 10px 0; }
        .error { color: #e74c3c; padding: 10px; background: #f8d7da; border-radius: 5px; margin: 10px 0; }
        .info { color: #3498db; padding: 10px; background: #d1ecf1; border-radius: 5px; margin: 10px 0; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #3498db; background: #f8f9fa; }
        .step h3 { margin-top: 0; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
<h1>🔄 Migration - Système de Rôles et Permissions</h1>
";

$errors = [];
$success = [];

try {
    // Début de la transaction
    $pdo->beginTransaction();
    
    // ========================================
    // ÉTAPE 1: Vérifier si la migration est nécessaire
    // ========================================
    echo "<div class='step'><h3>📋 Étape 1: Vérification</h3>";
    
    // Vérifier si la colonne 'role' existe déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $roleExists = $stmt->fetch();
    
    if ($roleExists) {
        echo "<div class='info'>ℹ️ La colonne 'role' existe déjà dans la table users</div>";
    } else {
        echo "<div class='info'>✅ La colonne 'role' doit être créée</div>";
    }
    
    // Vérifier si la table chantier_assignments existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'chantier_assignments'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<div class='info'>ℹ️ La table 'chantier_assignments' existe déjà</div>";
    } else {
        echo "<div class='info'>✅ La table 'chantier_assignments' doit être créée</div>";
    }
    
    echo "</div>";
    
    // ========================================
    // ÉTAPE 2: Ajouter la colonne 'role'
    // ========================================
    echo "<div class='step'><h3>👤 Étape 2: Ajout colonne 'role'</h3>";
    
    if (!$roleExists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('admin', 'architect') DEFAULT 'architect' AFTER password");
        echo "<div class='success'>✅ Colonne 'role' ajoutée avec succès</div>";
        echo "<pre>ALTER TABLE users ADD COLUMN role ENUM('admin', 'architect') DEFAULT 'architect'</pre>";
    } else {
        echo "<div class='info'>⏭️ Colonne 'role' déjà présente, ignoré</div>";
    }
    
    echo "</div>";
    
    // ========================================
    // ÉTAPE 3: Créer la table chantier_assignments
    // ========================================
    echo "<div class='step'><h3>🏗️ Étape 3: Table assignation chantiers</h3>";
    
    if (!$tableExists) {
        $sql = "CREATE TABLE IF NOT EXISTS chantier_assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chantier_id INT NOT NULL,
            user_id INT NOT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            assigned_by INT NULL,
            FOREIGN KEY (chantier_id) REFERENCES chantiers(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
            UNIQUE KEY unique_assignment (chantier_id, user_id),
            INDEX idx_user (user_id),
            INDEX idx_chantier (chantier_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "<div class='success'>✅ Table 'chantier_assignments' créée avec succès</div>";
        echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    } else {
        echo "<div class='info'>⏭️ Table 'chantier_assignments' déjà présente, ignoré</div>";
    }
    
    echo "</div>";
    
    // ========================================
    // ÉTAPE 4: Convertir le compte 'architect' en admin
    // ========================================
    echo "<div class='step'><h3>🔑 Étape 4: Conversion compte admin</h3>";
    
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE username = ?");
    $stmt->execute(['architect']);
    $user = $stmt->fetch();
    
    if ($user) {
        if ($user['role'] === 'admin') {
            echo "<div class='info'>ℹ️ Le compte 'architect' est déjà admin</div>";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE username = 'architect'");
            $stmt->execute();
            echo "<div class='success'>✅ Le compte 'architect' est maintenant ADMIN</div>";
            echo "<pre>UPDATE users SET role = 'admin' WHERE username = 'architect'</pre>";
        }
    } else {
        echo "<div class='error'>⚠️ Aucun utilisateur 'architect' trouvé. Création d'un admin par défaut...</div>";
        
        // Créer un compte admin
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, nom, prenom, role) VALUES (?, ?, ?, ?, ?, ?)");
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt->execute(['admin', 'admin@example.com', $hash, 'Admin', 'System', 'admin']);
        
        echo "<div class='success'>✅ Compte admin créé</div>";
        echo "<pre>Username: admin\nPassword: admin123</pre>";
    }
    
    echo "</div>";
    
    // ========================================
    // ÉTAPE 6: Ajouter la colonne 'user_id' à la table 'images'
    // ========================================
    echo "<div class='step'><h3>📸 Étape 6: Mise à jour table 'images'</h3>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM images LIKE 'user_id'");
    $userIdExists = $stmt->fetch();
    
    if (!$userIdExists) {
        // Ajouter la colonne
        $pdo->exec("ALTER TABLE images ADD COLUMN user_id INT NULL AFTER chantier_id");
        $pdo->exec("ALTER TABLE images ADD CONSTRAINT fk_images_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
        
        // Assigner les images existantes au premier admin trouvé (architect par défaut)
        $stmt_admin = $pdo->query("SELECT id FROM users ORDER BY id LIMIT 1");
        $first_user_id = $stmt_admin->fetch()['id'] ?? null;
        
        if ($first_user_id) {
            $stmt_update = $pdo->prepare("UPDATE images SET user_id = ? WHERE user_id IS NULL");
            $stmt_update->execute([$first_user_id]);
            echo "<div class='success'>✅ Colonne 'user_id' ajoutée aux images et assignée à l'utilisateur #$first_user_id</div>";
        } else {
            echo "<div class='success'>✅ Colonne 'user_id' ajoutée aux images</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ La colonne 'user_id' existe déjà dans la table 'images'</div>";
    }
    
    // ÉTAPE 7: Généralisation des projets (Type et Lot ID)
    // ========================================
    echo "<div class='step'><h3>🏗️ Étape 7: Généralisation des projets</h3>";
    
    // Ajouter la colonne 'type' si elle n'existe pas
    $stmt = $pdo->query("SHOW COLUMNS FROM chantiers LIKE 'type'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE chantiers ADD COLUMN type ENUM('chantier', 'visite_commerciale', 'etat_des_lieux', 'autre') DEFAULT 'chantier' AFTER statut");
        echo "<div class='success'>✅ Colonne 'type' ajoutée à la table 'chantiers'</div>";
    }
    
    // Ajouter la colonne 'lot_id' si elle n'existe pas
    $stmt = $pdo->query("SHOW COLUMNS FROM chantiers LIKE 'lot_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE chantiers ADD COLUMN lot_id VARCHAR(50) NULL AFTER type");
        echo "<div class='success'>✅ Colonne 'lot_id' ajoutée à la table 'chantiers'</div>";
    }
    
    echo "</div>";
    
    // ÉTAPE 8: Flexibilité des phases d'images
    // ========================================
    echo "<div class='step'><h3>🖼️ Étape 8: Flexibilité des phases d'images</h3>";
    $pdo->exec("ALTER TABLE images MODIFY COLUMN phase VARCHAR(50) DEFAULT 'autres'");
    echo "<div class='success'>✅ Colonne 'phase' modifiée en VARCHAR dans la table 'images'</div>";
    echo "</div>";
    
    // ÉTAPE 9: Support multi-templates
    // ========================================
    echo "<div class='step'><h3>📂 Étape 9: Support multi-templates</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM chantiers LIKE 'template_file'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE chantiers ADD COLUMN template_file VARCHAR(255) NULL AFTER lot_id");
        echo "<div class='success'>✅ Colonne 'template_file' ajoutée à la table 'chantiers'</div>";
    }
    echo "</div>";
    
    // Commit de la transaction
    $pdo->commit();
    
    echo "<div class='success'>";
    echo "<h2>🎉 Migration réussie!</h2>";
    echo "<p>Le système de rôles a été installé avec succès.</p>";
    echo "<ul>";
    echo "<li>✅ Colonne 'role' ajoutée</li>";
    echo "<li>✅ Table 'chantier_assignments' créée</li>";
    echo "<li>✅ Compte admin configuré</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<a href='index.php' class='btn'>🏠 Retour à la connexion</a>";
    echo "<a href='test-db.php' class='btn' style='background: #27ae60; margin-left: 10px;'>🔍 Tester la connexion</a>";
    
} catch (PDOException $e) {
    // Rollback en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "<div class='error'>";
    echo "<h2>❌ Erreur lors de la migration</h2>";
    echo "<p>La migration a été annulée (rollback). Aucune modification n'a été appliquée.</p>";
    echo "<p><strong>Erreur:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    
    echo "<a href='test-db.php' class='btn' style='background: #e74c3c;'>🔍 Tester la connexion DB</a>";
}

echo "</body></html>";
?>
