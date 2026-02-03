<?php
session_start();

// Simuler une connexion admin pour tester
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

require_once 'includes/config.php';

echo "<h1>Test de l'API toggle-public.php</h1>";

// Test 1: Appel direct
$chantier_id = 1;

echo "<h2>Avant le toggle:</h2>";
$stmt = $pdo->prepare("SELECT id, nom, is_public, share_token FROM chantiers WHERE id = ?");
$stmt->execute([$chantier_id]);
$before = $stmt->fetch();
echo "<pre>";
print_r($before);
echo "</pre>";

// Simuler POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['chantier_id'] = $chantier_id;

echo "<h2>Appel de l'API toggle-public.php:</h2>";

// Capturer la sortie
ob_start();
include 'api/toggle-public.php';
$api_output = ob_get_clean();

echo "<pre>" . htmlspecialchars($api_output) . "</pre>";

echo "<h2>Après le toggle:</h2>";
$stmt = $pdo->prepare("SELECT id, nom, is_public, share_token FROM chantiers WHERE id = ?");
$stmt->execute([$chantier_id]);
$after = $stmt->fetch();
echo "<pre>";
print_r($after);
echo "</pre>";

if ($before['is_public'] != $after['is_public']) {
    echo "<p style='color: green; font-weight: bold;'>✅ Le toggle a fonctionné!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Le toggle n'a PAS fonctionné!</p>";
}
?>
