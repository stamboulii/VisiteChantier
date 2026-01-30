<?php
require_once '../includes/config.php';

// Message d'erreur personnalisé
$error_message = $_SESSION['error_message'] ?? "Vous n'avez pas la permission d'accéder à cette ressource.";
unset($_SESSION['error_message']);

$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Refusé - Suivi de Chantiers</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .forbidden-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .forbidden-box {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
            animation: slideUp 0.5s ease-out;
        }
        
        .forbidden-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
            animation: shake 0.5s ease-in-out;
        }
        
        .forbidden-box h1 {
            color: #e74c3c;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .forbidden-box p {
            color: #7f8c8d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .forbidden-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-back {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login {
            padding: 1rem 2rem;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            border: 2px solid #667eea;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="forbidden-container">
        <div class="forbidden-box">
            <div class="forbidden-icon">🚫</div>
            <h1>Accès Refusé</h1>
            <p><?= htmlspecialchars($error_message) ?></p>
            
            <div class="forbidden-actions">
                <?php if ($is_logged_in): ?>
                    <a href="dashboard.php" class="btn-back">🏠 Retour au Dashboard</a>
                <?php else: ?>
                    <a href="../index.php" class="btn-login">🔑 Se connecter</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
