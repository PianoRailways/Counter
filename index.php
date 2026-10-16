<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Zähler">
    <meta name="theme-color" content="#f0f0f0">
    <title>Frequenz-Zähler</title>
    <script src="didokmappingcounter.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="apple-touch-icon" href="apple-touch-icon.png">
    <link rel="manifest" href="manifest.webmanifest">
</head>
<body>

    <div class="row">
        <input id="trainNumber" type="tel" inputmode="numeric" placeholder="Fahrtnummer" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
        <input id="productID" placeholder="Kategorie" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
        <input id="vehicleType" placeholder="Fz-Typ" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
    </div>
    <div class="row">
    <div class="suggestions-container">
        <input id="fromStation" placeholder="Von" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" oninput="updateSuggestions(this)">
        <div id="suggestions-fromStation" class="suggestions-list"></div>
    </div>
    <div class="suggestions-container">
        <input id="toStation" placeholder="Bis" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" oninput="updateSuggestions(this)">
        <div id="suggestions-toStation" class="suggestions-list"></div>
    </div>
</div>

    <div id="display">Lade...</div>
    <div id="toast" role="status" aria-live="polite"></div>

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

        <button class="bbike" onclick="change('bikes', 1)">Velo +1</button>
        <button class="bbike" onclick="change('bikes', -1)">Velo -1</button>
        <button class="bnav" onclick="prev()">« Wg -1</button>
        <button class="bnav" onclick="next()">Wg » +1</button>

        <button class="bexp btn-save" onclick="save()">Speichern</button>
        
        <button class="bwres btn-small btn-reset-car" onclick="resetCurrentCar()">Wg. Reset</button>
        <button class="bres btn-small btn-reset-all" onclick="resetAll()">Alles Reset</button>
    </div>

    <div class="row" style="margin-top:10px;">
        <input id="comments" placeholder="Bemerkung zum Wagen..." enterkeyhint="done" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" oninput="updateComment()">
        <button id="frontGangwayClosedButton" class="bgangway btn-small" onclick="toggleGangway('frontGangwayClosed')">Vorne zu</button>
        <button id="rearGangwayClosedButton" class="bgangway btn-small" onclick="toggleGangway('rearGangwayClosed')">Hinten zu</button>

    </div>

<script>
    const emptyCar = () => ({ firstClass:0, secondClass:0, restaurantClass:0, dogs:0, bikes:0, comments:'', frontGangwayClosed:false, rearGangwayClosed:false });
    let data = [emptyCar()];
    let currentCar = 1;

    let currentFocus = -1;
    let stationIndex;
    let suggestionTimer;

function getState() {
    return {
        data,
        currentCar,
        trainNumber: document.getElementById('trainNumber').value,
        productID: document.getElementById('productID').value,
        vehicleType: document.getElementById('vehicleType').value,
        fromStation: document.getElementById('fromStation').value,
        toStation: document.getElementById('toStation').value
    };
}

function saveState() {
    try {
        localStorage.setItem('counterState', JSON.stringify(getState()));
    } catch (error) {
        console.warn('Zustand konnte nicht gespeichert werden.', error);
    }
}

function restoreState() {
    try {
        const saved = localStorage.getItem('counterState');
        if (!saved) return;
        const state = JSON.parse(saved);
        if (!Array.isArray(state.data) || !state.data.length) return;
        data = state.data;
        currentCar = Math.min(Math.max(Number(state.currentCar) || 1, 1), data.length);
        ['trainNumber', 'productID', 'vehicleType', 'fromStation', 'toStation'].forEach(id => {
            document.getElementById(id).value = typeof state[id] === 'string' ? state[id] : '';
        });
    } catch (error) {
        console.warn('Gespeicherter Zustand konnte nicht geladen werden.', error);
    }
}

function updateSuggestions(el) {
    window.clearTimeout(suggestionTimer);
    suggestionTimer = window.setTimeout(() => renderSuggestions(el), 80);
}

function getStationIndex() {
    if (stationIndex) return stationIndex;

    const byName = new Map();
    Object.entries(window.didokMapping || {}).forEach(([rawId, rawName]) => {
        const id = String(rawId).trim();
        if (!id || /^\d+$/.test(id)) return;
        const name = String(Array.isArray(rawName) ? rawName[0] : rawName).trim();
        if (!name) return;
        const nameUp = name.toUpperCase();
        const existing = byName.get(nameUp);
        if (existing) {
            existing.ids.push(id);
            existing.ids.sort((a, b) => a.length - b.length || a.localeCompare(b));
        } else {
            byName.set(nameUp, { name, nameUp, ids: [id] });
        }
    });

    stationIndex = Array.from(byName.values()).map(station => ({
        id: station.ids.join(', '),
        idUp: station.ids.join(', ').toUpperCase(),
        name: station.name,
        nameUp: station.nameUp
    }));
    return stationIndex;
}

