<?php
$dbFile = __DIR__ . '/zugdaten.db';
$adminPassword = "kaunter"; // <--- HIER DEIN PASSWORT SETZEN
// ... restlicher PDO Code von vorhin ...

try {
    // Verbindung zur SQLite Datei (wird erstellt, falls nicht vorhanden)
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Tabellen automatisch erstellen, falls sie noch nicht existieren
    $pdo->exec("CREATE TABLE IF NOT EXISTS fahrten (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        zugnummer TEXT,
        produkt_id TEXT,
        von_station TEXT,
        bis_station TEXT,
        datum TEXT,
        zeit_erfasst DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS wagen_daten (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        fahrt_id INTEGER,
        wagen_index INTEGER,
        pax_a INTEGER,
        pax_b INTEGER,
        pax_wr INTEGER,
        hunde INTEGER,
        velos INTEGER,
        bemerkung TEXT,
        FOREIGN KEY (fahrt_id) REFERENCES fahrten(id) ON DELETE CASCADE
    )");

} catch (PDOException $e) {
    header('Content-Type: application/json');
    die(json_encode(['status' => 'error', 'message' => 'Datenbankfehler: ' . $e->getMessage()]));
}