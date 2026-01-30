<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Vérifier que l'utilisateur est admin
requireAdmin("Seuls les administrateurs peuvent modifier les utilisateurs");

$user_id_to_edit = intval($_GET['id'] ?? 0);

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id_to_edit]);
$user_to_edit = $stmt->fetch();

if (!$user_to_edit) {
    header('Location: users-management.php');
    exit;
}

$message = '';
$message_type = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Mise à jour des informations
    if ($action === 'update_info') {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'architect';
        
        if (empty($nom) || empty($prenom) || empty($username) || empty($email)) {
            $message = "Tous les champs sont obligatoires";
            $message_type = 'error';
        } else {
            // Vérifier que le username n'est pas déjà pris par un autre utilisateur
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user_id_to_edit]);
            
            if ($stmt->fetch()) {
                $message = "Ce nom d'utilisateur est déjà pris";
                $message_type = 'error';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET nom = ?, prenom = ?, username = ?, email = ?, role = ?
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$nom, $prenom, $username, $email, $role, $user_id_to_edit])) {
                    $message = "Informations mises à jour avec succès!";
                    $message_type = 'success';
                    
                    // Recharger les données
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id_to_edit]);
                    $user_to_edit = $stmt->fetch();
                    
                    logAdminAction('update_user', [
                        'user_id' => $user_id_to_edit,
                        'username' => $username
                    ]);
                } else {
                    $message = "Erreur lors de la mise à jour";
                    $message_type = 'error';
                }
            }
        }
    }
    
    // Changement de mot de passe
    if ($action === 'change_password') {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($new_password)) {
            $message = "Le mot de passe ne peut pas être vide";
            $message_type = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = "Les mots de passe ne correspondent pas";
            $message_type = 'error';
        } elseif (strlen($new_password) < 6) {
            $message = "Le mot de passe doit contenir au moins 6 caractères";
            $message_type = 'error';
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($stmt->execute([$hashed, $user_id_to_edit])) {
                $message = "Mot de passe modifié avec succès!";
                $message_type = 'success';
                
                logAdminAction('change_user_password', [
                    'user_id' => $user_id_to_edit,
                    'username' => $user_to_edit['username']
                ]);
            } else {
                $message = "Erreur lors du changement de mot de passe";
                $message_type = 'error';
            }
        }
    }
    
    // Assigner un chantier
    if ($action === 'assign_chantier') {
        $chantier_id = intval($_POST['chantier_id'] ?? 0);
        if ($chantier_id > 0) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO chantier_assignments (chantier_id, user_id, assigned_by) VALUES (?, ?, ?)");
            if ($stmt->execute([$chantier_id, $user_id_to_edit, getUserId()])) {
                $message = "Chantier assigné avec succès!";
                $message_type = 'success';
                logAdminAction('assign_chantier_from_user', ['user_id' => $user_id_to_edit, 'chantier_id' => $chantier_id]);
            }
        }
    }
    
    // Retirer un chantier
    if ($action === 'unassign_chantier') {
        $chantier_id = intval($_POST['chantier_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM chantier_assignments WHERE chantier_id = ? AND user_id = ?");
        if ($stmt->execute([$chantier_id, $user_id_to_edit])) {
            $message = "Assignation retirée avec succès!";
            $message_type = 'success';
            logAdminAction('unassign_chantier_from_user', ['user_id' => $user_id_to_edit, 'chantier_id' => $chantier_id]);
        }
    }
}

// Récupérer les chantiers de l'utilisateur (si architecte)
$assigned_chantiers = [];
if ($user_to_edit['role'] === 'architect') {
    $stmt = $pdo->prepare("
        SELECT c.id, c.nom, c.adresse, c.statut, ca.assigned_at,
               (SELECT COUNT(*) FROM images WHERE chantier_id = c.id AND user_id = ?) as nb_photos
        FROM chantier_assignments ca
        INNER JOIN chantiers c ON ca.chantier_id = c.id
        WHERE ca.user_id = ?
        ORDER BY ca.assigned_at DESC
    ");
    $stmt->execute([$user_id_to_edit, $user_id_to_edit]);
    $assigned_chantiers = $stmt->fetchAll();
}

// Récupérer tous les chantiers pour assignation
$all_chantiers = [];
if ($user_to_edit['role'] === 'architect') {
    $assigned_ids = array_column($assigned_chantiers, 'id');
    
    if (!empty($assigned_ids)) {
        $placeholders = str_repeat('?,', count($assigned_ids) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT id, nom, adresse, statut 
            FROM chantiers 
            WHERE id NOT IN ($placeholders)
            ORDER BY nom
        ");
        $stmt->execute($assigned_ids);
    } else {
        $stmt = $pdo->query("SELECT id, nom, adresse, statut FROM chantiers ORDER BY nom");
    }
    $all_chantiers = $stmt->fetchAll();
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
    <title>Modifier <?= htmlspecialchars($user_to_edit['prenom'] . ' ' . $user_to_edit['nom']) ?></title>
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
            <a href="users-management.php" class="back-link">← Retour aux utilisateurs</a>
            
            <div class="dashboard-header">
                <div>
                    <h1>✏️ Modifier l'utilisateur</h1>
                    <p style="display: flex; align-items: center; gap: 0.5rem;">
                        <?= getRoleBadge($user_to_edit['role']) ?>
                        <strong><?= htmlspecialchars($user_to_edit['prenom'] . ' ' . $user_to_edit['nom']) ?></strong>
                    </p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
                
                <!-- Informations personnelles -->
                <div class="edit-section">
                    <h2>📝 Informations Personnelles</h2>
                    <form method="POST" class="user-form">
                        <input type="hidden" name="action" value="update_info">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="prenom">Prénom *</label>
                                <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($user_to_edit['prenom']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="nom">Nom *</label>
                                <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($user_to_edit['nom']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Nom d'utilisateur *</label>
                            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user_to_edit['username']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_to_edit['email']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Rôle *</label>
                            <select id="role" name="role" required>
                                <option value="architect" <?= $user_to_edit['role'] === 'architect' ? 'selected' : '' ?>>Architecte</option>
                                <option value="admin" <?= $user_to_edit['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-primary" style="width: 100%;">
                            💾 Enregistrer les modifications
                        </button>
                    </form>
                </div>
                
                <!-- Changement de mot de passe -->
                <div class="edit-section">
                    <h2>🔒 Changer le Mot de Passe</h2>
                    <form method="POST" class="user-form">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="new_password">Nouveau mot de passe *</label>
                            <input type="password" id="new_password" name="new_password" minlength="6" required>
                            <small style="color: #7f8c8d;">Minimum 6 caractères</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmer le mot de passe *</label>
                            <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                        </div>
                        
                        <button type="submit" class="btn-primary" style="width: 100%; background: var(--gradient-2);">
                            🔑 Modifier le mot de passe
                        </button>
                    </form>
                    
                    <div style="margin-top: 2rem; padding: 1.5rem; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 8px;">
                        <p style="color: #856404; font-size: 0.9rem; margin: 0;">
                            ⚠️ <strong>Attention:</strong> L'utilisateur devra utiliser le nouveau mot de passe lors de sa prochaine connexion.
                        </p>
                    </div>
                </div>
            </div>
            
            <?php if ($user_to_edit['role'] === 'architect'): ?>
            <!-- Chantiers assignés -->
            <div class="edit-section" style="margin-top: 2rem;">
                <h2>🏗️ Chantiers Assignés (<?= count($assigned_chantiers) ?>)</h2>
                
                <?php if (empty($assigned_chantiers)): ?>
                    <div class="empty-state" style="padding: 2rem;">
                        <p>Aucun chantier assigné pour le moment</p>
                        <p style="font-size: 0.9rem; color: #7f8c8d;">Assignez des chantiers depuis la page de chaque chantier</p>
                    </div>
                <?php else: ?>
                    <div class="users-table-container" style="margin-top: 1.5rem;">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Nom du Chantier</th>
                                    <th>Adresse</th>
                                    <th>Statut</th>
                                    <th>Photos</th>
                                    <th>Assigné le</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assigned_chantiers as $chantier): ?>
                                    <tr>
                                        <td>
                                            <a href="chantier.php?id=<?= $chantier['id'] ?>" style="color: #3498db; text-decoration: none; font-weight: 600;">
                                                <?= htmlspecialchars($chantier['nom']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($chantier['adresse']) ?></td>
                                        <td>
                                            <span class="chantier-status status-<?= $chantier['statut'] ?>">
                                                <?= $statuts[$chantier['statut']] ?>
                                            </span>
                                        </td>
                                        <td><?= $chantier['nb_photos'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($chantier['assigned_at'])) ?></td>
                                        <td style="display: flex; gap: 0.5rem;">
                                            <a href="manage-assignments.php?id=<?= $chantier['id'] ?>" class="btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; text-decoration: none;">
                                                Gérer
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Retirer cet architecte de ce chantier ?')">
                                                <input type="hidden" name="action" value="unassign_chantier">
                                                <input type="hidden" name="chantier_id" value="<?= $chantier['id'] ?>">
                                                <button type="submit" class="btn-delete" style="padding: 0.4rem 0.6rem;">✕</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($all_chantiers)): ?>
                <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid #e9ecef;">
                    <h3>➕ Assigner à un nouveau chantier</h3>
                    <form method="POST" style="display: flex; gap: 1rem; margin-top: 1rem; align-items: flex-end;">
                        <input type="hidden" name="action" value="assign_chantier">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label for="chantier_id">Sélectionner un chantier</label>
                            <select id="chantier_id" name="chantier_id" required>
                                <option value="">-- Choisir un chantier --</option>
                                <?php foreach ($all_chantiers as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?> (<?= htmlspecialchars($c['adresse']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary">Assigner l'utilisateur</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .edit-section {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .edit-section h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        @media (max-width: 768px) {
            .container > div > div:nth-child(4) {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</body>
</html>
