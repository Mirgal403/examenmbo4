<?php
session_start();
require 'connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: reserverings_pagina_klant.php");
    exit();
}

$is_medewerker_booking = (isset($_POST['is_medewerker_booking']) && $_POST['is_medewerker_booking'] == '1');


$klant_id = $_SESSION['user_id'] ?? null; // default: klant boekt zelf
$medewerker_id = null;

if ($is_medewerker_booking) {
    if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? false)) {
        $_SESSION['error'] = "Geen toegang.";
        header("Location: login.php");
        exit();
    }

    $medewerker_id = (int)$_SESSION['user_id'];
    $selected_klant_id = (int)($_POST['selected_klant_id'] ?? 0);

    if ($selected_klant_id <= 0) {
        $_SESSION['error'] = "Selecteer eerst een klant.";
        header("Location: reserverings_pagina_medeweker.php");
        exit();
    }

    // check klant bestaat
    $stmt = $conn->prepare("SELECT klant_id FROM klant WHERE klant_id = ?");
    $stmt->bind_param("i", $selected_klant_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $_SESSION['error'] = "Klant niet gevonden.";
        header("Location: reserverings_pagina_medeweker.php");
        exit();
    }

    $klant_id = $selected_klant_id;
}

if (!$klant_id) {
    $_SESSION['error'] = "Klant niet gevonden. Log opnieuw in.";
    header("Location: login.php");
    exit();
}

// ===== Form data =====
$datum = $_POST['datum'] ?? '';
$starttijd = $_POST['starttijd'] ?? '';
$eindtijd = $_POST['eindtijd'] ?? '';
$baan_id = (int)($_POST['baan_id'] ?? 0);
$aantal_volwassenen = (int)($_POST['aantal_volwassenen'] ?? 0);
$aantal_kinderen = (int)($_POST['aantal_kinderen'] ?? 0);
$duur_uren = (int)($_POST['duur_uren'] ?? 1);
$is_magic_bowlen = (int)($_POST['is_magic_bowlen'] ?? 0);
$selected_extras = $_POST['extras'] ?? [];

if (empty($datum) || empty($starttijd) || empty($eindtijd) || $baan_id <= 0) {
    $_SESSION['error'] = "Alle velden zijn verplicht.";
    header("Location: " . ($is_medewerker_booking ? "reserverings_pagina_medeweker.php" : "reserverings_pagina_klant.php"));
    exit();
}

if (($aantal_volwassenen + $aantal_kinderen) <= 0) {
    $_SESSION['error'] = "Kies minimaal 1 persoon.";
    header("Location: " . ($is_medewerker_booking ? "reserverings_pagina_medeweker.php" : "reserverings_pagina_klant.php"));
    exit();
}

if ($aantal_volwassenen + $aantal_kinderen > 8) {
    $_SESSION['error'] = "Maximum 8 personen per baan.";
    header("Location: " . ($is_medewerker_booking ? "reserverings_pagina_medeweker.php" : "reserverings_pagina_klant.php"));
    exit();
}

// ===== Check lane conflict (overlap) =====
$check_query = "
    FROM reservering
    WHERE baan_id = ?
      AND datum = ?
      AND NOT (eindtijd <= ? OR starttijd >= ?)
";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("isss", $baan_id, $datum, $starttijd, $eindtijd);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if ((int)$row['cnt'] > 0) {
    $_SESSION['error'] = "Deze baan is niet beschikbaar voor de geselecteerde tijd.";
    header("Location: " . ($is_medewerker_booking ? "reserverings_pagina_medeweker.php" : "reserverings_pagina_klant.php"));
    exit();
}

// ===== Tarief bepalen =====
$dayOfWeek = (int)date('N', strtotime($datum));
$isWeekend = ($dayOfWeek >= 5);
$hour = (int)substr($starttijd, 0, 2);

$tarief_id = null;
$prijs_per_uur = 0.0;

if ($isWeekend) {
    if ($hour < 18) {
        $tarief_query = "SELECT tarief_id, prijs_per_uur FROM tarief WHERE naam = 'Vr-Zo Middag' LIMIT 1";
    } else {
        $tarief_query = "SELECT tarief_id, prijs_per_uur FROM tarief WHERE naam = 'Vr-Zo Avond' LIMIT 1";
    }
} else {
    $tarief_query = "SELECT tarief_id, prijs_per_uur FROM tarief WHERE naam = 'Ma-Do' LIMIT 1";
}

$result = $conn->query($tarief_query);
if ($result && $result->num_rows > 0) {
    $tarief = $result->fetch_assoc();
    $tarief_id = (int)$tarief['tarief_id'];
    $prijs_per_uur = (float)$tarief['prijs_per_uur'];
} else {
    $_SESSION['error'] = "Tarief niet gevonden in de database.";
    header("Location: " . ($is_medewerker_booking ? "reserverings_pagina_medeweker.php" : "reserverings_pagina_klant.php"));
    exit();
}

// ===== Totaal prijs =====
$totaal_prijs = $prijs_per_uur * $duur_uren;

// extras optellen
foreach ($selected_extras as $optie_id_raw) {
    $optie_id = (int)$optie_id_raw;
    if ($optie_id <= 0) continue;

    $stmt = $conn->prepare("SELECT meerprijs FROM optie WHERE optie_id = ?");
    $stmt->bind_param("i", $optie_id);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r->num_rows > 0) {
        $optie = $r->fetch_assoc();
        $totaal_prijs += (float)$optie['meerprijs'];
    }
}

$insert_query = "
INSERT INTO reservering
(klant_id, medewerker_id, baan_id, tarief_id, datum, starttijd, eindtijd, duur_uren,
 aantal_volwassenen, aantal_kinderen, totaal_prijs, is_magic_bowlen)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($insert_query);

$stmt->bind_param(
    "iiiisssiiidi",
    $klant_id,
    $medewerker_id,
    $baan_id,
    $tarief_id,
    $datum,
    $starttijd,
    $eindtijd,
    $duur_uren,
    $aantal_volwassenen,
    $aantal_kinderen,
    $totaal_prijs,
    $is_magic_bowlen
);

if (!$stmt->execute()) {
    $_SESSION['error'] = "Er is een fout opgetreden bij het opslaan van de reservering.";
    header("Location: " . ($is_medewerker_booking ? "reserverings_pagina_medeweker.php" : "reserverings_pagina_klant.php"));
    exit();
}

$reservering_id = $conn->insert_id;

// ===== Insert extras in reservering_optie =====
foreach ($selected_extras as $optie_id_raw) {
    $optie_id = (int)$optie_id_raw;
    if ($optie_id <= 0) continue;

    $stmt = $conn->prepare("INSERT INTO reservering_optie (reservering_id, optie_id, aantal) VALUES (?, ?, 1)");
    $stmt->bind_param("ii", $reservering_id, $optie_id);
    $stmt->execute();
}

$_SESSION['success'] = "Reservering succesvol aangemaakt!";

if ($is_medewerker_booking) {
    header("Location: medewerkers_dashboard.php");
} else {
    header("Location: klanten_dashboard.php");
}
exit();
?>
