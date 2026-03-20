<?php
require_once 'db.php';
require_once 'auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
checkLogin();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Notification Helper
function sendNotification($conn, $user_id, $message, $link = '#') {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $message, $link);
    $stmt->execute();
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // HANDLER: Join Trip (Student)
    if ($action == 'join_trip' && isset($_GET['trip_id']) && $role == 'student') {
        $trip_id = intval($_GET['trip_id']);
        
        // Validation: Check if full or deadline passed
        $trip = $conn->query("SELECT created_by, max_participants, registration_deadline, title FROM trips WHERE id=$trip_id")->fetch_assoc();
        
        // Count participants
        $count = $conn->query("SELECT COUNT(*) as c FROM enrollments WHERE trip_id=$trip_id AND status='approved'")->fetch_assoc()['c'];
        
        if ($trip['max_participants'] > 0 && $count >= $trip['max_participants']) {
            header("Location: trip.php?id=$trip_id&error=full");
            exit();
        }
        if ($trip['registration_deadline'] && strtotime($trip['registration_deadline']) < time()) {
             header("Location: trip.php?id=$trip_id&error=closed");
             exit();
        }

        $check = $conn->query("SELECT id FROM enrollments WHERE trip_id=$trip_id AND student_id=$user_id");
        if ($check->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO enrollments (trip_id, student_id, status) VALUES (?, ?, 'pending')");
            $stmt->bind_param("ii", $trip_id, $user_id);
            $stmt->execute();
            
            // Notify TripMaker
            sendNotification($conn, $trip['created_by'], "New join request for " . $trip['title'], "dashboard.php");
        }
        header("Location: trip.php?id=" . $trip_id . "&msg=requested");
        exit();
    }

    // HANDLER: Approve/Reject Request (TripMaker/Admin)
    if (($action == 'approve_request' || $action == 'reject_request') && isset($_GET['req_id'])) {
        $req_id = intval($_GET['req_id']);
        $new_status = ($action == 'approve_request') ? 'approved' : 'rejected';

        // Fetch Trip & Student Info first to verify and notify
        $sql = "SELECT e.student_id, e.trip_id, t.created_by, t.title 
                FROM enrollments e 
                JOIN trips t ON e.trip_id = t.id 
                WHERE e.id = $req_id";
        $res = $conn->query($sql);
        
        if ($res->num_rows > 0) {
            $data = $res->fetch_assoc();
            
            // Check Access
            if ($role == 'admin' || $data['created_by'] == $user_id) {
                $conn->query("UPDATE enrollments SET status='$new_status' WHERE id=$req_id");
                
                // Notify Student
                if ($new_status == 'approved') {
                    sendNotification($conn, $data['student_id'], "You've been approved for " . $data['title'], "trip.php?id=".$data['trip_id']);
                } else {
                    sendNotification($conn, $data['student_id'], "Your request for " . $data['title'] . " was declined.", "dashboard.php");
                }
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
    
    // HANDLER: Mark Notification Read
    if ($action == 'read_notif' && isset($_GET['notif_id'])) {
        $nid = intval($_GET['notif_id']);
        $conn->query("UPDATE notifications SET is_read=1 WHERE id=$nid AND user_id=$user_id");
        $link = isset($_GET['link']) ? $_GET['link'] : 'dashboard.php';
        header("Location: " . $link);
        exit();
    }
}

header("Location: dashboard.php");
exit();
?>
