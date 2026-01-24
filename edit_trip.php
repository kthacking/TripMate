<?php
require_once 'db.php';
require_once 'auth.php';
session_start();
checkTripMaker(); 

// AUTO-FIX: Ensure schema columns exist to prevent crash
try {
    $conn->query("ALTER TABLE trips ADD COLUMN IF NOT EXISTS timeline TEXT NULL");
    $conn->query("ALTER TABLE trips ADD COLUMN IF NOT EXISTS checklist TEXT NULL");
    // Also ensure notifications table exists since v2 update might have failed
    $conn->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message VARCHAR(255) NOT NULL,
        link VARCHAR(255) NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
} catch (Exception $e) { }

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$trip_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch existing trip data
// Admin can edit any, TripMaker only their own
$sql_check = "SELECT * FROM trips WHERE id = $trip_id";
if ($role !== 'admin') {
    $sql_check .= " AND created_by = $user_id";
}

$result = $conn->query($sql_check);
if ($result->num_rows == 0) {
    die("Trip not found or permission denied.");
}

$trip = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and Get Inputs
    $title = $conn->real_escape_string($_POST['title']);
    $destination = $conn->real_escape_string($_POST['destination']);
    $image_url = $conn->real_escape_string($_POST['image_url']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $cost = floatval($_POST['cost']);
    $description = $conn->real_escape_string($_POST['description']);
    
    $max_participants = intval($_POST['max_participants']);
    $registration_deadline = $_POST['registration_deadline'] ?: NULL; // Handle empty date
    $trip_type = $conn->real_escape_string($_POST['trip_type']);
    $comfort_level = intval($_POST['comfort_level']);
    $included_items = $conn->real_escape_string($_POST['included_items']);
    $not_included_items = $conn->real_escape_string($_POST['not_included_items']);
    
    // New fields for Timeline and Checklist
    $timeline = $conn->real_escape_string($_POST['timeline']);
    $checklist = $conn->real_escape_string($_POST['checklist']);

    // Update Query
    $stmt = $conn->prepare("UPDATE trips SET 
        title=?, destination=?, image_url=?, start_date=?, end_date=?, cost=?, description=?,
        max_participants=?, registration_deadline=?, trip_type=?, comfort_level=?, included_items=?, not_included_items=?,
        timeline=?, checklist=?
        WHERE id=?");
    
    // Bind requires reference, date logic slightly tricky with bind_param if NULL, let's just use raw query for simplicity or careful binding
    // For simplicity in this context given previous patterns:
    
    $sql_update = "UPDATE trips SET 
        title='$title', destination='$destination', image_url='$image_url', 
        start_date='$start_date', end_date='$end_date', cost=$cost, description='$description',
        max_participants=$max_participants, 
        registration_deadline=" . ($registration_deadline ? "'$registration_deadline'" : "NULL") . ",
        trip_type='$trip_type', comfort_level=$comfort_level, 
        included_items='$included_items', not_included_items='$not_included_items',
        timeline='$timeline', checklist='$checklist'
        WHERE id=$trip_id";

    if ($conn->query($sql_update) === TRUE) {
        header("Location: trip.php?id=$trip_id&msg=updated");
        exit();
    } else {
        $error = "Error updating trip: " . $conn->error;
    }
}
?>
<?php include 'header.php'; ?>

<div class="container section">
    <div style="max-width: 900px; margin: 0 auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
            <h1>Edit Trip</h1>
            <a href="trip.php?id=<?php echo $trip_id; ?>" class="btn btn-outline">View Trip</a>
        </div>
        
        <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>

        <div class="auth-card" style="max-width: 100%; text-align: left;">
            <form method="POST" action="">
                
                <!-- Basic Info -->
                <h4 style="color: var(--secondary-color); margin-bottom: 16px; border-bottom: 1px solid #edf2f7; padding-bottom: 8px;">Basic Information</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Trip Title</label>
                        <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($trip['title']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Destination</label>
                        <input type="text" name="destination" class="form-control" required value="<?php echo htmlspecialchars($trip['destination']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Cover Image URL</label>
                    <input type="url" name="image_url" class="form-control" required value="<?php echo htmlspecialchars($trip['image_url']); ?>">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" required value="<?php echo $trip['start_date']; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" required value="<?php echo $trip['end_date']; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cost ($)</label>
                        <input type="number" name="cost" class="form-control" required min="0" step="0.01" value="<?php echo $trip['cost']; ?>">
                    </div>
                </div>

                <!-- Logistics & Type -->
                <h4 style="color: var(--secondary-color); margin: 24px 0 16px; border-bottom: 1px solid #edf2f7; padding-bottom: 8px;">Logistics & Details</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Max Participants</label>
                        <input type="number" name="max_participants" class="form-control" min="1" value="<?php echo $trip['max_participants']; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Registration Deadline</label>
                        <input type="date" name="registration_deadline" class="form-control" value="<?php echo $trip['registration_deadline']; ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start;">
                    <div class="form-group">
                        <label class="form-label">Trip Type</label>
                        <select name="trip_type" class="form-select">
                            <?php 
                            $types = ['Leisure', 'Adventure', 'Educational', 'Religious', 'Budget', 'Luxury'];
                            foreach($types as $t) {
                                $selected = ($trip['trip_type'] == $t) ? 'selected' : '';
                                echo "<option value='$t' $selected>$t</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Comfort Level</label>
                        <div class="star-rating">
                            <?php for($i=5; $i>=1; $i--): ?>
                                <input type="radio" id="star<?php echo $i; ?>" name="comfort_level" value="<?php echo $i; ?>" <?php if($trip['comfort_level'] == $i) echo 'checked'; ?> />
                                <label for="star<?php echo $i; ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <h4 style="color: var(--secondary-color); margin: 24px 0 16px; border-bottom: 1px solid #edf2f7; padding-bottom: 8px;">Content & Itinerary</h4>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($trip['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Timeline (One event per line)</label>
                    <textarea name="timeline" class="form-control" rows="5" placeholder="Day 1: Arrival..."><?php echo htmlspecialchars(isset($trip['timeline']) ? $trip['timeline'] : ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Checklist (One item per line)</label>
                    <textarea name="checklist" class="form-control" rows="5" placeholder="Passport..."><?php echo htmlspecialchars(isset($trip['checklist']) ? $trip['checklist'] : ''); ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Included Items</label>
                        <textarea name="included_items" class="form-control" rows="3"><?php echo htmlspecialchars($trip['included_items']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Not Included</label>
                        <textarea name="not_included_items" class="form-control" rows="3"><?php echo htmlspecialchars($trip['not_included_items']); ?></textarea>
                    </div>
                </div>

                <div style="margin-top: 30px; display: flex; gap: 16px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Save Changes</button>
                    <a href="trip.php?id=<?php echo $trip_id; ?>" class="btn btn-outline" style="border:none;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
