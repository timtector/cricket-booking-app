<?php
session_start();
include 'db.php';

// Handle booking update or cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_booking'])) {
        // Update booking
        $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
        $booked_by = mysqli_real_escape_string($conn, $_POST['booked_by']);

        $query = "UPDATE bookings SET booked_by='$booked_by' WHERE id='$booking_id'";
        if (mysqli_query($conn, $query)) {
            $success_msg = "Booking updated successfully!";
        } else {
            $error_msg = "Error updating booking: " . mysqli_error($conn);
        }
    } elseif (isset($_POST['cancel_booking'])) {
        // Cancel booking
        $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);

        $query = "DELETE FROM bookings WHERE id='$booking_id'";
        if (mysqli_query($conn, $query)) {
            $success_msg = "Booking canceled successfully!";
        } else {
            $error_msg = "Error canceling booking: " . mysqli_error($conn);
        }
    }
}

// Fetch all nets (pitches)
$pitchesResult = mysqli_query($conn, "SELECT * FROM pitches");
$pitches = [];
while ($row = mysqli_fetch_assoc($pitchesResult)) {
    $pitches[$row['id']] = $row['name'];
}

// Get selected date & net
$selected_date = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : date('Y-m-d');
$selected_pitch = isset($_GET['pitch_id']) ? mysqli_real_escape_string($conn, $_GET['pitch_id']) : 'all';

// Query to fetch bookings based on selection
$query = "SELECT b.id, b.pitch_id, p.name AS pitch_name, b.day, b.time, IFNULL(b.booked_by, '') AS booked_by
          FROM bookings b
          JOIN pitches p ON b.pitch_id = p.id
          WHERE b.day = '$selected_date'";

if ($selected_pitch !== 'all') {
    $query .= " AND b.pitch_id = '$selected_pitch'";
}

$query .= " ORDER BY b.pitch_id, b.time";
$result = mysqli_query($conn, $query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Manage Nets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

<h2 class="text-center mb-4">Manage Net Bookings</h2>

<?php if (isset($success_msg)): ?>
    <div class="alert alert-success"><?= $success_msg; ?></div>
<?php endif; ?>
<?php if (isset($error_msg)): ?>
    <div class="alert alert-danger"><?= $error_msg; ?></div>
<?php endif; ?>

<!-- Date & Net Selection Form -->
<form method="GET" action="" class="mb-4 row g-3">
    <div class="col-md-6">
        <label for="date" class="form-label">Select Date:</label>
        <input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars($selected_date); ?>" required>
    </div>
    <div class="col-md-4">
        <label for="pitch_id" class="form-label">Select Net:</label>
        <select name="pitch_id" id="pitch_id" class="form-select">
            <option value="all" <?= $selected_pitch == 'all' ? 'selected' : ''; ?>>All Nets</option>
            <?php foreach ($pitches as $id => $name): ?>
                <option value="<?= $id; ?>" <?= $selected_pitch == $id ? 'selected' : ''; ?>><?= $name; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Show Bookings</button>
    </div>
</form>

<!-- Booking Table -->
<table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>Net</th>
            <th>Date</th>
            <th>Time</th>
            <th>Booked By</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr class="<?= ($row['booked_by'] !== '' ? 'table-warning' : ''); ?>">
                <td><?= htmlspecialchars($row['pitch_name']); ?></td>
                <td><?= htmlspecialchars($row['day']); ?></td>
                <td><?= htmlspecialchars($row['time']); ?></td>
                <td><?= htmlspecialchars($row['booked_by']); ?></td>
                <td>
                    <!-- Update Booking Form -->
                    <form method="POST" action="" class="d-inline">
                        <input type="hidden" name="booking_id" value="<?= $row['id']; ?>">
                        <input type="text" name="booked_by" class="form-control d-inline w-50" placeholder="Enter new name" value="<?= htmlspecialchars($row['booked_by']); ?>" required>
                        <button type="submit" name="update_booking" class="btn btn-sm btn-success">Update</button>
                    </form>
                    <!-- Cancel Booking Button -->
                    <form method="POST" action="" class="d-inline">
                        <input type="hidden" name="booking_id" value="<?= $row['id']; ?>">
                        <button type="submit" name="cancel_booking" class="btn btn-sm btn-danger">Cancel</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
