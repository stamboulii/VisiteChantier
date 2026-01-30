<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Vérifier que l'utilisateur est admin
requireAdmin("Seuls les administrateurs peuvent créer des chantiers");

// Récupérer la liste des architectes pour l'assignation
$stmt_architects = $pdo->prepare("SELECT id, username, nom, prenom FROM users WHERE role = 'architect' ORDER BY nom, prenom");
$stmt_architects->execute();
$architects = $stmt_architects->fetchAll();

$message = '';

// Charger les lots depuis template.json pour les visites immobilières
$template_path = '../template.json';
$lots = [];
if (file_exists($template_path)) {
    $template_data = json_decode(file_get_contents($template_path), true);
    if (isset($template_data['parcelData']['parcelList'])) {
        $lots = array_keys($template_data['parcelData']['parcelList']);
        sort($lots);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date_debut = $_POST['date_debut'] ?? '';
    $date_fin_prevue = $_POST['date_fin_prevue'] ?? null;
    $statut = $_POST['statut'] ?? 'en_cours';
    $type = $_POST['type'] ?? 'chantier';
    $lot_id = ($type === 'visite_commerciale' || $type === 'etat_des_lieux') ? ($_POST['lot_id'] ?? null) : null;
    $assigned_architects = $_POST['architects'] ?? [];
    
    if (empty($nom) || empty($adresse) || empty($date_debut)) {
        $message = '<div class="alert alert-error">Veuillez remplir tous les champs obligatoires</div>';
    } else {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO chantiers (user_id, nom, adresse, description, date_debut, date_fin_prevue, statut, type, lot_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$user_id, $nom, $adresse, $description, $date_debut, $date_fin_prevue ?: null, $statut, $type, $lot_id])) {
                $chantier_id = $pdo->lastInsertId();
                
                // Assigner les utilisateurs au projet
                if (!empty($assigned_architects)) {
                    $stmt_assign = $pdo->prepare("
                        INSERT INTO chantier_assignments (chantier_id, user_id, assigned_by) 
                        VALUES (?, ?, ?)
                    ");
                    
                    foreach ($assigned_architects as $architect_id) {
                        $stmt_assign->execute([$chantier_id, $architect_id, $user_id]);
                    }
                }
                
                $pdo->commit();
                
                logAdminAction('create_project', [
                    'project_id' => $chantier_id,
                    'nom' => $nom,
                    'type' => $type,
                    'lot_id' => $lot_id
                ]);
                
                header("Location: chantier.php?id=$chantier_id");
                exit;
            } else {
                $pdo->rollBack();
                $message = '<div class="alert alert-error">Erreur lors de la création du projet</div>';
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
    <title>Nouveau Chantier - Suivi de Chantiers</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🏗️ Suivi Chantiers</div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="nouveau-chantier.php">Nouveau Chantier</a></li>
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
            <a href="dashboard.php" style="color: #3498db; text-decoration: none; margin-bottom: 1rem; display: inline-block;">← Retour au dashboard</a>
            
            <h1 style="margin-bottom: 2rem; color: #2c3e50;">Créer un nouveau projet</h1>
            
            <?= $message ?>
            
            <form method="POST" action="" style="max-width: 800px;">
                <div class="form-group">
                    <label for="type">Type de projet *</label>
                    <select id="type" name="type" required onchange="toggleLotId(this.value)"
                            style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px;">
                        <option value="chantier">🏗️ Chantier / Construction</option>
                        <option value="visite_commerciale">🏠 Visite Commerciale</option>
                        <option value="etat_des_lieux">📋 État des Lieux</option>
                        <option value="autre">📁 Autre</option>
                    </select>
                </div>

                <div class="form-group" id="lot_group" style="display: none;">
                    <label for="lot_id">Lier à un lot (Template JSON)</label>
                    <select id="lot_id" name="lot_id" 
                            style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px;">
                        <option value="">-- Sélectionner un lot --</option>
                        <?php foreach ($lots as $lot): ?>
                            <option value="<?= htmlspecialchars($lot) ?>">Lot <?= htmlspecialchars($lot) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="nom">Nom du projet *</label>
                    <input type="text" id="nom" name="nom" required 
                           placeholder="Ex: Villa Moderne ou Visite Lot 004">
                </div>
                
                <div class="form-group">
                    <label for="adresse">Adresse *</label>
                    <input type="text" id="adresse" name="adresse" required 
                           placeholder="Lieu de la visite ou du chantier">
                </div>
                
                <div class="form-group">
                    <label for="description">Description (optionnelle)</label>
                    <textarea id="description" name="description" rows="4" 
                               style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; font-family: inherit;"
                               placeholder="Détails supplémentaires..."></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="date_debut">Date de début / Visite *</label>
                        <input type="date" id="date_debut" name="date_debut" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_fin_prevue">Date de fin prévue</label>
                        <input type="date" id="date_fin_prevue" name="date_fin_prevue">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="statut">Statut</label>
                    <select id="statut" name="statut" 
                            style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px;">
                        <option value="en_cours">En cours / Ouvert</option>
                        <option value="en_pause">En pause</option>
                        <option value="termine">Terminé / Clos</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="architects">Assigner des utilisateurs (architectes, agents...)</label>
                    <select id="architects" name="architects[]" multiple 
                            style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; min-height: 120px;">
                        <?php foreach ($architects as $architect): ?>
                            <option value="<?= $architect['id'] ?>">
                                <?= htmlspecialchars($architect['prenom'] . ' ' . $architect['nom']) ?> 
                                (<?= htmlspecialchars($architect['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #7f8c8d;">Maintenez Ctrl (ou Cmd sur Mac) pour sélection multiple</small>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn-primary">Créer le projet</button>
                    <a href="dashboard.php" class="btn-primary" 
                       style="background: #95a5a6; text-align: center;">Annuler</a>
                </div>
            </form>

            <script>
                function toggleLotId(type) {
                    const lotGroup = document.getElementById('lot_group');
                    if (type === 'visite_commerciale' || type === 'etat_des_lieux') {
                        lotGroup.style.display = 'block';
                    } else {
                        lotGroup.style.display = 'none';
                    }
                }
            </script>
        </div>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>
