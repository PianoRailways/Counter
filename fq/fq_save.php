<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ob_start();
header('Content-Type: application/json; charset=utf-8');

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Nur POST ist erlaubt.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput ?: '', true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Ungültige JSON-Daten.']);
    exit;
}

$databasePath = __DIR__ . '/fqdata.sqlite';
$database = null;

try {
    $database = new PDO('sqlite:' . $databasePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $database->exec('PRAGMA busy_timeout = 5000');
    $database->exec('PRAGMA journal_mode = WAL');
    $database->exec(
        'CREATE TABLE IF NOT EXISTS fq_versions (' .
        'id INTEGER PRIMARY KEY AUTOINCREMENT,' .
        'version_no INTEGER NOT NULL UNIQUE,' .
        'created_at TEXT NOT NULL,' .
        'payload_json TEXT NOT NULL' .
        ')'
    );

    $database->beginTransaction();
    $nextVersion = (int) $database->query('SELECT COALESCE(MAX(version_no), 0) + 1 FROM fq_versions')->fetchColumn();
    $createdAt = gmdate('c');
    $payload['version'] = $nextVersion;
    $payload['createdAt'] = $createdAt;

    $statement = $database->prepare(
        'INSERT INTO fq_versions (version_no, created_at, payload_json) VALUES (:version_no, :created_at, :payload_json)'
    );
    $statement->execute([
        ':version_no' => $nextVersion,
        ':created_at' => $createdAt,
        ':payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
    ]);
    $database->commit();

    echo json_encode([
        'ok' => true,
        'version' => $nextVersion,
        'createdAt' => $createdAt,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    if ($database instanceof PDO && $database->inTransaction()) {
        $database->rollBack();
    }
    error_log('FQ-Export fehlgeschlagen: ' . $error->getMessage());
    if (ob_get_length() !== false) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Der FQ-Export konnte nicht gespeichert werden.',
        'details' => $error->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
