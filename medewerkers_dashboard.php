<?php
session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

require 'connect.php';

$today = date('Y-m-d');

// Get today's reservations
$query = "
    SELECT 
        r.reservering_id,
        r.starttijd,
        r.eindtijd,
        r.aantal_volwassenen,
        r.aantal_kinderen,
        r.totaal_prijs,
        r.is_magic_bowlen,
        b.baan_nummer,
        CONCAT(k.voornaam, ' ', k.achternaam) as klant_naam,
        k.telefoon
    FROM reservering r
    JOIN baan b ON r.baan_id = b.baan_id
    JOIN klant k ON r.klant_id = k.klant_id
    WHERE r.datum = ?
    ORDER BY r.starttijd ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

$reservations_today = [];
while ($row = $result->fetch_assoc()) {
    $reservations_today[] = $row;
}

// Count total reservations today
$reservations_count = count($reservations_today);

// Calculate total revenue
$total_revenue = 0;
foreach ($reservations_today as $res) {
    $total_revenue += $res['totaal_prijs'];
}

// Get total lanes count
$lanes_result = $conn->query("SELECT COUNT(*) as total FROM baan");
$lanes_row = $lanes_result->fetch_assoc();
$total_lanes = $lanes_row['total'];

// Get lane statuses (check if in use right now)
$current_time = date('H:i:s');
$lanes_query = "
    SELECT 
        b.baan_id,
        b.baan_nummer,
        b.is_kinderbaan,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM reservering r 
                WHERE r.baan_id = b.baan_id 
                AND r.datum = ? 
                AND r.starttijd <= ? 
                AND r.eindtijd > ?
            ) THEN 1 
            ELSE 0 
        END as in_use
    FROM baan b
    ORDER BY b.baan_nummer
";

$stmt = $conn->prepare($lanes_query);
$stmt->bind_param("sss", $today, $current_time, $current_time);
$stmt->execute();
$lanes_result = $stmt->execute();
$lanes_result = $stmt->get_result();

