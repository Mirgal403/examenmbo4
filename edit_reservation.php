<?php
session_start();
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? false)) {
    header("Location: login.php");
    exit();
}
require 'connect.php';
date_default_timezone_set('Europe/Amsterdam');

$reservering_id = (int)($_GET['id'] ?? 0);
if ($reservering_id <= 0) {
    $_SESSION['error'] = "Ongeldige reservering.";
    header("Location: medewerkers_dashboard.php");
    exit();
}

$q = "
SELECT 
    r.reservering_id, r.klant_id, r.medewerker_id, r.baan_id, r.tarief_id,
    r.datum, r.starttijd, r.eindtijd, r.duur_uren,
    r.aantal_volwassenen, r.aantal_kinderen, r.totaal_prijs, r.is_magic_bowlen,
    b.baan_nummer,
    k.voornaam, k.achternaam, k.email, k.telefoon
FROM reservering r
JOIN baan b ON r.baan_id = b.baan_id
JOIN klant k ON r.klant_id = k.klant_id
WHERE r.reservering_id = ?
LIMIT 1
";
$stmt = $conn->prepare($q);
$stmt->bind_param("i", $reservering_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $_SESSION['error'] = "Reservering niet gevonden.";
    header("Location: medewerkers_dashboard.php");
    exit();
}
$booking = $res->fetch_assoc();

$extras_query = "SELECT * FROM optie ORDER BY optie_id";
$extras_result = $conn->query($extras_query);
$extras = [];
while ($row = $extras_result->fetch_assoc()) $extras[] = $row;

$sel = [];
$sel_stmt = $conn->prepare("SELECT optie_id FROM reservering_optie WHERE reservering_id = ?");
$sel_stmt->bind_param("i", $reservering_id);
$sel_stmt->execute();
$sel_res = $sel_stmt->get_result();
while ($r = $sel_res->fetch_assoc()) $sel[] = (int)$r['optie_id'];

