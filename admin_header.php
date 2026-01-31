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

// Fetch settings with error handling
$settings = [];
$default_settings = [
    'registration_enabled' => '1',
    'media_uploads_enabled' => '1',
    'zip_downloads_enabled' => '1',
    'max_upload_size_mb' => '50',
    'maintenance_mode' => '0',
    'site_title' => 'TripMate',
    'auto_approval' => '0'
];

try {
    $s_res = $conn->query("SELECT * FROM settings");
    if ($s_res) {
        while($s = $s_res->fetch_assoc()) {
            $settings[$s['setting_key']] = $s['setting_value'];
        }
    } else {
        $settings = $default_settings;
    }
} catch (Exception $e) {
    // If table doesn't exist, use defaults to prevent crash
    $settings = $default_settings;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripMate Admin - Command Center</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="script.js" defer></script>
    <style>
        :root {
            --admin-sidebar-width: 280px;
            --admin-primary: #6366F1;
            --admin-primary-light: rgba(99, 102, 241, 0.1);
            --admin-bg: #F8FAFC;
            --admin-sidebar-bg: #0F172A;
            --admin-card-bg: #FFFFFF;
            --admin-text-main: #1E293B;
            --admin-text-muted: #64748B;
            --admin-border: #E2E8F0;
            --radius-xl: 20px;
            --radius-lg: 16px;
            --shadow-subtle: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--admin-bg);
            margin: 0;
            display: flex;
            color: var(--admin-text-main);
        }

        /* Sidebar Styling */
        .admin-sidebar {
            width: var(--admin-sidebar-width);
            height: 100vh;
            background: var(--admin-sidebar-bg);
            color: #F1F5F9;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1001;
            padding: 0;
        }

        .admin-logo {
            padding: 40px 30px;
            font-size: 1.4rem;
            font-weight: 800;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .admin-logo i {
            background: var(--admin-primary);
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 1.3rem;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .admin-logo span { color: #818CF8; }

        .admin-menu {
            flex: 1;
            padding: 0 20px;
            list-style: none;
            overflow-y: auto;
            margin: 0;
        }

        .admin-menu-item {
            margin-bottom: 8px;
        }

        .admin-menu-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            border-radius: 12px;
            color: #94A3B8;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .admin-menu-link i { 
            font-size: 1.2rem;
            transition: transform 0.2s;
        }

        .admin-menu-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.05);
        }

        .admin-menu-link:hover i {
            transform: translateX(3px);
        }

        .admin-menu-link.active {
            background: var(--admin-primary);
            color: white;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }

        /* Main Content Area */
        .admin-main {
            flex: 1;
            margin-left: var(--admin-sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Bar */
        .admin-top-bar {
            height: 90px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 50px;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid var(--admin-border);
        }

        .top-bar-left h2 {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--admin-text-main);
            margin: 0;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .status-badge-live {
            background: #EEF2FF;
            color: #6366F1;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-badge-live::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #6366F1;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(99, 102, 241, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-info {
            text-align: right;
        }

        .profile-name {
            font-weight: 700;
            color: var(--admin-text-main);
            font-size: 1rem;
            display: block;
        }

        .profile-role {
            font-size: 0.8rem;
            color: var(--admin-text-muted);
            margin-top: 2px;
        }

        .profile-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #6366F1, #4F46E5);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
        }

        .admin-content {
            padding: 40px 50px;
            max-width: 1600px;
            width: 100%;
            box-sizing: border-box;
        }

        /* Generic Admin Utilities */
        .admin-card {
            background: var(--admin-card-bg);
            border-radius: var(--radius-xl);
            padding: 30px;
            box-shadow: var(--shadow-subtle);
            border: 1px solid var(--admin-border);
        }

        .stat-card {
            background: white;
            padding: 28px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-subtle);
            display: flex;
            flex-direction: column;
            gap: 20px;
            border: 1px solid var(--admin-border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover { 
            transform: translateY(-5px);
            box-shadow: 0 12px 25px -5px rgba(0, 0, 0, 0.08); 
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .stat-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--admin-text-muted);
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--admin-text-main);
            margin: 10px 0 5px;
            letter-spacing: -1px;
        }

        .stat-footer {
            font-size: 0.8rem;
            color: var(--admin-text-muted);
            border-top: 1px solid #F1F5F9;
            padding-top: 15px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .admin-table th {
            font-weight: 600;
            color: var(--admin-text-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 20px 25px;
        }

        .admin-table td {
            padding: 20px 25px;
            font-size: 0.95rem;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
            border-radius: 12px;
            padding: 8px;
        }

        .dropdown-item {
            border-radius: 8px;
            padding: 10px 15px;
            font-weight: 500;
            transition: all 0.2s;
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
                <i class="ri-dashboard-fill"></i> Dashboard
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
                <i class="ri-image-2-line"></i> Media Library
            </a>
        </li>
        <li class="admin-menu-item">
            <a href="admin_analytics.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_analytics.php' ? 'active' : ''; ?>">
                <i class="ri-bar-chart-fill"></i> Analytics
            </a>
        </li>
        <li class="admin-menu-item" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05);">
            <a href="admin_settings.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_settings.php' ? 'active' : ''; ?>">
                <i class="ri-settings-4-line"></i> Settings
            </a>
        </li>
        <li class="admin-menu-item">
            <a href="admin_logs.php" class="admin-menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_logs.php' ? 'active' : ''; ?>">
                <i class="ri-history-line"></i> Activity Logs
            </a>
        </li>
    </ul>

    <div style="padding: 30px 20px; border-top: 1px solid rgba(255,255,255,0.05);">
        <a href="dashboard.php" class="admin-menu-link" style="margin-bottom: 8px;">
            <i class="ri-home-line"></i> Back to Site
        </a>
        <a href="logout.php" class="admin-menu-link" style="color: #FDA4AF;">
            <i class="ri-logout-circle-r-line"></i> Logout
        </a>
    </div>
</aside>

<div class="admin-main">
    <header class="admin-top-bar">
        <div class="top-bar-left">
            <h2>
                <?php 
                $titles = [
                    'admin_dashboard.php' => 'Dashboard Overview',
                    'admin_users.php' => 'Users Management',
                    'admin_trips.php' => 'Trip Control Center',
                    'admin_requests.php' => 'Join Requests',
                    'admin_media.php' => 'Media Library',
                    'admin_analytics.php' => 'Analytics & Insights',
                    'admin_settings.php' => 'System Settings',
                    'admin_logs.php' => 'Activity Logs'
                ];
                echo $titles[basename($_SERVER['PHP_SELF'])] ?? 'Admin Panel';
                ?>
                <span class="status-badge-live">LIVE MONITORING</span>
            </h2>
        </div>
        
        <div class="admin-profile">
            <div class="profile-info">
                <span class="profile-name"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <span class="profile-role">System Administrator</span>
            </div>
            <div class="profile-avatar">
                <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
            </div>
        </div>
    </header>

    <main class="admin-content">