$lanes = [];
while ($row = $lanes_result->fetch_assoc()) {
    $lanes[] = $row;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8" />
  <title>Medewerkers Dashboard - Bowlingcenter</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <style>
    :root {
      --blue: #007bff;
      --blue-light: #e6f0ff;
      --black: #000000;
      --text-main: #111111;
      --text-muted: #555555;
      --border: #dddddd;
      --bg: #f5f5f5;
      --white: #ffffff;
      --radius-lg: 12px;
      --radius-md: 8px;
      --red: #dc3545;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI",
        sans-serif;
    }

    body {
      background: var(--bg);
      color: var(--text-main);
      padding: 0;
    }

    /* HEADER */
    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 40px;
        background-color: var(--white);
        border-bottom: 1px solid var(--border);
    }
    
    .header-left, .header-right { 
        display: flex; 
        align-items: center; 
    }

    .logo { 
        font-size: 24px; 
        font-weight: 900; 
        color: #999; 
        margin-right: 40px; 
    }
    
    .nav-links a { 
        text-decoration: none; 
        color: var(--text-main); 
        font-weight: 600; 
        margin: 0 15px; 
        font-size: 14px; 
    }
    
    .user-greeting { 
        font-weight: 600; 
        font-size: 14px; 
        margin-right: 20px; 
        color: var(--text-muted);
    }

    .btn-logout { 
        background-color: var(--blue); 
        color: var(--white); 
        padding: 10px 20px; 
        text-decoration: none; 
        border-radius: var(--radius-md); 
        font-weight: 600; 
        font-size: 14px; 
    }

    .dashboard {
      max-width: 1100px;
      margin: 0 auto;
      padding: 24px;
    }

    .dashboard-title {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 16px;
    }

    /* ALERTS */
    .alert {
      padding: 12px 20px;
      margin-bottom: 20px;
      border-radius: 4px;
      font-size: 14px;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    /* STAT CARDS */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 16px;
      margin-bottom: 32px;
    }

    .stat-card {
      background: var(--white);
      border-radius: var(--radius-lg);
      padding: 16px 20px;
      border: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      justify-content: center;
      text-align: left;
    }

    .stat-value {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 6px;
      color: var(--black);
    }

    .stat-label {
      font-size: 14px;
      color: var(--text-muted);
    }

    /* SECTION HEADERS */
    .section-header {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 10px;
      margin-top: 8px;
    }

    /* RESERVATION TABLE */
    .card {
      background: var(--white);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border);
      padding: 16px 20px;
      margin-bottom: 24px;
    }

    .reservations-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 8px;
      font-size: 14px;
    }

    .reservations-table th,
    .reservations-table td {
      padding: 10px 8px;
      text-align: left;
      border-bottom: 1px solid var(--border);
      white-space: nowrap;
    }

    .reservations-table th {
      font-weight: 600;
      color: var(--text-muted);
      font-size: 13px;
    }

    .reservations-table td:last-child {
      text-align: right;
    }

    .badge-lane {
      padding: 4px 10px;
      border-radius: 999px;
      border: 1px solid var(--blue);
      color: var(--blue);
      background: var(--blue-light);
      font-size: 12px;
      font-weight: 500;
      display: inline-block;
    }

    .reservation-customer {
      font-weight: 500;
      display: block;
    }

    .reservation-phone {
      font-size: 12px;
      color: var(--text-muted);
      display: block;
    }

    .btn-cancel {
      background: var(--red);
      color: white;
      border: none;
      padding: 4px 12px;
      font-size: 12px;
      border-radius: 2px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
    }

    .btn-cancel:hover {
      background: #c82333;
    }

    .no-reservations {
      text-align: center;
      padding: 40px;
      color: var(--text-muted);
    }

    .badge-magic {
      background: purple;
      color: white;
      padding: 2px 6px;
      border-radius: 3px;
      font-size: 11px;
      margin-left: 4px;
    }

    /* LANES GRID */
    .lanes-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 16px;
      margin-top: 12px;
    }

    .lane-card {
      background: var(--white);
      border-radius: var(--radius-md);
      border: 1px solid var(--border);
      padding: 12px 14px;
      font-size: 14px;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .lane-card span.lane-name {
      font-weight: 600;
      color: var(--black);
    }

    .lane-status {
      font-size: 13px;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .lane-card.in-use {
      background: var(--blue);
      border-color: var(--blue);
      color: var(--white);
    }

    .lane-card.in-use .lane-name,
    .lane-card.in-use .lane-status {
      color: var(--white);
    }

    /* RESPONSIVE */
    @media (max-width: 900px) {
      .stats-row {
        grid-template-columns: 1fr;
      }

      .lanes-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 600px) {
      body {
        padding: 16px;
      }

      .lanes-grid {
        grid-template-columns: 1fr;
      }

      .reservations-table {
        font-size: 13px;
      }
    }
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

  <main class="dashboard">
    <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success">
        <?php 
        echo htmlspecialchars($_SESSION['success']); 
        unset($_SESSION['success']);
        ?>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-error">
        <?php 
        echo htmlspecialchars($_SESSION['error']); 
        unset($_SESSION['error']);
        ?>
      </div>
    <?php endif; ?>

    <h1 class="dashboard-title">Dashboard medewerkers</h1>

    <!-- STATISTIEKEN -->
    <section class="stats-row">
      <article class="stat-card">
        <div class="stat-value"><?php echo $reservations_count; ?></div>
        <div class="stat-label">Reserveringen vandaag</div>
      </article>

      <article class="stat-card">
        <div class="stat-value"><?php echo $total_lanes; ?></div>
        <div class="stat-label">Totaal banen</div>
      </article>

      <article class="stat-card">
        <div class="stat-value">€ <?php echo number_format($total_revenue, 2, ',', '.'); ?></div>
        <div class="stat-label">Omzet vandaag</div>
      </article>
    </section>

    <!-- RESERVERINGEN VANDAAG -->
    <section class="card">
      <h2 class="section-header">Reserveringen op <?php echo date('d-m-Y'); ?></h2>

      <?php if (count($reservations_today) > 0): ?>
      <table class="reservations-table">
        <thead>
          <tr>
            <th>Tijd</th>
            <th>Baan</th>
            <th>Klant</th>
            <th>Volwassenen</th>
            <th>Kinderen</th>
            <th>Prijs</th>
            <th>Type</th>
            <th>Actie</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reservations_today as $res): ?>
          <tr>
            <td><?php echo substr($res['starttijd'], 0, 5) . ' – ' . substr($res['eindtijd'], 0, 5); ?></td>
            <td><span class="badge-lane">Baan <?php echo $res['baan_nummer']; ?></span></td>
            <td>
              <span class="reservation-customer"><?php echo htmlspecialchars($res['klant_naam']); ?></span>
              <?php if ($res['telefoon']): ?>
              <span class="reservation-phone">📞 <?php echo htmlspecialchars($res['telefoon']); ?></span>
              <?php endif; ?>
            </td>
            <td><?php echo $res['aantal_volwassenen']; ?></td>
            <td><?php echo $res['aantal_kinderen']; ?></td>
            <td>€ <?php echo number_format($res['totaal_prijs'], 2, ',', '.'); ?></td>
            <td>
              <?php 
              echo $res['is_magic_bowlen'] ? '<span class="badge-magic">✨ Magic</span>' : 'Normaal'; 
              ?>
            </td>
            <td>
              <a href="cancel_reservation.php?id=<?php echo $res['reservering_id']; ?>" 
                 class="btn-cancel"
                 onclick="return confirm('Weet je zeker dat je deze reservering wilt annuleren?');">
                Annuleren
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="no-reservations">
        <p>Geen reserveringen vandaag.</p>
      </div>
      <?php endif; ?>
    </section>

    <!-- STATUS BANEN -->
    <section class="card">
      <h2 class="section-header">Status banen (nu)</h2>

      <div class="lanes-grid">
        <?php foreach ($lanes as $lane): ?>
        <div class="lane-card <?php echo $lane['in_use'] ? 'in-use' : ''; ?>">
          <span class="lane-name">Baan <?php echo $lane['baan_nummer']; ?></span>
          <div class="lane-status">
            <span><?php echo $lane['in_use'] ? 'In gebruik' : 'Vrij'; ?></span>
            <?php if ($lane['is_kinderbaan']): ?>
            <span style="margin-left: auto;">👶</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</body>
</html>