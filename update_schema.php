<?php
require_once 'db.php';

// SQL to add new columns
$sql = "ALTER TABLE trips 
ADD COLUMN IF NOT EXISTS max_participants INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS registration_deadline DATE NULL,
ADD COLUMN IF NOT EXISTS trip_type VARCHAR(50) DEFAULT 'Leisure',
ADD COLUMN IF NOT EXISTS comfort_level INT DEFAULT 3,
ADD COLUMN IF NOT EXISTS included_items TEXT NULL,
ADD COLUMN IF NOT EXISTS not_included_items TEXT NULL;";

if ($conn->query($sql) === TRUE) {
    echo "<h1>Database Updated Successfully!</h1>";
    echo "<p>New columns added to 'trips' table.</p>";
    echo "<a href='create_trip.php'>Go to Create Trip</a>";
} else {
    echo "Error updating database: " . $conn->error;
}
?>
