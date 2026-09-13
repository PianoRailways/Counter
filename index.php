<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Frequenz-Zähler</title>
    <script src="didokmappingcounter.js"></script>
    <style>
        :root {
            --c1: #ffeb3b; --c2: #ff8a80; --wr: #e040fb;
            --dog: #4caf50; --bike: #2196f3; --nav: #8bc34a;
            --exp: #212121; --res: #f44336; --wres: #fb8c00;
        }
        body { width: 375px; margin: 0 auto; background: #f0f0f0; padding: 10px; font-family: sans-serif; }
        .row { display: flex; gap: 5px; margin-bottom: 5px; alight-items: flex-start; }
        input { padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 10px; flex: 1; min-width: 0; outline: none; }
        
        #display { background: white; padding: 12px; border-radius: 8px; margin: 10px 0; font-weight: bold; line-height: 1.4; border-left: 5px solid #333; }

        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; }
        button { aspect-ratio: 1/1; border: none; border-radius: 6px; color: white; font-weight: bold; font-size: 20px; cursor: pointer; }
        button:active { opacity: 0.6; }

        .b1 { background: var(--c1); color: #000; }
        .b2 { background: var(--c2); }
        .bwr { background: var(--wr); }
        .bdog { background: var(--dog); }
        .bbike { background: var(--bike); }
        .bnav { background: var(--nav); }
        .bexp { background: var(--exp); }
        
        .btn-small { aspect-ratio: auto; height: 40px; font-size: 15px; }
        .bwres { background: var(--wres); }
        .bres { background: var(--res); }
		
		/* Custom Suggestions Container */
		.suggestions-container {
			flex: 1;
			position: relative;
			min-width: 0;;
		}


		/* Die Inputs innerhalb der Container nutzen 100% der Containerbreite */
		.suggestions-container input {
			width: 100%;
			box-sizing: border-box; /* Wichtig, damit Padding den Input nicht sprengt */
			padding: 10px;
			font-size: 10px;
			border: 1px solid #ccc;
			border-radius: 4px;
			outline: none;
		}

		.suggestions-list {
			position: absolute;
			top: 100%;
			left: 0;
			right: 0;
			background: white;
			border: 1px solid #ccc;
			border-top: none;
			border-radius: 0 0 4px 4px;
			z-index: 1000;
			max-height: 250px;
			overflow-y: auto;
			box-shadow: 0 4px 6px rgba(0,0,0,0.1);
			display: none;
		}

		.suggestion-item {
			padding: 10px;
			cursor: pointer;
			font-size: 14px;
			border-bottom: 1px solid #eee;
			display: flex;
			justify-content: space-between;
		}

		.suggestion-item:last-child { border-bottom: none; }

		.suggestion-item:hover, .suggestion-item.active {
			background-color: #e3f2fd;
		}

		.suggestion-item b { color: #1565c0; }
    </style>
</head>
<body>

    <div class="row">
        <input id="trainNumber" type="tel" placeholder="Fahrtnummer">
        <input id="productID" placeholder="Kategorie">
        <input id="vehicleType" placeholder="Fz-Typ">
    </div>
    <div class="row">
    <div class="suggestions-container">
        <input id="fromStation" placeholder="Von" autocomplete="off" oninput="updateSuggestions(this, event)">
        <div id="suggestions-fromStation" class="suggestions-list"></div>
    </div>
    <div class="suggestions-container">
        <input id="toStation" placeholder="Bis" autocomplete="off" oninput="updateSuggestions(this, event)">
        <div id="suggestions-toStation" class="suggestions-list"></div>
    </div>
</div>

    <datalist id="stationen"></datalist>

    <div id="display">Lade...</div>

    <div class="grid">
        <button class="b1" onclick="change('firstClass', 1)">A +1</button>
        <button class="b1" onclick="change('firstClass', 10)">A +10</button>
        <button class="b1" onclick="change('firstClass', -1)">A -1</button>
        <button class="b1" onclick="change('firstClass', -10)">A -10</button>

        <button class="b2" onclick="change('secondClass', 1)">B +1</button>
        <button class="b2" onclick="change('secondClass', 10)">B +10</button>
        <button class="b2" onclick="change('secondClass', -1)">B -1</button>
        <button class="b2" onclick="change('secondClass', -10)">B -10</button>

        <button class="bwr" onclick="change('restaurantClass', 1)">WR +1</button>
        <button class="bwr" onclick="change('restaurantClass', -1)">WR -1</button>
        <button class="bdog" onclick="change('dogs', 1)">Hund +1</button>
        <button class="bdog" onclick="change('dogs', -1)">Hund -1</button>

        <button class="bbike" onclick="change('bikes', 1)">Velos +1</button>
        <button class="bbike" onclick="change('bikes', -1)">Velos -1</button>
        <button class="bnav" onclick="prev()">« Wg -1</button>
        <button class="bnav" onclick="next()">Wg » +1</button>

        <button class="bexp" style="grid-column: span 4; aspect-ratio: auto; height: 40px;" onclick="save()">Speichern</button>
        
        <button class="bwres btn-small" style="grid-column: span 2;" onclick="resetCurrentCar()">Wg. Reset</button>
        <button class="bres btn-small" style="grid-column: span 2;" onclick="resetAll()">Alles Reset</button>
    </div>

    <div class="row" style="margin-top:10px;">
        <input id="comments" placeholder="Bemerkung zum Wagen..." oninput="updateComment()">
    </div>

<script>
    let data = [{ firstClass:0, secondClass:0, restaurantClass:0, dogs:0, bikes:0, comments:'' }];
    let currentCar = 1;

    let currentFocus = -1;

function updateSuggestions(el, event) {
    const val = el.value.toUpperCase().trim();
    const listId = "suggestions-" + el.id;
    const listContainer = document.getElementById(listId);
    
    listContainer.innerHTML = '';
    listContainer.style.display = 'none';
    currentFocus = -1;

    if (val.length < 1 || !window.didokMapping) return;

    let suggestions = [];
    for (let key in window.didokMapping) {
        const id = String(key).toUpperCase();
        const name = String(window.didokMapping[key]);
        if (id.includes(val) || name.toUpperCase().includes(val)) {
            suggestions.push({ id, name });
        }
    }

    // Intelligente Sortierung

	suggestions.sort((a, b) => {
		const query = val.toUpperCase();
		const aName = a.name.toUpperCase();
		const bName = b.name.toUpperCase();
		const aId = a.id.toUpperCase();
		const bId = b.id.toUpperCase();

		// 1. Priorität: Exakter Treffer bei der ID (z.B. "BN")
		if (aId === query && bId !== query) return -1;
		if (bId === query && aId !== query) return 1;

		// 2. Priorität: Exakter Treffer beim Namen (z.B. "BERN")
		if (aName === query && bName !== query) return -1;
		if (bName === query && aName !== query) return 1;

		// 3. Priorität: Name beginnt mit dem Suchbegriff (Bernfeld vor Aarberg)
		if (aName.startsWith(query) && !bName.startsWith(query)) return -1;
		if (bName.startsWith(query) && !aName.startsWith(query)) return 1;

		// 4. Fallback: Alphabetisch
		return a.name.localeCompare(b.name);
	});

    if (suggestions.length > 0) {
        listContainer.style.display = 'block';
        suggestions.slice(0, 15).forEach((s, index) => {
            const div = document.createElement("div");
            div.className = "suggestion-item";
            div.innerHTML = `<span>${s.name}</span><b>${s.id}</b>`;
            div.onclick = function() {
                el.value = s.name;
                listContainer.style.display = 'none';
            };
            listContainer.appendChild(div);
        });
    }
}

// Schließen des Menüs bei Klick außerhalb
document.addEventListener("click", function (e) {
    if (!e.target.classList.contains('suggestions-list') && e.target.tagName !== 'INPUT') {
        document.querySelectorAll('.suggestions-list').forEach(l => l.style.display = 'none');
    }
});

    function render() {
        const cur = data[currentCar-1];
        const total = data.reduce((s, c) => s + (c.firstClass + c.secondClass + c.restaurantClass), 0);
        const totalDogs = data.reduce((s, c) => s + (c.dogs), 0);
        const totalBikes = data.reduce((s, c) => s + (c.bikes), 0);
        document.getElementById('display').innerHTML = 
            `Wagen: ${currentCar} (${cur.firstClass + cur.secondClass + cur.restaurantClass}P)` +
            ` | A/B/WR: ${total} | V: ${totalBikes} | H: ${totalDogs}<br>` +
            `A: ${cur.firstClass} | B: ${cur.secondClass} | WR: ${cur.restaurantClass}` + 
            ` | Hunde: ${cur.dogs} | Velos: ${cur.bikes}`;
        document.getElementById('comments').value = cur.comments || '';
    }

    function change(f, v) { 
        data[currentCar-1][f] = Math.max(0, data[currentCar-1][f] + v); 
        render(); 
    }

    function next() { 
        currentCar++; 
        if(!data[currentCar-1]) data.push({firstClass:0, secondClass:0, restaurantClass:0, dogs:0, bikes:0, comments:''});
        render(); 
    }

    function prev() { if(currentCar > 1) { currentCar--; render(); } }
    function updateComment() { data[currentCar-1].comments = document.getElementById('comments').value; }
    
    function resetCurrentCar() {
        if(confirm(`Wagen ${currentCar} wirklich auf Null setzen?`)) {
            data[currentCar-1] = { firstClass:0, secondClass:0, restaurantClass:0, dogs:0, bikes:0, comments:'' };
            render();
        }
    }

    function resetAll() { if(confirm("Gesamte Frequenzerhebung löschen?")) location.reload(); }

    async function save() {
        const trainNum = document.getElementById('trainNumber').value;
        if (!trainNum) {
            alert("Bitte zumindest eine Fahrtnummer angeben.");
            return;
        }

        const payload = {
            trainNumber: trainNum,
            productID: document.getElementById('productID').value,
            vehicleType: document.getElementById('vehicleType').value,
            fromStation: document.getElementById('fromStation').value,
            toStation: document.getElementById('toStation').value,
            cars: data
        };

        try {
            const res = await fetch('api.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload) 
            });
            const resData = await res.json();

            if (resData.status === 'success') {
                alert("Daten erfolgreich übermittelt!");
                location.reload(); 
            } else {
                alert("Fehler beim Speichern: " + resData.message);
            }
        } catch(e) { 
            alert("Serverfehler: Verbindung zum Server unterbrochen."); 
        }
    }

    render();
</script>
</body>
</html>