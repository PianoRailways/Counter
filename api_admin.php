<?php
require 'db_connect.php';
header('Content-Type: application/json');

// Das Passwort aus der db_connect.php nutzen
$pw = $_GET['pw'] ?? '';

// Falls $adminPassword in db_connect.php definiert wurde:
if (!isset($adminPassword) || $pw !== (string)$adminPassword) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Zugriff verweigert']));
}

$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);

if ($action === 'delete' && $id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM fahrten WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}