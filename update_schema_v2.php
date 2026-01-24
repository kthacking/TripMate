<?php
require_once 'db.php';

// SQL to add new columns and tables
$sql = "
-- Add Timeline and Checklist to trips
ALTER TABLE trips 
ADD COLUMN IF NOT EXISTS timeline TEXT NULL,
ADD COLUMN IF NOT EXISTS checklist TEXT NULL;

-- Create Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
";

if ($conn->multi_query($sql)) {
    echo "<h1>Database Updated Successfully!</h1>";
    echo "<p>Added 'timeline', 'checklist' columns and 'notifications' table.</p>";
    while ($conn->next_result()) {;} // flush
    echo "<a href='dashboard.php'>Go to Dashboard</a>";
} else {
    echo "Error updating database: " . $conn->error;
}
?>