$initialDate = $booking['datum'];
$initialDuration = (int)$booking['duur_uren'];
$initialAdults = (int)$booking['aantal_volwassenen'];
$initialChildren = (int)$booking['aantal_kinderen'];
$initialStart = substr($booking['starttijd'],0,5);
$initialEnd = substr($booking['eindtijd'],0,5);
$initialLaneId = (int)$booking['baan_id'];
$initialLaneNumber = (int)$booking['baan_nummer'];
$initialMagic = (int)$booking['is_magic_bowlen'];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reservering Bewerken</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#fff; color:#333; }

    header {
      display:flex; justify-content:space-between; align-items:center;
      padding:20px 40px; border-bottom:1px solid #eee;
    }
    .header-left, .header-right { display:flex; align-items:center; }
    .logo { font-size:24px; font-weight:900; color:#999; margin-right:40px; }
    .nav-links a { text-decoration:none; color:#333; font-weight:600; margin:0 15px; font-size:14px; }
    .user-greeting { font-weight:600; font-size:14px; margin-right:20px; }
    .btn-logout { background:#1a73e8; color:#fff; padding:10px 20px; text-decoration:none; border-radius:6px; font-weight:600; font-size:14px; }

    .container { max-width:1100px; margin:0 auto; padding:20px; }
    h2 { font-size:14px; font-weight:800; text-transform:uppercase; margin:28px 0 10px; }
    .divider { border:0; border-top:1px solid #777; margin-bottom:18px; }

    .card { border:2px solid #333; padding:20px; width:260px; text-align:center; background:#fff; }
    .cards-wrapper { display:flex; gap:20px; flex-wrap:wrap; }

    .date-picker-wrapper { margin-bottom:18px; }
    .date-picker-wrapper label { display:block; font-weight:700; margin-bottom:8px; }
    .date-picker-wrapper input[type="date"] { padding:10px; font-size:16px; border:2px solid #333; width:260px; }

    .duration-selector { margin-bottom:18px; }
    .duration-selector label { display:inline-block; margin-right:18px; font-weight:700; cursor:pointer; }
    .duration-selector input { margin-right:6px; }

    .gray-box { background:#dcdcdc; padding:20px 40px; border-radius:6px; }
    .composition-row { display:flex; justify-content:space-between; align-items:center; padding:10px 0; }
    .label-group { display:flex; flex-direction:column; }
    .label-main { font-weight:800; font-size:14px; }
    .label-sub { font-size:11px; font-weight:800; color:#555; margin-top:2px; }
    .counter-controls { font-weight:800; font-size:16px; display:flex; gap:14px; align-items:center; }
    .counter-btn { cursor:pointer; user-select:none; padding:5px 10px; background:#eee; border-radius:6px; }
    .counter-btn:hover { background:#ddd; }

    .loading { text-align:center; padding:30px; color:#666; }
    .hidden { display:none; }

    .card-time { font-weight:900; font-size:16px; margin-bottom:6px; }
    .card-lane { font-size:12px; color:#666; margin-bottom:10px; }
    .card-price { font-size:14px; margin-bottom:10px; }
    .badge-magic { background:purple; color:white; padding:2px 6px; border-radius:3px; font-size:11px; display:inline-block; margin-top:5px; }
    .badge-bumpers { background:#28a745; color:white; padding:2px 6px; border-radius:3px; font-size:11px; display:inline-block; }

    .btn-select { background:none; border:none; font-weight:900; font-size:14px; cursor:pointer; display:inline-flex; align-items:center; }
    .btn-select-icon { background:#1a73e8; color:#fff; width:16px; height:16px; display:flex; align-items:center; justify-content:center; margin-left:6px; font-size:12px; border-radius:3px; }
    .btn-select-icon.selected { background:#28a745; }

    .overview-table { width:100%; border-collapse:collapse; }
    .overview-table td { padding:10px 0; border-bottom:1px solid #ccc; }
    .overview-table tr:last-child td { border-bottom:none; }
    .ov-label { font-weight:700; font-size:14px; }
    .ov-value { text-align:right; font-size:14px; }
    .ov-price { text-align:right; font-weight:900; font-size:14px; width:90px; }

    .total-row { margin-top:15px; padding-top:15px; border-top:2px solid #333; display:flex; justify-content:space-between; font-weight:1000; font-size:18px; }
    .confirm-btn-wrapper { margin-top:18px; text-align:right; display:flex; gap:10px; justify-content:flex-end; }
    .btn-confirm { background:#1a73e8; color:#fff; padding:12px 30px; border:none; font-size:16px; font-weight:800; cursor:pointer; border-radius:6px; }
    .btn-cancel { background:#6c757d; color:#fff; padding:12px 20px; text-decoration:none; border-radius:6px; font-weight:800; }

    .alert { padding:12px 20px; margin-bottom:20px; border-radius:6px; font-size:14px; }
    .alert-error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
    .alert-info { background:#d1ecf1; color:#0c5460; border:1px solid #bee5eb; }
  </style>
</head>
<body>

<header>
  <div class="header-left">
    <div class="logo">Bowling Brooklyn</div>
    <nav class="nav-links">
      <a href="medewerkers_dashboard.php">Dashboard</a>
      <a href="reserverings_pagina_medeweker.php">Reservatie</a>
    </nav>
  </div>
  <div class="header-right">
    <span class="user-greeting">Welkom, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Medewerker'); ?>!</span>
    <a href="logout.php" class="btn-logout">Uitloggen</a>
  </div>
</header>

<div class="container">
  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
  <?php endif; ?>

  <h1 style="font-size:24px; font-weight:900; margin-bottom:6px;">Reservering bewerken</h1>
  <div style="color:#666; margin-bottom:14px;">
    Reservering #<?php echo (int)$booking['reservering_id']; ?> —
    Klant: <strong><?php echo htmlspecialchars(trim(($booking['voornaam'] ?? '').' '.($booking['achternaam'] ?? '')) ?: $booking['email']); ?></strong>
  </div>

  <form id="editForm" action="update_reservation.php" method="POST">
    <input type="hidden" name="reservering_id" value="<?php echo (int)$booking['reservering_id']; ?>">

    <!-- Stap 1: Datum & duur -->
    <h2>Stap 1: Datum en duur</h2>
    <hr class="divider">

    <div class="date-picker-wrapper">
      <label for="datum">Selecteer datum:</label>
      <input type="date" id="datum" name="datum" required value="<?php echo htmlspecialchars($initialDate); ?>" min="<?php echo date('Y-m-d'); ?>">
    </div>

    <div class="duration-selector">
      <label><input type="radio" name="duur_uren" value="1" <?php echo ($initialDuration===1?'checked':''); ?>> 1 uur</label>
      <label><input type="radio" name="duur_uren" value="2" <?php echo ($initialDuration===2?'checked':''); ?>> 2 uur</label>
    </div>

    <h2>Stap 2: Aantal personen</h2>
    <hr class="divider">

    <div class="gray-box">
      <div class="composition-row">
        <div class="label-group">
          <span class="label-main">Aantal volwassenen (18+)</span>
          <span class="label-sub">Max 8 per baan</span>
        </div>
        <div class="counter-controls">
          <span class="counter-btn" onclick="changeCount('adults', -1)">-</span>
          <span id="adults-count"><?php echo (int)$initialAdults; ?></span>
          <span class="counter-btn" onclick="changeCount('adults', 1)">+</span>
        </div>
      </div>

      <div class="composition-row">
        <div class="label-group">
          <span class="label-main">Aantal kinderen</span>
        </div>
        <div class="counter-controls">
          <span class="counter-btn" onclick="changeCount('children', -1)">-</span>
          <span id="children-count"><?php echo (int)$initialChildren; ?></span>
          <span class="counter-btn" onclick="changeCount('children', 1)">+</span>
        </div>
      </div>
    </div>

    <input type="hidden" name="aantal_volwassenen" id="aantal_volwassenen" value="<?php echo (int)$initialAdults; ?>">
    <input type="hidden" name="aantal_kinderen" id="aantal_kinderen" value="<?php echo (int)$initialChildren; ?>">

    <h2>Stap 3: Kies tijd en baan</h2>
    <hr class="divider">

    <div class="alert alert-info">
      Huidige keuze: <strong>Baan <?php echo $initialLaneNumber; ?></strong> — <?php echo htmlspecialchars($initialStart.' - '.$initialEnd); ?>
    </div>

    <div id="loading" class="loading hidden">Beschikbaarheid ophalen...</div>
    <div id="time-slots-container" class="cards-wrapper"></div>

    <input type="hidden" name="starttijd" id="starttijd" value="<?php echo htmlspecialchars($booking['starttijd']); ?>">
    <input type="hidden" name="eindtijd" id="eindtijd" value="<?php echo htmlspecialchars($booking['eindtijd']); ?>">
    <input type="hidden" name="baan_id" id="baan_id" value="<?php echo (int)$initialLaneId; ?>">
    <input type="hidden" name="is_magic_bowlen" id="is_magic_bowlen" value="<?php echo (int)$initialMagic; ?>">

    <h2>Stap 4: Opties & extra's</h2>
    <hr class="divider">

    <div class="cards-wrapper">
      <?php foreach ($extras as $extra): 
        $optie_id = (int)$extra['optie_id'];
        $selected = in_array($optie_id, $sel, true);
      ?>
        <div class="card">
          <div class="card-time"><?php echo htmlspecialchars($extra['naam']); ?></div>
          <?php if (!empty($extra['beschrijving'])): ?>
            <div class="card-lane" style="margin-bottom:10px;"><?php echo htmlspecialchars($extra['beschrijving']); ?></div>
          <?php endif; ?>
          <div class="card-price">€ <?php echo number_format((float)$extra['meerprijs'], 2, ',', '.'); ?></div>

          <button type="button" class="btn-select" onclick="toggleExtra(this, <?php echo $optie_id; ?>)">
            Kies <span class="btn-select-icon <?php echo $selected ? 'selected' : ''; ?>"><?php echo $selected ? '&#10003;' : '-'; ?></span>
          </button>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Overzicht -->
    <h2>Overzicht</h2>
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
        <span>Nieuw totaal (indicatie)</span>
        <span id="ov-total">€ 0,00</span>
      </div>

      <div class="confirm-btn-wrapper">
        <a class="btn-cancel" href="medewerkers_dashboard.php?date=<?php echo htmlspecialchars($initialDate); ?>">Terug</a>
        <button type="submit" class="btn-confirm" id="confirmBtn">Opslaan</button>
      </div>
    </div>
  </form>
</div>

<script>
  // init
  let children = <?php echo (int)$initialChildren; ?>;

  let selectedSlot = {
    baanId: <?php echo (int)$initialLaneId; ?>,
    start: "<?php echo htmlspecialchars($initialStart); ?>",
    end: "<?php echo htmlspecialchars($initialEnd); ?>",
    price: 0,
    isMagic: <?php echo (int)$initialMagic; ?>,
    baan_nummer: <?php echo (int)$initialLaneNumber; ?>
  };

  let selectedExtras = <?php echo json_encode($sel, JSON_UNESCAPED_UNICODE); ?>;
  let availableSlots = [];
  let extrasData = <?php echo json_encode($extras, JSON_UNESCAPED_UNICODE); ?>;

  function escapeHtml(str){ return String(str ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s])); }

  function changeCount(type, delta){
    if(type === 'adults'){
      adults = Math.max(0, Math.min(8, adults + delta));
      document.getElementById('adults-count').textContent = adults;
      document.getElementById('aantal_volwassenen').value = adults;
    } else {
      children = Math.max(0, Math.min(8, children + delta));
      document.getElementById('children-count').textContent = children;
      document.getElementById('aantal_kinderen').value = children;
    }

    if(adults + children > 8){
      if(type === 'adults'){
        adults = Math.max(0, adults - delta);
        document.getElementById('adults-count').textContent = adults;
        document.getElementById('aantal_volwassenen').value = adults;
      } else {
        children = Math.max(0, children - delta);
        document.getElementById('children-count').textContent = children;
        document.getElementById('aantal_kinderen').value = children;
      }
      alert('Maximum 8 personen per baan!');
    }

    updateAvailability();
    updateOverview();
  }

  function updateAvailability(){
    const datum = document.getElementById('datum').value;
    const duur = parseInt(document.querySelector('input[name="duur_uren"]:checked').value);

    if(!datum) return;

    document.getElementById('loading').classList.remove('hidden');
    document.getElementById('time-slots-container').innerHTML = '';

    const hasChildren = children > 0 ? 1 : 0;

    fetch(`get_available_slots.php?date=${encodeURIComponent(datum)}&duration=${encodeURIComponent(duur)}&hasChildren=${encodeURIComponent(hasChildren)}`)
      .then(r => r.json())
      .then(data => {
        document.getElementById('loading').classList.add('hidden');
        if(data.success){
          availableSlots = data.timeSlots || [];
          renderTimeSlots();
        } else {
          document.getElementById('time-slots-container').innerHTML = '<div class="alert alert-info">Geen beschikbare tijden.</div>';
        }
      })
      .catch(() => {
        document.getElementById('loading').classList.add('hidden');
        alert('Fout bij ophalen beschikbaarheid');
      });
  }

  function renderTimeSlots(){
    const container = document.getElementById('time-slots-container');
    container.innerHTML = '';

    if(!availableSlots.length){
      container.innerHTML = '<div class="alert alert-info">Geen beschikbare tijden.</div>';
      return;
    }

    availableSlots.forEach(slot => {
      if(!slot.available) return;

      const card = document.createElement('div');
      card.className = 'card';

      const isSelected = (String(slot.baan_id) === String(selectedSlot.baanId) &&
                          slot.start === selectedSlot.start &&
                          slot.end === selectedSlot.end);

      card.innerHTML = `
        <div class="card-time">${slot.start} - ${slot.end}</div>
        <div class="card-lane">Baan ${slot.baan_nummer} ${slot.is_kinderbaan ? '<span class="badge-bumpers">👶 Hekjes</span>' : ''}</div>
        <div class="card-price">€ ${Number(slot.price).toFixed(2).replace('.', ',')}</div>
        ${slot.is_magic_bowling ? '<div class="badge-magic">✨ Magic Bowling</div>' : ''}
        <button type="button" class="btn-select"
          onclick="selectTimeSlot(event, ${slot.baan_id}, '${slot.start}', '${slot.end}', ${Number(slot.price)}, ${slot.is_magic_bowling ? 1 : 0}, ${slot.baan_nummer})">
          Kies <span class="btn-select-icon ${isSelected ? 'selected' : ''}">${isSelected ? '&#10003;' : '-'}</span>
        </button>
      `;
      container.appendChild(card);
    });
  }

  function selectTimeSlot(ev, baanId, start, end, price, isMagic, baanNummer){
    document.querySelectorAll('#time-slots-container .btn-select-icon').forEach(i => { i.classList.remove('selected'); i.innerHTML = '-'; });

    const iconEl = ev.currentTarget.querySelector('.btn-select-icon');
    iconEl.classList.add('selected');
    iconEl.innerHTML = '&#10003;';

    selectedSlot = { baanId, start, end, price:Number(price), isMagic:!!Number(isMagic), baan_nummer: baanNummer };

    document.getElementById('starttijd').value = start + ':00';
    document.getElementById('eindtijd').value = end + ':00';
    document.getElementById('baan_id').value = baanId;
    document.getElementById('is_magic_bowlen').value = Number(isMagic) ? 1 : 0;

    updateOverview();
  }

  function toggleExtra(btn, optieId){
    const icon = btn.querySelector('.btn-select-icon');
    if(icon.classList.contains('selected')){
      icon.classList.remove('selected');
      icon.innerHTML = '-';
      selectedExtras = selectedExtras.filter(id => String(id) !== String(optieId));
    } else {
      icon.classList.add('selected');
      icon.innerHTML = '&#10003;';
      selectedExtras.push(optieId);
    }
    updateOverview();
  }

  function updateOverview(){
    const datum = document.getElementById('datum').value;
    if(datum && selectedSlot){
      const formattedDate = new Date(datum).toLocaleDateString('nl-NL');
      document.getElementById('ov-datetime').innerHTML = `${formattedDate} | ${selectedSlot.start} - ${selectedSlot.end}`;
      document.getElementById('ov-lane').innerHTML = `Baan ${selectedSlot.baan_nummer}`;
    } else {
      document.getElementById('ov-datetime').innerHTML = '-';
      document.getElementById('ov-lane').innerHTML = '-';
    }

    document.getElementById('ov-guests').innerHTML = `${adults} Volwassenen, ${children} Kinderen`;

    let extrasTotal = 0;
    let extrasNames = [];
    selectedExtras.forEach(id => {
      const extra = extrasData.find(e => String(e.optie_id) === String(id));
      if(extra){
        extrasTotal += Number(extra.meerprijs);
        extrasNames.push(extra.naam);
      }
    });

    const extrasRow = document.getElementById('ov-extras-row');
    if(selectedExtras.length > 0){
      extrasRow.classList.remove('hidden');
      document.getElementById('ov-extras').innerHTML = extrasNames.map(escapeHtml).join(', ');
      document.getElementById('ov-extras-price').innerHTML = `€ ${extrasTotal.toFixed(2).replace('.', ',')}`;
    } else {
      extrasRow.classList.add('hidden');
    }

    // Indicatie: slot price komt uit API. Als API geen price geeft, dan 0.
    const slotPrice = selectedSlot?.price ? Number(selectedSlot.price) : 0;
    document.getElementById('ov-datetime-price').innerHTML = slotPrice ? `€ ${slotPrice.toFixed(2).replace('.', ',')}` : '-';

    const total = slotPrice + extrasTotal;
    document.getElementById('ov-total').innerHTML = `€ ${total.toFixed(2).replace('.', ',')}`;

    document.querySelectorAll('input[name="extras[]"]').forEach(el => el.remove());
    selectedExtras.forEach(id => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'extras[]';
      input.value = id;
      document.getElementById('editForm').appendChild(input);
    });
  }

  // listeners
  document.getElementById('datum').addEventListener('change', updateAvailability);
  document.querySelectorAll('input[name="duur_uren"]').forEach(r => r.addEventListener('change', updateAvailability));

  // init
  updateAvailability();
  updateOverview();
</script>
</body>
</html>
