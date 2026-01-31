<?php
require_once 'db.php';

// SQL to add settings and activity_logs
$sql = "
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
";

// Insert default settings
$default_settings = [
    'registration_enabled' => '1',
    'media_uploads_enabled' => '1',
    'zip_downloads_enabled' => '1',
    'max_upload_size_mb' => '50',
    'maintenance_mode' => '0',
    'site_title' => 'TripMate',
    'auto_approval' => '0'
];

if ($conn->multi_query($sql)) {
    // Collect results to clear out multi_query buffer
    do { if ($res = $conn->store_result()) { $res->free(); } } while ($conn->next_result());
    
    foreach ($default_settings as $key => $val) {
        $conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('$key', '$val')");
    }
    
    echo "<h1>Schema Updated Successfully!</h1>";
    echo "<p>Settings and Activity Logs tables created.</p>";
    echo "<a href='dashboard.php'>Go to Dashboard</a>";
} else {
    echo "<h1>Error updating schema:</h1> " . $conn->error;
}
?>
