<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Vérifier que l'utilisateur est admin
requireAdmin("Seuls les administrateurs peuvent gérer les assignations");

$chantier_id = intval($_GET['id'] ?? 0);

// Vérifier que le chantier existe
$stmt = $pdo->prepare("SELECT * FROM chantiers WHERE id = ?");
$stmt->execute([$chantier_id]);
$chantier = $stmt->fetch();

if (!$chantier) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$message_type = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Ajouter un architecte
    if ($action === 'assign') {
        $architect_id = intval($_POST['architect_id'] ?? 0);
        
        if ($architect_id > 0) {
            // Vérifier que c'est bien un architecte
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? AND role = 'architect'");
            $stmt->execute([$architect_id]);
            
            if ($stmt->fetch()) {
                // Vérifier qu'il n'est pas déjà assigné
                $stmt = $pdo->prepare("SELECT id FROM chantier_assignments WHERE chantier_id = ? AND user_id = ?");
                $stmt->execute([$chantier_id, $architect_id]);
                
                if (!$stmt->fetch()) {
                    // Ajouter l'assignation
                    $stmt = $pdo->prepare("INSERT INTO chantier_assignments (chantier_id, user_id, assigned_by) VALUES (?, ?, ?)");
                    if ($stmt->execute([$chantier_id, $architect_id, getUserId()])) {
                        $message = "Architecte assigné avec succès!";
                        $message_type = 'success';
                        
                        logAdminAction('assign_architect', [
                            'chantier_id' => $chantier_id,
                            'architect_id' => $architect_id
                        ]);
                    } else {
                        $message = "Erreur lors de l'assignation";
                        $message_type = 'error';
                    }
                } else {
                    $message = "Cet architecte est déjà assigné à ce chantier";
                    $message_type = 'error';
                }
            } else {
                $message = "Utilisateur invalide";
                $message_type = 'error';
            }
        }
    }
    
    // Retirer un architecte
    if ($action === 'unassign') {
        $architect_id = intval($_POST['architect_id'] ?? 0);
        
        $stmt = $pdo->prepare("DELETE FROM chantier_assignments WHERE chantier_id = ? AND user_id = ?");
        if ($stmt->execute([$chantier_id, $architect_id])) {
            $message = "Architecte retiré avec succès!";
            $message_type = 'success';
            
            logAdminAction('unassign_architect', [
                'chantier_id' => $chantier_id,
                'architect_id' => $architect_id
            ]);
        } else {
            $message = "Erreur lors du retrait";
            $message_type = 'error';
        }
    }
}

// Récupérer les architectes assignés
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.nom, u.prenom, u.email, ca.assigned_at,
           (SELECT COUNT(*) FROM images WHERE chantier_id = ? AND user_id = u.id) as nb_photos
    FROM chantier_assignments ca
    INNER JOIN users u ON ca.user_id = u.id
    WHERE ca.chantier_id = ?
    ORDER BY u.nom, u.prenom
");
$stmt->execute([$chantier_id, $chantier_id]);
$assigned_architects = $stmt->fetchAll();

// Récupérer les architectes NON assignés
$assigned_ids = array_column($assigned_architects, 'id');
$placeholders = !empty($assigned_ids) ? str_repeat('?,', count($assigned_ids) - 1) . '?' : '';

