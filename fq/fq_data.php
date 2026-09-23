<?php
declare(strict_types=1);

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

$databasePath = __DIR__ . '/fqdata.sqlite';

if (!is_file($databasePath)) {
    echo json_encode(['ok' => true, 'versions' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $database = new PDO('sqlite:' . $databasePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $query = $database->query(
        'SELECT version_no, created_at, payload_json FROM fq_versions ORDER BY version_no DESC'
    );
    $versions = [];

    foreach ($query as $row) {
        $versions[] = [
            'version' => (int) $row['version_no'],
            'createdAt' => $row['created_at'],
            'payload' => json_decode($row['payload_json'], true) ?: [],
        ];
    }

    echo json_encode(['ok' => true, 'versions' => $versions], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('FQ-Daten konnten nicht geladen werden: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Die gespeicherten FQ-Daten konnten nicht geladen werden.',
    ], JSON_UNESCAPED_UNICODE);
}
