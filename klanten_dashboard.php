<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

require 'connect.php';

$klant_id = $_SESSION['user_id'];

// Get customer reservations
$query = "
    SELECT 
        r.reservering_id,
        r.datum,
        r.starttijd,
        r.eindtijd,
        r.aantal_volwassenen,
        r.aantal_kinderen,
        r.totaal_prijs,
        r.is_magic_bowlen,
        b.baan_nummer,
        GROUP_CONCAT(o.naam SEPARATOR ', ') as extras
    FROM reservering r
    JOIN baan b ON r.baan_id = b.baan_id
    LEFT JOIN reservering_optie ro ON r.reservering_id = ro.reservering_id
    LEFT JOIN optie o ON ro.optie_id = o.optie_id
    WHERE r.klant_id = ?
    GROUP BY r.reservering_id
    ORDER BY r.datum DESC, r.starttijd DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $klant_id);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
$total_spent = 0;
$upcoming_count = 0;
$today = date('Y-m-d');

while ($row = $result->fetch_assoc()) {
    $reservations[] = $row;
    $total_spent += $row['totaal_prijs'];
    if ($row['datum'] >= $today) {
        $upcoming_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8" />
  <title>Mijn Reserveringen</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <style>
    :root {
      --blue: #007bff;
      --blue-dark: #005fd1;
      --black: #000000;
      --text-main: #111111;
      --text-muted: #555555;
      --border: #000000;
      --bg: #ffffff;
      --grey: #e5e5e5;
      --white: #ffffff;
      --green: #28a745;
      --red: #dc3545;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; */
    }

    body {
      background: var(--bg);
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 40px;
        border-bottom: 1px solid #eee;
        background: #fff;
    }
    
    .header-left, .header-right { display: flex; align-items: center; }
    .logo { font-size: 24px; font-weight: 900; color: #999; margin-right: 40px; }
    .nav-links a { text-decoration: none; color: #333; font-weight: 600; margin: 0 15px; font-size: 14px; }
    .user-greeting { font-weight: 600; font-size: 14px; margin-right: 20px; }
    
    .btn-logout { 
        background-color: #1a73e8; 
        color: white; 
        padding: 10px 20px; 
        text-decoration: none; 
        border-radius: 2px; 
        font-weight: 600; 
        font-size: 14px; 
        border: none;
        cursor: pointer;
    }
    /* ----------------------------------------------------- */

    /* MAIN CONTENT */
    .page {
      flex: 1;
      padding: 30px 60px 40px;
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
      display: flex;
      gap: 40px;
      margin-bottom: 40px;
      margin-top: 20px;
    }

    .stat-card {
      min-width: 220px;
      height: 80px;
      border: 2px solid var(--border);
      background: var(--white);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      cursor: pointer;
      transition: background-color 0.2s;
    }

    .stat-card:hover {
      background: #f8f9fa;
    }

    .stat-card.clickable:hover {
      background: var(--blue);
      color: white;
      border-color: var(--blue);
    }

    .stat-card span:first-child {
      font-weight: 600;
      margin-bottom: 4px;
    }

    /* TITLE */
    .page-title {
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 16px;
    }

    /* RESERVATION TABLE */
    .reservations-wrapper {
      background: var(--grey);
      padding: 24px;
      max-width: 100%;
    }

    table.reservations {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }

    table.reservations th,
    table.reservations td {
      padding: 8px 6px;
    }

    table.reservations th {
      font-weight: 600;
      text-align: left;
      border-bottom: 1px solid var(--black);
    }

    table.reservations tr + tr td {
      border-top: 1px solid #c0c0c0;
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

    /* RESPONSIVE */
    @media (max-width: 900px) {
      .page {
        padding: 20px;
      }

      .stats-row {
        flex-direction: column;
        gap: 16px;
      }

      .stat-card {
        width: 100%;
      }
      
      header {
          flex-direction: column;
          gap: 10px;
          padding: 10px;
      }
    }
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

  <!-- PAGE CONTENT -->
  <main class="page">
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

    <!-- STATS -->
    <section class="stats-row">
      <div class="stat-card">
        <span><?php echo $upcoming_count; ?></span>
        <span>Toekomstige Reserveringen</span>
      </div>

      <div class="stat-card clickable" onclick="window.location.href='reserverings_pagina_klant.php'">
        <span>Nieuwe reservering</span>
      </div>

      <div class="stat-card">
        <span>€ <?php echo number_format($total_spent, 2, ',', '.'); ?></span>
        <span>Totaal Uitgegeven</span>
      </div>
    </section>

    <!-- TITLE -->
    <h1 class="page-title">Mijn Reserveringen</h1>

    <!-- RESERVATIONS TABLE -->
    <section class="reservations-wrapper">
      <?php if (count($reservations) > 0): ?>
      <table class="reservations">
        <thead>
          <tr>
            <th>Datum</th>
            <th>Tijd</th>
            <th>Baan</th>
            <th>Spelers</th>
            <th>Prijs</th>
            <th>Extra's</th>
            <th>Actie</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reservations as $res): ?>
          <tr>
            <td><?php echo date('d/m/Y', strtotime($res['datum'])); ?></td>
            <td>
              <?php 
              echo substr($res['starttijd'], 0, 5) . '–' . substr($res['eindtijd'], 0, 5); 
              if ($res['is_magic_bowlen']) {
                echo '<span class="badge-magic">✨ Magic</span>';
              }
              ?>
            </td>
            <td><?php echo $res['baan_nummer']; ?></td>
            <td>
              <?php echo $res['aantal_volwassenen']; ?> Volw.<br />
              <?php echo $res['aantal_kinderen']; ?> Kind<?php echo $res['aantal_kinderen'] != 1 ? 'eren' : ''; ?>
            </td>
            <td>€ <?php echo number_format($res['totaal_prijs'], 2, ',', '.'); ?></td>
            <td><?php echo $res['extras'] ?? 'Geen'; ?></td>
            <td>
              <?php if ($res['datum'] >= $today): ?>
                <a href="cancel_reservation.php?id=<?php echo $res['reservering_id']; ?>" 
                   class="btn-cancel"
                   onclick="return confirm('Weet je zeker dat je deze reservering wilt annuleren?');">
                  Annuleren
                </a>
              <?php else: ?>
                <span style="color: #999; font-size: 12px;">Voltooid</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="no-reservations">
        <p>Je hebt nog geen reserveringen.</p>
        <p><a href="reserverings_pagina_klant.php" style="color: var(--blue);">Maak je eerste reservering!</a></p>
      </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>