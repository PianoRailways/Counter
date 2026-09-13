<?php
require 'db_connect.php'; 
if (!isset($adminPassword)) { $adminPassword = "kaunter"; }
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frequenz-Dashboard Formation</title>
    <style>
        :root {
            --c1: #ffeb3b; /* 1. Klasse Gelb */
            --c2: #ff8a80; /* 2. Klasse Rot/Rosa */
            --wr: #e040fb; /* Speisewagen Lila */
        }
        body { font-family: sans-serif; background: #f0f2f5; margin: 0; padding: 20px; color: #1a1a1a; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #d1d1d1; padding-bottom: 15px; }
        .search-box { display: flex; gap: 8px; margin-bottom: 20px; }
        input { padding: 12px; border: 1px solid #bbb; border-radius: 6px; flex: 1; font-size: 16px; outline: none; }
        button { padding: 10px 20px; background: #2196f3; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }

        .train-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 30px; overflow: hidden; border: 1px solid #e0e0e0; }
        .train-info { padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .train-title { font-size: 1.2em; font-weight: bold; }
        .train-meta { font-size: 0.9em; color: #666; margin-top: 4px; }

        .formation-wrapper { 
            display: flex; 
            overflow-x: auto; 
            padding: 25px 20px; 
            gap: 15px; 
            background: #ffffff;
            align-items: flex-start;
        }
        .formation-wrapper::-webkit-scrollbar { height: 8px; }
        .formation-wrapper::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }

        .wagon { 
            min-width: 130px; 
            max-width: 130px;
            background: #fff; 
            border: 2px solid #333; 
            border-radius: 6px; 
            position: relative;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }
        .wagon::after { content: ''; position: absolute; right: -17px; top: 50%; width: 15px; height: 4px; background: #666; }
        .wagon:last-child::after { display: none; }

        .gangway-marker {
            position: absolute;
            top: 50%;
            width: 24px;
            height: 24px;
            margin-top: -12px;
            border: 3px solid #d00000;
            border-radius: 50%;
            background: #fff;
            z-index: 2;
            box-sizing: border-box;
        }
        .gangway-marker::after {
            content: '';
            position: absolute;
            left: 3px;
            right: 3px;
            top: 8px;
            height: 3px;
            background: #d00000;
        }
        .gangway-marker.front { left: -14px; }
        .gangway-marker.rear { right: -14px; }
        .gangway-marker.all { top: 22px; right: 8px; margin-top: 0; }

        .wagon-nr { background: #333; color: white; text-align: center; font-size: 11px; padding: 3px 0; font-weight: bold; }
        /* Der class-strip wird jetzt über JavaScript mit einem Gradient gefüllt */
.class-strip { 
    height: 10px; 
    width: 100%; 
    border-bottom: 1px solid #333; 
    background: #eee; /* Fallback */
}
        
        /* Farben für die Balken */
        .bg-a { background: var(--c1); }
        .bg-b { background: var(--c2); }
        .bg-wr { background: var(--wr); }
        .bg-empty { background: #eee; }

        .wagon-content { padding: 10px; font-size: 13px; }
        .pax-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .label { color: #888; font-size: 10px; font-weight: bold; }
        
        .wagon-footer { font-size: 10px; color: #777; padding: 6px; border-top: 1px solid #eee; background: #fafafa; min-height: 1.2em; }
        .admin-only { display: none; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-area">
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
    </div>

    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Zugnummer, Station oder Fz-Typ..." onkeyup="if(event.key==='Enter') loadData()">
        <button onclick="loadData()">Suchen</button>
    </div>

    <div id="results">Lade Formationen...</div>
</div>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    const isAdmin = (urlParams.get('pw') === "<?php echo $adminPassword; ?>");

    if (isAdmin) {
        document.querySelectorAll('.admin-only').forEach(el => el.style.display = 'block');
    }

    function formatDateTime(raw) {
        if (!raw) return "-";
        try {
            const t = raw.split(/[- :]/);
            if(t.length < 3) return raw;
            return `${t[2]}.${t[1]}.${t[0]} ${t[3]}:${t[4]}`;
        } catch(e) { return raw; }
    }

    async function loadData() {
        const search = document.getElementById('searchInput').value;
        const resContainer = document.getElementById('results');
        resContainer.innerHTML = "Suche Züge...";

        try {
            const response = await fetch(`get_data.php?search=${encodeURIComponent(search)}`);
            const data = await response.json();

            if (data.length === 0) {
                resContainer.innerHTML = "Keine Daten gefunden.";
                return;
            }

            const grouped = {};
            data.forEach(row => {
                const fId = row.fahrt_id || row.id;
                if (!grouped[fId]) grouped[fId] = { info: row, wagen: [] };
                grouped[fId].wagen.push(row);
            });

            resContainer.innerHTML = "";

            for (const fId in grouped) {
                const fahrt = grouped[fId];
                const info = fahrt.info;
                fahrt.wagen.sort((a, b) => a.wagen_index - b.wagen_index);
                const displayTime = formatDateTime(info.datum || info.zeit_erfasst);

                let trainHtml = `
                    <div class="train-card">
                        <div class="train-info">
                            <div>
                                <div class="train-title">
                                    ${info.produkt_id} ${info.zugnummer}
                                    <span style="font-size: 0.7em; color: #1565c0; margin-left: 10px; font-weight: normal; font-style: italic;">
                                        ${info.fahrzeug_typ || ''}
                                    </span>
                                </div>
                                <div class="train-meta">${info.von_station} → ${info.bis_station}</div>
                            </div>
                            <div style="text-align: right">
                                <div style="font-weight: bold; color: #333; font-size: 0.9em;">${displayTime}</div>
                                ${isAdmin ? `<button onclick="deleteFahrt(${fId})" style="background:#f44336; color:white; border:none; border-radius:4px; padding: 4px 8px; font-size: 10px; margin-top:5px; cursor:pointer;">Löschen</button>` : ''}
                            </div>
                        </div>
                        <div class="formation-wrapper">`;

                fahrt.wagen.forEach(w => {
    const a = parseInt(w.pax_a || 0);
    const b = parseInt(w.pax_b || 0);
    const wr = parseInt(w.pax_wr || 0);
                const gangwayClosed = Number(w.uebergang_geschlossen) === 1;
                const frontGangwayClosed = Number(w.uebergang_vorne_geschlossen) === 1;
                const rearGangwayClosed = Number(w.uebergang_hinten_geschlossen) === 1;

    // Array für aktive Klassen sammeln
    let activeColors = [];
    if (a > 0) activeColors.push('var(--c1)');
    if (b > 0) activeColors.push('var(--c2)');
    if (wr > 0) activeColors.push('var(--wr)');

    // Gradient erstellen: Wenn keine Pax, dann grau, sonst aufgeteilt
    let gradientStyle = "";
    if (activeColors.length === 0) {
        gradientStyle = "background: #eee;";
    } else if (activeColors.length === 1) {
        gradientStyle = `background: ${activeColors[0]};`;
    } else {
        // Erzeugt einen harten Übergang zwischen den Farben (50/50 oder 33/33/33)
        const step = 100 / activeColors.length;
        let stops = [];
        activeColors.forEach((color, index) => {
            stops.push(`${color} ${index * step}%`);
            stops.push(`${color} ${(index + 1) * step}%`);
        });
        gradientStyle = `background: linear-gradient(to right, ${stops.join(', ')});`;
    }

    trainHtml += `
        <div class="wagon">
            <div class="wagon-nr">Wagen ${w.wagen_index}</div>
            ${gangwayClosed ? '<span class="gangway-marker all" title="Wagenübergang geschlossen"></span>' : ''}
            ${frontGangwayClosed ? '<span class="gangway-marker front" title="Wagenübergang vorne geschlossen"></span>' : ''}
            ${rearGangwayClosed ? '<span class="gangway-marker rear" title="Wagenübergang hinten geschlossen"></span>' : ''}
            <div class="class-strip" style="${gradientStyle}"></div>
            <div class="wagon-content">
                <div class="pax-row"><span class="label">1. Kl</span> <b>${a}</b></div>
                <div class="pax-row"><span class="label">2. Kl</span> <b>${b}</b></div>
                <div class="pax-row"><span class="label">Rest.</span> <b>${wr}</b></div>
                <div style="margin-top:10px; display:flex; gap:12px; font-size: 12px; border-top: 1px dashed #eee; padding-top: 5px;">
                    <span title="Hunde">🐕 ${w.hunde || 0}</span>
                    <span title="Velos">🚲 ${w.velos || 0}</span>
                </div>
            </div>
            <div class="wagon-footer" title="${w.bemerkung || ''}">
                ${w.bemerkung ? w.bemerkung : '&nbsp;'}
            </div>
        </div>`;
});

                trainHtml += `</div></div>`;
                resContainer.innerHTML += trainHtml;
            }
        } catch (e) { resContainer.innerHTML = "Fehler beim Laden."; }
    }

    async function deleteFahrt(id) {
        if(!confirm("Gesamte Fahrt löschen?")) return;
        const pw = urlParams.get('pw');
        await fetch(`api_admin.php?pw=${pw}&action=delete&id=${id}`);
        loadData();
    }

    loadData();
</script>
</body>
</html>