<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Debug: Uncomment to check session values
// var_dump($_SESSION); exit();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$pitch_id = isset($_POST['pitch_id']) ? mysqli_real_escape_string($conn, $_POST['pitch_id']) : null;
$day      = isset($_POST['day']) ? mysqli_real_escape_string($conn, $_POST['day']) : null;
$time     = isset($_POST['time']) ? mysqli_real_escape_string($conn, $_POST['time']) : null;

// Use the username from the session, if available
if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    $booked_by = mysqli_real_escape_string($conn, $_SESSION['username']);
} else {
    $booked_by = 'Anonymous';
}

if (!$pitch_id || !$day || !$time) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

// Disable autocommit for transaction handling.
mysqli_autocommit($conn, false);
$errors = [];

if ($pitch_id === 'all') {
    $nets = [1, 2, 3];
    foreach ($nets as $net_id) {
        $sql = "INSERT INTO bookings (pitch_id, day, time, booked_by)
                VALUES ('$net_id', '$day', '$time', '$booked_by')
                ON DUPLICATE KEY UPDATE booked_by='$booked_by'";
        if (!mysqli_query($conn, $sql)) {
            $errors[] = mysqli_error($conn);
        }
    }
} else {
    $sql = "INSERT INTO bookings (pitch_id, day, time, booked_by)
            VALUES ('$pitch_id', '$day', '$time', '$booked_by')
            ON DUPLICATE KEY UPDATE booked_by='$booked_by'";
    if (!mysqli_query($conn, $sql)) {
        $errors[] = mysqli_error($conn);
    }
}

if (count($errors) > 0) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Error booking slot: ' . implode("; ", $errors)]);
    exit();
} else {
    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Booking successful!']);
    exit();
}
?>
