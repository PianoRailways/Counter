<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db_connect.php';
header('Content-Type: application/json');

// Zeitzone explizit auf Schweiz setzen, damit date() stimmt
date_default_timezone_set('Europe/Zurich');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Keine Daten empfangen']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO fahrten (zugnummer, produkt_id, von_station, bis_station, fahrzeug_typ, datum) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $input['trainNumber'],
        $input['productID'],
        $input['fromStation'],
        $input['toStation'],
        $input['vehicleType'], // Neu
        date('Y-m-d H:i:s')
    ]);
    
    $fahrtId = $pdo->lastInsertId();

    $stmtWagen = $pdo->prepare("INSERT INTO wagen_daten (fahrt_id, wagen_index, pax_a, pax_b, pax_wr, hunde, velos, bemerkung, uebergang_vorne_geschlossen, uebergang_hinten_geschlossen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($input['cars'] as $idx => $car) {
        if (!$car) continue;
        $stmtWagen->execute([
            $fahrtId,
            $idx + 1,
            $car['firstClass'] ?? 0,
            $car['secondClass'] ?? 0,
            $car['restaurantClass'] ?? 0,
            $car['dogs'] ?? 0,
            $car['bikes'] ?? 0,
            $car['comments'] ?? '',
            !empty($car['frontGangwayClosed']) ? 1 : 0,
            !empty($car['rearGangwayClosed']) ? 1 : 0
        ]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'db_id' => $fahrtId]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}