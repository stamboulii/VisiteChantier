<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Vérifier que l'utilisateur est admin
requireAdmin("Seuls les administrateurs peuvent modifier les chantiers");

$chantier_id = intval($_GET['id'] ?? 0);

// Récupérer les informations du chantier
$stmt = $pdo->prepare("SELECT * FROM chantiers WHERE id = ?");
$stmt->execute([$chantier_id]);
$chantier = $stmt->fetch();

if (!$chantier) {
    header('Location: dashboard.php');
    exit;
}

// Récupérer la liste des architectes pour l'assignation
$stmt_architects = $pdo->prepare("SELECT id, username, nom, prenom FROM users WHERE role = 'architect' ORDER BY nom, prenom");
$stmt_architects->execute();
$architects = $stmt_architects->fetchAll();

// Récupérer les architectes déjà assignés
$stmt_assigned = $pdo->prepare("SELECT user_id FROM chantier_assignments WHERE chantier_id = ?");
$stmt_assigned->execute([$chantier_id]);
$assigned_ids = $stmt_assigned->fetchAll(PDO::FETCH_COLUMN);

// Lister les templates disponibles
$templates_dir = '../templates/';
$template_files = [];
if (is_dir($templates_dir)) {
    $files = scandir($templates_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $template_files[] = $file;
        }
    }
}

