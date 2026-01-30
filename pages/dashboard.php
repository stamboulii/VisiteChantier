<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Récupérer les chantiers selon le rôle
$chantier_ids = getAccessibleChantierIds();

if (empty($chantier_ids)) {
    $chantiers = [];
    $stats = [
        'total_chantiers' => 0,
        'chantiers_en_cours' => 0,
        'total_images' => 0,
        'projets_par_type' => []
    ];
} else {
    $placeholders = str_repeat('?,', count($chantier_ids) - 1) . '?';
    
    // Récupérer les chantiers accessibles
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(i.id) as nb_images,
               MAX(i.uploaded_at) as derniere_photo
        FROM chantiers c
        LEFT JOIN images i ON c.id = i.chantier_id
        WHERE c.id IN ($placeholders)
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute($chantier_ids);
    $chantiers = $stmt->fetchAll();
    
    // Statistiques selon le rôle
    $stats = getUserStats();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Suivi de Chantiers</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#004E70">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🏘️ Visites & Suivis</div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="nouveau-chantier.php">Nouveau Projet</a></li>
                    <li><a href="users-management.php">Utilisateurs</a></li>
                <?php endif; ?>
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
            <div class="dashboard-header">
                <div>
                    <h1>Tableau de bord</h1>
                    <p style="color: #7f8c8d;"><?= isAdmin() ? 'Gérez vos projets et suivez leur avancement' : 'Suivez l\'avancement de vos projets assignés' ?></p>
                </div>
                <?php if (isAdmin()): ?>
                    <a href="nouveau-chantier.php" class="btn-primary">+ Nouveau Projet</a>
                <?php endif; ?>
            </div>

            <!-- Statistiques -->
            <div class="stats-grid">
                <div class="stat-card stat-card-1">
                    <h3><?= $stats['total_chantiers'] ?></h3>
                    <p>Total Projets</p>
                </div>
                <div class="stat-card stat-card-2">
                    <h3><?= $stats['chantiers_en_cours'] ?></h3>
                    <p>En Cours</p>
                </div>
                <div class="stat-card stat-card-3">
                    <h3><?= $stats['total_photos'] ?? $stats['total_images'] ?></h3>
                    <p>Photos Uploadées</p>
                </div>
            </div>

            <!-- Barre de recherche -->
            <div class="search-container" style="margin: 2rem 0;">
                <div class="search-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="chantierSearch" placeholder="Rechercher un projet par nom ou adresse..." class="search-input">
                </div>
            </div>

            <!-- Liste des chantiers -->
            <h2 style="margin-bottom: 1rem; color: #2c3e50;"><?= isAdmin() ? 'Tous les Projets' : 'Mes Projets Assignés' ?></h2>
            
            <?php if (empty($chantiers)): ?>
                <div class="empty-state">
                    <p><?= isAdmin() ? 'Aucun projet créé.' : 'Aucun projet ne vous a été assigné.' ?></p>
                    <?php if (isAdmin()): ?>
                        <a href="nouveau-chantier.php" class="btn-primary">Créer votre premier projet</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="chantiers-grid" id="chantiersGrid">
                    <?php foreach ($chantiers as $chantier): ?>
                        <div class="chantier-card" onclick="location.href='chantier.php?id=<?= $chantier['id'] ?>'">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <h3 style="margin: 0;"><?= htmlspecialchars($chantier['nom']) ?></h3>
                                <span style="font-size: 1.2rem;" title="<?= getProjectTypeLabel($chantier['type'] ?? 'chantier') ?>">
                                    <?= getProjectTypeIcon($chantier['type'] ?? 'chantier') ?>
                                </span>
                            </div>
                            <p>📍 <span class="chantier-address"><?= htmlspecialchars($chantier['adresse']) ?></span></p>
                            <p>📅 Début: <?= date('d/m/Y', strtotime($chantier['date_debut'])) ?></p>
                            <p>📸 <?= $chantier['nb_images'] ?> photo(s)</p>
                            <?php if ($chantier['derniere_photo']): ?>
                                <p style="font-size: 0.85rem;">Dernière photo: <?= date('d/m/Y H:i', strtotime($chantier['derniere_photo'])) ?></p>
                            <?php endif; ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                                <span class="chantier-status status-<?= $chantier['statut'] ?>">
                                    <?php
                                        $statuts = [
                                            'en_cours' => 'En cours',
                                            'termine' => 'Terminé',
                                            'en_pause' => 'En pause'
                                        ];
                                        echo $statuts[$chantier['statut']];
                                    ?>
                                </span>
                                <?php if (isset($chantier['type']) && $chantier['type'] !== 'chantier'): ?>
                                    <span style="font-size: 0.75rem; color: #95a5a6; background: #f8f9fa; padding: 2px 8px; border-radius: 10px;">
                                        <?= getProjectTypeLabel($chantier['type']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="noResults" class="empty-state" style="display: none; padding: 2rem;">
                    <p>Aucun projet ne correspond à votre recherche.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .search-container {
            margin-bottom: 2rem;
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

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <script>
        document.getElementById('chantierSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.chantier-card');
            let hasVisibleCards = false;
            
            cards.forEach(card => {
                const name = card.querySelector('h3').textContent.toLowerCase();
                const address = card.querySelector('.chantier-address').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || address.includes(searchTerm)) {
                    card.style.display = 'block';
                    card.style.animation = 'fadeIn 0.3s ease';
                    hasVisibleCards = true;
                } else {
                    card.style.display = 'none';
                }
            });

            const noResults = document.getElementById('noResults');
            if (noResults) {
                noResults.style.display = (searchTerm !== '' && !hasVisibleCards) ? 'block' : 'none';
            }
        });
    </script>
    <script src="../js/main.js"></script>
</body>
</html>
