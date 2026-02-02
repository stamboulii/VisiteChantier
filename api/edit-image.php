<?php
/**
 * API: Édition des métadonnées d'une image
 *
 * Permet de modifier les métadonnées d'une image (commentaire, phase, date_prise)
 * Permissions:
 * - Admin: peut éditer toutes les images
 * - Architect: peut éditer les images des chantiers auxquels il a accès
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
if (!canEditImage($image_id)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Vous n\'avez pas la permission de modifier cette image'
    ]);
    exit;
}

try {
    // Récupérer les informations actuelles de l'image
    $stmt = $pdo->prepare("SELECT * FROM images WHERE id = ?");
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

    // Récupérer les nouvelles valeurs (garder les anciennes si non fournies)
    $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : $image['commentaire'];
    $phase = isset($_POST['phase']) ? trim($_POST['phase']) : $image['phase'];
    $date_prise = isset($_POST['date_prise']) ? trim($_POST['date_prise']) : $image['date_prise'];

    // Validation de la date si fournie
    if (!empty($date_prise)) {
        $dateObj = DateTime::createFromFormat('Y-m-d', $date_prise);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $date_prise) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Format de date invalide (attendu: YYYY-MM-DD)'
            ]);
            exit;
        }
    } else {
        $date_prise = null;
    }

    // Validation de la phase
    $phases_valides = [
        'fondations', 'structure', 'clos_couvert', 'second_oeuvre', 'finitions',
        'exterieur', 'entree', 'sejour', 'cuisine', 'chambre', 'sdb', 'balcon',
        'general', 'detail', 'defaut', 'autres'
    ];

    if (!in_array($phase, $phases_valides)) {
        $phase = 'autres';
    }

    // Mettre à jour l'image
    $stmt = $pdo->prepare("
        UPDATE images
        SET commentaire = ?,
            phase = ?,
            date_prise = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $commentaire,
        $phase,
        $date_prise,
        $image_id
    ]);

    // Logger l'action
    logAdminAction('image_edited', [
        'image_id' => $image_id,
        'chantier_id' => $image['chantier_id'],
        'changes' => [
            'commentaire' => $commentaire,
            'phase' => $phase,
            'date_prise' => $date_prise
        ]
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Image mise à jour avec succès',
        'data' => [
            'commentaire' => $commentaire,
            'phase' => $phase,
            'date_prise' => $date_prise
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
    ]);
}
?>
