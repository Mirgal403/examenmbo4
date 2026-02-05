<?php
session_start();
if (!isset($_SESSION['user_id']) || !($_SESSION['is_admin'] ?? false)) {
    header("Location: login.php");
    exit();
}
require 'connect.php';
date_default_timezone_set('Europe/Amsterdam');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: medewerkers_dashboard.php");
    exit();
}

$reservering_id = (int)($_POST['reservering_id'] ?? 0);
if ($reservering_id <= 0) {
    $_SESSION['error'] = "Ongeldige reservering.";
    header("Location: medewerkers_dashboard.php");
    exit();
}

// bestaande reservering ophalen (klant_id blijft hetzelfde)
$stmt = $conn->prepare("SELECT klant_id FROM reservering WHERE reservering_id = ? LIMIT 1");
$stmt->bind_param("i", $reservering_id);
$stmt->execute();
$old = $stmt->get_result();
if ($old->num_rows === 0) {
    $_SESSION['error'] = "Reservering niet gevonden.";
    header("Location: medewerkers_dashboard.php");
    exit();
}
$oldRow = $old->fetch_assoc();
$klant_id = (int)$oldRow['klant_id'];

// form data
$datum = $_POST['datum'] ?? '';
$starttijd = $_POST['starttijd'] ?? '';
$eindtijd = $_POST['eindtijd'] ?? '';
$baan_id = (int)($_POST['baan_id'] ?? 0);
$duur_uren = (int)($_POST['duur_uren'] ?? 1);
$aantal_volwassenen = (int)($_POST['aantal_volwassenen'] ?? 0);
$aantal_kinderen = (int)($_POST['aantal_kinderen'] ?? 0);
$is_magic_bowlen = (int)($_POST['is_magic_bowlen'] ?? 0);
$selected_extras = $_POST['extras'] ?? [];

if (empty($datum) || empty($starttijd) || empty($eindtijd) || $baan_id <= 0) {
    $_SESSION['error'] = "Alle velden zijn verplicht (datum/tijd/baan).";
    header("Location: edit_reservation.php?id=".$reservering_id);
    exit();
}

if (($aantal_volwassenen + $aantal_kinderen) <= 0) {
    $_SESSION['error'] = "Kies minimaal 1 persoon.";
    header("Location: edit_reservation.php?id=".$reservering_id);
    exit();
}

if ($aantal_volwassenen + $aantal_kinderen > 8) {
    $_SESSION['error'] = "Maximum 8 personen per baan.";
    header("Location: edit_reservation.php?id=".$reservering_id);
    exit();
}

// overlap check (EXCLUSIEF huidige reservering)
$check = "
SELECT COUNT(*) AS cnt
FROM reservering
WHERE baan_id = ?
  AND datum = ?
  AND reservering_id <> ?
  AND NOT (eindtijd <= ? OR starttijd >= ?)
";
$stmt = $conn->prepare($check);
$stmt->bind_param("isiss", $baan_id, $datum, $reservering_id, $starttijd, $eindtijd);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if ((int)$row['cnt'] > 0) {
    $_SESSION['error'] = "Deze baan is niet beschikbaar voor de gekozen tijd (conflict).";
    header("Location: edit_reservation.php?id=".$reservering_id);
    exit();
}

// tarief bepalen (zelfde logica als process_reservation)
$dayOfWeek = (int)date('N', strtotime($datum));
$isWeekend = ($dayOfWeek >= 5);
$hour = (int)substr($starttijd, 0, 2);

if ($isWeekend) {
    $tarief_query = ($hour < 18)
        ? "SELECT tarief_id, prijs_per_uur FROM tarief WHERE naam = 'Vr-Zo Middag' LIMIT 1"
        : "SELECT tarief_id, prijs_per_uur FROM tarief WHERE naam = 'Vr-Zo Avond' LIMIT 1";
} else {
    $tarief_query = "SELECT tarief_id, prijs_per_uur FROM tarief WHERE naam = 'Ma-Do' LIMIT 1";
}

$result = $conn->query($tarief_query);
if (!$result || $result->num_rows === 0) {
    $_SESSION['error'] = "Tarief niet gevonden.";
    header("Location: edit_reservation.php?id=".$reservering_id);
    exit();
}
$tarief = $result->fetch_assoc();
$tarief_id = (int)$tarief['tarief_id'];
$prijs_per_uur = (float)$tarief['prijs_per_uur'];

$totaal_prijs = $prijs_per_uur * $duur_uren;

foreach ($selected_extras as $optie_id_raw) {
    $optie_id = (int)$optie_id_raw;
    if ($optie_id <= 0) continue;

    $st = $conn->prepare("SELECT meerprijs FROM optie WHERE optie_id = ?");
    $st->bind_param("i", $optie_id);
    $st->execute();
    $r = $st->get_result();
    if ($r->num_rows > 0) {
        $optie = $r->fetch_assoc();
        $totaal_prijs += (float)$optie['meerprijs'];
    }
}

$upd = "
UPDATE reservering
SET baan_id = ?, tarief_id = ?, datum = ?, starttijd = ?, eindtijd = ?, duur_uren = ?,
    aantal_volwassenen = ?, aantal_kinderen = ?, totaal_prijs = ?, is_magic_bowlen = ?,
    medewerker_id = ?
WHERE reservering_id = ?
";
$medewerker_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare($upd);
$stmt->bind_param(
    "iisssiiidiii",
    $baan_id,
    $tarief_id,
    $datum,
    $starttijd,
    $eindtijd,
    $duur_uren,
    $aantal_volwassenen,
    $aantal_kinderen,
    $totaal_prijs,
    $is_magic_bowlen,
    $medewerker_id,
    $reservering_id
);

if (!$stmt->execute()) {
    $_SESSION['error'] = "Updaten mislukt.";
    header("Location: edit_reservation.php?id=".$reservering_id);
    exit();
}

$del = $conn->prepare("DELETE FROM reservering_optie WHERE reservering_id = ?");
$del->bind_param("i", $reservering_id);
$del->execute();

foreach ($selected_extras as $optie_id_raw) {
    $optie_id = (int)$optie_id_raw;
    if ($optie_id <= 0) continue;

    $ins = $conn->prepare("INSERT INTO reservering_optie (reservering_id, optie_id, aantal) VALUES (?, ?, 1)");
    $ins->bind_param("ii", $reservering_id, $optie_id);
    $ins->execute();
}

$_SESSION['success'] = "Reservering succesvol aangepast!";
header("Location: medewerkers_dashboard.php?date=" . urlencode($datum));
exit();
?>
