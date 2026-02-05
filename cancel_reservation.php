<?php
session_start();
require 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$reservering_id = intval($_GET['id'] ?? 0);

if ($reservering_id == 0) {
    $_SESSION['error'] = "Ongeldige reservering.";
    if ($_SESSION['is_admin']) {
        header("Location: medewerkers_dashboard.php");
    } else {
        header("Location: klanten_dashboard.php");
    }
    exit();
}

if ($_SESSION['is_admin']) {
    // Medewerker can cancel any reservation
    $query = "SELECT reservering_id FROM reservering WHERE reservering_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $reservering_id);
} else {
    // Klant can only cancel their own reservations
    $klant_id = $_SESSION['user_id'];
    $query = "SELECT reservering_id FROM reservering WHERE reservering_id = ? AND klant_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $reservering_id, $klant_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Reservering niet gevonden of geen toegang.";
    if ($_SESSION['is_admin']) {
        header("Location: medewerkers_dashboard.php");
    } else {
        header("Location: klanten_dashboard.php");
    }
    exit();
}

$delete_extras = $conn->prepare("DELETE FROM reservering_optie WHERE reservering_id = ?");
$delete_extras->bind_param("i", $reservering_id);
$delete_extras->execute();

$delete_scores = $conn->prepare("DELETE FROM score WHERE reservering_id = ?");
$delete_scores->bind_param("i", $reservering_id);
$delete_scores->execute();

// Delete the reservation
$delete_reservation->bind_param("i", $reservering_id);

if ($delete_reservation->execute()) {
    $_SESSION['success'] = "Reservering succesvol geannuleerd.";
} else {
    $_SESSION['error'] = "Er is een fout opgetreden bij het annuleren.";
}

// Redirect back to dashboard
if ($_SESSION['is_admin']) {
    header("Location: medewerkers_dashboard.php");
} else {
    header("Location: klanten_dashboard.php");
}
exit();
?>