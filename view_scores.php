<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

require 'connect.php';

$reservering_id = intval($_GET['id'] ?? 0);
$klant_id = $_SESSION['user_id'];

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
        r.klant_id
    FROM reservering r
    JOIN baan b ON r.baan_id = b.baan_id
    WHERE r.reservering_id = ? AND r.klant_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $reservering_id, $klant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Reservering niet gevonden of geen toegang.";
    header("Location: klanten_dashboard.php");
    exit();
}

$reservation = $result->fetch_assoc();

$today = date('Y-m-d');
if ($reservation['datum'] >= $today) {
    $_SESSION['error'] = "Scores zijn alleen beschikbaar voor voltooide reserveringen.";
    header("Location: klanten_dashboard.php");
    exit();
}

$total_players = $reservation['aantal_volwassenen'] + $reservation['aantal_kinderen'];

$check_scores = "SELECT COUNT(*) as count FROM score WHERE reservering_id = ?";
$stmt = $conn->prepare($check_scores);
$stmt->bind_param("i", $reservering_id);
$stmt->execute();
$count_result = $stmt->get_result();
$row = $count_result->fetch_assoc();

if ($row['count'] == 0) {
    // Generate random scores for each player
    
    for ($i = 1; $i <= $total_players; $i++) {
        $speler_naam = "Persoon " . $i;
        $random_score = rand(0, 300);
        
        $insert_score->bind_param("isi", $reservering_id, $speler_naam, $random_score);
        $insert_score->execute();
    }
}

$get_scores = "SELECT speler_naam, score FROM score WHERE reservering_id = ? ORDER BY score DESC";
$stmt->bind_param("i", $reservering_id);
$stmt->execute();
$scores_result = $stmt->get_result();

$scores = [];
while ($score_row = $scores_result->fetch_assoc()) {
    $scores[] = $score_row;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8" />
  <title>Scores - Bowling Brooklyn</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <style>
    :root {
      --blue: #007bff;
      --blue-dark: #005fd1;
      --black: #000000;
      --text-main: #111111;
      --text-muted: #555555;
      --border: #dddddd;
      --bg: #ffffff;
      --grey: #e5e5e5;
      --white: #ffffff;
      --gold: #FFD700;
      --silver: #C0C0C0;
      --bronze: #CD7F32;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    body {
      background: var(--bg);
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* NAVBAR */
    .navbar {
      width: 100%;
      background: var(--white);
      border-bottom: 1px solid #dddddd;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 40px;
    }

    .navbar-brand {
      font-weight: 700;
      font-size: 18px;
      color: var(--text-main);
    }

    .navbar-links {
      display: flex;
      gap: 32px;
      align-items: center;
      flex: 1;
      justify-content: center;
    }

    .navbar-link {
      font-size: 14px;
      color: var(--text-main);
      text-decoration: none;
    }

    .navbar-link:hover {
      text-decoration: underline;
    }

    .btn-logout {
      background: var(--blue);
      color: var(--white);
      border: none;
      padding: 8px 18px;
      font-size: 14px;
      font-weight: 500;
      border-radius: 2px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
    }

    .btn-logout:hover {
      background: var(--blue-dark);
    }

    /* MAIN CONTENT */
    .page {
      flex: 1;
      padding: 30px 60px 40px;
      max-width: 900px;
      margin: 0 auto;
      width: 100%;
    }

    .page-title {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .page-subtitle {
      font-size: 14px;
      color: var(--text-muted);
      margin-bottom: 24px;
    }

    /* RESERVATION INFO */
    .reservation-info {
      background: var(--grey);
      padding: 20px;
      border-radius: 4px;
      margin-bottom: 32px;
    }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
    }

    .info-item {
      font-size: 14px;
    }

    .info-label {
      color: var(--text-muted);
      font-weight: 600;
    }

    .info-value {
      margin-top: 4px;
    }

    /* SCORES TABLE */
    .scores-section {
      background: var(--white);
      border: 2px solid var(--border);
      border-radius: 4px;
      overflow: hidden;
    }

    .scores-header {
      background: var(--grey);
      padding: 16px 20px;
      border-bottom: 2px solid var(--border);
      font-weight: 600;
      font-size: 16px;
    }

    .scores-table {
      width: 100%;
    }

    .score-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
      transition: background 0.2s;
    }

    .score-row:last-child {
      border-bottom: none;
    }

    .score-row:hover {
      background: #f8f9fa;
    }

    .score-row.rank-1 {
      background: linear-gradient(90deg, rgba(255, 215, 0, 0.1) 0%, transparent 100%);
    }

    .score-row.rank-2 {
      background: linear-gradient(90deg, rgba(192, 192, 192, 0.1) 0%, transparent 100%);
    }

    .score-row.rank-3 {
      background: linear-gradient(90deg, rgba(205, 127, 50, 0.1) 0%, transparent 100%);
    }

    .player-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .rank-badge {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 14px;
      background: var(--grey);
      color: var(--text-main);
    }

    .rank-badge.rank-1 {
      background: var(--gold);
      color: white;
    }

    .rank-badge.rank-2 {
      background: var(--silver);
      color: white;
    }

    .rank-badge.rank-3 {
      background: var(--bronze);
      color: white;
    }

    .player-name {
      font-weight: 600;
      font-size: 16px;
    }

    .score-value {
      font-size: 24px;
      font-weight: 700;
      color: var(--blue);
    }

    /* BACK BUTTON */
    .btn-back {
      display: inline-block;
      margin-top: 24px;
      padding: 10px 20px;
      background: var(--grey);
      color: var(--text-main);
      text-decoration: none;
      border-radius: 2px;
      font-weight: 600;
      font-size: 14px;
      border: 1px solid var(--border);
    }

    .btn-back:hover {
      background: #d0d0d0;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .page {
        padding: 20px;
      }

      .navbar {
        padding: 10px 16px;
        flex-wrap: wrap;
        gap: 8px;
      }

      .navbar-links {
        order: 3;
        width: 100%;
        margin-top: 4px;
        justify-content: center;
      }

      .navbar-brand {
        font-size: 16px;
      }

      .info-grid {
        grid-template-columns: 1fr;
      }

      .score-row {
        padding: 12px 16px;
      }

      .player-name {
        font-size: 14px;
      }

      .score-value {
        font-size: 20px;
      }

      .page-title {
        font-size: 20px;
      }

      .scores-header {
        padding: 12px 16px;
        font-size: 14px;
      }

      .rank-badge {
        width: 28px;
        height: 28px;
        font-size: 12px;
      }
    }
  </style>
