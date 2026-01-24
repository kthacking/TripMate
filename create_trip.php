<?php
require_once 'db.php';
require_once 'auth.php';
session_start();
checkTripMaker(); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and Get Inputs
    $title = $conn->real_escape_string($_POST['title']);
    $destination = $conn->real_escape_string($_POST['destination']);
    $image_url = $conn->real_escape_string($_POST['image_url']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $cost = floatval($_POST['cost']);
    $description = $conn->real_escape_string($_POST['description']);
    // New Fields
    $max_participants = intval($_POST['max_participants']);
    $registration_deadline = $_POST['registration_deadline'];
    $trip_type = $conn->real_escape_string($_POST['trip_type']);
    $comfort_level = intval($_POST['comfort_level']);
    $included_items = $conn->real_escape_string($_POST['included_items']);
    $not_included_items = $conn->real_escape_string($_POST['not_included_items']);
    
    $created_by = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO trips (
        title, destination, image_url, start_date, end_date, cost, description, created_by,
        max_participants, registration_deadline, trip_type, comfort_level, included_items, not_included_items
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("sssssdsiississ", 
        $title, $destination, $image_url, $start_date, $end_date, $cost, $description, $created_by,
        $max_participants, $registration_deadline, $trip_type, $comfort_level, $included_items, $not_included_items
    );
    
    if ($stmt->execute()) {
        header("Location: dashboard.php?msg=trip_created");
        exit();
    } else {
        $error = "Error creating trip: " . $conn->error;
    }
}
?>
<?php include 'header.php'; ?>

<div class="container section">
    <div style="max-width: 900px; margin: 0 auto;">
        <h1 style="margin-bottom: 24px;">Create New Trip</h1>
        
        <div class="auth-card" style="max-width: 100%; text-align: left;">
            <form method="POST" action="">
                
                <!-- Basic Info -->
                <h4 style="color: var(--secondary-color); margin-bottom: 16px; border-bottom: 1px solid #edf2f7; padding-bottom: 8px;">Basic Information</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Trip Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Bali Summer Retreat">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Destination</label>
                        <input type="text" name="destination" class="form-control" required placeholder="e.g. Bali, Indonesia">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Cover Image URL</label>
                    <input type="url" name="image_url" class="form-control" placeholder="https://..." required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cost ($)</label>
                        <input type="number" name="cost" class="form-control" required min="0" step="0.01">
                    </div>
                </div>

                <!-- Logistics & Type (New Section) -->
                <h4 style="color: var(--secondary-color); margin: 24px 0 16px; border-bottom: 1px solid #edf2f7; padding-bottom: 8px;">Logistics & Details</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Max Participants</label>
                        <input type="number" name="max_participants" class="form-control" placeholder="e.g. 25" min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Registration Deadline</label>
                        <input type="date" name="registration_deadline" class="form-control">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start;">
                    <div class="form-group">
                        <label class="form-label">Trip Type</label>
                        <select name="trip_type" class="form-select">
                            <option value="Leisure">Leisure</option>
                            <option value="Adventure">Adventure</option>
                            <option value="Educational">Educational</option>
                            <option value="Religious">Religious</option>
                            <option value="Budget">Budget</option>
                            <option value="Luxury">Luxury</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Comfort Level</label>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="comfort_level" value="5" /><label for="star5" title="Luxury">★</label>
                            <input type="radio" id="star4" name="comfort_level" value="4" /><label for="star4" title="Very Comfortable">★</label>
                            <input type="radio" id="star3" name="comfort_level" value="3" checked /><label for="star3" title="Comfortable">★</label>
                            <input type="radio" id="star2" name="comfort_level" value="2" /><label for="star2" title="Basic">★</label>
                            <input type="radio" id="star1" name="comfort_level" value="1" /><label for="star1" title="Rough">★</label>
                        </div>
                    </div>
                </div>

                <!-- Description & Inclusions -->
                <h4 style="color: var(--secondary-color); margin: 24px 0 16px; border-bottom: 1px solid #edf2f7; padding-bottom: 8px;">Itinerary & Inclusions</h4>
                
                <div class="form-group">
                    <label class="form-label">Description & Itinerary</label>
                    <textarea name="description" class="form-control" rows="5" required placeholder="Describe the itinerary and highlights..."></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Included Items <i class="ri-check-line" style="color: #48bb78;"></i></label>
                        <textarea name="included_items" class="form-control" rows="3" placeholder="Stay, Food, Transport..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Not Included <i class="ri-close-line" style="color: #F50057;"></i></label>
                        <textarea name="not_included_items" class="form-control" rows="3" placeholder="Personal expenses, Flights..."></textarea>
                    </div>
                </div>

                <div style="margin-top: 30px; display: flex; gap: 16px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Publish Trip</button>
                    <a href="dashboard.php" class="btn btn-outline" style="border:none;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
