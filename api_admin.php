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
    exit;
}

if ($action === 'update' && $id > 0) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Ungültige Daten']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE fahrten SET zugnummer = ?, produkt_id = ?, von_station = ?, bis_station = ?, fahrzeug_typ = ? WHERE id = ?");
        $stmt->execute([
            $input['trainNumber'] ?? '',
            $input['productID'] ?? '',
            $input['fromStation'] ?? '',
            $input['toStation'] ?? '',
            $input['vehicleType'] ?? '',
            $id
        ]);

        $stmt = $pdo->prepare("DELETE FROM wagen_daten WHERE fahrt_id = ?");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("INSERT INTO wagen_daten (fahrt_id, wagen_index, pax_a, pax_b, pax_wr, hunde, velos, bemerkung, uebergang_vorne_geschlossen, uebergang_hinten_geschlossen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach (($input['cars'] ?? []) as $index => $car) {
            $stmt->execute([
                $id,
                $index + 1,
                max(0, (int)($car['firstClass'] ?? 0)),
                max(0, (int)($car['secondClass'] ?? 0)),
                max(0, (int)($car['restaurantClass'] ?? 0)),
                max(0, (int)($car['dogs'] ?? 0)),
                max(0, (int)($car['bikes'] ?? 0)),
                $car['comments'] ?? '',
                !empty($car['frontGangwayClosed']) ? 1 : 0,
                !empty($car['rearGangwayClosed']) ? 1 : 0
            ]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Unbekannte Aktion']);