// Charger les lots du template actuel si présent
$current_lots = [];
if (!empty($chantier['template_file'])) {
    $current_template_path = '../templates/' . basename($chantier['template_file']);
    if (file_exists($current_template_path)) {
        $template_data = json_decode(file_get_contents($current_template_path), true);
        if (isset($template_data['parcelData']['parcelList'])) {
            $current_lots = array_keys($template_data['parcelData']['parcelList']);
            sort($current_lots);
        }
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date_debut = $_POST['date_debut'] ?? '';
    $date_fin_prevue = $_POST['date_fin_prevue'] ?? null;
    $statut = $_POST['statut'] ?? 'en_cours';
    $type = $_POST['type'] ?? 'chantier';
    $template_file = $_POST['template_file'] ?? $chantier['template_file'];
    $lot_id = ($type === 'visite_commerciale' || $type === 'etat_des_lieux') ? ($_POST['lot_id'] ?? null) : null;
    $new_assigned_architects = $_POST['architects'] ?? [];
    
    if (empty($nom) || empty($adresse) || empty($date_debut)) {
        $message = '<div class="alert alert-error">Veuillez remplir tous les champs obligatoires</div>';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Mise à jour du projet
            $stmt = $pdo->prepare("
                UPDATE chantiers 
                SET nom = ?, adresse = ?, description = ?, date_debut = ?, date_fin_prevue = ?, statut = ?, type = ?, lot_id = ?, template_file = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([$nom, $adresse, $description, $date_debut, $date_fin_prevue ?: null, $statut, $type, $lot_id, $template_file, $chantier_id])) {
                
                // Mettre à jour les assignations
                // 1. Supprimer les anciennes assignations
                $stmt_delete = $pdo->prepare("DELETE FROM chantier_assignments WHERE chantier_id = ?");
                $stmt_delete->execute([$chantier_id]);
                
                // 2. Ajouter les nouvelles assignations
                if (!empty($new_assigned_architects)) {
                    $stmt_assign = $pdo->prepare("
                        INSERT INTO chantier_assignments (chantier_id, user_id, assigned_by) 
                        VALUES (?, ?, ?)
                    ");
                    
                    foreach ($new_assigned_architects as $architect_id) {
                        $stmt_assign->execute([$chantier_id, $architect_id, $user_id]);
                    }
                }
                
                $pdo->commit();
                
                logAdminAction('update_project', [
                    'project_id' => $chantier_id,
                    'nom' => $nom,
                    'type' => $type,
                    'lot_id' => $lot_id
                ]);
                
                header("Location: chantier.php?id=$chantier_id&success=1");
                exit;
            } else {
                $pdo->rollBack();
                $message = '<div class="alert alert-error">Erreur lors de la modification du chantier</div>';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = '<div class="alert alert-error">Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le Chantier - <?= htmlspecialchars($chantier['nom']) ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🏘️ Visites & Suivis</div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="nouveau-chantier.php">Nouveau Projet</a></li>
            </ul>
            <div class="user-info">
                <span>👤 <?= htmlspecialchars($nom_complet) ?></span>
                <form method="POST" action="logout.php" style="display: inline;">
                    <button type="submit" class="logout-btn">Déconnexion</button>
                </form>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="dashboard">
            <a href="chantier.php?id=<?= $chantier_id ?>" style="color: #3498db; text-decoration: none; margin-bottom: 1rem; display: inline-block;">← Retour au projet</a>
            
            <h1 style="margin-bottom: 2rem; color: #2c3e50;">Modifier le projet</h1>

            <?= $message ?>

            <form method="POST" class="auth-box" style="max-width: 800px; margin: 0 auto; padding: 2rem;">
                <div class="form-group">
                    <label for="type">Type de projet *</label>
                    <select id="type" name="type" required onchange="toggleLotId(this.value)"
                            style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px;">
                        <option value="chantier" <?= $chantier['type'] === 'chantier' ? 'selected' : '' ?>>🏗️ Chantier / Construction</option>
                        <option value="visite_commerciale" <?= $chantier['type'] === 'visite_commerciale' ? 'selected' : '' ?>>🏠 Visite Commerciale</option>
                        <option value="etat_des_lieux" <?= $chantier['type'] === 'etat_des_lieux' ? 'selected' : '' ?>>📋 État des Lieux</option>
                        <option value="autre" <?= $chantier['type'] === 'autre' ? 'selected' : '' ?>>📁 Autre</option>
                    </select>
                </div>

                <div class="form-group" id="template_group" style="<?= ($chantier['type'] === 'visite_commerciale' || $chantier['type'] === 'etat_des_lieux') ? 'display: block;' : 'display: none;' ?>">
                    <label for="template_file">Catalogue Immobilier (Template JSON)</label>
                    <select id="template_file" name="template_file" onchange="loadLots(this.value)"
                            style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px;">
                        <option value="">-- Sélectionner un catalogue --</option>
                        <?php foreach ($template_files as $file): ?>
                            <option value="<?= htmlspecialchars($file) ?>" <?= $chantier['template_file'] === $file ? 'selected' : '' ?>>
                                <?= htmlspecialchars($file) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="lot_group" style="<?= ($chantier['type'] === 'visite_commerciale' || $chantier['type'] === 'etat_des_lieux') ? 'display: block;' : 'display: none;' ?>">
                    <label for="lot_id">Lier à un lot</label>
                    <select id="lot_id" name="lot_id" 
                            style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px;">
                        <option value="">-- Sélectionner un lot --</option>
                        <?php foreach ($current_lots as $lot): ?>
                            <option value="<?= htmlspecialchars($lot) ?>" <?= $chantier['lot_id'] === $lot ? 'selected' : '' ?>>
                                Lot <?= htmlspecialchars($lot) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="nom">Nom du projet *</label>
                    <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($chantier['nom']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="adresse">Adresse *</label>
                    <input type="text" id="adresse" name="adresse" value="<?= htmlspecialchars($chantier['adresse']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description (optionnelle)</label>
                    <textarea id="description" name="description" rows="4"><?= htmlspecialchars($chantier['description']) ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="date_debut">Date de début *</label>
                        <input type="date" id="date_debut" name="date_debut" value="<?= $chantier['date_debut'] ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="date_fin_prevue">Date de fin prévue</label>
                        <input type="date" id="date_fin_prevue" name="date_fin_prevue" value="<?= $chantier['date_fin_prevue'] ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="statut">Statut</label>
                    <select id="statut" name="statut" required>
                        <option value="en_cours" <?= $chantier['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                        <option value="termine" <?= $chantier['statut'] === 'termine' ? 'selected' : '' ?>>Terminé</option>
                        <option value="en_pause" <?= $chantier['statut'] === 'en_pause' ? 'selected' : '' ?>>En pause</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Assigner des architectes (Maintenez Ctrl pour choix multiples)</label>
                    <select name="architects[]" multiple style="height: 120px;">
                        <?php foreach ($architects as $arch): ?>
                            <option value="<?= $arch['id'] ?>" <?= in_array($arch['id'], $assigned_ids) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($arch['prenom'] . ' ' . $arch['nom'] . ' (' . $arch['username'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p style="font-size: 0.85rem; color: #7f8c8d; margin-top: 0.5rem;">
                        Note: Les architectes assignés pourront voir ce chantier et y uploader des photos.
                    </p>
                </div>

                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn-primary" style="width: 100%;">Enregistrer les modifications</button>
                    <a href="chantier.php?id=<?= $chantier_id ?>" class="btn-primary" 
                       style="background: #95a5a6; text-align: center; width: 100%; display: block; margin-top: 1rem; text-decoration: none;">Annuler</a>
                </div>
            </form>

            <script>
                function toggleLotId(type) {
                    const lotGroup = document.getElementById('lot_group');
                    const templateGroup = document.getElementById('template_group');
                    if (type === 'visite_commerciale' || type === 'etat_des_lieux') {
                        lotGroup.style.display = 'block';
                        templateGroup.style.display = 'block';
                    } else {
                        lotGroup.style.display = 'none';
                        templateGroup.style.display = 'none';
                    }
                }

                function loadLots(templateFile) {
                    const lotSelect = document.getElementById('lot_id');
                    const currentLot = "<?= $chantier['lot_id'] ?>";
                    lotSelect.innerHTML = '<option value="">Chargement...</option>';
                    
                    if (!templateFile) {
                        lotSelect.innerHTML = '<option value="">-- Sélectionner d'abord un catalogue --</option>';
                        return;
                    }

                    fetch(`../includes/get_lots.php?file=${templateFile}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                alert(data.error);
                                lotSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                            } else {
                                lotSelect.innerHTML = '<option value="">-- Sélectionner un lot --</option>';
                                data.lots.forEach(lot => {
                                    const option = document.createElement('option');
                                    option.value = lot;
                                    option.textContent = `Lot ${lot}`;
                                    if (lot === currentLot) option.selected = true;
                                    lotSelect.appendChild(option);
                                });
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            lotSelect.innerHTML = '<option value="">Erreur réseau</option>';
                        });
                }
            </script>
        </div>
    </div>
</body>
</html>
