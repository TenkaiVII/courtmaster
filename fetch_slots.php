<?php
header("Content-Type: application/json; charset=utf-8");
include "db.php";

// --- Input handling ---
$court_id = isset($_GET['court_id']) ? intval($_GET['court_id']) : 0;
$date     = $_GET['date'] ?? '';

if ($date) {
    // Normalize date format (handles 10/05/2025 → 2025-10-05)
    $date = date("Y-m-d", strtotime(str_replace('/', '-', $date)));
}

if (!$court_id || !$date) {
    echo json_encode([]);
    exit;
}

// --- Fetch availability for the selected court/date ---
$stmt = $conn->prepare("
    SELECT start_time, end_time 
    FROM availability 
    WHERE court_id = ? AND available_date = ?
");
$stmt->bind_param("is", $court_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$slots = [];

while ($row = $result->fetch_assoc()) {
    $start_ts = strtotime($row['start_time']);
    $end_ts   = strtotime($row['end_time']);

    // Generate slots in 30-minute increments
    for ($t = $start_ts; $t < $end_ts; $t = strtotime("+30 minutes", $t)) {
        foreach ([30, 60, 120, 180] as $minutes) {
            $next = strtotime("+$minutes minutes", $t);
            if ($next > $end_ts) continue;

            $start24 = date("H:i:s", $t);
            $end24   = date("H:i:s", $next);
            $status  = "available";

            // --- Check if slot overlaps with confirmed bookings ---
            $stmt2 = $conn->prepare("
                SELECT booking_id 
                FROM bookings
                WHERE court_id = ? AND booking_date = ? AND status = 'confirmed'
                AND NOT (? >= end_time OR ? <= start_time)
            ");
            $stmt2->bind_param("isss", $court_id, $date, $start24, $end24);
            $stmt2->execute();
            if ($stmt2->get_result()->num_rows > 0) {
                $status = "booked";
            }

            // --- Check if slot overlaps with maintenance (uses block_date) ---
            $stmt3 = $conn->prepare("
                SELECT id 
                FROM maintenance
                WHERE court_id = ? AND block_date = ?
                AND NOT (? >= end_time OR ? <= start_time)
            ");
            $stmt3->bind_param("isss", $court_id, $date, $start24, $end24);
            $stmt3->execute();
            if ($stmt3->get_result()->num_rows > 0) {
                $status = "maintenance";
            }

            // Convert times for display
            $labelStart = date("g:i A", $t);
            $labelEnd   = date("g:i A", $next);

            // Avoid duplicates
            $exists = false;
            foreach ($slots as $s) {
                if ($s['start'] === $labelStart && $s['end'] === $labelEnd) {
                    $exists = true;
                    break;
                }
            }

            // Add valid slot
            if (!$exists) {
                $slots[] = [
                    "start"    => $labelStart,
                    "end"      => $labelEnd,
                    "duration" => $minutes,
                    "status"   => $status
                ];
            }
        }
    }
}

// --- Output result ---
echo json_encode($slots, JSON_PRETTY_PRINT);
?>
