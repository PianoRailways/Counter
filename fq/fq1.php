<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="FQ Panel">
    <meta name="theme-color" content="#333">
    
    <!-- Favicon -->
    <link rel="icon" type="favicon" href="fq.png">
    <link rel="shortcut icon" href="fq.png" type="image/png">
    
    <!-- Apple Touch Icon (für iOS Home Screen) -->
    <link rel="apple-touch-icon" href="fq.png">
    
    <!-- Web App Manifest für Android -->
    <link rel="manifest" href="fq.png">
    <title>FQ Panel</title>
        <script>
            const abbrevMap = {};
            const nameToAbbrevMap = {};
            window.didokMapping = {};

            async function loadAbbreviations() {
                const countries = ['custom', 'ch', 'de', 'at', 'fr', 'uk', 'libero'];

                for (const country of countries) {
                    try {
                        const response = await fetch(`/didok/${country}.json`);
                        if (!response.ok) continue;

                        const data = await response.json();
                        const countryCode = country.toUpperCase();

                        Object.entries(data).forEach(([abbrev, name]) => {
                            if (typeof name !== 'string') return;

                            if (!abbrevMap[abbrev]) abbrevMap[abbrev] = [];
                            abbrevMap[abbrev].push({ name, country: countryCode });
                            window.didokMapping[abbrev] = name;

                            const normName = name.trim().toLowerCase();
                            if (!nameToAbbrevMap[normName]) nameToAbbrevMap[normName] = [];
                            nameToAbbrevMap[normName].push({ abbrev, country: countryCode });
                        });
                    } catch (error) {
                        console.warn(`Konnte /didok/${country}.json nicht laden:`, error);
                    }
                }

                window.abbrevMap = abbrevMap;
                window.nameToAbbrevMap = nameToAbbrevMap;
                window.dispatchEvent(new Event('didokmappingready'));
                console.log('Abkürzungs-Mappings geladen:', Object.keys(abbrevMap).length, 'Abkürzungen');
            }

            function getAbbrevsForName(stationName) {
                if (!stationName) return [];
                return nameToAbbrevMap[stationName.trim().toLowerCase()] || [];
            }

            loadAbbreviations();
        </script>
    <link rel="stylesheet" type="text/css" href="fq1.css">