if (!empty($assigned_ids)) {
    $stmt = $pdo->prepare("
        SELECT id, username, nom, prenom, email 
        FROM users 
        WHERE role = 'architect' AND id NOT IN ($placeholders)
        ORDER BY nom, prenom
    ");
    $stmt->execute($assigned_ids);
} else {
    $stmt = $pdo->query("
        SELECT id, username, nom, prenom, email 
        FROM users 
        WHERE role = 'architect'
        ORDER BY nom, prenom
    ");
}
$available_architects = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Assignations - <?= htmlspecialchars($chantier['nom']) ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🏗️ Suivi Chantiers</div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="nouveau-chantier.php">Nouveau Chantier</a></li>
                <li><a href="users-management.php">Utilisateurs</a></li>
            </ul>
            <div class="user-info">
                <span class="user-badge"><?= getRoleBadge($user_role) ?> <?= htmlspecialchars($nom_complet) ?></span>
                <form method="POST" action="logout.php" style="display: inline;">
                    <button type="submit" class="logout-btn">Déconnexion</button>
                </form>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="dashboard">
            <a href="chantier.php?id=<?= $chantier_id ?>" class="back-link">← Retour au chantier</a>
            
            <div class="dashboard-header">
                <div>
                    <h1>👥 Gérer les Assignations</h1>
                    <p style="color: #7f8c8d;"><?= htmlspecialchars($chantier['nom']) ?></p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Barre de recherche -->
            <div class="search-container" style="margin-top: 1.5rem;">
                <div class="search-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="architectSearch" placeholder="Rechercher un architecte par nom, email ou username..." class="search-input">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
                
                <!-- Architectes assignés -->
                <div>
                    <h2 style="color: #2c3e50; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        ✅ Architectes Assignés
                        <span style="background: var(--gradient-1); color: white; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.9rem;">
                            <?= count($assigned_architects) ?>
                        </span>
                    </h2>
                    
                    <?php if (empty($assigned_architects)): ?>
                        <div class="empty-state" style="padding: 2rem;">
                            <p style="font-size: 1rem; margin-bottom: 1rem;">Aucun architecte assigné</p>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($assigned_architects as $architect): ?>
                                <div class="architect-card assigned">
                                    <div class="architect-info">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <span style="font-size: 1.5rem;">👨‍💼</span>
                                            <strong style="font-size: 1.1rem; color: #2c3e50;">
                                                <?= htmlspecialchars($architect['prenom'] . ' ' . $architect['nom']) ?>
                                            </strong>
                                        </div>
                                        <p style="color: #7f8c8d; font-size: 0.9rem; margin-bottom: 0.3rem;">
                                            📧 <?= htmlspecialchars($architect['email']) ?>
                                        </p>
                                        <p style="color: #7f8c8d; font-size: 0.9rem; margin-bottom: 0.3rem;">
                                            🔑 <?= htmlspecialchars($architect['username']) ?>
                                        </p>
                                        <p style="color: #7f8c8d; font-size: 0.85rem; margin-bottom: 0.5rem;">
                                            📸 <?= $architect['nb_photos'] ?> photo(s) uploadée(s)
                                        </p>
                                        <p style="color: #95a5a6; font-size: 0.8rem;">
                                            Assigné le <?= date('d/m/Y à H:i', strtotime($architect['assigned_at'])) ?>
                                        </p>
                                    </div>
                                    <form method="POST" style="margin-top: 1rem;" onsubmit="return confirm('Voulez-vous vraiment retirer cet architecte du chantier ?')">
                                        <input type="hidden" name="action" value="unassign">
                                        <input type="hidden" name="architect_id" value="<?= $architect['id'] ?>">
                                        <button type="submit" class="btn-remove">
                                            🗑️ Retirer
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Architectes disponibles -->
                <div>
                    <h2 style="color: #2c3e50; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        ➕ Architectes Disponibles
                        <span style="background: var(--gradient-3); color: white; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.9rem;">
                            <?= count($available_architects) ?>
                        </span>
                    </h2>
                    
                    <?php if (empty($available_architects)): ?>
                        <div class="empty-state" style="padding: 2rem;">
                            <p style="font-size: 1rem; margin-bottom: 1rem;">Tous les architectes sont déjà assignés</p>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($available_architects as $architect): ?>
                                <div class="architect-card available">
                                    <div class="architect-info">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <span style="font-size: 1.5rem;">👤</span>
                                            <strong style="font-size: 1.1rem; color: #2c3e50;">
                                                <?= htmlspecialchars($architect['prenom'] . ' ' . $architect['nom']) ?>
                                            </strong>
                                        </div>
                                        <p style="color: #7f8c8d; font-size: 0.9rem; margin-bottom: 0.3rem;">
                                            📧 <?= htmlspecialchars($architect['email']) ?>
                                        </p>
                                        <p style="color: #7f8c8d; font-size: 0.9rem;">
                                            🔑 <?= htmlspecialchars($architect['username']) ?>
                                        </p>
                                    </div>
                                    <form method="POST" style="margin-top: 1rem;">
                                        <input type="hidden" name="action" value="assign">
                                        <input type="hidden" name="architect_id" value="<?= $architect['id'] ?>">
                                        <button type="submit" class="btn-assign">
                                            ➕ Assigner
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .architect-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 2px solid #e9ecef;
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .architect-card.assigned {
            border-left: 4px solid #27ae60;
        }
        
        .architect-card.available {
            border-left: 4px solid #3498db;
        }
        
        .architect-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .btn-assign {
            background: var(--gradient-4);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67, 233, 123, 0.3);
        }
        
        .btn-assign:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 233, 123, 0.4);
        }
        
        .btn-remove {
            background: var(--gradient-2);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
        }
        
        .btn-remove:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 87, 108, 0.4);
        }
        
        @media (max-width: 768px) {
            .container > div > div:nth-child(3) {
                grid-template-columns: 1fr !important;
            }
        }

        /* Styles pour la barre de recherche */
        .search-container {
            margin-bottom: 1.5rem;
        }

        .search-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            background: white;
            border-radius: 12px;
            padding: 0.5rem 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .search-wrapper:focus-within {
            border-color: #3498db;
            box-shadow: 0 4px 20px rgba(52, 152, 219, 0.15);
            transform: translateY(-2px);
        }

        .search-icon {
            font-size: 1.2rem;
            margin-right: 0.8rem;
        }

        .search-input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 1rem;
            color: #2c3e50;
            background: transparent;
            padding: 0.5rem 0;
        }

        .search-input::placeholder {
            color: #95a5a6;
        }
    </style>

    <script>
        document.getElementById('architectSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.architect-card');
            
            cards.forEach(card => {
                const info = card.querySelector('.architect-info').textContent.toLowerCase();
                if (info.includes(searchTerm)) {
                    card.style.display = 'block';
                    card.style.animation = 'fadeIn 0.3s ease';
                } else {
                    card.style.display = 'none';
                }
            });

            // Gérer l'affichage des colonnes vides si besoin
            updateEmptyStates();
        });

        function updateEmptyStates() {
            // Optionnel: On pourrait cacher les titres de section ou afficher un message 
            // "Aucun résultat" si toutes les cartes d'une colonne sont cachées.
        }

        // Animation de base
        const style = document.createElement('style');
        style.innerHTML = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
