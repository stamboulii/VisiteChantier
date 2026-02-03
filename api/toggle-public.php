<?php
/**
 * API: Toggle du statut public/privé d'un chantier
 *
 * Permet de rendre un chantier public ou privé
 * Génère automatiquement un token unique pour le partage
 *
 * Permissions:
 * - Admin uniquement
 */

header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/permissions.php';

// Vérifier que l'utilisateur est connecté et est admin
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour effectuer cette action'
    ]);
    exit;
}

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Seuls les administrateurs peuvent modifier la visibilité des chantiers'
    ]);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit;
}

// Récupérer l'ID du chantier
$chantier_id = isset($_POST['chantier_id']) ? intval($_POST['chantier_id']) : 0;

if ($chantier_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID de chantier invalide'
    ]);
    exit;
}

try {
    // Récupérer les informations actuelles du chantier
    $stmt = $pdo->prepare("SELECT id, nom, is_public, share_token FROM chantiers WHERE id = ?");
    $stmt->execute([$chantier_id]);
    $chantier = $stmt->fetch();

    if (!$chantier) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Chantier non trouvé'
        ]);
        exit;
    }

    // Inverser l'état public
    $new_state = $chantier['is_public'] ? 0 : 1;

    // Générer un token si le chantier devient public et n'a pas encore de token
    $share_token = $chantier['share_token'];
    if ($new_state == 1 && empty($share_token)) {
        // Générer un token unique et sécurisé
        $share_token = bin2hex(random_bytes(32)); // 64 caractères
    }

    // Mettre à jour le chantier
    $stmt = $pdo->prepare("
        UPDATE chantiers
        SET is_public = ?,
            share_token = ?
        WHERE id = ?
    ");

    $stmt->execute([$new_state, $share_token, $chantier_id]);

    // Générer l'URL de partage
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $share_url = null;

    if ($new_state == 1) {
        $share_url = $protocol . '://' . $host . dirname(dirname($_SERVER['PHP_SELF'])) . '/share.php?token=' . $share_token;
    }

    // Logger l'action
    logAdminAction('chantier_visibility_changed', [
        'chantier_id' => $chantier_id,
        'chantier_nom' => $chantier['nom'],
        'new_state' => $new_state ? 'public' : 'private',
        'share_token' => $share_token
    ]);

    echo json_encode([
        'success' => true,
        'message' => $new_state ? 'Chantier rendu public' : 'Chantier rendu privé',
        'is_public' => (bool)$new_state,
        'share_url' => $share_url,
        'share_token' => $share_token
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la modification: ' . $e->getMessage()
    ]);
}
?>
