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
    $editVersion = $payload['editVersion'] ?? null;
    if ($editVersion !== null && (!is_int($editVersion) && !ctype_digit((string) $editVersion))) {
        throw new InvalidArgumentException('Ungültige Versionsnummer.');
    }

    $version = $editVersion === null
        ? (int) $database->query('SELECT COALESCE(MAX(version_no), 0) + 1 FROM fq_versions')->fetchColumn()
        : (int) $editVersion;
    $createdAt = gmdate('c');
    $payload['version'] = $version;
    $payload['createdAt'] = $createdAt;

    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    if ($editVersion !== null) {
        $exists = $database->prepare('SELECT 1 FROM fq_versions WHERE version_no = :version_no');
        $exists->execute([':version_no' => $version]);
        if (!$exists->fetchColumn()) {
            throw new InvalidArgumentException('Die zu bearbeitende Version wurde nicht gefunden.');
        }

        $statement = $database->prepare(
            'UPDATE fq_versions SET created_at = :created_at, payload_json = :payload_json WHERE version_no = :version_no'
        );
    } else {
        $statement = $database->prepare(
            'INSERT INTO fq_versions (version_no, created_at, payload_json) VALUES (:version_no, :created_at, :payload_json)'
        );
    }
    $statement->execute([
        ':version_no' => $version,
        ':created_at' => $createdAt,
        ':payload_json' => $payloadJson,
    ]);
    $database->commit();

    echo json_encode([
        'ok' => true,
        'version' => $version,
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
