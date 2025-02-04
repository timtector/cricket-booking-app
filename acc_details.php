<?php
session_start();
include "db.php";
include "nav.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$query = "SELECT p.name AS pitch_name, b.booking_time, b.all_pitches FROM bookings b
          LEFT JOIN pitches p ON b.pitch_id = p.id
          WHERE b.user_id = ? ORDER BY b.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Details</title>
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Account Details</h1>
    <h2>Your Bookings:</h2>
    <?php if (empty($bookings)): ?>
        <p>No bookings found.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Pitch</th>
                    <th>Time</th>
                    <th>All Pitches</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?= htmlspecialchars($booking['all_pitches'] ? 'All Pitches' : $booking['pitch_name']) ?></td>
                        <td><?= htmlspecialchars($booking['booking_time']) ?></td>
                        <td><?= $booking['all_pitches'] ? 'Yes' : 'No' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
