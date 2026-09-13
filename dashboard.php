<?php
// 1. Verbindung und Passwort aus der zentralen Datei laden
require 'db_connect.php'; 

// Sicherheits-Check: Falls die Variable in db_connect.php fehlt
if (!isset($adminPassword)) {
    $adminPassword = "kaunter"; 
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frequenz-Archiv Admin</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f9; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1000px; margin: 0 auto; }
        h2 { border-bottom: 2px solid #212121; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .search-box { margin-bottom: 20px; display: flex; gap: 10px; }
        input { padding: 10px; border: 1px solid #ccc; border-radius: 4px; flex: 1; font-size: 16px; outline: none; }
        input:focus { border-color: #2196f3; }
        button { padding: 10px 20px; background: #2196f3; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button:hover { background: #1976d2; }
        
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
        .card-header { background: #eee; padding: 12px 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; }
        .timestamp { font-size: 0.85em; color: #666; font-weight: normal; margin-right: 10px; }
        .fz-info { font-size: 0.9em; color: #1565c0; margin-left: 10px; font-style: italic; }
        
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; padding: 12px 15px; border-bottom: 1px solid #eee; }
        th { background: #f9f9f9; color: #666; font-size: 0.75em; text-transform: uppercase; letter-spacing: 0.5px; }
        tr:last-child td { border-bottom: none; }
        
        .btn-del { background: #f44336; padding: 6px 12px; font-size: 12px; border-radius: 4px; color: white; border: none; cursor: pointer; }
        .btn-del:hover { background: #d32f2f; }
        .admin-only { display: none; }
    </style>
</head>
<body>

<div class="container">
    <h2>
        <div style="display: flex; align-items: center; gap: 15px;">
            Frequenz-Archiv
            <a href="https://stellwerksim.ch/counter5" style="text-decoration: none;">
                <button type="button">
                    Fahrt erfassen
                </button>
            </a>
        </div>
        
        <span id="adminBadge" class="admin-only" style="font-size: 12px; background: #4caf50; color: white; padding: 4px 8px; border-radius: 4px;">
            Admin Modus
        </span>
    </h2>
    
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Zugnummer oder Station suchen..." onkeyup="if(event.key==='Enter') loadData()">
        <button onclick="loadData()">Suchen</button>
        <button onclick="exportToCSV()" id="exportBtn" class="admin-only" style="background: #4caf50;">Export (CSV)</button>
    </div>

    <div id="results">Lade Daten...</div>
</div>

<script>
    // 1. Passwort-Logik
    const urlParams = new URLSearchParams(window.location.search);
    const eingegebenesPw = urlParams.get('pw');
    
    // PHP injiziert das Passwort hierher
    const korrektesPw = "<?php echo $adminPassword; ?>"; 

    // Diagnose für dich (F12 Konsole)

    // Vergleich (trim entfernt versteckte Leerzeichen)
    const isAdmin = (eingegebenesPw !== null && eingegebenesPw.trim() === korrektesPw.trim());
    console.log("- Ist Admin?:", isAdmin);

    // 2. UI Freischaltung
    if (isAdmin) {
        document.querySelectorAll('.admin-only').forEach(el => {
            el.style.display = 'inline-block';
        });
    }

    window.currentData = [];

    function formatDateTime(raw) {
        if (!raw) return "-";
        try {
            const t = raw.split(/[- :]/);
            if(t.length < 3) return raw;
            let out = `${t[2]}.${t[1]}.${t[0]}`;
            if(t[3]) out += ` ${t[3]}:${t[4]}`;
            return out;
        } catch(e) { return raw; }
    }

    async function deleteEntry(id) {
        if (!isAdmin) return;
        if (!confirm("Diese Fahrt wirklich unwiderruflich löschen?")) return;
        try {
            const res = await fetch(`api_admin.php?pw=${encodeURIComponent(eingegebenesPw)}&action=delete&id=${id}`);
            const result = await res.json();
            if (result.status === 'success') loadData();
            else alert("Fehler: " + result.message);
        } catch (e) { alert("Serverfehler beim Löschen."); }
    }

    async function loadData() {
    const search = document.getElementById('searchInput').value;
    const resContainer = document.getElementById('results');
    resContainer.innerHTML = "Suche...";

    try {
        const response = await fetch(`get_data.php?search=${encodeURIComponent(search)}`);
        const data = await response.json();
        window.currentData = data; 

        if (data.length === 0) {
            resContainer.innerHTML = "Keine Einträge gefunden.";
            return;
        }

        // 1. Daten nach Fahrt gruppieren
        const grouped = {};
        data.forEach(row => {
            const fId = row.fahrt_id || row.id; // Nutzt fahrt_id zur Gruppierung
            if (!grouped[fId]) {
                grouped[fId] = { 
                    info: row, 
                    wagen: [],
                    totals: { a: 0, b: 0, wr: 0, h: 0, v: 0 }
                };
            }
            grouped[fId].wagen.push(row);
            // Summen berechnen
            grouped[fId].totals.a += parseInt(row.pax_a || 0);
            grouped[fId].totals.b += parseInt(row.pax_b || 0);
            grouped[fId].totals.wr += parseInt(row.pax_wr || 0);
            grouped[fId].totals.h += parseInt(row.hunde || 0);
            grouped[fId].totals.v += parseInt(row.velos || 0);
        });

        resContainer.innerHTML = "";

        // 2. Gruppierte Daten ausgeben
        for (const fId in grouped) {
            const fahrt = grouped[fId];
            const info = fahrt.info;
            const t = fahrt.totals;
            const displayDate = formatDateTime(info.datum || info.zeit_erfasst);
            
            // Sortiert die Wagen innerhalb der Fahrt nach ihrem Index (Wagen 1, 2, 3...)
            fahrt.wagen.sort((a, b) => a.wagen_index - b.wagen_index);

            let html = `
                <div class="card">
                    <div class="card-header">
                        <div>
                            <span class="timestamp">${displayDate}</span>
                            <b>${info.produkt_id} ${info.zugnummer}</b> 
                            <span style="color:#666">(${info.von_station} → ${info.bis_station})</span>
                            <div style="color: #1565c0; font-style: italic; font-size: 0.9em; margin-top: 2px;">
                                ${info.fahrzeug_typ || ''}
                            </div>
                        </div>
                        <div class="admin-only" style="${isAdmin ? 'display:block' : 'display:none'}">
                            <button class="btn-del" onclick="deleteEntry(${fId})">Löschen</button>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Wg</th>
                                <th>A (Σ ${t.a})</th>
                                <th>B (Σ ${t.b})</th>
                                <th>WR</th>
                                <th>H</th>
                                <th>V</th>
                                <th>Bemerkung</th>
                            </tr>
                        </thead>
                        <tbody>`;
            
            // Jeder Wagen bekommt eine eigene Zeile in DERSELBEN Tabelle
            fahrt.wagen.forEach(w => {
                html += `<tr>
                    <td><b>${w.wagen_index}</b></td>
                    <td>${w.pax_a || 0}</td>
                    <td>${w.pax_b || 0}</td>
                    <td>${w.pax_wr || 0}</td>
                    <td>${w.hunde || 0}</td>
                    <td>${w.velos || 0}</td>
                    <td style="color:#666; font-size:12px;">${w.bemerkung || '-'}</td>
                </tr>`;
            });

            html += `</tbody></table></div>`;
            resContainer.innerHTML += html;
        }
    } catch (e) { 
        console.error(e);
        resContainer.innerHTML = "Fehler beim Laden der Daten."; 
    }
}

    function exportToCSV() {
        if (!isAdmin) return;
        if (!window.currentData || window.currentData.length === 0) {
            alert("Keine Daten zum Exportieren.");
            return;
        }

        let csvContent = "\ufeff" + "ID;Zeitpunkt;Produkt;Zugnummer;Fz-Typ;Von;Bis;Wagen;A;B;WR;Hunde;Velos;Bemerkung\r\n";
        window.currentData.forEach(row => {
            const line = [
                row.id, row.datum || row.zeit_erfasst, row.produkt_id, row.zugnummer,
                row.fahrzeug_typ || "", `"${row.von_station}"`, `"${row.bis_station}"`,
                row.wagen_index, row.pax_a, row.pax_b, row.pax_wr, row.hunde, row.velos,
                `"${(row.bemerkung || "").replace(/"/g, '""')}"`
            ].join(";");
            csvContent += line + "\r\n";
        });

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `frequenz_export_${new Date().toISOString().slice(0,10)}.csv`;
        link.click();
    }

    loadData();
</script>
</body>
</html>