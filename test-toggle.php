<?php
/**
 * Page de test pour le toggle public
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireAdmin();

// Test 1: Vérifier les colonnes
echo "<h2>Test 1: Structure de la table</h2>";
$stmt = $pdo->query("SHOW COLUMNS FROM chantiers WHERE Field IN ('is_public', 'share_token')");
$columns = $stmt->fetchAll();
echo "<pre>";
print_r($columns);
echo "</pre>";

// Test 2: Vérifier les données actuelles
echo "<h2>Test 2: Données actuelles</h2>";
$stmt = $pdo->query("SELECT id, nom, is_public, share_token FROM chantiers");
$chantiers = $stmt->fetchAll();
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Nom</th><th>is_public</th><th>share_token</th></tr>";
foreach ($chantiers as $c) {
    echo "<tr>";
    echo "<td>{$c['id']}</td>";
    echo "<td>" . htmlspecialchars($c['nom']) . "</td>";
    echo "<td>{$c['is_public']}</td>";
    echo "<td>" . ($c['share_token'] ?: 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test 3: Tester l'UPDATE manuellement
if (isset($_GET['test_update'])) {
    $test_id = intval($_GET['test_update']);

    echo "<h2>Test 3: Mise à jour manuelle du chantier $test_id</h2>";

    // Récupérer l'état actuel
    $stmt = $pdo->prepare("SELECT is_public, share_token FROM chantiers WHERE id = ?");
    $stmt->execute([$test_id]);
    $current = $stmt->fetch();

    echo "<p>État actuel: is_public = {$current['is_public']}, share_token = " . ($current['share_token'] ?: 'NULL') . "</p>";

    // Inverser l'état
    $new_state = $current['is_public'] ? 0 : 1;
    $share_token = $current['share_token'];

    if ($new_state == 1 && empty($share_token)) {
        $share_token = bin2hex(random_bytes(32));
    }

    echo "<p>Nouvel état: is_public = $new_state, share_token = $share_token</p>";

    // Mettre à jour
    $stmt = $pdo->prepare("UPDATE chantiers SET is_public = ?, share_token = ? WHERE id = ?");
    $result = $stmt->execute([$new_state, $share_token, $test_id]);

    if ($result) {
        echo "<p style='color: green;'>✅ Mise à jour réussie!</p>";

        // Vérifier
        $stmt = $pdo->prepare("SELECT is_public, share_token FROM chantiers WHERE id = ?");
        $stmt->execute([$test_id]);
        $updated = $stmt->fetch();

        echo "<p>État après mise à jour: is_public = {$updated['is_public']}, share_token = " . ($updated['share_token'] ?: 'NULL') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Erreur lors de la mise à jour</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Toggle Public</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; }
        h2 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 0.5rem; }
        table { margin: 1rem 0; }
        th, td { padding: 0.5rem 1rem; text-align: left; }
        th { background: #667eea; color: white; }
    </style>
</head>
<body>
    <h1>🧪 Test du système de partage public</h1>

    <p><a href="?">Rafraîchir</a></p>

    <h3>Actions de test:</h3>
    <ul>
        <li><a href="?test_update=1">Tester l'UPDATE sur le chantier #1</a></li>
        <li><a href="?test_update=2">Tester l'UPDATE sur le chantier #2</a></li>
        <li><a href="?test_update=3">Tester l'UPDATE sur le chantier #3</a></li>
    </ul>

    <hr>

    <?php
    // Afficher le contenu testé ci-dessus
    ?>

    <hr>

    <h2>Test 4: Appel AJAX simulé</h2>
    <button onclick="testToggle(1)">Toggle chantier #1</button>
    <button onclick="testToggle(2)">Toggle chantier #2</button>
    <button onclick="testToggle(3)">Toggle chantier #3</button>

    <div id="result" style="margin-top: 1rem; padding: 1rem; background: #f0f0f0; border-radius: 8px;"></div>

    <script>
        function testToggle(chantierId) {
            const formData = new FormData();
            formData.append('chantier_id', chantierId);

            document.getElementById('result').innerHTML = '⏳ Appel en cours...';

            fetch('api/toggle-public.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                if (data.success) {
                    setTimeout(() => location.reload(), 2000);
                }
            })
            .catch(error => {
                document.getElementById('result').innerHTML = '<p style="color: red;">❌ Erreur: ' + error + '</p>';
            });
        }
    </script>
</body>
</html>
