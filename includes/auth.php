<?php
// Charger le système de permissions
require_once __DIR__ . '/permissions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$nom_complet = $_SESSION['nom_complet'] ?? 'Utilisateur';
$user_role = getUserRole() ?? 'architect';
?>
