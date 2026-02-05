<?php
header('Content-Type: application/json');
require 'connect.php';

$date = $_GET['date'] ?? '';
$duration = intval($_GET['duration'] ?? 1);
$hasChildren = intval($_GET['hasChildren'] ?? 0);

if (!$date) {
    echo json_encode(['error' => 'Date required']);
    exit();
}

$dayOfWeek = date('N', strtotime($date));
$isWeekend = ($dayOfWeek >= 5);

$lanes_query = "SELECT baan_id, baan_nummer, is_kinderbaan FROM baan ORDER BY baan_nummer";
$lanes_result = $conn->query($lanes_query);
$lanes = [];
while ($row = $lanes_result->fetch_assoc()) {
    $lanes[] = $row;
}

$timeSlots = [];
$startHour = 14;
$endHour = 24;

for ($hour = $startHour; $hour < $endHour; $hour++) {
    $startTime = sprintf('%02d:00', $hour);
    $endHour_slot = $hour + $duration;
    
    // Don't allow slots that go past 24:00
    
    $endTime = sprintf('%02d:00', $endHour_slot);
    
    // Determine tariff and price
    $price = 0;
    
    if ($isWeekend) {
        if ($hour < 18) {
            $tariff = 'Vr-Zo Middag';
            $price = 28.00 * $duration;
        } else {
            $tariff = 'Vr-Zo Avond';
            $price = 33.50 * $duration;
        }
    } else {
        $tariff = 'Ma-Do';
        $price = 24.00 * $duration;
    }
    
    // Check Magic Bowling (Friday-Sunday 22:00-24:00)
    $isMagicBowling = ($isWeekend && $hour >= 22);
    // Check availability for each lane
    foreach ($lanes as $lane) {
        $baan_id = $lane['baan_id'];
        $is_kinderbaan = $lane['is_kinderbaan'];
        
        $check_query = "SELECT COUNT(*) AS cnt FROM reservering WHERE baan_id = ? AND datum = ? AND NOT (eindtijd <= ? OR starttijd >= ?)";
        
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("isss", $baan_id, $date, $endTime, $startTime);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $isAvailable = ($row['count'] == 0);
        
        // Prioritize lanes 1 and 8 for children
        $isPriority = ($hasChildren && $is_kinderbaan);
        
        $timeSlots[] = [
            'end' => $endTime,
            'baan_id' => $baan_id,
            'baan_nummer' => $lane['baan_nummer'],
            'is_kinderbaan' => $is_kinderbaan,
            'tarief' => $tariff,
            'price' => $price,
            'available' => $isAvailable,
            'is_magic_bowling' => $isMagicBowling,
            'is_priority' => $isPriority
        ];
    }
}

echo json_encode([
    'success' => true,
    'date' => $date,
    'duration' => $duration,
    'timeSlots' => $timeSlots,
    'isWeekend' => $isWeekend
]);
?>