function renderSuggestions(el) {
    const val = el.value.toUpperCase().trim();
    const listId = "suggestions-" + el.id;
    const listContainer = document.getElementById(listId);
    
    listContainer.innerHTML = '';
    listContainer.style.display = 'none';
    currentFocus = -1;

    if (val.length < 1 || !window.didokMapping) return;

    const suggestions = getStationIndex().filter(station =>
        station.idUp.includes(val) || station.nameUp.includes(val)
    );

    // Intelligente Sortierung

	suggestions.sort((a, b) => {
		const query = val.toUpperCase();
		const aName = a.name.toUpperCase();
		const bName = b.name.toUpperCase();
		const aId = a.idUp;
		const bId = b.idUp;

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
            const name = document.createElement("span");
            name.textContent = s.name;
            const id = document.createElement("b");
            id.textContent = s.id;
            div.append(name, id);
            div.onpointerdown = function(e) {
                e.preventDefault();
                el.value = s.name;
                listContainer.style.display = 'none';
                el.blur();
            };
            listContainer.appendChild(div);
        });
    }
}

// Schließen des Menüs bei Interaktion außerhalb
function closeSuggestionsOutside(e) {
    if (!e.target.closest('.suggestions-container')) {
        document.querySelectorAll('.suggestions-list').forEach(l => l.style.display = 'none');
    }
}
document.addEventListener("click", closeSuggestionsOutside);
document.addEventListener("touchstart", closeSuggestionsOutside, { passive: true });
document.addEventListener("pointerdown", closeSuggestionsOutside);

document.querySelectorAll('input').forEach(input => {
    input.addEventListener('focusout', () => {
        window.setTimeout(() => window.scrollTo(0, 0), 100);
    });
    input.addEventListener('keydown', event => {
        if (event.key === 'Enter') input.blur();
    });
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
        saveState();
        render(); 
    }

    function next() { 
        currentCar++; 
        if(!data[currentCar-1]) data.push(emptyCar());
        saveState();
        render(); 
    }

    function prev() { if(currentCar > 1) { currentCar--; saveState(); render(); } }
    function updateComment() {
        data[currentCar-1].comments = document.getElementById('comments').value;
        saveState();
    }
    function toggleGangway(field) {
        data[currentCar-1][field] = !data[currentCar-1][field];
        saveState();
        render();
    }
    
    function resetCurrentCar() {
        if(confirm(`Wagen ${currentCar} wirklich auf Null setzen?`)) {
            data[currentCar-1] = emptyCar();
            saveState();
            render();
        }
    }

    function resetCounterState() {
        data = [emptyCar()];
        currentCar = 1;
        try {
            localStorage.removeItem('counterState');
        } catch (error) {
            console.warn('Gespeicherter Zustand konnte nicht gelöscht werden.', error);
        }
        ['trainNumber', 'productID', 'vehicleType', 'fromStation', 'toStation'].forEach(id => {
            document.getElementById(id).value = '';
        });
        document.querySelectorAll('.suggestions-list').forEach(list => {
            list.innerHTML = '';
            list.style.display = 'none';
        });
        render();
    }

    function resetAll() {
        if (confirm("Gesamte Frequenzerhebung löschen?")) resetCounterState();
    }

    function showToast(message) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.classList.remove('visible');
        window.requestAnimationFrame(() => toast.classList.add('visible'));
        window.setTimeout(() => toast.classList.remove('visible'), 2000);
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

        const saveButton = document.querySelector('.btn-save');
        const originalText = saveButton.textContent;
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 15000);
        saveButton.disabled = true;
        saveButton.textContent = "Speichert...";

        try {
            const res = await fetch('api.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                signal: controller.signal
            });
            const resData = await res.json();

            if (resData.status === 'success') {
                showToast("Daten erfolgreich übermittelt!");
                resetCounterState();
            } else {
                alert("Fehler beim Speichern: " + resData.message);
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                alert("Speichern abgebrochen: Der Server hat nicht rechtzeitig geantwortet.");
            } else {
                alert("Speichern fehlgeschlagen: Bitte Verbindung prüfen und erneut versuchen.");
            }
        } finally {
            window.clearTimeout(timeout);
            saveButton.disabled = false;
            saveButton.textContent = originalText;
        }
    }

    ['trainNumber', 'productID', 'vehicleType', 'fromStation', 'toStation'].forEach(id => {
        document.getElementById(id).addEventListener('input', saveState);
    });
    restoreState();
    render();
</script>
</body>
</html>