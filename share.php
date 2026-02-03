<?php
/**
 * Page publique de partage de chantier
 * Accessible sans authentification via un token unique ou un ID
 */

require_once 'includes/config.php';

// Récupérer le token ou l'ID depuis l'URL
$token = $_GET['token'] ?? '';
$chantier_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Vérifier qu'au moins un paramètre est fourni
if (empty($token) && $chantier_id <= 0) {
    http_response_code(400);
    die('Paramètre manquant (token ou id requis)');
}

// Récupérer le chantier selon le paramètre fourni
if (!empty($token)) {
    // Accès via token (lien de partage direct)
    $stmt = $pdo->prepare("
        SELECT * FROM chantiers
        WHERE share_token = ? AND is_public = 1
    ");
    $stmt->execute([$token]);
} else {
    // Accès via ID (depuis la page publique)
    $stmt = $pdo->prepare("
        SELECT * FROM chantiers
        WHERE id = ? AND is_public = 1
    ");
    $stmt->execute([$chantier_id]);
}

$chantier = $stmt->fetch();

// Vérifier que le chantier existe et est public
if (!$chantier) {
    http_response_code(404);
    die('Chantier non trouvé ou non disponible publiquement');
}

$chantier_id = $chantier['id'];

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
    <title><?= htmlspecialchars($chantier['nom']) ?> - Vue Publique</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#004E70">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8f9fa;
            color: #212529;
            line-height: 1.6;
        }

        /* Navigation */
        .public-nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1.25rem 3rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .nav-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-btn {
            padding: 0.65rem 1.5rem;
            background: white;
            color: #1a1a1a;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid #e0e0e0;
        }

        .back-btn:hover {
            background: #f8f9fa;
            transform: translateX(-4px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .public-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.6rem 1.25rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Header Section */
        .project-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 4rem 2rem 3rem;
            position: relative;
            overflow: hidden;
        }

        .project-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="60" height="60" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h60v60H0z" fill="none"/><path d="M30 0v60M0 30h60" stroke="rgba(255,255,255,0.03)" stroke-width="1"/></svg>');
            opacity: 0.5;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .project-header h1 {
            font-size: 3rem;
            font-weight: 900;
            color: white;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .project-subtitle {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 2.5rem;
            font-weight: 400;
        }

        .header-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            padding: 1.5rem 2.5rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            min-width: 160px;
        }

        .stat-number {
            display: block;
            font-size: 2.5rem;
            font-weight: 900;
            color: white;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        /* Timeline Styles */
        .timeline-wrapper {
            max-width: 1400px;
            margin: 3rem auto;
            padding: 0 2rem;
        }

        .timeline-container {
            display: flex;
            gap: 3rem;
            position: relative;
        }

        /* Year Labels Column */
        .timeline-labels {
            position: sticky;
            top: 100px;
            width: 100px;
            flex-shrink: 0;
            height: fit-content;
        }

        .timeline-year-label {
            font-size: 1.75rem;
            font-weight: 900;
            color: #667eea;
            padding: 1.25rem;
            text-align: center;
            background: white;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 2px solid #f0f0f0;
            letter-spacing: -0.02em;
        }

        /* Vertical Timeline Line */
        .timeline-line {
            width: 3px;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            flex-shrink: 0;
            position: relative;
            min-height: 100%;
            border-radius: 10px;
        }

        /* Timeline Content */
        .timeline-content {
            flex: 1;
            padding-bottom: 2rem;
        }

        .timeline-date-group {
            margin-bottom: 4rem;
            position: relative;
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .timeline-date-header {
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            background: white;
            color: #1a1a1a;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.15rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            position: relative;
            border-left: 4px solid #667eea;
        }

        .timeline-date-header svg {
            color: #667eea;
        }

        .timeline-date-header::before {
            content: '';
            position: absolute;
            left: -3.75rem;
            top: 50%;
            transform: translateY(-50%);
            width: 14px;
            height: 14px;
            background: #667eea;
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 0 0 3px #667eea;
        }

        .timeline-date-count {
            font-size: 0.85rem;
            font-weight: 500;
            color: #6c757d;
            background: #f8f9fa;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
        }

        .timeline-images-list {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .timeline-image-card {
            display: flex;
            gap: 2rem;
            background: white;
            border-radius: 20px;
            padding: 1.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .timeline-image-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
            border-color: rgba(102, 126, 234, 0.2);
        }

        .timeline-image-thumbnail {
            width: 280px;
            height: 200px;
            border-radius: 16px;
            overflow: hidden;
            flex-shrink: 0;
            background: #f0f0f0;
        }

        .timeline-image-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .timeline-image-card:hover .timeline-image-thumbnail img {
            transform: scale(1.05);
        }

        .timeline-image-metadata {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .phase-badge {
            display: inline-block;
            width: fit-content;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .image-date {
            font-size: 0.95rem;
            color: #6c757d;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .image-comment {
            font-size: 0.95rem;
            color: #495057;
            line-height: 1.7;
            margin: 0;
        }

        .image-comment strong {
            color: #1a1a1a;
            display: block;
            margin-bottom: 0.25rem;
        }

        .image-author {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: auto;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .timeline-empty {
            text-align: center;
            padding: 6rem 2rem;
            background: white;
            border-radius: 20px;
            margin: 2rem 0;
        }

        .empty-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-text {
            font-size: 1.25rem;
            color: #6c757d;
        }

        /* Footer */
        .footer {
            background: #1a1a1a;
            color: rgba(255, 255, 255, 0.7);
            padding: 3rem 2rem;
            margin-top: 6rem;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            text-align: center;
        }

        .footer-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.75rem;
        }

        .footer-text {
            font-size: 0.95rem;
            line-height: 1.7;
        }

        /* Modal */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 9999;
            cursor: pointer;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-close {
            position: absolute;
            top: 2rem;
            right: 2rem;
            color: white;
            font-size: 3rem;
            font-weight: 300;
            cursor: pointer;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .modal-image {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            border-radius: 8px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .timeline-container {
                gap: 2rem;
            }

            .timeline-labels {
                width: 80px;
            }

            .timeline-year-label {
                font-size: 1.5rem;
                padding: 1rem;
            }
        }

        @media (max-width: 768px) {
            .public-nav {
                padding: 1rem 1.5rem;
            }

            .project-header {
                padding: 3rem 1.5rem 2rem;
            }

            .project-header h1 {
                font-size: 2rem;
            }

            .project-subtitle {
                font-size: 1.1rem;
            }

            .header-stats {
                gap: 1rem;
            }

            .stat-card {
                padding: 1.25rem 2rem;
                min-width: 130px;
            }

            .stat-number {
                font-size: 2rem;
            }

            .timeline-wrapper {
                padding: 0 1.5rem;
            }

            .timeline-container {
                flex-direction: column;
                gap: 0;
            }

            .timeline-labels {
                position: static;
                width: 100%;
                display: flex;
                gap: 0.75rem;
                overflow-x: auto;
                padding-bottom: 1rem;
                margin-bottom: 2rem;
            }

            .timeline-year-label {
                min-width: 70px;
                margin-bottom: 0;
                font-size: 1.25rem;
                padding: 0.75rem;
            }

            .timeline-line {
                display: none;
            }

            .timeline-date-header::before {
                display: none;
            }

            .timeline-date-header {
                padding: 0.85rem 1.5rem;
                font-size: 1rem;
            }

            .timeline-image-card {
                flex-direction: column;
                gap: 1.25rem;
                padding: 1.25rem;
            }

            .timeline-image-thumbnail {
                width: 100%;
                height: 220px;
            }

            .modal-close {
                top: 1rem;
                right: 1rem;
                font-size: 2rem;
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="public-nav">
        <div class="nav-content">
            <a href="public.php" class="back-btn">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Retour aux projets</span>
            </a>
            <div class="public-badge">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M8 2C5.79086 2 4 3.79086 4 6C4 8.20914 5.79086 10 8 10C10.2091 10 12 8.20914 12 6" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M2 10C2 10 3 12 8 12C13 12 14 10 14 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                Vue publique
            </div>
        </div>
    </nav>

    <!-- Project Header -->
    <header class="project-header">
        <div class="header-content">
            <h1><?= htmlspecialchars($chantier['nom']) ?></h1>
            <p class="project-subtitle"><?= htmlspecialchars($chantier['adresse'] ?? 'Suivi de chantier') ?></p>

            <div class="header-stats">
                <div class="stat-card">
                    <span class="stat-number"><?= count($images) ?></span>
                    <span class="stat-label">Photos</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= count($images_by_date) ?></span>
                    <span class="stat-label">Jours documentés</span>
                </div>
                <?php if ($chantier['date_debut']): ?>
                <div class="stat-card">
                    <span class="stat-number"><?= date('d/m/Y', strtotime($chantier['date_debut'])) ?></span>
                    <span class="stat-label">Date de début</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Timeline Section -->
    <?php if (empty($images_by_date)): ?>
        <div class="timeline-wrapper">
            <div class="timeline-empty">
                <div class="empty-icon">📷</div>
                <p class="empty-text">Aucune photo disponible pour ce projet.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="timeline-wrapper">
            <div class="timeline-container">
                <!-- Year Labels -->
                <div class="timeline-labels">
                    <?php foreach ($years as $year): ?>
                        <div class="timeline-year-label"><?= $year ?></div>
                    <?php endforeach; ?>
                </div>

                <!-- Vertical Line -->
                <div class="timeline-line"></div>

                <!-- Timeline Content -->
                <div class="timeline-content">
                    <?php foreach ($images_by_date as $date => $date_images): ?>
                        <div class="timeline-date-group">
                            <div class="timeline-date-header">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="3" y="4" width="14" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M3 7H17" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M6 2V4M14 2V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                                <span><?= date('d/m/Y', strtotime($date)) ?></span>
                                <span class="timeline-date-count">
                                    <?= count($date_images) ?> photo<?= count($date_images) > 1 ? 's' : '' ?>
                                </span>
                            </div>

                            <div class="timeline-images-list">
                                <?php foreach ($date_images as $image): ?>
                                    <div class="timeline-image-card" onclick="openImageModal('uploads/<?= htmlspecialchars($image['filename']) ?>')">
                                        <div class="timeline-image-thumbnail">
                                            <img src="uploads/<?= htmlspecialchars($image['filename']) ?>"
                                                 alt="<?= htmlspecialchars($image['original_name']) ?>"
                                                 loading="lazy">
                                        </div>

                                        <div class="timeline-image-metadata">
                                            <span class="phase-badge"><?= $phases[$image['phase']] ?? 'Autre' ?></span>

                                            <div class="image-date">
                                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.5"/>
                                                    <path d="M8 5V8L10 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                                </svg>
                                                <?= $image['date_prise'] ? date('d/m/Y', strtotime($image['date_prise'])) : date('d/m/Y', strtotime($image['uploaded_at'])) ?>
                                            </div>

                                            <?php if ($image['commentaire']): ?>
                                                <p class="image-comment">
                                                    <strong>Commentaire</strong>
                                                    <?= nl2br(htmlspecialchars($image['commentaire'])) ?>
                                                </p>
                                            <?php endif; ?>

                                            <?php if ($image['nom'] && $image['prenom']): ?>
                                                <div class="image-author">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <circle cx="8" cy="5" r="3" stroke="currentColor" stroke-width="1.5"/>
                                                        <path d="M2 14C2 11.7909 4.68629 10 8 10C11.3137 10 14 11.7909 14 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                                    </svg>
                                                    Par <?= htmlspecialchars($image['prenom'] . ' ' . $image['nom']) ?>
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

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-title">🏗️ Plateforme de Suivi de Chantiers</div>
            <p class="footer-text">
                Cette page a été partagée publiquement par l'équipe du projet.<br>
                © <?= date('Y') ?> - Tous droits réservés
            </p>
        </div>
    </footer>

    <!-- Image Modal -->
    <div id="imageModal" class="image-modal" onclick="closeImageModal()">
        <div class="modal-close">&times;</div>
        <img id="modalImage" src="" class="modal-image" alt="Image agrandie">
    </div>

    <script>
        function openImageModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            modal.style.display = 'block';
            modalImage.src = imageSrc;
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeImageModal();
            }
        });

        // Prevent image click from closing modal
        document.getElementById('modalImage').addEventListener('click', function(e) {
            e.stopPropagation();
        });
    </script>
</body>
</html>
