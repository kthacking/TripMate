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
            --admin-sidebar-width: 250px;
            --admin-primary: #6366F1;
            --admin-primary-dark: #4F46E5;
            --admin-bg: #F1F5F9;
            --admin-sidebar-bg: #0F172A;
            --admin-sidebar-item-active: rgba(99, 102, 241, 0.15);
            --admin-card-bg: #FFFFFF;
            --admin-text-main: #0F172A;
            --admin-text-muted: #64748B;
            --admin-border: #E2E8F0;
            --radius-2xl: 20px;
            --radius-xl: 16px;
            --radius-lg: 10px;
            --shadow-premium: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-active: 0 10px 15px -3px rgba(99, 102, 241, 0.12), 0 4px 6px -2px rgba(99, 102, 241, 0.05);
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 14px;
            background-color: var(--admin-bg);
            margin: 0;
            display: flex;
            color: var(--admin-text-main);
            -webkit-font-smoothing: antialiased;
        }

        /* Premium Sidebar */
        .admin-sidebar {
            width: var(--admin-sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, #0F172A 0%, #1E293B 100%);
            color: #F1F5F9;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1001;
            padding: 0;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.1);
        }

        .admin-logo {
            padding: 30px 25px;
            font-size: 1.35rem;
            font-weight: 800;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            letter-spacing: -0.6px;
        }

        .admin-logo i {
            background: linear-gradient(135deg, #6366F1, #4F46E5);
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.2rem;
            box-shadow: 0 6px 12px rgba(99, 102, 241, 0.3);
            color: white;
        }

        .admin-logo span { 
            background: linear-gradient(to right, #818CF8, #C084FC);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .admin-menu {
            flex: 1;
            padding: 0 22px;
            list-style: none;
            overflow-y: auto;
            margin: 0;
        }

        .admin-menu::-webkit-scrollbar {
            width: 4px;
        }
        .admin-menu::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
        }

        .admin-menu-item {
            margin-bottom: 8px; /* Reduced margin */
        }

        .admin-menu-link {
            display: flex;
            align-items: center;
            padding: 11px 16px;
            border-radius: 12px;
            color: #94A3B8;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.92rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            gap: 12px;
        }

        .admin-menu-link i {
            font-size: 1.3rem;
            transition: transform 0.2s;
        }

        .admin-menu-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(4px);
        }

        .admin-menu-link.active {
            background: var(--admin-primary);
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        
        .admin-menu-link.active i {
            color: white;
        }

        /* Main Content Container */
        .admin-main {
            flex: 1;
            margin-left: var(--admin-sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Sticky Header */
        .admin-top-bar {
            height: 70px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--admin-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .top-bar-left h2 {
            font-size: 1.4rem; /* Reduced font size */
            font-weight: 800;
            color: var(--admin-text-main);
            margin: 0;
            letter-spacing: -0.8px;
            display: flex;
            align-items: center;
            gap: 15px; /* Reduced gap */
        }

        .status-badge-live {
            background: #EEF2FF;
            color: #6366F1;
            padding: 6px 14px; /* Reduced padding */
            border-radius: 40px;
            font-size: 0.7rem; /* Reduced font size */
            font-weight: 800;
            letter-spacing: 0.8px;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #E0E7FF;
        }

        .status-badge-live::before {
            content: '';
            width: 10px;
            height: 10px;
            background: #6366F1;
            border-radius: 50%;
            animation: pulse-live 2s infinite;
        }

        @keyframes pulse-live {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.6); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(99, 102, 241, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 18px; /* Reduced gap */
            padding: 6px 6px 6px 16px; /* Reduced padding */
            background: white;
            border-radius: 16px; /* Reduced radius */
            border: 1px solid var(--admin-border);
            box-shadow: var(--shadow-premium);
        }

        .profile-info {
            text-align: right;
        }

        .profile-name {
            font-weight: 800;
            color: var(--admin-text-main);
            font-size: 0.9rem; /* Reduced font size */
            display: block;
        }

        .profile-role {
            font-size: 0.7rem; /* Reduced font size */
            color: var(--admin-text-muted);
            margin-top: 1px;
            font-weight: 600;
        }

        .profile-avatar {
            width: 38px; /* Reduced size */
            height: 38px; /* Reduced size */
            background: linear-gradient(135deg, #6366F1, #4F46E5);
            border-radius: 12px; /* Reduced radius */
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 1rem; /* Reduced font size */
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .admin-content {
            padding: 35px 40px; /* Reduced padding */
            max-width: 1700px;
            width: 100%;
            box-sizing: border-box;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Global UI Elements */
        /* Main Card System */
        .admin-card {
            background: var(--admin-card-bg);
            border-radius: 16px;
            padding: 22px;
            border: 1px solid var(--admin-border);
            box-shadow: var(--shadow-premium);
            margin-bottom: 25px;
        }

        /* Stat Cards */
        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 16px;
            border: 1px solid var(--admin-border);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
        }

        .stat-card:hover { 
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08); 
            border-color: var(--admin-primary);
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            box-shadow: inset 0 -4px 0 rgba(0,0,0,0.05);
        }

        .stat-label {
            font-size: 0.82rem;
            color: var(--admin-text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--admin-text-main);
            line-height: 1.2;
            margin: 0; /* Ensure margin is reset if needed */
            letter-spacing: -1.5px; /* Keep original letter spacing */
        }

        .stat-footer {
            font-size: 0.85rem;
            color: var(--admin-text-muted);
            border-top: 1px solid #F1F5F9;
            padding-top: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .admin-table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .admin-table th {
            font-weight: 700;
            color: var(--admin-text-muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 22px 30px;
            background: #F8FAFC;
        }

        .admin-table td {
            padding: 24px 30px;
            font-size: 0.95rem;
            border-bottom: 1px solid #F1F5F9;
        }

        .admin-badge {
            padding: 6px 16px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-premium {
            background: var(--admin-primary);
            color: white;
            padding: 14px 28px;
            border-radius: 14px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
        }

        .btn-premium:hover {
            background: var(--admin-primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
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
