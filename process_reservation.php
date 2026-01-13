<?php
session_start();
require 'connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: reserverings_pagina_klant.php");
    exit();
}

// Get user info
$klant_id = $_SESSION['user_id'] ?? null;
$medewerker_id = null;

// Check if this is a medewerker making a reservation for a customer
$is_medewerker_booking = false;
if (isset($_POST['is_medewerker_booking']) && $_POST['is_medewerker_booking'] == '1') {
    $is_medewerker_booking = true;
    $medewerker_id = $_SESSION['user_id'];
    
    // Get or create customer
    $customer_email = trim($_POST['customer_email']);
    $customer_voornaam = trim($_POST['customer_voornaam']);
    $customer_achternaam = trim($_POST['customer_achternaam']);
    $customer_telefoon = trim($_POST['customer_telefoon'] ?? '');
    
    // Check if customer exists
    $stmt = $conn->prepare("SELECT klant_id FROM klant WHERE email = ?");
    $stmt->bind_param("s", $customer_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $klant_id = $row['klant_id'];
    } else {
        // Create new customer with a random password (they can reset it later)
        $random_password = bin2hex(random_bytes(8));
        $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO klant (voornaam, achternaam, email, wachtwoord_hash, telefoon) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $customer_voornaam, $customer_achternaam, $customer_email, $hashed_password, $customer_telefoon);
        $stmt->execute();
        $klant_id = $conn->insert_id;
    }
}

if (!$klant_id) {
    $_SESSION['error'] = "Klant niet gevonden. Log opnieuw in.";
    header("Location: login.php");
    exit();
}

// Get form data
$datum = $_POST['datum'] ?? '';
$starttijd = $_POST['starttijd'] ?? '';
$eindtijd = $_POST['eindtijd'] ?? '';
$baan_id = intval($_POST['baan_id'] ?? 0);
$aantal_volwassenen = intval($_POST['aantal_volwassenen'] ?? 0);
$aantal_kinderen = intval($_POST['aantal_kinderen'] ?? 0);
$duur_uren = intval($_POST['duur_uren'] ?? 1);
$is_magic_bowlen = intval($_POST['is_magic_bowlen'] ?? 0);
$selected_extras = $_POST['extras'] ?? [];

// Validation
if (empty($datum) || empty($starttijd) || empty($eindtijd) || $baan_id == 0) {
    $_SESSION['error'] = "Alle velden zijn verplicht.";
    header("Location: " . ($is_medewerker_booking ? "reserverings_pagina_medewerker.php" : "reserverings_pagina_klant.php"));
    exit();
}

if ($aantal_volwassenen + $aantal_kinderen > 8) {
    $_SESSION['error'] = "Maximum 8 personen per baan.";
    header("Location: " . ($is_medewerker_booking ? "reserverings_pagina_medewerker.php" : "reserverings_pagina_klant.php"));
    exit();
}

// Check if lane is available
$check_query = "
    SELECT COUNT(*) as count 
    FROM reservering 
    WHERE baan_id = ? 
    AND datum = ? 
    AND (
        (starttijd < ? AND eindtijd > ?) OR
        (starttijd >= ? AND starttijd < ?)
    )
";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("isssss", $baan_id, $datum, $eindtijd, $starttijd, $starttijd, $eindtijd);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    $_SESSION['error'] = "Deze baan is niet beschikbaar voor de geselecteerde tijd.";
    header("Location: " . ($is_medewerker_booking ? "reserverings_pagina_medewerker.php" : "reserverings_pagina_klant.php"));
    exit();
}

// Calculate tariff
$dayOfWeek = date('N', strtotime($datum));
$isWeekend = ($dayOfWeek >= 5);
$hour = intval(substr($starttijd, 0, 2));

$tarief_id = null;
$prijs_per_uur = 0;

if ($isWeekend) {
    if ($hour < 18) {
        $tarief_query = "SELECT tarief_id, prijs_per_uur FROM tarief WHERE naam = 'Vr-Zo Middag'";
    } else {
        $tarief_query = "SELECT tarief_id, prijs_per_uur FROM tarief WHERE naam = 'Vr-Zo Avond'";
    }
} else {
    $tarief_query = "SELECT tarief_id, prijs_per_uur FROM tarief WHERE naam = 'Ma-Do'";
}

$result = $conn->query($tarief_query);
if ($result->num_rows > 0) {
    $tarief = $result->fetch_assoc();
    $tarief_id = $tarief['tarief_id'];
    $prijs_per_uur = $tarief['prijs_per_uur'];
}

// Calculate total price
$totaal_prijs = $prijs_per_uur * $duur_uren;

// Add extras prices
foreach ($selected_extras as $optie_id) {
    $stmt = $conn->prepare("SELECT meerprijs FROM optie WHERE optie_id = ?");
    $stmt->bind_param("i", $optie_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $optie = $result->fetch_assoc();
        $totaal_prijs += $optie['meerprijs'];
    }
}

// Insert reservation
$insert_query = "
    INSERT INTO reservering 
    (klant_id, medewerker_id, baan_id, tarief_id, datum, starttijd, eindtijd, duur_uren, 
     aantal_volwassenen, aantal_kinderen, totaal_prijs, is_magic_bowlen) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($insert_query);
$stmt->bind_param(
    "iiiisssiiiid", 
    $klant_id, $medewerker_id, $baan_id, $tarief_id, $datum, $starttijd, $eindtijd, 
    $duur_uren, $aantal_volwassenen, $aantal_kinderen, $totaal_prijs, $is_magic_bowlen
);

if (!$stmt->execute()) {
    $_SESSION['error'] = "Er is een fout opgetreden bij het opslaan van de reservering.";
    header("Location: " . ($is_medewerker_booking ? "reserverings_pagina_medewerker.php" : "reserverings_pagina_klant.php"));
    exit();
}

$reservering_id = $conn->insert_id;

// Insert extras
foreach ($selected_extras as $optie_id) {
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