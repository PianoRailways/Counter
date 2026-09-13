<?php
require 'db_connect.php';
header('Content-Type: application/json');

$search = $_GET['search'] ?? '';

try {
    $sql = "SELECT f.*, w.* FROM fahrten f 
            LEFT JOIN wagen_daten w ON f.id = w.fahrt_id";
    
    if ($search !== '') {
        $sql .= " WHERE f.zugnummer LIKE :s 
                  OR f.von_station LIKE :s 
                  OR f.bis_station LIKE :s 
                  OR f.fahrzeug_typ LIKE :s";
    }
    
    $sql .= " ORDER BY f.datum DESC, w.wagen_index ASC";
    
    $stmt = $pdo->prepare($sql);
    if ($search !== '') {
        $stmt->execute(['s' => "%$search%"]);
    } else {
        $stmt->execute();
    }
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}