<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP

// Connect to MySQL server (not DB yet)
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "<br>Please check your XAMPP MySQL is running.");
}

// Read SQL file
$sql_file = file_get_contents('database.sql');

// Execute multi query
if ($conn->multi_query($sql_file)) {
    echo "<h1>Installation Successful!</h1>";
    echo "<p>Database 'tripmate_db' created and tables set up.</p>";
    echo "<a href='index.php'>Go to Home</a>";
    
    // Clear results to avoid sync issues
    while ($conn->next_result()) {;} 
} else {
    echo "Error creating database: " . $conn->error;
}
$conn->close();
?>
