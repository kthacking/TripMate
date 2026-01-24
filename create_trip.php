<?php
require_once 'db.php';
require_once 'auth.php';
session_start();
checkTripMaker(); // Only TripMakers and Admin

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $destination = $conn->real_escape_string($_POST['destination']);
    $image_url = $conn->real_escape_string($_POST['image_url']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $cost = floatval($_POST['cost']);
    $description = $conn->real_escape_string($_POST['description']);
    $created_by = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO trips (title, destination, image_url, start_date, end_date, cost, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssdsi", $title, $destination, $image_url, $start_date, $end_date, $cost, $description, $created_by);
    
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
    <div style="max-width: 800px; margin: 0 auto;">
        <h1 style="margin-bottom: 24px;">Create New Trip</h1>
        
        <div class="auth-card" style="max-width: 100%; text-align: left;">
            <form method="POST" action="">
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
                    <small style="color: var(--text-light);">Use a high-quality landscape image URL.</small>
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

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="5" required placeholder="Describe the itinerary and highlights..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Publish Trip</button>
                <a href="dashboard.php" class="btn btn-outline" style="margin-left: 10px; border:none;">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
