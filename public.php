<?php
/**
 * Page d'accueil publique
 * Liste tous les chantiers publics accessibles sans authentification
 */

require_once 'includes/config.php';

// Récupérer tous les chantiers publics
$stmt = $pdo->prepare("
    SELECT c.*,
           COUNT(i.id) as total_images,
           MAX(i.date_prise) as derniere_photo
    FROM chantiers c
    LEFT JOIN images i ON c.id = i.chantier_id
    WHERE c.is_public = 1
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$chantiers_publics = $stmt->fetchAll();

// Fonction pour obtenir l'icône selon le type
function getTypeIcon($type) {
    switch ($type) {
        case 'chantier': return '🏗️';
        case 'visite_commerciale': return '🏠';
        case 'etat_des_lieux': return '📋';
        default: return '📁';
    }
}

function getTypeLabel($type) {
    switch ($type) {
        case 'chantier': return 'Chantier / Construction';
        case 'visite_commerciale': return 'Visite Commerciale';
        case 'etat_des_lieux': return 'État des Lieux';
        default: return 'Autre';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projets Publics - Suivi de Chantiers</title>
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

        /* Modern Navigation */
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

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #1a1a1a;
        }

        .logo-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .login-btn {
            padding: 0.65rem 1.75rem;
            background: #1a1a1a;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .login-btn:hover {
            background: #333;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 5rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="60" height="60" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h60v60H0z" fill="none"/><path d="M30 0v60M0 30h60" stroke="rgba(255,255,255,0.03)" stroke-width="1"/></svg>');
            opacity: 0.5;
        }

        .hero-content {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 900;
            color: white;
            margin-bottom: 1.25rem;
            letter-spacing: -0.02em;
            text-align: center;
        }

        .hero-subtitle {
            font-size: 1.35rem;
            color: rgba(255, 255, 255, 0.95);
            text-align: center;
            max-width: 700px;
            margin: 0 auto 3rem;
            font-weight: 400;
            line-height: 1.7;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            padding: 2rem 3rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            min-width: 180px;
        }

        .stat-number {
            display: block;
            font-size: 3rem;
            font-weight: 900;
            color: white;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        /* Main Content */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 4rem 2rem;
        }

        .section-header {
            text-align: center;
            margin-bottom: 3.5rem;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }

        .section-description {
            font-size: 1.1rem;
            color: #6c757d;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Projects Grid */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 2.5rem;
        }

        .project-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: inherit;
            display: block;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .project-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
            border-color: rgba(102, 126, 234, 0.2);
        }

        .project-image {
            width: 100%;
            height: 280px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .project-image::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.5) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .project-card:hover .project-image::after {
            opacity: 1;
        }

        .project-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .project-card:hover .project-image img {
            transform: scale(1.08);
        }

        .project-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
        }

        .project-type-badge {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            background: rgba(255, 255, 255, 0.98);
            padding: 0.6rem 1.25rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #1a1a1a;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10;
        }

        .project-content {
            padding: 2rem;
        }

        .project-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 0.75rem;
            letter-spacing: -0.01em;
        }

        .project-address {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.95rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .project-description {
            font-size: 0.95rem;
            color: #495057;
            line-height: 1.7;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .project-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.25rem;
            border-top: 1px solid #f0f0f0;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }

        .view-project-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            margin-top: 1.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
        }

        .project-card:hover .view-project-btn {
            background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 6rem 2rem;
        }

        .empty-icon {
            font-size: 6rem;
            margin-bottom: 2rem;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 2.25rem;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 1rem;
        }

        .empty-description {
            font-size: 1.15rem;
            color: #6c757d;
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.7;
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

        /* Responsive */
        @media (max-width: 1024px) {
            .projects-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 2rem;
            }
        }

        @media (max-width: 768px) {
            .public-nav {
                padding: 1rem 1.5rem;
            }

            .hero {
                padding: 3.5rem 1.5rem;
            }

            .hero h1 {
                font-size: 2.25rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .hero-stats {
                gap: 1.5rem;
            }

            .stat-card {
                padding: 1.5rem 2rem;
                min-width: 140px;
            }

            .stat-number {
                font-size: 2.25rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .projects-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .container {
                padding: 3rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="public-nav">
        <div class="nav-content">
            <div class="logo">
                <div class="logo-icon">🏗️</div>
                <span>Visites & Suivis</span>
            </div>
            <a href="login.php" class="login-btn">
                <span>Se connecter</span>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Découvrez Nos Projets</h1>
            <p class="hero-subtitle">
                Explorez nos réalisations et suivez l'évolution de nos chantiers à travers des galeries photos détaillées et des timelines interactives.
            </p>

            <div class="hero-stats">
                <div class="stat-card">
                    <span class="stat-number"><?= count($chantiers_publics) ?></span>
                    <span class="stat-label">Projets Publics</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= array_sum(array_column($chantiers_publics, 'total_images')) ?></span>
                    <span class="stat-label">Photos Partagées</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container">
        <?php if (empty($chantiers_publics)): ?>
            <div class="empty-state">
                <div class="empty-icon">📂</div>
                <h2 class="empty-title">Aucun projet disponible</h2>
                <p class="empty-description">
                    Il n'y a actuellement aucun projet disponible publiquement. Revenez bientôt pour découvrir nos réalisations !
                </p>
            </div>
        <?php else: ?>
            <div class="section-header">
                <h2 class="section-title">Tous les Projets</h2>
                <p class="section-description">
                    Parcourez notre portfolio de projets et découvrez l'évolution de chaque chantier
                </p>
            </div>

            <div class="projects-grid">
                <?php foreach ($chantiers_publics as $chantier): ?>
                    <?php
                    // Récupérer une image de couverture (la plus récente)
                    $stmt_cover = $pdo->prepare("
                        SELECT filename FROM images
                        WHERE chantier_id = ?
                        ORDER BY date_prise DESC, uploaded_at DESC
                        LIMIT 1
                    ");
                    $stmt_cover->execute([$chantier['id']]);
                    $cover_image = $stmt_cover->fetch();
                    ?>

                    <a href="share.php?id=<?= $chantier['id'] ?>" class="project-card">
                        <div class="project-image">
                            <?php if ($cover_image): ?>
                                <img src="uploads/<?= htmlspecialchars($cover_image['filename']) ?>"
                                     alt="<?= htmlspecialchars($chantier['nom']) ?>">
                            <?php else: ?>
                                <div class="project-placeholder">
                                    <?= getTypeIcon($chantier['type']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="project-type-badge">
                                <?= getTypeIcon($chantier['type']) ?> <?= getTypeLabel($chantier['type']) ?>
                            </div>
                        </div>

                        <div class="project-content">
                            <h3 class="project-title"><?= htmlspecialchars($chantier['nom']) ?></h3>

                            <div class="project-address">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8 1C5.79086 1 4 2.79086 4 5C4 8 8 13 8 13C8 13 12 8 12 5C12 2.79086 10.2091 1 8 1Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="8" cy="5" r="1.5" stroke="currentColor" stroke-width="1.5"/>
                                </svg>
                                <?= htmlspecialchars($chantier['adresse']) ?>
                            </div>

                            <?php if ($chantier['description']): ?>
                                <p class="project-description">
                                    <?= htmlspecialchars($chantier['description']) ?>
                                </p>
                            <?php endif; ?>

                            <div class="project-meta">
                                <div class="meta-item">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="2" y="3" width="12" height="11" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                        <path d="M2 6H14" stroke="currentColor" stroke-width="1.5"/>
                                        <path d="M5 1V3M11 1V3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                    <?= date('d/m/Y', strtotime($chantier['date_debut'])) ?>
                                </div>
                                <div class="meta-item">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="2" y="3" width="12" height="10" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                        <circle cx="6" cy="7" r="1.5" stroke="currentColor" stroke-width="1.5"/>
                                        <path d="M14 10L11 7L2 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <?= $chantier['total_images'] ?> photo<?= $chantier['total_images'] > 1 ? 's' : '' ?>
                                </div>
                            </div>

                            <button class="view-project-btn">
                                <span>Voir la timeline</span>
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-title">🏗️ Plateforme de Suivi de Chantiers</div>
            <p class="footer-text">
                Tous les projets affichés sont partagés publiquement par leurs gestionnaires.<br>
                © <?= date('Y') ?> - Tous droits réservés
            </p>
        </div>
    </footer>
</body>
</html>
