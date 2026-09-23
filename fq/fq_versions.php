<?php
declare(strict_types=1);

$databasePath = __DIR__ . '/fqdata.sqlite';
$versions = [];
$error = null;

if (is_file($databasePath)) {
    try {
        $database = new PDO('sqlite:' . $databasePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $query = $database->query('SELECT version_no, created_at, payload_json FROM fq_versions ORDER BY version_no DESC');
        foreach ($query as $version) {
            $version['payload'] = json_decode($version['payload_json'], true) ?: [];
            unset($version['payload_json']);
            $versions[] = $version;
        }
    } catch (Throwable $exception) {
        $error = 'Die Versionen konnten nicht geladen werden.';
        error_log('FQ-Versionsansicht fehlgeschlagen: ' . $exception->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FQ-Versionen</title>
    <style>
        body { margin: 0; padding: 24px; background: #333; color: #222; font: 14px Arial, sans-serif; }
        main { max-width: 900px; margin: 0 auto; background: #fff; padding: 20px; }
        h1 { margin-top: 0; }
        .version { margin-top: 16px; padding: 14px; border: 1px solid #bbb; background: #f5f5f5; }
        .meta { color: #555; margin-bottom: 8px; }
        pre { margin: 0; padding: 12px; overflow: auto; background: #222; color: #eee; font-size: 12px; }
        .empty, .error { padding: 12px; background: #fff3cd; }
    </style>
</head>
<body>
<main>
    <h1>FQ-Versionen</h1>
    <?php if ($error !== null): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php elseif ($versions === []): ?>
        <div class="empty">Noch keine gespeicherten Versionen vorhanden.</div>
    <?php else: ?>
        <?php foreach ($versions as $version): ?>
            <article class="version">
                <div class="meta">
                    Version <?= (int) $version['version_no'] ?> · <?= htmlspecialchars($version['created_at'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <pre><?= htmlspecialchars(json_encode($version['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></pre>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>
