<?php
require_once 'db.php';
require_once 'auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

checkAdmin(); // Ensure only admins can access

// Helper function to log actions
function logActivity($conn, $user_id, $action, $details = "") {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $details);
    $stmt->execute();
}

// Fetch settings
$settings = [];
$s_res = $conn->query("SELECT * FROM settings");
while($s = $s_res->fetch_assoc()) {
    $settings[$s['setting_key']] = $s['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMate Admin - Command Center</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            --admin-sidebar-width: 260px;
            --admin-primary: #4F46E5;
            --admin-bg: #F9FAFB;
        }

        body {
            background-color: var(--admin-bg);
            margin: 0;
            display: flex;
        }

        .admin-sidebar {
            width: var(--admin-sidebar-width);
            height: 100vh;
            background: #111827;
            color: #D1D5DB;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1001;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }

        .admin-logo {
            padding: 30px 25px;
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #1F2937;
        }

        .admin-logo span { color: #818CF8; }

        .admin-menu {
            flex: 1;
            padding: 20px 15px;
            list-style: none;
            overflow-y: auto;
        }

        .admin-menu-item {
            margin-bottom: 5px;
        }

        .admin-menu-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 10px;
            color: #9CA3AF;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .admin-menu-link i { font-size: 1.25rem; }

        .admin-menu-link:hover, .admin-menu-link.active {
            background: #1F2937;
            color: white;
        }

        .admin-menu-link.active {
            background: var(--admin-primary);
            color: white;
        }

        .admin-main {
            flex: 1;
            margin-left: var(--admin-sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-top-bar {
            height: 70px;
            background: white;
            border-bottom: 1px solid #E5E7EB;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .admin-content {
            padding: 40px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.2s;
        }

        .stat-card:hover { transform: translateY(-5px); }

        .stat-icon {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .admin-table {
            width: 100%;
            background: white;
            border-radius: 16px;
            border-collapse: collapse;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .admin-table th {
            text-align: left;
            padding: 18px 25px;
            background: #F9FAFB;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #E5E7EB;
        }

        .admin-table td {
            padding: 18px 25px;
            border-bottom: 1px solid #F3F4F6;
            color: #4B5563;
        }

        .admin-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }

        .switch input { opacity: 0; width: 0; height: 0; }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #D1D5DB;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px; width: 18px;
            left: 4px; bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider { background-color: var(--admin-primary); }
        input:checked + .slider:before { transform: translateX(24px); }

        .bulk-actions {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
            background: #EEF2FF;
            padding: 12px 20px;
            border-radius: 12px;
            border: 1px solid #C7D2FE;
        }
    </style>
</head>
<body>

<aside class="admin-sidebar">
    <a href="admin_dashboard.php" class="admin-logo">
        <i class="ri-shield-user-line"></i> Trip<span>Mate</span> Admin
    </a>

    <ul class="admin-menu">
        <li class="admin-menu-item">
            <a href="admin_dashboard.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                <i class="ri-dashboard-3-line"></i> Dashboard
            </a>
        </li>
        <li class="admin-menu-item">
            <a href="admin_users.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? 'active' : ''; ?>">
                <i class="ri-group-line"></i> Users Management
            </a>
        </li>
        <li class="admin-menu-item">
            <a href="admin_trips.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_trips.php' ? 'active' : ''; ?>">
                <i class="ri-plane-line"></i> Trips Control
            </a>
        </li>
        <li class="admin-menu-item">
            <a href="admin_requests.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_requests.php' ? 'active' : ''; ?>">
                <i class="ri-user-add-line"></i> Global Requests
            </a>
        </li>
        <li class="admin-menu-item">
            <a href="admin_media.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_media.php' ? 'active' : ''; ?>">
                <i class="ri-image-line"></i> Media Library
            </a>
        </li>
        <li class="admin-menu-item">
            <a href="admin_analytics.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_analytics.php' ? 'active' : ''; ?>">
                <i class="ri-bar-chart-2-line"></i> Analytics
            </a>
        </li>
        <li class="admin-menu-item">
            <a href="admin_settings.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_settings.php' ? 'active' : ''; ?>">
                <i class="ri-settings-3-line"></i> System Settings
            </a>
        </li>
        <li class="admin-menu-item">
            <a href="admin_logs.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_logs.php' ? 'active' : ''; ?>">
                <i class="ri-history-line"></i> Audit Logs
            </a>
        </li>
    </ul>

    <div style="padding: 20px; border-top: 1px solid #1F2937;">
        <a href="dashboard.php" class="admin-menu-link" style="margin-bottom: 10px;">
            <i class="ri-home-4-line"></i> Back to Site
        </a>
        <a href="logout.php" class="admin-menu-link" style="color: #F87171;">
            <i class="ri-logout-box-r-line"></i> Logout Admin
        </a>
    </div>
</aside>

<div class="admin-main">
    <header class="admin-top-bar">
        <div style="display: flex; align-items: center; gap: 20px;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: #111827;"><?php 
                $titles = [
                    'admin_dashboard.php' => 'Dashboard Overview',
                    'admin_users.php' => 'User Directory',
                    'admin_trips.php' => 'Trip Management',
                    'admin_requests.php' => 'Global Join Requests',
                    'admin_media.php' => 'Shared Media Assets',
                    'admin_analytics.php' => 'Platform Analytics',
                    'admin_settings.php' => 'Global Configurations',
                    'admin_logs.php' => 'Activity Logs'
                ];
                echo $titles[basename($_SERVER['PHP_SELF'])] ?? 'Admin Panel';
            ?></h2>
            <div style="background: #E0E7FF; color: #4338CA; padding: 4px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700;">LIVE MONITORING</div>
        </div>
        
        <div style="display: flex; align-items: center; gap: 25px;">
            <div style="text-align: right;">
                <div style="font-weight: 700; color: #111827;"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                <div style="font-size: 0.75rem; color: #6B7280;">System Administrator</div>
            </div>
            <div style="width: 45px; height: 45px; background: var(--admin-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 1.2rem;">
                <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
            </div>
        </div>
    </header>

    <main class="admin-content">