</head>
<body>
    <div class="panel-container">
        <!-- Header with Wagenklasse -->
        <div class="panel-header">
            <button class="nav-btn" id="wagen-prev" aria-label="Zum vorherigen Wagen">◀</button>
            <div class="wagen-slider">
                <div class="wagen-track" id="wagen-track"></div>
            </div>
            <button class="nav-btn add-btn" id="wagen-next" aria-label="Neuen Wagen hinzufügen">＋</button>
        </div>

        <!-- Data Display -->
        <div class="data-display">
            <label class="data-field input-field">
                <span></span>
                <input id="fahrtnummer" type="text" inputmode="none" readonly placeholder="Fahrtnummer">
            </label>
            <button class="data-field input-field select-field" id="kategorie" type="button">
                <span></span>
                <strong>Cat.</strong>
            </button>
            <label class="data-field input-field">
                <span></span>
                <input id="fahrtzeugtyp" type="text" placeholder="Fahrzeugtyp">
            </label>
        </div>
        <div class="data-display">
            <div class="station-field">
                <label class="data-field input-field">
                    <span></span>
                    <input id="in-station" type="text" autocomplete="off" placeholder="Startbahnhof">
                </label>
                <div class="station-suggestions" id="in-suggestions"></div>
            </div>
            <button class="data-field function-field nofunction" type="button">Ausl.</button>
            <div class="station-field">
                <label class="data-field input-field">
                    <span></span>
                    <input id="out-station" type="text" autocomplete="off" placeholder="Endbahnhof">
                </label>
                <div class="station-suggestions" id="out-suggestions"></div>
            </div>
        </div>

        <!-- Button Row -->
        <div class="button-row">
            <button class="button-small">1./2.</button>
            <button class="button-small">Ausl</button>
            <button class="button-small" id="remarque-button">Remarque</button>
            <button class="button-small" id="view-button" type="button">View</button>
        </div>

        <!-- Read-only view of saved trains -->
        <section class="view-panel" id="view-panel" hidden>
            <div class="view-panel-header">
                <strong>Gespeicherte Züge</strong>
                <span id="view-status">Lade Daten ...</span>
            </div>
            <div class="view-versions" id="view-versions"></div>
        </section>

        <!-- Grid Section -->
        <div class="grid-section">
                <button class="grid-cell count-cell" data-count-key="A"><span>A</span><strong>0</strong><small class="deck-counts"></small></button>
                <button class="grid-cell count-cell" data-count-key="B"><span>B</span><strong>0</strong><small class="deck-counts"></small></button>
                <button class="grid-cell count-cell" data-count-key="WR"><span>WR</span><strong>0</strong><small class="deck-counts"></small></button>
                <button class="grid-cell nofunction">HR</button>
                <button class="grid-cell nofunction">TK</button>
                <button class="grid-cell nofunction">VERBUND</button>
                <button class="grid-cell nofunction">1/2-ABO</button>
                <button class="grid-cell nofunction">SPEZ</button>
                <button class="grid-cell count-cell" data-count-key="VELO"><span>VELO</span><strong>0</strong><small class="deck-counts"></small></button>
                <button class="grid-cell count-cell" data-count-key="SKUUTER"><span>SKUUTER</span><strong>0</strong><small class="deck-counts"></small></button>
                <button class="grid-cell count-cell" data-count-key="KIWA"><span>KIWA</span><strong>0</strong><small class="deck-counts"></small></button>
                <button class="grid-cell count-cell" data-count-key="BEHIG"><span>BEHIG</span><strong>0</strong><small class="deck-counts"></small></button>
                <button class="grid-cell count-cell" data-count-key="DOG"><span>DOG</span><strong>0</strong><small class="deck-counts"></small></button>
                <button class="grid-cell nofunction">GA</button>
                <button class="grid-cell nofunction">1/2</button>
                <button class="grid-cell nofunction">FAMILIE</button>
            <div class="count-panel" id="count-panel" hidden>
                <div class="count-panel-header">
                    <span id="count-panel-title">A</span>
                    <strong id="count-panel-value">0</strong>
                </div>
                <input id="count-input" class="count-input" type="number" inputmode="numeric" min="0" placeholder="Zahl eingeben">
                <div class="count-actions">
                    <button type="button" class="count-action add" data-count-operation="add">+ Addieren</button>
                    <button type="button" class="count-action subtract" data-count-operation="subtract">− Subtrahieren</button>
                    <button type="button" class="count-action reset" id="count-reset">0 Zurücksetzen</button>
                </div>
                <button type="button" class="count-close" id="count-close">Zurück zur Übersicht</button>
            </div>
        </div>

        <!-- Control Bar -->
        <div class="control-bar">
            <button class="control-field" id="abteilung">Stockwerk</button>
            <button class="control-field fq">2/100</button>
            <button class="control-field">1x</button>
            <button class="star-icon" id="save-button" type="button" aria-label="FQ speichern" title="FQ speichern">&#128190;</button>
            <button class="control-field highlight">ASP</button>
            <button class="control-field">1</button>
            <button class="control-field">Leer</button>
        </div>

        <!-- Keyboard -->
        <div class="keyboard">
            <button class="key number-key">1</button>
            <button class="key number-key">2</button>
            <button class="key number-key">3</button>
            <button class="key" style="grid-row: 1; grid-column: 4; grid-row-end: 3;">Del</button>
            
            <button class="key number-key">4</button>
            <button class="key number-key">5</button>
            <button class="key number-key">6</button>
            
            <button class="key number-key">7</button>
            <button class="key number-key">8</button>
            <button class="key number-key">9</button>
            <button class="key" id="count-next" style="grid-row: 4; grid-column: 4;">Weiter</button>
            
            <button class="key" id="vehicle-keyboard">abc</button>
            <button class="key number-key">0</button>
            <button class="key function">Hafas-Zuglauf</button>
            
            <button class="key function" style="grid-column: 1 / 5; margin-top: 8px;">Enter</button>
        </div>
    </div>

    <!-- Kategorie-Auswahl -->
    <div class="modal-overlay" id="kategorie-modal">
        <div class="modal category-modal">
            <div class="modal-title">Kategorie</div>
            <div class="category-options" id="category-options"></div>
            <div class="modal-close">
                <button onclick="closeModal('kategorie-modal')">Abbrechen</button>
            </div>
        </div>
    </div>

    <!-- Eigene Tastatur fuer den Fahrtzeugtyp -->
    <div class="modal-overlay" id="vehicle-keyboard-modal">
        <div class="modal vehicle-modal">
            <div class="modal-title">Fahrtzeugtyp</div>
            <input class="vehicle-input" id="vehicle-modal-input" type="text" autocomplete="off">
            <div class="vehicle-keys" id="vehicle-keys"></div>
            <div class="modal-close">
                <button id="vehicle-keyboard-done">Übernehmen</button>
            </div>
        </div>
    </div>

    <!-- Wagenklasse Modal -->
    <div class="modal-overlay" id="wagenklasse-modal">
        <div class="modal">
            <div class="modal-title">Wagenklasse</div>
            <div class="modal-option" data-value="2">2. Klasse (2)</div>
            <div class="modal-option" data-value="1">1. Klasse (1)</div>
            <div class="modal-option" data-value="12">1. und 2. Klasse (12)</div>
            <div class="modal-option" data-value="WR">Restaurant (WR)</div>
            <div class="modal-option" data-value="FW">Familienwagen (FW)</div>
            <div class="modal-option" data-value="WL">Schlafwagen (WL)</div>
            <div class="modal-option" data-value="Lok">Lokomotive (Lok)</div>
            <div class="modal-option" data-value="-">Kein Durchgang (-)</div>
            <div class="modal-option" data-value="D">Dienstwagen (D)</div>
            <div class="modal-option" data-value="CC">CC (CC)</div>
            <div class="modal-option delete-option" id="wagen-delete">Wagen löschen</div>
            <div class="modal-close">
                <button onclick="closeModal('wagenklasse-modal')">Abbrechen</button>
            </div>
        </div>
    </div>

    <!-- Abteilung Modal -->
    <div class="modal-overlay" id="abteilung-modal">
        <div class="modal">
            <div class="modal-title">Stockwerk</div>
            <div class="modal-option" data-value="Unterdeck">↓</div>
            <div class="modal-option" data-value="Zwischendeck (EG)">—</div>
            <div class="modal-option" data-value="Oberdeck">↑</div>
            <div class="modal-close">
                <button onclick="closeModal('abteilung-modal')">Abbrechen</button>
            </div>
        </div>
    </div>

    <!-- Bemerkung fuer den aktuell ausgewaehlten Wagen -->
    <div class="modal-overlay" id="remarque-modal">
        <div class="modal remarque-modal">
            <div class="modal-title">Hinweistext für Wagen <span id="remarque-wagen-number"></span></div>
            <textarea id="remarque-input" rows="5" maxlength="500" placeholder="Hinweis eingeben"></textarea>
            <div class="modal-close">
                <button type="button" id="remarque-cancel">Abbrechen</button>
                <button type="button" id="remarque-save">Übernehmen</button>
            </div>
        </div>
    </div>

    <script>
        const countKeys = ['A', 'B', 'WR', 'VELO', 'DOG', 'SKUUTER', 'KIWA', 'BEHIG'];
        const deckNames = ['Unterdeck', 'Zwischendeck (EG)', 'Oberdeck'];

        function createEmptyCounts() {
            return Object.fromEntries(countKeys.map(key => [key, 0]));
        }

        function createEmptyWagen() {
            return { klasse: '?', decks: {} };
        }

        let currentWagen = null;
        let wagen = {};
        let currentDeck = null;

        function ensureActiveWagen() {
            if (currentWagen === null) {
                currentWagen = 1;
                wagen[currentWagen] = createEmptyWagen();
            }
            if (!currentDeck) selectDeck('Zwischendeck (EG)');
        }

        const abteilungElement = document.getElementById('abteilung');
        const wagenSliderElement = document.querySelector('.wagen-slider');
        const wagenTrackElement = document.getElementById('wagen-track');
        const fahrtnummerElement = document.getElementById('fahrtnummer');
        const kategorieElement = document.getElementById('kategorie');
        const fahrtzeugtypElement = document.getElementById('fahrtzeugtyp');
        const saveButtonElement = document.getElementById('save-button');
        const viewButtonElement = document.getElementById('view-button');
        const viewPanelElement = document.getElementById('view-panel');
        const viewVersionsElement = document.getElementById('view-versions');
        const viewStatusElement = document.getElementById('view-status');
        let isViewMode = false;
        let dragStartX = 0;
        let dragStartScrollLeft = 0;
        let isDraggingWagenSlider = false;
        let isPointerDownWagenSlider = false;
        let suppressWagenClickUntil = 0;

        const kategorien = ['S', 'IR', 'R', 'RE', 'IC', 'ICE', 'EC', 'EN', 'NJ', 'SN', 'RN', 'REG', 'CC', 'FUN', 'B', 'T', 'NFT', 'NFB', 'BAT', 'WB', 'ICN', 'TGV', 'RJX', 'TER', 'CNL', 'LIX', 'LEX', 'PE', 'PEX', 'GPX', 'GEX', 'BEX', 'EV', 'SEV', 'BE', 'EST', 'ELZ', 'U', 'MAT', 'UUU', 'BUS'];

        function initializeDataInputs() {
            const categoryOptions = document.getElementById('category-options');
            kategorien.forEach(kategorie => {
                const option = document.createElement('button');
                option.className = 'category-option';
                option.type = 'button';
                option.textContent = kategorie;
                option.addEventListener('click', () => {
                    ensureActiveWagen();
                    kategorieElement.querySelector('strong').textContent = kategorie;
                    closeModal('kategorie-modal');
                });
                categoryOptions.appendChild(option);
            });

            kategorieElement.addEventListener('click', () => openModal('kategorie-modal'));

            fahrtnummerElement.addEventListener('focus', () => {
                fahrtnummerElement.classList.add('active-input');
            });

            document.querySelectorAll('.number-key').forEach(key => {
                key.addEventListener('click', () => {
                    ensureActiveWagen();
                    if (activeCountKey) {
                        countInputElement.value += key.textContent;
                        countInputElement.focus();
                        return;
                    }
                    if (document.activeElement !== fahrtnummerElement) {
                        fahrtnummerElement.focus();
                    }
                    fahrtnummerElement.value += key.textContent;
                });
            });

            document.querySelector('.keyboard .key:nth-child(4)').addEventListener('click', () => {
                if (activeCountKey) {
                    countInputElement.value = countInputElement.value.slice(0, -1);
                } else {
                    fahrtnummerElement.value = fahrtnummerElement.value.slice(0, -1);
                }
            });

            document.getElementById('vehicle-keyboard').addEventListener('click', () => {
                ensureActiveWagen();
                document.getElementById('vehicle-modal-input').value = fahrtzeugtypElement.value;
                openModal('vehicle-keyboard-modal');
            });

            const vehicleKeys = document.getElementById('vehicle-keys');
            [...'1234567890QWERTZUIOPASDFGHJKLYXCVBNM'].forEach(zeichen => {
                const key = document.createElement('button');
                key.type = 'button';
                key.className = 'vehicle-key';
                key.textContent = zeichen;
                key.addEventListener('click', () => {
                    document.getElementById('vehicle-modal-input').value += zeichen;
                });
                vehicleKeys.appendChild(key);
            });

            const backspace = document.createElement('button');
            backspace.type = 'button';
            backspace.className = 'vehicle-key wide';
            backspace.textContent = '←';
            backspace.addEventListener('click', () => {
                const input = document.getElementById('vehicle-modal-input');
                input.value = input.value.slice(0, -1);
            });
            vehicleKeys.appendChild(backspace);

            document.getElementById('vehicle-keyboard-done').addEventListener('click', () => {
                fahrtzeugtypElement.value = document.getElementById('vehicle-modal-input').value;
                closeModal('vehicle-keyboard-modal');
            });

            fahrtzeugtypElement.addEventListener('input', ensureActiveWagen);

            document.querySelectorAll('.station-field input').forEach(input => {
                input.addEventListener('input', () => {
                    ensureActiveWagen();
                    updateStationSuggestions(input);
                });
                input.addEventListener('focus', () => {
                    ensureActiveWagen();
                    updateStationSuggestions(input);
                });
            });
        }

        function updateStationSuggestions(input) {
            const query = input.value.trim().toUpperCase();
            const list = document.getElementById(input.id === 'in-station' ? 'in-suggestions' : 'out-suggestions');
            list.replaceChildren();
            if (!query || !window.didokMapping) {
                list.classList.remove('visible');
                return;
            }

            const shortDidokId = /^\d+$/.test(query) ? '850' + query : '';
                const suggestions = Object.entries(window.abbrevMap || {})
                    .flatMap(([id, entries]) => entries.map(entry => ({
                        id: String(id).toUpperCase(),
                        name: String(entry.name),
                        country: entry.country
                    })))
                .filter(entry => entry.id.includes(query) || entry.name.toUpperCase().includes(query))
                .sort((a, b) => {
                    const aShortId = a.id === shortDidokId;
                    const bShortId = b.id === shortDidokId;
                    const aExact = a.id === query || a.name.toUpperCase() === query;
                    const bExact = b.id === query || b.name.toUpperCase() === query;
                    if (aShortId !== bShortId) return aShortId ? -1 : 1;
                    if (aExact !== bExact) return aExact ? -1 : 1;
                    return a.name.localeCompare(b.name);
                })
                .filter((entry, index, entries) => entries.findIndex(item =>
                    item.name === entry.name && item.id === entry.id && item.country === entry.country
                ) === index);

            suggestions.slice(0, 12).forEach(suggestion => {
                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'station-suggestion';
                option.innerHTML = '<span>' + suggestion.name + '</span><strong>' + suggestion.id + '</strong>';
                option.addEventListener('click', () => {
                    input.value = suggestion.name;
                    list.classList.remove('visible');
                });
                list.appendChild(option);
            });
            list.classList.toggle('visible', suggestions.length > 0);
        }

        const countValues = createEmptyCounts();
        let activeCountKey = null;
        const gridSectionElement = document.querySelector('.grid-section');
        const countPanelElement = document.getElementById('count-panel');
        const countInputElement = document.getElementById('count-input');
        const countPanelTitleElement = document.getElementById('count-panel-title');
        const countPanelValueElement = document.getElementById('count-panel-value');

        function renderCountValues() {
            document.querySelectorAll('[data-count-key]').forEach(button => {
                const key = button.dataset.countKey;
                button.querySelector('strong').textContent = countValues[key];
                button.querySelector('.deck-counts').textContent = deckNames.map(deckName => {
                    const value = wagen[currentWagen]?.decks?.[deckName]?.counts?.[key] || 0;
                    return `${deckName.slice(0, 1)}:${value}`;
                }).join(' ');
            });
        }

        function syncCountValuesToWagen() {
            if (currentWagen === null || !wagen[currentWagen] || !currentDeck) return;
            wagen[currentWagen].decks[currentDeck] = { counts: { ...countValues } };
        }

        function loadCountValuesForWagen() {
            if (currentWagen === null || !wagen[currentWagen] || !currentDeck) {
                Object.assign(countValues, createEmptyCounts());
                renderCountValues();
                return;
            }
            Object.assign(countValues, createEmptyCounts(), wagen[currentWagen].decks[currentDeck]?.counts || {});
            renderCountValues();
        }

        function openCountPanel(key) {
            ensureActiveWagen();
            activeCountKey = key;
            countPanelTitleElement.textContent = key;
            countPanelValueElement.textContent = countValues[key];
            countInputElement.value = '';
            countPanelElement.hidden = false;
            gridSectionElement.classList.add('count-mode');
            countInputElement.focus();
        }

        function closeCountPanel() {
            const pendingAmount = Number.parseInt(countInputElement.value, 10);
            if (activeCountKey && Number.isFinite(pendingAmount) && pendingAmount >= 0) {
                countValues[activeCountKey] += pendingAmount;
                syncCountValuesToWagen();
                renderCountValues();
            }
            activeCountKey = null;
            countInputElement.value = '';
            countPanelElement.hidden = true;
            gridSectionElement.classList.remove('count-mode');
        }

        function applyCount(operation) {
            const amount = Number.parseInt(countInputElement.value, 10);
            if (!activeCountKey || !Number.isFinite(amount) || amount < 0) return;
            countValues[activeCountKey] = operation === 'add'
                ? countValues[activeCountKey] + amount
                : Math.max(0, countValues[activeCountKey] - amount);
            syncCountValuesToWagen();
            countPanelValueElement.textContent = countValues[activeCountKey];
            countInputElement.value = '';
            renderCountValues();
            countInputElement.focus();
        }

        document.querySelectorAll('[data-count-key]').forEach(button => {
            button.addEventListener('click', () => openCountPanel(button.dataset.countKey));
        });

        document.querySelectorAll('[data-count-operation]').forEach(button => {
            button.addEventListener('click', () => applyCount(button.dataset.countOperation));
        });

        document.getElementById('count-reset').addEventListener('click', () => {
            if (!activeCountKey) return;
            countValues[activeCountKey] = 0;
            syncCountValuesToWagen();
            countPanelValueElement.textContent = '0';
            renderCountValues();
        });

        document.getElementById('count-close').addEventListener('click', closeCountPanel);
        document.getElementById('count-next').addEventListener('click', closeCountPanel);
        loadCountValuesForWagen();
        renderCountValues();

        function updateDisplay() {
            const w = currentWagen === null ? null : wagen[currentWagen];
            const deckLabels = {
                'Unterdeck': '↓',
                'Zwischendeck (EG)': '—',
                'Oberdeck': '↑'
            };
            abteilungElement.textContent = currentDeck ? deckLabels[currentDeck] : (w ? 'Stockwerk' : '');
            renderWagenButtons();
        }

        function selectDeck(deckName) {
            if (currentWagen === null || !wagen[currentWagen]) return;
            syncCountValuesToWagen();
            if (!wagen[currentWagen].decks) wagen[currentWagen].decks = {};
            if (!wagen[currentWagen].decks[deckName]) {
                wagen[currentWagen].decks[deckName] = { counts: createEmptyCounts() };
            }
            currentDeck = deckName;
            loadCountValuesForWagen();
            updateDisplay();
        }

        function renderWagenButtons() {
            wagenTrackElement.replaceChildren();

            Object.keys(wagen).map(Number).sort((a, b) => a - b).forEach(wagenNummer => {
                const wagenButton = document.createElement('button');
                const klasse = wagen[wagenNummer].klasse || '?';
                wagenButton.className = 'wagen-button ' + getKlasseClass(klasse);
                wagenButton.textContent = klasse;
                wagenButton.dataset.wagen = wagenNummer;
                wagenButton.setAttribute('aria-label', 'Wagen ' + wagenNummer + ', Klasse ' + klasse);

                if (wagenNummer === currentWagen) {
                    wagenButton.classList.add('selected');
                }

                wagenButton.addEventListener('click', () => {
                    if (Date.now() < suppressWagenClickUntil) {
                        return;
                    }
                    if (wagenNummer === currentWagen) {
                        openModal('wagenklasse-modal');
                        return;
                    }
                    syncCountValuesToWagen();
                    currentWagen = wagenNummer;
                    const deckNames = Object.keys(wagen[wagenNummer].decks || {});
                    currentDeck = deckNames[0] || null;
                    loadCountValuesForWagen();
                    updateDisplay();
                });
                wagenTrackElement.appendChild(wagenButton);
            });

            const selectedButton = wagenTrackElement.querySelector('.selected');
            selectedButton?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }

        wagenSliderElement.addEventListener('pointerdown', event => {
            if (!event.isPrimary) return;
            dragStartX = event.clientX;
            dragStartScrollLeft = wagenSliderElement.scrollLeft;
            isDraggingWagenSlider = false;
            isPointerDownWagenSlider = true;
        });

        wagenSliderElement.addEventListener('pointermove', event => {
            if (!isPointerDownWagenSlider || !event.isPrimary) return;

            const distance = event.clientX - dragStartX;
            if (Math.abs(distance) > 4) {
                isDraggingWagenSlider = true;
                wagenSliderElement.scrollLeft = dragStartScrollLeft - distance;
                event.preventDefault();
            }
        });

        wagenSliderElement.addEventListener('pointerup', event => {
            if (isDraggingWagenSlider) {
                suppressWagenClickUntil = Date.now() + 300;
            }
            isPointerDownWagenSlider = false;
            isDraggingWagenSlider = false;
        });

        wagenSliderElement.addEventListener('pointercancel', event => {
            isPointerDownWagenSlider = false;
            suppressWagenClickUntil = 0;
            isDraggingWagenSlider = false;
        });

        function getKlasseClass(klasse) {
            const classNames = {
                '2': 'klasse-2',
                '1': 'klasse-1',
                '12': 'klasse-12',
                'WR': 'klasse-wr',
                'FW': 'klasse-fw',
                'WL': 'klasse-wl',
                'Lok': 'klasse-lok',
                '-': 'klasse-nogangway',
                'D': 'klasse-d',
                'CC': 'klasse-cc'
            };
            return classNames[klasse] || 'klasse-unbekannt';
        }

        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        document.getElementById('wagen-prev').addEventListener('click', () => {
            if (currentWagen === null) return;
            const wagenNummern = Object.keys(wagen).map(Number).sort((a, b) => a - b);
            const aktuellerIndex = wagenNummern.indexOf(currentWagen);
            if (aktuellerIndex > 0) {
                syncCountValuesToWagen();
                currentWagen = wagenNummern[aktuellerIndex - 1];
                const deckNames = Object.keys(wagen[currentWagen].decks || {});
                currentDeck = deckNames[0] || null;
                loadCountValuesForWagen();
                updateDisplay();
            }
        });

        document.getElementById('wagen-next').addEventListener('click', () => {
            const nextWagen = currentWagen === null ? 1 : currentWagen + 1;
            if (!wagen[nextWagen]) {
                wagen[nextWagen] = createEmptyWagen();
            }
            syncCountValuesToWagen();
            currentWagen = nextWagen;
            currentDeck = Object.keys(wagen[currentWagen].decks || {})[0] || null;
            loadCountValuesForWagen();
            updateDisplay();
            openModal('wagenklasse-modal');
        });

        abteilungElement.addEventListener('click', () => {
            if (currentWagen === null) return;
            openModal('abteilung-modal');
        });

        const remarqueInputElement = document.getElementById('remarque-input');
        const remarqueWagenNumberElement = document.getElementById('remarque-wagen-number');

        document.getElementById('remarque-button').addEventListener('click', () => {
            if (currentWagen === null) return;
            remarqueWagenNumberElement.textContent = currentWagen;
            remarqueInputElement.value = wagen[currentWagen].remarque || '';
            openModal('remarque-modal');
            remarqueInputElement.focus();
        });

        document.getElementById('remarque-save').addEventListener('click', () => {
            if (currentWagen === null) return;
            wagen[currentWagen].remarque = remarqueInputElement.value.trim();
            closeModal('remarque-modal');
        });

        document.getElementById('remarque-cancel').addEventListener('click', () => {
            closeModal('remarque-modal');
        });

        function createExportPayload() {
            syncCountValuesToWagen();
            const stationInputs = {
                from: document.getElementById('in-station').value.trim(),
                to: document.getElementById('out-station').value.trim()
            };

            return {
                tripNumber: fahrtnummerElement.value,
                category: kategorieElement.querySelector('strong').textContent,
                vehicleType: fahrtzeugtypElement.value,
                stations: stationInputs,
                counts: { ...countValues },
                currentWagen,
                wagen: Object.fromEntries(Object.entries(wagen).map(([number, data]) => [number, {
                    ...data,
                    decks: Object.fromEntries(Object.entries(data.decks || {}).map(([deckName, deckData]) => [deckName, {
                        counts: { ...(deckData.counts || createEmptyCounts()) }
                    }]))
                }]))
            };
        }

        saveButtonElement.addEventListener('click', async () => {
            if (!fahrtnummerElement.value.trim()) {
                alert('Bitte zuerst eine Fahrtnummer eingeben.');
                fahrtnummerElement.focus();
                return;
            }
            saveButtonElement.disabled = true;
            try {
                const response = await fetch('fq_save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(createExportPayload())
                });
                const responseText = await response.text();
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    throw new Error(`Ungültige Serverantwort (${response.status}).`);
                }
                if (!response.ok || !result.ok) throw new Error(result.error || 'Speichern fehlgeschlagen.');
                saveButtonElement.title = `Gespeichert: Version ${result.version}`;
                alert(`Gespeichert als Version ${result.version}`);
            } catch (error) {
                console.error(error);
                alert('Die Daten konnten nicht gespeichert werden.');
            } finally {
                saveButtonElement.disabled = false;
            }
        });

        function renderSavedVersions(versions) {
            viewVersionsElement.replaceChildren();
            if (!versions.length) {
                viewStatusElement.textContent = 'Keine gespeicherten Züge';
                return;
            }

            viewStatusElement.textContent = `${versions.length} Version${versions.length === 1 ? '' : 'en'}`;
            versions.forEach(version => {
                const payload = version.payload || {};
                const versionElement = document.createElement('article');
                versionElement.className = 'view-version';

                const heading = document.createElement('div');
                heading.className = 'view-version-heading';
                heading.textContent = `Version ${version.version} · ${version.createdAt || ''}`;
                versionElement.appendChild(heading);

                const summary = document.createElement('div');
                summary.className = 'view-summary';
                summary.textContent = `${payload.tripNumber || '-'} · ${payload.category || '-'} · ${payload.vehicleType || '-'} · ${payload.stations?.from || '-'} → ${payload.stations?.to || '-'}`;
                versionElement.appendChild(summary);

                const wagonList = document.createElement('div');
                wagonList.className = 'view-wagons';
                Object.entries(payload.wagen || {}).forEach(([number, wagonData]) => {
                    const wagonElement = document.createElement('section');
                    wagonElement.className = 'view-wagon';

                    const wagonHeading = document.createElement('strong');
                    wagonHeading.textContent = `Wagen ${number} · Klasse ${wagonData.klasse || '-'}`;
                    wagonElement.appendChild(wagonHeading);

                    const details = document.createElement('div');
                    const deckEntries = Object.entries(wagonData.decks || {});
                    const legacyCounts = wagonData.counts || payload.counts || {};
                    if (deckEntries.length) {
                        deckEntries.forEach(([deckName, deckData]) => {
                            const deckElement = document.createElement('div');
                            const countText = Object.entries(deckData.counts || {})
                                .filter(([, value]) => Number(value) !== 0)
                                .map(([key, value]) => `${key}: ${value}`)
                                .join(', ') || 'Keine Auslastungsdaten';
                            deckElement.textContent = `${deckName}: ${countText}`;
                            details.appendChild(deckElement);
                        });
                    } else {
                        const countText = Object.entries(legacyCounts)
                            .filter(([, value]) => Number(value) !== 0)
                            .map(([key, value]) => `${key}: ${value}`)
                            .join(', ') || 'Keine Auslastungsdaten';
                        details.textContent = `${countText} · Abteilung: ${wagonData.abteilung || '-'}`;
                    }
                    if (wagonData.remarque) {
                        const remarqueElement = document.createElement('div');
                        remarqueElement.textContent = `Remarque: ${wagonData.remarque}`;
                        details.appendChild(remarqueElement);
                    }
                    wagonElement.appendChild(details);
                    wagonList.appendChild(wagonElement);
                });
                versionElement.appendChild(wagonList);
                viewVersionsElement.appendChild(versionElement);
            });
        }

        async function loadSavedVersions() {
            viewStatusElement.textContent = 'Lade Daten ...';
            try {
                const response = await fetch('fq_data.php', { cache: 'no-store' });
                const result = await response.json();
                if (!response.ok || !result.ok) throw new Error(result.error || 'Laden fehlgeschlagen.');
                renderSavedVersions(result.versions || []);
            } catch (error) {
                console.error(error);
                viewStatusElement.textContent = 'Daten konnten nicht geladen werden';
                viewVersionsElement.replaceChildren();
            }
        }

        viewButtonElement.addEventListener('click', () => {
            isViewMode = !isViewMode;
            document.querySelector('.panel-container').classList.toggle('view-mode', isViewMode);
            viewPanelElement.hidden = !isViewMode;
            viewButtonElement.textContent = isViewMode ? 'Eingabe' : 'View';
            if (isViewMode) loadSavedVersions();
        });

        // Wagenklasse Modal Options
        document.querySelectorAll('#wagenklasse-modal .modal-option').forEach(opt => {
            if (opt.id === 'wagen-delete') return;
            opt.addEventListener('click', () => {
                if (currentWagen === null) return;
                wagen[currentWagen].klasse = opt.dataset.value;
                updateDisplay();
                closeModal('wagenklasse-modal');
            });
        });

        document.getElementById('wagen-delete').addEventListener('click', () => {
            if (currentWagen === null) return;
            const wagenNummern = Object.keys(wagen).map(Number).sort((a, b) => a - b);
            if (wagenNummern.length === 1) return;

            delete wagen[currentWagen];
            const nextWagen = wagenNummern.find(wagenNummer => wagenNummer > currentWagen);
            currentWagen = nextWagen || wagenNummern[wagenNummern.length - 2];
            const deckNames = Object.keys(wagen[currentWagen].decks || {});
            currentDeck = deckNames[0] || null;
            loadCountValuesForWagen();
            updateDisplay();
            closeModal('wagenklasse-modal');
        });

        // Abteilung Modal Options
        document.querySelectorAll('#abteilung-modal .modal-option').forEach(opt => {
            opt.addEventListener('click', () => {
                if (currentWagen === null) return;
                selectDeck(opt.dataset.value);
                updateDisplay();
                closeModal('abteilung-modal');
            });
        });

        // Close on overlay click
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal(modal.id);
                }
            });
        });

        initializeDataInputs();
        updateDisplay();
    </script>
</body>
</html>