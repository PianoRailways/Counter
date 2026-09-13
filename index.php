<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Frequenz-Zähler</title>
    <script src="didokmappingcounter.js"></script>
    <link rel="stylesheet" href="style.css">
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

        <button id="frontGangwayClosedButton" class="bgangway" style="grid-column: span 2;" onclick="toggleGangway('frontGangwayClosed')">Vorne zu</button>
        <button id="rearGangwayClosedButton" class="bgangway" style="grid-column: span 2;" onclick="toggleGangway('rearGangwayClosed')">Hinten zu</button>

        <button class="bexp" style="grid-column: span 4; aspect-ratio: auto; height: 40px;" onclick="save()">Speichern</button>
        
        <button class="bwres btn-small" style="grid-column: span 2;" onclick="resetCurrentCar()">Wg. Reset</button>
        <button class="bres btn-small" style="grid-column: span 2;" onclick="resetAll()">Alles Reset</button>
    </div>

    <div class="row" style="margin-top:10px;">
        <input id="comments" placeholder="Bemerkung zum Wagen..." oninput="updateComment()">
    </div>

<script>
    const emptyCar = () => ({ firstClass:0, secondClass:0, restaurantClass:0, dogs:0, bikes:0, comments:'', frontGangwayClosed:false, rearGangwayClosed:false });
    let data = [emptyCar()];
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
        ['frontGangwayClosed', 'rearGangwayClosed'].forEach(field => {
            const button = document.getElementById(field + 'Button');
            button.classList.toggle('active', Boolean(cur[field]));
            button.setAttribute('aria-pressed', Boolean(cur[field]));
        });
    }

    function change(f, v) { 
        data[currentCar-1][f] = Math.max(0, data[currentCar-1][f] + v); 
        render(); 
    }

    function next() { 
        currentCar++; 
        if(!data[currentCar-1]) data.push(emptyCar());
        render(); 
    }

    function prev() { if(currentCar > 1) { currentCar--; render(); } }
    function updateComment() { data[currentCar-1].comments = document.getElementById('comments').value; }
    function toggleGangway(field) {
        data[currentCar-1][field] = !data[currentCar-1][field];
        render();
    }
    
    function resetCurrentCar() {
        if(confirm(`Wagen ${currentCar} wirklich auf Null setzen?`)) {
            data[currentCar-1] = emptyCar();
            render();
        }
    }

    function resetAll() {
        if (!confirm("Gesamte Frequenzerhebung löschen?")) return;

        data = [emptyCar()];
        currentCar = 1;
        ['trainNumber', 'productID', 'vehicleType', 'fromStation', 'toStation'].forEach(id => {
            document.getElementById(id).value = '';
        });
        document.querySelectorAll('.suggestions-list').forEach(list => {
            list.innerHTML = '';
            list.style.display = 'none';
        });
        render();
    }

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