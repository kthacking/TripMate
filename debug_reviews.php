<?php
require_once 'db.php';
echo "<h2>Current System State for Reviews</h2>";
echo "<table border='1' cellspacing='0' cellpadding='5'>";
echo "<tr><th>Trip ID</th><th>Title</th><th>Status</th><th>Approved Students (IDs)</th></tr>";

$sql = "SELECT t.id, t.title, t.status, GROUP_CONCAT(e.student_id) as students 
        FROM trips t 
        LEFT JOIN enrollments e ON t.id = e.trip_id AND e.status = 'approved'
        GROUP BY t.id";

$res = $conn->query($sql);
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['title']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['students']}</td>";
        echo "</tr>";
    }
}
echo "</table>";
echo "<p><strong>Note:</strong> Only students listed in the last column for trips with status 'completed' can leave a review.</p>";
?>
