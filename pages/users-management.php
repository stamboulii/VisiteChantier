<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Vérifier que l'utilisateur est admin
requireAdmin();

$message = '';
$message_type = '';

// Traitement de la création d'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_user') {
        $new_username = trim($_POST['username'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $new_nom = trim($_POST['nom'] ?? '');
        $new_prenom = trim($_POST['prenom'] ?? '');
        $new_role = $_POST['role'] ?? 'architect';
        $new_password = $_POST['password'] ?? '';
        
        // Validation
        if (empty($new_username) || empty($new_email) || empty($new_nom) || empty($new_prenom)) {
            $message = 'Tous les champs sont requis';
            $message_type = 'error';
        } else {
            // Vérifier que le username n'existe pas
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$new_username]);
            if ($stmt->fetch()) {
                $message = 'Ce nom d\'utilisateur existe déjà';
                $message_type = 'error';
            } else {
                // Vérifier que l'email n'existe pas
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$new_email]);
                if ($stmt->fetch()) {
                    $message = 'Cet email existe déjà';
                    $message_type = 'error';
                } else {
                    // Si pas de mot de passe fourni, en générer un
                    if (empty($new_password)) {
                        $new_password = bin2hex(random_bytes(6)); // 12 caractères
                    }
                    
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, nom, prenom, role) VALUES (?, ?, ?, ?, ?, ?)");
                    
                    if ($stmt->execute([$new_username, $new_email, $password_hash, $new_nom, $new_prenom, $new_role])) {
                        $message = "Utilisateur créé avec succès! Mot de passe: <strong>" . htmlspecialchars($new_password) . "</strong> (copiez-le maintenant!)";
                        $message_type = 'success';
                        
                        // Log l'action
                        logAdminAction('create_user', [
                            'username' => $new_username,
                            'role' => $new_role
                        ]);
                    } else {
                        $message = 'Erreur lors de la création de l\'utilisateur';
                        $message_type = 'error';
                    }
                }
            }
        }
    }
    
    // Suppression d'utilisateur
    if ($_POST['action'] === 'delete_user') {
        $user_id_to_delete = intval($_POST['user_id'] ?? 0);
        
        if ($user_id_to_delete === getUserId()) {
            $message = 'Vous ne pouvez pas supprimer votre propre compte';
            $message_type = 'error';
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id_to_delete])) {
                $message = 'Utilisateur supprimé avec succès';
                $message_type = 'success';
                
                logAdminAction('delete_user', ['user_id' => $user_id_to_delete]);
            } else {
                $message = 'Erreur lors de la suppression';
                $message_type = 'error';
            }
        }
    }
}

// Récupérer tous les utilisateurs
$stmt = $pdo->query("
    SELECT u.*, 
           COUNT(DISTINCT c.id) as nb_chantiers,
           COUNT(DISTINCT i.id) as nb_photos
    FROM users u
    LEFT JOIN chantiers c ON u.id = c.user_id
    LEFT JOIN chantier_assignments ca ON u.id = ca.user_id
    LEFT JOIN images i ON (c.id = i.chantier_id OR ca.chantier_id = i.chantier_id)
    GROUP BY u.id
    ORDER BY u.role DESC, u.created_at DESC
");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Suivi de Chantiers</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">🏗️ Suivi Chantiers</div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="users-management.php" class="active">Utilisateurs</a></li>
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
        <div class="dashboard-header">
            <div>
                <h1>👥 Gestion des Utilisateurs</h1>
                <p>Créer et gérer les comptes des architectes</p>
            </div>
            <button onclick="openCreateUserModal()" class="btn-primary">➕ Nouvel Utilisateur</button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Nom Complet</th>
                        <th>Rôle</th>
                        <th>Chantiers</th>
                        <th>Photos</th>
                        <th>Créé le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></td>
                            <td><?= getRoleBadge($user['role']) ?></td>
                            <td><?= $user['nb_chantiers'] ?></td>
                            <td><?= $user['nb_photos'] ?></td>
                            <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <a href="edit-user.php?id=<?= $user['id'] ?>" class="btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; margin-right: 0.5rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;">
                                    ✏️ Modifier
                                </a>
                                <?php if ($user['id'] !== getUserId()): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn-delete">🗑️</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #95a5a6;">Vous</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Création Utilisateur -->
    <div id="createUserModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal" onclick="closeCreateUserModal()">&times;</span>
            <h2>➕ Créer un Nouvel Utilisateur</h2>
            
            <form method="POST" class="user-form">
                <input type="hidden" name="action" value="create_user">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur *</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="role">Rôle *</label>
                        <select id="role" name="role" required>
                            <option value="architect">Architecte</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe (optionnel)</label>
                        <input type="text" id="password" name="password" placeholder="Sera généré automatiquement">
                        <small style="color: #7f8c8d;">Laissez vide pour générer un mot de passe aléatoire</small>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="closeCreateUserModal()" class="btn-secondary">Annuler</button>
                    <button type="submit" class="btn-primary">Créer l'utilisateur</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateUserModal() {
            document.getElementById('createUserModal').style.display = 'flex';
        }
        
        function closeCreateUserModal() {
            document.getElementById('createUserModal').style.display = 'none';
        }
        
        // Fermer le modal si on clique en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('createUserModal');
            if (event.target === modal) {
                closeCreateUserModal();
            }
        }
    </script>
</body>
</html>
