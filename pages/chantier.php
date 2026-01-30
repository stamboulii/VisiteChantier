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

// Récupérer les données du lot depuis le template spécifique si applicable
$lot_data = null;
if (!empty($chantier['lot_id'])) {
    $template_name = !empty($chantier['template_file']) ? $chantier['template_file'] : 'default_template.json';
    $template_path = '../templates/' . basename($template_name);
    
    if (file_exists($template_path)) {
        $template_json = json_decode(file_get_contents($template_path), true);
        if (isset($template_json['parcelData']['parcelList'][$chantier['lot_id']])) {
            $lot_data = $template_json['parcelData']['parcelList'][$chantier['lot_id']];
        }
    }
}

// Récupérer les images du chantier
$stmt_images = $pdo->prepare("
    SELECT * FROM images 
    WHERE chantier_id = ? 
    ORDER BY uploaded_at DESC
");
$stmt_images->execute([$chantier_id]);
$images = $stmt_images->fetchAll();

// Récupérer les architectes assignés
$stmt_architects = $pdo->prepare("
    SELECT u.id, u.username, u.nom, u.prenom 
    FROM chantier_assignments ca
    INNER JOIN users u ON ca.user_id = u.id
    WHERE ca.chantier_id = ?
    ORDER BY u.nom, u.prenom
");
$stmt_architects->execute([$chantier_id]);
$assigned_architects = $stmt_architects->fetchAll();

// Traitement de l'upload
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    // Vérifier la permission d'upload
    if (!canUploadImage($chantier_id)) {
        $message = '<div class="alert alert-error">Vous n\'avez pas la permission d\'uploader des photos sur ce chantier</div>';
    } else {
    $commentaire = trim($_POST['commentaire'] ?? '');
    $phase = $_POST['phase'] ?? 'autres';
    
    $file = $_FILES['image'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($extension, ALLOWED_EXTENSIONS) && $file['size'] <= MAX_FILE_SIZE) {
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $destination = UPLOAD_DIR . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $stmt_insert = $pdo->prepare("
                    INSERT INTO images (chantier_id, user_id, filename, original_name, commentaire, phase) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt_insert->execute([$chantier_id, $user_id, $filename, $file['name'], $commentaire, $phase])) {
                    $message = '<div class="alert alert-success">Photo uploadée avec succès !</div>';
                    // Recharger les images
                    $stmt_images->execute([$chantier_id]);
                    $images = $stmt_images->fetchAll();
                } else {
                    $message = '<div class="alert alert-error">Erreur lors de l\'enregistrement en base de données</div>';
                }
            } else {
                $message = '<div class="alert alert-error">Erreur lors de l\'upload du fichier</div>';
            }
        } else {
            $message = '<div class="alert alert-error">Format ou taille de fichier non autorisé (max 5MB, formats: JPG, PNG, GIF)</div>';
        }
    } else {
        $message = '<div class="alert alert-error">Erreur lors de l\'upload</div>';
    }
    } // Fermeture canUploadImage
}

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

