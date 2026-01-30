<?php
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

$file = $_GET['file'] ?? '';

if (empty($file)) {
    echo json_encode(['error' => 'Fichier non spécifié']);
    exit;
}

// Sécurité : n'autoriser que les fichiers .json dans le dossier templates
$file = basename($file);
$path = __DIR__ . '/../templates/' . $file;

if (!file_exists($path) || pathinfo($path, PATHINFO_EXTENSION) !== 'json') {
    echo json_encode(['error' => 'Fichier invalide ou inexistant']);
    exit;
}

$content = file_get_contents($path);
$data = json_decode($content, true);

if (!$data || !isset($data['parcelData']['parcelList'])) {
    echo json_encode(['error' => 'Structure JSON invalide']);
    exit;
}

$lots = array_keys($data['parcelData']['parcelList']);
sort($lots);

echo json_encode(['lots' => $lots]);
