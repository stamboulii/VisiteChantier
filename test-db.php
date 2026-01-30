<?php
// Script de test de connexion à la base de données

echo "<h1>🔍 Test de Connexion Base de Données</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }</style>";

// Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'suivi_chantiers');

// Test 1: Connexion MySQL sans base de données
echo "<h2>Test 1: Connexion au serveur MySQL</h2>";
try {
    $pdo_test = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS
    );
    echo "<p class='success'>✅ Connexion au serveur MySQL réussie!</p>";
    
    // Vérifier si la base existe
    echo "<h2>Test 2: Vérification de la base de données</h2>";
    $stmt = $pdo_test->query("SHOW DATABASES LIKE 'suivi_chantiers'");
    $db_exists = $stmt->fetch();
    
    if ($db_exists) {
        echo "<p class='success'>✅ La base de données 'suivi_chantiers' existe</p>";
    } else {
        echo "<p class='error'>❌ La base de données 'suivi_chantiers' N'EXISTE PAS</p>";
        echo "<p class='info'>💡 Vous devez créer la base de données avec le fichier database.sql</p>";
        echo "<p>Exécutez cette commande dans le terminal:</p>";
        echo "<pre>mysql -u root -p < database.sql</pre>";
        echo "<p>Ou importez le fichier via phpMyAdmin</p>";
        exit;
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Erreur de connexion MySQL: " . $e->getMessage() . "</p>";
    echo "<p class='info'>💡 Vérifiez que MySQL est démarré dans Laragon</p>";
    exit;
}

// Test 3: Connexion à la base suivi_chantiers
echo "<h2>Test 3: Connexion à la base 'suivi_chantiers'</h2>";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    echo "<p class='success'>✅ Connexion à la base de données réussie!</p>";
} catch (PDOException $e) {
    echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
    exit;
}

// Test 4: Vérifier les tables
echo "<h2>Test 4: Vérification des tables</h2>";
$tables_required = ['users', 'chantiers', 'images'];
$tables_found = [];

$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables_required as $table) {
    if (in_array($table, $tables)) {
        echo "<p class='success'>✅ Table '$table' existe</p>";
        $tables_found[] = $table;
    } else {
        echo "<p class='error'>❌ Table '$table' manquante</p>";
    }
}

if (count($tables_found) !== count($tables_required)) {
    echo "<p class='error'>❌ Certaines tables sont manquantes. Importez le fichier database.sql</p>";
    exit;
}

// Test 5: Vérifier l'utilisateur de test
echo "<h2>Test 5: Vérification de l'utilisateur de test</h2>";
$stmt = $pdo->query("SELECT * FROM users WHERE username = 'architect'");
$user = $stmt->fetch();

if ($user) {
    echo "<p class='success'>✅ L'utilisateur 'architect' existe</p>";
    echo "<pre>";
    echo "Username: " . htmlspecialchars($user['username']) . "\n";
    echo "Email: " . htmlspecialchars($user['email']) . "\n";
    echo "Nom: " . htmlspecialchars($user['nom']) . " " . htmlspecialchars($user['prenom']) . "\n";
    echo "Hash du mot de passe: " . substr($user['password'], 0, 20) . "...\n";
    echo "</pre>";
    
    // Test 6: Vérifier le mot de passe
    echo "<h2>Test 6: Vérification du mot de passe</h2>";
    $password_test = 'architect123';
    
    if (password_verify($password_test, $user['password'])) {
        echo "<p class='success'>✅ Le mot de passe 'architect123' est CORRECT</p>";
        echo "<p class='success'>🎉 Tout est configuré correctement! Vous devriez pouvoir vous connecter.</p>";
    } else {
        echo "<p class='error'>❌ Le mot de passe 'architect123' ne correspond pas au hash en base</p>";
        echo "<p class='info'>💡 Le hash du mot de passe est incorrect. Régénération...</p>";
        
        // Régénérer le hash
        $new_hash = password_hash($password_test, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'architect'");
        $stmt->execute([$new_hash]);
        
        echo "<p class='success'>✅ Mot de passe réinitialisé! Essayez de vous connecter maintenant.</p>";
    }
} else {
    echo "<p class='error'>❌ L'utilisateur 'architect' N'EXISTE PAS dans la base de données</p>";
    echo "<p class='info'>💡 Création de l'utilisateur de test...</p>";
    
    // Créer l'utilisateur
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, nom, prenom) VALUES (?, ?, ?, ?, ?)");
    $hash = password_hash('architect123', PASSWORD_DEFAULT);
    $stmt->execute(['architect', 'architect@example.com', $hash, 'Dupont', 'Jean']);
    
    echo "<p class='success'>✅ Utilisateur créé! Vous pouvez maintenant vous connecter avec:</p>";
    echo "<pre>Username: architect\nPassword: architect123</pre>";
}

// Test 7: Compter les chantiers
echo "<h2>Test 7: Données de test</h2>";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM chantiers");
$count = $stmt->fetch()['count'];
echo "<p class='info'>📊 Nombre de chantiers: $count</p>";

$stmt = $pdo->query("SELECT COUNT(*) as count FROM images");
$count = $stmt->fetch()['count'];
echo "<p class='info'>📷 Nombre d'images: $count</p>";

echo "<hr>";
echo "<h2>✅ Résumé</h2>";
echo "<p class='success'>La configuration est correcte. Vous pouvez maintenant:</p>";
echo "<ol>";
echo "<li>Aller sur <a href='http://localhost:8000'>http://localhost:8000</a></li>";
echo "<li>Se connecter avec:<br><strong>username:</strong> architect<br><strong>password:</strong> architect123</li>";
echo "</ol>";
?>
