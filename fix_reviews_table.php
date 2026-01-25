<?php
require_once 'db.php';

// Explicitly create the reviews table
$sql = "CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    student_id INT NOT NULL,
    tripmaker_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tripmaker_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_trip_review (trip_id, student_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'reviews' created successfully or already exists.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}
?>
