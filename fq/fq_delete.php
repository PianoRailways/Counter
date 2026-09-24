<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ob_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Nur POST ist erlaubt.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($payload) || !ctype_digit((string) ($payload['version'] ?? ''))) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Ungültige Fahrt.']);
    exit;
}

if (($payload['password'] ?? null) !== 'kaunter') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Löschen ist nicht erlaubt.']);
    exit;
}

$database = null;
try {
    $database = new PDO('sqlite:' . __DIR__ . '/fqdata.sqlite', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $database->exec('PRAGMA busy_timeout = 5000');
    $database->beginTransaction();

    $statement = $database->prepare('DELETE FROM fq_versions WHERE version_no = :version_no');
    $statement->execute([':version_no' => (int) $payload['version']]);
    if ($statement->rowCount() !== 1) {
        throw new RuntimeException('Die Fahrt wurde nicht gefunden.');
    }

    $database->commit();
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    if ($database instanceof PDO && $database->inTransaction()) {
        $database->rollBack();
    }
    if (ob_get_length() !== false) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Die Fahrt konnte nicht gelöscht werden.',
    ], JSON_UNESCAPED_UNICODE);
}
