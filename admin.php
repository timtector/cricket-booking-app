<?php
session_start();
include 'db.php';

// Only allow admin access (adjust logic as needed)
//if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//    header("Location: index.php");
//    exit();
//}

// Process admin booking update if form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_booking'])) {
    $pitch_id  = mysqli_real_escape_string($conn, $_POST['pitch_id']);
    $day       = mysqli_real_escape_string($conn, $_POST['day']);
    $time      = mysqli_real_escape_string($conn, $_POST['time']);
    $booked_by = mysqli_real_escape_string($conn, $_POST['booked_by']);
    
    // Insert or update the booking.
    $query = "INSERT INTO bookings (pitch_id, day, time, booked_by)
              VALUES ('$pitch_id', '$day', '$time', '$booked_by')
              ON DUPLICATE KEY UPDATE booked_by='$booked_by'";
              
    if (mysqli_query($conn, $query)) {
        $success_msg = "Slot booked/updated successfully!";
    } else {
        $error_msg = "Error booking slot: " . mysqli_error($conn);
    }
}

// Allow admin to select a date. Default to today.
$selected_date = isset($_GET['date']) 
    ? mysqli_real_escape_string($conn, $_GET['date']) 
    : date('Y-m-d');

// First, retrieve all individual nets (pitches) for later use.
$pitchesResult = mysqli_query($conn, "SELECT * FROM pitches");
$pitches = [];
while ($row = mysqli_fetch_assoc($pitchesResult)) {
    $pitches[$row['id']] = $row;
}

// Fetch all booking times for the selected date (joined with pitches and any booking info).
// Notice the use of SUBSTRING(b.time, 1, 5) so that "08:00:00" becomes "08:00"
$query = "SELECT bt.*, p.name AS pitch_name, IFNULL(b.booked_by, '') AS booked_by
          FROM booking_times bt
          JOIN pitches p ON bt.pitch_id = p.id
          LEFT JOIN bookings b 
            ON bt.pitch_id = b.pitch_id 
           AND bt.day = b.day 
           AND bt.start_time = SUBSTRING(b.time, 1, 5)
          WHERE bt.day = '$selected_date'
          ORDER BY bt.pitch_id, bt.start_time";
$result = mysqli_query($conn, $query);

$data = [];
if (mysqli_num_rows($result) > 0) {
    // If records exist, use them.
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
} else {
    // No records in booking_times for this day? Auto‑generate time slots.
    foreach ($pitches as $pitch_id => $pitch) {
        for ($hour = 8; $hour < 20; $hour++) {
            $start_time = sprintf("%02d:00", $hour);
            $end_time   = sprintf("%02d:00", $hour + 1);
            $data[] = [
                'pitch_id'   => $pitch_id,
                'day'        => $selected_date,
                'start_time' => $start_time,
                'end_time'   => $end_time,
                'pitch_name' => $pitch['name'],
                'booked_by'  => ''
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Panel - Manage Nets</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .form-inline { 
      display: flex; 
      gap: 5px; 
      align-items: center; 
    }
    .form-inline input { 
      flex: 1; 
    }
  </style>
</head>
<body>
  <div class="container mt-5">
    <h1 class="text-center">Admin Panel - Manage Nets</h1>
    
    <?php if (isset($success_msg)): ?>
      <div class="alert alert-success"><?= $success_msg; ?></div>
    <?php endif; ?>
    <?php if (isset($error_msg)): ?>
      <div class="alert alert-danger"><?= $error_msg; ?></div>
    <?php endif; ?>
    
    <!-- Date Selection Form -->
    <form method="GET" action="" class="mb-4 text-center">
      <label for="date" class="form-label">Select Date:</label>
      <input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars($selected_date); ?>" required>
      <button type="submit" class="btn btn-primary mt-2">Show Slots</button>
    </form>
    
    <!-- Display Booking Times -->
    <h3 class="text-center">Booking Times on <?= htmlspecialchars($selected_date); ?></h3>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Net</th>
          <th>Date</th>
          <th>Start Time</th>
          <th>End Time</th>
          <th>Booked By</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data as $row): ?>
          <!-- Highlight row if the slot is booked -->
          <tr class="<?= ($row['booked_by'] !== '' ? 'table-danger' : ''); ?>">
            <td><?= htmlspecialchars($row['pitch_name']); ?></td>
            <td><?= htmlspecialchars($row['day']); ?></td>
            <td><?= htmlspecialchars($row['start_time']); ?></td>
            <td><?= htmlspecialchars($row['end_time']); ?></td>
            <td><?= htmlspecialchars($row['booked_by']); ?></td>
            <td>
              <!-- Inline form to add/update a booking -->
              <form method="POST" action="" class="form-inline">
                <input type="hidden" name="pitch_id" value="<?= $row['pitch_id']; ?>">
                <input type="hidden" name="day" value="<?= htmlspecialchars($row['day']); ?>">
                <input type="hidden" name="time" value="<?= htmlspecialchars($row['start_time']); ?>">
                <input type="text" name="booked_by" class="form-control" placeholder="Enter name" value="<?= htmlspecialchars($row['booked_by']); ?>" required>
                <button type="submit" name="admin_booking" class="btn btn-sm btn-primary">Book/Update</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
