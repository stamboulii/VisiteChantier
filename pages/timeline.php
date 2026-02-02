<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$chantier_id = intval($_GET['id'] ?? 0);

// Vérifier l'accès au chantier selon le rôle
requirePermission(
    canAccessChantier($chantier_id),
    "Vous n'avez pas accès à ce chantier"
);

// Récupérer les informations du chantier
$stmt = $pdo->prepare("SELECT * FROM chantiers WHERE id = ?");
$stmt->execute([$chantier_id]);
$chantier = $stmt->fetch();

if (!$chantier) {
    header('Location: dashboard.php');
    exit;
}

// Récupérer les images du chantier triées par date de prise de vue
$stmt_images = $pdo->prepare("
    SELECT i.*, u.nom, u.prenom
    FROM images i
    LEFT JOIN users u ON i.user_id = u.id
    WHERE i.chantier_id = ?
    ORDER BY i.date_prise ASC, i.uploaded_at ASC
");
$stmt_images->execute([$chantier_id]);
$images = $stmt_images->fetchAll();

// Grouper les images par date
$images_by_date = [];
$years = [];
foreach ($images as $image) {
    $date = $image['date_prise'] ? $image['date_prise'] : date('Y-m-d', strtotime($image['uploaded_at']));
    $year = date('Y', strtotime($date));

    if (!isset($images_by_date[$date])) {
        $images_by_date[$date] = [];
    }
    $images_by_date[$date][] = $image;

    // Collecter les années uniques
    if (!in_array($year, $years)) {
        $years[] = $year;
    }
}
sort($years); // Trier les années

// Définir les phases selon le type de projet
$type = $chantier['type'] ?? 'chantier';
if ($type === 'chantier') {
    $phases = [
        'fondations' => 'Fondations',
        'structure' => 'Structure',
        'clos_couvert' => 'Clos & Couvert',
        'second_oeuvre' => 'Second Œuvre',
        'finitions' => 'Finitions',
        'autres' => 'Autres'
    ];
} else if ($type === 'visite_commerciale' || $type === 'etat_des_lieux') {
    $phases = [
        'exterieur' => 'Extérieur / Façade',
        'entree' => 'Entrée / Hall',
        'sejour' => 'Séjour / Salon',
        'cuisine' => 'Cuisine',
        'chambre' => 'Chambre',
        'sdb' => 'Salle de Bain / WC',
        'balcon' => 'Balcon / Terrasse',
        'autres' => 'Autres'
    ];
} else {
    $phases = [
        'general' => 'Vue Générale',
        'detail' => 'Détails',
        'defaut' => 'Défauts / Points à revoir',
        'autres' => 'Autres'
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timeline - <?= htmlspecialchars($chantier['nom']) ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#004E70">
    <style>
        /* Timeline Styles - Design inspiré du schéma */
        .timeline-wrapper {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .timeline-container {
            display: flex;
            gap: 2rem;
            position: relative;
        }

        /* Colonne des labels (années) à gauche */
        .timeline-labels {
            position: sticky;
            top: 100px;
            width: 120px;
            flex-shrink: 0;
            height: fit-content;
        }

        .timeline-year-label {
            font-size: 1.5rem;
            font-weight: 800;
            color: #667eea;
            padding: 1rem;
            text-align: center;
            background: white;
            border-radius: 12px;
            margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
        }

        /* Ligne verticale */
        .timeline-line {
            width: 4px;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            flex-shrink: 0;
            position: relative;
            min-height: 100%;
        }

        /* Contenu principal (images et métadonnées) */
        .timeline-content {
            flex: 1;
            padding-bottom: 2rem;
        }

        .timeline-date-group {
            margin-bottom: 3rem;
            position: relative;
        }

        .timeline-date-header {
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            position: relative;
        }

        .timeline-date-header::before {
            content: '';
            position: absolute;
            left: -2.5rem;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            background: white;
            border: 4px solid #667eea;
            border-radius: 50%;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.2);
        }

        .timeline-images-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .timeline-image-card {
            display: flex;
            gap: 1.5rem;
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .timeline-image-card:hover {
            transform: translateX(10px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .timeline-image-thumbnail {
            width: 250px;
            height: 180px;
            border-radius: 12px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .timeline-image-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .timeline-image-card:hover .timeline-image-thumbnail img {
            transform: scale(1.1);
        }

        .timeline-image-metadata {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .timeline-image-metadata .phase {
            display: inline-block;
            width: fit-content;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .timeline-image-metadata .date {
            font-size: 0.95rem;
            color: #667eea;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .timeline-image-metadata .comment {
            font-size: 0.95rem;
            color: #495057;
            line-height: 1.6;
            margin: 0;
        }

        .timeline-image-metadata .author {
            font-size: 0.85rem;
            color: #6c757d;
            font-style: italic;
            margin-top: auto;
        }

        .timeline-empty {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .timeline-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .timeline-header h2 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .timeline-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .timeline-stat {
            background: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .timeline-stat strong {
            display: block;
            font-size: 2rem;
            font-weight: 800;
            color: #667eea;
        }

        .timeline-stat span {
            font-size: 0.9rem;
            color: #6c757d;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .timeline-container {
                flex-direction: column;
                gap: 1rem;
            }

            .timeline-labels {
                position: static;
                width: 100%;
                display: flex;
                gap: 0.5rem;
                overflow-x: auto;
            }

            .timeline-year-label {
                min-width: 80px;
                margin-bottom: 0;
            }

            .timeline-line {
                display: none;
            }

            .timeline-date-header::before {
                display: none;
            }

            .timeline-image-card {
                flex-direction: column;
            }

            .timeline-image-thumbnail {
                width: 100%;
                height: 200px;
            }
        }
    </style>
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
        <a href="chantier.php?id=<?= $chantier_id ?>" class="back-link">← Retour au chantier</a>

        <div class="timeline-header">
            <h2>📅 Timeline du Projet</h2>
            <p style="font-size: 1.2rem; color: #6c757d; margin: 0.5rem 0;">
                <?= htmlspecialchars($chantier['nom']) ?>
            </p>
            <div class="timeline-stats">
                <div class="timeline-stat">
                    <strong><?= count($images) ?></strong>
                    <span>Photos</span>
                </div>
                <div class="timeline-stat">
                    <strong><?= count($images_by_date) ?></strong>
                    <span>Jours documentés</span>
                </div>
                <?php if ($chantier['date_debut']): ?>
                <div class="timeline-stat">
                    <strong><?= date('d/m/Y', strtotime($chantier['date_debut'])) ?></strong>
                    <span>Date de début</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($images_by_date)): ?>
            <div class="timeline-empty">
                <p style="font-size: 1.2rem;">📷 Aucune photo disponible pour ce projet.</p>
                <a href="chantier.php?id=<?= $chantier_id ?>" class="btn-primary" style="display: inline-block; margin-top: 1rem; text-decoration: none;">
                    Ajouter des photos
                </a>
            </div>
        <?php else: ?>
            <div class="timeline-wrapper">
                <div class="timeline-container">
                    <!-- Colonne des labels (années) -->
                    <div class="timeline-labels">
                        <?php foreach ($years as $year): ?>
                            <div class="timeline-year-label"><?= $year ?></div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Ligne verticale -->
                    <div class="timeline-line"></div>

                    <!-- Contenu principal (images et métadonnées) -->
                    <div class="timeline-content">
                        <?php foreach ($images_by_date as $date => $date_images): ?>
                            <div class="timeline-date-group">
                                <div class="timeline-date-header">
                                    📅 <?= date('d/m/Y', strtotime($date)) ?>
                                    <span style="font-size: 0.85rem; font-weight: 400; opacity: 0.9;">
                                        (<?= count($date_images) ?> photo<?= count($date_images) > 1 ? 's' : '' ?>)
                                    </span>
                                </div>

                                <div class="timeline-images-list">
                                    <?php foreach ($date_images as $image): ?>
                                        <div class="timeline-image-card" onclick="openImageModal('../uploads/<?= htmlspecialchars($image['filename']) ?>')">
                                            <div class="timeline-image-thumbnail">
                                                <img src="../uploads/<?= htmlspecialchars($image['filename']) ?>"
                                                     alt="<?= htmlspecialchars($image['original_name']) ?>">
                                            </div>

                                            <div class="timeline-image-metadata">
                                                <span class="phase"><?= $phases[$image['phase']] ?? 'Autre' ?></span>

                                                <div class="date">
                                                    📅 Date: <?= $image['date_prise'] ? date('d/m/Y', strtotime($image['date_prise'])) : date('d/m/Y', strtotime($image['uploaded_at'])) ?>
                                                </div>

                                                <?php if ($image['commentaire']): ?>
                                                    <p class="comment">
                                                        <strong>Commentaire:</strong><br>
                                                        <?= nl2br(htmlspecialchars($image['commentaire'])) ?>
                                                    </p>
                                                <?php endif; ?>

                                                <?php if ($image['nom'] && $image['prenom']): ?>
                                                    <div class="author">
                                                        👤 Par <?= htmlspecialchars($image['prenom'] . ' ' . $image['nom']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal pour afficher l'image en grand -->
    <div id="imageModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; cursor: pointer;" onclick="closeImageModal()">
        <span style="position: absolute; top: 20px; right: 40px; color: white; font-size: 40px; font-weight: bold; cursor: pointer;">&times;</span>
        <img id="modalImage" src="" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); max-width: 90%; max-height: 90%; object-fit: contain;">
    </div>

    <script src="../js/main.js"></script>
</body>
</html>
