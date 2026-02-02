<?php
/**
 * API: Suppression d'image
 *
 * Permet de supprimer une image d'un chantier
 * Permissions:
 * - Admin: peut supprimer toutes les images
 * - Architect: peut supprimer les images des chantiers auxquels il a accès
 */

header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/permissions.php';

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour effectuer cette action'
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

// Récupérer l'ID de l'image
$image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;

if ($image_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID d\'image invalide'
    ]);
    exit;
}

// Vérifier les permissions
if (!canDeleteImage($image_id)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Vous n\'avez pas la permission de supprimer cette image'
    ]);
    exit;
}

try {
    // Récupérer les informations de l'image
    $stmt = $pdo->prepare("SELECT filename, chantier_id FROM images WHERE id = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch();

    if (!$image) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Image non trouvée'
        ]);
        exit;
    }

    // Supprimer le fichier physique
    $filepath = UPLOAD_DIR . $image['filename'];
    if (file_exists($filepath)) {
        if (!unlink($filepath)) {
            throw new Exception('Impossible de supprimer le fichier physique');
        }
    }

    // Supprimer l'entrée de la base de données
    $stmt = $pdo->prepare("DELETE FROM images WHERE id = ?");
    $stmt->execute([$image_id]);

    // Logger l'action
    logAdminAction('image_deleted', [
        'image_id' => $image_id,
        'filename' => $image['filename'],
        'chantier_id' => $image['chantier_id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Image supprimée avec succès'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
    ]);
}
?>
