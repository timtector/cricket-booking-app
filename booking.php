<?php
session_start();
include 'db.php'; // Include database connection

// Check if user is logged in
if (!isset($_SESSION['user_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Retrieve all individual nets (assume nets with IDs 1, 2, and 3)
$pitchesResult = mysqli_query($conn, "SELECT * FROM pitches");
$pitches = [];
while ($row = mysqli_fetch_assoc($pitchesResult)) {
    $pitches[$row['id']] = $row;
}

$selected_date = isset($_GET['date']) ? $_GET['date'] : null;
$available_times = []; // Available slots keyed by pitch_id
$booked_slots    = []; // Booked slots keyed by pitch_id (each an array of booked start times)
$allNetsSlots    = []; // For the "All Nets" option

if ($selected_date) {
    // Retrieve available times for the selected date from booking_times table.
    $query = "SELECT * FROM booking_times WHERE day = '$selected_date'";
    $timesResult = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($timesResult)) {
        $available_times[$row['pitch_id']][] = $row;
    }
    
    // Auto-generate available times if none exist for a pitch.
    foreach ($pitches as $pitch_id => $pitch) {
        if (empty($available_times[$pitch_id])) {
            for ($hour = 8; $hour < 20; $hour++) {
                $start = sprintf("%02d:00", $hour);
                $end   = sprintf("%02d:00", $hour + 1);
                $available_times[$pitch_id][] = [
                    'pitch_id'   => $pitch_id,
                    'day'        => $selected_date,
                    'start_time' => $start,  // Format: "08:00"
                    'end_time'   => $end
                ];
            }
        }
    }
    
    // Fetch booked slots for the selected date.
    $bookedQuery = "SELECT pitch_id, time FROM bookings WHERE day = '$selected_date'";
    $bookedResult = mysqli_query($conn, $bookedQuery);
    while ($booked = mysqli_fetch_assoc($bookedResult)) {
        // Convert booked time (e.g. "08:00:00") to "HH:MM" format
        $timeShort = substr($booked['time'], 0, 5);
        $booked_slots[$booked['pitch_id']][] = $timeShort;
    }
    
    // Build the "All Nets" slots (only available if the same slot is free on nets 1, 2, and 3).
    for ($hour = 8; $hour < 20; $hour++) {
        $slotStart = sprintf("%02d:00", $hour);
        $slotEnd   = sprintf("%02d:00", $hour + 1);
        $slotAvailable = true;
        foreach ([1, 2, 3] as $net_id) {
            // Check that the slot exists for the net.
            $exists = false;
            if (isset($available_times[$net_id])) {
                foreach ($available_times[$net_id] as $slot) {
                    if ($slot['start_time'] == $slotStart) {
                        $exists = true;
                        break;
                    }
                }
            }
            if (!$exists) {
                $slotAvailable = false;
                break;
            }
            // Check if the slot is booked on any net.
            if (isset($booked_slots[$net_id]) && in_array($slotStart, $booked_slots[$net_id])) {
                $slotAvailable = false;
                break;
            }
        }
        if ($slotAvailable) {  // Only include if available on all nets.
            $allNetsSlots[] = [
                'pitch_id'   => 'all', // Special value for All Nets
                'day'        => $selected_date,
                'start_time' => $slotStart,
                'end_time'   => $slotEnd,
                'available'  => true
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
  <title>Book a Net</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include 'nav.php'; ?>
  <div class="container mt-5">
    <h1 class="text-center">Book a Net</h1>
    
    <!-- Date Selection Form -->
    <form method="GET" action="" class="mb-4 text-center">
      <label for="date" class="form-label">Select Date:</label>
      <input type="date" name="date" id="date" class="form-control" required value="<?= htmlspecialchars($selected_date); ?>">
      <button type="submit" class="btn btn-primary mt-2">Show Available Nets</button>
    </form>

    <?php if ($selected_date): ?>
      <h3 class="text-center">Available Nets on <?= htmlspecialchars($selected_date); ?></h3>
      <div class="row text-center">
        <?php 
        // Loop through each individual net.
        foreach ($pitches as $pitch_id => $pitch):
          $slots = $available_times[$pitch_id] ?? [];
          $filteredSlots = [];
          if (!empty($slots)) {
              // Filter out any slot that is booked.
              foreach ($slots as $time) {
                  if (isset($booked_slots[$pitch_id]) && in_array($time['start_time'], $booked_slots[$pitch_id])) {
                      continue;
                  }
                  $filteredSlots[] = $time;
              }
          }
        ?>
        <div class="col-md-3 mb-4">
          <h4><?= htmlspecialchars($pitch['name']); ?></h4>
          <form class="bookingForm" method="POST" action="save_booking.php">
            <input type="hidden" name="pitch_id" value="<?= $pitch_id; ?>">
            <input type="hidden" name="day" value="<?= htmlspecialchars($selected_date); ?>">
            <?php if (!empty($filteredSlots)): ?>
              <div class="list-group">
                <?php foreach ($filteredSlots as $time): ?>
                <label class="list-group-item">
                  <input type="radio" name="time" value="<?= $time['start_time']; ?>" required>
                  <?= $time['start_time']; ?> - <?= $time['end_time']; ?>
                </label>
                <?php endforeach; ?>
              </div>
              <button type="submit" class="btn btn-success mt-2">Book</button>
            <?php else: ?>
              <p class="text-danger">No available times.</p>
            <?php endif; ?>
          </form>
        </div>
        <?php endforeach; ?>

        <!-- "All Nets" Option -->
        <div class="col-md-3 mb-4">
          <h4>All Nets</h4>
          <form class="bookingForm" method="POST" action="save_booking.php">
            <input type="hidden" name="pitch_id" value="all">
            <input type="hidden" name="day" value="<?= htmlspecialchars($selected_date); ?>">
            <?php if (!empty($allNetsSlots)): ?>
              <div class="list-group">
                <?php foreach ($allNetsSlots as $slot): ?>
                <label class="list-group-item">
                  <input type="radio" name="time" value="<?= $slot['start_time']; ?>" required>
                  <?= $slot['start_time']; ?> - <?= $slot['end_time']; ?>
                </label>
                <?php endforeach; ?>
              </div>
              <button type="submit" class="btn btn-success mt-2">Book All Nets</button>
            <?php else: ?>
              <p class="text-danger">No available times.</p>
            <?php endif; ?>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
  
  <!-- jQuery for AJAX submission and popup notifications -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    $(document).ready(function(){
      $('.bookingForm').on('submit', function(e){
        e.preventDefault();
        var form = $(this);
        var formData = form.serialize();
        $.ajax({
          url: form.attr('action'),
          type: 'POST',
          data: formData,
          dataType: 'json',
          success: function(response){
            showPopup(response.message, response.success);
            // Optionally, refresh or update available slots via AJAX.
          },
          error: function(){
            showPopup("An unexpected error occurred.", false);
          }
        });
      });
      
      function showPopup(message, success) {
        var alertDiv = $('#ajaxAlert');
        if (alertDiv.length === 0) {
          alertDiv = $('<div id="ajaxAlert" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>');
          $('body').append(alertDiv);
        }
        var alertType = success ? 'alert-success' : 'alert-danger';
        var alertMessage = $('<div class="alert ' + alertType + ' alert-dismissible fade show" role="alert">' + 
                             message + 
                             '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                             '</div>');
        alertDiv.html(alertMessage);
        setTimeout(function(){
          alertMessage.alert('close');
        }, 3000);
      }
    });
  </script>
</body>
</html>
