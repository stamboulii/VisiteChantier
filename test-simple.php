<?php
// Test ultra simple du toggle
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Vérifier qu'on est admin
if (!isAdmin()) {
    die("❌ Vous devez être admin pour faire ce test. Connectez-vous d'abord.");
}

$chantier_id = 1;

echo "<h1>Test Simple Toggle Public</h1>";
echo "<hr>";

// État AVANT
echo "<h2>1. État AVANT:</h2>";
$stmt = $pdo->prepare("SELECT id, nom, is_public, share_token FROM chantiers WHERE id = ?");
$stmt->execute([$chantier_id]);
$before = $stmt->fetch();
echo "<pre>";
echo "ID: {$before['id']}\n";
echo "Nom: {$before['nom']}\n";
echo "is_public: {$before['is_public']}\n";
echo "share_token: " . ($before['share_token'] ?: 'NULL') . "\n";
echo "</pre>";

// TOGGLE
echo "<h2>2. Exécution du toggle:</h2>";

$new_state = $before['is_public'] ? 0 : 1;
$share_token = $before['share_token'];

if ($new_state == 1 && empty($share_token)) {
    $share_token = bin2hex(random_bytes(32));
    echo "<p>✅ Génération d'un nouveau token: <code>$share_token</code></p>";
}

echo "<p>Nouvelle valeur: is_public = $new_state</p>";

$stmt = $pdo->prepare("UPDATE chantiers SET is_public = ?, share_token = ? WHERE id = ?");
$result = $stmt->execute([$new_state, $share_token, $chantier_id]);

if ($result) {
    echo "<p style='color: green;'>✅ UPDATE exécuté avec succès</p>";
    echo "<p>Nombre de lignes affectées: " . $stmt->rowCount() . "</p>";
} else {
    echo "<p style='color: red;'>❌ Erreur lors de l'UPDATE</p>";
    $error = $stmt->errorInfo();
    echo "<pre>";
    print_r($error);
    echo "</pre>";
}

// État APRÈS
echo "<h2>3. État APRÈS:</h2>";
$stmt = $pdo->prepare("SELECT id, nom, is_public, share_token FROM chantiers WHERE id = ?");
$stmt->execute([$chantier_id]);
$after = $stmt->fetch();
echo "<pre>";
echo "ID: {$after['id']}\n";
echo "Nom: {$after['nom']}\n";
echo "is_public: {$after['is_public']}\n";
echo "share_token: " . ($after['share_token'] ?: 'NULL') . "\n";
echo "</pre>";

// COMPARAISON
echo "<h2>4. Résultat:</h2>";
if ($before['is_public'] != $after['is_public']) {
    echo "<p style='color: green; font-size: 1.5rem; font-weight: bold;'>✅ LE TOGGLE A FONCTIONNÉ!</p>";
    echo "<p>L'état a changé de {$before['is_public']} à {$after['is_public']}</p>";
} else {
    echo "<p style='color: red; font-size: 1.5rem; font-weight: bold;'>❌ LE TOGGLE N'A PAS FONCTIONNÉ!</p>";
    echo "<p>L'état est resté à {$after['is_public']}</p>";
}

echo "<hr>";
echo "<p><a href='?'>⟲ Re-tester (toggle à nouveau)</a></p>";
echo "<p><a href='pages/edit-chantier.php?id=$chantier_id'>→ Aller sur edit-chantier.php</a></p>";
?>
