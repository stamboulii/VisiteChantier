<?php
/**
 * Système de gestion des permissions
 * 
 * Ce fichier gère les rôles et permissions pour l'application
 * Rôles disponibles: admin, architect
 */

// Vérifier qu'une session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Obtenir le rôle de l'utilisateur connecté
 */
function getUserRole(): ?string {
    return $_SESSION['role'] ?? null;
}

/**
 * Obtenir l'ID de l'utilisateur connecté
 */
function getUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Vérifie si l'utilisateur est admin
 */
function isAdmin(): bool {
    return isLoggedIn() && getUserRole() === 'admin';
}

/**
 * Vérifie si l'utilisateur est architecte
 */
function isArchitect(): bool {
    return isLoggedIn() && getUserRole() === 'architect';
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 */
function hasRole(string $role): bool {
    return isLoggedIn() && getUserRole() === $role;
}

/**
 * Vérifie si l'utilisateur peut créer des chantiers
 */
function canCreateChantier(): bool {
    return isAdmin();
}

/**
 * Vérifie si l'utilisateur peut créer des utilisateurs
 */
function canCreateUser(): bool {
    return isAdmin();
}

/**
 * Vérifie si l'utilisateur peut éditer un chantier
 */
function canEditChantier(int $chantier_id): bool {
    return isAdmin(); // Seuls les admins peuvent éditer
}

/**
 * Vérifie si l'utilisateur peut supprimer un chantier
 */
function canDeleteChantier(int $chantier_id): bool {
    return isAdmin(); // Seuls les admins peuvent supprimer
}

/**
 * Vérifie si l'utilisateur peut accéder à un chantier
 * Admin: accès à tous
 * Architect: seulement ceux assignés
 */
function canAccessChantier(int $chantier_id): bool {
    global $pdo;
    
    if (isAdmin()) {
        return true; // Admin a accès à tout
    }
    
    if (isArchitect()) {
        // Vérifier si le chantier est assigné à cet architecte
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM chantier_assignments 
            WHERE chantier_id = ? AND user_id = ?
        ");
        $stmt->execute([$chantier_id, getUserId()]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    return false;
}

/**
 * Vérifie si l'utilisateur peut supprimer une image
 * Admin: peut supprimer toutes les images
 * Architect: peut supprimer toutes les images des chantiers auxquels il a accès
 */
function canDeleteImage(int $image_id): bool {
    global $pdo;

    if (isAdmin()) {
        return true; // Admin peut tout supprimer
    }

    if (isArchitect()) {
        // Vérifier si l'image appartient à un chantier accessible par cet architecte
        $stmt = $pdo->prepare("SELECT chantier_id FROM images WHERE id = ?");
        $stmt->execute([$image_id]);
        $image = $stmt->fetch();

        if (!$image) {
            return false;
        }

        return canAccessChantier($image['chantier_id']);
    }

    return false;
}

/**
 * Vérifie si l'utilisateur peut éditer une image
 * Admin: peut éditer toutes les images
 * Architect: peut éditer toutes les images des chantiers auxquels il a accès
 */
function canEditImage(int $image_id): bool {
    // Même logique que canDeleteImage
    return canDeleteImage($image_id);
}

/**
 * Vérifie si l'utilisateur peut uploader des images sur un chantier
 */
function canUploadImage(int $chantier_id): bool {
    // Les deux rôles peuvent uploader si ils ont accès au chantier
    return canAccessChantier($chantier_id);
}

/**
 * Obtenir les IDs des chantiers accessibles par l'utilisateur
 */
function getAccessibleChantierIds(): array {
    global $pdo;
    
    if (isAdmin()) {
        // Admin a accès à tous les chantiers
        $stmt = $pdo->query("SELECT id FROM chantiers");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    if (isArchitect()) {
        // Architecte: seulement les chantiers assignés
        $stmt = $pdo->prepare("
            SELECT chantier_id 
            FROM chantier_assignments 
            WHERE user_id = ?
        ");
        $stmt->execute([getUserId()]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    return [];
}

/**
 * Obtenir les statistiques de l'utilisateur
 */
function getUserStats(): array {
    global $pdo;
    
    $stats = [
        'total_chantiers' => 0,
        'chantiers_en_cours' => 0,
        'total_photos' => 0
    ];
    
    $chantierIds = getAccessibleChantierIds();
    
    if (empty($chantierIds)) {
        return $stats;
    }
    
    $placeholders = str_repeat('?,', count($chantierIds) - 1) . '?';
    
    // Total chantiers
    $stats['total_chantiers'] = count($chantierIds);
    
    // Chantiers en cours
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM chantiers 
        WHERE id IN ($placeholders) AND statut = 'en_cours'
    ");
    $stmt->execute($chantierIds);
    $stats['chantiers_en_cours'] = $stmt->fetch()['count'];
    
    // Total photos
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM images 
        WHERE chantier_id IN ($placeholders)
    ");
    $stmt->execute($chantierIds);
    $stats['total_photos'] = $stmt->fetch()['count'];
    
    return $stats;
}

/**
 * Require que l'utilisateur soit admin
 * Redirige vers 403 si ce n'est pas le cas
 */
function requireAdmin(string $message = "Vous devez être administrateur pour accéder à cette page"): void {
    if (!isAdmin()) {
        $_SESSION['error_message'] = $message;
        header('Location: ../pages/403.php');
        exit;
    }
}

/**
 * Require qu'une permission soit vraie
 * Redirige vers 403 si ce n'est pas le cas
 */
function requirePermission(bool $hasPermission, string $message = "Vous n'avez pas la permission d'accéder à cette ressource"): void {
    if (!$hasPermission) {
        $_SESSION['error_message'] = $message;
        header('Location: ../pages/403.php');
        exit;
    }
}

/**
 * Obtenir un badge HTML pour le rôle
 */
function getRoleBadge(string $role): string {
    $badges = [
        'admin' => '<span class="badge-role badge-admin">👑 Admin</span>',
        'architect' => '<span class="badge-role badge-architect">👨‍💼 Architecte</span>'
    ];
    
    return $badges[$role] ?? '<span class="badge-role">Inconnu</span>';
}

/**
 * Obtenir le nom en français d'un rôle
 */
function getRoleName(string $role): string {
    $names = [
        'admin' => 'Administrateur',
        'architect' => 'Architecte'
    ];
    
    return $names[$role] ?? 'Inconnu';
}

/**
 * Obtenir le label en français d'un type de projet
 */
function getProjectTypeLabel(string $type): string {
    $types = [
        'chantier' => 'Chantier',
        'visite_commerciale' => 'Visite Commerciale',
        'etat_des_lieux' => 'État des Lieux',
        'autre' => 'Autre'
    ];
    
    return $types[$type] ?? 'Projet';
}

/**
 * Obtenir l'icône associée à un type de projet
 */
function getProjectTypeIcon(string $type): string {
    $icons = [
        'chantier' => '🏗️',
        'visite_commerciale' => '🏠',
        'etat_des_lieux' => '📋',
        'autre' => '📁'
    ];
    
    return $icons[$type] ?? '📁';
}

/**
 * Logger une action admin pour audit
 */
function logAdminAction(string $action, array $data = []): void {
    global $pdo;
    
    // Pour le moment, on peut log dans un fichier
    // Plus tard, on pourra créer une table audit_logs
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => getUserId(),
        'username' => $_SESSION['username'] ?? 'unknown',
        'action' => $action,
        'data' => $data,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    $logFile = __DIR__ . '/../logs/admin-actions.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    @file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND);
}
?>
