<?php
require_once 'db.php';
$sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1";
if ($conn->query($sql)) {
    echo "<h1>Migration Success! 'is_active' column added to users table.</h1>";
} else {
    echo "<h1>Migration Failed:</h1> " . $conn->error;
}
echo "<br><a href='admin_users.php'>Return to User Management</a>";
?>
