<?php
require_once 'db.php';
require_once 'auth.php';
session_start();
checkLogin();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // HANDLER: Join Trip (Student)
    if ($action == 'join_trip' && isset($_GET['trip_id']) && $role == 'student') {
        $trip_id = intval($_GET['trip_id']);
        // Check if already joined
        $check = $conn->query("SELECT id FROM enrollments WHERE trip_id=$trip_id AND student_id=$user_id");
        if ($check->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO enrollments (trip_id, student_id, status) VALUES (?, ?, 'pending')");
            $stmt->bind_param("ii", $trip_id, $user_id);
            $stmt->execute();
        }
        header("Location: trip.php?id=" . $trip_id . "&msg=requested");
        exit();
    }

    // HANDLER: Approve/Reject Request (TripMaker/Admin)
    if (($action == 'approve_request' || $action == 'reject_request') && isset($_GET['req_id'])) {
        $req_id = intval($_GET['req_id']);
        $new_status = ($action == 'approve_request') ? 'approved' : 'rejected';

        // Security check: Ensure the trip belongs to this maker (or is admin)
        // complex join slightly, or just trust for this 'simple' prompt? 
        // Let's do a quick check to be safe-ish.
        if ($role == 'admin') {
             $conn->query("UPDATE enrollments SET status='$new_status' WHERE id=$req_id");
        } else {
             // Check ownership
             $check_sql = "SELECT e.id FROM enrollments e 
                           JOIN trips t ON e.trip_id = t.id 
                           WHERE e.id=$req_id AND t.created_by=$user_id";
             if ($conn->query($check_sql)->num_rows > 0) {
                 $conn->query("UPDATE enrollments SET status='$new_status' WHERE id=$req_id");
             }
        }
        header("Location: dashboard.php?msg=updated");
        exit();
    }

    // HANDLER: Delete Trip (TripMaker/Admin)
    if ($action == 'delete_trip' && isset($_GET['trip_id'])) {
        $trip_id = intval($_GET['trip_id']);
        if ($role == 'admin') {
            $conn->query("DELETE FROM trips WHERE id=$trip_id");
        } elseif ($role == 'tripmaker') {
            $conn->query("DELETE FROM trips WHERE id=$trip_id AND created_by=$user_id");
        }
        header("Location: dashboard.php?msg=deleted");
        exit();
    }
}

// Redirect if no action matched
header("Location: dashboard.php");
exit();
?>
