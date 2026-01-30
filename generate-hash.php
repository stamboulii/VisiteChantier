<?php
/**
 * Utilitaire pour générer des mots de passe hashés
 * 
 * Utilisation:
 * 1. Ouvrez ce fichier dans votre navigateur
 * 2. Entrez le mot de passe que vous souhaitez hasher
 * 3. Copiez le hash généré
 * 4. Utilisez-le dans votre base de données
 */

$hash = '';
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Générateur de Hash</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        p {
            color: #7f8c8d;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        input[type="text"],
        input[type="password"],
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            font-family: 'Courier New', monospace;
        }
        
        input:focus,
        textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        button {
            width: 100%;
            padding: 0.75rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #2980b9;
        }
        
        .result {
            background: #e8f5e9;
            border: 2px solid #27ae60;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
        }
        
        .result h3 {
            color: #27ae60;
            margin-bottom: 1rem;
        }
        
        .hash-output {
            background: white;
            padding: 1rem;
            border-radius: 5px;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: #2c3e50;
        }
        
        .copy-btn {
            margin-top: 1rem;
            background: #27ae60;
            padding: 0.5rem 1rem;
            width: auto;
        }
        
        .copy-btn:hover {
            background: #229954;
        }
        
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 1rem;
            border-radius: 8px;
            color: #856404;
            margin-bottom: 1.5rem;
        }
        
        .sql-example {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .sql-example h4 {
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .sql-example code {
            display: block;
            background: white;
            padding: 1rem;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            overflow-x: auto;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Générateur de Hash de Mot de Passe</h1>
        <p>Créez un hash sécurisé pour vos mots de passe</p>
        
        <div class="warning">
            <strong>⚠️ Attention:</strong> Ne partagez jamais ce hash publiquement. 
            Supprimez ce fichier une fois que vous n'en avez plus besoin.
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="password">Entrez le mot de passe à hasher:</label>
                <input type="password" id="password" name="password" 
                       value="<?= htmlspecialchars($password) ?>" 
                       placeholder="Votre mot de passe" required>
            </div>
            
            <button type="submit">Générer le Hash</button>
        </form>
        
        <?php if ($hash): ?>
        <div class="result">
            <h3>✅ Hash généré avec succès!</h3>
            <p style="margin-bottom: 1rem; text-align: left;">
                Mot de passe: <strong><?= htmlspecialchars($password) ?></strong>
            </p>
            <div class="hash-output" id="hashOutput"><?= htmlspecialchars($hash) ?></div>
            <button class="copy-btn" onclick="copyHash()">📋 Copier le Hash</button>
            
            <div class="sql-example">
                <h4>Exemple de requête SQL pour créer un utilisateur:</h4>
                <code>INSERT INTO users (username, email, password, nom, prenom) 
VALUES (
    'votre_username',
    'email@example.com',
    '<?= htmlspecialchars($hash) ?>',
    'Nom',
    'Prenom'
);</code>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function copyHash() {
            const hashText = document.getElementById('hashOutput').textContent;
            navigator.clipboard.writeText(hashText).then(() => {
                alert('Hash copié dans le presse-papier!');
            }).catch(err => {
                console.error('Erreur lors de la copie:', err);
            });
        }
    </script>
</body>
</html>
