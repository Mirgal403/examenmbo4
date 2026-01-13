<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin']) {
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
    <title>Bowling Brooklyn - Reservering</title>
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
                <a href="klanten_dashboard.php">Dashboard</a>
                <a href="reserverings_pagina_klant.php">Reservatie</a>
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

            // Group by time
            const grouped = {};
            availableSlots.forEach(slot => {
                const key = `${slot.start}-${slot.end}`;
                if (!grouped[key]) grouped[key] = [];
                grouped[key].push(slot);
            });

            Object.keys(grouped).forEach(timeKey => {
                const slots = grouped[timeKey];
                
                slots.forEach(slot => {
                    if (!slot.available) return; // Skip unavailable

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
            // Clear previous selection
            document.querySelectorAll('#time-slots-container .btn-select-icon').forEach(icon => {
                icon.classList.remove('selected');
                icon.innerHTML = '-';
            });

            // Set new selection
            event.target.classList.add('selected');
            event.target.innerHTML = '&#10003;';

            // Find the complete slot info
            const completeSlot = availableSlots.find(s => s.baan_id === baanId && s.start === start && s.end === end);
            
            selectedSlot = { 
                baanId, 
                start, 
                end, 
                price, 
                isMagic,
                baan_nummer: completeSlot ? completeSlot.baan_nummer : '?'
            };
            
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
            
            // Date & Time
            if (selectedSlot && datum) {
                const formattedDate = new Date(datum).toLocaleDateString('nl-NL');
                document.getElementById('ov-datetime').innerHTML = `${formattedDate} | ${selectedSlot.start} - ${selectedSlot.end}`;
                document.getElementById('ov-datetime-price').innerHTML = `€ ${selectedSlot.price.toFixed(2).replace('.', ',')}`;
                
                // Display lane number (stored in selectedSlot)
                document.getElementById('ov-lane').innerHTML = `Baan ${selectedSlot.baan_nummer}`;
            } else {
                document.getElementById('ov-datetime').innerHTML = '-';
                document.getElementById('ov-datetime-price').innerHTML = '-';
                document.getElementById('ov-lane').innerHTML = '-';
            }

            // Guests
            document.getElementById('ov-guests').innerHTML = `${adults} Volwassenen, ${children} Kinderen`;

            // Extras
            let extrasTotal = 0;
            let extrasNames = [];
            
            selectedExtras.forEach(id => {
                const extra = extrasData.find(e => e.optie_id == id);
                if (extra) {
                    const extraPrice = parseFloat(extra.meerprijs);
                    extrasTotal += extraPrice;
                    extrasNames.push(extra.naam);
                    console.log('Extra:', extra.naam, 'Price:', extraPrice, 'Running total:', extrasTotal);
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

            // Total 
            let total = 0;
            if (selectedSlot) {
                const slotPrice = parseFloat(selectedSlot.price);
                total = slotPrice + extrasTotal;
                console.log('Slot price:', slotPrice, 'Extras total:', extrasTotal, 'Final total:', total);
            }
            document.getElementById('ov-total').innerHTML = `€ ${total.toFixed(2).replace('.', ',')}`;

            // Enable/disable confirm button
            const confirmBtn = document.getElementById('confirmBtn');
            confirmBtn.disabled = !selectedSlot || !datum || (adults + children === 0);

            // Update hidden extras inputs
            document.querySelectorAll('input[name="extras[]"]').forEach(el => el.remove());
            selectedExtras.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'extras[]';
                input.value = id;
                document.getElementById('reservationForm').appendChild(input);
            });
        }

        // Event listeners
        document.getElementById('datum').addEventListener('change', updateAvailability);
        document.querySelectorAll('input[name="duur_uren"]').forEach(radio => {
            radio.addEventListener('change', updateAvailability);
        });

        // Initial update
        updateOverview();
    </script>
</body>
</html>