</head>
<body>
  <!-- NAVBAR -->
  <header class="navbar">
    <div class="navbar-brand">Bowling Brooklyn</div>

    <nav class="navbar-links">
      <a href="klanten_dashboard.php" class="navbar-link">Dashboard</a>
      <a href="reserverings_pagina_klant.php" class="navbar-link">Reservatie</a>
    </nav>

    <a href="logout.php" class="btn-logout">Uitloggen</a>
  </header>

  <!-- PAGE CONTENT -->
  <main class="page">
    <h1 class="page-title">🎳 Bowling Scores</h1>
    <p class="page-subtitle">Bekijk de scores van deze bowling sessie</p>

    <!-- RESERVATION INFO -->
    <section class="reservation-info">
      <div class="info-grid">
        <div class="info-item">
          <div class="info-label">Datum</div>
          <div class="info-value"><?php echo date('d/m/Y', strtotime($reservation['datum'])); ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Tijd</div>
          <div class="info-value">
            <?php echo substr($reservation['starttijd'], 0, 5) . ' - ' . substr($reservation['eindtijd'], 0, 5); ?>
          </div>
        </div>
        <div class="info-item">
          <div class="info-label">Baan</div>
          <div class="info-value">Baan <?php echo $reservation['baan_nummer']; ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">Spelers</div>
          <div class="info-value">
            <?php echo $reservation['aantal_volwassenen'] . ' Volwassenen, ' . $reservation['aantal_kinderen'] . ' Kinderen'; ?>
          </div>
        </div>
      </div>
    </section>

    <!-- SCORES TABLE -->
    <section class="scores-section">
      <div class="scores-header">
        Resultaten (<?php echo count($scores); ?> spelers)
      </div>
      <div class="scores-table">
        <?php 
        $rank = 1;
        foreach ($scores as $score): 
        ?>
        <div class="score-row rank-<?php echo $rank; ?>">
          <div class="player-info">
            <div class="rank-badge rank-<?php echo $rank; ?>">
              <?php 
              if ($rank == 1) echo '🥇';
              elseif ($rank == 2) echo '🥈';
              elseif ($rank == 3) echo '🥉';
              else echo $rank;
              ?>
            </div>
            <div class="player-name"><?php echo htmlspecialchars($score['speler_naam']); ?></div>
          </div>
          <div class="score-value"><?php echo $score['score']; ?></div>
        </div>
        <?php 
        $rank++;
        endforeach; 
        ?>
      </div>
    </section>

    <a href="klanten_dashboard.php" class="btn-back">← Terug naar Dashboard</a>
  </main>
</body>
</html>