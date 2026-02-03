<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireAdmin();

$chantier_id = 1;

// Récupérer le chantier
$stmt = $pdo->prepare("SELECT * FROM chantiers WHERE id = ?");
$stmt->execute([$chantier_id]);
$chantier = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Toggle UI</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; max-width: 800px; margin: 0 auto; }
        .toggle-switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc; transition: 0.4s; border-radius: 34px;
        }
        .toggle-slider:before {
            position: absolute; content: ""; height: 26px; width: 26px;
            left: 4px; bottom: 4px; background-color: white;
            transition: 0.4s; border-radius: 50%;
        }
        input:checked + .toggle-slider { background-color: #28a745; }
        input:checked + .toggle-slider:before { transform: translateX(26px); }
        #debug { background: #f0f0f0; padding: 1rem; border-radius: 8px; margin-top: 2rem; font-family: monospace; font-size: 0.9rem; }
        .log { margin: 0.25rem 0; }
    </style>
</head>
<body>
    <h1>🧪 Test Toggle UI</h1>

    <div style="background: <?= $chantier['is_public'] ? '#d4edda' : '#f8f9fa' ?>; padding: 1.5rem; border-radius: 12px; border: 2px solid <?= $chantier['is_public'] ? '#28a745' : '#e0e0e0' ?>;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <strong style="display: block; font-size: 1rem; margin-bottom: 0.5rem;">
                    État actuel: <span id="public-status-text"><?= $chantier['is_public'] ? '🟢 Public' : '🔴 Privé' ?></span>
                </strong>
                <p id="status-description" style="font-size: 0.85rem; color: #6c757d; margin: 0;">
                    <?= $chantier['is_public'] ? 'Ce chantier est accessible publiquement' : 'Ce chantier est privé' ?>
                </p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="public-toggle" <?= $chantier['is_public'] ? 'checked' : '' ?> onchange="togglePublicStatus()">
                <span class="toggle-slider"></span>
            </label>
        </div>
    </div>

    <div id="debug">
        <strong>📋 Debug Log:</strong>
        <div id="log-container"></div>
    </div>

    <p style="margin-top: 2rem;">
        <a href="?">⟲ Recharger la page</a> |
        <a href="test-simple.php">→ Test Simple</a> |
        <a href="pages/edit-chantier.php?id=<?= $chantier_id ?>">→ Edit Chantier</a>
    </p>

    <script>
        function addLog(message, type = 'info') {
            const logContainer = document.getElementById('log-container');
            const logEntry = document.createElement('div');
            logEntry.className = 'log';
            const timestamp = new Date().toLocaleTimeString();
            const icon = type === 'error' ? '❌' : type === 'success' ? '✅' : '📝';
            logEntry.textContent = `[${timestamp}] ${icon} ${message}`;
            logContainer.appendChild(logEntry);
            console.log(`[${type.toUpperCase()}]`, message);
        }

        function togglePublicStatus() {
            const chantierId = <?= $chantier_id ?>;
            addLog(`Appel togglePublicStatus() pour chantier ID: ${chantierId}`);

            const formData = new FormData();
            formData.append('chantier_id', chantierId);

            addLog('Envoi de la requête AJAX...');

            fetch('../api/toggle-public.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                addLog(`Réponse HTTP reçue: ${response.status}`);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                addLog(`Données reçues: ${JSON.stringify(data)}`);

                if (data.success) {
                    const statusText = document.getElementById('public-status-text');
                    const statusDescription = document.getElementById('status-description');

                    if (data.is_public) {
                        addLog('Passage en mode PUBLIC', 'success');
                        statusText.textContent = '🟢 Public';
                        statusDescription.textContent = 'Ce chantier est accessible publiquement';
                        alert('✅ ' + data.message);
                    } else {
                        addLog('Passage en mode PRIVÉ', 'success');
                        statusText.textContent = '🔴 Privé';
                        statusDescription.textContent = 'Ce chantier est privé';
                        alert('✅ ' + data.message);
                    }
                } else {
                    addLog(`Erreur: ${data.message}`, 'error');
                    alert('❌ Erreur: ' + data.message);
                    // Réinitialiser le toggle
                    document.getElementById('public-toggle').checked = !document.getElementById('public-toggle').checked;
                }
            })
            .catch(error => {
                addLog(`Exception: ${error.message}`, 'error');
                console.error('Erreur complète:', error);
                alert('❌ Erreur lors de la modification du statut public: ' + error.message);
                // Réinitialiser le toggle
                document.getElementById('public-toggle').checked = !document.getElementById('public-toggle').checked;
            });
        }

        addLog('Page chargée - état initial: <?= $chantier['is_public'] ? "PUBLIC" : "PRIVÉ" ?>');
    </script>
</body>
</html>
