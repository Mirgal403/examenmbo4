<?php
session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}
require 'connect.php';

// Get all extras
$extras_query = "SELECT * FROM optie ORDER BY optie_id";
$extras_result = $conn->query($extras_query);
$extras = [];
while ($row = $extras_result->fetch_assoc()) {
    $extras[] = $row;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bowling Brooklyn - Reservering voor Klant</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #ffffff;
            color: #333;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            border-bottom: 1px solid #eee;
        }
        
        .header-left, .header-right { display: flex; align-items: center; }
        .logo { font-size: 24px; font-weight: 900; color: #999; margin-right: 40px; }
        .nav-links a { text-decoration: none; color: #333; font-weight: 600; margin: 0 15px; font-size: 14px; }
        .user-greeting { font-weight: 600; font-size: 14px; margin-right: 20px; }
        .btn-logout { background-color: #1a73e8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 2px; font-weight: 600; font-size: 14px; }

        .container { max-width: 1100px; margin: 0 auto; padding: 20px; }

        h2 { font-size: 14px; font-weight: 700; text-transform: uppercase; margin-bottom: 10px; margin-top: 30px; color: #222; }
        .divider { border: 0; border-top: 1px solid #777; margin-bottom: 20px; }

        .customer-form { background: #f5f5f5; padding: 20px; border-radius: 4px; margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; margin-bottom: 5px; font-size: 14px; }
        .form-group input { padding: 10px; font-size: 14px; border: 1px solid #ccc; border-radius: 2px; }

        .date-picker-wrapper { margin-bottom: 20px; }
        .date-picker-wrapper label { display: block; font-weight: 600; margin-bottom: 8px; }
        .date-picker-wrapper input[type="date"] { padding: 10px; font-size: 16px; border: 2px solid #333; width: 250px; }

        .duration-selector { margin-bottom: 20px; }
        .duration-selector label { display: inline-block; margin-right: 20px; font-weight: 600; cursor: pointer; }
        .duration-selector input[type="radio"] { margin-right: 5px; }

        .cards-wrapper { display: flex; gap: 20px; flex-wrap: wrap; }
        
        .card { border: 2px solid #333; padding: 20px; width: 250px; text-align: center; background: white; position: relative; }
        .card.unavailable { opacity: 0.5; background: #f0f0f0; }
        .card.priority { border-color: #28a745; background: #f0fff0; }
        
        .card-time { font-weight: 700; font-size: 16px; margin-bottom: 5px; }
        .card-price { font-size: 14px; margin-bottom: 10px; }
        .card-lane { font-size: 12px; color: #666; margin-bottom: 10px; }
        
        .btn-select { background: none; border: none; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; }
        .btn-select:disabled { cursor: not-allowed; }
        .btn-select-icon { background-color: #1a73e8; color: white; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; margin-left: 5px; font-size: 12px; line-height: 1; transition: background-color 0.2s; }
        .btn-select-icon.selected { background-color: #28a745; }

        .badge-magic { background: purple; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; display: inline-block; margin-top: 5px; }
        .badge-bumpers { background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; display: inline-block; }

        .gray-box { background-color: #dcdcdc; padding: 20px 40px; border-radius: 2px; }
        .composition-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; }
        .label-group { display: flex; flex-direction: column; }
        .label-main { font-weight: 700; font-size: 14px; }
        .label-sub { font-size: 10px; font-weight: 700; color: #555; margin-top: 2px; }
        .counter-controls { font-weight: 700; font-size: 16px; display: flex; gap: 15px; align-items: center; }
        .counter-btn { cursor: pointer; user-select: none; padding: 5px 10px; background: #eee; border-radius: 3px; }
        .counter-btn:hover { background: #ddd; }

        .overview-table { width: 100%; border-collapse: collapse; }
        .overview-table td { padding: 10px 0; border-bottom: 1px solid #ccc; }
        .overview-table tr:last-child td { border-bottom: none; }
        .ov-label { font-weight: 600; font-size: 14px; }
        .ov-value { text-align: right; font-size: 14px; }
        .ov-price { text-align: right; font-weight: 700; font-size: 14px; width: 80px;}

        .total-row { margin-top: 15px; padding-top: 15px; border-top: 2px solid #333; display: flex; justify-content: space-between; font-weight: 900; font-size: 18px; }
        .confirm-btn-wrapper { margin-top: 20px; text-align: right; }
        .btn-confirm { background-color: #1a73e8; color: white; padding: 12px 30px; border: none; font-size: 16px; font-weight: 600; cursor: pointer; }
        .btn-confirm:hover { background-color: #1558b0; }
        .btn-confirm:disabled { background-color: #ccc; cursor: not-allowed; }

        .alert { padding: 12px 20px; margin-bottom: 20px; border-radius: 4px; font-size: 14px; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        .loading { text-align: center; padding: 40px; color: #666; }
        .hidden { display: none; }
    </style>
</head>
<body>

    <header>
        <div class="header-left">
            <div class="logo">Bowling Brooklyn</div>
            <nav class="nav-links">
                <a href="medewerkers_dashboard.php">Dashboard</a>
                <a href="reserverings_pagina_medewerker.php">Reservatie</a>
            </nav>
        </div>
        
        <div class="header-right">
            <span class="user-greeting">Welkom, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <a href="logout.php" class="btn-logout">Uitloggen</a>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php 
                echo htmlspecialchars($_SESSION['error']); 
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <form id="reservationForm" action="process_reservation.php" method="POST">
            <input type="hidden" name="is_medewerker_booking" value="1">

            <!-- Customer Selection -->
            <h2>Selecteer Klant</h2>
            <hr class="divider">
            <div class="customer-form">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="customer_select">Zoek klant op naam of e-mail:</label>
                    <input 
                        type="text" 
                        id="customer_search" 
                        placeholder="Typ naam of e-mail..."
                        autocomplete="off"
                        style="width: 100%; padding: 10px; font-size: 14px; border: 1px solid #ccc; border-radius: 2px;">
                </div>
                
                <div id="customer_results" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; display: none;">
                    <!-- Search results will appear here -->
                </div>

                <div id="selected_customer_info" style="margin-top: 15px; padding: 15px; background: #e8f5e9; border-radius: 4px; display: none;">
                    <strong>Geselecteerde klant:</strong>
                    <div style="margin-top: 8px;">
                        <div id="selected_customer_name" style="font-size: 16px; font-weight: 600;"></div>
                        <div id="selected_customer_email" style="font-size: 14px; color: #666;"></div>
                        <div id="selected_customer_telefoon" style="font-size: 14px; color: #666;"></div>
                    </div>
                    <button type="button" onclick="clearCustomerSelection()" style="margin-top: 10px; padding: 6px 12px; background: #f44336; color: white; border: none; border-radius: 2px; cursor: pointer;">
                        Andere klant kiezen
                    </button>
                </div>

                <input type="hidden" name="selected_klant_id" id="selected_klant_id">
            </div>

            <!-- Step 1: Date & Duration -->
            <h2>Stap 1 van 4: KIES DATUM EN DUUR</h2>
            <hr class="divider">
            
            <div class="date-picker-wrapper">
                <label for="datum">Selecteer datum:</label>
                <input type="date" id="datum" name="datum" required min="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="duration-selector">
                <label><input type="radio" name="duur_uren" value="1" checked> 1 uur</label>
                <label><input type="radio" name="duur_uren" value="2"> 2 uur</label>
            </div>

            <!-- Step 2: Composition -->
            <h2>Stap 2 van 4: Samenstelling</h2>
            <hr class="divider">
            <div class="gray-box">
                <div class="composition-row">
                    <div class="label-group">
                        <span class="label-main">Aantal volwassenen (18+)</span>
                        <span class="label-sub">Max 8 alleen volwassenen, max 6 bij gemengde groep</span>
                    </div>
                    <div class="counter-controls">
                        <span class="counter-btn" onclick="changeCount('adults', -1)">-</span>
                        <span id="adults-count">2</span>
                        <span class="counter-btn" onclick="changeCount('adults', 1)">+</span>
                    </div>
                </div>
                <div class="composition-row">
                    <div class="label-group"><span class="label-main">Aantal kinderen</span></div>
                    <div class="counter-controls">
                        <span class="counter-btn" onclick="changeCount('children', -1)">-</span>
                        <span id="children-count">0</span>
                        <span class="counter-btn" onclick="changeCount('children', 1)">+</span>
                    </div>
                </div>
            </div>

            <input type="hidden" name="aantal_volwassenen" id="aantal_volwassenen" value="2">
            <input type="hidden" name="aantal_kinderen" id="aantal_kinderen" value="0">

            <!-- Step 3: Time & Lane Selection -->
            <h2>Stap 3 van 4: KIES JE TIJD EN BAAN</h2>
            <hr class="divider">
            
            <div id="loading" class="loading hidden">Beschikbaarheid ophalen...</div>
            <div id="no-slots" class="alert alert-info hidden">Selecteer eerst een datum.</div>
            <div id="time-slots-container" class="cards-wrapper"></div>

            <input type="hidden" name="starttijd" id="starttijd">
            <input type="hidden" name="eindtijd" id="eindtijd">
            <input type="hidden" name="baan_id" id="baan_id">
            <input type="hidden" name="is_magic_bowlen" id="is_magic_bowlen" value="0">

            <!-- Step 4: Extras -->
            <h2>Stap 4 van 4: Opties & Extra's</h2>
            <hr class="divider">
            <div class="cards-wrapper">
                <?php foreach ($extras as $extra): ?>
                <div class="card">
                    <div class="card-time"><?php echo htmlspecialchars($extra['naam']); ?></div>
                    <?php if ($extra['beschrijving']): ?>
                    <div class="card-lane" style="margin-bottom: 10px;"><?php echo htmlspecialchars($extra['beschrijving']); ?></div>
                    <?php endif; ?>
                    <div class="card-price">€ <?php echo number_format($extra['meerprijs'], 2, ',', '.'); ?></div>
                    <button type="button" class="btn-select" onclick="toggleExtra(this, <?php echo $extra['optie_id']; ?>)">
                        Kies <span class="btn-select-icon">-</span>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Overview -->
            <h2>Overzicht en Bevestigen</h2>
            <hr class="divider">
            <div class="gray-box">
                <table class="overview-table">
                    <tr>
                        <td class="ov-label">Klant</td>
                        <td class="ov-value" id="ov-customer">-</td>
                        <td class="ov-price">-</td>
                    </tr>
                    <tr>
                        <td class="ov-label">Datum & Tijd</td>
                        <td class="ov-value" id="ov-datetime">-</td>
                        <td class="ov-price" id="ov-datetime-price">-</td>
                    </tr>
                    <tr>
                        <td class="ov-label">Baan</td>
                        <td class="ov-value" id="ov-lane">-</td>
                        <td class="ov-price">-</td>
                    </tr>
                    <tr>
                        <td class="ov-label">Gasten</td>
                        <td class="ov-value" id="ov-guests">-</td>
                        <td class="ov-price">-</td>
                    </tr>
                    <tr id="ov-extras-row" class="hidden">
                        <td class="ov-label">Extra's</td>
                        <td class="ov-value" id="ov-extras">-</td>
                        <td class="ov-price" id="ov-extras-price">-</td>
                    </tr>
                </table>
                <div class="total-row">
                    <span>Totaal</span>
                    <span id="ov-total">€ 0,00</span>
                </div>
                <div class="confirm-btn-wrapper">
                    <button type="submit" class="btn-confirm" id="confirmBtn" disabled>Bevestig Reservering</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        let adults = 2;
        let children = 0;
        let selectedSlot = null;
        let selectedExtras = [];
        let availableSlots = [];
        let extrasData = <?php echo json_encode($extras); ?>;
        let selectedCustomer = null;

        // Customer search functionality
        let searchTimeout;
        document.getElementById('customer_search').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                document.getElementById('customer_results').style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                searchCustomers(query);
            }, 300);
        });

        function searchCustomers(query) {
            fetch(`search_customers.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    const resultsDiv = document.getElementById('customer_results');
                    
                    if (data.customers && data.customers.length > 0) {
                        let html = '<div style="padding: 5px;">';
                        data.customers.forEach(customer => {
                            html += `
                                <div onclick="selectCustomer(${customer.klant_id}, '${customer.voornaam}', '${customer.achternaam}', '${customer.email}', '${customer.telefoon || ''}')" 
                                     style="padding: 12px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s;"
                                     onmouseover="this.style.background='#f5f5f5'"
                                     onmouseout="this.style.background='white'">
                                    <div style="font-weight: 600;">${customer.voornaam} ${customer.achternaam}</div>
                                    <div style="font-size: 13px; color: #666;">${customer.email}</div>
                                    ${customer.telefoon ? `<div style="font-size: 13px; color: #666;">📞 ${customer.telefoon}</div>` : ''}
                                </div>
                            `;
                        });
                        html += '</div>';
                        resultsDiv.innerHTML = html;
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.innerHTML = '<div style="padding: 15px; text-align: center; color: #666;">Geen klanten gevonden</div>';
                        resultsDiv.style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error('Search error:', err);
                });
        }

        function selectCustomer(id, voornaam, achternaam, email, telefoon) {
            selectedCustomer = { id, voornaam, achternaam, email, telefoon };
            
            // Update hidden input
            document.getElementById('selected_klant_id').value = id;
            
            // Show selected customer info
            document.getElementById('selected_customer_name').textContent = `${voornaam} ${achternaam}`;
            document.getElementById('selected_customer_email').textContent = `📧 ${email}`;
            document.getElementById('selected_customer_telefoon').textContent = telefoon ? `📞 ${telefoon}` : '';
            
            // Hide search results and search box
            document.getElementById('customer_results').style.display = 'none';
            document.getElementById('customer_search').value = '';
            document.getElementById('customer_search').style.display = 'none';
            
            // Show selected info
            document.getElementById('selected_customer_info').style.display = 'block';
            
            updateOverview();
        }

        function clearCustomerSelection() {
            selectedCustomer = null;
            document.getElementById('selected_klant_id').value = '';
            document.getElementById('selected_customer_info').style.display = 'none';
            document.getElementById('customer_search').style.display = 'block';
            document.getElementById('customer_search').focus();
            updateOverview();
        }

        function changeCount(type, delta) {
            if (type === 'adults') {
                adults = Math.max(0, Math.min(8, adults + delta));
                document.getElementById('adults-count').textContent = adults;
                document.getElementById('aantal_volwassenen').value = adults;
            } else {
                children = Math.max(0, Math.min(8, children + delta));
                document.getElementById('children-count').textContent = children;
                document.getElementById('aantal_kinderen').value = children;
            }

            // Validatie: Max 8 volwassenen alleen, OF max 6 totaal bij gemengde groep
            let isValid = true;
            let errorMessage = '';
            
            if (children > 0) {
                // Gemengde groep: max 6 totaal
                if (adults + children > 6) {
                    isValid = false;
                    errorMessage = 'Maximum 6 personen per baan bij groepen met kinderen!';
                }
            } else {
                // Alleen volwassenen: max 8
                if (adults > 8) {
                    isValid = false;
                    errorMessage = 'Maximum 8 volwassenen per baan!';
                }
            }
            
            if (!isValid) {
                // Reset to previous valid value
                if (type === 'adults') {
                    adults = Math.max(0, adults - delta);
                    document.getElementById('adults-count').textContent = adults;
                    document.getElementById('aantal_volwassenen').value = adults;
                } else {
                    children = Math.max(0, children - delta);
                    document.getElementById('children-count').textContent = children;
                    document.getElementById('aantal_kinderen').value = children;
                }
                alert(errorMessage);
            }

            updateAvailability();
            updateOverview();
        }

        function updateAvailability() {
            const datum = document.getElementById('datum').value;
            const duur = parseInt(document.querySelector('input[name="duur_uren"]:checked').value);
            
            if (!datum) {
                document.getElementById('no-slots').classList.remove('hidden');
                document.getElementById('time-slots-container').innerHTML = '';
                return;
            }

            document.getElementById('no-slots').classList.add('hidden');
            document.getElementById('loading').classList.remove('hidden');
            document.getElementById('time-slots-container').innerHTML = '';

            const hasChildren = children > 0 ? 1 : 0;

            fetch(`get_available_slots.php?date=${datum}&duration=${duur}&hasChildren=${hasChildren}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('loading').classList.add('hidden');
                    
                    if (data.success) {
                        availableSlots = data.timeSlots;
                        renderTimeSlots();
                    }
                })
                .catch(err => {
                    document.getElementById('loading').classList.add('hidden');
                    alert('Fout bij ophalen beschikbaarheid');
                });
        }

        function renderTimeSlots() {
            const container = document.getElementById('time-slots-container');
            container.innerHTML = '';

            if (availableSlots.length === 0) {
                container.innerHTML = '<div class="alert alert-info">Geen beschikbare tijden.</div>';
                return;
            }

            const grouped = {};
            availableSlots.forEach(slot => {
                const key = `${slot.start}-${slot.end}`;
                if (!grouped[key]) grouped[key] = [];
                grouped[key].push(slot);
            });

            Object.keys(grouped).forEach(timeKey => {
                const slots = grouped[timeKey];
                
                slots.forEach(slot => {
                    if (!slot.available) return;

                    const card = document.createElement('div');
                    card.className = 'card';
                    if (slot.is_priority) card.classList.add('priority');

                    let html = `
                        <div class="card-time">${slot.start} - ${slot.end}</div>
                        <div class="card-lane">Baan ${slot.baan_nummer} ${slot.is_kinderbaan ? '<span class="badge-bumpers">👶 Hekjes</span>' : ''}</div>
                        <div class="card-price">€ ${slot.price.toFixed(2).replace('.', ',')}</div>
                        ${slot.is_magic_bowling ? '<div class="badge-magic">✨ Magic Bowling</div>' : ''}
                        <button type="button" class="btn-select" onclick="selectTimeSlot(${slot.baan_id}, '${slot.start}', '${slot.end}', ${slot.price}, ${slot.is_magic_bowling})">
                            Kies <span class="btn-select-icon">-</span>
                        </button>
                    `;
                    
                    card.innerHTML = html;
                    container.appendChild(card);
                });
            });
        }

        function selectTimeSlot(baanId, start, end, price, isMagic) {
            document.querySelectorAll('#time-slots-container .btn-select-icon').forEach(icon => {
                icon.classList.remove('selected');
                icon.innerHTML = '-';
            });

            event.target.classList.add('selected');
            event.target.innerHTML = '&#10003;';

            selectedSlot = { baanId, start, end, price, isMagic };
            
            document.getElementById('starttijd').value = start + ':00';
            document.getElementById('eindtijd').value = end + ':00';
            document.getElementById('baan_id').value = baanId;
            document.getElementById('is_magic_bowlen').value = isMagic ? 1 : 0;

            updateOverview();
        }

        function toggleExtra(btn, optieId) {
            const icon = btn.querySelector('.btn-select-icon');
            
            if (icon.classList.contains('selected')) {
                icon.classList.remove('selected');
                icon.innerHTML = '-';
                selectedExtras = selectedExtras.filter(id => id !== optieId);
            } else {
                icon.classList.add('selected');
                icon.innerHTML = '&#10003;';
                selectedExtras.push(optieId);
            }

            updateOverview();
        }

        function updateOverview() {
            const datum = document.getElementById('datum').value;

            // Customer info
            if (selectedCustomer) {
                document.getElementById('ov-customer').innerHTML = `${selectedCustomer.voornaam} ${selectedCustomer.achternaam} (${selectedCustomer.email})`;
            } else {
                document.getElementById('ov-customer').innerHTML = '<span style="color: #f44336;">Selecteer eerst een klant</span>';
            }
            
            // Date & Time
            if (selectedSlot && datum) {
                const formattedDate = new Date(datum).toLocaleDateString('nl-NL');
                document.getElementById('ov-datetime').innerHTML = `${formattedDate} | ${selectedSlot.start} - ${selectedSlot.end}`;
                document.getElementById('ov-datetime-price').innerHTML = `€ ${selectedSlot.price.toFixed(2).replace('.', ',')}`;
                
                document.getElementById('ov-lane').innerHTML = `Baan ${selectedSlot.baan_nummer}`;
            } else {
                document.getElementById('ov-datetime').innerHTML = '-';
                document.getElementById('ov-datetime-price').innerHTML = '-';
                document.getElementById('ov-lane').innerHTML = '-';
            }

            document.getElementById('ov-guests').innerHTML = `${adults} Volwassenen, ${children} Kinderen`;

            let extrasTotal = 0;
            let extrasNames = [];
            
            selectedExtras.forEach(id => {
                const extra = extrasData.find(e => e.optie_id == id);
                if (extra) {
                    extrasTotal += parseFloat(extra.meerprijs);
                    extrasNames.push(extra.naam);
                }
            });

            const extrasRow = document.getElementById('ov-extras-row');
            if (selectedExtras.length > 0) {
                extrasRow.classList.remove('hidden');
                document.getElementById('ov-extras').innerHTML = extrasNames.join(', ');
                document.getElementById('ov-extras-price').innerHTML = `€ ${extrasTotal.toFixed(2).replace('.', ',')}`;
            } else {
                extrasRow.classList.add('hidden');
            }

            let total = 0;
            if (selectedSlot) {
                total = selectedSlot.price + extrasTotal;
            }
            document.getElementById('ov-total').innerHTML = `€ ${total.toFixed(2).replace('.', ',')}`;

            const confirmBtn = document.getElementById('confirmBtn');
            confirmBtn.disabled = !selectedSlot || !datum || !selectedCustomer || (adults + children === 0);

            document.querySelectorAll('input[name="extras[]"]').forEach(el => el.remove());
            selectedExtras.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'extras[]';
                input.value = id;
                document.getElementById('reservationForm').appendChild(input);
            });
        }
    </script>
</body>
</html>