$statuts = [
    'en_cours' => 'En cours',
    'termine' => 'Terminé',
    'en_pause' => 'En pause'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($chantier['nom']) ?> - Suivi de Chantiers</title>
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
        <div class="chantier-detail">
            <a href="dashboard.php" class="back-link">← Retour au dashboard</a>
            
            <!-- Informations du projet -->
            <div class="chantier-info">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 style="margin: 0;"><?= htmlspecialchars($chantier['nom']) ?></h2>
                    <span class="badge-role" style="background: #f8f9fa; color: #2c3e50; border: 1px solid #dee2e6;">
                        <?= getProjectTypeIcon($type) ?> <?= getProjectTypeLabel($type) ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Lieu / Adresse:</span>
                    <span class="info-value"><?= htmlspecialchars($chantier['adresse']) ?></span>
                </div>
                <?php if ($chantier['lot_id']): ?>
                <div class="info-row">
                    <span class="info-label">Lot lié:</span>
                    <span class="info-value"><strong>Lot <?= htmlspecialchars($chantier['lot_id']) ?></strong></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Description:</span>
                    <span class="info-value"><?= htmlspecialchars($chantier['description'] ?? 'Aucune description') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date de début:</span>
                    <span class="info-value"><?= date('d/m/Y', strtotime($chantier['date_debut'])) ?></span>
                </div>
                <?php if ($chantier['date_fin_prevue']): ?>
                <div class="info-row">
                    <span class="info-label">Date de fin prévue:</span>
                    <span class="info-value"><?= date('d/m/Y', strtotime($chantier['date_fin_prevue'])) ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Statut:</span>
                    <span class="chantier-status status-<?= $chantier['statut'] ?>">
                        <?= $statuts[$chantier['statut']] ?>
                    </span>
                    <?php if (isAdmin()): ?>
                        <div style="margin-top: 1.5rem;">
                            <a href="edit-chantier.php?id=<?= $chantier_id ?>" class="btn-primary" style="text-decoration: none; display: inline-block;">✏️ Modifier le projet</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($assigned_architects)): ?>
                <div class="info-row">
                    <span class="info-label">Utilisateurs assignés:</span>
                    <span class="info-value">
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            <?php foreach ($assigned_architects as $architect): ?>
                                <span style="background: var(--gradient-3); color: white; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                    👨‍💼 <?= htmlspecialchars($architect['prenom'] . ' ' . $architect['nom']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </span>
                </div>
                <?php endif; ?>
                
                <?php if (isAdmin()): ?>
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid #dee2e6;">
                        <a href="manage-assignments.php?id=<?= $chantier_id ?>" class="btn-primary" style="text-decoration: none; display: inline-flex;">
                            👥 Gérer les Assignations
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upload section -->
            <div class="upload-section">
                <h3>📤 Ajouter une photo</h3>
                <?= $message ?>
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group">
                        <label for="image">Sélectionner une photo</label>
                        <input type="file" id="image" name="image" accept="image/*" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phase"><?= $type === 'chantier' ? 'Phase du chantier' : 'Zone / Catégorie' ?></label>
                        <select id="phase" name="phase" style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px;">
                            <?php foreach ($phases as $key => $label): ?>
                                <option value="<?= $key ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="commentaire">Commentaire (optionnel)</label>
                        <textarea id="commentaire" name="commentaire" rows="3" 
                                  style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; font-family: inherit;"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-primary">Uploader la photo</button>
                </form>
            </div>

            <!-- Galerie -->
            <div class="gallery-header">
                <h3>📷 Galerie Photos</h3>
                <span class="gallery-count"><?= count($images) ?> photo(s)</span>
            </div>
            
            <?php if (empty($images)): ?>
                <div class="empty-state">
                    <p>Aucune photo pour ce projet. Uploadez votre première photo ci-dessus !</p>
                </div>
            <?php else: ?>
                <div class="gallery">
                    <?php foreach ($images as $image): ?>
                        <div class="gallery-item">
                            <img src="../uploads/<?= htmlspecialchars($image['filename']) ?>" 
                                 alt="<?= htmlspecialchars($image['original_name']) ?>"
                                 onclick="openImageModal('../uploads/<?= htmlspecialchars($image['filename']) ?>')">
                            <div class="gallery-info">
                                <span class="phase"><?= $phases[$image['phase']] ?? 'Autre' ?></span>
                                <p class="date">📅 <?= date('d/m/Y à H:i', strtotime($image['uploaded_at'])) ?></p>
                                <?php if ($image['commentaire']): ?>
                                    <p class="comment"><?= htmlspecialchars($image['commentaire']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal pour afficher l'image en grand -->
    <div id="imageModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; cursor: pointer;" onclick="closeImageModal()">
        <span style="position: absolute; top: 20px; right: 40px; color: white; font-size: 40px; font-weight: bold; cursor: pointer;">&times;</span>
        <img id="modalImage" src="" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); max-width: 90%; max-height: 90%; object-fit: contain;">
    </div>

    <script src="../js/main.js"></script>
</body>
</html>
